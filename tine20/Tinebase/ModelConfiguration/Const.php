<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  ModelConfiguration
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2018-2025 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */

/**
 * Tinebase_ModelConfiguration_Const provides constants
 *
 * @package     Tinebase
 * @subpackage  ModelConfiguration
 */

class Tinebase_ModelConfiguration_Const {
    public const ADD_FILTERS = 'addFilters';
    public const ALLOW_CAMEL_CASE = 'allowCamelCase';
    public const APPLICATION = 'application';
    public const APP_NAME = 'appName';
    public const ASSOCIATIONS = 'associations';
    public const AUTOINCREMENT = 'autoincrement';
    public const AVAILABLE_MODELS = 'availableModels';

    /**
     * additional boxLabel for checkboxes
     */
    public const BOX_LABEL = 'boxLabel';

    public const CASCADE = 'CASCADE';
    public const CONFIG = 'config';
    public const CONTROLLER = 'controller';
    public const CONTROLLER_CLASS_NAME = 'controllerClassName';
    public const CONTROLLER_HOOK_BEFORE_UPDATE = '_controllerHookBeforeUpdate';
    public const CONVERTERS = 'converters';
    public const COLUMNS = 'columns';
    /**
     * sub of uiconfig, config for columnManager
     */
    public const COLUMN_CONFIG = 'columnConfig';
    public const COPY_OMIT = 'copyOmit';
    public const COPY_RELATIONS = 'copyRelations';

    public const CREATE = 'create';
    public const CREATE_MODULE = 'createModule';
    public const CREDENTIAL_CACHE = 'credential_cache';
    public const CURRENT_TIMESTAMP = 'CURRENT_TIMESTAMP';

    public const DB_COLUMNS = 'dbColumns';
    public const DEFAULT_FROM_CONFIG = 'defaultFromConfig';
    /**
     * default sort info
     *
     * example: ['field' => 'number', 'direction' => 'DESC']
     */
    public const DEFAULT_SORT_INFO = 'defaultSortInfo';
    /**
     * evaluated from doctrine only! has nothing to do with record validation default or empty value
     */
    public const DEFAULT_VAL = 'default';
    /**
     * config for default value
     */
    public const DEFAULT_VAL_CONFIG = 'defaultValConfig';
    public const DEGREE = 'degree';
    public const DELEGATED_ACL_FIELD = 'delegateAclField';
    public const DENORMALIZATION_CONFIG = 'denormalizationConfig';
    public const DENORMALIZATION_OF = 'denormalizationOf';
    /**
     * valid vor type 'records' - means records are governed (not independent)
     */
    public const DEPENDENT_RECORDS = 'dependentRecords';
    public const DELAY_DEPENDENT_RECORDS = 'delayDependentRecords';
    public const DESCRIPTION = 'description'; // e.g. Tinebase_Model_Grants
    /**
     * UI ONLY - If this is set to true, the field can't be updated and will not be shown in the frontend
     */
    public const DISABLED = 'disabled';
    public const DO_JOIN = 'doJoin';
    public const DOCTRINE_IGNORE = 'doctrineIgnore';
    /** use this type for doctrine mapping instead of normal type */
    public const DOCTRINE_MAPPING_TYPE = 'doctrineMapType';

    public const EXPORT = 'export';
    public const EXPOSE_HTTP_API = 'exposeHttpApi';
    public const EXPOSE_JSON_API = 'exposeJsonApi';
    public const EXTENDS_CONTAINER = 'extendsContainer';

    public const FIELD = 'field';
    public const FIELDS = 'fields';

    /**
     * sub of uiconfig, config for fieldManager
     */
    public const FIELDS_CONFIG = 'fieldConfig';
    public const FIELD_NAME = 'fieldName';
    public const FILTER = 'filter';
    public const FILTER_CLASS_NAME = 'filterClassName';
    public const FILTER_DEFINITION = 'filterDefinition';
    public const FILTER_GROUP = 'filtergroup';
    /**
     * holds additional filters for the record
     *
     * @todo document the differences between FILTER, FILTER_DEFINITION + FILTER_MODEL
     *
     * example:
     *      self::FILTER_MODEL => [
                'contact'        => ['filter' => 'Tinebase_Model_Filter_Relation', 'options' => [
                    'related_model'     => 'Addressbook_Model_Contact',
                    'filtergroup'    => 'Addressbook_Model_ContactFilter'
                ]],
            ],
     */
    public const FILTER_MODEL = 'filterModel';
    public const FILTER_OPTIONS = 'filterOptions';
    public const FIXED_LENGTH = 'fixedLength';
    public const FLAGS = 'flags';
    public const FLD_ACCOUNT_GRANTS = 'account_grants';
    public const FLD_ALARMS = 'alarms';
    public const FLD_ATTACHMENTS = 'attachments';
    public const FLD_CONTAINER_ID = 'container_id';
    public const FLD_DELETED_TIME = 'deleted_time';
    public const FLD_GRANTS = 'grants';
    public const FLD_IS_DELETED = 'is_deleted';
    public const FLD_LOCALLY_CHANGED = 'locally_changed';
    public const FLD_NOTES = 'notes';
    public const FLD_ORIGINAL_ID = 'original_id';
    public const FLD_RELATIONS = 'relations';
    public const FLD_TAGS = 'tags';
    public const FLD_XPROPS = 'xprops';
    public const FORCE_VALUES = 'forceValues';
    /**
     * valid for the config of fields of type record(s). Defines virtual field in foreign record which holds own record(s)
     */
    public const FOREIGN_FIELD = 'foreignField';
    public const FUNCTION = 'function';

