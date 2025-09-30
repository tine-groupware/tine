<?php
/**
 * Tine 2.0
 *
 * @package     CrewScheduling
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Stefanie Stamer <s.stamer@metaways.de>
 * @copyright   Copyright (c) 2017-2019 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

use Tinebase_ModelConfiguration_Const as TMCC;

/**
 * class for CrewScheduling initialization
 *
 * @package     Setup
 */
class CrewScheduling_Setup_Initialize extends Setup_Initialize
{
    public static $customfields = array(
        array(
            'app'       => 'Addressbook',
            'model'     => 'Addressbook_Model_Contact',
            'cfields'   => array(
                array(
                    'name' => 'favorite_day',
                    'label' => 'Lieblings Tag',
                    'uiconfig' => array(
                        'order' => '',
                        'group' => '',
                        'tab' => 'Dienstplanung'),
                    'type' => 'text'
                ),
                array(
                    'name' => 'favorite_partner',
                    'label' => 'Lieblings Partner',
                    'uiconfig' => array(
                        'order' => '',
                        'group' => '',
                        'tab' => 'Dienstplanung'),
                    'type' => 'recordList',
                    'recordConfig' => array('value' => array('records' => 'Tine.Addressbook.Model.Contact'))
                ),
            ),
        ),
        array(
            'app'       => 'Calendar',
            'model'     => 'Calendar_Model_Event',
            'cfields'   => array(
                array(
                    'name' => 'site',
                    'label' => 'Standort',
                    'uiconfig' => array(
                        'order' => '',
                        'group' => '',
                        'tab' => ''),
                    'type' => 'record',
                    'recordConfig' => array(
                        'value' => array('records' => 'Tine.Addressbook.Model.Contact'),
                        'additionalFilterSpec' => [
                            'config' => [
                                'name' => 'siteFilter',
                                'appName' => 'Addressbook'
                            ]
                        ]
                    )
                ),
            )
        ),
        array(
            'app'       => 'Addressbook',
            'model'     => 'Addressbook_Model_List',
            'cfields'   => array(
                array(
                    'name' => 'schedulingRole',
                    'label' => 'Dienstplanungs Rolle',
                    'uiconfig' => array(
                        'order' => '',
                        'group' => '',
                        'tab' => 'Dienstplanung'),
                    'type' => 'record',
                    'recordConfig' => array('value' => array('records' => 'Tine.CrewScheduling.Model.SchedulingRole'))
                )
            )
        )
    );

    /**
     * Override method: additional initialisation
     *
     * @see tine20/Setup/Setup_Initialize#_initialize($_application)
     * @param Tinebase_Model_Application $_application
     * @param array|null $_options
     */
    public function _initialize(Tinebase_Model_Application $_application, $_options = null)
    {
        parent::_initialize($_application, $_options);
        $this->_initCustomfields();
    }

    //TODO RE
    //remove these
    public static function getInitialCustomFields()
    {
        $initialCustomFields = static::$customfields;

        return $initialCustomFields;
    }

    protected function _initCustomfields()
    {
        foreach(static::getInitialCustomFields() as $appModel) {
            $appId = Tinebase_Application::getInstance()->getApplicationByName($appModel['app'])->getId();

            foreach($appModel['cfields'] as $customfield) {
                $cfc = array(
                    'name' => $customfield['name'],
                    'application_id' => $appId,
                    'model' => $appModel['model'],
                    'definition' => array(
                        'uiconfig' => $customfield['uiconfig'],
                        'label' => $customfield['label'],
                        'type' => $customfield['type']
                    )
                );

                if ($customfield['type'] == 'record') {
                    $cfc['definition']['recordConfig'] = $customfield['recordConfig'];
                } elseif ($customfield['type'] == 'recordList') {
                    $cfc['definition']['recordListConfig'] = $customfield['recordConfig'];
                } elseif ($customfield['type'] == 'keyField') {
                    $cfc['definition']['keyFieldConfig'] = $customfield['recordConfig'];
                }

                $cf = new Tinebase_Model_CustomField_Config($cfc);

                Tinebase_CustomField::getInstance()->addCustomField($cf);
            }
        }
    }

    protected function _initializeSystemCFs()
    {
        self::createSystemCustomfields();
    }

