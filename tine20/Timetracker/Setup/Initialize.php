<?php
/**
 * Tine 2.0
 *
 * @package     Timetracker
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Jonas Fischer <j.fischer@metaways.de>
 * @copyright   Copyright (c) 2008-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

use Tinebase_ModelConfiguration_Const as TMCC;

/**
 * class for Timetracker initialization
 *
 * @package     Setup
 */
class Timetracker_Setup_Initialize extends Setup_Initialize
{
    public static function addTSRequestedFavorite()
    {
        Tinebase_PersistentFilter::getInstance()->createDuringSetup(new Tinebase_Model_PersistentFilter(array(
            'account_id'        => NULL,
            'application_id'    => Tinebase_Application::getInstance()->getApplicationByName('Timetracker')->getId(),
            'model'             => Timetracker_Model_Timesheet::class,
            'name'              => "Requested Timesheets", // _("Requested Timesheets")
            'description'       => "Requested Timesheets",
            'filters'           => array(array(
                'field'     => 'process_status',
                'operator'  => 'equals',
                'value'     => Timetracker_Config::TS_PROCESS_STATUS_REQUESTED,
            )),
        )));
    }

    /**
     * init favorites
     */
    protected function _initializeFavorites()
    {
        $pfe = Tinebase_PersistentFilter::getInstance();

        $commonValues = array(
            'account_id'        => NULL,
            'application_id'    => Tinebase_Application::getInstance()->getApplicationByName('Timetracker')->getId(),
            'model'             => 'Timetracker_Model_TimesheetFilter',
        );

        $pfe->createDuringSetup(new Tinebase_Model_PersistentFilter(array_merge($commonValues, array(
            'name'              => "My Timesheets today", // _("My Timesheets today")
            'description'       => "My Timesheets today",
            'filters'           => array(array(
                'field'     => 'account_id',
                'operator'  => 'equals',
                'value'     => Tinebase_Model_User::CURRENTACCOUNT,
            ), array(
                'field'     => 'start_date',
                'operator'  => 'within',
                'value'     => 'dayThis',
            )),
        ))));

        $pfe->createDuringSetup(new Tinebase_Model_PersistentFilter(array_merge($commonValues, array(
            'name'              => "My Timesheets this week", // _("My Timesheets this week")
            'description'       => "My Timesheets this week",
            'filters'           => array(array(
                'field'     => 'account_id',
                'operator'  => 'equals',
                'value'     => Tinebase_Model_User::CURRENTACCOUNT,
            ), array(
                'field'     => 'start_date',
                'operator'  => 'within',
                'value'     => 'weekThis',
            )),
        ))));

        $pfe->createDuringSetup(new Tinebase_Model_PersistentFilter(array_merge($commonValues, array(
            'name'              => "My Timesheets last week", // _("My Timesheets last week")
            'description'       => "My Timesheets last week",
            'filters'           => array(array(
                'field'     => 'account_id',
                'operator'  => 'equals',
                'value'     => Tinebase_Model_User::CURRENTACCOUNT,
            ), array(
                'field'     => 'start_date',
                'operator'  => 'within',
                'value'     => 'weekLast',
            )),
        ))));

        $pfe->createDuringSetup(new Tinebase_Model_PersistentFilter(array_merge($commonValues, array(
            'name'              => "My Timesheets this month", // _("My Timesheets this month")
            'description'       => "My Timesheets this month",
            'filters'           => array(array(
                'field'     => 'account_id',
                'operator'  => 'equals',
                'value'     => Tinebase_Model_User::CURRENTACCOUNT,
            ), array(
                'field'     => 'start_date',
                'operator'  => 'within',
                'value'     => 'monthThis',
            )),
        ))));

        $pfe->createDuringSetup(new Tinebase_Model_PersistentFilter(array_merge($commonValues, array(
            'name'              => "My Timesheets last month", // _("My Timesheets last month")
            'description'       => "My Timesheets last month",
            'filters'           => array(array(
                'field'     => 'account_id',
                'operator'  => 'equals',
                'value'     => Tinebase_Model_User::CURRENTACCOUNT,
            ), array(
                'field'     => 'start_date',
                'operator'  => 'within',
                'value'     => 'monthLast',
            )),
        ))));

        static::addTSRequestedFavorite();
        
        // Timeaccounts
        $commonValues = array(
            'account_id'        => NULL,
            'application_id'    => Tinebase_Application::getInstance()->getApplicationByName('Timetracker')->getId(),
            'model'             => 'Timetracker_Model_TimeaccountFilter',
        );

        $pfe->createDuringSetup(new Tinebase_Model_PersistentFilter(
            array_merge($commonValues, array(
                'name'              => "Time accounts to bill", // _('Time accounts to bill')
                'description'       => "Time accounts to bill",
                'filters'           => array(
                    array(
                        'field'     => 'status',
                        'operator'  => 'equals',
                        'value'     => 'to bill',
                    )
                ),
            ))
        ));

        $pfe->createDuringSetup(new Tinebase_Model_PersistentFilter(
            array_merge($commonValues, array(
                'name'              => "Time accounts not yet billed", // _('Time accounts not yet billed')
                'description'       => "Time accounts not yet billed",
                'filters'           => array(
                    array(
                        'field'     => 'status',
                        'operator'  => 'equals',
                        'value'     => 'not yet billed',
                    )
                ),
            ))
        ));

        
        $pfe->createDuringSetup(new Tinebase_Model_PersistentFilter(
            array_merge($commonValues, array(
                'name'              => "Time accounts already billed", // _('Time accounts already billed')
                'description'       => "Time accounts already billed",
                   'filters'           => array(
                    array(
                        'field'     => 'status',
                        'operator'  => 'equals',
                        'value'     => 'billed',
                    )
                ),
            ))
        ));
        
    }

