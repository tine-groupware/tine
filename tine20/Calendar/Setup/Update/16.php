<?php

/**
 * Tine 2.0
 *
 * @package     Calendar
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2022 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 *
 * this is 2023.11 (ONLY!)
 */
class Calendar_Setup_Update_16 extends Setup_Update_Abstract
{
    const RELEASE016_UPDATE000 = __CLASS__ . '::update000';
    const RELEASE016_UPDATE001 = __CLASS__ . '::update001';
    const RELEASE016_UPDATE002 = __CLASS__ . '::update002';

    static protected $_allUpdates = [
        self::PRIO_NORMAL_APP_UPDATE        => [
            self::RELEASE016_UPDATE000          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update000',
            ],
        ],
        self::PRIO_NORMAL_APP_STRUCTURE        => [
            self::RELEASE016_UPDATE001          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update001',
            ],
            self::RELEASE016_UPDATE002          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update002',
            ],
        ],
    ];

    public function update000()
    {
        $this->addApplicationUpdate('Calendar', '16.0', self::RELEASE016_UPDATE000);
    }

    public function update001()
    {
        Tinebase_TransactionManager::getInstance()->rollBack();

        foreach ($this->_backend->getOwnForeignKeys(Calendar_Model_Attender::TABLE_NAME) as $fKey) {
            $this->_backend->dropForeignKey(Calendar_Model_Attender::TABLE_NAME, $fKey['constraint_name']);
            try {
                $this->_backend->dropIndex(Calendar_Model_Attender::TABLE_NAME, $fKey['constraint_name']);
            } catch (Exception){}
        }
        try {
            $this->_backend->dropIndex(Calendar_Model_Attender::TABLE_NAME, 'tine20_cal_attendee::cal_event_id-cal_events::id');
        } catch (Exception){}
        try {
            $this->_backend->dropIndex(Calendar_Model_Attender::TABLE_NAME, 'cal_attendee::cal_event_id-cal_events::id');
        } catch (Exception){}
        try {
            $this->_backend->dropIndex(Calendar_Model_Attender::TABLE_NAME, 'tine20_cal_attendee::displaycontainer_id--container::id');
        } catch (Exception){}
        try {
            $this->_backend->dropIndex(Calendar_Model_Attender::TABLE_NAME, 'cal_attendee::displaycontainer_id--container::id');
        } catch (Exception){}

        Setup_SchemaTool::updateSchema([
            Tinebase_Model_Container::class,
            Calendar_Model_Attender::class,
            Calendar_Model_Event::class,
        ]);

        $this->addApplicationUpdate('Calendar', '16.1', self::RELEASE016_UPDATE001);
    }

    public function update002()
    {
        Setup_SchemaTool::updateSchema([
            Calendar_Model_EventType::class,
            Calendar_Model_EventTypes::class,
            Calendar_Model_Event::class
        ]);

        $this->addApplicationUpdate('Calendar', '16.2', self::RELEASE016_UPDATE002);
    }
}
