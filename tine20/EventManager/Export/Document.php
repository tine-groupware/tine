<?php declare(strict_types=1);
/**
 * Tine 2.0
 *
 * @package     EventManager
 * @subpackage  Export
 * @license     https://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Tonia Wulff <t.wulff@metaways.de>
 * @copyright   Copyright (c) 2026 Metaways Infosystems GmbH (https://www.metaways.de)
 */

/**
 * Class EventManager_Export_Document
 */
class EventManager_Export_Document extends Tinebase_Export_DocV2
{
    protected function _loadTwig()
    {
        $this->_records = $this->_controller->search($this->_filter);
        if ($this->_records->count() !== 1) {
            throw new Tinebase_Exception_Record_Validation('can only export exactly one event at a time');
        }

        $event = $this->_records->getFirstRecord();

        $filter = Tinebase_Model_Filter_FilterGroup::getFilterForModel(
            EventManager_Model_Registration::class,
            [
                [
                    'field' => EventManager_Model_Registration::FLD_EVENT_ID,
                    'operator' => 'equals',
                    'value' => $event->getId()
                ],
            ],
        );
        $registrations = EventManager_Controller_Registration::getInstance()->search($filter);

        (new Tinebase_Record_Expander(EventManager_Model_Registration::class, [
            Tinebase_Record_Expander::EXPANDER_PROPERTIES => [
                EventManager_Model_Registration::FLD_PARTICIPANT => [],
                EventManager_Model_Registration::FLD_REGISTRANT  => [],
                EventManager_Model_Registration::FLD_BOOKED_OPTIONS => [
                    Tinebase_Record_Expander::EXPANDER_PROPERTIES => [
                        EventManager_Model_BookedOption::FLD_OPTION           => [],
                        EventManager_Model_BookedOption::FLD_SELECTION_CONFIG => [],
                    ],
                ],
            ],
        ]))->expand($registrations);

        $this->_records = [
            'REGISTRATIONS' => $registrations,
        ];

        parent::_loadTwig();
    }

    protected function _renderTwigTemplate($_record = null)
    {
        if (null === $_record) {
            $_record = $this->_records['REGISTRATIONS']->getFirstRecord();
        }
        parent::_renderTwigTemplate($_record);
    }
}
