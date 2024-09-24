<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Configuration
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2013-2024 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 */

use Tinebase_Model_Filter_Abstract as TMFA;

/**
 * Tinebase_ModelConfiguration
 *
 * @package     Tinebase
 * @subpackage  Configuration
 *

 * these properties are availbale throug __get which prefixes them with _
 *
 * @property array      $availableApplications this holds (caches) the availability info of applications globally
 * @property string     $idProperty the id property
 * @property array      $table table definition
 * @property string     $version model version
 * @property string     $identifier legacy
 * @property string     $recordName Human readable name of the record
 * @property string     $recordsName Human readable name of multiple records
 * @property string     $moduleName The name of the module if it doesn't fit to the recordsName, e.g. used in frontend module tree panel
 * @property string     $containerProperty The property of the container, if any
 * @property string     $grantsModel acl grants model
 * @property boolean    $containerUsesFilter set this to false, if no filter and grid column should be created - default is true
 * @property boolean    $hasPersonalContainer set this to false, if personal containers should be ommited - default is true
 * @property string     $titleProperty The property of the title, if any - if an array is given, the second item is the array of arguments for vsprintf, the first the format string
 * @property boolean    $exposeJsonApi If this is true, the json api (smd) is generated automatically
 * @property string     $containerName Human readable name of the container
 * @property string     $containersName Human readable name of multiple containers
 * @property boolean    $hasRelations If this is true, the record has relations
 * @property boolean    $copyRelations If this is true, the record relations are copied
 * @property boolean    $hasCustomFields If this is true, the record has customfields
 * @property boolean    $hasNotes If this is true, the record has notes
 * @property boolean    $hasTags If this is true, the record has tags
 * @property boolean    $hasAttachments If this is true, the record has file attachments
 * @property boolean    $hasAlarms If this is true, the record has alarms
 * @property boolean    $modlogActive If this is true, a modlog will be created
 * @property boolean    $multipleEdit If this is true, multiple edit of records of this model is possible
 * @property string     $multipleEditRequiredRight If multiple edit requires a special right, it's defined here
 * @property boolean    $splitButton if this is set to true, this model will be added to the global add splitbutton
 * @property string     $moduleGroup Group name of this model (will create a parent node in the modulepanel with this name)
 * @property string     $defaultFilter Set the default Filter (defaults to query)
 * @property array      $defaultSortInfo Set the default sort info for the gridpanel (Tine.widgets.grid.GridPanel.defaultSortInfo)
 * @property string     $requiredRight Defines the right to see this model
 * @property boolean    $singularContainerMode no containers
 * @property array      $fields Holds the field definitions in an associative array
 * @property boolean    $resolveVFGlobally if this is set to true, all virtual fields get resolved by the record controller method "resolveVirtualFields"
 * @property array      $recordsFields holds all field definitions of type records
 * @property array      $recordFields holds all field definitions of type record (foreignId fields)
 * @property boolean    $resolveRelated if this is set to true, related data will be fetched on fetching dependent records by frontend json
 * @property array      $virtualFields holds virtual field definitions used for non-persistent fields getting calculated on each call of the record
 * @property boolean    $createModule
 * @property string     $appName the application this configuration belongs to (if the class has the name "Calendar_Model_Event", this will be resolved to "Calendar")
 * @property string     $application legacy
 * @property string     $applicationName legacy
 * @property string     $modelName the name of the model (if the class has the name "Calendar_Model_Event", this will be resolved to "Event")
 * @property array      $fieldKeys holds the keys of all fields
 * @property array      $timeFields holds the time fields
 * @property array      $modlogOmitFields holds the fields which will be omitted in the modlog
 * @property array      $pwFields holds the fields which are passwords
 * @property array      $readOnlyFields these fields will just be readOnly
 * @property array      $datetimeFields holds the datetime fields
 * @property array      $dateFields holds the date fields (maybe we use Tinebase_Date sometimes)
 * @property array      $alarmDateTimeField holds the alarm datetime fields
 * @property array      $filterModel The calculated filters for this model (auto set)
 * @property array      $validators holds the validators for the model (auto set)
 * @property array      $ownValidators holds validators which will be instanciated on model construction
 * @property boolean    $isDependent if a record is dependent to another, this is true
 * @property array      $filters input filters (will be set by field configuration)
 * @property array      $converters converters (will be set by field configuration)
 * @property array      $defaultData Holds the default Data for the model (autoset from field config)
 * @property array      $autoincrementFields holds the fields of type autoincrement (will be auto set by field configuration)
 * @property array      $duplicateCheckFields holds the fields / groups to check for duplicates (will be auto set by field configuration)
 * @property array      $filterProperties properties to collect for the filters (_appName and _modelName are set in the filter)
 * @property array      $modelProperties properties to collect for the model
 * @property array      $frontendProperties properties to collect for the frontend
 * @property string     $group the module group (will be on the same leaf of the content type tree panel)
 * @property array      $modelConfiguration the backend properties holding the collected properties
 * @property array      $frontendConfiguration holds the collected values for the frontendconfig (autoset on first call of getFrontendConfiguration)
 * @property array      $filterConfiguration the backend properties holding the collected properties
 * @property array      $attributeConfig
 * @property array      $filterModelMapping This defines the filters use for all known types
 * @property array      $inputFilterDefaultMapping This maps field types to own validators, which will be instanciated in the constructor.
 * @property array      $validatorMapping This maps field types to their default validators, just zendfw validators can be used here.
 * @property array      $converterDefaultMapping This maps field types to their default converter
 * @property array      $copyOmitFields Collection of copy omit properties for frontend
 * @property array      $keyfieldFields
 * @property array      $jsonExpander
 * @property array      $denormalizedFields
 * @property array      $denormalizationConfig
 * @property string     $delegateAclField
 * @property array|null $grantProtectedFields
 * @property array      $languagesAvailable
 * @property bool       $runConvertToRecordFromJson
 * @property bool       $hasPerspectives
 * @property bool       $hasSystemCustomFields
 * @property bool       $skipLegacyJsonConvert
 */

class Tinebase_ModelConfiguration extends Tinebase_ModelConfiguration_Const
{
    public static $genericProperties = [
        'id',
        'customfields',
        'relations',
        'container_id',
        self::FLD_ACCOUNT_GRANTS,
        'tags',
        'attachments',
        'alarms',
        'xprops',
        'notes',
        'created_by',
        'creation_time',
        'last_modified_by',
        'last_modified_time',
        'seq',
        'deleted_by',
        'deleted_time',
        'is_deleted',
    ];

    /**
     * this holds (caches) the availability info of applications globally
     * 
     * @var array
     */
    static protected $_availableApplications = array('Tinebase' => TRUE);

    /**
     * the id property
     *
     * @var string
     */
    protected $_idProperty = 'id';

    /**
     * the module name
     *
     * @var string
     */
    protected $_moduleName;

    /**
     * frontend property
     *
     * TODO add type
     */
    protected $_copyNoAppendTitle;

    /**
     * table definition
     *
     * @var array
     */
    protected $_table = array();

    /**
     * association definitions (ONE_TO_ONE, MANY_TO_ONE, ...)
     *
     * @var array
     */
    protected $_associations = [];

    /**
     * model version
     *
     * @var integer
     */
    protected $_version = null;
    
    // legacy
    protected $_identifier;

    /**
     * Human readable name of the record
     * add plural translation information in comments like:
     * // ngettext('Record Name', 'Records Name', 1);
     *
     * @var string
     */
    protected $_recordName = NULL;

    /**
     * Human readable name of multiple records
     * add plural translation information in comments like:
     * // ngettext('Record Name', 'Records Name', 2);
     *
     * @var string
     */
    protected $_recordsName = NULL;

    /**
     * The property of the container, if any
     *
     * @var string
     */
    protected $_containerProperty = NULL;

    /**
     * The property of the container, if any
     *
     * @var string
     */
    protected $_extendsContainer = NULL;

    /**
     * The grants model of containers, if any
     *
     * @var string
     */
    protected $_grantsModel = 'Tinebase_Model_Grants';

    /**
     * set this to false, if no filter and grid column should be created
     * 
     * @var boolean
     */
    protected $_containerUsesFilter = TRUE;

    /**
     * set this to false, if personal containers should be ommited
     *
     * @var boolean
     */
    protected $_hasPersonalContainer = TRUE;

    /**
     * The property of the title, if any
     *
     * if an array is given, the second item is the array of arguments for vsprintf, the first the format string
     *
     * @var string/array
     */
    protected $_titleProperty = 'title';

    /**
     * If this is true, the json api (smd) is generated automatically
     *
     * @var boolean
     */
    protected $_exposeJsonApi = NULL;

    /**
     * If this is true, the http api (smd) is generated automatically
     *
     * @var boolean
     */
    protected $_exposeHttpApi = NULL;

    /**
     * skip legacy expanding in json converter _resolveBeforeToArray
     */
    protected bool $_skipLegacyJsonConvert = false;

    /**
     * Human readable name of the container
     * add plural translation information in comments like:
     * // ngettext('Record Name', 'Records Name', 2);
     *
     * @var string
     */
    protected $_containerName = NULL;

    /**
     * Human readable name of multiple containers
     * add plural translation information in comments like:
     * // ngettext('Record Name', 'Records Name', 2);
     *
     * @var string
     */
    protected $_containersName = NULL;

    /**
     * If this is true, the record has relations
     *
     * @var boolean
     */
    protected $_hasRelations = NULL;

    /**
     * relations are copied by default
     *
     * @var bool
     */
    protected $_copyRelations = true;

    /**
     * If this is true, the record has customfields
     *
     * @var boolean
     */
    protected $_hasCustomFields = NULL;

    /**
     * If this is true, the record has system customfields
     *
     * @var boolean
     */
    protected $_hasSystemCustomFields = NULL;

    /** @var bool */
    protected $_hasPerspectives = false;

    /**
     * If this is true, the record has notes
     *
     * @var boolean
     */
    protected $_hasNotes = NULL;

    /**
     * If this is true, the record has tags
     *
     * @var boolean
     */
    protected $_hasTags = NULL;

    /**
     * If this is true, the record has file attachments
     *
     * @var boolean
     */
    protected $_hasAttachments = NULL;

    /**
     * If this is true, the record has alarms
     *
     * @var boolean
     */
    protected $_hasAlarms = NULL;

    /**
     * If this is true, the record has extended properties
     *
     * @var boolean
     */
    protected $_hasXProps = NULL;
    
    /**
     * If this is true, a modlog will be created
     *
     * @var boolean
     */
    protected $_modlogActive = NULL;

    protected $_denormalizationOf = null;

    protected $_isMetadataModelFor = null;

    protected $_denormalizationConfig = [];

    /**
     * If this is true, multiple edit of records of this model is possible.
     *
     *
     * @var boolean
     */
    protected $_multipleEdit = NULL;

    /**
     * If multiple edit requires a special right, it's defined here
     *
     * @var string
     */
    protected $_multipleEditRequiredRight = NULL;

    /**
     * if this is set to true, this model will be added to the global add splitbutton
     *
     * @todo add this to a "frontend configuration / uiConfig"
     * 
     * @var boolen
     */
    protected $_splitButton = FALSE;
    
    /**
     * Group name of this model (will create a parent node in the modulepanel with this name)
     * add translation information in comments like: // _('Group')
     *
     * @var string
     */
    protected $_moduleGroup = NULL;

    /**
     * Set the default Filter (defaults to query)
     *
     * @var string
     */
    protected $_defaultFilter = 'query';

    /**
     * Set the default sort info for the gridpanel (Tine.widgets.grid.GridPanel.defaultSortInfo)
     * set as array('field' => 'title', 'direction' => 'DESC')
     *
     * @var array
     */
    protected $_defaultSortInfo = NULL;

    /**
     * @var null|array<string, array<string>>
     */
    protected $_grantProtectedFields = null;

    /**
     * Defines the right to see this model
     *
     * @var string
     */
    protected $_requiredRight = NULL;

    /**
     * no containers
     * 
     * @var boolean
     */
    protected $_singularContainerMode = NULL;

    protected $_runConvertToRecordFromJson = false;

    /**
     * inspectBeforeUpdateHooks to be called by \Tinebase_Controller_Record_Abstract::_inspectBeforeUpdate
     *
     * @var array
     */
    protected $_controllerHookBeforeUpdate = [];

    protected $_jsonFacadeFields = [];

