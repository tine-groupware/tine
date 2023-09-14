# tine PHP models

Property Expanding
------

For example, when we define a property in a model of type "RECORDS", like this (class HumanResources_Model_AttendanceRecorderDevice):

~~~ php
    self::FLD_STOPS                 => [
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
~~~

We need an expander config to make the json frontend autmatically "expand" the record:

~~~ php
        self::JSON_EXPANDER             => [
            Tinebase_Record_Expander::EXPANDER_PROPERTIES => [
                self::FLD_STOPS => [
                    Tinebase_Record_Expander::EXPANDER_PROPERTIES => [
                        HumanResources_Model_AttendanceRecorderDeviceRef::FLD_DEVICE_ID => [],
                    ],
                ],
            ],
        ],
~~~

If you need to expand the record in your code (for example in the tests), you can just call the expandRecord() method, like this:

~~~ php
    /* @var HumanResources_Model_AttendanceRecorderDevice $device */
    Tinebase_Record_Expander::expandRecord($device);
~~~