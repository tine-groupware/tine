<?php
/**
 * holds information about the requested data
 *
 * @package     Tinebase
 * @subpackage  Record
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2018 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

class Tinebase_Record_Expander_DataRequest
{
    protected $_merged = false;
    protected static $_dataCache = [];
    protected static $_deletedDataCache = [];

    /**
     * @param \Tinebase_Controller_Record_Interface $controller
     */
    public function __construct(public $prio, public $controller, public $ids, public $callback, protected $_getDeleted = false)
    {
    }

    public function merge(Tinebase_Record_Expander_DataRequest $_dataRequest)
    {
        $this->ids = array_merge($this->ids, $_dataRequest->ids);
        $this->_merged = true;
    }

    public function getKey(): string
    {
        if (null === $this->controller) {
            return 'null';
        }
        return $this->controller::class;
    }

    public function getData()
    {
        if ($this->_merged) {
            $this->ids = array_unique($this->ids);
            $this->_merged = false;
        }

        // get instances from datacache
        if (null === $this->controller) {
            $model = Tinebase_Model_DynamicRecordWrapper::class; // or just any, doesnt matter
        } else {
            $model = $this->controller->getModel();
        }
        $data = static::_getInstancesFromCache($model, $model, $this->ids, $this->_getDeleted);
    try {
        if (!empty($this->ids)) {
            /** TODO make sure getMultiple doesnt do any resolving, customfields etc */
            /** TODO Tinebase_Container / Tinebase_User_Sql etc. do not have the propery mehtod signature! */
            if ($this->controller instanceof Tinebase_Controller_Record_Abstract) {
                $newRecords = $this->controller->getMultiple($this->ids, false, null, $this->_getDeleted);
            } elseif ($this->controller instanceof Tinebase_Container) {
                $newRecords = new Tinebase_Record_RecordSet(Tinebase_Model_Container::class,
                    $this->controller->getContainerWithGrants($this->ids, Tinebase_Core::getUser()));
            } else {
                $newRecords = $this->controller->getMultiple($this->ids);
            }
            static::_addInstancesToCache($model, $newRecords, $this->_getDeleted);
            $data->mergeById($newRecords);
        }
    } catch (Tinebase_Exception_AccessDenied) {} // TODO FIXME this is a very bad hotfix, needs to be fixed!

        return $data;
    }

    /**
     * @param string $_cacheKey
     * @param Tinebase_Record_RecordSet $_data
     * @param bool $_getDeleted
     */
    protected static function _addInstancesToCache($_cacheKey, Tinebase_Record_RecordSet $_data, $_getDeleted = false)
    {
        // always set both! we only check one below in \Tinebase_Record_Expander_DataRequest::_getInstancesFromCache
        if (!isset(static::$_dataCache[$_cacheKey])) {
            static::$_dataCache[$_cacheKey] = [];
        }
        if (!isset(static::$_deletedDataCache[$_cacheKey])) {
            static::$_deletedDataCache[$_cacheKey] = [];
        }
        $array = &static::$_dataCache[$_cacheKey];

        /** @var Tinebase_Record_Abstract $record */
        foreach ($_data as $record) {
            if ($_getDeleted && $record->is_deleted) {
                static::$_deletedDataCache[$_cacheKey][$record->getId()] = $record;
            } else {
                $array[$record->getId()] = $record;
            }
        }

    }
    /**
     * @param string $_model
     * @param string $_cacheKey
     * @param array $_ids
     * @param bool $_getDeleted
     * @return Tinebase_Record_RecordSet
     * @throws Tinebase_Exception_InvalidArgument
     * @throws Tinebase_Exception_Record_NotAllowed
     */
    protected static function _getInstancesFromCache($_model, $_cacheKey, &$_ids, $_getDeleted = false)
    {
        $data = new Tinebase_Record_RecordSet($_model);
        // only one isset check, as we always set both arrays
        if (isset(static::$_dataCache[$_cacheKey])) {
            foreach ($_ids as $key => $id) {
                if (isset(static::$_dataCache[$_cacheKey][$id])) {
                    $data->addRecord(static::$_dataCache[$_cacheKey][$id]);
                    unset($_ids[$key]);
                } elseif ($_getDeleted && isset(static::$_deletedDataCache[$_cacheKey][$id])) {
                    $data->addRecord(static::$_deletedDataCache[$_cacheKey][$id]);
                    unset($_ids[$key]);
                }
            }
        }

        return $data;
    }

    public static function clearCache()
    {
        static::$_dataCache = [];
        static::$_deletedDataCache = [];
    }
}
