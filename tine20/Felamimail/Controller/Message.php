<?php
/**
 * Tine 2.0
 *
 * @package     Felamimail
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2009-2023 Metaways Infosystems GmbH (http://www.metaways.de)
 */


use Hfig\MAPI;
use Hfig\MAPI\OLE\Pear;

/**
 * message controller for Felamimail
 *
 * @package     Felamimail
 * @subpackage  Controller
 */
class Felamimail_Controller_Message extends Tinebase_Controller_Record_Abstract
{
    /**
     * application name (is needed in checkRight())
     *
     * @var string
     */
    protected $_applicationName = 'Felamimail';

    /**
     * holds the instance of the singleton
     *
     * @var Felamimail_Controller_Message
     */
    private static $_instance = NULL;

    /**
     * cache controller
     *
     * @var Felamimail_Controller_Cache_Message
     */
    protected $_cacheController = NULL;

    /**
     * message backend
     *
     * @var Felamimail_Backend_Cache_Sql_Message
     */
    protected $_backend = NULL;

    /**
     * foreign application content types
     *
     * @var array
     */
    protected $_supportedForeignContentTypes = array(
        'Calendar' => Felamimail_Model_Message::CONTENT_TYPE_CALENDAR,
        'Addressbook' => Felamimail_Model_Message::CONTENT_TYPE_VCARD,
    );

    /**
     * the constructor
     *
     * don't use the constructor. use the singleton
     */
    private function __construct()
    {
        $this->_modelName = 'Felamimail_Model_Message';
        $this->_doContainerACLChecks = FALSE;
        $this->_backend = new Felamimail_Backend_Cache_Sql_Message();

        $this->_cacheController = Felamimail_Controller_Cache_Message::getInstance();
    }

    /**
     * don't clone. Use the singleton.
     *
     */
    private function __clone()
    {
    }

    /**
     * the singleton pattern
     *
     * @return Felamimail_Controller_Message
     */
    public static function getInstance()
    {
        if (self::$_instance === NULL) {
            self::$_instance = new Felamimail_Controller_Message();
        }

        return self::$_instance;
    }

    /**
     * Removes accounts where current user has no access to
     *
     * @param Tinebase_Model_Filter_FilterGroup $_filter
     * @param string $_action get|update
     *
     * @todo move logic to Felamimail_Model_MessageFilter
     */
    public function checkFilterACL(Tinebase_Model_Filter_FilterGroup $_filter, $_action = 'get')
    {
        $accountFilter = $_filter->getFilter('account_id');

        // force a $accountFilter filter (ACL) / all accounts of user
        if ($accountFilter === NULL || $accountFilter['operator'] !== 'equals' || !empty($accountFilter['value'])) {
            $_filter->createFilter('account_id', 'equals', array());
        }
    }

    /**
     * append a new message to given folder
     *
     * @param string|Felamimail_Model_Folder $_folder id of target folder
     * @param string|resource $_message full message content
     * @param array $_flags flags for new message
     */
    public function appendMessage($_folder, $_message, $_flags = null)
    {
        $folder = ($_folder instanceof Felamimail_Model_Folder) ? $_folder : Felamimail_Controller_Folder::getInstance()->get($_folder);
        $message = (is_resource($_message)) ? stream_get_contents($_message) : $_message;
        $flags = ($_flags !== null) ? (array)$_flags : null;

        $imapBackend = $this->_getBackendAndSelectFolder(NULL, $folder);
        try {
            $imapBackend->appendMessage($message, Felamimail_Model_Folder::encodeFolderName($folder->globalname), $flags);
        } catch (Zend_Mail_Storage_Exception $zmse) {
            if ($zmse->getMessage() === 'cannot create message, please check if the folder exists and your flags') {
                throw new Tinebase_Exception_NotFound('Folder ' . $folder->globalname . ' not found');
            } else {
                throw $zmse;
            }
        }
    }

    /**
     * get complete message by id
     *
     * @param string|Felamimail_Model_Message $_id
     * @param string $_partId
     * @param string $mimeType
     * @param boolean $_setSeen
     * @return Felamimail_Model_Message
     * @throws Exception
     */
    public function getCompleteMessage($_id, $_partId = NULL, $mimeType = 'configured', $_setSeen = FALSE)
    {
        if ($_id instanceof Felamimail_Model_Message) {
            $message = $_id;
        } else {
            $message = $this->get($_id);
        }

        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ .
            ' Getting message content ' . $message->messageuid
        );

        $folder = Felamimail_Controller_Folder::getInstance()->get($message->folder_id);
        $account = Felamimail_Controller_Account::getInstance()->get($folder->account_id);

        Felamimail_Controller_Account::getInstance()->checkAccess($message, $account);

        $message = $this->_getCompleteMessageContent($message, $account, $_partId, $mimeType);

        if (Felamimail_Controller_Message_Flags::getInstance()->tine20FlagEnabled($message)) {
            Felamimail_Controller_Message_Flags::getInstance()->setTine20Flag($message);
        }

        if (Felamimail_Config::getInstance()->featureEnabled(Felamimail_Config::FEATURE_SPAM_SUSPICION_STRATEGY)) {
            $strategy = Felamimail_Spam_SuspicionStrategy_Factory::factory();
            $message->is_spam_suspicions = $strategy->apply($message);
        }

        if ($_setSeen) {
            Felamimail_Controller_Message_Flags::getInstance()->setSeenFlag($message);
        }

        $this->prepareAndProcessParts($message, $account);

        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(
            __METHOD__ . '::' . __LINE__ . ' ' . print_r($message->toArray(), true));

