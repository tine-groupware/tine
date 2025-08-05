<?php declare(strict_types=1);
/**
 * class to hold AttendanceRecorderDevice data
 *
 * @package     HumanResources
 * @subpackage  Model
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2022 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * class to hold AttendanceRecorderDevice data
 *
 * @package     HumanResources
 * @subpackage  Model
 */
class HumanResources_Model_AttendanceRecorderDevice extends Tinebase_Record_NewAbstract
{
    const MODEL_NAME_PART = 'AttendanceRecorderDevice';
    const TABLE_NAME = 'humanresources_attendance_record_device';

    const FLD_ALLOW_MULTI_START = 'allowMultiStart';
    const FLD_ALLOW_PAUSE = 'allowPause';
    const FLD_BLPIPE = 'blpipe';
    const FLD_IS_TINE_UI_DEVICE = 'is_tine_ui_device';
    const FLD_NAME = 'name';
    const FLD_PAUSES = 'pauses';
    const FLD_STOPS = 'stops';
    const FLD_STARTS = 'starts';
    const FLD_UNPAUSES = 'unpauses';
    const FLD_DESCRIPTION = 'description';

    const SYSTEM_WORKING_TIME_ID = 'wt00000000000000000000000000000000000000';
    const SYSTEM_PROJECT_TIME_ID = 'pt00000000000000000000000000000000000000';
    const SYSTEM_STANDALONE_PROJECT_TIME_ID = 'pt00000000000000000000000000000000000001';

