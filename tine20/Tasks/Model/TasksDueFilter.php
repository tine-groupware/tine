<?php declare(strict_types=1);
/**
 * Tine 2.0
 *
 * @package     Tasks
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2023 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * Tasks Filter Class
 * @package Tasks
 */
class Tasks_Model_TasksDueFilter extends Tinebase_Model_Filter_Abstract
{
    protected $_operators = [self::OP_EQUALS];

    public function appendFilterSql($_select, $_backend)
    {
        if (is_array($this->_value)) {
            throw new Tinebase_Exception_SystemGeneric('bad value for tasks due filter');
        }

        /** @var Addressbook_Model_Contact $contact */
        $contact = Addressbook_Controller_Contact::getInstance()->get(
            Tinebase_Model_User::CURRENTACCOUNT === $this->_value ? Tinebase_Core::getUser()->contact_id : $this->_value);

        $stati = Tasks_Config::getInstance()->{Tasks_Config::ATTENDEE_STATUS}->records->filter('is_open', 1)
            ->getArrayOfIds();
        $filters = [
            ['field' => Tasks_Model_Task::FLD_ATTENDEES, 'operator' => 'definedBy', 'value' => [
                ['field' => Tasks_Model_Attendee::FLD_USER_ID, 'operator' => 'equals', 'value' => $contact->getId()],
                ['field' => Tasks_Model_Attendee::FLD_STATUS, 'operator' => 'in', 'value' =>  $stati],
            ]]
        ];
        if ($contact->account_id) {
            $filters[] = [
                Tinebase_Model_Filter_FilterGroup::CONDITION => Tinebase_Model_Filter_FilterGroup::CONDITION_AND,
                Tinebase_Model_Filter_FilterGroup::FILTERS => [
                    ['field' => 'organizer', 'operator' => 'equals', 'value' => $contact->account_id],
                    ['field' => Tasks_Model_Task::FLD_DUE, 'operator' => 'before', 'value' => Tinebase_DateTime::now()],
                ],
            ];
            $filters[] = [
                Tinebase_Model_Filter_FilterGroup::CONDITION => Tinebase_Model_Filter_FilterGroup::CONDITION_AND,
                Tinebase_Model_Filter_FilterGroup::FILTERS => [
                    ['field' => 'organizer', 'operator' => 'equals', 'value' => $contact->account_id],
                    ['field' => Tasks_Model_Task::FLD_ATTENDEES, 'operator' => 'notDefinedBy', 'value' => [
                        ['field' => Tasks_Model_Attendee::FLD_STATUS, 'operator' => 'in', 'value' =>  $stati],
                    ]],
                ],
            ];
        }

        $stati = Tasks_Config::getInstance()->{Tasks_Config::TASK_STATUS}->records->filter('is_open', 0)
            ->getArrayOfIds();

        Tinebase_Backend_Sql_Filter_FilterGroup::appendFilters($_select,
            Tinebase_Model_Filter_FilterGroup::getFilterForModel(Tasks_Model_Task::class, [
                ['field' => Tasks_Model_Task::FLD_DEPENDENS_ON, 'operator' => 'notDefinedBy', 'value' => [
                    ['field' => Tasks_Model_TaskDependency::FLD_DEPENDS_ON, 'operator' => 'definedBy', 'value' => [
                        ['field' => 'status', 'operator' => 'notin', 'value' => $stati],
                    ]],
                ]],
                [
                    Tinebase_Model_Filter_FilterGroup::CONDITION => Tinebase_Model_Filter_FilterGroup::CONDITION_OR,
                    Tinebase_Model_Filter_FilterGroup::FILTERS => $filters,
                ],
            ]), $_backend);
    }
}