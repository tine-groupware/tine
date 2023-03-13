<?php
/**
 * @package     DFCom
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiß <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2018 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * DeviceList controller class for DFCom application
 * 
 * @package     DFCom
 * @subpackage  Controller
 */
class DFCom_Controller_DeviceList extends Tinebase_Controller_Record_Abstract
{

    /**
     * the constructor
     *
     * don't use the constructor. use the singleton
     */
    private function __construct()
    {
        $this->_applicationName = 'DFCom';

        $this->_modelName = 'DFCom_Model_DeviceList';
        $this->_purgeRecords = false;
        // @todo get this from model conf??
        $this->_doContainerACLChecks = false;

        $this->_backend = new Tinebase_Backend_Sql([
            'modelName'     => $this->_modelName,
            'tableName'     => 'dfcom_device_list',
            'modlogActive'  => true
        ]);
    }
    
    /**
     * holds the instance of the singleton
     *
     * @var DFCom_Controller_DeviceList
     */
    private static $_instance = NULL;

    /**
     * the singleton pattern
     *
     * @return DFCom_Controller_DeviceList
     */
    public static function getInstance()
    {
        if (static::$_instance === NULL) {
            static::$_instance = new self();
        }
        
        return static::$_instance;
    }

    /**
     * create default deviceLists for given device
     *
     * @param DFCom_Model_Device $device
     * @return Tinebase_Record_RecordSet
     * @throws Tinebase_Exception_AccessDenied
     * @throws Tinebase_Exception_NotFound
     * @throws Tinebase_Exception_Record_NotAllowed
     */
    public function createDefaultDeviceLists(DFCom_Model_Device $device)
    {
        $createdLists = new Tinebase_Record_RecordSet(DFCom_Model_DeviceList::class);
        $defaultListNames = DFCom_Config::getInstance()->get(DFCom_Config::DEFAULT_DEVICE_LISTS);

        foreach($defaultListNames as $listName) {
            try {
                $exportDefinition = Tinebase_ImportExportDefinition::getInstance()->getByName($listName);
                $options = Tinebase_ImportExportDefinition::getInstance()->getOptionsAsZendConfigXml($exportDefinition);
                $deviceList = new DFCom_Model_DeviceList([
                    'device_id' => $device->getId(),
                    'name' => $options->deviceName,
                    'export_definition_id' => $exportDefinition->getId(),
                    'controlCommands' => $options->controlCommands,
                ]);
            } catch (Exception $e) {
                $deviceName  = preg_match('/^keyField_/', $listName) ?
                    strtolower(str_replace('DeviceList', '', explode('_', $listName)[2]))
                    : $listName;

                $deviceList = new DFCom_Model_DeviceList([
                    'device_id' => $device->getId(),
                    'name' => $deviceName,
                    'export_definition_id' => $listName,
                ]);
            }

            $createdLists->addRecord($this->create($deviceList));
        }

        return $createdLists;
    }

