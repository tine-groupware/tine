<?php
/**
 * Tine 2.0
 * 
 * @package     Tasks
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2023 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

use Tinebase_Model_Filter_Abstract as TMFA;
use Tasks_Model_Task as TMT;
use Tasks_Model_Attendee as TMA;
use Tinebase_Model_Alarm as TBMA;

/**
 * Tasks Controller for Tasks
 * 
 * The Tasks 2.0 Controller manages access (acl) to the different backends and supports
 * a common interface to the servers/views
 * 
 * @package Tasks
 * @subpackage  Controller
 */
class Tasks_Controller_Task extends Tinebase_Controller_Record_Abstract implements Tinebase_Controller_Alarm_Interface
{
    use Tinebase_Controller_SingletonTrait;

    /**
     * the constructor
     *
     * don't use the constructor. use the singleton
     */
    protected function __construct()
    {
        $this->_applicationName = Tasks_Config::APP_NAME;
        $this->_backend = new Tinebase_Backend_Sql([
            Tinebase_Backend_Sql_Abstract::MODEL_NAME => Tasks_Model_Task::class,
            Tinebase_Backend_Sql_Abstract::TABLE_NAME => Tasks_Model_Task::TABLE_NAME,
            Tinebase_Backend_Sql_Abstract::MODLOG_ACTIVE => true,
        ]);
        $this->_modelName = Tasks_Model_Task::class;
        $this->_purgeRecords = false;
        $this->_recordAlarmField = 'due';
    }

    /****************************** overwritten functions ************************/

    /**
     * check grant for action (CRUD)
     *
     * @param Tinebase_Record_Interface $_record
     * @param string $_action
     * @param boolean $_throw
     * @param string $_errorMessage
     * @param Tinebase_Record_Interface $_oldRecord
     * @return boolean
     * @throws Tinebase_Exception_AccessDenied
     */
    protected function _checkGrant($_record, $_action, $_throw = TRUE, $_errorMessage = 'No Permission.', $_oldRecord = NULL)
    {
        try {
            $result = parent::_checkGrant($_record, $_action, $_throw, $_errorMessage, $_oldRecord);
        } catch (Tinebase_Exception_AccessDenied $tead) {
            $result = false;
        }

        if (!$result && (self::ACTION_GET === $_action || ($_oldRecord && self::ACTION_UPDATE === $_action))) {
            // check attendees for Tinebase_Core::getUser()->contact_id
            /** @phpstan-ignore-next-line */
            $result = Tasks_Controller_Attendee::getInstance()->searchCount(
                    Tinebase_Model_Filter_FilterGroup::getFilterForModel(Tasks_Model_Attendee::class, [
                        [TMFA::FIELD => Tasks_Model_Attendee::FLD_TASK_ID, TMFA::OPERATOR => TMFA::OP_EQUALS, TMFA::VALUE => $_record->getId()],
                        [TMFA::FIELD => Tasks_Model_Attendee::FLD_USER_ID, TMFA::OPERATOR => TMFA::OP_EQUALS, TMFA::VALUE => Tinebase_Core::getUser()->contact_id],
                    ])) > 0;

            if ($result && $_oldRecord && self::ACTION_UPDATE === $_action) {
                // limit write to own attendee status/alarms, alarms.skip, add notes, add attachements
                $findOwnAttendee = function(Tasks_Model_Attendee $a) {
                    return $a->getIdFromProperty(TMA::FLD_USER_ID) === Tinebase_Core::getUser()->contact_id;
                };
                $ownAttendee = $_record->{TMT::FLD_ATTENDEES}?->find($findOwnAttendee, null);
                $alarms = $_record->alarms;
                $notes = $_record->notes;
                $attachments = is_array($_record->attachments) ?
                    new Tinebase_Record_RecordSet(Tinebase_Model_Tree_Node::class, $_record->attachments, true) :
                    $_record->attachments;
                foreach (array_keys($_record::getConfiguration()->getFields()) as $property) {
                    $_record->{$property} = $_oldRecord->{$property};
                }
                if ($ownAttendee) {
                    Tinebase_Record_Expander::expandRecord($_record);
                    $oldOwnAttendee = $_record->{TMT::FLD_ATTENDEES}?->find($findOwnAttendee, null);
                    $oldOwnAttendee->{TMA::FLD_STATUS} = $ownAttendee->{TMA::FLD_STATUS};
                    if (null !== $ownAttendee->alarms) {
                        $oldOwnAttendee->alarms = $ownAttendee->alarms;
                    }
                }
                if ($alarms && $_record->alarms) {
                    foreach ($alarms as $alarm) {
                        if ($oldAlarm = $_record->alarms->getById($alarm->getId())) {
                            $oldAlarm->{TBMA::FLD_SKIP}         = $alarm->{TBMA::FLD_SKIP};
                            $oldAlarm->{TBMA::FLD_SNOOZE_TIME}  = $alarm->{TBMA::FLD_SNOOZE_TIME};
                            $oldAlarm->{TBMA::FLD_ACK_TIME}     = $alarm->{TBMA::FLD_ACK_TIME};
                        }
                    }
                }

                foreach ($notes ?? [] as $note) {
                    if ($note['id'] ?? false) continue;
                    if (!is_object($note)) {
                        $note = new Tinebase_Model_Note($note, true);
                    }
                    $note->note_type_id = Tinebase_Model_Note::SYSTEM_NOTE_NAME_NOTE;
                    $_record->notes->addRecord($note);
                }
                if ($attachments && ($attachments = $attachments->filter('id', null)) && $attachments->count() > 0) {
                    $_record->attachments->merge($attachments);
                }
            }
        }

        if (!$result && $_throw) {
            throw new Tinebase_Exception_AccessDenied($_errorMessage);
        }

        return $result;
    }