    /**
     * Holds the model configuration (must be assigned in the concrete class)
     *
     * @var array
     */
    protected static $_modelConfiguration = [
        self::VERSION                   => 3,
        self::APP_NAME                  => HumanResources_Config::APP_NAME,
        self::MODEL_NAME                => self::MODEL_NAME_PART,
        self::MODLOG_ACTIVE             => true,
        self::HAS_DELETED_TIME_UNIQUE   => true,
        self::TITLE_PROPERTY            => self::FLD_NAME,
        self::RECORD_NAME               => 'Attendance Recorder Device', // gettext('GENDER_Attendance Recorder Device')
        self::RECORDS_NAME              => 'Attendance Recorder Devices', // ngettext('Attendance Recorder Device', 'Attendance Recorder Devices', n)
        self::EXPOSE_JSON_API           => true,

        self::JSON_EXPANDER             => [
            Tinebase_Record_Expander::EXPANDER_PROPERTIES => [
                self::FLD_STOPS => [
                    Tinebase_Record_Expander::EXPANDER_PROPERTIES => [
                        HumanResources_Model_AttendanceRecorderDeviceRef::FLD_DEVICE_ID => [],
                    ],
                ],
                self::FLD_STARTS => [
                    Tinebase_Record_Expander::EXPANDER_PROPERTIES => [
                        HumanResources_Model_AttendanceRecorderDeviceRef::FLD_DEVICE_ID => [],
                    ],
                ],
                self::FLD_PAUSES => [
                    Tinebase_Record_Expander::EXPANDER_PROPERTIES => [
                        HumanResources_Model_AttendanceRecorderDeviceRef::FLD_DEVICE_ID => [],
                    ],
                ],
                self::FLD_UNPAUSES => [
                    Tinebase_Record_Expander::EXPANDER_PROPERTIES => [
                        HumanResources_Model_AttendanceRecorderDeviceRef::FLD_DEVICE_ID => [],
                    ],
                ],
                self::FLD_BLPIPE => [
                    Tinebase_Record_Expander::EXPANDER_PROPERTIES => [
                        Tinebase_Model_BLConfig::FLDS_CONFIG_RECORD => [
                            Tinebase_Record_Expander::EXPANDER_PROPERTIES => [
                                HumanResources_Model_BLAttendanceRecorder_TimeSheetConfig::FLD_STATIC_TA => [],
                                HumanResources_Model_BLAttendanceRecorder_TimeSheetConfig::FLD_FILL_GAPS_OF_DEVICES => [],
                            ],
                        ],
                    ],
                ],
            ],
        ],

        self::TABLE                     => [
            self::NAME                      => self::TABLE_NAME,
            self::UNIQUE_CONSTRAINTS        => [
                self::FLD_NAME                  => [
                    self::COLUMNS                   => [self::FLD_NAME, 'deleted_time'],
                ],
            ],
        ],

        self::FIELDS                    => [
            self::FLD_NAME                  => [
                self::LABEL                     => 'Name', // _('Name')
                self::TYPE                      => self::TYPE_STRING,
                self::LENGTH                    => 255,
                self::VALIDATORS                => [
                    Zend_Filter_Input::ALLOW_EMPTY  => false,
                    Zend_Filter_Input::PRESENCE     => Zend_Filter_Input::PRESENCE_REQUIRED,
                ],
            ],
            self::FLD_DESCRIPTION           => [
                self::LABEL                     => 'Description', // _('Description')
                self::TYPE                      => self::TYPE_TEXT,
                self::NULLABLE                  => true,
            ],
            self::FLD_IS_TINE_UI_DEVICE     => [
                self::LABEL                     => 'Show in User Interface', // _('Show in User Interface')
                self::TYPE                      => self::TYPE_BOOLEAN,
                self::DEFAULT_VAL               => false,
            ],
            self::FLD_ALLOW_MULTI_START     => [
                self::LABEL                     => 'Allow Multiple Starts', // _('Allow Multiple Starts')
                self::TYPE                      => self::TYPE_BOOLEAN,
                self::DEFAULT_VAL               => false,
                self::ALLOW_CAMEL_CASE          => true,
            ],
            self::FLD_ALLOW_PAUSE           => [
                self::LABEL                     => 'Allow Pause', // _('Allow Pause')
                self::TYPE                      => self::TYPE_BOOLEAN,
                self::DEFAULT_VAL               => true,
                self::ALLOW_CAMEL_CASE          => true,
            ],
            self::FLD_STOPS                 => [
                self::LABEL                     => 'Stops', // _('Stops')
                self::TYPE                      => self::TYPE_RECORDS,
                self::CONFIG                    => [
                    self::APP_NAME                  => HumanResources_Config::APP_NAME,
                    self::MODEL_NAME                => HumanResources_Model_AttendanceRecorderDeviceRef::MODEL_NAME_PART,
                    self::REF_ID_FIELD              => HumanResources_Model_AttendanceRecorderDeviceRef::FLD_PARENT_ID,
                    self::DEPENDENT_RECORDS         => true,
                    self::FORCE_VALUES              => [
                        HumanResources_Model_AttendanceRecorderDeviceRef::FLD_TYPE => self::FLD_STOPS,
                    ],
                    self::ADD_FILTERS               => [
                        ['field' => HumanResources_Model_AttendanceRecorderDeviceRef::FLD_TYPE, 'operator' => 'equals', 'value' => self::FLD_STOPS],
                    ],
                ],
            ],
            self::FLD_STARTS                => [
                self::LABEL                     => 'Starts', // _('Starts')
                self::TYPE                      => self::TYPE_RECORDS,
                self::CONFIG                    => [
                    self::APP_NAME                  => HumanResources_Config::APP_NAME,
                    self::MODEL_NAME                => HumanResources_Model_AttendanceRecorderDeviceRef::MODEL_NAME_PART,
                    self::REF_ID_FIELD              => HumanResources_Model_AttendanceRecorderDeviceRef::FLD_PARENT_ID,
                    self::DEPENDENT_RECORDS         => true,
                    self::FORCE_VALUES              => [
                        HumanResources_Model_AttendanceRecorderDeviceRef::FLD_TYPE => self::FLD_STARTS,
                    ],
                    self::ADD_FILTERS               => [
                        ['field' => HumanResources_Model_AttendanceRecorderDeviceRef::FLD_TYPE, 'operator' => 'equals', 'value' => self::FLD_STARTS],
                    ],
                ],
            ],
            self::FLD_PAUSES                => [
                self::LABEL                     => 'Pauses', // _('Pauses')
                self::TYPE                      => self::TYPE_RECORDS,
                self::CONFIG                    => [
                    self::APP_NAME                  => HumanResources_Config::APP_NAME,
                    self::MODEL_NAME                => HumanResources_Model_AttendanceRecorderDeviceRef::MODEL_NAME_PART,
                    self::REF_ID_FIELD              => HumanResources_Model_AttendanceRecorderDeviceRef::FLD_PARENT_ID,
                    self::DEPENDENT_RECORDS         => true,
                    self::FORCE_VALUES              => [
                        HumanResources_Model_AttendanceRecorderDeviceRef::FLD_TYPE => self::FLD_PAUSES,
                    ],
                    self::ADD_FILTERS               => [
                        ['field' => HumanResources_Model_AttendanceRecorderDeviceRef::FLD_TYPE, 'operator' => 'equals', 'value' => self::FLD_PAUSES],
                    ],
                ],
            ],
            self::FLD_UNPAUSES              => [
                self::LABEL                     => 'Unpauses', // _('Unpauses')
                self::TYPE                      => self::TYPE_RECORDS,
                self::CONFIG                    => [
                    self::APP_NAME                  => HumanResources_Config::APP_NAME,
                    self::MODEL_NAME                => HumanResources_Model_AttendanceRecorderDeviceRef::MODEL_NAME_PART,
                    self::REF_ID_FIELD              => HumanResources_Model_AttendanceRecorderDeviceRef::FLD_PARENT_ID,
                    self::DEPENDENT_RECORDS         => true,
                    self::FORCE_VALUES              => [
                        HumanResources_Model_AttendanceRecorderDeviceRef::FLD_TYPE => self::FLD_UNPAUSES,
                    ],
                    self::ADD_FILTERS               => [
                        ['field' => HumanResources_Model_AttendanceRecorderDeviceRef::FLD_TYPE, 'operator' => 'equals', 'value' => self::FLD_UNPAUSES],
                    ],
                ],
            ],
            // field restarts?
            self::FLD_BLPIPE                => [
                self::LABEL                     => 'Device Config', // _('Device Config')
                self::TYPE                      => self::TYPE_RECORDS,
                self::NULLABLE                  => true,
                self::CONFIG                    => [
                    self::APP_NAME                  => HumanResources_Config::APP_NAME,
                    self::MODEL_NAME                => HumanResources_Model_BLAttendanceRecorder_Config::MODEL_NAME_PART,
                    self::STORAGE                   => self::TYPE_JSON,
                ],
            ],
        ],
    ];

    /**
     * holds the configuration object (must be declared in the concrete class)
     *
     * @var Tinebase_ModelConfiguration
     */
    protected static $_configurationObject = null;
}
