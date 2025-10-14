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

Expanding is on demand: 
Tinebase_Record_Expander::expandRecord($accident); is short for "expand for json" => self::JSON_EXPANDER

You could also define a custom expander with a given $definition:


~~~ php
$expander = new Tinebase_Record_Expander($definition);
$expander->expand(RecordSet);
~~~

System Custom Fields
------

TODO: translate / move to separate file

* Kopieren von GDPR beispielsweise (initialize, ...)
  * Wichtig auch “isReplica” beachten

* Wo ist der unterschied zu normalen customfields?
  *	Normal: zentrale tabelle, nicht im model verankert
  *	Systemcustomfields: werden direkt ins schema eingetragen

* Warum sind die customfields manchmal in einem array “customfields” am record und manchmal als eigene felder
  *	Serverseitig sind normale customfields in einem array ($record->customfields[‘customfield’])
  *	Im client kann man auch so direkt auf die felder zugreifen
  *	Systemcustomfields sind wie normale felder ($record->systemcustomfield)

* Modelconfighook: erlaubt feingranulares überschreiben / manipulieren des models / der felder
  * So kann man auch die reihenfolge der felder beeinflussen

* Auch UI config updates brauchen update script

* Im Client geht man am besten über den fieldmanager.register, wenn man spezielle felder für z.b. system customfields haben möchte

* Verbesserungsideen
    *	“owning app” auch in der cf config tabelle? Wäre vorteil für uninit / disabled apps