    /**
     * Holds the field definitions in an associative array where the key
     * corresponds to the db-table name. Possible definitions and their defaults:
     *
     * !! Get sure to have at least one default value set and added one field to the query filter !!
     *
     * - validators: Use Zend Input Filters to validate the values.
     *       @type: Array, @default: array(Zend_Filter_Input::ALLOW_EMPTY => true)
     *
     * - label: The human readable label of the field. If this is set to null, this won't be shown in the auto FE Grid or EditDialog.
     *       Add translation information in comments like: // _('Title')
     *       @type: String, @default: NULL
     *
     * - default: The default value of the field.
     *       Add translation information in comments like: // _('New Car')
     *       @type: as defined (see DEFAULT MAPPING), @default: NULL
     *
     * - duplicateCheckGroup: All Fields having the same group will be combined for duplicate
     *       check. If no group is given, no duplicate check will be done.
     *       @type: String, @default: NULL
     *
     * - type: The type of the Value
     *       @type: String, @default: "string"
     *
     * - specialType: Defines the type closer
     *       @type: String, @default: NULL
     *
     * - filterDefinition: Overwrites the default filter used for this field
     *       Definition knows all from Tinebase_Model_Filter_FilterGroup._filterModel
     *       @type: Array, @default: array('filter' => 'Tinebase_Model_Filter_Text') or the default used for this type (see DEFAULT MAPPING)
     * 
     * - inputFilters: zend input filters to use for this field
     *       @type: Array, use array(<InPutFilterClassName> => <constructorData>, ...)
     * 
     * - queryFilter: If this is set to true, this field will be used by the "query" filter
     *       @type: Boolean, @default: NULL
     *
     * - duplicateOmit: Will neither be shown nor handled on duplicate resolving
     *       @type: Boolean, @default: NULL
     *
     * - copyOmit: If this is set to true, the field won't be used on copy the record
     *       @type: Boolean, @default: NULL
     *
     * - readOnly: If this is set to true, the field can't be updated in BE and will be shown as readOnly in the frontend
     *       @type: Boolean, @default: NULL
     *
     * - disabled: UI Only - If this is set to true, the field will not be shown in the frontend
     *       @type: Boolean, @default: NULL
     *
     * - group: Add this field to a group. Each group will be shown as a separate FieldSet of the
     *       EditDialog and group in the DuplicateResolveGridPanel. If any field of this model
     *       has a group set, FieldSets will be created and fields without a group set will be
     *       added to a group with the same name as the RecordName.
     *       Add translation information in comments like: // _('Group')
     *       @type: String, @default: NULL
     *
     * - dateFormat: If type is a date, the format can be overwritten here
     *       @type: String, @default: NULL or the default used for this type (see DEFAULT MAPPING)
     *
     * - shy: If this is set to true, the row for this field won't be shown in the grid, but can be activated
     * 
     * - system: If this is set to true, fields are marked as internal or system relevant fields which are not important to be shown anywhere.
     *           Unlike shy, the user has no option to see them. At the moment this is only implemented within the export.
     *       @type boolean, @default: null
     * 
     * - sortable: If this is set to false, no sort by this field is possible in the gridpanel, defaults to true
     *
     * - default: Default value for a field if empty
     *      @type: mixed (same as field)
     *
     * - defaultConfig: config field name (+ app) for fetching value for a field if empty
     *      @type: Array
     *      @example: ['appName' => 'Tinebase', 'config' => Tinebase_Config::SALES_TAX]
     *
     *   // TODO: generalize, currently only in ContractGridPanel, take it from there:
     * - showInDetailsPanel: auto show in details panel if any is defined in the js gridpanel class
     * 
     * - useGlobalTranslation: if set, the global translation is used
     * 
     * DEFAULT MAPPING:
     *
     * Field-Type  specialType   Human-Type          SQL-Type JS-Type                       PHP-Type          PHP-Filter                  dateFormat    JS-FilterType
     *
     * date                      Date                datetime date                          Tinebase_DateTime Tinebase_Model_Filter_Date  ISO8601Short
     * datetime                  Date with time      datetime date                          Tinebase_DateTime Tinebase_Model_Filter_Date  ISO8601Long
     * time                      Time                datetime date                          string?           Tinebase_Model_Filter_Time  ISO8601Time
     * string                    Text                varchar  string                        string            Tinebase_Model_Filter_Text
     * text                      Text with lnbr.     text     string                        string            Tinebase_Model_Filter_Text
     * fulltext                  Text with lnbr.     text     string                        string            Tinebase_Model_Filter_FullText
     * boolean                   Boolean             boolean  bool                          bool              Tinebase_Model_Filter_Bool
     * bigint                    Integer             bigint   integer                       int               Tinebase_Model_Filter_Int                 number
     * integer                   Integer             integer  integer                       int               Tinebase_Model_Filter_Int                 number
     * integer     percent       Integer             integer  integer                       int               Tinebase_Model_Filter_Int                 extuxnumberfield
     * integer     bytes         Bytes               integer  integer                       int               Tinebase_Model_Filter_Int
     * integer     seconds       Seconds             integer  integer                       int               Tinebase_Model_Filter_Int
     * integer     minutes       Minutes             integer  integer                       int               Tinebase_Model_Filter_Int
     * float                     Float               float    float                         float             Tinebase_Model_Filter_Int                 extuxnumberfield
     * float       percent       Float               float    float                         float             Tinebase_Model_Filter_Int
     * float       money         value and currency  float    float                         int               Tinebase_Model_Filter_Int
     * note: money is an own type so we can separate currency
     * money                     Float               float    float                         float             Tinebase_Model_Filter_Int
     * json                      Json String         text     string                        array             Tinebase_Model_Filter_Text
     * container                 Container           string   Tine.Tinebase.Model.Container Tinebase_Model_Container                                    tine.widget.container.filtermodel
     * tag tinebase.tag
     * user                      User                string                                 Tinebase_Model_Filter_User
     * keyfield                  ?                   varchar(40) ?                          string(?)         Tinebase_Model_Filter_Text(?)
     * (keyfield must have a 'name' => Addressbook_Config::CONTACT_SALUTATION field definition, optionally an 'application' => 'Calendar' to refer to other applications keyfields)
     *
     * virtual:
     * 
     * Field Type "virtual" has a config property which holds the field configuration.
     * An additional property is "function". If this property is set, the given function
     * will be called to resolve the field in Tinebase_Convert_Json.
     * If an array with two values is given, the first value will be handled as a class,
     * the second one would be handled as a statically callable method.
     * if the array is an associative one with one key and one value, 
     * the key will be used for the classname of a singleton (callable by getInstance),
     * the value will be used as method name.
     * 
     * * record                  1:1 - Relation      text     Tine.<APP>.Model.<MODEL>      Tinebase_Record_Abstract  Tinebase_Model_Filter_ForeignId   Tine.widgets.grid.ForeignRecordFilter
     * * records                 1:n - Relation      -        Array of Record.data Objects  Tinebase_Record_RecordSet -                                 -
     * * relation                m:m - Relation      -        Tinebase.Model.Relation       Tinebase_Model_Relation   Tinebase_Model_Filter_Relation
     * * keyfield                String              string   <as defined>                  string            Tinebase_Model_Filter_Text
     *
     * * Accepts additional parameter: 'config' => array with these keys:
     *     - @string appName    (the name of the application of the referenced record/s)
     *     - @string modelName  (the name of the model of the referenced record/s)
     *     - @boolean doNotCheckModuleRight (set to true to skip the module right check for this field, this allows filters
     *                           and grid columns to be visible for users that do not have the "view" right for the module
     *                           for example: timeaccounts in the timetracker-timesheet grid panel)
     *
     *   Config for 'record' and 'records' accepts also these keys: (optionally if record class name doesn't fit the convention, will be autogenerated, if not set)
     *     - @string recordClassName 
     *     - @string controllerClassName
     *     
     *   Config for 'records' accepts also these keys:
     *     - @string refIdField (the field of the foreign record referencing the idProperty of the own record)
     *     - @array  paging     (accepts the parameters as Tinebase_Model_Pagination does)
     *     - @string filterClassName
     *     - @array  addFilters define them as array like Tinebase_Model_Filter_FilterGroup
     * 
     * record accepts keys additionally
     *     - @string isParent   set this to true if the field is the parent property of an dependent record. This field will be hidden in an edit dialog nested grid
     *     
     * records accepts keys additionally
     *     - @string omitOnSearch set this to FALSE, if the field should be resolved on json-search (defaults to TRUE)
     *     
     * <code>
     *
     * array(
     *     'title' => array(
     *         'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => true),
     *         'label' => NULL,
     *         'duplicateCheckGroup' => 'number'
     *     ),
     *     'number' => ...
     * )
     *
     * </code>
     *
     * @var array
     */
    public $_fields = array();

    /**
     * like _fields, but db only, they are not properties
     *
     * @var array
     */
    protected $_dbColumns = array();
    
    /**
     * if this is set to true, all virtual fields get resolved by the record controller method "resolveVirtualFields"
     *
     * @var boolean
     */
    protected $_resolveVFGlobally = FALSE;

    /**
     * if this is an array, rights are converted to grants. config example:
     *
     *       'convertRightToGrants' => [
     *          'right' => KeyManager_Acl_Rights::MANAGE_KEYS,
     *          'providesGrants' => [
     *              Tinebase_Model_Grants::GRANT_ADD => true,
     *              Tinebase_Model_Grants::GRANT_EDIT => true,
     *              Tinebase_Model_Grants::GRANT_DELETE => true,
     *          ]
     *       ],
     *
     * @var boolean|array
     */
    protected $_convertRightToGrants = false;
    
    /**
     * holds all field definitions of type records
     *
     * @var array
    */
    protected $_recordsFields = NULL;

    /**
     * holds all field definitions of type record (foreignId fields)
     *
     * @var array
     */
    protected $_recordFields  = NULL;

    protected $_denormalizedFields = null;

    /**
     * if this is set to true, related data will be fetched on fetching dependent records by frontend json
     * look at: Tinebase_Convert_Json._resolveMultipleRecordFields
     * 
     * @var boolean
     */
    protected $_resolveRelated = FALSE;
    
    /**
     * holds virtual field definitions used for non-persistent fields getting calculated on each call of the record
     * no backend property will be build, no filters etc. will exist. they must be filled in frontend json
     * 
     * @var array
     */
    protected $_virtualFields = [];

    protected $_createModule = FALSE;
    
    /*
     * auto set by the constructor
    */

    /**
     * the application this configuration belongs to (if the class has the name "Calendar_Model_Event", this will be resolved to "Calendar")
     *
     * @var string
     */
    protected $_appName = NULL;    // this should be used everytime, everywhere
    // legacy
    protected $_application = NULL;
    protected $_applicationName = NULL;

    /**
     * the name of the model (if the class has the name "Calendar_Model_Event", this will be resolved to "Event")
     *
     * @var string
     */
    protected $_modelName = NULL;

    /**
     * holds the keys of all fields
     *
     * @var array
     */
    protected $_fieldKeys = array();

    /**
     * holds the time fields
     *
     * @var array
    */
    protected $_timeFields = array();

    /**
     * holds the fields which will be omitted in the modlog
     *
     * @var array
    */
    protected $_modlogOmitFields = array();

    /**
     * holds the fields which will be replaced in the notes
     *
     * @var array
     */
    protected $_pwFields = [];

    /**
     * these fields will just be readOnly
     *
     * @var array
    */
    protected $_readOnlyFields = array();

    /**
     * holds the datetime fields
     *
     * @var array
    */
    protected $_datetimeFields = array();

    /**
     * holds the keyfield fields
     *
     * @var array
     */
    protected $_keyfieldFields = array();

    /**
     * holds the date fields (maybe we use Tinebase_Date sometimes)
     * 
     * @var array
     */
    protected $_dateFields = array();
    
    /**
     * holds the alarm datetime fields
     *
     * @var array
    */
    protected $_alarmDateTimeField = array();

    /**
     * The calculated filters for this model (auto set)
     *
     * @var array
    */
    protected $_filterModel = array();

    /**
     * holds the validators for the model (auto set)
    */
    protected $_validators = array();

    /**
     * holds validators which will be instanciated on model construction
     *
     * @var array
    */
    protected $_ownValidators = array();
    
    /**
     * if a record is dependent to another, this is true
     * 
     * @var boolean
     */
    protected $_isDependent = FALSE;

    /**
     * if the records acl are delegated to an other record, that field holds the alcs record id
     *
     * @var string|null
     */
    protected $_delegateAclField = null;
    
    /**
     * input filters (will be set by field configuration)
     * 
     * @var array
     */
    protected $_filters;

