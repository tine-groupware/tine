<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Notification
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2008-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 */

/**
 * notifications smtp backend class
 *
 * @package     Tinebase
 * @subpackage  Notification
 */
class Tinebase_Notification_Backend_Smtp implements Tinebase_Notification_Interface
{
    /**
     * the from address
     *
     * @var string
     */
    protected $_fromAddress;
    
    /**
     * the sender name
     *
     * @var string
     */
    protected $_fromName = 'Tine 2.0 notification service';
    
    /**
     * the constructor
     *
     */
    public function __construct()
    {
        $this->_fromAddress = self::getFromAddress();
    }

    static function getFromAddress()
    {
        $smtpConfig = Tinebase_Config::getInstance()->get(Tinebase_Config::SMTP, new Tinebase_Config_Struct(array()))->toArray();
        $fromAddress = (isset($smtpConfig['from']) && ! empty($smtpConfig['from'])) ? $smtpConfig['from'] : '';

        // try to sanitize sender address
        if (empty($fromAddress) && isset($smtpConfig['primarydomain']) && ! empty($smtpConfig['primarydomain'])) {
            $fromAddress = 'noreply@' . $smtpConfig['primarydomain'];
        }
        return $fromAddress;
    }

    public static function getNotificationAddress()
    {
        $emailNotification = Felamimail_Config::getInstance()->{Felamimail_Config::EMAIL_NOTIFICATION_EMAIL_FROM};
        if (!$emailNotification || !preg_match(Tinebase_Mail::EMAIL_ADDRESS_REGEXP, $emailNotification)) {
            $emailNotification = self::getFromAddress();
        }
        return $emailNotification;
    }
    
    /**
     * send a notification as email
     *
     * @param Tinebase_Model_FullUser   $_updater
     * @param Addressbook_Model_Contact $_recipient
     * @param string                    $_subject the subject
     * @param string                    $_messagePlain the message as plain text
     * @param string                    $_messageHtml the message as html
     * @param string|array              $_attachments
     * @throws Zend_Mail_Protocol_Exception
     */
    public function send($_updater, Addressbook_Model_Contact $_recipient, $_subject, $_messagePlain, $_messageHtml = NULL, $_attachments = NULL, $_fireEvent = false, $_actionLogType = null)
    {
        // create mail object
        $mail = new Tinebase_Mail('UTF-8');
        // this seems to break some subjects, removing it for the moment 
        // -> see 0004070: sometimes we can't decode message subjects (calendar notifications?)
        //$mail->setHeaderEncoding(Zend_Mime::ENCODING_BASE64);
        $mail->setSubject($_subject);
        $mail->setBodyText($_messagePlain);
        
        if($_messageHtml !== NULL) {
            $mail->setBodyHtml($_messageHtml);
        }
        
        // add header to identify mails sent by notification service / don't reply to this mail, dear autoresponder ... :)
        $mail->addHeader('X-Tine20-Type', 'Notification');
        $mail->addHeader('Precedence', 'bulk');
        $mail->addHeader('User-Agent', Tinebase_Core::getTineUserAgent('Notification Service'));
        
        if (empty($this->_fromAddress)) {
            Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . ' No notification service address set. Could not send notification.');
            return;
        }
        
        if($_updater !== NULL && ! empty($_updater->accountEmailAddress)) {
            $mail->setFrom($_updater->accountEmailAddress, $_updater->accountFullName);
            if ($_actionLogType === Tinebase_Model_ActionLog::TYPE_DATEV_EMAIL) {
                $mail->setSender($_updater->accountEmailAddress, $_updater->accountFullName);
            } else {
                $mail->setSender($this->_fromAddress, $this->_fromName);
            }
        } else {
            $mail->setFrom($this->_fromAddress, $this->_fromName);
        }
        
        // attachments
        if (is_array($_attachments) || $_attachments instanceof Tinebase_Record_RecordSet) {
            $attachments = &$_attachments;
        } elseif (is_string($_attachments)) {
            $attachments = array(&$_attachments);
        } else {
            $attachments = array();
        }
        foreach ($attachments as $attachment) {
            if ($attachment instanceof Zend_Mime_Part) {
                $mail->addAttachment($attachment);
            }  else if ($attachment instanceof Tinebase_Model_Tree_Node) {
                $content = Tinebase_FileSystem::getInstance()->getNodeContents($attachment);
                $mail->createAttachment(
                    $content,
                    $attachment->contenttype,
                    Zend_Mime::DISPOSITION_ATTACHMENT,
                    Zend_Mime::ENCODING_BASE64,
                    $attachment->name
                );
            } else if (isset($attachment['filename'])) {
                $mail->createAttachment(
                    $attachment['rawdata'], 
                    Zend_Mime::TYPE_OCTETSTREAM,
                    Zend_Mime::DISPOSITION_ATTACHMENT,
                    Zend_Mime::ENCODING_BASE64,
                    $attachment['filename']
                );
            } else {
                $mail->createAttachment($attachment);
            }
        }

        $preferredEmailAddress = $_recipient->getPreferredEmailAddress();
        
        // send
        if (! empty($preferredEmailAddress)) {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(
                __METHOD__ . '::' . __LINE__ . ' Send notification email to ' . $preferredEmailAddress);
            $mail->addTo($preferredEmailAddress, $_recipient->n_fn);
            try {
                Tinebase_Smtp::getInstance()->sendMessage($mail);
            } catch (Zend_Mail_Protocol_Exception $zmpe) {
                // TODO check Felamimail - there is a similar error handling. should be generalized!
                if (preg_match('/^5\.1\.1/', $zmpe->getMessage())) {
                    // User unknown in virtual mailbox table
                    if (Tinebase_Core::isLogLevel(Zend_Log::WARN)) Tinebase_Core::getLogger()->warn(
                        __METHOD__ . '::' . __LINE__ . ' ' . $zmpe->getMessage());
                } else if (preg_match('/^5\.1\.3/', $zmpe->getMessage())) {
                    // Bad recipient address syntax
                    if (Tinebase_Core::isLogLevel(Zend_Log::WARN)) Tinebase_Core::getLogger()->warn(
                        __METHOD__ . '::' . __LINE__ . ' ' . $zmpe->getMessage());
                } else {
                    throw $zmpe;
                }
            }
        } else {
            Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ 
                . ' Not sending notification email to ' . $_recipient->n_fn . '. No email address available.');
        }
    }
}