    /**
     * Removes containers where current user has no access to
     *
     * @param Tinebase_Model_Filter_FilterGroup $_filter
     * @param string $_action get|update
     */
    public function checkFilterACL(Tinebase_Model_Filter_FilterGroup $_filter, $_action = self::ACTION_GET)
    {
        if (!$this->_doContainerACLChecks) {
            if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__
                . ' Container ACL disabled for ' . $_filter->getModelName() . '.');
            return;
        }

        if (self::ACTION_GET !== $_action) {
            parent::checkFilterACL($_filter, $_action);
            return;
        }

        static $knownFilterIds = [];
        if (isset($knownFilterIds[$_filter->getId()])) {
            return;
        }
        $_filter->andWrapItself();
        $_filter->isImplicit(true);
        $_filter->setId(Tinebase_Record_Abstract::generateUID());
        $knownFilterIds[$_filter->getId()] = true;

        parent::checkFilterACL($_filter, $_action);

        $aclFilters = $_filter->getAclFilters();
        if (! $aclFilters) {
            throw new Tinebase_Exception('no acl filter found, must not happen');
        }

        $aclFilterGroup = new Tinebase_Model_Filter_FilterGroup();
        $aclFilterGroup->isImplicit(true);
        /** @var Tinebase_Model_Filter_Abstract $filter */
        foreach ($aclFilters as $filter) {
            $_filter->removeFilter($filter);
            $aclFilterGroup->addFilter($filter);
        }
        $orWrapper = Tinebase_Model_Filter_FilterGroup::getFilterForModel($this->_modelName, [
            [TMFA::FIELD => Tasks_Model_Task::FLD_ATTENDEES, TMFA::OPERATOR => 'definedBy', TMFA::VALUE => [
                [TMFA::FIELD => Tasks_Model_Attendee::FLD_USER_ID, TMFA::OPERATOR => TMFA::OP_EQUALS, TMFA::VALUE => Tinebase_Core::getUser()->contact_id],
            ]],
        ], Tinebase_Model_Filter_FilterGroup::CONDITION_OR);
        $orWrapper->addFilterGroup($aclFilterGroup);
        $orWrapper->isImplicit(true);