    /**
     * converters (will be set by field configuration)
     *
     * @var array
     */
    protected $_converters = array();
    
    /**
     * Holds the default Data for the model (autoset from field config)
     *
     * @var array
    */
    protected $_defaultData = array();

    /**
     * holds the fields of type autoincrement (will be auto set by field configuration)
     */
    protected $_autoincrementFields = array();

    /**
     * holds the fields / groups to check for duplicates (will be auto set by field configuration)
    */
    protected $_duplicateCheckFields = NULL;

    /**
     * properties to collect for the filters (_appName and _modelName are set in the filter)
     *
     * @var array
     */
    protected $_filterProperties = array('_filterModel', '_defaultFilter', '_modelName', '_applicationName');

    /**
     * properties to collect for the model
     *
     * @var array
    */
    protected $_modelProperties = array(
        '_identifier', '_timeFields', '_dateFields', '_datetimeFields', '_alarmDateTimeField', '_validators', '_modlogOmitFields',
        '_application', '_readOnlyFields', '_filters'
    );

    /**
     * properties to collect for the frontend
     *
     * @var array
    */
    protected $_frontendProperties = array(
        'containerProperty', 'extendsContainer', 'containersName', 'containerName', 'grantsModel', 'defaultSortInfo', 'fieldKeys', 'filterModel',
        'defaultFilter', 'requiredRight', 'singularContainerMode', 'fields', 'defaultData', 'titleProperty',
        'multipleEdit', 'multipleEditRequiredRight', 'languagesAvailable', 'uiconfig',
        'copyEditAction', 'copyOmitFields', 'recordName', 'recordsName', 'appName', 'modelName', 'createModule', 'moduleName',
        'isDependent', 'hasCustomFields', 'hasSystemCustomFields', 'modlogActive', 'hasNotes', 'hasAttachments', 'hasAlarms',
        'idProperty', 'splitButton', 'attributeConfig', 'hasPersonalContainer', 'import', 'export', 'virtualFields',
        'group', 'multipleEdit', 'multipleEditRequiredRight', 'copyNoAppendTitle', 'denormalizationOf', 'isMetadataModelFor'
    );

    /**
     * the module group (will be on the same leaf of the content type tree panel
     * 
     * @var string
     */
    protected $_group = NULL;
    
    /**
     * the backend properties holding the collected properties
     *
     * @var array
    */
    protected $_modelConfiguration = NULL;

    /**
     * holds the collected values for the frontendconfig (autoset on first call of getFrontendConfiguration)
     * 
     * @var array
     */
    protected $_frontendConfiguration = NULL;
    
    /**
     * the backend properties holding the collected properties
     *
     * @var array
     */
    protected $_filterConfiguration = NULL;

    /**
     *
     * @var array
     */
    protected $_attributeConfig = NULL;

    /*
     * mappings
     */

    /**
     * This defines the filters use for all known types
     * @var array
     */
    protected $_filterModelMapping = array(
        'datetime_separated_date' => Tinebase_Model_Filter_Date::class,
        'datetime_separated_time' => Tinebase_Model_Filter_Date::class,
        'datetime_separated_tz' => Tinebase_Model_Filter_Text::class,
        'date'                  => Tinebase_Model_Filter_Date::class,
        'datetime'              => Tinebase_Model_Filter_DateTime::class,
        'time'                  => Tinebase_Model_Filter_Time::class,
        'string'                => Tinebase_Model_Filter_Text::class,
        'stringAutocomplete'    => Tinebase_Model_Filter_Text::class,
        'text'                  => Tinebase_Model_Filter_Text::class,
        'fulltext'              => Tinebase_Model_Filter_FullText::class,
        self::TYPE_STRICTFULLTEXT        => Tinebase_Model_Filter_StrictFullText::class,
        'json'                  => Tinebase_Model_Filter_Text::class,
        'boolean'               => Tinebase_Model_Filter_Bool::class,
        'integer'               => Tinebase_Model_Filter_Int::class,
        self::TYPE_BIGINT       => Tinebase_Model_Filter_Int::class,
        'float'                 => Tinebase_Model_Filter_Float::class,
        'money'                 => Tinebase_Model_Filter_Float::class,
        self::TYPE_RECORD                => Tinebase_Model_Filter_ForeignId::class,
        'records'               => Tinebase_Model_Filter_ForeignRecords::class,
        'relation'              => Tinebase_Model_Filter_Relation::class,
        'relations'             => Tinebase_Model_Filter_Relation::class,
        'keyfield'              => Tinebase_Model_Filter_Text::class,
        'container'             => Tinebase_Model_Filter_Container::class,
        'tag'                   => Tinebase_Model_Filter_Tag::class,
        'user'                  => Tinebase_Model_Filter_User::class,
        'application'           => Tinebase_Model_Filter_Text::class,
        'numberableStr'         => Tinebase_Model_Filter_Text::class,
        'numberableInt'         => Tinebase_Model_Filter_Int::class,
        'hexcolor'              => Tinebase_Model_Filter_Text::class,
        self::TYPE_MODEL        => Tinebase_Model_Filter_Text::class,
        self::TYPE_LANGUAGE     => Tinebase_Model_Filter_Text::class,
    );

    /**
     * This maps field types to own validators, which will be instanciated in the constructor.
     *
     * @var array
    */
    protected $_inputFilterDefaultMapping = array(
        'text'     => array('Tinebase_Model_InputFilter_CrlfConvert'),
        'fulltext' => array('Tinebase_Model_InputFilter_CrlfConvert'),
        self::TYPE_STRICTFULLTEXT => ['Tinebase_Model_InputFilter_CrlfConvert'],
    );