    protected function _initializeCostCenterCostBearer()
    {
        self::initializeCostCenterCostBearer();
    }

    public static function initializeCostCenterCostBearer()
    {
        if (Tinebase_Core::isReplica()) {
            return;
        }

        Tinebase_Controller_EvaluationDimension::addModelsToDimension(Tinebase_Model_EvaluationDimension::COST_CENTER, [
            Timetracker_Model_Timeaccount::class,
        ]);
    }

    /**
     * init system customfields
     */
    protected function _initializeSystemCFs()
    {
        if (Tinebase_Core::isReplica()) {
            return;
        }

        $taskAppId = Tinebase_Application::getInstance()->getApplicationByName(
            Tasks_Config::APP_NAME
        )->getId();

        $cf = new Tinebase_Model_CustomField_Config([
            'name' => 'timeaccount',
            'application_id' => $taskAppId,
            'model' => Tasks_Model_Task::class,
            'is_system' => true,
            'definition' => [
                Tinebase_Model_CustomField_Config::DEF_FIELD => [
                    TMCC::NAME => 'timeaccount',
                    TMCC::LABEL => 'Time Account', //_('Time Account')
                    TMCC::TYPE => TMCC::TYPE_RECORD,
                    TMCC::VALIDATORS => [Zend_Filter_Input::ALLOW_EMPTY => true,],
                    TMCC::NULLABLE => true,
                    TMCC::OWNING_APP => Tasks_Config::APP_NAME,
                    TMCC::CONFIG => [
                        TMCC::APPLICATION => Timetracker_Config::APP_NAME,
                        TMCC::APP_NAME => Timetracker_Config::APP_NAME,
                        TMCC::MODEL_NAME => Timetracker_Model_Timeaccount::MODEL_NAME_PART,
                    ],
                ],
            ],
        ], true);
        Tinebase_CustomField::getInstance()->addCustomField($cf);
    }

    protected function _initializePersistentObserver()
    {
        static::addPeristentObserverTT();
    }

    public static function addPeristentObserverTT()
    {
        Tinebase_Record_PersistentObserver::getInstance()->addObserver(
            new Tinebase_Model_PersistentObserver([
                'observable_model'      => Timetracker_Model_Timesheet::class,
                'observer_model'        => Timetracker_Controller_Timeaccount::class,
                'observer_identifier'   => 'calculateBudgetUpdate',
                'observed_event'        => Tinebase_Event_Record_Update::class,
            ])
        );
        Tinebase_Record_PersistentObserver::getInstance()->addObserver(
            new Tinebase_Model_PersistentObserver([
                'observable_model'      => Timetracker_Model_Timesheet::class,
                'observer_model'        => Timetracker_Controller_Timeaccount::class,
                'observer_identifier'   => 'calculateBudgetDelete',
                'observed_event'        => Tinebase_Event_Record_Delete::class,
            ])
        );
    }
}