    public const GRANTS_MODEL = 'grantsModel';
    public const GROUP = 'group';

    public const HAS_ALARMS = 'hasAlarms';
    public const HAS_ATTACHMENTS = 'hasAttachments';
    public const HAS_CUSTOM_FIELDS = 'hasCustomFields';
    public const HAS_DELETED_TIME_UNIQUE = 'hasDeletedTimeUnique';
    public const HAS_NOTES = 'hasNotes';
    public const CONTAINER_PROPERTY = 'containerProperty';
    public const HAS_PERSONAL_CONTAINER = 'hasPersonalContainer';
    public const CONTAINER_NAME = 'containerName';
    public const CONTAINERS_NAME = 'containersName';
    public const HAS_RELATIONS = 'hasRelations';
    public const HAS_SYSTEM_CUSTOM_FIELDS = 'hasSystemCustomFields';
    public const HAS_TAGS = 'hasTags';
    public const HAS_XPROPS = 'hasXProps';

    public const ID = 'id';
    public const ID_GENERATOR_TYPE = 'idGeneratorType';
    public const IGNORE_ACL = 'ignoreACL';
    public const INDEXES = 'indexes';
    public const INPUT_FILTERS = 'inputFilters';
    public const IS_DEPENDENT = 'isDependent';
    /**
     * flags a model as metadata model for the configured field in the own model
     * this configured field is a record which gets additional information/metadata
     */
    public const IS_METADATA_MODEL_FOR = 'isMetadataModelFor';
    /**
     * valid vor type record - means the configured model is the parent of this record
     */
    public const IS_PARENT = 'isParent';
    public const IS_PERSPECTIVE = 'isPerspective';
    public const IS_VIRTUAL = 'isVirtual';

    public const JOIN_COLUMNS = 'joinColumns';
    public const JSON_EXPANDER = 'jsonExpander';
    public const JSON_FACADE = 'jsonFacade';

    public const LABEL = 'label';
    public const LANGUAGES_AVAILABLE = 'languagesAvailable';
    public const LENGTH = 'length';
    public const LENGTHS = 'lengths';

    public const MAPPED_BY = 'mappedBy';
    public const MODEL_NAME = 'modelName';
    public const MODLOG_ACTIVE = 'modlogActive';

    public const MULTIPLE_EDIT = 'multipleEdit';

    public const NAME = 'name';
    public const NO_DEFAULT_VALIDATOR = 'noDefaultValidator';
    public const NORESOLVE = 'noResolve';
    public const NULLABLE = 'nullable';

    public const OMIT_MOD_LOG = 'modlogOmit';
    public const ON_DELETE = 'onDelete';
    public const ON_UPDATE = 'onUpdate';
    public const OPTIONS = 'options';
    public const ORDER = 'order';
    // used for example by system customfields. Tells the receiving model, that this property originates from a different app
    // relevant for translation, keyfields, etc.
    public const OWNING_APP = 'owningApp';

    public const PAGING = 'paging';
    public const PERSISTENT = 'persistent';
    public const PERSPECTIVE_CONVERTERS = 'perspectiveConverters';
    public const PERSPECTIVE_DEFAULT = 'perspectiveDefault';

    public const QUERY_FILTER = 'queryFilter';

    /**
     * If this is set to true, the field can't be updated in BE and will be shown as readOnly in the frontend
     * if set bellow self::UI_CONFIG server can update field
     */
    public const READ_ONLY = 'readOnly';
    public const REFERENCED_COLUMN_NAME = 'referencedColumnName';
    /**
     * valid for the config of fields of type record(s). Defines field in foreign record which holds id to own record(s)
     */
    public const REF_ID_FIELD = 'refIdField';
    public const REF_MODEL_FIELD = 'refModelField';
    public const RECORD_CLASS_NAME = 'recordClassName';
    public const RECORD_NAME = 'recordName';
    public const RECORDS_NAME = 'recordsName';
    public const RECURSIVE_RESOLVING = 'recursiveResolving';
    public const REQUIRED_GRANTS = 'requiredGrants';
    /**
     * UI only -> required right to see/use module
     */
    public const REQUIRED_RIGHT = 'requiredRight';
    public const RESOLVE_DELETED = 'resolveDeleted';
    public const RESPONSIVE_LEVEL = 'responsiveLevel';
    public const RUN_CONVERT_TO_RECORD_FROM_JSON = 'runConvertToRecordFromJson';