    /**
     * This maps field types to their default validators, just zend validators can be used here.
     * For using own validators, use _ownValidatorMapping instead. If no validator is given,
     * "array(Zend_Filter_Input::ALLOW_EMPTY => true)" will be used
     *
     * @var array
    */
    protected $_validatorMapping = array(
        self::TYPE_RECORD    => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => NULL),
        'relation'  => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => NULL),
        'hexcolor'  => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => '#969696', Zend_Filter_Input::VALIDATE => array('Regex' => '/^#[0-9a-fA-F]{6}$/'))
    );

    /**
     * This maps field types to their default converter
     *
     * @var array
     */
    protected $_converterDefaultMapping = array(
        'json'      => [Tinebase_Model_Converter_Json::class],
        self::TYPE_PASSWORD => [Tinebase_Model_Converter_Password::class],
    );

    /**
     * If this is true, copy of records of this model is possible.
     *
     * @todo add this to a "frontend configuration / uiConfig"
     *
     * @var boolean
     */
    protected $_copyEditAction = null;

    /**
     * Collection of copy omit properties for frontend
     *
     * @todo add this to a "frontend configuration / uiConfig"
     *
     * @var array
     */
    protected $_copyOmitFields = NULL;

    /**
     * @deprecated -> use definitions
     * import configuration
     *
     * sub keys:
     *  - defaultImportContainerRegistryKey: contains the registry key for model default comtainer
     *      for example:
     *          'defaultImportContainerRegistryKey' => 'defaultInventoryItemContainer',
     *
     * @var array
     */
    protected $_import = NULL;

    /**
     * @deprecated -> use definitions
     * export configuration
     *
     * sub keys:
     *  - supportedFormats: array of supported export formats (in lowercase)
     *      for example:
     *          'supportedFormats' => array('csv', 'ods', 'xls'),
     *
     * @var array
     */
    protected $_export = NULL;

    protected $_recursiveResolvingFields = [];

    protected $_hasDeletedTimeUnique = false;

    protected $_jsonExpander;

    protected $_languagesAvailable;

    protected $_uiconfig;

    protected $_document_id;

    protected static $createdModels = [];

    protected static $deNormalizationCache = [];

    /**
     * the constructor (must be called by the singleton pattern)
     *
     * @var array $modelClassConfiguration
     * @var string $recordClass
     * @throws Tinebase_Exception_Record_DefinitionFailure
     * @throws Tinebase_Exception
     */
    public function __construct($modelClassConfiguration, $recordClass)
    {
        if (! $modelClassConfiguration) {
            throw new Tinebase_Exception('The model class configuration must be submitted!');
        }
        /** @var Tinebase_Record_Interface $recordClass */
        $recordClass::inheritModelConfigHook($modelClassConfiguration);

        // some crude validating
        foreach ($modelClassConfiguration as $propertyName => $propertyValue) {
            $this->{'_' . $propertyName} = $propertyValue;
        }

        $this->_application = $this->_applicationName = $this->_appName;

        // add appName to available applications
        self::$_availableApplications[$this->_appName] = TRUE;

        if (null === $this->_idProperty) {
            $this->_idProperty = 'id';
        }
        $this->_identifier = $this->_idProperty;

        $this->_filters = array();
        if (!isset($this->_fields[$this->_idProperty])) {
            $this->_fields[$this->_idProperty] = array(
                'id' => true,
                'label' => 'ID',
                'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => true),
                'length' => 40,
                'shy' => true,
                'filterDefinition' => [
                    'filter' => 'Tinebase_Model_Filter_Id',
                    'options' => [
                        'idProperty' => $this->_idProperty,
                        'modelName' => $this->_appName . '_Model_' . $this->_modelName
                    ]
                ]
            );
        }

        $hooks = [];
        if ($this->_hasSystemCustomFields) {
            try {
                $modelsSystemCFs = Tinebase_CustomField::getInstance()->getCustomFieldsForApplication($this->_appName,
                    $this->_appName . '_Model_' . $this->_modelName, Tinebase_Model_CustomField_Grant::GRANT_READ,
                    true);
            } catch (Tinebase_Exception_NotFound $tenf) {
                // during install app may not be there yet
                $modelsSystemCFs = [];
            } catch (Zend_Db_Exception $e) {
                // during install app may not be there yet
                $modelsSystemCFs = [];
            }
            /** @var Tinebase_Model_CustomField_Config $cfc */
            foreach ($modelsSystemCFs as $cfc) {
                $definition = $cfc->definition->toArray();
                if (isset($definition[Tinebase_Model_CustomField_Config::DEF_FIELD])) {
                    $this->_fields[$cfc->name] = $definition[Tinebase_Model_CustomField_Config::DEF_FIELD];
                    $this->_fields[$cfc->name][self::SYSTEM_CF] = true;
                }

                if (isset($definition[Tinebase_Model_CustomField_Config::DEF_HOOK])) {
                    foreach ($definition[Tinebase_Model_CustomField_Config::DEF_HOOK] as $hook) {
                        $hooks[] = $hook;
                    }
                }

                if (isset($definition[Tinebase_Model_CustomField_Config::CONTROLLER_HOOKS])) {
                    foreach ($definition[Tinebase_Model_CustomField_Config::CONTROLLER_HOOKS] as $key => $cHooks) {
                        $this->$key = array_merge((array)$this->$key, $cHooks);
                    }
                }
            }
        }

        if ($this->_hasCustomFields) {
            $this->_fields['customfields'] = [
                'label' => 'Custom Fields',
                'shy' => true,
                'sortable' => false,
                'type' => 'custom',
                'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => NULL)
            ];
        }

        if ($this->_hasRelations) {
            $this->_fields['relations'] = [
                'label' => 'Relations',
                'shy' => true,
                'sortable' => false,
                'type' => 'relation',
                'config' => [
                    self::APP_NAME => Tinebase_Config::APP_NAME,
                    self::MODEL_NAME => Tinebase_Model_Relation::MODEL_NAME_PART,
                ],
                self::FILTER_DEFINITION => [],
                'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => NULL),
                'copyOmit' => ! $this->_copyRelations
            ];
        }

        if ($this->_containerProperty) {
            $this->_fields[$this->_containerProperty] = array(
                'nullable'         => true,
                self::LENGTH       => 40,
                'label'            => $this->_containerUsesFilter ? $this->_containerName : NULL,
                'shy'              => true,
                'type'             => 'container',
                'validators'       => array(Zend_Filter_Input::ALLOW_EMPTY => true),
                'filterDefinition' => array(
                    'filter'  => $this->_filterModelMapping['container'],
                    'options' => array('modelName' => $recordClass)
                )
            );
        }
        if ($this->_extendsContainer) {
            if (!$this->_containerProperty) {
                $this->_containerProperty = $this->_extendsContainer;
            } elseif ($this->_containerProperty !== $this->_extendsContainer) {
                throw new Tinebase_Exception_Record_DefinitionFailure('containerProperty and extendsContainer may not be different');
            }
            $this->_fields[$this->_extendsContainer] = array(
                'nullable'         => true,
                self::LENGTH       => 40,
                'label'            => $this->_containerUsesFilter ? $this->_containerName : NULL,
                'shy'              => true,
                'type'             => 'container',
                'validators'       => array(Zend_Filter_Input::ALLOW_EMPTY => true),
                'filterDefinition' => array(
                    'filter'  => $this->_filterModelMapping['container'],
                    'options' => array('modelName' => $recordClass)
                )
            );
            if (!array_key_exists(self::FLD_GRANTS, $this->_fields) &&
                    class_exists($this->_appName . '_Model_' . $this->_modelName . 'Grants')) {
                $this->_fields[self::FLD_GRANTS] = [
                    self::TYPE          => self::TYPE_RECORDS,
                    self::CONFIG        => [
                        self::APP_NAME      => $this->_appName,
                        self::MODEL_NAME    => $this->_modelName . 'Grants',
//                        self::DEPENDENT_RECORDS => true, // breaks create + update
                    ],
                    self::VALIDATORS    => [Zend_Filter_Input::ALLOW_EMPTY => true],
                ];
            }
            if (!array_key_exists(self::FLD_ACCOUNT_GRANTS, $this->_fields) &&
                    class_exists($this->_appName . '_Model_' . $this->_modelName . 'Grants')) {
                $this->_fields[self::FLD_ACCOUNT_GRANTS] = [
                    self::TYPE          => self::TYPE_VIRTUAL,
                    /*self::DOCTRINE_IGNORE => true,
                    self::OMIT_MOD_LOG  => true,
                    self::NORESOLVE     => true,
                    self::CONFIG        => [
                        self::APP_NAME      => $this->_appName,
                        self::MODEL_NAME    => $this->_modelName . 'Grants'
                    ],
                    self::VALIDATORS    => [Zend_Filter_Input::ALLOW_EMPTY => true],*/
                ];
            }
        }
        if (!$this->_containerProperty) {
            $this->_singularContainerMode = true;
            while ($this->_delegateAclField) {
                if (!array_key_exists(self::FLD_ACCOUNT_GRANTS, $this->_fields)) {
                    $this->_fields[self::FLD_ACCOUNT_GRANTS] = [
                        self::TYPE          => self::TYPE_VIRTUAL,
                        /*self::DOCTRINE_IGNORE => true,
                        self::OMIT_MOD_LOG  => true,
                        self::NORESOLVE     => true,
                        self::CONFIG        => [
                            self::APP_NAME      => $this->_appName,
                            self::MODEL_NAME    => $this->_modelName . 'Grants'
                        ],
                        self::VALIDATORS    => [Zend_Filter_Input::ALLOW_EMPTY => true],*/
                    ];
                }
                // you can null everything below here to prevent it from being writte to
                if (!array_key_exists(self::JSON_EXPANDER, $modelClassConfiguration) && !is_array($this->_jsonExpander)) {
                    $this->_jsonExpander = [];
                }
                if (!is_array($this->_jsonExpander)) {
                    break;
                }
                if (!array_key_exists(Tinebase_Record_Expander::EXPANDER_PROPERTY_CLASSES, $this->_jsonExpander)) {
                    $this->_jsonExpander[Tinebase_Record_Expander::EXPANDER_PROPERTY_CLASSES] = [];
                }
                if (!is_array($this->_jsonExpander[Tinebase_Record_Expander::EXPANDER_PROPERTY_CLASSES])) {
                    break;
                }
                if (!array_key_exists(Tinebase_Record_Expander::PROPERTY_CLASS_GRANTS,
                        $this->_jsonExpander[Tinebase_Record_Expander::EXPANDER_PROPERTY_CLASSES])) {
                    $this->_jsonExpander[Tinebase_Record_Expander::EXPANDER_PROPERTY_CLASSES]
                        [Tinebase_Record_Expander::PROPERTY_CLASS_ACCOUNT_GRANTS] = [];
                }
                break;
            }
        }

        // quick hack ('key')
        if ($this->_hasTags) {
            $this->_fields['tags'] = array(
                'label' => 'Tags',
                'sortable' => false,
                'type' => 'tag', 
                'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => NULL), 
                'useGlobalTranslation' => TRUE,
                'filterDefinition' => array(
                    'key'     => 'tag',
                    'filter'  => $this->_filterModelMapping['tag'],
                    'options' => array(
                           'idProperty' => $this->getTableName() . '.' . $this->_idProperty,
                           'applicationName' => $this->_appName
                    )
                )
            );
        }

        if ($this->_hasAttachments) {
            $this->_fields['attachments'] = array(
                'label' => 'Attachments',
                'type'  => 'attachments',
                'recursiveResolving' => true,
                'filterDefinition'  => [
                    'filter'    => 'Tinebase_Model_Filter_RecordAttachment',
                    'options'   => [
                        'idProperty'    => $this->_idProperty,
                        'modelName'     => $this->_appName . '_Model_' . $this->_modelName
                    ]
                ],
                'sortable' => false,
                // node: this config is currently not used here (only in "records" fields)
                'omitOnSearch' => false,
            );
        }

        if ($this->_hasAlarms) {
            $this->_fields['alarms'] = [
                self::LABEL     => null,
                self::TYPE      => self::TYPE_RECORDS,
                self::CONFIG    => [
                    self::APP_NAME      => Tinebase_Config::APP_NAME,
                    self::MODEL_NAME    => Tinebase_Model_Alarm::MODEL_NAME_PART,
                    self::REF_ID_FIELD  => Tinebase_Model_Alarm::FLD_RECORD_ID,
                    self::ADD_FILTERS   => [
                        [TMFA::FIELD => Tinebase_Model_Alarm::FLD_MODEL, TMFA::OPERATOR => TMFA::OP_EQUALS, TMFA::VALUE => $recordClass]
                    ],
                ],
            ];
        }

        if ($this->_hasXProps) {
            $this->_fields['xprops'] = array(
                'label' => NULL,
                'type'  => 'json',
                self::LENGTH => \Doctrine\DBAL\Platforms\MySqlPlatform::LENGTH_LIMIT_TEXT,
                self::NULLABLE => true,
                self::DEFAULT_VAL => null,
                self::DISABLED => true,
                'validators' => [
                    Zend_Filter_Input::ALLOW_EMPTY => true,
                    Zend_Filter_Input::DEFAULT_VALUE => [],
                    Tinebase_Record_Validator_Json::class,
                ],
                self::SYSTEM => true,
            );
        }
        
        if ($this->_modlogActive) {
            // notes are needed if modlog is active
            $this->_fields['notes']              = array('label' => NULL,                 'type' => 'note',     'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => NULL), 'useGlobalTranslation' => TRUE);
            $this->_fields['created_by']         = array('label' => 'Created By',         'type' => 'user',     'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => true), 'shy' => true, 'useGlobalTranslation' => TRUE, 'length' => 40, 'nullable' => true);
            $this->_fields['creation_time']      = array('label' => 'Creation Time',      'type' => 'datetime', 'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => true), 'shy' => true, 'useGlobalTranslation' => TRUE, 'nullable' => true);
            $this->_fields['last_modified_by']   = array('label' => 'Last Modified By',   'type' => 'user',     'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => true), 'shy' => true, 'useGlobalTranslation' => TRUE, 'length' => 40, 'nullable' => true);
            $this->_fields['last_modified_time'] = array('label' => 'Last Modified Time', 'type' => 'datetime', 'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => true), 'shy' => true, 'useGlobalTranslation' => TRUE, 'nullable' => true);
            $this->_fields['seq']                = array('label' => NULL,                 'type' => 'integer', 'system' => true,  'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => true), 'shy' => true, 'useGlobalTranslation' => TRUE, 'default' => 0, 'unsigned' => true);
            
            // don't show deleted information
            $this->_fields['deleted_by']         = array('label' => NULL, 'system' => true, 'type' => 'user',     'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => true), 'useGlobalTranslation' => TRUE, 'length' => 40, 'nullable' => true);
            $this->_fields['deleted_time']       = array('label' => NULL, 'system' => true, 'type' => 'datetime', 'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => true), 'useGlobalTranslation' => TRUE, 'nullable' => true);
            if ($this->_hasDeletedTimeUnique) {
                $this->_fields['deleted_time'][self::NULLABLE] = false;
                $this->_fields['deleted_time'][self::DEFAULT_VAL] = '1970-01-01 00:00:00';
                $this->_fields['deleted_time'][self::CONVERTERS] = [Tinebase_Model_Converter_DateTimeFakeNull::class];
            }
            $this->_fields['is_deleted']         = [
                self::TYPE    => self::TYPE_BOOLEAN,
                self::UNSIGNED => true,
                self::SYSTEM => true,
                self::VALIDATORS => [Zend_Filter_Input::ALLOW_EMPTY => true],
                'useGlobalTranslation' => TRUE,
                self::DEFAULT_VAL => 0
            ];

        } elseif ($this->_hasNotes) {
            $this->_fields['notes'] = array('label' => NULL, 'type' => 'note', 'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => NULL));
        }

        if ($this->_denormalizationOf) {
            list($app, $model) = explode('_Model_', $this->_denormalizationOf);
            $this->_fields[self::FLD_ORIGINAL_ID] = [
                self::TYPE                  => self::TYPE_RECORD,
                self::NULLABLE              => true,
                self::NORESOLVE             => true,
                self::CONFIG                => [
                    self::APP_NAME              => $app,
                    self::MODEL_NAME            => $model,
                ],
            ];
            if (isset($this->_denormalizationConfig[self::TRACK_CHANGES]) &&
                    $this->_denormalizationConfig[self::TRACK_CHANGES]) {
                $this->_fields[self::FLD_LOCALLY_CHANGED] = [
                    self::TYPE                  => self::TYPE_BOOLEAN,
                    self::DEFAULT_VAL           => 0,
                ];
            }
            if (isset($this->_denormalizationConfig[self::CASCADE]) && $this->_denormalizationConfig[self::CASCADE]) {
                if (null === Tinebase_CustomField::getInstance()->getCustomFieldsForApplication($app, $this->_denormalizationOf, Tinebase_Model_CustomField_Grant::GRANT_READ, true, true)
                        ->find('name', self::CASCADE . $this->_appName . $this->_modelName)) {
                    if (true === ($cascade = $this->_denormalizationConfig[self::CASCADE])) {
                        $cascade = [
                            self::ON_UPDATE => [Tinebase_Controller_Record_Abstract::class, 'cascadeDenormalization', $this->_appName . '_Model_' . $this->_modelName],
                        ];
                    }
                    Tinebase_CustomField::getInstance()->addCustomField(new Tinebase_Model_CustomField_Config([
                        'is_system' => true,
                        'name' => self::CASCADE . $this->_appName . $this->_modelName,
                        'application_id' => Tinebase_Application::getInstance()->getApplicationByName($app)->getId(),
                        'model' => $this->_denormalizationOf,
                        'definition' => [
                            Tinebase_Model_CustomField_Config::CONTROLLER_HOOKS => array_merge(
                                isset($cascade[self::ON_UPDATE]) ? [
                                    '_controllerHookBeforeUpdate' => [$cascade[self::ON_UPDATE]]
                                ] : [],
                                isset($cascade[self::ON_DELETE]) ? [
                                    '_controllerHookBeforeDelete' => [$cascade[self::ON_DELETE]]
                                ] : [],
                            )
                        ],
                    ]));
                }
            }
        }

        foreach ($hooks as $hook) {
            if (is_callable($hook)) {
                call_user_func_array($hook, [/*well well legacy... :-/*/&$this->_fields, $this]);
            } else {
                if (Tinebase_Core::isLogLevel(Zend_Log::WARN)) Tinebase_Core::getLogger()->warn(__METHOD__
                    . '::' . __LINE__ . ' Configured hook is not callable: ' . print_r($hook, true));
            }
        }

        $recordClass::modelConfigHook($this->_fields);

        // holds the filters used for the query-filter, if any
        $queryFilters = array();
        
        foreach ($this->_fields as $fieldKey => &$fieldDef) {
            $fieldDef['fieldName'] = $fieldKey;

            if (isset($fieldDef['readOnly']) && $fieldDef['readOnly']) {
                $this->_readOnlyFields[] = $fieldKey;
            }

            // set default type to string, if no type is given
            if (! isset($fieldDef[self::TYPE])) {
                $fieldDef[self::TYPE] = 'string';
            }
            
            // don't handle field if app is not available or feature disabled
            if (isset($fieldDef['config'])
                && in_array($fieldDef[self::TYPE], [self::TYPE_RECORD, self::TYPE_RECORDS, 'virtual'])
                && ! $this->_isAvailable($fieldDef['config']))
            {
                $fieldDef['doctrineIgnore'] = true;
                $fieldDef[self::TYPE] = 'string';
                $fieldDef['label'] = NULL;
                unset($this->_filterModel[$fieldKey]);
                continue;
            }
            // the property name
            $fieldDef['key'] = $fieldKey;

            switch($fieldDef[self::TYPE]) {
                case self::TYPE_KEY_FIELD:
                    $fieldDef['length'] = 40;
                    $keyFieldAppName = isset($fieldDef['config']['application']) ? $fieldDef['config']['application']
                        : $this->_applicationName;
                    if (Tinebase_Application::getInstance()->isInstalled($keyFieldAppName)) {
                        $appConfig = Tinebase_Config::getAppConfig($keyFieldAppName);
                        $keyField = null;
                        if (!isset($fieldDef['name']) || ($appConfig && ! ($keyField =
                                    $appConfig->get($fieldDef['name'])) instanceof Tinebase_Config_KeyField)) {
                            throw new Tinebase_Exception_Record_DefinitionFailure('bad keyfield configuration: ' .
                                $this->_modelName . ' ' . $fieldKey . ' ' . print_r($fieldDef, true));

                            // yes array_key_exists, as you should be able to set default to null
                        } elseif ($keyField && !array_key_exists(self::DEFAULT_VAL, $fieldDef) && is_scalar($keyField->default)) {
                            $fieldDef[self::DEFAULT_VAL] = $keyField->default;
                        }
                    }
                    break;

                case self::TYPE_VIRTUAL:
                    if (!isset($fieldDef['modlogOmit'])) {
                        $fieldDef['modlogOmit'] = true;
                    }
                    $fieldDef['config']['sortable'] = $fieldDef['config']['sortable'] ??
                        (($fieldDef['config'][self::TYPE] ?? null) === self::TYPE_RELATION);
                    $virtualField = $fieldDef['config'];
                    $virtualField['key'] = $fieldKey;
                    if ((isset($virtualField['default']))) {
                        // @todo: better handling of virtualfields
                        $this->_defaultData[$fieldKey] = $virtualField['default'];
                    }
                    $this->_virtualFields[$fieldKey] = $virtualField;
                    break;

                case self::TYPE_NUMBERABLE_STRING:
                case self::TYPE_NUMBERABLE_INT:
                    if (isset($fieldDef[self::CONFIG][Tinebase_Numberable_Abstract::BUCKETKEY]) && $numberableCfgs = Tinebase_Controller_NumberableConfig::getInstance()->search(
                        Tinebase_Model_Filter_FilterGroup::getFilterForModel(Tinebase_Model_NumberableConfig::class, [
                            [TMFA::FIELD => Tinebase_Model_NumberableConfig::FLD_MODEL, TMFA::OPERATOR => TMFA::OP_EQUALS, TMFA::VALUE => $this->_appName . '_Model_' . $this->_modelName],
                            [TMFA::FIELD => Tinebase_Model_NumberableConfig::FLD_PROPERTY, TMFA::OPERATOR => TMFA::OP_EQUALS, TMFA::VALUE => $fieldDef['fieldName']],
                        ]))) {
                        // for UI
                        $fieldDef[self::CONFIG]['configsAvailable'] = $numberableCfgs->toArray();
                        if ($numberableCfg = $numberableCfgs->filter(Tinebase_Model_NumberableConfig::FLD_BUCKET_KEY, $fieldDef[self::CONFIG][Tinebase_Numberable_Abstract::BUCKETKEY])->getFirstRecord()) {
                            $fieldDef[self::CONFIG][Tinebase_Model_NumberableConfig::FLD_EDITABLE] = $numberableCfg->{Tinebase_Model_NumberableConfig::FLD_EDITABLE};
                        }
                    }

                    $this->_autoincrementFields[] = $fieldDef;
                    break;

                case 'image':
                    $fieldDef['label'] = 'Image'; // _('Image')
                    break;

                case self::TYPE_STRING_AUTOCOMPLETE:
                    $fieldDef[self::CONFIG] = isset($fieldDef[self::CONFIG]) && is_array($fieldDef[self::CONFIG]) ? $fieldDef[self::CONFIG] : [];
                    $fieldDef[self::CONFIG][self::APP_NAME] = $this->_appName;
                    $fieldDef[self::CONFIG][self::MODEL_NAME] = $this->_modelName;
                    break;

                case self::TYPE_LOCALIZED_STRING:
                    $fieldDef[self::TYPE] = self::TYPE_RECORDS;
                    if (!isset($fieldDef[self::CONFIG])) {
                        $fieldDef[self::CONFIG] = [];
                    }
                    $fieldDef[self::CONFIG][self::SPECIAL_TYPE] = self::TYPE_LOCALIZED_STRING;
                    if (!array_key_exists(self::APP_NAME, $fieldDef[self::CONFIG])) {
                        $fieldDef[self::CONFIG][self::APP_NAME] = $this->_appName;
                    }
                    if (!array_key_exists(self::MODEL_NAME, $fieldDef[self::CONFIG])) {
                        $fieldDef[self::CONFIG][self::MODEL_NAME] = $this->_modelName . 'Localization';
                    }
                    if (!array_key_exists(self::REF_ID_FIELD, $fieldDef[self::CONFIG])) {
                        $fieldDef[self::CONFIG][self::REF_ID_FIELD] = Tinebase_Record_PropertyLocalization::FLD_RECORD_ID;
                    }
                    if (!array_key_exists(self::DEPENDENT_RECORDS, $fieldDef[self::CONFIG])) {
                        $fieldDef[self::CONFIG][self::DEPENDENT_RECORDS] = true;
                    }
                    if (!array_key_exists(self::ADD_FILTERS, $fieldDef[self::CONFIG])) {
                        $fieldDef[self::CONFIG][self::ADD_FILTERS] = [[
                            'field' => Tinebase_Record_PropertyLocalization::FLD_TYPE,
                            'operator' => 'equals',
                            'value' => $fieldKey,
                        ]];
                    }
                    if (!array_key_exists(self::FORCE_VALUES, $fieldDef[self::CONFIG])) {
                        $fieldDef[self::CONFIG][self::FORCE_VALUES] = [
                            Tinebase_Record_PropertyLocalization::FLD_TYPE => $fieldKey,
                        ];
                    }
                    if (!array_key_exists(self::JSON_EXPANDER, $modelClassConfiguration) && !is_array($this->_jsonExpander)) {
                        $this->_jsonExpander = [];
                    }
                    while (is_array($this->_jsonExpander)) {
                        if (!array_key_exists(Tinebase_Record_Expander::EXPANDER_PROPERTIES, $this->_jsonExpander)) {
                            $this->_jsonExpander[Tinebase_Record_Expander::EXPANDER_PROPERTIES] = [];
                        }
                        if (!is_array($this->_jsonExpander[Tinebase_Record_Expander::EXPANDER_PROPERTIES])) {
                            break;
                        }
                        if (!array_key_exists($fieldKey, $this->_jsonExpander[Tinebase_Record_Expander::EXPANDER_PROPERTIES])) {
                            $this->_jsonExpander[Tinebase_Record_Expander::EXPANDER_PROPERTIES][$fieldKey] = [];
                        }
                        break;
                    }
                    break;
            }

            if ($fieldDef[self::IS_PERSPECTIVE] ?? false) {
                $this->_hasPerspectives = true;
                if (! class_implements($recordClass)[Tinebase_Record_PerspectiveInterface::class] ?? false) {
                    throw new Tinebase_Exception_Record_DefinitionFailure('perspective trait/interface not used in ' . $recordClass);
                }
                $fieldDef[self::DOCTRINE_MAPPING_TYPE] = self::TYPE_JSON;
                if (isset($fieldDef[self::DEFAULT_VAL])) {
                    throw new Tinebase_Exception_Record_DefinitionFailure('perspective property need to use perspective default, not default_val');
                }
                if (!isset($fieldDef[self::PERSPECTIVE_DEFAULT])) {
                    $fieldDef[self::PERSPECTIVE_DEFAULT] = null;
                }

                if (!array_key_exists(self::CONVERTERS, $fieldDef)) {
                    $fieldDef[self::CONVERTERS] = [Tinebase_Model_Converter_Perspective::class];
                }
                if (($this->_converterDefaultMapping[$fieldDef[self::TYPE]] ?? false) && !array_key_exists(self::PERSPECTIVE_CONVERTERS, $fieldDef)) {
                    $fieldDef[self::PERSPECTIVE_CONVERTERS] = $this->_converterDefaultMapping[$fieldDef[self::TYPE]];
                }
                $fieldDef[self::NULLABLE] = true;
            }

            if (isset($fieldDef[self::IS_VIRTUAL])) {
                $virtualField = isset($fieldDef['config']) ? $fieldDef['config'] : [];
                $virtualField['key'] = $fieldKey;
                $this->_virtualFields[$fieldKey] = $virtualField;
            }

            if (isset($fieldDef['copyOmit']) && $fieldDef['copyOmit']) {
                if (!is_array($this->_copyOmitFields)) {
                    $this->_copyOmitFields = array();
                }
                $this->_copyOmitFields[] = $fieldKey;
            }

            // set default value
            if (isset($fieldDef['defaultValConfig'])) {
                $config = Tinebase_Config::getAppConfig($fieldDef['defaultValConfig']['appName']);
                $fieldDef['default'] = $config->get($fieldDef['defaultValConfig']['config']);
            }
            // TODO: implement complex default values (maybe use defaultConfig for this?)
            if (isset($fieldDef['default'])) {
//                 // allows dynamic default values
//                 if (is_array($fieldDef['default'])) {
//                     switch ($fieldDef[self::TYPE]) {
//                         case 'time':
//                         case 'date':
//                         case 'datetime':
//                         default:
//                             throw new Tinebase_Exception_NotImplemented($_message);
//                     }
//                 } else {
                    $this->_defaultData[$fieldKey] = $fieldDef['default'];
            }

            // TODO: Split this up in multiple functions
            // TODO: Refactor: key 'tag' should be 'tags' in filter definition / quick hack
            // also see ticket 8944 (https://forge.tine20.org/mantisbt/view.php?id=8944)
            
            $this->_setFieldFilterModel($fieldDef, $fieldKey);

            if (isset($fieldDef['queryFilter']) && $fieldDef['queryFilter']) {
                $queryFilters[] = $fieldKey;
            }

            // set validators
            if (isset($fieldDef['validators'])) {
                // use _validators from definition
                $this->_validators[$fieldKey] = $fieldDef['validators'];
            } else if ((isset($this->_validatorMapping[$fieldDef[self::TYPE]]) || array_key_exists($fieldDef[self::TYPE], $this->_validatorMapping))) {
                // if no validatorsDefinition is given, try to use the default one
                $fieldDef['validators'] = $this->_validators[$fieldKey] = $this->_validatorMapping[$fieldDef[self::TYPE]];
            } else {
                $fieldDef['validators'] = $this->_validators[$fieldKey] = array(Zend_Filter_Input::ALLOW_EMPTY => true);
            }
            
            // set input filters, append defined if any or use defaults from _inputFilterDefaultMapping 
            if (isset($fieldDef['inputFilters'])) {
                foreach ($fieldDef['inputFilters'] as $if => $val) {
                    if (is_array($val)) {
                        $reflect  = new ReflectionClass($if);
                        $this->_filters[$fieldKey][] = $reflect->newInstanceArgs($val);
                    } else {
                        if ($if && !is_int($if)) {
                            $this->_filters[$fieldKey][] = new $if($val);
                        } elseif (class_exists($val)) {
                            $this->_filters[$fieldKey][] = new $val();
                        }
                    }
                }
            } elseif (isset($this->_inputFilterDefaultMapping[$fieldDef[self::TYPE]])) {
                foreach ($this->_inputFilterDefaultMapping[$fieldDef[self::TYPE]] as $if => $val) {
                    $this->_filters[$fieldKey][] = $if ? new $if($val) : new $val();
                }
            }

            $this->_addToModlogOmit($fieldDef, $fieldKey);
            $this->_addToPasswordFields($fieldDef, $fieldKey);

            // set converters
            foreach ((isset($fieldDef['converters']) && is_array($fieldDef['converters'])) ? $fieldDef['converters'] :
                         (isset($this->_converterDefaultMapping[$fieldDef[self::TYPE]]) ?
                             $this->_converterDefaultMapping[$fieldDef[self::TYPE]] : []) as $converter) {
                if (!isset($this->_converters[$fieldKey])) {
                    $this->_converters[$fieldKey] = [];
                }
                if (! class_exists($converter)) {
                    if (Tinebase_Core::isLogLevel(Zend_Log::WARN)) Tinebase_Core::getLogger()->warn(__METHOD__
                        . '::' . __LINE__ . ' Could not create converter ' . $converter . ' for model ' . $this->_modelName);
                } else {
                    $this->_converters[$fieldKey][] = new $converter();
                }
            }

            if (isset($fieldDef['recursiveResolving'])) {
                $this->_recursiveResolvingFields[] = $fieldKey;
            }
            
            $this->_populateProperties($fieldKey, $fieldDef);
        }
        
        // set some default filters
        $this->_getQueryFilter($queryFilters);
        if (!isset($this->_filterModel[$this->_idProperty])) {
            $this->_filterModel[$this->_idProperty] = [
                'filter'    => 'Tinebase_Model_Filter_Id',
                'options'   => [
                    'idProperty'    => $this->_idProperty,
                    'modelName'     => $this->_appName . '_Model_' . $this->_modelName
                ]
            ];
        }
        $this->_fieldKeys = array_keys($this->_fields);

        self::$createdModels[] = $recordClass;
    }

    /**
     * add field to modlog omit, if configured and modlog is used OR field is password
     *
     * @param array $fieldDef
     * @param string $fieldKey
     * @return void
     */
    protected function _addToModlogOmit(array $fieldDef, string $fieldKey): void
    {
        if ($this->_modlogActive && isset($fieldDef['modlogOmit']) && $fieldDef['modlogOmit']) {
            $this->_modlogOmitFields[] = $fieldKey;
        }
    }

    /**
     * add field to modlog omit, if configured and modlog is used OR field is password
     *
     * @param array $fieldDef
     * @param string $fieldKey
     * @return void
     */
    protected function _addToPasswordFields(array $fieldDef, string $fieldKey): void
    {
        if (isset($fieldDef[self::SPECIAL_TYPE]) && $fieldDef[self::SPECIAL_TYPE] === self::SPECIAL_TYPE_PASSWORD) {
            $this->_pwFields[] = $fieldKey;
        }
    }

    /**
     * set filter model for field
     *
     * @param $fieldDef
     * @param $fieldKey
     */
    protected function _setFieldFilterModel($fieldDef, $fieldKey)
    {
        if (isset($fieldDef['filterDefinition'])) {
            // use filter from definition
            if (empty($fieldDef[self::FILTER_DEFINITION])) {
                return;
            }
            $key = isset($fieldDef['filterDefinition']['key']) ? $fieldDef['filterDefinition']['key'] : $fieldKey;
            if (isset($this->_filterModel[$key])) {
                return;
            }

            if ((isset($fieldDef['config']) || array_key_exists('config', $fieldDef))
                && in_array($fieldDef[self::TYPE], [self::TYPE_RECORD, self::TYPE_RECORDS, 'virtual'])
                && ! $this->_isAvailable($fieldDef['config']))
            {
                return;
            }

            $this->_filterModel[$key] = $fieldDef['filterDefinition'];
        } else {
            if (isset($this->_filterModel[$fieldKey])) {
                return;
            }
            $type = $fieldDef[self::TYPE];
            $config = isset($fieldDef['config']) ? $fieldDef['config'] : null;
            if (self::TYPE_VIRTUAL === $type && isset($fieldDef['config']) && isset($fieldDef['config'][self::TYPE]) &&
                    (self::TYPE_RELATION === $fieldDef['config'][self::TYPE] || self::TYPE_RELATIONS ===
                        $fieldDef['config'][self::TYPE])) {
                $type = $fieldDef['config'][self::TYPE];
                if (isset($config['config'])) {
                    $config = $config['config'];
                }
                if (isset($config['appName']) && isset($config['modelName'])) {
                    $config['related_model'] = $config['appName'] . '_Model_' . $config['modelName'];
                }
                if (!isset($config['own_model'])) {
                    $config['own_model'] = $this->_getPhpClassName();
                }
            }
            if (isset($this->_filterModelMapping[$type])) {
                // if no filterDefinition is given, try to use the default one
                $this->_filterModel[$fieldKey] = array('filter' => $this->_filterModelMapping[$type]);
                if (null !== $config && isset($config['appName']) && isset($config['modelName'])) {
                    $this->_filterModel[$fieldKey]['options'] = $config;

                    // set id filter controller
                    if ($type === self::TYPE_RECORD || $type === self::TYPE_RECORDS) {
                        if ($type === self::TYPE_RECORD && ($config[self::REF_ID_FIELD] ?? false)) {
                            $this->_filterModel[$fieldKey]['filter'] = $this->_filterModelMapping[self::TYPE_RECORDS];
                        }
                        $this->_filterModel[$fieldKey]['options']['filtergroup'] =
                            ($config['recordClassName'] ?? ($config['appName'] . '_Model_' . $config['modelName'])) . 'Filter';
                        $this->_filterModel[$fieldKey]['options']['controller']  =
                            $config['controllerClassName'] ?? ($config['appName'] . '_Controller_' . $config['modelName']);
                    }
                } else if ($type === self::TYPE_RELATION || $type === self::TYPE_RELATIONS) {
                    unset($this->_filterModel[$fieldKey]);
                }
            }
        }
    }

    /**
     * get table name for model
     *
     * @return string
     * @throws Tinebase_Exception_AccessDenied
     * @throws Tinebase_Exception_NotFound
     */
    public function getTableName()
    {
        if (is_array($this->_table) && isset($this->_table['name'])) {
            $tableName = $this->_table['name'];
        } else {
            $tableName = null;
            try {
                // legacy way to find out table name, model conf should bring its table name
                if (null !== ($appInstance = Tinebase_Core::getApplicationInstance(
                        $this->_applicationName, $this->_modelName, /* $_ignoreACL */
                        true))
                    && method_exists($appInstance, 'getBackend')
                    && null !== ($backend = $appInstance->getBackend())) {
                    $tableName = $backend->getTableName();
                }
            } catch (Tinebase_Exception_NotFound $tenf) {}
        }

        return $tableName;
    }

    public function getTableNameForField(string $field): string
    {
        if (!isset($this->_fields[$field])) {
            throw new Tinebase_Exception($field . ' is not a property of ' . $this->_modelName);
        }
        if (isset($this->_fields[$field][self::TABLE])) {
            return $this->_fields[$field][self::TABLE];
        }
        return $this->getTableName();
    }

    /**
     * constructs the query filter
     *
     * adds ExplicitRelatedRecords-filters to query filter (relatedModels) to allow search in relations
     *
     * @param array $queryFilters
     *
     * @see 0011494: activate advanced search for contracts (customers, ...)
     */
    protected function _getQueryFilter(array $queryFilters)
    {
        if (! empty($this->_filterModel['query'])) {
            // model has custom query filter ... skipping this
            return;
        }

        $relatedModels = array();
        foreach ($this->_filterModel as $name => $filter) {
            if ($filter['filter'] === 'Tinebase_Model_Filter_ExplicitRelatedRecord') {
                $relatedModels[] = $filter['options']['related_model'];
            }
            if (isset($filter[self::QUERY_FILTER]) && $filter[self::QUERY_FILTER]) {
                $queryFilters[] = $name;
            }
        }

        if (empty($queryFilters) && in_array($this->_titleProperty, array_keys($this->_fields))) {
            if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__
                . '::' . __LINE__ . ' Adding title prop as default query filter to ' . $this->_modelName);
            $queryFilters[] = $this->_titleProperty;
        }

        if (! empty($queryFilters)) {
            $queryFilterData = array(
                'label' => 'Quick Search',
                'field' => 'query',
                'filter' => 'Tinebase_Model_Filter_Query',
                'useGlobalTranslation' => true,
                'options' => array(
                    'fields' => array_unique($queryFilters),
                    'modelName' => $this->_getPhpClassName(),
                )
            );

            if (count($relatedModels) > 0) {
                $queryFilterData['options']['relatedModels'] = array_unique($relatedModels);
            }

            $this->_filterModel['query'] = $queryFilterData;
        }
    }

    /**
     * get modelconfig for an array of models
     *
     * @param array $models
     * @param string $appname
     * @return array
     */
    public static function getFrontendConfigForModels($models, $appname = null)
    {
        $modelconfig = array();
        if (is_array($models)) {
            foreach ($models as $modelName) {
                /** @var Tinebase_Record_Abstract $recordClass */
                $recordClass = $appname ? $appname . '_Model_' . $modelName : $modelName;
                $modelName = preg_replace('/^.+_Model_/', '', $modelName);
                // ... well, need to run through the Tinebase_Model_Grants constructor magic at least once for each Grant Model...
                new $recordClass([], true);
                $config = $recordClass::getConfiguration();
                if ($config) {
                    $modelconfig[$modelName] = $config->getFrontendConfiguration();
                }
            }
        }

        return $modelconfig;
    }

    public function getAutoincrementFields()
    {
        return $this->_autoincrementFields;
    }

    public function getIdProperty()
    {
        return $this->_idProperty;
    }

    public function getContainerProperty()
    {
        return $this->_containerProperty ?: $this->_extendsContainer;
    }

    public function getTable()
    {
        return $this->_table;
    }

    public function setTable(array $table)
    {
        $this->_table = $table;
    }

    public function getVersion()
    {
        return $this->_version;
    }

    public function getAppName()
    {
        return $this->_appName;
    }

    public function getModelName()
    {
        return $this->_modelName;
    }

    public function setFilterModel(array $fm): void
    {
        $this->_filterModel = $fm;
    }

    public function getFieldModel($field)
    {
        if (isset($this->_fields[$field]) && isset($this->_fields[$field][self::TYPE])) {
            switch ($this->_fields[$field][self::TYPE]) {
                case 'user':
                    return Tinebase_Model_User::class;
                case 'relation':
                    return Tinebase_Model_Relation::class;
                case 'tag':
                    return Tinebase_Model_Tag::class;
                case 'attachments':
                    return Tinebase_Model_Tree_Node::class;
                case 'note':
                    return Tinebase_Model_Note::class;
                case 'container':
                    return Tinebase_Model_Container::class;
                case self::TYPE_RECORD:
                    return $this->_recordFields[$field][self::CONFIG][self::RECORD_CLASS_NAME];
                case self::TYPE_RECORDS:
                    return $this->_recordsFields[$field][self::CONFIG][self::RECORD_CLASS_NAME];
                case self::TYPE_DYNAMIC_RECORD:
                    return self::TYPE_DYNAMIC_RECORD;
                case self::TYPE_VIRTUAL:
                    if (isset($this->_fields[$field][self::CONFIG][self::TYPE]) && (self::TYPE_RELATION ===
                            $this->_fields[$field][self::CONFIG][self::TYPE] || self::TYPE_RELATIONS ===
                            $this->_fields[$field][self::CONFIG][self::TYPE] || self::TYPE_PRE_EXPANDED ===
                            $this->_fields[$field][self::CONFIG][self::TYPE])) {
                        return $this->_fields[$field][self::CONFIG][self::CONFIG][self::RECORD_CLASS_NAME];
                    }
            }
        }
        return null;
    }
    /**
     * populate model config properties
     * 
     * @param string $fieldKey
     * @param array &$fieldDef
     */
    protected function _populateProperties($fieldKey, &$fieldDef)
    {
        if (isset($fieldDef[self::REQUIRED_GRANTS]) && is_array($fieldDef[self::REQUIRED_GRANTS])) {
            foreach ($fieldDef[self::REQUIRED_GRANTS] as $key => $acl) {
                if (is_int($key)) {
                    $key = Tinebase_Controller_Record_Abstract::ACTION_ALL;
                }
                if (!isset($this->_grantProtectedFields[$key])) {
                    $this->_grantProtectedFields[$key] = [];
                }
                if (!isset($this->_grantProtectedFields[$key][$acl])) {
                    $this->_grantProtectedFields[$key][$acl] = [];
                }
                $this->_grantProtectedFields[$key][$acl][] = $fieldKey;
            }
        }
        switch ($fieldDef[self::TYPE]) {
            case self::TYPE_JSON:
            case self::TYPE_MODEL:
            case 'string':
            case 'text':
            case 'fulltext':
            case self::TYPE_STRICTFULLTEXT:
            case 'integer':
            case self::TYPE_BIGINT:
            case 'float':
            case 'boolean':
            case 'container':
                break;
            case 'datetime_separated_date':
            case 'date':
                // add to datetime fields
                $this->_dateFields[] = $fieldKey;
                break;
            case 'datetime':
                // add to alarm fields
                if (isset($fieldDef['alarm'])) {
                    $this->_alarmDateTimeField = $fieldKey;
                }
                // add to datetime fields
                $this->_datetimeFields[] = $fieldKey;
                break;
            case 'time':
                // add to timefields
                $this->_timeFields[] = $fieldKey;
                break;
            case 'user':
                $fieldDef['config'] = array(
                    'length'                  => 40,
                    'appName'                 => 'Tinebase',
                    'modelName'               => 'User',
                    'type'                    => self::TYPE_RECORD,
                    'recordClassName'         => Tinebase_Model_User::class,
                    'controllerClassName'     => Tinebase_User::class,
                    'filterClassName'         => Tinebase_Model_FullUserFilter::class,
                );
                $this->_recordFields[$fieldKey] = $fieldDef;
                break;
            /** @noinspection PhpMissingBreakStatementInspection */
            case self::TYPE_RECORD:
                if (isset($this->_filterModel[$fieldKey]['options'])) {
                    $this->_filterModel[$fieldKey]['options']['controller'] = $this->_getPhpClassName($this->_filterModel[$fieldKey]['options'], 'Controller');
                    $this->_filterModel[$fieldKey]['options']['filtergroup'] = $this->_getPhpClassName($this->_filterModel[$fieldKey]['options'], 'Model') . 'Filter';
                }
            case self::TYPE_RECORDS:
                if (!isset($fieldDef[self::CONFIG])) {
                    break;
                }
                if (!isset($fieldDef[self::CONFIG][self::RECORD_CLASS_NAME])) {
                    if (! isset($fieldDef[self::CONFIG]['appName'])) {
                        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(
                            __METHOD__ . '::' . __LINE__ . ' appName missing in config for field ' . $fieldKey
                            . ' of model ' . $this->_modelName);
                        break;
                    }
                    $fieldDef[self::CONFIG][self::RECORD_CLASS_NAME] = $this->_getPhpClassName($fieldDef[self::CONFIG]);
                    if (!class_exists($fieldDef[self::CONFIG][self::RECORD_CLASS_NAME])) {
                        Tinebase_Core::getLogger()->err(__METHOD__ . '::' . __LINE__ . ' Record class does not exists '
                            . $fieldKey . ' ' . print_r($fieldDef, true));
                        break;
                    }
                }
                $fieldDef['config']['controllerClassName'] = isset($fieldDef['config']['controllerClassName']) ? $fieldDef['config']['controllerClassName'] : $this->_getPhpClassName($fieldDef['config'], 'Controller');
                $fieldDef['config']['filterClassName']     = isset($fieldDef['config']['filterClassName'])     ? $fieldDef['config']['filterClassName']     : $this->_getPhpClassName($fieldDef['config']) . 'Filter';

                // resolve self or circular references
                static::$deNormalizationCache[$this->_appName . '_Model_' . $this->_modelName] = $this->_denormalizationOf ?: false;
                $deNormOf = null;
                if (isset($fieldDef[self::CONFIG][self::IS_PARENT]) && $fieldDef[self::CONFIG][self::IS_PARENT]) {
                    unset($fieldDef[self::CONFIG][self::DENORMALIZATION_OF]);
                } elseif (isset(static::$deNormalizationCache[$fieldDef[self::CONFIG][self::RECORD_CLASS_NAME]])) {
                    if (static::$deNormalizationCache[$fieldDef[self::CONFIG][self::RECORD_CLASS_NAME]]) {
                        $deNormOf = static::$deNormalizationCache[$fieldDef[self::CONFIG][self::RECORD_CLASS_NAME]];
                    } else {
                        unset($fieldDef[self::CONFIG][self::DENORMALIZATION_OF]);
                    }
                } elseif (($mc = $fieldDef[self::CONFIG][self::RECORD_CLASS_NAME]::getConfiguration()) && $deNormOf = $mc->denormalizationOf) {
                    static::$deNormalizationCache[$fieldDef[self::CONFIG][self::RECORD_CLASS_NAME]] = $deNormOf;
                } else {
                    unset($fieldDef[self::CONFIG][self::DENORMALIZATION_OF]);
                    static::$deNormalizationCache[$fieldDef[self::CONFIG][self::RECORD_CLASS_NAME]] = false;
                }
                if ($deNormOf && array_key_exists(self::DENORMALIZATION_OF, $fieldDef[self::CONFIG]) &&
                        null === $fieldDef[self::CONFIG][self::DENORMALIZATION_OF]) {
                    $deNormOf = null;
                }
                if ($deNormOf) {
                    $fieldDef[self::CONFIG][self::DENORMALIZATION_OF] = $deNormOf;
                    $fieldDef[self::CONFIG][self::DEPENDENT_RECORDS] = true;
                    if (self::TYPE_RECORD === $fieldDef[self::TYPE] && !isset($fieldDef[self::FILTER_DEFINITION])) {
                        if (!isset($fieldDef[self::CONFIG][self::REF_ID_FIELD])) {
                            throw new Tinebase_Exception_Record_DefinitionFailure($this->_modelName . '::' . $fieldKey . ' is missing the ' . self::REF_ID_FIELD);
                        }
                        $fieldDef[self::FILTER_DEFINITION] = [
                            self::FILTER                => Tinebase_Model_Filter_ForeignRecords::class,
                            self::OPTIONS => [
                                self::APP_NAME              => $fieldDef[self::CONFIG][self::APP_NAME],
                                self::MODEL_NAME            => $fieldDef[self::CONFIG][self::MODEL_NAME],
                                self::REF_ID_FIELD          => $fieldDef[self::CONFIG][self::REF_ID_FIELD],
                                self::CONTROLLER            => $fieldDef[self::CONFIG][self::CONTROLLER_CLASS_NAME],
                                self::RECORD_CLASS_NAME     => $fieldDef[self::CONFIG][self::RECORD_CLASS_NAME],
                            ],
                        ];
                        unset($this->_filterModel[$fieldKey]);
                        $this->_setFieldFilterModel($fieldDef, $fieldKey);
                    }
                }
                $fieldDef['config']['dependentRecords'] = isset($fieldDef['config']['dependentRecords']) ? $fieldDef['config']['dependentRecords'] : false;
                if ($fieldDef[self::TYPE] === self::TYPE_RECORD) {
                    if ($fieldDef[self::CONFIG][self::REF_ID_FIELD] ?? false) {
                        $fieldDef[self::DOCTRINE_IGNORE] = true;
                    } else {
                        $fieldDef['config']['length'] = 40;
                    }
                    $this->_recordFields[$fieldKey] = $fieldDef;
                    if (!($fieldDef[self::DOCTRINE_IGNORE] ?? false)) {
                        $this->_converters[$fieldKey] = [new Tinebase_Model_Converter_Record()];
                    }
                } else {
                    $this->_recordsFields[$fieldKey] = $fieldDef;
                    if (isset($fieldDef[self::CONFIG][self::STORAGE]) && self::TYPE_JSON === $fieldDef[self::CONFIG][self::STORAGE] &&
                            !isset($this->_converters[$fieldKey])) {
                        $this->_converters[$fieldKey] = [new Tinebase_Model_Converter_JsonRecordSet()];
                    }
                    if (isset($fieldDef[self::CONFIG][self::STORAGE]) && self::TYPE_JSON_REFID === $fieldDef[self::CONFIG][self::STORAGE] &&
                            !isset($this->_converters[$fieldKey])) {
                        $this->_converters[$fieldKey] = [new Tinebase_Model_Converter_JsonRecordSet(true)];
                    }
                }
                if ($deNormOf) {
                    $this->_denormalizedFields[$fieldKey] = $fieldDef;
                }
                if (isset($fieldDef[self::CONFIG][self::JSON_FACADE])) {
                    $this->_jsonFacadeFields[$fieldKey] = $fieldDef;
                }
                break;
            case self::TYPE_DYNAMIC_RECORD:
                if (!isset($this->_converters[$fieldKey])) {
                    if (isset($fieldDef[self::CONFIG][self::REF_MODEL_FIELD]) || isset($fieldDef[self::CONFIG][self::MODEL_NAME])) {
                        $this->_converters[$fieldKey] = [new Tinebase_Model_Converter_DynamicRecord(
                            isset($fieldDef[self::CONFIG][self::REF_MODEL_FIELD]) ?
                                $fieldDef[self::CONFIG][self::REF_MODEL_FIELD] : null,
                            isset($fieldDef[self::CONFIG][self::PERSISTENT]) ?
                                $fieldDef[self::CONFIG][self::PERSISTENT] : false,
                            isset($fieldDef[self::CONFIG][self::MODEL_NAME]) ?
                                $fieldDef[self::CONFIG][self::MODEL_NAME] : null
                        )];
                    }
                }
                break;
            case 'custom':
                try {
                    // prepend table name to id prop because of ambiguous ids
                    $this->_filterModel['customfield'] = array(
                        'filter' => 'Tinebase_Model_Filter_CustomField', 
                        'options' => array(
                            'idProperty' => $this->getTableName() . '.' . $this->_idProperty
                        )
                    );
                } catch (Exception $e) {
                    // no customfield filter available (yet?)
                    Tinebase_Exception::log($e);
                }
                break;
            case 'virtual':
                if (isset($fieldDef[self::CONFIG][self::TYPE])) {
                    switch ($fieldDef[self::CONFIG][self::TYPE]) {
                        case self::TYPE_DATE:
                            $this->_dateFields[] = $fieldKey;
                            break;
                        case self::TYPE_DATETIME:
                            $this->_datetimeFields[] = $fieldKey;
                            break;
                        case self::TYPE_RELATIONS:
                        case self::TYPE_RELATION:
                            if (!isset($fieldDef[self::CONFIG][self::CONFIG][self::RECORD_CLASS_NAME])) {
                                $fieldDef[self::CONFIG][self::CONFIG][self::RECORD_CLASS_NAME] = $this
                                    ->_getPhpClassName($fieldDef[self::CONFIG][self::CONFIG]);
                            }
                            if (!isset($fieldDef[self::CONFIG][self::CONFIG][self::DEGREE])) {
                                $fieldDef[self::CONFIG][self::CONFIG][self::DEGREE] =
                                    Tinebase_Model_Relation::DEGREE_SIBLING;
                            }
                            /*if (!isset($fieldDef[self::CONFIG][self::CONFIG][self::CONTROLLER_CLASS_NAME])) {
                                $fieldDef[self::CONFIG][self::CONFIG][self::CONTROLLER_CLASS_NAME] = $this
                                    ->_getPhpClassName($fieldDef[self::CONFIG][self::CONFIG], 'Controller');
                            }
                            if (!isset($fieldDef[self::CONFIG][self::CONFIG][self::FILTER_CLASS_NAME])) {
                                $fieldDef[self::CONFIG][self::CONFIG][self::FILTER_CLASS_NAME] = $this
                                    ->_getPhpClassName($fieldDef[self::CONFIG][self::CONFIG]) . 'Filter';
                            }*/
                            break;
                    }
                }
                break;
            case 'keyfield':
                $this->_keyfieldFields[] = $fieldKey;
                break;
            default:
                break;
        }
    }
    
    /**
     * returns an instance of the record controller
     * 
     * @return Tinebase_Controller_Record_Interface
     */
    public function getControllerInstance()
    {
        return Tinebase_Core::getApplicationInstance($this->_appName, $this->_modelName);
    }
    
    /**
     * gets phpClassName by field definition['config']
     *
     * @param array $_fieldConfig
     * @param string $_type
     * @return string
     */
    protected function _getPhpClassName($_fieldConfig = null, $_type = 'Model')
    {
        if (! $_fieldConfig) {
            $_fieldConfig = array('appName' => $this->_appName, 'modelName' => $this->_modelName);
        }

        return $_fieldConfig['appName'] . '_' . $_type . '_' . $_fieldConfig['modelName'];
    }
    
    /**
     * checks if app and model is available for the user at record and records fields
     * - later this can be used to use field acl
     * - also checks feature switches
     * 
     * @param array $_fieldConfig the field configuration
     * @return boolean
     */
    protected function _isAvailable($_fieldConfig)
    {
        $fieldConfig = isset($_fieldConfig['config']) ? $_fieldConfig['config'] : $_fieldConfig;

        $result = true;
        if (isset($fieldConfig['appName'])) {
            if (!isset(self::$_availableApplications[$fieldConfig['appName']])) {
                self::$_availableApplications[$fieldConfig['appName']] = Tinebase_Application::getInstance()
                    ->isInstalled($fieldConfig['appName'], true);
            }
            $result = self::$_availableApplications[$fieldConfig['appName']];
        }

        if ($result && isset($fieldConfig['feature'])) {
            $config = Tinebase_Config_Abstract::factory($fieldConfig['appName']);
            $result = $config->featureEnabled($fieldConfig['feature']);

            if (! $result && Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__
                . '::' . __LINE__ . ' Feature ' . $fieldConfig['feature'] . ' disables field');
        }

        return $result;
    }
    
    /**
     * returns the filter configuration needed in the filtergroup for this model
     * 
     * @return array
     */
    public function getFilterModel()
    {
        // add calculated values to filter configuration
        if (! $this->_filterConfiguration) {
            foreach ($this->_filterProperties as $prop) {
                $this->_filterConfiguration[$prop] = $this->{$prop};
            }
        }
        return $this->_filterConfiguration;
    }

    /**
     * returns the properties needed for the model
     * 
     * @return array
     */
    public function toArray()
    {
        if (! $this->_modelConfiguration) {
            // add calculated values to model configuration
            foreach ($this->_modelProperties as $prop) {
                $this->_modelConfiguration[$prop] = $this->{$prop};
            }
        }
        
        return $this->_modelConfiguration;
    }

    /**
     * returns the frontend configuration for creating js models, filters, defaults and some other js stubs.
     * this will be included in the registry.
     * Look at Tinebase/js/ApplicationStarter.js
     */
    public function getFrontendConfiguration()
    {
        if (! $this->_frontendConfiguration) {
            
            $this->_frontendConfiguration = array();
            
            // add calculated values to frontend configuration
            foreach ($this->_frontendProperties as $prop) {
                // There should be no need to require all properties, the frontend should handle the abstinence.
                $property = '_' . $prop;
                if (!property_exists($this, $property)) {
                    continue;
                }
                $this->_frontendConfiguration[$prop] = $this->{$property};
            }
        }
        return $this->_frontendConfiguration;
    }

    /**
     * returns default data for this model
     * 
     * @return array
     */
    public function getDefaultData()
    {
        return $this->_defaultData;
    }

    /**
     * returns the field configuration of the model
     *
     * @return array
     */
    public function getFields()
    {
        return $this->_fields;
    }

    /**
     * returns the field configuration of the model
     *
     * @return array
     */
    public function getDbColumns()
    {
        return $this->_dbColumns;
    }

    /**
     * returns the Associations of the model
     *
     * @return array
     */
    public function getAssociations()
    {
        return $this->_associations;
    }

    public function setAssociations(array $assoc)
    {
        $this->_associations = $assoc;
    }

    /**
     * returns the converters of the model
     */
    public function getConverters()
    {
        return $this->_converters;
    }

    public function setJsonExpander(?array $expander)
    { // used by crewscheduling gdpr should be refaactored to use it too
        $this->_jsonExpander = $expander;
    }

    public function getJsonFacadeFields(): array
    {
        return $this->_jsonFacadeFields;
    }

    /**
     * get protected property
     *
     * @param string name of property
     * @throws Tinebase_Exception_UnexpectedValue
     * @return mixed value of property
     */
    public function __get($_property)
    {
        if (! property_exists($this,  '_' . $_property)) {
            throw new Tinebase_Exception_UnexpectedValue('Property does not exist: ' . $_property);
        }
        return $this->{'_' . $_property};
    }

    public function __isset($name)
    {
        return property_exists($this,  '_' . $name);
    }

    /**
     * this is the new resolve function, param $_what can be something like this:
     *
     * ['*'] = everything
     * ['/'] = two levels (discuss)
     * ['relations'] = relations with related records
     * ['relations/*'] = relations and one level into related records (discuss)
     * ['location'] = record field with name 'location' - if that is a virtual field or an id, it is resolved
     *
     * @param Tinebase_Record_RecordSet $_records
     * @param array $_what
     * @throws Tinebase_Exception_NotImplemented
     *
     * @todo move it to a "resolver" class?
     * @todo finish implementation - currently only supports ['relations'] and ['VIRTUALRELATIONPROPERTY']
     * @todo support resolving path ('FIRSTLEVEL/SECONDLEVEL/THIRD/...')
     * @todo support '*'
     */
    public function resolve(Tinebase_Record_RecordSet $_records, $_what)
    {
        if (count($_records) == 0) {
            return;
        }

        $fields = $this->getFields();
        foreach ($_what as $fieldToResolve) {
            // TODO explode resolving path

            if (! in_array($fieldToResolve, array_keys($fields))) {
                throw new Tinebase_Exception_NotImplemented(
                    $fieldToResolve . ' resolving not supported yet or field is no property of model');
            }

            $fieldConfig = $fields[$fieldToResolve];

            switch ($fieldConfig[self::TYPE]) {
                case 'virtual':
                    $virtualConfig = isset($fieldConfig['config']) ? $fieldConfig['config'] : null;
                    if (! isset($virtualConfig[self::TYPE]) || ($virtualConfig[self::TYPE] !== self::TYPE_RELATION &&
                            self::TYPE_RELATIONS !== $virtualConfig[self::TYPE])) {
                        throw new Tinebase_Exception_NotImplemented('supports only relation virtual type');
                    }
                    $this->_resolveVirtualRelations($_records, $fieldConfig);
                default:
                    // do nothing
            }

            if ($fieldToResolve === 'relations') {
                $this->_resolveRelations($_records);
            }
        }
    }

    /**
     * @param Tinebase_Record_RecordSet $_records
     * @param array $_field
     */
    protected function _resolveVirtualRelations(Tinebase_Record_RecordSet $_records, $_field)
    {
        // @todo always resolve or just on demand?
        $this->_resolveRelations($_records);

        foreach ($_records as $record) {
            $fc = $_field['config']['config'];
            if (self::TYPE_RELATIONS === $_field['config'][self::TYPE]) {
                $record[$_field['key']] = new Tinebase_Record_RecordSet($fc['appName'] . '_Model_' . $fc['modelName']);
            }
            foreach ($record->relations as $relation) {
                if (($relation[self::TYPE] == $fc[self::TYPE]) && ($relation['related_model'] == ($fc['appName'] . '_Model_' . $fc['modelName']))) {
                    if (self::TYPE_RELATIONS === $_field['config'][self::TYPE]) {
                        $record[$_field['key']]->addRecord($relation['related_record']);
                    } else {
                        $record[$_field['key']] = $relation['related_record'];
                        break;
                    }
                }
            }
        }
    }

    /**
     * @param Tinebase_Record_RecordSet $_records
     *
     * @todo check if already resolved?
     */
    protected function _resolveRelations(Tinebase_Record_RecordSet $_records)
    {
        if ($_records->getFirstRecord()->has('relations')) {
            $_records->setByIndices('relations', Tinebase_Relations::getInstance()->getMultipleRelations(
                $_records->getRecordClassName(), 'Sql', $_records->getId())
            );
        }
    }

    /**
     * @param $records
     * @return mixed
     *
     * @deprecated implement functionality in resolve
     */
    public function resolveRecords($records)
    {
        // temporary till we fixed resolving
        $recordClassName = $records->getRecordClassName();
        $converter = Tinebase_Convert_Factory::factory($recordClassName);

        return $converter->resolveRecords($records);
    }

    /**
     * @deprecated do not use - it's a modified copy of Tinebase_Convert_Json::_resolveMultipleRecordFields fix it!
     *
     * @param Tinebase_Record_RecordSet $_records
     * @param $modelConfiguration
     * @param bool $isSearch
     * @throws Tinebase_Exception_InvalidArgument
     * @throws Tinebase_Exception_Record_NotDefined
     * @throws Tinebase_Exception_Record_Validation
     */
    public static function resolveRecordsPropertiesForRecordSet(Tinebase_Record_RecordSet $_records, $modelConfiguration, $isSearch = false)
    {
        if (0 === $_records->count() || ! ($resolveFields = $modelConfiguration->recordsFields)) {
            return;
        }

        $ownIds = $_records->{$modelConfiguration->idProperty};

        // iterate fields to resolve
        foreach ($resolveFields as $fieldKey => $c) {
            $config = $c['config'];

            // resolve records, if omitOnSearch is definitively set to FALSE (by default they won't be resolved on search)
            if ($isSearch && !(isset($config['omitOnSearch']) && $config['omitOnSearch'] === FALSE)) {
                continue;
            }

            if (! isset($config['controllerClassName'])) {
                throw new Tinebase_Exception_UnexpectedValue('Controller class name needed');
            }

            // fetch the fields by the refIfField
            /** @var Tinebase_Controller_Record_Interface|Tinebase_Controller_SearchInterface $controller */
            /** @noinspection PhpUndefinedMethodInspection */
            $controller = $config['controllerClassName']::getInstance();
            $filterName = $config['filterClassName'];

            $filterArray = array();

            // addFilters can be added and must be added if the same model resides in more than one records fields
            if (isset($config['addFilters']) && is_array($config['addFilters'])) {
                $filterArray = $config['addFilters'];
            }

            $filter = Tinebase_Model_Filter_FilterGroup::getFilterForModel($filterName, $filterArray);
            $filter->addFilter(new Tinebase_Model_Filter_Id(array('field' => $config['refIdField'], 'operator' => 'in', 'value' => $ownIds)));

            $paging = NULL;
            if (isset($config['paging']) && is_array($config['paging'])) {
                $paging = new Tinebase_Model_Pagination($config['paging']);
            }

            $foreignRecords = $controller->search($filter, $paging);
            /** @var Tinebase_Record_Interface $foreignRecordClass */
            /** @var Tinebase_Record_RecordSet $foreignRecords */
            $foreignRecordClass = $foreignRecords->getRecordClassName();
            $foreignRecordModelConfiguration = $foreignRecordClass::getConfiguration();

            $foreignRecords->setTimezone(Tinebase_Core::getUserTimezone());
            $foreignRecords->setConvertDates(true);

            if ($foreignRecords->count() > 0) {

                // @todo: resolve alarms?
                // @todo: use parts parameter?
                if ($foreignRecordModelConfiguration->resolveRelated) {
                    $fr = $foreignRecords->getFirstRecord();
                    if ($fr->has('notes')) {
                        Tinebase_Notes::getInstance()->getMultipleNotesOfRecords($foreignRecords);
                    }
                    if ($fr->has('tags')) {
                        Tinebase_Tags::getInstance()->getMultipleTagsOfRecords($foreignRecords);
                    }
                    if ($fr->has('relations')) {
                        $relations = Tinebase_Relations::getInstance()->getMultipleRelations($foreignRecordClass, 'Sql', $foreignRecords->{$fr->getIdProperty()} );
                        $foreignRecords->setByIndices('relations', $relations);
                    }
                    if ($fr->has('customfields')) {
                        Tinebase_CustomField::getInstance()->resolveMultipleCustomfields($foreignRecords);
                    }
                    if ($fr->has('attachments') && Tinebase_Core::isFilesystemAvailable()) {
                        Tinebase_FileSystem_RecordAttachments::getInstance()->getMultipleAttachmentsOfRecords($foreignRecords);
                    }
                    if ($fr->has('alarms')) {
                        Tinebase_Alarm::getInstance()->getAlarms($foreignRecords);
                    }
                }

                /** @var Tinebase_Record_Interface $record */
                foreach ($_records as $record) {
                    $filtered = $foreignRecords->filter($config['refIdField'], $record->getId());
                    $record->{$fieldKey} = $filtered;
                }
            }

            foreach ($_records as $record) {
                if (null === $record->{$fieldKey}) {
                    $record->{$fieldKey} = new Tinebase_Record_RecordSet($foreignRecordClass);
                }
            }
        }
    }

    /**
     * Returns all virtual fields
     *
     * @return array
     */
    public function getVirtualFields()
    {
        return $this->_virtualFields;
    }

    public function hasField($_field)
    {
        return isset($this->_fields[$_field]);
    }

    public function getControllerHooksBeforeUpdate(): array
    {
        return $this->_controllerHookBeforeUpdate;
    }

    /**
     * reset cache vars
     */
    public static function resetAvailableApps()
    {
        self::$_availableApplications = ['Tinebase' => true];
    }

    public static function resetAllCreatedModels()
    {
        /** @var Tinebase_Record_Interface $model */
        foreach (self::$createdModels as $model) {
            $model::resetConfiguration();
        }
        self::$createdModels = [];
        self::$deNormalizationCache = [];
    }
}