        return $message;
    }

    /**
     * get message content (body, headers and attachments)
     *
     * @param Felamimail_Model_Message $_message
     * @param Felamimail_Model_Account $_account
     * @param string $_partId
     * @param string $mimeType
     * @return Felamimail_Model_Message
     */
    protected function _getCompleteMessageContent(Felamimail_Model_Message $_message,
                                                  Felamimail_Model_Account $_account = null,
                                                  $_partId = null,
                                                  $mimeType = 'configured')
    {
        if ($mimeType == 'configured') {
            $mimeType = (
                $_account->display_format == Felamimail_Model_Account::DISPLAY_HTML
                || $_account->display_format == Felamimail_Model_Account::DISPLAY_CONTENT_TYPE
            )
                ? Zend_Mime::TYPE_HTML
                : Zend_Mime::TYPE_TEXT;
        }

        $headers = $this->getMessageHeaders($_message, $_partId, true);
        $body = $this->getMessageBody($_message, $_partId, $mimeType, $_account);
        $attachments = array();
        if ($body === '' && $_partId === null && isset($headers['content-transfer-encoding']) && $headers['content-transfer-encoding'] === 'base64') {
            // maybe we have a single part message that needs to be treated like an attachment
            $attachments = $this->getAttachments($_message, 1);
            $_message->has_attachment = true;
        }

        $attachments = array_merge($attachments, $this->getAttachments($_message, $_partId, true));

        if ($_partId === null) {
            $message = $_message;

            $message->body = $body;
            $message->headers = $headers;
            $message->attachments = $attachments;
            // make sure the structure is present
            $message->structure = $message->structure;

        } else {
            // create new object for rfc822 message
            $structure = $_message->getPartStructure($_partId, FALSE);

            $message = new Felamimail_Model_Message(array(
                'account_id' => $_message->account_id,
                'messageuid' => $_message->messageuid,
                'folder_id' => $_message->folder_id,
                'received' => $_message->received,
                'size' => isset($structure['size']) ? $structure['size'] : 0,
                'partid' => $_partId,
                'body' => $body,
                'headers' => $headers,
                'attachments' => $attachments
            ));

            $message->parseHeaders($headers);

            $structure = isset($structure['messageStructure']) ? $structure['messageStructure'] : $structure;
            $message->parseStructure($structure);
        }

        $message->body_content_type_of_body_property_of_this_record = $mimeType;

        return $message;
    }

    /**
     * send reading confirmation for message
     *
     * @param string $messageId
     */
    public function sendReadingConfirmation($messageId)
    {
        /** @var Felamimail_Model_Message $message */
        $message = $this->get($messageId);
        Felamimail_Controller_Account::getInstance()->checkAccess($message);
        $message->sendReadingConfirmation();
    }

    /**
     * prepare message parts that could be interesting for other apps
     *
     * @param Felamimail_Model_Message $_message
     * @param Felamimail_Model_Account $_account
     */
    public function prepareAndProcessParts(Felamimail_Model_Message $_message, Felamimail_Model_Account $_account)
    {
        $preparedParts = new Tinebase_Record_RecordSet('Felamimail_Model_PreparedMessagePart');

        foreach ($this->_supportedForeignContentTypes as $application => $contentType) {
            if (!Tinebase_Application::getInstance()->isInstalled($application) || !Tinebase_Core::getUser()->hasRight($application, Tinebase_Acl_Rights::RUN)) {
                if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
                    . ' ' . $application . ' not installed or access denied.');
                continue;
            }

            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                . ' Looking for ' . $application . '[' . $contentType . '] content ...');

            $parts = $_message->getBodyParts(NULL, $contentType);
            foreach ($parts as $partId => $partData) {
                if ($partData['contentType'] !== $contentType) {
                    continue;
                }

                if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
                    . ' ' . $application . '[' . $contentType . '] content found.');

                $preparedPart = $this->_getForeignMessagePart($_message, $partId, $partData);
                if ($preparedPart) {
                    $this->_processForeignMessagePart($application, $preparedPart);
                    $preparedParts->addRecord(new Felamimail_Model_PreparedMessagePart(array(
                        'id' => $_message->getId() . '_' . $partId,
                        'contentType' => $contentType,
                        'preparedData' => $preparedPart,
                    )));
                }
            }
        }

        $this->_processPGPMimeVersion1Part($_message, $preparedParts);

        // PGP INLINE
        if (strpos($_message->body, '-----BEGIN PGP MESSAGE-----') !== false) {
            preg_match('/(-----BEGIN PGP MESSAGE-----.*-----END PGP MESSAGE-----)/msU', $_message->body, $matches);
            $amored = Felamimail_Message::convertFromHTMLToText($matches[0]);

            $preparedParts->addRecord(new Felamimail_Model_PreparedMessagePart(array(
                'id' => $_message->getId(),
                'contentType' => 'application/pgp-encrypted',
                'preparedData' => $amored,
            )));
        }

        $_message->preparedParts = $preparedParts;
    }

    protected function _processPGPMimeVersion1Part($message, $preparedParts)
    {
        try {
            $structure = $message->structure;
        } catch (Zend_Mail_Protocol_Exception $zmpe) {
            if (Tinebase_Core::isLogLevel(Zend_Log::ERR)) Tinebase_Core::getLogger()->err(__METHOD__ . '::' . __LINE__
                . ' Error while fetching structure, could not process PGP part: ' . $zmpe);
            $structure = null;
        }
        if (isset($structure['contentType']) && $structure['contentType'] == 'multipart/encrypted'
            && isset($structure['parts'][1]['subType'])
            && $structure['parts'][1]['subType'] == 'pgp-encrypted')
        {
            $identification = $this->getMessagePart($message, 1)->getContent();

            if (strpos($identification, 'Version: 1') !== FALSE) {
                $amored = $this->getMessagePart($message, 2)->getContent();

                $preparedParts->addRecord(new Felamimail_Model_PreparedMessagePart(array(
                    'id' => $message->getId() . '_2',
                    'contentType' => 'application/pgp-encrypted',
                    'preparedData' => $amored,
                )));
            }
        }
    }

    /**
     * get foreign message parts
     *
     * - calendar invitations
     * - addressbook vcards
     * - ...
     *
     * @param Felamimail_Model_Message $_message
     * @param string $_partId
     * @param array $_partData
     * @return NULL|Tinebase_Record_Interface
     */
    protected function _getForeignMessagePart(Felamimail_Model_Message $_message, $_partId, $_partData)
    {
        $part = $this->getMessagePart($_message, $_partId);

        $userAgent = (isset($_message->headers['user-agent'])) ? $_message->headers['user-agent'] : NULL;
        $parameters = (isset($_partData['parameters'])) ? $_partData['parameters'] : array();
        $decodedContent = Tinebase_Core::filterInputForDatabase($part->getDecodedContent());

        switch ($part->type) {
            case Felamimail_Model_Message::CONTENT_TYPE_CALENDAR:
                try {
                    $partData = new Calendar_Model_iMIP(array(
                        'id' => $_message->getId() . '_' . $_partId,
                        'ics' => $decodedContent,
                        'method' => (isset($parameters['method'])) ? $parameters['method'] : NULL,
                        'originator' => $_message->from_email,
                        'userAgent' => $userAgent,
                    ));
                } catch (Tinebase_Exception_Record_Validation $terv) {
                    if (Tinebase_Core::isLogLevel(Zend_Log::WARN)) Tinebase_Core::getLogger()->warn(
                        __METHOD__ . '::' . __LINE__ . ' Could not create iMIP: ' . $terv->getMessage());
                    $partData = NULL;
                }
                break;
            default:
                if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) Tinebase_Core::getLogger()->notice(
                    __METHOD__ . '::' . __LINE__ . ' Could not create iMIP of content type ' . $part->type);
                $partData = NULL;
        }

        return $partData;
    }

    /**
     * process foreign iMIP part
     *
     * @param string $_application
     * @param Tinebase_Record_Interface $_iMIP
     * @return mixed
     *
     * @todo use iMIP factory?
     */
    protected function _processForeignMessagePart($_application, $_iMIP)
    {
        $iMIPFrontendClass = $_application . '_Frontend_iMIP';
        if (!class_exists($iMIPFrontendClass)) {
            if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__ . ' iMIP class not found in application ' . $_application);
            return NULL;
        }

        $iMIPFrontend = new $iMIPFrontendClass();
        try {
            $result = $iMIPFrontend->autoProcess($_iMIP);
        } catch (Exception $e) {
            if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Processing failed: ' . $e->getMessage());
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . $e->getTraceAsString());
            $result = NULL;
        }

        return $result;
    }

    /**
     * get iMIP by message and part id
     *
     * @param string $_iMIPId
     * @return Tinebase_Record_Interface
     * @throws Tinebase_Exception_InvalidArgument
     */
    public function getiMIP($_iMIPId)
    {
        if (strpos($_iMIPId, '_') === FALSE) {
            throw new Tinebase_Exception_InvalidArgument('messageId_partId expecetd.');
        }

        list($messageId, $partId) = explode('_', $_iMIPId);

        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
            . ' Fetching ' . $messageId . '[' . $partId . '] part with iMIP data ...');

        /** @var Felamimail_Model_Message $message */
        $message = $this->get($messageId);
        $iMIPPartStructure = $message->getPartStructure($partId);
        $iMIP = $this->_getForeignMessagePart($message, $partId, $iMIPPartStructure);

        return $iMIP;
    }

    /**
     * get message part
     *
     * @param string|Felamimail_Model_Message $_id
     * @param ?string $_partId (the part id, can look like this: 1.3.2 -> returns the second part of third part of first part...)
     * @param boolean $_onlyBodyOfRfc822 only fetch body of rfc822 messages (FALSE to get headers, too)
     * @param ?array $_partStructure (is fetched if NULL/omitted)
     * @return Zend_Mime_Part
     * @throws Felamimail_Exception_IMAPMessageNotFound
     * @throws Tinebase_Exception_AccessDenied
     * @throws Tinebase_Exception_NotFound
     */
    public function getMessagePart($_id, $_partId = null, $_onlyBodyOfRfc822 = false, $_partStructure = null): Zend_Mime_Part
    {
        if ($_id instanceof Felamimail_Model_Message) {
            $message = $_id;
        } else {
            /** @var Felamimail_Model_Message $message */
            $message = $this->get($_id);
        }

        // need to re-fetch part structure of RFC822 messages because message structure is used instead
        $partContentType = ($_partId && isset($message->structure['parts'][$_partId])) ? $message->structure['parts'][$_partId]['contentType'] : NULL;
        $partStructure = ($_partStructure !== NULL
            && $partContentType !== Felamimail_Model_Message::CONTENT_TYPE_MESSAGE_RFC822)
                ? $_partStructure : $message->getPartStructure($_partId, FALSE);

        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__
            . ' ' . print_r($partStructure, TRUE));

        $rawContent = $this->_getPartContent($message, $_partId, $partStructure, $_onlyBodyOfRfc822);
        if (! $partStructure) {
            // try to get part structure from attachment
            $partStructure = $this->_getPartStructureFromAttachments($message, $_partId);
            if (! $partStructure) {
                throw new Tinebase_Exception_NotFound('Part structure not found');
            }
        }
        return $this->_createMimePart($rawContent, $partStructure);
    }

    protected function _getPartStructureFromAttachments($message, $partId)
    {
        $attachments = $message->attachments instanceof Tinebase_Record_RecordSet ? $message->attachments->toArray() : $message->attachments;
        if ($attachments === null) {
            return null;
        }
        $attachment = array_filter($attachments, function($attach) use ($partId) {
            return isset($attach['partId']) && $attach['partId'] === $partId;
        });
        if (count($attachment) >= 1) {
            $partAttachment = array_pop($attachment);
            return [
                'contentType' => $partAttachment['content-type'],
                'description' => $partAttachment['description'],
                'parameters' => [
                    'name' => $partAttachment['filename'],
                ]
            ];
        } else {
            // not found
            return null;
        }
    }

    /**
     * @param Felamimail_Model_Message $message
     * @param string $partId
     * @return string
     */
    public function getMessageRawContent(Felamimail_Model_Message $message, $partId = null)
    {
        $partStructure = $message->getPartStructure(/* partId */ $partId, /* $_useMessageStructure */ FALSE);
        return $this->_getPartContent($message, $partId, $partStructure);
    }

    /**
     * create node filename from message data
     *
     * @param Felamimail_Model_Message $message
     * @return string
     *
     * @todo allow to configure this via twig (config)
     */
    public function getMessageNodeFilename($message)
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
            . ' ' . print_r($message->toArray(), true));

        // remove '/' and '\' from name as this might break paths
        $subject = preg_replace('/[\/\\\]+/', '_', $message->subject);
        $subjectAndMail = $message->from_email . '_' . $subject;
        $fileName = Tinebase_Model_Tree_Node::sanitizeName(str_replace(' ', '_', $message->received->toString('Y-m-d'))
            . '_' . mb_substr($subjectAndMail, 0, 200)
            . '_' . mb_substr(md5($message->messageuid
            . $message->folder_id), 0, 10)
            . '.eml');

        if (class_exists('EFile_Config') && Tinebase_Application::getInstance()->isInstalled(EFile_Config::APP_NAME)) {
            str_replace((array)EFile_Config::getInstance()->{EFile_Config::NODE_NAME_DENIED_SUBSTRINGS}, '-', $fileName);
        }

        return $fileName;
    }

    /**
     * @param Felamimail_Model_Message $message
     * @param string $partId
     * @return Tinebase_Model_TempFile
     */
    public function putRawMessageIntoTempfile($message, $partId = null)
    {
        if ($partId) {
            $part = $this->getMessagePart($message, $partId);
            $rawContent = $part->getDecodedContent();
        } else {
            $rawContent = $this->getMessageRawContent($message, $partId);
        }
        $tempFilename = Tinebase_TempFile::getInstance()->getTempPath();
        file_put_contents($tempFilename, $rawContent);
        return Tinebase_TempFile::getInstance()->createTempFile($tempFilename);
    }

    /**
     * get part content (and update structure) from message part
     *
     * @param Felamimail_Model_Message $_message
     * @param string $_partId
     * @param array $_partStructure
     * @param boolean $_onlyBodyOfRfc822 only fetch body of rfc822 messages (FALSE to get headers, too)
     * @return string
     * @throws Felamimail_Exception_IMAPMessageNotFound
     */
    protected function _getPartContent(Felamimail_Model_Message $_message, $_partId, &$_partStructure, $_onlyBodyOfRfc822 = FALSE)
    {
        $imapBackend = $this->_getBackendAndSelectFolder($_message->folder_id);

        $rawContent = '';

        // special handling for rfc822 messages
        if ($_partId !== NULL && $_partStructure && $_partStructure['contentType'] === Felamimail_Model_Message::CONTENT_TYPE_MESSAGE_RFC822) {
            if ($_onlyBodyOfRfc822) {
                $logmessage = 'Fetch message part (TEXT) ' . $_partId . ' of messageuid ' . $_message->messageuid;
                if ((isset($_partStructure['messageStructure']) || array_key_exists('messageStructure', $_partStructure))) {
                    $_partStructure = $_partStructure['messageStructure'];
                }
            } else {
                $logmessage = 'Fetch message part (HEADER + TEXT) ' . $_partId . ' of messageuid ' . $_message->messageuid;
                $rawContent .= $imapBackend->getRawContent($_message->messageuid, $_partId . '.HEADER', true);
            }

            $section = $_partId . '.TEXT';
        } elseif ($_partId && preg_match('/winmail-([0-9]+)/', $_partId, $matches)) {
            // winmail.dat part requested
            $_message = $this->getCompleteMessage($_message);
            if (isset($_message->attachments[$matches[1]])) {
                return $this->getWinmailAttachmentContents($_message, $_message->attachments[$matches[1]]);
            }
            throw new Felamimail_Exception_IMAPMessageNotFound('part ' . $_partId . ' not found in message');
        } else {
            $logmessage = ($_partId !== NULL)
                ? 'Fetch message part ' . $_partId . ' of messageuid ' . $_message->messageuid
                : 'Fetch main of messageuid ' . $_message->messageuid;

            $section = $_partId;
        }

        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(
            __METHOD__ . '::' . __LINE__ . ' ' . $logmessage);

        $rawContent .= $imapBackend->getRawContent($_message->messageuid, $section, TRUE);

        return $rawContent;
    }

    /**
     * create mime part from raw content and part structure
     *
     * @param string $_rawContent
     * @param array $_partStructure
     * @return Zend_Mime_Part
     */
    protected function _createMimePart($_rawContent, $_partStructure)
    {
        $stream = fopen("php://temp", 'r+');
        fputs($stream, $_rawContent);
        rewind($stream);

        unset($_rawContent);

        $part = new Zend_Mime_Part($stream, true);
        $part->type = $_partStructure['contentType'];
        $part->encoding = isset($_partStructure['encoding'])? $_partStructure['encoding'] : null;
        $part->id = isset($_partStructure['id']) ? $_partStructure['id'] : null;
        $part->description = isset($_partStructure['description']) ? $_partStructure['description'] : null;
        $part->charset = isset($_partStructure['parameters']['charset'])
            ? $_partStructure['parameters']['charset']
            : Tinebase_Mail::DEFAULT_FALLBACK_CHARSET;
        $part->boundary = isset($_partStructure['parameters']['boundary'])? $_partStructure['parameters']['boundary'] : null;
        $part->location = isset($_partStructure['location']) ? $_partStructure['location'] : null;
        $part->location = isset($_partStructure['language']) ? $_partStructure['language'] : null;
        if (isset($_partStructure['disposition']) && is_array($_partStructure['disposition'])) {
            $part->disposition = $_partStructure['disposition']['type'];
            if (isset($_partStructure['disposition']['parameters'])) {
                $part->filename = (isset($_partStructure['disposition']['parameters']['filename']) || array_key_exists('filename', $_partStructure['disposition']['parameters'])) ? $_partStructure['disposition']['parameters']['filename'] : null;
            }
        }
        if (empty($part->filename) && isset($_partStructure['parameters']) && isset($_partStructure['parameters']['name'])) {
            $part->filename = $_partStructure['parameters']['name'];
        }

        return $part;
    }

    /**
     * get message body
     *
     * @param string|Felamimail_Model_Message $_messageId
     * @param string $_partId
     * @param string $_contentType
     * @param Felamimail_Model_Account $_account
     * @return string
     */
    public function getMessageBody($_messageId, $_partId, $_contentType, $_account = NULL)
    {
        $message = ($_messageId instanceof Felamimail_Model_Message) ? $_messageId : $this->get($_messageId);

        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
            . ' Get Message body (part: ' . $_partId . ') of message id ' . $message->getId() . ' (content type ' . $_contentType . ')');

        $cacheBody = Felamimail_Config::getInstance()->get(Felamimail_Config::CACHE_EMAIL_BODY, TRUE);
        if ($cacheBody) {
            $cache = Tinebase_Core::getCache();
            $cacheId = $this->_getMessageBodyCacheId($message, $_partId, $_contentType, $_account);

            if ($cache->test($cacheId)) {
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(
                    __METHOD__ . '::' . __LINE__ . ' Getting Message from cache.');
                return $cache->load($cacheId);
            }
        }

        $messageBody = $this->_getAndDecodeMessageBody($message, $_partId, $_contentType, $_account);

        // activate garbage collection (@see 0008216: HTMLPurifier/TokenFactory.php : Allowed memory size exhausted)
        $cycles = gc_collect_cycles();
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__
            . ' Current mem usage after gc_collect_cycles(' . $cycles . ' ): ' . memory_get_usage() / 1024 / 1024);

        if ($cacheBody) {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Put message body into Tinebase cache (for 24 hours).');
            $cache->save($messageBody, $cacheId, array('getMessageBody'), 86400);
        }

        return $messageBody;
    }

    /**
     * get message body cache id
     *
     * @param string|Felamimail_Model_Message $_message
     * @param string $_partId
     * @param string $_contentType
     * @param ?Felamimail_Model_Account $_account
     * @return string
     */
    protected function _getMessageBodyCacheId($_message, $_partId, $_contentType, $_account): string
    {
        $cacheId = 'getMessageBody_'
            . $_message->getId()
            . str_replace('.', '', (string)$_partId)
            . substr((string)$_contentType, -4)
            . (($_account !== NULL) ? 'acc' : '');

        return Tinebase_Helper::convertCacheId($cacheId);
    }

    /**
     * get and decode message body
     *
     * @param Felamimail_Model_Message $_message
     * @param string $_partId
     * @param string $_contentType
     * @param Felamimail_Model_Account $_account
     * @return string
     *
     * @todo multipart_related messages should deliver inline images
     */
    protected function _getAndDecodeMessageBody(Felamimail_Model_Message $_message, $_partId, $_contentType, $_account = NULL)
    {
        $messageBody = '';

        $structure = $_message->getPartStructure($_partId);
        if (empty($structure)) {
            if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__
                . ' Empty structure, could not find body parts of message ' . $_message->subject);
            return $messageBody;
        }

        $bodyParts = $_message->getBodyParts($structure, $_contentType);
        if (empty($bodyParts)) {
            if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__
                . ' Could not find body parts of message ' . $_message->subject);
            return $messageBody;
        }

        if (count($bodyParts) === 1 && isset($bodyParts[$_partId]['contentType'])
            && $bodyParts[$_partId]['contentType'] === Felamimail_Model_Message::CONTENT_TYPE_MESSAGE_RFC822
            && isset($structure['messageStructure']['type']) && $structure['messageStructure']['type'] === 'multipart') {
            // fetch first sub-part of rfc822 message if it is a multipart message
            return $this->_getAndDecodeMessageBody($_message, $_partId . '.1', $_contentType, $_account);
        }

        foreach ($bodyParts as $partId => $partStructure) {
            $bodyPart = $this->getMessagePart($_message, $partId, TRUE, $partStructure);

            if ($bodyPart->type === Zend_Mime::MULTIPART_MIXED) {
                foreach ($partStructure['messageStructure']['parts'] as $subPartId => $subpart) {
                    // TODO add all subparts here?
                    if ($subpart['contentType'] === Zend_Mime::TYPE_TEXT || $subpart['contentType'] === Zend_Mime::TYPE_HTML) {
                        $messageBody .= $this->_getAndDecodeMessageBody($_message, $subPartId, $_contentType, $_account);
                    }
                }
                continue;
            } else if ($bodyPart->type === Zend_Mime::MULTIPART_ALTERNATIVE) {
                foreach ($partStructure['messageStructure']['parts'] as $subPartId => $subpart) {
                    if ($subpart['contentType'] === $_contentType) {
                        $messageBody .= $this->_getAndDecodeMessageBody($_message, $subPartId, $_contentType, $_account);
                    }
                }
                continue;
            }

            $body = (string)Tinebase_Mail::getDecodedContent($bodyPart, $partStructure);

            if ($partStructure['contentType'] != Zend_Mime::TYPE_TEXT) {
                $bodyCharCountBefore = strlen($body);
                $body = $this->_purifyBodyContent($body, $_message->getId());
                $bodyCharCountAfter = strlen($body);

                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                    . ' Purifying removed ' . ($bodyCharCountBefore - $bodyCharCountAfter) . ' / ' . $bodyCharCountBefore . ' characters.');
                if ($_message->text_partid && $bodyCharCountAfter < $bodyCharCountBefore / 10) {
                    if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
                        . ' Purify may have removed (more than 9/10) too many chars, using alternative text message part.');
                    $result = $this->_getAndDecodeMessageBody($_message, $_message->text_partid, Zend_Mime::TYPE_TEXT, $_account);
                    return Felamimail_Message::convertContentType(Zend_Mime::TYPE_TEXT, Zend_Mime::TYPE_HTML, $result);
                }
            } else {
                // only needed without html purifier (@see Felamimail_HTMLPurifier_AttrTransform_AValidator)
                $body = Felamimail_Message::replaceTargets($body);
            }

            if (!($_account !== NULL && $_account->display_format === Felamimail_Model_Account::DISPLAY_CONTENT_TYPE && $bodyPart->type == Zend_Mime::TYPE_TEXT)) {
                $body = Felamimail_Message::convertContentType($partStructure['contentType'], $_contentType, $body);
                if ($bodyPart->type == Zend_Mime::TYPE_TEXT && $_contentType == Zend_Mime::TYPE_HTML) {
                    $body = Felamimail_Message::replaceUris($body);
                    $body = Felamimail_Message::replaceEmails($body);
                }
            } else {
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                    . ' Do not convert ' . $bodyPart->type . ' part to ' . $_contentType);
            }

            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                . ' Adding part ' . $partId . ' to message body.');

            $messageBody .= Tinebase_Core::filterInputForDatabase($body);
        }

        return $messageBody;
    }

    /**
     * use html purifier to remove 'bad' tags/attributes from html body
     *
     * @param string $_content
     * @param string $messageId
     * @return string
     */
    protected function _purifyBodyContent($_content, $messageId = null)
    {
        if (!defined('HTMLPURIFIER_PREFIX')) {
            define('HTMLPURIFIER_PREFIX', realpath(dirname(__FILE__) . '/../../library/HTMLPurifier'));
        }

        $config = Tinebase_Core::getConfig();

        $path = ($config->caching && $config->caching->active && $config->caching->path)
            ? $config->caching->path : Tinebase_Core::getTempDir();

        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
            . ' Purifying html body. (cache path: ' . $path . ')');
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__
            . ' Current mem usage before purify: ' . memory_get_usage() / 1024 / 1024);

        // add custom schema for passing message id to URIScheme
        $configSchema = HTMLPurifier_ConfigSchema::makeFromSerial();
        $configSchema->add('Felamimail.messageId', NULL, 'string', TRUE);
        $config = HTMLPurifier_Config::create(NULL, $configSchema);
        $config->set('HTML.DefinitionID', 'purify message body contents');
        $config->set('HTML.DefinitionRev', 1);
        // keep the whole document even if it has <html>/<body> tags
        $config->set('Core.ConvertDocumentToFragment', false);

        // @see: http://htmlpurifier.org/live/configdoc/plain.html#Attr.EnableID
        $config->set('Attr.EnableID', TRUE);

        // @see: http://htmlpurifier.org/live/configdoc/plain.html#HTML.TidyLevel
        $config->set('HTML.TidyLevel', 'heavy');

        // some config values to consider
        /*
        $config->set('Attr.EnableID', true);
        $config->set('Attr.ClassUseCDATA', true);
        $config->set('CSS.AllowTricky', true);
        $config->set('Attr.ID.HTML5', TRUE);
        $config->set('AutoFormat.Linkify', TRUE);
        $config->set('Core.LexerImpl', 'DirectLex');
        */
        $config->set('Cache.SerializerPath', $path);
        $config->set('URI.AllowedSchemes', array(
            'http' => true,
            'https' => true,
            'mailto' => true,
            'data' => true,
            'cid' => true
        ));
        if ($messageId) {
            $config->set('Felamimail.messageId', $messageId);
        }

        $this->_transformBodyTags($config);

        // add uri filter
        // TODO could be improved by adding on demand button if loading external resources is allowed
        //   or only load uris of known recipients
        if (Felamimail_Config::getInstance()->get(Felamimail_Config::FILTER_EMAIL_URIS)) {
            /** @var HTMLPurifier_URIDefinition $uri */
            $uri = $config->getDefinition('URI');
            $uri->addFilter(new Felamimail_HTMLPurifier_URIFilter_TransformURI(), $config);
        }

        // add cid uri scheme
        require_once(dirname(dirname(__FILE__)) . '/HTMLPurifier/URIScheme/cid.php');

        $purifier = new HTMLPurifier($config);
        $content = $purifier->purify($_content);

        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__
            . ' Current mem usage after purify: ' . memory_get_usage() / 1024 / 1024);

        return $content;
    }

    /**
     * transform some tags / attributes
     *
     * @param HTMLPurifier_Config $config
     */
    protected function _transformBodyTags(HTMLPurifier_Config $config)
    {
        if ($def = $config->maybeGetRawHTMLDefinition()) {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                . ' Add target="_blank" to anchors');
            $a = $def->addBlankElement('a');
            $a->attr_transform_post[] = new Felamimail_HTMLPurifier_AttrTransform_AValidator();

            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                . ' Add class="felamimail-body-blockquote" to blockquote tags that do not already have the class');
            $bq = $def->addBlankElement('blockquote');
            $bq->attr_transform_post[] = new Felamimail_HTMLPurifier_AttrTransform_BlockquoteValidator();
        } else {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                . ' Could not get HTMLDefinition, no transformation possible');
        }
    }

    /**
     * get message headers
     *
     * @param string|Felamimail_Model_Message $_messageId
     * @param int $_partId
     * @param boolean $_readOnly
     * @return array
     * @throws Felamimail_Exception_IMAPMessageNotFound
     */
    public function getMessageHeaders($_messageId, $_partId = null, $_readOnly = false)
    {
        if (!$_messageId instanceof Felamimail_Model_Message) {
            $message = $this->_backend->get($_messageId);
        } else {
            $message = $_messageId;
        }

        $cache = Tinebase_Core::getCache();
        $cacheId = Tinebase_Helper::convertCacheId(
            'getMessageHeaders' . $message->getId() . str_replace('.', '', (string)$_partId)
        );
        if ($cache->test($cacheId)) {
            return $cache->load($cacheId);
        }

        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
            . ' Fetching headers for message uid ' . $message->messageuid . ' (part:' . $_partId . ')');

        try {
            $imapBackend = $this->_getBackendAndSelectFolder($message->folder_id);
        } catch (Zend_Mail_Storage_Exception $zmse) {
            if (Tinebase_Core::isLogLevel(Zend_Log::WARN)) Tinebase_Core::getLogger()->warn(__METHOD__ . '::'
                . __LINE__ . ' ' . $zmse->getMessage());
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::'
                . __LINE__ . ' ' . $zmse->getTraceAsString());
            throw new Felamimail_Exception_IMAPMessageNotFound('Folder not found');
        }

        if ($imapBackend === null) {
            throw new Felamimail_Exception('Failed to get imap backend');
        }

        $section = ($_partId === null) ? 'HEADER' : $_partId . '.HEADER';

        try {
            $rawHeaders = $imapBackend->getRawContent($message->messageuid, $section, $_readOnly);
        } catch (Felamimail_Exception_IMAPMessageNotFound $feimnf) {
            $this->_backend->delete($message->getId());
            throw $feimnf;
        }

        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__
            . ' Fetched Headers: ' . $rawHeaders);

        $headers = array();
        $body = null;
        Zend_Mime_Decode::splitMessage($rawHeaders, $headers, $body);

        $cache->save($headers, $cacheId, array('getMessageHeaders'), 86400);

        return $headers;
    }

    /**
     * get imap backend and folder (and select folder)
     *
     * @param string $_folderId
     * @param Felamimail_Backend_Folder $_folder
     * @param boolean $_select
     * @param Felamimail_Backend_ImapProxy $_imapBackend
     * @return Felamimail_Backend_ImapProxy
     * @throws Felamimail_Exception_IMAPFolderNotFound
     * @throws Felamimail_Exception_IMAPServiceUnavailable
     */
    protected function _getBackendAndSelectFolder($_folderId = NULL, &$_folder = NULL, $_select = TRUE, Felamimail_Backend_ImapProxy $_imapBackend = NULL)
    {
        if ($_folder === NULL || empty($_folder)) {
            $folderBackend = new Felamimail_Backend_Folder();
            $_folder = $folderBackend->get($_folderId);
        }

        try {
            $imapBackend = ($_imapBackend === NULL) ? Felamimail_Backend_ImapFactory::factory($_folder->account_id) : $_imapBackend;
            if ($_select) {
                if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__
                    . ' Select folder ' . $_folder->globalname);
                $imapBackend->selectFolder(Felamimail_Model_Folder::encodeFolderName($_folder->globalname));
            }
        } catch (Zend_Mail_Storage_Exception $zmse) {
            // @todo remove the folder from cache if it could not be found on the IMAP server?
            throw new Felamimail_Exception_IMAPFolderNotFound($zmse->getMessage());
        } catch (Zend_Mail_Protocol_Exception $zmpe) {
            throw new Felamimail_Exception_IMAPServiceUnavailable($zmpe->getMessage());
        }

        return $imapBackend;
    }

    /**
     * get attachments of message
     *
     * @param string|Felamimail_Model_Message $_messageId
     * @param string $_partId
     * @param boolean $_skipEmptyAttachments
     * @param integer $recursionCounter
     * @return array
     * @refactor split into smaller functions
     */
    public function getAttachments($_messageId, $_partId = null, $_skipEmptyAttachments = false, $recursionCounter = 0)
    {
        if (!$_messageId instanceof Felamimail_Model_Message) {
            /** @var Felamimail_Model_Message $message */
            $message = $this->_backend->get($_messageId);
        } else {
            $message = $_messageId;
        }

        if ($recursionCounter > 20) {
            if (Tinebase_Core::isLogLevel(Zend_Log::WARN)) Tinebase_Core::getLogger()->warn(
                __METHOD__ . '::' . __LINE__ . ' Could not find attachments for part (> 20 recursive calls) '
                . $_partId . ' in message ' . print_r($message->toArray(), true));
            return [];
        }

        $structure = $message->getPartStructure($_partId);
        if (!isset($structure['parts'])) {
            if (isset($structure['contentType']) && $structure['contentType'] === Felamimail_Model_Message::CONTENT_TYPE_MESSAGE_RFC822
                && isset($structure['messageStructure']['parts']) && is_array($structure['messageStructure']['parts'])
            ) {
                $structure = $structure['messageStructure'];
            } elseif ($_partId === 1) {
                // handle single part messages with attachment-like content (like a pdf file)
                $structure['parts'] = array($structure);
            } else {
                return [];
            }
        }

        $attachments = array();
        foreach ($structure['parts'] as $part) {
            if (! $part) {
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                    . ' Skipping empty part');
                continue;
            }

            if ($part['type'] == 'multipart') {
                $attachments = array_merge($attachments, $this->getAttachments(
                    $message,
                    $part['partId'],
                    $_skipEmptyAttachments,
                    ++$recursionCounter)
                );
            } else {
                $filename = $this->_getAttachmentFilename($part);

                if ($part['type'] == 'text'
                    && (!is_array($part['disposition']) || ($part['disposition']['type'] == Zend_Mime::DISPOSITION_INLINE
                        && !(isset($part['disposition']["parameters"]) || array_key_exists("parameters", $part['disposition']))))
                ) {
                    if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                        . ' Skipping DISPOSITION_INLINE attachment with name ' . $filename);
                    if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__
                        . ' part: ' . print_r($part, TRUE));
                    continue;
                }

                $winmailHandled = $this->_handleWinmailDat($message, $filename, $part, $attachments, $_skipEmptyAttachments);

                // if it's not a winmail.dat, or the winmail.dat couldn't be expanded
                // properly because it has richtext embedded, return attachment as it is
                if (! $winmailHandled && isset($part['contentType']) && isset($part['partId'])) {

                    $attachmentData = array(
                        'content-type' => $part['contentType'],
                        'filename' => $filename,
                        'partId' => $part['partId'],
                        'size' => isset($part['size']) ? $part['size'] : 0,
                        'description' => isset($part['description']) ? Tinebase_Helper::mbConvertTo($part['description']) : '',
                        'cid' => (!empty($part['id'])) ? $part['id'] : NULL,
                    );

                    if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                        . ' Got attachment with name ' . $filename);

                    $attachments[] = $attachmentData;
                }
            }
        }

        return $attachments;
    }

    protected function _handleWinmailDat(Felamimail_Model_Message $message,
                                         string $filename,
                                         array $part,
                                         array &$attachments,
                                         bool $_skipEmptyAttachments = false): bool
    {
        // if a winmail.dat exists, try to expand it
        if (! preg_match('/^winmail[.]*\.dat/i', $filename) || ! (
                Tinebase_Core::systemCommandExists('tnef') || Tinebase_Core::systemCommandExists('ytnef')
            )) {

            return false;
        }

        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
            . ' Got winmail.dat attachment (contentType=' . $part['contentType'] . '). Trying to extract files ...');

        if (preg_match('/^application\/.{0,4}ms-tnef$/', $part['contentType'])
            || $part['contentType'] === 'text/plain'
            || $part['contentType'] === 'application/octet-stream'
        ) {
            try {
                $expanded = $this->_expandWinMailDat($message, $part['partId']);
            } catch (Tinebase_Exception_InvalidArgument $teia) {
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(
                    __METHOD__ . '::' . __LINE__ . ' ' . $teia->getMessage());
                return false;
            }

            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(
                __METHOD__ . '::' . __LINE__ . ' Extracted ' . count($expanded) . ' files from '
                . $filename);

            if (!empty($expanded)) {
                $attachments = array_merge($attachments, $expanded);
                return true;
            } else if ($_skipEmptyAttachments) {
                if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__
                    . ' Skipping empty winmail.dat attachment.');
                return true;
            } else {
                return false;
            }
        } else {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                . ' Unsupported winmail.dat content-type. Skipping ...');
        }

        return false;
    }

    /**
     * extracts contents from the ugly .dat format
     *
     * @param Felamimail_Model_Message $message
     * @param string|null $partId
     * @return array
     * @throws Tinebase_Exception_InvalidArgument
     * @throws Tinebase_Exception_NotFound
     * @throws Zend_Mime_Exception
     */
    protected function _expandWinMailDat(Felamimail_Model_Message $message, ?string $partId)
    {
        if (! $message->getId()) {
            throw new Tinebase_Exception_InvalidArgument('Message id missing - could not extract winmail.dat');
        }

        $files = $this->extractWinMailDat($message->getId(), $partId);
        $path = Tinebase_Core::getTempDir() . '/winmail/' . $message->getId() . '/';

        $attachmentData = array();

        $i = 0;

        foreach ($files as $filename) {
            $attachmentData[] = array(
                'content-type' => mime_content_type($path . $filename),
                'filename' => $filename,
                'partId' => 'winmail-' . $i,
                'size' => filesize($path . $filename),
                'description' => 'Extracted Content',
                'cid' => 'winmail-' . Tinebase_Record_Abstract::generateUID(10),
            );

            $i++;
        }

        return $attachmentData;
    }

    /**
     * @param string $messageId
     * @param string|null $partId
     * @return array
     * @throws Tinebase_Exception_NotFound
     * @throws Zend_Mime_Exception
     */
    public function extractWinMailDat(string $messageId, ?string $partId = null): array
    {
        $path = Tinebase_Core::getTempDir() . '/winmail/';

        // create base path
        if (!is_dir($path)) {
            mkdir($path);
        }

        // create path for this message id
        $pathWithMessageId = $path . $messageId;
        if (!is_dir($pathWithMessageId)) {
            if (file_exists($pathWithMessageId)) {
                unlink($pathWithMessageId);
            }

            @mkdir($pathWithMessageId);
            if (is_writable($pathWithMessageId)) {
                $part = $this->getMessagePart($messageId, $partId);
                $stream = $part->getDecodedStream();
                $datFile = $pathWithMessageId . '/winmail.dat';
                $tmpFile = fopen($datFile, 'w');
                stream_copy_to_stream($stream, $tmpFile);
                fclose($tmpFile);
                $this->_extractWinMailDatToDir($datFile, $pathWithMessageId);
            } else {
                if (Tinebase_Core::isLogLevel(Zend_Log::WARN)) Tinebase_Core::getLogger()->warn(
                    __METHOD__ . '::' . __LINE__ . ' DAT file path is not writable: ' . $pathWithMessageId);
            }
        }

        $dir = new DirectoryIterator($pathWithMessageId);
        $files = array();

        foreach ($dir as $file) {
            if ($file->isFile() && $file->getFilename() != 'winmail.dat') {
                $files[] = $file->getFilename();
            }
        }

        ksort($files);

        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(
            __METHOD__ . '::' . __LINE__ . ' Winmail contents:  ' . print_r($files, true));

        return $files;
    }

    /**
     * @param string $datFile
     * @param string $path
     * @throws Tinebase_Exception_NotFound
     */
    protected function _extractWinMailDatToDir($datFile, $path)
    {
        if (Tinebase_Core::systemCommandExists('tnef')) {
            Tinebase_Core::callSystemCommand('tnef -C ' . $path . ' ' . $datFile);
        } elseif (Tinebase_Core::systemCommandExists('ytnef')) {
            Tinebase_Core::callSystemCommand('ytnef -f ' . $path . ' ' . $datFile);
        } else {
            throw new Tinebase_Exception_NotFound('no (y)tnef executable found');
        }
    }

    /**
     * @param Felamimail_Model_Message $message
     * @param array $attachment
     * @param bool $extract
     * @return false|string
     * @throws Tinebase_Exception_NotFound
     */
    public function getWinmailAttachmentContents($message, $attachment, $extract = true)
    {
        $path = Tinebase_Core::getTempDir() . '/winmail/' . $message->getId();
        $attachmentFilename = $path . '/' . $attachment['filename'];
        if (file_exists($attachmentFilename)) {
            return file_get_contents($attachmentFilename);
        } elseif ($extract) {
            $this->extractWinMailDat($message->getId());
            return $this->getWinmailAttachmentContents($message, $attachment, false);
        } else {
            throw new Tinebase_Exception_NotFound('no winmail.dat found in message');
        }
    }

    /**
     * fetch attachment filename from part
     *
     * @param array $part
     * @return string
     */
    protected function _getAttachmentFilename($part)
    {
        if (is_array($part['disposition']) && (isset($part['disposition']['parameters']) || array_key_exists('parameters', $part['disposition']))
            && (isset($part['disposition']['parameters']['filename']) || array_key_exists('filename', $part['disposition']['parameters']))) {
            $filename = $part['disposition']['parameters']['filename'];
        } elseif (is_array($part['parameters']) && (isset($part['parameters']['name']) || array_key_exists('name', $part['parameters']))) {
            $filename = $part['parameters']['name'];
        } else {
            $filename = 'Part ' . $part['partId'];
            if (isset($part['contentType'])) {
                $filename .= ' (' . $part['contentType'] . ')';
            }
        }

        return Tinebase_Helper::mbConvertTo($filename);
    }

    /**
     * delete messages from cache by folder
     *
     * @param Felamimail_Model_Folder $_folder
     */
    public function deleteByFolder(Felamimail_Model_Folder $_folder)
    {
        $this->_backend->deleteByFolderId($_folder);
    }

    /**
     * update folder counts and returns list of affected folders
     *
     * @param array $_folderCounter (folderId => unreadcounter)
     * @return Tinebase_Record_RecordSet of affected folders
     * @throws Felamimail_Exception
     */
    protected function _updateFolderCounts($_folderCounter)
    {
        foreach ($_folderCounter as $folderId => $counter) {
            $folder = Felamimail_Controller_Folder::getInstance()->get($folderId);

            // get error condition and update array by checking $counter keys
            if ((isset($counter['incrementUnreadCounter']) || array_key_exists('incrementUnreadCounter', $counter))) {
                // this is only used in clearFlags() atm
                $errorCondition = ($folder->cache_unreadcount + $counter['incrementUnreadCounter'] > $folder->cache_totalcount);
                $updatedCounters = array(
                    'cache_unreadcount' => '+' . $counter['incrementUnreadCounter'],
                );
            } else if ((isset($counter['decrementMessagesCounter']) || array_key_exists('decrementMessagesCounter', $counter)) && (isset($counter['decrementUnreadCounter']) || array_key_exists('decrementUnreadCounter', $counter))) {
                $errorCondition = ($folder->cache_unreadcount < $counter['decrementUnreadCounter'] || $folder->cache_totalcount < $counter['decrementMessagesCounter']);
                $updatedCounters = array(
                    'cache_totalcount' => '-' . $counter['decrementMessagesCounter'],
                    'cache_unreadcount' => '-' . $counter['decrementUnreadCounter']
                );
            } else {
                throw new Felamimail_Exception('Wrong folder counter given: ' . print_r($_folderCounter, TRUE));
            }

            if ($errorCondition) {
                // something went wrong => recalculate counter
                if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ .
                    ' folder counters dont match => refresh counters'
                );
                $updatedCounters = Felamimail_Controller_Cache_Folder::getInstance()->getCacheFolderCounter($folder);
            }

            Felamimail_Controller_Folder::getInstance()->updateFolderCounter($folder, $updatedCounters);
        }

        return Felamimail_Controller_Folder::getInstance()->getMultiple(array_keys($_folderCounter));
    }

    /**
     * get resource part id
     *
     * @param string $cid
     * @param string $messageId
     * @return array
     * @throws Tinebase_Exception_NotFound
     *
     * @todo add param string $folderId?
     */
    public function getResourcePartStructure($cid, $messageId)
    {
        $message = $this->get($messageId);
        Felamimail_Controller_Account::getInstance()->checkAccess($message);

        $attachments = $this->getAttachments($messageId);

        foreach ($attachments as $attachment) {
            if ($attachment['cid'] === '<' . $cid . '>') {
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                    . ' Found attachment ' . $attachment['partId'] . ' with cid ' . $cid);
                return $attachment;
            }
        }

        throw new Tinebase_Exception_NotFound('Resource not found');
    }

    /**
     * @param string $nodeId
     * @return Felamimail_Model_Message
     * @throws Tinebase_Exception_NotFound
     * @throws Tinebase_Exception_SystemGeneric
     */
    public function getMessageFromNode($nodeId): Felamimail_Model_Message
    {
        // @todo simplify this / create Tinebase_Model_Tree_Node_Path::createFromNode()?

        $node = Tinebase_FileSystem::getInstance()->get($nodeId);
        $nodePath = Tinebase_FileSystem::getInstance()->getPathOfNode($node, true);
        $path = Tinebase_Model_Tree_Node_Path::createFromStatPath($nodePath);
        Tinebase_FileSystem::getInstance()->checkPathACL($path);

        // @todo check if it's an email (.eml?)
        if ($node['contenttype'] === 'application/vnd.ms-outlook') {
            // message parsing and file IO are kept separate
            $messageFactory = new MAPI\MapiMessageFactory(new Felamimail_MAPI_Factory());
            $documentFactory = new Pear\DocumentFactory();
            
            $hashFile = Tinebase_FileSystem::getInstance()->getRealPathForHash($node->hash);
            try {
                $ole = $documentFactory->createFromFile($hashFile);
                $parsedMessage = $messageFactory->parseMessage($ole);
                $content = $parsedMessage->toMimeString();
            } catch (Throwable $t) {
                $message = 'Could not parse message: ' . $t->getMessage();
                if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) Tinebase_Core::getLogger()->notice(
                    __METHOD__ . '::' . __LINE__ . ' ' . $message);
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(
                    __METHOD__ . '::' . __LINE__ . ' ' . $t->getTraceAsString());
                throw new Tinebase_Exception_SystemGeneric($message);
            }

            // write it to cache
            $cacheId = sha1(self::class . $node['name']);
            Tinebase_Core::getCache()->save($content, $cacheId);
            $message = Felamimail_Model_Message::createFromMime($content);
            
            if ($message['body_content_type'] === 'text/html') {
                $body = $parsedMessage->getBodyHTML();
                $encoding = mb_detect_encoding($body);
                if (! $encoding) {
                    $body = utf8_encode($body);
                }
                $message->body = str_replace("\r", '', $body);
                $message->body = $this->_purifyBodyContent($message->body);
                $message['body_content_type_of_body_property_of_this_record'] = Zend_Mime::TYPE_HTML;
            }
        } else {
            $content = Tinebase_FileSystem::getInstance()->getNodeContents($node);
            // @todo allow to configure body mime type to fetch?
            $message = Felamimail_Model_Message::createFromMime($content);
            $message->body = $this->_purifyBodyContent($message->body);
        }

        $message->setId($node->getId());
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
            . ' Got Message: ' . print_r($message->toArray(), true));

        return $message;
    }

    /**
     * @param Felamimail_Model_Message $message
     * @return Tinebase_Record_RecordSet
     */
    public function getSenderContactsOfMessage($message)
    {
        $contactFilter = Tinebase_Model_Filter_FilterGroup::getFilterForModel(
            Addressbook_Model_Contact::class,
            [
                ['field' => 'email_query', 'operator' => 'contains', 'value' => $message->from_email]
            ]
        );
        return Addressbook_Controller_Contact::getInstance()->search($contactFilter);
    }

    /**
     * @param Felamimail_Model_Message $message
     * @return array|Tinebase_Record_RecordSet
     */
    public function getRecipientContactsOfMessage(Felamimail_Model_Message $message)
    {
        $emailAddresses = [];
        // fetch and sanitize email addresses
        foreach (['to', 'cc', 'bcc'] as $type) {
            if (isset($message->{$type})) {
                foreach ($message->{$type} as $recipient) {
                    $recipient = $recipient['email'] ?? $recipient;
                    $converted = Felamimail_Message::convertAddresses($recipient);
                    $emailAddresses = array_merge($emailAddresses, $converted);
                }
            }
        }
        $emailAddressesFiltered = array_map(function ($address) {
            return $address['email'];
        }, $emailAddresses);

        $contactFilter = Tinebase_Model_Filter_FilterGroup::getFilterForModel(
            Addressbook_Model_Contact::class, [
            [
                'condition' => 'OR',
                'filters' => [
                    ['field' => 'email', 'operator' => 'in', 'value' => $emailAddressesFiltered],
                    ['field' => 'email_home', 'operator' => 'in', 'value' => $emailAddressesFiltered],
                ]
            ]
        ]);
        return Addressbook_Controller_Contact::getInstance()->search($contactFilter);
    }

    /**
     * save message in draft folder
     *
     * @param Felamimail_Model_Message $_message
     * @return Felamimail_Model_Message|null
     * @throws Felamimail_Exception_IMAPInvalidCredentials
     * @throws Tinebase_Exception_NotFound
     * @throws Zend_Mail_Storage_Exception
     */
    public function saveDraft(Felamimail_Model_Message $_message): ?Felamimail_Model_Message
    {
        // get account & folder
        $account = Felamimail_Controller_Account::getInstance()->get($_message->account_id);
        $draftFolder = Felamimail_Controller_Account::getInstance()->getSystemFolder($account,
            Felamimail_Model_Folder::FOLDER_DRAFTS);

        // remove old draft if uid given
        if ($_message->messageuid && $draftFolder) {
            $this->_deleteDraftByUid($_message->messageuid, $account, $draftFolder);
        }

        // add custom header (for easy removal)
        $headers = is_array($_message->headers) ? $_message->headers : [];
        $headers['X-Tine20-AutoSaved'] = true;
        $_message->headers = $headers;
        
        $draft = Felamimail_Controller_Message_Send::getInstance()->saveMessageInFolder($draftFolder, $_message,
            [Zend_Mail_Storage::FLAG_SEEN]);
        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(
            __METHOD__ . '::' . __LINE__ . ' Saved draft with uid ' . $draft->messageuid);
        return $draft;
    }

    /**
     * @param array|string $accountIds
     * @return Tinebase_Record_RecordSet|null
     * @throws Setup_Exception
     * @throws Tinebase_Exception_InvalidArgument
     */
    public function cleanupAutoSavedDrafts($accountIds): ?Tinebase_Record_RecordSet
    {
        if (!Felamimail_Config::getInstance()->featureEnabled(Felamimail_Config::FEATURE_AUTOSAVE_DRAFTS)) {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(
                __METHOD__ . '::' . __LINE__ . ' FEATURE_AUTOSAVE_DRAFTS is disabled');
            return null;
        }
        $draftFolderIds = [];
        if (is_string($accountIds)) {
            $accountIds = [$accountIds];
        }
        foreach ($accountIds as $accountId) {
            try {
                $account = Felamimail_Controller_Account::getInstance()->get($accountId);
                $draftFolder = Felamimail_Controller_Account::getInstance()->getSystemFolder($account,
                    Felamimail_Model_Folder::FOLDER_DRAFTS);
                $draftFolderIds[] = $draftFolder->getId();
            } catch (Exception $e) {
                if (($e instanceof Felamimail_Exception_IMAPServiceUnavailable
                        || $e instanceof Felamimail_Exception_IMAPInvalidCredentials)
                    && Tinebase_Core::isLogLevel(Zend_Log::INFO)
                ) {
                    Tinebase_Core::getLogger()->info(
                        __METHOD__ . '::' . __LINE__ . ' ' . $e
                    );
                } else {
                    Tinebase_Exception::log($e);
                }
            }
        }

        if (empty($draftFolderIds)) {
            return null;
        }

        $messages = null;
        try {
            $messages = $this->_backend->search(Tinebase_Model_Filter_FilterGroup::getFilterForModel(
                Felamimail_Model_Message::class, [
                ['field' => 'folder_id', 'operator' => 'in', 'value' => $draftFolderIds]
            ]));
            $messages = $messages->filter(function($record) {
                $headers = $this->getMessageHeaders($record, null, true);
                return isset($headers['x-tine20-autosaved']);
            });
            $this->_backend->delete($messages->getId());
        } catch (Exception $e) {
            if (!$e instanceof Felamimail_Exception_IMAPMessageNotFound &&
                !$e instanceof Felamimail_Exception_IMAPInvalidCredentials
            ) {
                Tinebase_Exception::log($e);
            }
        }

        return $messages;
    }

    /**
     * @param string $uid
     * @param Felamimail_Model_Account $account
     * @param Felamimail_Model_Folder $draftFolder
     * @throws Felamimail_Exception_IMAPInvalidCredentials
     * @return boolean
     */
    protected function _deleteDraftByUid($uid, Felamimail_Model_Account $account, Felamimail_Model_Folder $draftFolder = null)
    {
        if (! $draftFolder) {
            $draftFolder = Felamimail_Controller_Account::getInstance()->getSystemFolder($account, Felamimail_Model_Folder::FOLDER_DRAFTS);
            if (! $draftFolder) {
                return false;
            }
        }

        // TODO use uid expunge?
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(
            __METHOD__ . '::' . __LINE__ . ' Remove old draft with uid ' . $uid);
        $imap = Felamimail_Backend_ImapFactory::factory($account);
        $imap->selectFolder(Felamimail_Model_Folder::encodeFolderName($draftFolder->globalname));
        $imap->addFlags([$uid], [Zend_Mail_Storage::FLAG_DELETED]);

        return true;
    }

    /**
     * @param string $uid
     * @param string $accountid
     * @return bool
     * @throws Felamimail_Exception_IMAPInvalidCredentials
     */
    public function deleteDraft($uid, $accountid)
    {
        $account = Felamimail_Controller_Account::getInstance()->get($accountid);
        return $this->_deleteDraftByUid($uid, $account);
    }

    /**
     * @param Felamimail_Model_Folder $_sentFolder
     * @param Felamimail_Model_Message $_message
     * @return NULL|Tinebase_Record_Interface
     * @throws Felamimail_Exception
     * @throws Tinebase_Exception_Record_DefinitionFailure
     * @throws Tinebase_Exception_Record_Validation
     */
    public function fetchRecentMessageFromFolder($_sentFolder, $_message = null)
    {
        // update cache to fetch new message
        $folder = Felamimail_Controller_Cache_Message::getInstance()->updateCache($_sentFolder, 10, 1);
        $i = 0;
        while ($folder->cache_status != Felamimail_Model_Folder::CACHE_STATUS_COMPLETE && $i < 10) {
            $folder = Felamimail_Controller_Cache_Message::getInstance()->updateCache($folder, 10);
            $i++;
        }
        $filterData =  [
            ['field' => 'received', 'operator' => 'within', 'value' => Tinebase_Model_Filter_Date::DAY_THIS],
        ];
        if ($_message) {
            $filterData[] = ['field' => 'subject', 'operator' => 'equals', 'value' => $_message->subject];
        }
        $filter = Tinebase_Model_Filter_FilterGroup::getFilterForModel(Felamimail_Model_Message::class, $filterData);
        $result = $this->search($filter, new Tinebase_Model_Pagination([
            'sort' => 'received',
            'dir' => 'DESC',
            'limit' => 1,
        ]));
        return $result->getFirstRecord();
    }

    /**
     * @param Felamimail_Model_Message $message
     * @param string $newSubject
     * @return Felamimail_Model_Message
     * @throws Felamimail_Exception_IMAP
     */
    public function rewriteMessageSubject(Felamimail_Model_Message $message, $newSubject)
    {
        $folder = Felamimail_Controller_Folder::getInstance()->get($message->folder_id);
        $account = Felamimail_Controller_Account::getInstance()->get($message->account_id);
        $imap = Felamimail_Backend_ImapFactory::factory($account);
        $updatedMessage = clone($message);

        $mailAsString = $this->getMessageRawContent($updatedMessage);
        $mailAsString = $this->_replaceHeaderInRawMessage($mailAsString, 'subject', $newSubject);

        $uid = $imap->appendMessage(
            $mailAsString,
            Felamimail_Model_Folder::encodeFolderName($folder->globalname),
            []
        );
        
        if ($uid) {
            $updatedMessage->messageuid = $uid;

            //append flags from original message to updated message
            foreach ($updatedMessage->flags as $flag) {
               $supportedFlags = array_keys(Felamimail_Controller_Message_Flags::getInstance()->getSupportedFlags(FALSE));
                
              if (in_array($flag, $supportedFlags)) {
                   $imap->addFlags($updatedMessage->messageuid, [$flag]);
                }
            }
        } else {
            throw new Felamimail_Exception_IMAP('appendMessage failed');
        }

        // remove old message
        if ($message->messageuid) {
            $imap->addFlags([$message->messageuid], [Zend_Mail_Storage::FLAG_DELETED]);
        }

        $updatedMessage->subject = $newSubject;
        return $updatedMessage;
    }

    protected function _replaceHeaderInRawMessage($mailAsString, $header, $newValue)
    {
        return preg_replace(
            // find header (also replaces multiline headers!)
            '/(' . ucfirst($header) . ':) .*\n( .*\n)*/',
            '${1} ' . mb_encode_mimeheader($newValue) . "\n",
            $mailAsString);
    }

    /**
     * attach tags hook
     * 
     */
    public function attachTagsHook($recordIds, $tagId)
    {
        foreach ($recordIds as $messageId) {
            Felamimail_Controller_Message_Flags::getInstance()->addFlags($messageId, [$tagId]);
        }
    }

    /**
     * detach tags hook
     *
     */
    public function detachTagsHook($recordIds, $tagId)
    {
        foreach ($recordIds as $messageId) {
            Felamimail_Controller_Message_Flags::getInstance()->clearFlags($messageId, [$tagId]);
        }
    }
}