    /**
     * get device list
     *
     * @param  string $deviceId
     * @param  string $listId
     * @param  string $authKey
     * @return string sorted tsv
     *
     */
    public function getDeviceList($deviceId, $listId, $authKey)
    {
        /** @var Tinebase_Http_Request $request */
        $request = Tinebase_Core::get(Tinebase_Core::REQUEST);

        $deviceController = DFCom_Controller_Device::getInstance();

        $assertACLUsageCallbacks = [
            $this->assertPublicUsage(),
            $deviceController->assertPublicUsage(),
            HumanResources_Controller_Employee::getInstance()->assertPublicUsage(),
            HumanResources_Controller_FreeTimeType::getInstance()->assertPublicUsage(),
        ];

        if ($request->getQuery('userId')) {
            $currentUser = Tinebase_Core::getUser();
            $user = Tinebase_Core::setUser(Tinebase_User::getInstance()->getUserById($request->getQuery('userId'), Tinebase_Model_FullUser::class));
        }

        try {
            /** @var DFCom_Model_Device $device */
            $device = $deviceController->get($deviceId);
            if ($device->authKey !== $authKey) {
                sleep(DFCom_Controller_Device::$unAuthSleepTime);
                $response = new \Zend\Diactoros\Response('php://memory', 401);
                $response->getBody()->write('Device authentication failed');
                return $response;
            }

            /** @var DFCom_Model_DeviceList $deviceList */
            $deviceList = $this->search(Tinebase_Model_Filter_FilterGroup::getFilterForModel(DFCom_Model_DeviceList::class, [
                ['field' => 'id', 'operator' => 'equals', 'value' => $listId],
            ]))->getFirstRecord();

            if (!$deviceList || $deviceList->device_id != $deviceId) {
                $response = new \Zend\Diactoros\Response('php://memory', 404);
                $response->getBody()->write('DeviceList not found');
                return $response;
            }

            $responseStream = fopen('php://memory', 'w');
            if(preg_match('/^keyField_/', $deviceList->export_definition_id)) {
                try {
                    [, $appName, $keyFieldName] = explode('_', $deviceList->export_definition_id);
                    $config = Tinebase_Config::getAppConfig($appName)->$keyFieldName;
                    foreach($config->records as $record) {
                        Tinebase_Export_CsvNew::fputcsv($responseStream, array_values($record->toArray()), "\t", '');
                    };
                } catch (Exception $e) {
                    $response = new \Zend\Diactoros\Response('php://memory', 404);
                    $response->getBody()->write('keyField '. $deviceList->name. ' not found!');
                    return $response;
                }
            } else {
                /** @var Tinebase_Export_CsvNew $export */
                $export = Tinebase_Export::factory(null, [
                    'definitionId' => $deviceList->export_definition_id,
                    'charset' => str_replace(' ', '-', $request
                        ->getHeader('Accept-Charset')
                        ->getFieldValue()),
                    'ignoreACL' => true,
                ]);
                $export->generate();
                $export->write($responseStream);
            }

            $deviceList->list_version = $this->getSyncToken($deviceList);
            $deviceList->list_status = -1;

            $this->update($deviceList);

            $device->lastSeen = Tinebase_DateTime::now();
            $deviceController->update($device);

            return new \Zend\Diactoros\Response($responseStream, 200, [
                'Content-Length' => fstat($responseStream)['size'],
            ]);
        } catch (Exception $e) {
            if (Tinebase_Core::isLogLevel(Zend_Log::ERR)) Tinebase_Core::getLogger()->err(__METHOD__ . '::' . __LINE__
                . ' cannot create export ' . $e);
            $response = new \Zend\Diactoros\Response('php://memory', 500);
            $response->getBody()->write('DeviceList export error');
            return $response;
        } finally {
            if (isset($currentUser)) {
                Tinebase_Core::setUser($currentUser);
            }
            foreach(array_reverse($assertACLUsageCallbacks) as $assertACLUsageCallback) {
                $assertACLUsageCallback();
            }
        }
    }

    /**
     * get lists of given device
     *
     * @param DFCom_Model_Device $device
     * @return Tinebase_Record_RecordSet
     */
    public function getDeviceLists(DFCom_Model_Device $device)
    {
        $filter = Tinebase_Model_Filter_FilterGroup::getFilterForModel(DFCom_Model_DeviceList::class);
        $filter->addFilter(new Tinebase_Model_Filter_Id('device_id', 'equals', $device->getId()));
        return $this->search($filter, new Tinebase_Model_Pagination(['sort' => 'name']));
    }

    /**
     * get current syncToken of given deviceList
     *
     * @param DFCom_Model_DeviceList    $deviceList
     * @return string
     * @throws Tinebase_Exception_AccessDenied
     * @throws Tinebase_Exception_NotFound
     * @throws Tinebase_Exception_Record_DefinitionFailure
     * @throws Tinebase_Exception_Record_NotAllowed
     * @throws Zend_Db_Statement_Exception
     */
    public function getSyncToken($deviceList)
    {
        if(preg_match('/^keyField_/', $deviceList->export_definition_id)) {
            [, $appName, $keyFieldName] = explode('_', $deviceList->export_definition_id);
            $config = Tinebase_Config::getAppConfig($appName)->$keyFieldName;
            return sha1(print_r($config->records->toArray(), true));
        } else {
            $export = Tinebase_Export::factory(null, [
                'definitionId' => $deviceList->export_definition_id,
                'ignoreACL' => true,
            ]);
            return Tinebase_FilterSyncToken::getInstance()->getFilterSyncToken($export->getFilter(), $export->getController());
        }
    }
}