    public static function createSystemCustomfields()
    {
        if (Tinebase_Core::isReplica()) {
            return;
        }

        $appId = Tinebase_Application::getInstance()->getApplicationByName('Calendar')->getId();

        $cf = new Tinebase_Model_CustomField_Config([
            'name' => CrewScheduling_Config::CREWSHEDULING_ROLES,
            'application_id' => $appId,
            'model' => Calendar_Model_Attender::class,
            'is_system' => true,
            'definition' => [
                Tinebase_Model_CustomField_Config::DEF_FIELD => [
                    TMCC::LABEL => 'Crewscheduling Roles', //_('Crewscheduling Roles')
                    TMCC::TYPE => TMCC::TYPE_RECORDS,
                    TMCC::NULLABLE => true,
                    TMCC::OWNING_APP => CrewScheduling_Config::APP_NAME,
                    TMCC::CONFIG => [
                        TMCC::APP_NAME => CrewScheduling_Config::APP_NAME,
                        TMCC::MODEL_NAME => CrewScheduling_Model_AttendeeRole::MODEL_NAME_PART,
                        TMCC::DEPENDENT_RECORDS => true,
                        TMCC::REF_ID_FIELD => CrewScheduling_Model_AttendeeRole::FLD_ATTENDEE,
                    ],
                ],
                Tinebase_Model_CustomField_Config::DEF_HOOK => [
                    [CrewScheduling_Controller_AttendeeRole::class, 'modelConfigHook'],
                ],
            ]
        ], true);
        Tinebase_CustomField::getInstance()->addCustomField($cf);

        $cf = new Tinebase_Model_CustomField_Config([
            'name' => CrewScheduling_Config::EVENT_ROLES_CONFIGS,
            'application_id' => $appId,
            'model' => Calendar_Model_Event::class,
            'is_system' => true,
            'definition' => [
                Tinebase_Model_CustomField_Config::DEF_FIELD => [
                    TMCC::LABEL => "Event's role Config", //_("Event's role Config")
                    TMCC::TYPE => TMCC::TYPE_RECORDS,
                    TMCC::OWNING_APP => CrewScheduling_Config::APP_NAME,
                    TMCC::CONFIG => [
                        TMCC::APP_NAME => CrewScheduling_Config::APP_NAME,
                        TMCC::MODEL_NAME => CrewScheduling_Model_EventRoleConfig::MODEL_NAME_PART,
                        TMCC::DEPENDENT_RECORDS => true,
                        TMCC::REF_ID_FIELD => CrewScheduling_Model_EventRoleConfig::FLD_CAL_EVENT,
                    ],
                    TMCC::FILTER_DEFINITION => [
                        TMCC::FILTER                    => Tinebase_Model_Filter_ForeignRecords::class,
                        TMCC::OPTIONS                   => [
                            TMCC::FILTER_GROUP              => CrewScheduling_Model_EventRoleConfig::class . 'Filter',
                            TMCC::CONTROLLER                => CrewScheduling_Controller_EventRoleConfig::class,
                            TMCC::REF_ID_FIELD              => CrewScheduling_Model_EventRoleConfig::FLD_CAL_EVENT,
                            TMCC::FILTER_OPTIONS            => [
                                'doJoin'                        => true,
                            ],
                        ],
                    ],
                ],
                Tinebase_Model_CustomField_Config::DEF_HOOK => [
                    [CrewScheduling_Controller_EventRoleConfig::class, 'modelConfigHook'],
                ],
            ]
        ], true);
        Tinebase_CustomField::getInstance()->addCustomField($cf);

        $cf = new Tinebase_Model_CustomField_Config([
            'name' => CrewScheduling_Config::CS_ROLE_CONFIGS,
            'application_id' => $appId,
            'model' => Calendar_Model_EventType::class,
            'is_system' => true,
            'definition' => [
                Tinebase_Model_CustomField_Config::DEF_FIELD => [
                    TMCC::LABEL => 'Crewscheduling Role Configs', //_('Crewscheduling Role Configs')
                    TMCC::TYPE => TMCC::TYPE_RECORDS,
                    TMCC::OWNING_APP => CrewScheduling_Config::APP_NAME,
                    TMCC::CONFIG => [
                        TMCC::APP_NAME => CrewScheduling_Config::APP_NAME,
                        TMCC::MODEL_NAME => CrewScheduling_Model_EventTypeConfig::MODEL_NAME_PART,
                        TMCC::DEPENDENT_RECORDS => true,
                        TMCC::REF_ID_FIELD => CrewScheduling_Model_EventTypeConfig::FLD_EVENT_TYPE,
                    ],
                ],
                Tinebase_Model_CustomField_Config::DEF_HOOK => [
                    [CrewScheduling_Controller_EventTypeConfig::class, 'modelConfigHook'],
                ],
            ]
        ], true);
        Tinebase_CustomField::getInstance()->addCustomField($cf);
    }

    public function _initializePersistentObservers()
    {
        $inspectObserver = new Tinebase_Model_PersistentObserver(array(
            'observable_model'      => Calendar_Model_Event::class,
            'observable_identifier' => NULL,
            'observer_model'        => CrewScheduling_Model_EventRoleConfig::class,
            'observer_identifier'   => 'EventType',
            'observed_event'        => Calendar_Event_InspectEvent::class
        ));
        Tinebase_Record_PersistentObserver::getInstance()->addObserver($inspectObserver);
    }
}
