<?php

/**
 * Tine 2.0
 *
 * @package     Felamimail
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2023 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 *
 * this is 2024.11 (ONLY!)
 */
class Felamimail_Setup_Update_17 extends Setup_Update_Abstract
{
    const RELEASE017_UPDATE000 = __CLASS__ . '::update000';
    const RELEASE017_UPDATE001 = __CLASS__ . '::update001';

    const RELEASE017_UPDATE002 = __CLASS__ . '::update002';
    const RELEASE017_UPDATE003 = __CLASS__ . '::update003';
    const RELEASE017_UPDATE004 = __CLASS__ . '::update004';


    static protected $_allUpdates = [
        self::PRIO_NORMAL_APP_STRUCTURE => [
            self::RELEASE017_UPDATE001          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update001',
            ],
        ],
        self::PRIO_NORMAL_APP_UPDATE        => [
            self::RELEASE017_UPDATE000          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update000',
            ],
            self::RELEASE017_UPDATE002          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update002',
            ],
            self::RELEASE017_UPDATE003          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update003',
            ],
            self::RELEASE017_UPDATE004          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update004',
            ],
        ],

    ];

    public function update000()
    {
        $this->addApplicationUpdate(Felamimail_Config::APP_NAME, '17.0', self::RELEASE017_UPDATE000);
    }
    
    public function update001()
    {
        Tinebase_TransactionManager::getInstance()->rollBack();

        if ($this->getTableVersion('felamimail_account') < 30) {
            $this->setTableVersion('felamimail_account', 30);
        }
        $this->_backend->dropCol('felamimail_account', 'preserve_format');
        
        $this->addApplicationUpdate(Felamimail_Config::APP_NAME, '17.1', self::RELEASE017_UPDATE001);
    }

    public function update002()
    {
        Setup_SchemaTool::updateSchema([
            Felamimail_Model_MessageExpectedAnswer::class,
        ]);

        Felamimail_Scheduler_Task::addCheckExpectedAnswerTask(Tinebase_Core::getScheduler());

        $this->addApplicationUpdate(Felamimail_Config::APP_NAME, '17.2', self::RELEASE017_UPDATE002);
    }
    public function update003()
    {
        $filter = Tinebase_Model_Filter_FilterGroup::getFilterForModel(
            Felamimail_Model_MessageFileLocation::class, [
                ['field' => 'record_title', 'operator' => 'contains', 'value' =>'html-proxy'],
            ]
        );
        $result = Felamimail_Controller_MessageFileLocation::getInstance()->search($filter);
        foreach ($result as $location) {
            try {
                $pos = strpos($location->model, '_');
                $appName = substr($location->model, 0, $pos);
                $modelName = preg_replace('/^.+_Model_/', '', $location->model);
                $controllerName = $appName . '_Controller_' . $modelName;
                /** @var Tinebase_Controller_Record_Abstract $controllerName */
                $controller = $controllerName::getInstance();
                if ($controller) {
                    $record = $controller->get($location->record_id);
                    if ($record) {
                        $location->record_title = $record->getTitle();
                        Felamimail_Controller_MessageFileLocation::getInstance()->update($location);
                    }
                }
            } catch (Exception $e) {
                Tinebase_Exception::log($e);
            }
        }

        $this->addApplicationUpdate(Felamimail_Config::APP_NAME, '17.3', self::RELEASE017_UPDATE003);
    }

    public function update004()
    {
        $listCtrl = Addressbook_Controller_List::getInstance();

        foreach ($listCtrl->getAll() as $list) {
            if ($list->xprops()[Addressbook_Model_List::XPROP_USE_AS_MAILINGLIST] ?? false) {
                try {
                    Felamimail_Sieve_AdbList::setScriptForList($list);
                } catch (Exception $e) {
                    Tinebase_Exception::log($e);
                }
            }
        }

        $this->addApplicationUpdate(Felamimail_Config::APP_NAME, '17.4', self::RELEASE017_UPDATE004);
    }
}