        $_filter->addFilterGroup($orWrapper);
    }

    /**
     * inspect creation of one record (before create)
     *
     * @param   Tasks_Model_Task $_record
     * @return  void
     */
    protected function _inspectBeforeCreate(Tinebase_Record_Interface $_record)
    {
        $this->_addAutomaticAlarms($_record);
        $this->_inspectTask($_record);
    }
    
    /**
     * inspect update of one record (before update)
     *
     * @param   Tasks_Model_Task $_record      the update record
     * @param   Tasks_Model_Task $_oldRecord   the current persistent record
     * @return  void
     */
    protected function _inspectBeforeUpdate($_record, $_oldRecord)
    {
        if ($_record->due && $_oldRecord->due && date_format($_record->due, 'Y-m-d H:i:s') !== date_format($_oldRecord->due, 'Y-m-d H:i:s')) {
            $_record = $this->_updateAlarms($_record, $_oldRecord);
        }
        $this->_inspectTask($_record);
    }

    /**
     * inspect before create/update
     *
     * @param   Tasks_Model_Task $_record      the record to inspect
     */
    protected function _inspectTask($_record)
    {
        $_record->uid = $_record->uid ?: Tinebase_Record_Abstract::generateUID();
        $_record->organizer = $_record->organizer ?: Tinebase_Core::getUser()->getId();
        $_record->originator_tz = $_record->originator_tz ?: Tinebase_Core::getUserTimezone();

        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__
            . " Inspected Task: " . print_r($_record->toArray(), true)
        );

        $this->_handleCompleted($_record);
    }

    /**
     * handles completed date
     * 
     * @param Tasks_Model_Task $_task
     */
    protected function _handleCompleted($_task)
    {
        $allStatus = Tasks_Config::getInstance()->get(Tasks_Config::TASK_STATUS)->records;
        
        $statusId = $allStatus->getIndexById($_task->status);
        
        if (is_int($statusId)){
            $status = $allStatus[$statusId];
            
            if($status->is_open) {
                $_task->completed = NULL;
            } elseif (! $_task->completed instanceof DateTime) {
                $_task->completed = Tinebase_DateTime::now();
            }
        }
    }

    /**
     * send an alarm (to responsible person and if it does not exist, to creator)
     *
     * @param  Tinebase_Model_Alarm $_alarm
     * @return void
     */
    public function sendAlarm(Tinebase_Model_Alarm $_alarm)
    {
        // save and disable container checks to be able to get all required tasks
        $oldCheckValue = $this->_doContainerACLChecks;
        $this->_doContainerACLChecks = FALSE;
        
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ 
            . " About to send alarm " . print_r($_alarm->toArray(), TRUE)
        );

        try {
            $task = $this->get($_alarm->record_id);

            if (null !== $task->completed) {
                // do not send alarms for completed tasks
                return;
            }
            
            if ($task->organizer) {
                $organizerContact = Addressbook_Controller_Contact::getInstance()->getContactByUserId($task->organizer, TRUE);
            } else {
                // use creator as organizer
                $organizerContact = Addressbook_Controller_Contact::getInstance()->getContactByUserId($task->created_by, TRUE);
            }
            
            // create message
            $translate = Tinebase_Translation::getTranslation($this->_applicationName);
            $messageSubject = sprintf($translate->_('Notification for Task %1$s'), $task->summary);
            $messageBody = $task->getNotificationMessage();
            
            $notificationsBackend = Tinebase_Notification_Factory::getBackend(Tinebase_Notification_Factory::SMTP);
            
            // send message
            if ($organizerContact->email && ! empty($organizerContact->email)) {
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Trying to send alarm email to ' . $organizerContact->email);
                $notificationsBackend->send(NULL, $organizerContact, $messageSubject, $messageBody);
            } else {
                if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Organizer / creator has no email address.');
            }
        } catch (Exception $e) {
            $this->_doContainerACLChecks = $oldCheckValue;
            throw $e;
        }
    }
    
    /**
     * add automatic alarms to record (if configured)
     * 
     * @param Tinebase_Record_Interface $_record
     * @return void
     */
    protected function _addAutomaticAlarms(Tinebase_Record_Interface $_record)
    {
        $automaticAlarms = Tasks_Config::getInstance()->get(Tinebase_Config::AUTOMATICALARM, new Tinebase_Config_Struct());
        if (! is_object($automaticAlarms)) {
            if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__ 
                . ' Invalid ' . Tinebase_Config::AUTOMATICALARM . ' config option');
            return;
        }
        $automaticAlarmsArray = $automaticAlarms->toArray();
        
        if (count($automaticAlarmsArray) == 0) {
            return;
        }
        
        if (! $_record->alarms instanceof Tinebase_Record_RecordSet) {
            $_record->alarms = new Tinebase_Record_RecordSet('Tinebase_Model_Alarm');
        }

        if (count($_record->alarms) > 0) {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Do not overwrite existing alarm.');
            return;
        }
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Add automatic alarms / minutes before: ' . implode(',', $automaticAlarmsArray));
        foreach ($automaticAlarmsArray as $minutesBefore) {
            $_record->alarms->addRecord(new Tinebase_Model_Alarm(array(
                'minutes_before' => $minutesBefore,
                'options' => '{"custom":false}',
            ), TRUE));
        }
    }

    /**
     * re schedule Alarms to new due datetime and reactivate them if they where already sent
     * 
     * @param Tinebase_Record_Interface $_record
     * @param Tinebase_Record_Interface $_oldRecord
     * @return Tinebase_Record_Interface
     */
    protected function _updateAlarms(Tinebase_Record_Interface $_record, Tinebase_Record_Interface $_oldRecord)
    {
        $dueDiff = $_oldRecord->due->diff($_record->due);
        
        if ($_record->alarms instanceof Tinebase_Record_RecordSet) {
            /** @var Tinebase_Model_Alarm $alarm */
            foreach ($_record->alarms as $alarm) {
               if ($alarm->alarm_time && $alarm->options == '{"custom":false}') {
                   $alarm->alarm_time = $alarm->alarm_time->add($dueDiff);
                   if ($alarm->sent_status != 'pending') {
                       $alarm->sent_status = 'pending';
                       $alarm->sent_time = null;
                       $alarm-> sent_message = null;
                   }
               }
            }
        }
        
        return $_record;
    }
}
