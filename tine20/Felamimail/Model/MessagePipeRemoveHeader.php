<?php
/**
 * Tine 2.0
 *
 * @package     Felamimail
 * @subpackage  Model
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Ching-En, Cheng <c.cheng@metaways.de>
 * @copyright   Copyright (c) 2026 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */


/**
 * felamimail model message pipe remove header config model
 *
 * @package     Felamimail
 * @subpackage  Model
 *
 */
class Felamimail_Model_MessagePipeRemoveHeader implements Tinebase_BL_ElementInterface, Tinebase_BL_ElementConfigInterface
{
    protected $_config;

    public function __construct(array $config)
    {
        $this->_config = $config;
    }

    /**
     * copy mail with removed spam header to inbox and delete original message
     *
     * @param Tinebase_BL_PipeContext $_context
     * @param Tinebase_BL_DataInterface $_data
     */
    public function execute(Tinebase_BL_PipeContext $_context, Tinebase_BL_DataInterface $_data)
    {
        $header = strtolower($this->_config['header']);
        /** @var Felamimail_Model_Message $_data */
        $headers = $_data->headers;

        if (empty($headers) || !isset($headers[$header])) {
            return;
        }

        $account = Felamimail_Controller_Account::getInstance()->get($_data->account_id);
        $currentFolder = Felamimail_Controller_Folder::getInstance()->get($_data->folder_id);
        $spamMoveFolderName = Felamimail_Config::getInstance()->get(Felamimail_Config::SPAM_MOVE_FOLDER);
        $spamMoveFolder = Felamimail_Model_MessagePipeConfig::getTargetFolder($account, $spamMoveFolderName);

        if ($spamMoveFolder && $spamMoveFolder->getId() == $currentFolder->getId()) {
            $_data->is_spam_suspicions = false;
            $imap = Felamimail_Backend_ImapFactory::factory($account);
            $updatedMessage = clone($_data);

            $mailAsString = Felamimail_Controller_Message::getInstance()->getMessageRawContent($updatedMessage);
            // Remove the header line (handles CRLF/LF and folded multi-line headers)
            $mailAsString = preg_replace(
                '/^' . preg_quote($header, '/') . ':[ \t].*\r?\n(\s.*\r?\n)*/im',
                '',
                $mailAsString
            );
            $inbox = Felamimail_Model_MessagePipeConfig::getTargetFolder($account, 'INBOX');

            $uid = $imap->appendMessage(
                $mailAsString,
                Felamimail_Model_Folder::encodeFolderName($inbox->globalname),
                []
            );

            if ($uid) {
                $updatedMessage->messageuid = $uid;
                //append flags from original message to updated message
                foreach ($updatedMessage->flags as $flag) {
                    $supportedFlags = array_keys(
                        Felamimail_Controller_Message_Flags::getInstance()->getSupportedFlags(false)
                    );

                    if (in_array($flag, $supportedFlags)) {
                        $imap->addFlags($updatedMessage->messageuid, [$flag]);
                    }
                }
            } else {
                throw new Felamimail_Exception_IMAP('appendMessage failed');
            }
            // remove old message
            Felamimail_Controller_Message_Flags::getInstance()->addFlags($_data, [Zend_Mail_Storage::FLAG_DELETED]);
            Felamimail_Controller_Cache_Message::getInstance()->updateCache($spamMoveFolder);
        }
    }

    public function getNewBLElement()
    {
        return $this;
    }

    public function cmp(Tinebase_BL_ElementConfigInterface $_element)
    {
        throw new Tinebase_Exception_NotImplemented(__METHOD__ . ' should not be called');
    }
}
