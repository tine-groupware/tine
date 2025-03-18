<?php

/**
 * ActionLog controller for Tinebase application
 *
 * @package     Tinebase
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Ching En Cheng <c.cheng@metaways.de>
 * @copyright   Copyright (c) 2023 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * ActionLog controller for Tinebase application
 *
 * @package     Tinebase
 * @subpackage  Controller
 */
class Tinebase_Controller_ActionLog extends Tinebase_Controller_Record_Abstract
{
    use Tinebase_Controller_SingletonTrait;

    /**
     * the constructor
     *
     * don't use the constructor. use the singleton
     * @throws Tinebase_Exception_Backend_Database
     * @throws Tinebase_Exception_InvalidArgument
     */
    protected function __construct()
    {
        $this->_doContainerACLChecks = false;
        $this->_applicationName = Tinebase_Config::APP_NAME;
        $this->_modelName = Tinebase_Model_ActionLog::class;
        $this->_backend = new Tinebase_Backend_Sql(array(
            Tinebase_Backend_Sql::MODEL_NAME => Tinebase_Model_ActionLog::class,
            Tinebase_Backend_Sql::TABLE_NAME => Tinebase_Model_ActionLog::TABLE_NAME,
            Tinebase_Backend_Sql::MODLOG_ACTIVE => true
        ));

        $this->_purgeRecords = false;
        $this->_resolveCustomFields = true;
        // activate this if you want to use containers
        $this->_doContainerACLChecks = false;
    }

    public function addActionLogConfirmationEvent(object $_eventObject)
    {
        $actionLog = new Tinebase_Model_ActionLog([
            Tinebase_Model_ActionLog::FLD_ACTION_TYPE =>  Tinebase_Model_ActionLog::TYPE_ADD_USER_CONFIRMATION,
            Tinebase_Model_ActionLog::FLD_USER => Tinebase_Core::getUser()->getId(),
            Tinebase_Model_ActionLog::FLD_DATA => serialize($_eventObject),
            Tinebase_Model_ActionLog::FLD_DATETIME => Tinebase_DateTime::now(),
        ]);
        $this->create($actionLog);
    }

    public function addActionLogUserDelete($accountIds)
    {
        $data = is_string($accountIds) ? $accountIds : implode(',', $accountIds);
        $actionLog = new Tinebase_Model_ActionLog([
            Tinebase_Model_ActionLog::FLD_ACTION_TYPE => Tinebase_Model_ActionLog::TYPE_DELETION,
            Tinebase_Model_ActionLog::FLD_USER => Tinebase_Core::getUser()->getId(),
            Tinebase_Model_ActionLog::FLD_DATA => 'user ids deleted: ' . $data,
            Tinebase_Model_ActionLog::FLD_DATETIME => Tinebase_DateTime::now(),
        ]);
        $this->create($actionLog);
    }

    public function addActionLogQuotaNotification($data)
    {
        $actionLog = new Tinebase_Model_ActionLog([
            Tinebase_Model_ActionLog::FLD_ACTION_TYPE =>  Tinebase_Model_ActionLog::TYPE_EMAIL_NOTIFICATION,
            Tinebase_Model_ActionLog::FLD_USER => Tinebase_Core::getUser()->getId(),
            Tinebase_Model_ActionLog::FLD_DATA => 'email notification ' . $data,
            Tinebase_Model_ActionLog::FLD_DATETIME => Tinebase_DateTime::now(),
        ]);
        $this->create($actionLog);
    }

    /**
     * @throws Tinebase_Exception_AccessDenied
     * @throws Tinebase_Exception_Record_DefinitionFailure
     * @throws Tinebase_Exception_Record_Validation
     * @throws Tinebase_Exception_NotFound
     * @throws Tinebase_Exception_InvalidArgument
     * @throws Exception
     */
    public function addActionLogDatevEmail($_updater, $recipients, $_subject, $_messagePlain, $_messageHtml, $_attachments)
    {
        $recipients = array_map(fn($recipient) => $recipient['email'], $recipients);
        $attachments = array_map(fn($attachment) => $attachment['name'], $_attachments);
        $date = Tinebase_DateTime::now();
        
        if (preg_match('/(\d{4})-(\d{2})-(\d{2}) (\d{2}):(\d{2}):(\d{2})/', (string) $_messagePlain, $matches)) {
            $date = new Tinebase_DateTime($matches[0]);
        }

        $this->create(new Tinebase_Model_ActionLog([
            Tinebase_Model_ActionLog::FLD_ACTION_TYPE =>  Tinebase_Model_ActionLog::TYPE_DATEV_EMAIL,
            Tinebase_Model_ActionLog::FLD_USER => Tinebase_Core::getUser()->getId(),
            Tinebase_Model_ActionLog::FLD_DATETIME => $date,
            Tinebase_Model_ActionLog::FLD_DATA => json_encode([
                'sender'    => $_updater->accountEmailAddress,
                'messagePlain' => $_messagePlain,
                'recipients' => $recipients,
                'subject'   => $_subject,
                'attachments' => $attachments,
            ]),
        ]));
    }
}
