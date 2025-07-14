<?php
/**
 * MessageFileLocation controller for Felamimail application
 *
 * @package     Felamimail
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2018-2019 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * MessageFileLocation controller class for Felamimail application
 *
 * @package     Felamimail
 * @subpackage  Controller
 */
class Felamimail_Controller_MessageFileLocation extends Tinebase_Controller_Record_Abstract
{
    /**
     * the constructor
     *
     * don't use the constructor. use the singleton
     */
    private function __construct()
    {
        $this->_doContainerACLChecks = false;
        $this->_applicationName = 'Felamimail';
        $this->_modelName = Felamimail_Model_MessageFileLocation::class;
        $this->_backend = new Tinebase_Backend_Sql(array(
            'modelName' => $this->_modelName,
            'tableName' => 'felamimail_message_filelocation',
            'modlogActive' => true
        ));
        // we don't want them to stack up
        $this->_purgeRecords = true;
    }

    /**
     * holds the instance of the singleton
     *
     * @var Felamimail_Controller_MessageFileLocation
     */
    private static $_instance = NULL;

    /**
     * the singleton pattern
     *
     * @return Felamimail_Controller_MessageFileLocation
     */
    public static function getInstance()
    {
        if (self::$_instance === NULL) {
            self::$_instance = new self();
        }

        return self::$_instance;
    }

    /**
     * @param string $referenceString
     * @return Tinebase_Record_RecordSet of Felamimail_Model_MessageFileLocation
     */
    public function getLocationsByReference($referenceString)
    {
        if (is_array($referenceString)) {
            // only use the first element?
            $referenceString = array_pop($referenceString);
        }

        if (empty($referenceString)) {
            return new Tinebase_Record_RecordSet(Felamimail_Model_MessageFileLocation::class);
        }

        if (strpos(',', $referenceString) !== false) {
            $references = explode(',', $referenceString);
        } else if (strpos($referenceString,' ') !== false) {
            $references = explode(' ', $referenceString);
        } else {
            $references = [$referenceString];
        }

        $trimmedReferences = array_map('trim', $references);
        $hashedReferences = array_map('sha1', $trimmedReferences);
        $filter = Tinebase_Model_Filter_FilterGroup::getFilterForModel(
            Felamimail_Model_MessageFileLocation::class, [
                ['field' => 'message_id_hash', 'operator' => 'in', 'value' => $hashedReferences]
            ]
        );
        return $this->search($filter);
    }

    /**
     * get cached message file locations
     *
     * @param Felamimail_Model_Message $message
     * @throws Tinebase_Exception_InvalidArgument
     * @return Tinebase_Record_RecordSet
     */
    public function getLocationsForMessage(Felamimail_Model_Message $message)
    {
        $result = new Tinebase_Record_RecordSet(Felamimail_Model_MessageFileLocation::class);
        if (! $message->getId()) {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                . ' No id - no locations');
            return $result;
        }

        $cache = Tinebase_Core::getCache();
        $cacheId = Tinebase_Helper::convertCacheId('getLocationsForMessage' . $message->getId());
        if ($cache->test($cacheId)) {
            $locations = $cache->load($cacheId);
            if (count($locations) > 0) {
                return $locations;
            }
        }

        if (! $message->folder_id) {
            // skip message without folder
            return $result;
        }

        try {
            $messageId = $this->_getMessageId($message);
        } catch (Exception $e) {
            if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
                . ' Message might be removed from cache (' . $e->getMessage() . ')');
            return $result;
        }
        $locations = Felamimail_Controller_MessageFileLocation::getInstance()->getLocationsByReference(
            $messageId
        );
        $cache->save($locations, $cacheId);

        return $locations;
    }

    /**
     * @param Felamimail_Model_Message $message
     * @param Felamimail_Model_MessageFileLocation $location
     * @param Tinebase_Record_Interface $record
     * @param Tinebase_Model_Tree_Node $node
     * @return void
     * @throws Tinebase_Exception_AccessDenied
     * @throws Tinebase_Exception_InvalidArgument
     * @throws Tinebase_Exception_NotFound
     * @throws Zend_Db_Statement_Exception
     */
    public function createMessageLocationForRecord(Felamimail_Model_Message $message,
                                                   Felamimail_Model_MessageFileLocation $location,
                                                   Tinebase_Record_Interface $record,
                                                   Tinebase_Model_Tree_Node $node)
    {
        if (empty($record->getId())) {
            throw new Tinebase_Exception_InvalidArgument('existing record is required');
        }

        if (strlen($record->getId()) > 40) {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) {
                Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' record: ' . print_r($record->toArray(), true));
            }
            // TODO allow to file messages in recurring events
            $translation = Tinebase_Translation::getTranslation($this->_applicationName);
            $message = $translation->_('It is not possible to file the message in this record (it might be a recurring event).');
            throw new Tinebase_Exception_SystemGeneric($message);
        }

        $messageId = $this->_getMessageId($message);
        $locationToCreate = clone($location);
        $locationToCreate->message_id = $messageId;
        $locationToCreate->message_id_hash = sha1($messageId);
        $locationToCreate->record_id = $record->getId();
        $locationToCreate->node_id = $node->getId();
        if (empty($locationToCreate->record_title)) {
            $locationToCreate->record_title = $record->getTitle();
        }
        if (empty($locationToCreate->type)) {
            $locationToCreate->type = $locationToCreate->model === Filemanager_Model_Node::class
                ? Felamimail_Model_MessageFileLocation::TYPE_NODE
                : Felamimail_Model_MessageFileLocation::TYPE_ATTACHMENT;
        }

        try {
            $this->create($locationToCreate);
        } catch (Zend_Db_Statement_Exception $zdse) {
            if (Tinebase_Exception::isDbDuplicate($zdse)) {
                if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) {
                    Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__ . ' ' . $zdse->getMessage());
                }
            } else {
                throw $zdse;
            }
        }

        // invalidate location cache
        $cache = Tinebase_Core::getCache();
        $cacheId = Tinebase_Helper::convertCacheId('getLocationsForMessage' . $message->getId());
        $cache->remove($cacheId);
    }

    /**
     * @param Felamimail_Model_Message $message
     * @return mixed
     * @throws Tinebase_Exception_NotFound
     */
    protected function _getMessageId(Felamimail_Model_Message $message)
    {
        if ($message->message_id && ! empty($message->message_id)) {
            $messageId = $message->message_id;
        } else if (! isset($message->headers['message-id'])) {
            $headers = Felamimail_Controller_Message::getInstance()->getMessageHeaders($message, null, true);
            if (! isset($headers['message-id'])) {
                throw new Tinebase_Exception_NotFound('no message-id header found');
            }
            $messageId = $headers['message-id'];
        } else {
            $messageId = $message->headers['message-id'];
        }
        return $messageId;
    }

    /**
     * implement logic for each controller in this function
     *
     * @param Tinebase_Event_Abstract $_eventObject
     */
    protected function _handleEvent(Tinebase_Event_Abstract $_eventObject)
    {
        if ($_eventObject instanceof Tinebase_Event_Observer_DeleteFileNode) {
            if (! Setup_Backend_Factory::factory()->tableExists('felamimail_message_filelocation')) {
                // prevent problems during uninstall
                return;
            }

            // delete all MessageFileLocations of observered node that is deleted
            $filter = Tinebase_Model_Filter_FilterGroup::getFilterForModel(
                Felamimail_Model_MessageFileLocation::class, [
                    ['field' => 'node_id', 'operator' => 'equals', 'value' => $_eventObject->observable->getId()]
                ]
            );
            $this->deleteByFilter($filter);
        }
    }
}