    public const SET_DEFAULT_INSTANCE = 'setDefaultInstance';
    /**
     * frontends do not show this field in grids per default
     */
    public const SHY = 'shy';
    public const SINGULAR_CONTAINER_MODE = 'singularContainerMode';
    public const SKIP_LEGACY_JSON_CONVERT = 'skipLegacyJsonConvert';
    public const SPECIAL_TYPE = 'specialType';
    public const SPECIAL_TYPE_DISCOUNT = 'discount';
    public const SPECIAL_TYPE_DURATION_SEC = 'durationSec';
    public const SPECIAL_TYPE_EMAIL = 'email';
    public const SPECIAL_TYPE_PASSWORD = 'password';
    public const SPECIAL_TYPE_PERCENT = 'percent';
    public const SPECIAL_TYPE_URL = 'url';
    public const SPECIAL_TYPE_COUNTRY = 'country';
    public const SPECIAL_TYPE_CURRENCY = 'currency';

    public const SPECIAL_TYPE_TIMEZONE = 'timezone';
    public const SPECIAL_TYPE_MINUTES = 'minutes';
    public const SPECIAL_TYPE_MONTH = 'month';
    public const STORAGE = 'storage';
    public const SUPPORTED_FORMATS = 'supportedFormats';
    /**
     * legacy - field is not included in export (but not respected by all exports)
     */
    public const SYSTEM = 'system';
    public const SYSTEM_CF = 'systemCF'; // this property was created by a system custom field

    public const TAB = 'tab';
    public const TABLE = 'table';
    public const TARGET_ENTITY = 'targetEntity';
    public const TITLE_PROPERTY = 'titleProperty';
    public const TOOLTIP = 'tooltip';
    public const TRACK_CHANGES = 'trackChanges';
    /**
     * uiconfig -> translate value in renderers and forms, useful for default data (e.g. coredata)
     */
    public const TRANSLATE = 'translate';
    public const TYPE = 'type';
    public const TYPE_ATTACHMENTS = 'attachments';
    public const TYPE_BIGINT = 'bigint';
    public const TYPE_BLOB = 'blob';
    public const TYPE_BOOLEAN = 'boolean';
    public const TYPE_CONTAINER = 'container';
    public const TYPE_DATETIME = 'datetime';
    public const TYPE_DATETIME_SEPARATED_DATE = 'datetime_separated_date';
    public const TYPE_DATE = 'date';
    public const TYPE_DYNAMIC_RECORD = 'dynamicRecord';
    public const TYPE_FLOAT = 'float';
    public const TYPE_FULLTEXT = 'fulltext';

    /**
     * Colour in the web standard hexadecimal format (#000000 to #FFFFFF)
     */
    public const TYPE_HEX_COLOR = 'hexcolor';

    public const TYPE_INTEGER = 'integer';
    public const TYPE_JSON = 'json';
    public const TYPE_NATIVE_JSON = 'nativeJson';
    public const TYPE_JSON_REFID = 'jsonRefId';
    public const TYPE_KEY_FIELD = 'keyfield';
    public const TYPE_LABEL = 'label';
    public const TYPE_LANGUAGE = 'language';
    /**
     * TODO comment
     */
    public const TYPE_LOCALIZED_STRING = 'localizedString';
    public const TYPE_MODEL = 'model';
    public const TYPE_MONEY = 'money';
    public const TYPE_NOTE = 'note';
    public const TYPE_NUMBERABLE_INT = 'numberableInt';
    public const TYPE_NUMBERABLE_STRING = 'numberableStr';
    public const TYPE_PASSWORD = 'password';

    public const TYPE_PRE_EXPANDED = 'preExpanded';
    public const TYPE_RECORD = 'record';
    public const TYPE_RECORDS = 'records';
    public const TYPE_RELATION = 'relation';
    public const TYPE_RELATIONS = 'relations';
    public const TYPE_STRICTFULLTEXT = 'strictFulltext';
    public const TYPE_STRING = 'string';
    public const TYPE_STRING_AUTOCOMPLETE = 'stringAutocomplete';
    public const TYPE_TAG = 'tag';
    public const TYPE_TEXT = 'text';
    public const TYPE_TIME = 'time';
    public const TYPE_USER = 'user';
    public const TYPE_VIRTUAL = 'virtual';

    public const UNIQUE_CONSTRAINTS = 'uniqueConstraints';
    public const UNSIGNED = 'unsigned';
    public const UI_CONFIG = 'uiconfig';
    /**
     * define which feature is required to enable field or filter
     */
    public const UI_CONFIG_FEATURE = 'feature';
    public const UI_CONFIG_LAYOUT_SMALL = 'small';
    public const UI_CONFIG_LAYOUT_MEDIUM = 'medium';
    public const UI_CONFIG_LAYOUT_BIG = 'big';
    public const UI_CONFIG_LAYOUT_LARGE = 'large';
    public const VALIDATE = 'validate';
    public const VALIDATORS = 'validators';
    public const VERSION = 'version';
}
