<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Model
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2010-2020 Metaways Infosystems GmbH (http://www.metaways.de)
 */

use Doctrine\ORM\Mapping\ClassMetadataInfo;

/**
 * class to hold data representing one node in the tree
 *
 * @package     Tinebase
 * @subpackage  Model
 * @property    string                      contenttype
 * @property    Tinebase_DateTime           creation_time
 * @property    string                      hash
 * @property    string                      indexed_hash
 * @property    string                      name
 * @property    Tinebase_DateTime           last_modified_time
 * @property    string                      object_id
 * @property    string                      parent_id
 * @property    int                         size
 * @property    int                         revision_size
 * @property    string                      type
 * @property    string                      revision
 * @property    string                      available_revisions
 * @property    string                      description
 * @property    string                      acl_node
 * @property    array                       revisionProps
 * @property    array                       notificationProps
 * @property    string                      preview_count
 * @property    integer                     preview_status
 * @property    integer                     preview_error_count
 * @property    integer                     quota
 * @property    Tinebase_Record_RecordSet   grants
 * @property    string                      pin_protected_node
 * @property    string                      path
 * @property    Tinebase_DateTime           lastavscan_time
 * @property    boolean                     is_quarantined
 * @property    Tinebase_Record_RecordSet   metadata
 * @property    string                      $flysystem
 * @property    string                      $flypath
 */
class Tinebase_Model_Tree_Node extends Tinebase_Record_Abstract
{
    public const MODEL_NAME_PART = 'Tree_Node';
    public const TABLE_NAME = 'tree_nodes';

    // forbidden in windows, @see #202420
    const FILENAME_FORBIDDEN_CHARS_EXP = '/[\/\\\:*?"<>|]/';

    const XPROPS_REVISION = 'revisionProps';
    const XPROPS_REVISION_NODE_ID = 'nodeId';
    const XPROPS_REVISION_ON = 'keep';
    const XPROPS_REVISION_NUM = 'keepNum';
    const XPROPS_REVISION_MONTH = 'keepMonth';

    /**
     * {"notificationProps":[{"active":true,....},{"active":true....},{....}]}
     */
    const XPROPS_NOTIFICATION = 'notificationProps';
    const XPROPS_NOTIFICATION_ACTIVE = 'active';
    const XPROPS_NOTIFICATION_SUMMARY = 'summary';
    const XPROPS_NOTIFICATION_ACCOUNT_ID = 'accountId';
    const XPROPS_NOTIFICATION_ACCOUNT_TYPE = 'accountType';
    
    /**
     * key in $_validators/$_properties array for the filed which
     * represents the identifier
     *
     * @var string
     */
    protected $_identifier = 'id';
    
    /**
     * application the record belongs to
     *
     * @var string
     */
    protected $_application = 'Tinebase';

    protected static $_sortExternalMapping = [
        'creation_time' => [
            'fieldCallback' => [self::class, 'sortFieldManipulator'],
            'noJoinRequired' => true,
        ],
        'last_modified_time' => [
            'fieldCallback' => [self::class, 'sortFieldManipulator'],
            'noJoinRequired' => true,
        ],
    ];

    /**
     * holds the configuration object (must be declared in the concrete class)
     *
     * @var Tinebase_ModelConfiguration
     */
    protected static $_configurationObject = NULL;

    /**
     * Holds the model configuration (must be assigned in the concrete class)
     *
     * @var array
     */
    protected static $_modelConfiguration = [
        self::VERSION       => 10,
        'hasRelations'      => true,
        'hasCustomFields'   => true,
        'hasNotes'          => true,
        'hasTags'           => true,
        'modlogActive'      => true,
        self::HAS_DELETED_TIME_UNIQUE => true,
        self::HAS_SYSTEM_CUSTOM_FIELDS => true,

        'titleProperty'     => 'name',
        'appName'           => 'Tinebase',
        'modelName'         => 'Tree_Node',
        self::RECORD_NAME   => 'File', // ngettext('File', 'Files', n); gettext('File');
        self::RECORDS_NAME  => 'Files',
        self::CONTAINER_NAME => 'Folder', // ngettext('Folder', 'Folders', n); gettext('Folder');
        self::CONTAINERS_NAME => 'Folders',

        'idProperty'        => 'id',
        'table'             => [
            'name'              => self::TABLE_NAME,
            self::UNIQUE_CONSTRAINTS => [
                'object_id'             => [
                    self::COLUMNS           => ['object_id', 'parent_id']
                ],
                'parent_id_name'        => [
                    self::COLUMNS           => ['parent_id', 'name', 'deleted_time']
                ]
            ]
        ],

        self::ASSOCIATIONS => [
            ClassMetadataInfo::MANY_TO_ONE => [
                'parent_id' => [
                    'targetEntity' => self::class,
                    'fieldName' => 'parent_id',
                    'joinColumns' => [[
                        'name' => 'parent_id',
                        'referencedColumnName' => 'id'
                    ]],
                ],
                // this morphs into a one_to_one since object_id is unique too (well ... object_id, parent_id ... argh! legacy)
                'object_id' => [
                    'targetEntity' => Tinebase_Model_Tree_FileObject::class,
                    'fieldName' => 'object_id',
                    'joinColumns' => [[
                        'name' => 'object_id',
                        'referencedColumnName' => 'id',
                        'onDelete' => 'CASCADE',
                    ]],
                ]
            ],
        ],

        'filterModel'       => [
            'recursive'         => [
                'filter'            => Tinebase_Model_Filter_Bool::class,
            ],
            'content'           => [
                'filter'            => Tinebase_Model_Filter_ExternalFullText::class,
                self::QUERY_FILTER  => true,
                'options'           => [
                    'idProperty'        => 'object_id',
                ],
                'label' => 'File Contents', // _('File Contents')
                'jsConfig' => ['operators' => ['wordstartswith']]
            ],
            'isIndexed'         => [
                'label' => 'Indexed',    // _('Indexed')
                'filter' => Tinebase_Model_Tree_Node_IsIndexedFilter::class,
                'jsConfig' => ['valueType' => 'bool']
            ],
        ],

        'fields'            => [
            'parent_id'                     => [
                'type'                          => 'string',
                self::LENGTH                    => 40,
                self::NULLABLE                  => true,
                'validators'                    => [Zend_Filter_Input::ALLOW_EMPTY => true],
            ],
            'object_id'                     => [
                'type'                          => 'string',
                self::LENGTH                    => 40,
                'validators'                    => ['presence' => 'required'],
            ],
            'name'                          => [
                'type'                          => 'string',
                self::LABEL                     => 'Name', // _('Name')
                self::LENGTH                    => 255,
                self::QUERY_FILTER              => true,
                'validators'                    => ['presence' => 'required'],
                'inputFilters'                  => ['Zend_Filter_StringTrim' => NULL],
                self::OPTIONS                   => [
                    'collation'                     => 'utf8mb4_bin',
                ],
                self::FILTER_DEFINITION     => [
                    self::FILTER                => Tinebase_Model_Filter_Text::class,
                    self::OPTIONS               => ['binary' => true]
                ],
                self::INPUT_FILTERS         => [Zend_Filter_StringTrim::class => NULL],
            ],
            'islink'                        => [
                'type'                          => self::TYPE_BOOLEAN,
                'validators'                    => [
                    Zend_Filter_Input::ALLOW_EMPTY => true,
                    Zend_Filter_Input::DEFAULT_VALUE => 0
                ],
                self::DEFAULT_VAL               => 0,
                self::UNSIGNED                  => true,
            ],
            'indexed_hash'                  => [
                'type'                          => 'string',
                self::LENGTH                    => 40,
                self::NULLABLE                  => true,
                'modlogOmit'                    => true,
                'validators'                    => [Zend_Filter_Input::ALLOW_EMPTY => true],
            ],
            // contains id of node with acl info
            'acl_node'                      => [
                'type'                          => 'string',
                self::LENGTH                    => 40,
                self::NULLABLE                  => true,
                'validators'                    => [Zend_Filter_Input::ALLOW_EMPTY => true],
            ],
            'linkto'                        => [
                self::TYPE                      => self::TYPE_STRING,
                self::LENGTH                    => 40,
                self::NULLABLE                  => true,
            ],
            'revisionProps'                 => [
                'type'                          => self::TYPE_JSON,
                self::NULLABLE                  => true,
                'validators'                    => [Zend_Filter_Input::ALLOW_EMPTY => true],
                self::ALLOW_CAMEL_CASE          => true,
            ],
            'notificationProps'             => [
                'type'                          => self::TYPE_JSON,
                self::NULLABLE                  => true,
                'validators'                    => [Zend_Filter_Input::ALLOW_EMPTY => true],
                self::ALLOW_CAMEL_CASE          => true,
            ],
            'is_deleted'                    => [
                'type'                          => self::TYPE_BOOLEAN,
                'validators'                    => [Zend_Filter_Input::DEFAULT_VALUE => 0],
                self::DEFAULT_VAL               => 0,
                self::UNSIGNED                  => true,
            ],
            'quota'                         => [
                'type'                          => self::TYPE_BIGINT,
                self::LENGTH                    => 64,
                'validators'                    => [
                    Zend_Filter_Input::ALLOW_EMPTY => true,
                    Zend_Filter_Input::DEFAULT_VALUE => null,
                ],
                self::NULLABLE                  => true,
                self::UNSIGNED                  => true,
            ],
            'pin_protected_node'            => [
                'type'                          => 'string',
                self::LENGTH                    => 40,
                self::NULLABLE                  => true,
                'validators'                    => [Zend_Filter_Input::ALLOW_EMPTY => true],
            ],
            'deleted_time'                  => [
                'type'                          => self::TYPE_DATETIME,
                self::DEFAULT_VAL               => '1970-01-01 00:00:00',
                'validators'                    => [Zend_Filter_Input::ALLOW_EMPTY => true],
                self::FILTER_DEFINITION         => [
                    self::FILTER                    => Tinebase_Model_Filter_DateTime::class,
                    self::OPTIONS                   => ['tablename' => 'tree_fileobjects']
                ]
            ],


            // fields from filemanager_objects table (ro)
            'type'                          => [
                self::DOCTRINE_IGNORE           => true,
                'type'                          => 'string',
                'validators'                    => ['inArray' => [
                    Tinebase_Model_Tree_FileObject::TYPE_FILE,
                    Tinebase_Model_Tree_FileObject::TYPE_FOLDER,
                    Tinebase_Model_Tree_FileObject::TYPE_PREVIEW,
                    Tinebase_Model_Tree_FileObject::TYPE_LINK,
                ], Zend_Filter_Input::ALLOW_EMPTY => true,],
                self::FILTER_DEFINITION         => [
                    self::FILTER                    => Tinebase_Model_Filter_Text::class,
                    self::OPTIONS                   => ['tablename' => 'tree_fileobjects']
                ]
            ],
            'flysystem'                     => [
                self::DOCTRINE_IGNORE           => true,
                self::TYPE                      => self::TYPE_RECORD,
                self::NULLABLE                  => true,
                self::CONFIG                    => [
                    self::APP_NAME                  => Tinebase_Config::APP_NAME,
                    self::MODEL_NAME                => Tinebase_Model_Tree_FlySystem::MODEL_NAME_PART,
                ],
            ],
            'flypath'                       => [
                self::DOCTRINE_IGNORE           => true,
                self::TYPE                      => self::TYPE_TEXT,
                self::NULLABLE                  => true,
            ],
            'description'                   => [
                self::DOCTRINE_IGNORE           => true,
                'type'                          => 'string',
                'modlogOmit'                    => true,
                self::QUERY_FILTER              => true,
                'validators'                    => [Zend_Filter_Input::ALLOW_EMPTY => true],
                self::FILTER_DEFINITION         => [
                    self::FILTER                    => Tinebase_Model_Filter_Text::class,
                    self::OPTIONS                   => ['tablename' => 'tree_fileobjects']
                ]
            ],
            'contenttype'                   => [
                self::LABEL                     => 'Content type', // _('Content type')
                self::DOCTRINE_IGNORE           => true,
                'type'                          => 'string',
                'modlogOmit'                    => true,
                'validators'                    => [Zend_Filter_Input::ALLOW_EMPTY => true],
                self::FILTER_DEFINITION         => [
                    self::FILTER                    => Tinebase_Model_Filter_Text::class,
                    self::OPTIONS                   => ['tablename' => 'tree_fileobjects']
                ]
            ],
            'revision'                      => [
                self::DOCTRINE_IGNORE           => true,
                'type'                          => 'string',
                'modlogOmit'                    => true,
                'validators'                    => [Zend_Filter_Input::ALLOW_EMPTY => true],
            ],
            'available_revisions'           => [
                self::DOCTRINE_IGNORE           => true,
                self::TYPE                      => self::TYPE_VIRTUAL,
                'modlogOmit'                    => true,
                'validators'                    => [Zend_Filter_Input::ALLOW_EMPTY => true],
            ],
            'hash'                          => [
                self::LABEL                     => 'MD5 Hash', // _('MD5 Hash')
                self::DOCTRINE_IGNORE           => true,
                self::SHY                       => true,
                'type'                          => 'string',
                'modlogOmit'                    => true,
                'validators'                    => [Zend_Filter_Input::ALLOW_EMPTY => true],
            ],

            'isIndexed'                     => [
                self::LABEL                     => 'Indexed', // _('Indexed')
                self::DOCTRINE_IGNORE           => true,
                self::SHY                       => true,
                'type'                          => 'string',
                'modlogOmit'                    => true,
                'validators'                    => [Zend_Filter_Input::ALLOW_EMPTY => true],
                'inputFilters'                  => [Zend_Filter_StringTrim::class => null],
                self::ALLOW_CAMEL_CASE          => true,
            ],
            'size'                          => [
                self::LABEL                     => 'Size', // _('Size')
                self::DOCTRINE_IGNORE           => true,
                'type'                          => 'integer',
                'modlogOmit'                    => true,
                'validators'                    => [
                    Zend_Filter_Input::ALLOW_EMPTY => true,
                    Zend_Filter_Empty::class => 0,
                    Zend_Filter_Input::DEFAULT_VALUE => 0
                ],
                self::FILTER_DEFINITION         => [
                    self::FILTER                    => Tinebase_Model_Filter_Int::class,
                    self::OPTIONS                   => ['tablename' => 'tree_filerevisions']
                ]
            ],
            'revision_size'                 => [
                self::LABEL                     => 'Revision Size', // _('Revision Size')
                self::DOCTRINE_IGNORE           => true,
                self::SHY                       => true,
                'type'                          => 'integer',
                'modlogOmit'                    => true,
                'validators'                    => [Zend_Filter_Input::ALLOW_EMPTY => true],
            ],
            'preview_count'                 => [
                self::DOCTRINE_IGNORE           => true,
                'type'                          => 'integer',
                'modlogOmit'                    => true,
                'validators'                    => [
                    Zend_Filter_Input::ALLOW_EMPTY => true,
                    'Digits',
                    Zend_Filter_Input::DEFAULT_VALUE => 0,
                ],
            ],
            'preview_status'                 => [
                self::DOCTRINE_IGNORE           => true,
                'type'                          => 'integer',
                'modlogOmit'                    => true,
                'validators'                    => [
                    Zend_Filter_Input::ALLOW_EMPTY => true,
                    'Digits',
                    Zend_Filter_Input::DEFAULT_VALUE => 0,
                ],
            ],
            'preview_error_count'                 => [
                self::DOCTRINE_IGNORE           => true,
                'type'                          => 'integer',
                'modlogOmit'                    => true,
                'validators'                    => [
                    Zend_Filter_Input::ALLOW_EMPTY => true,
                    'Digits',
                    Zend_Filter_Input::DEFAULT_VALUE => 0,
                ],
            ],

            'lastavscan_time'               => [
                self::DOCTRINE_IGNORE           => true,
                'type'                          => 'datetime',
                'validators'                    => [Zend_Filter_Input::ALLOW_EMPTY => true],
                'modlogOmit'                    => true,
            ],
            'is_quarantined'                => [
                self::DOCTRINE_IGNORE           => true,
                'type'                          => 'boolean',
                'validators'                    => [Zend_Filter_Input::ALLOW_EMPTY => true],
                'default'                       => 0,
                'modlogOmit'                    => true,
            ],


            // not persistent
            'container_name'                => [
                self::DOCTRINE_IGNORE           => true,
                'type'                          => 'string',
                'validators'                    => [Zend_Filter_Input::ALLOW_EMPTY => true],
            ],

            // this is needed should be sent by / delivered to client (not persistent in db atm)
            'path'                          => [
                self::DOCTRINE_IGNORE           => true,
                'type'                          => 'string',
                'modlogOmit'                    => true,
                'validators'                    => [Zend_Filter_Input::ALLOW_EMPTY => true],
                self::FILTER_DEFINITION         => [
                    self::LABEL                     => 'Path', // _('Path')
                    self::FILTER                    => Tinebase_Model_Tree_Node_PathFilter::class,
                    'jsConfig'                      => ['filtertype' => 'tine.filemanager.pathfiltermodel']
                ]
            ],
            'account_grants'                => [
                self::OMIT_MOD_LOG              => true,
                self::DOCTRINE_IGNORE           => true,
                self::TYPE                      => self::TYPE_VIRTUAL,
                'validators'                    => [Zend_Filter_Input::ALLOW_EMPTY => true],
            ],
            'tempFile'                      => [
                self::DOCTRINE_IGNORE           => true,
                //'type'                          => 'string',
                'validators'                    => [Zend_Filter_Input::ALLOW_EMPTY => true],
                self::ALLOW_CAMEL_CASE          => true,
            ],
            'stream'                        => [
                self::DOCTRINE_IGNORE           => true,
                //'type'                          => 'string',
                'validators'                    => [Zend_Filter_Input::ALLOW_EMPTY => true],
            ],
            // acl grants
            'grants'                        => [
                self::OMIT_MOD_LOG              => false,
                self::DOCTRINE_IGNORE           => true,
                self::TYPE                      => self::TYPE_VIRTUAL,
                'validators'                    => [Zend_Filter_Input::ALLOW_EMPTY => true],
            ],
            'effectiveAndLocalQuota'        => [
                self::DOCTRINE_IGNORE           => true,
                self::TYPE                      => self::TYPE_VIRTUAL,
                'validators'                    => [Zend_Filter_Input::ALLOW_EMPTY => true],
                self::ALLOW_CAMEL_CASE          => true,
            ],
        ],
    ];

    /**
     * if foreign Id fields should be resolved on search and get from json
     * should have this format:
     *     array('Calendar_Model_Contact' => 'contact_id', ...)
     * or for more fields:
     *     array('Calendar_Model_Contact' => array('contact_id', 'customer_id), ...)
     * (e.g. resolves contact_id with the corresponding Model)
     *
     * @var array
     */
    protected static $_resolveForeignIdFields = array(
        'Tinebase_Model_User' => array('created_by', 'last_modified_by')
    );

    /**
     * @param string $name
     * @return array|string|string[]|null
     */
    public static function sanitizeName(string $name)
    {
        return preg_replace(self::FILENAME_FORBIDDEN_CHARS_EXP, '', $name);
    }

    public static function modelConfigHook(array &$_definition)
    {
        $fileObjectTime = [
            self::FILTER    => Tinebase_Model_Filter_DateTime::class,
            self::OPTIONS   => ['tablename' => Tinebase_Model_Tree_FileObject::TABLE_NAME]
        ];
        $fileObjectUser = [
            self::FILTER    => Tinebase_Model_Filter_User::class,
            self::OPTIONS   => ['tablename' => Tinebase_Model_Tree_FileObject::TABLE_NAME]
        ];

        $_definition['created_by'][self::DOCTRINE_IGNORE] = true;
        $_definition['created_by'][self::FILTER_DEFINITION] = $fileObjectUser;
        $_definition['created_by'][self::TABLE] = Tinebase_Model_Tree_FileObject::TABLE_NAME;

        $_definition['creation_time'][self::DOCTRINE_IGNORE] = true;
        $_definition['creation_time'][self::FILTER_DEFINITION] = $fileObjectTime;

        $_definition['last_modified_by'][self::DOCTRINE_IGNORE] = true;
        $_definition['last_modified_by'][self::FILTER_DEFINITION] = $fileObjectUser;
        $_definition['last_modified_by'][self::TABLE] = Tinebase_Model_Tree_FileObject::TABLE_NAME;

        $_definition['last_modified_time'][self::DOCTRINE_IGNORE] = true;
        $_definition['last_modified_time'][self::FILTER_DEFINITION] = $fileObjectTime;

        $_definition['seq'][self::DOCTRINE_IGNORE] = true;

        $_definition['deleted_by'][self::DOCTRINE_IGNORE] = true;
        $_definition['deleted_by'][self::FILTER_DEFINITION] = $fileObjectUser;
        $_definition['deleted_by'][self::TABLE] = Tinebase_Model_Tree_FileObject::TABLE_NAME;
    }

    public function runConvertToRecord()
    {
        if (isset($this->_properties['available_revisions']) && is_string($this->_properties['available_revisions'])) {
            $this->_properties['available_revisions'] = explode(',', ltrim(
                rtrim($this->_properties['available_revisions'], '}'), '{'));
        }

        parent::runConvertToRecord();
    }

    public function runConvertToData()
    {
        if (isset($this->_properties[self::XPROPS_REVISION]) && is_array($this->_properties[self::XPROPS_REVISION])) {
            if (count($this->_properties[self::XPROPS_REVISION]) > 0) {
                $this->_properties[self::XPROPS_REVISION] = json_encode($this->_properties[self::XPROPS_REVISION]);
            } else {
                $this->_properties[self::XPROPS_REVISION] = null;
            }
        }

        if (isset($this->_properties[self::XPROPS_NOTIFICATION]) &&
                is_array($this->_properties[self::XPROPS_NOTIFICATION])) {
            if (count($this->_properties[self::XPROPS_NOTIFICATION]) > 0) {
                $this->_properties[self::XPROPS_NOTIFICATION] =
                    json_encode($this->_properties[self::XPROPS_NOTIFICATION]);
            } else {
                $this->_properties[self::XPROPS_NOTIFICATION] = null;
            }
        }

        parent::runConvertToData();
    }

    /**
     * returns real filesystem path
     *
     * @param string $baseDir
     * @throws Tinebase_Exception_NotFound
     * @return string
     */
    public function getFilesystemPath($baseDir = null)
    {
        if (empty($this->hash)) {
            throw new Tinebase_Exception_NotFound('file object hash is missing');
        }

        if ($baseDir === null) {
            $baseDir = Tinebase_Core::getConfig()->filesdir;
        }

        return $baseDir . DIRECTORY_SEPARATOR . substr($this->hash, 0, 3) . DIRECTORY_SEPARATOR .
            substr($this->hash, 3);
    }

    public function isValid($_throwExceptionOnInvalidData = false)
    {
        if ($this->_isValidated === true) {
            return true;
        }

        $invalid = array();
        if (!empty($this->xprops(static::XPROPS_REVISION))) {
            if (count($this->revisionProps) > 4 || !array_key_exists(self::XPROPS_REVISION_NODE_ID, $this->revisionProps)
                    || !array_key_exists(self::XPROPS_REVISION_MONTH, $this->revisionProps) ||
                    !array_key_exists(self::XPROPS_REVISION_ON, $this->revisionProps) ||
                    !array_key_exists(self::XPROPS_REVISION_NUM, $this->revisionProps)) {
                $invalid[] = 'revisionProps';
            }
        }
        if (!empty($this->xprops(static::XPROPS_NOTIFICATION))) {
            foreach ($this->notificationProps as $val) {
                foreach ($val as $key => $val1) {
                    if (!in_array($key, array(
                            static::XPROPS_NOTIFICATION_ACCOUNT_ID,
                            static::XPROPS_NOTIFICATION_ACCOUNT_TYPE,
                            static::XPROPS_NOTIFICATION_ACTIVE,
                            static::XPROPS_NOTIFICATION_SUMMARY))) {
                        $invalid[] = 'notificationProps';
                        break 2;
                    }
                }
            }
        }

        if (!empty($invalid) && $_throwExceptionOnInvalidData) {
            $e = new Tinebase_Exception_Record_Validation('Some fields ' . implode(',', $invalid)
                . ' have invalid content');

            if (Tinebase_Core::isLogLevel(Zend_Log::ERR)) Tinebase_Core::getLogger()->err(__METHOD__ . '::' . __LINE__ . " "
                . $e->getMessage()
                . print_r($this->_validationErrors, true));
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                . ' Record: ' . print_r($this->toArray(), true));

            throw $e;
        }

        return parent::isValid($_throwExceptionOnInvalidData);
    }

    /**
     * returns true if this record should be replicated
     *
     * @return boolean
     */
    public function isReplicable()
    {
        if ($this->type === Tinebase_Model_Tree_FileObject::TYPE_FILE) {
            return true;
        }
        if ($this->type === Tinebase_Model_Tree_FileObject::TYPE_FOLDER) {
            if (strlen((string)$this->name) === 3 &&
                    Tinebase_FileSystem_Previews::getInstance()->getBasePathNode()->getId() === $this->parent_id) {
                return false;
            }
            if (strlen((string)$this->name) === 37 && null !== $this->parent_id &&
                    Tinebase_FileSystem_Previews::getInstance()->getBasePathNode()->getId() ===
                    Tinebase_FileSystem::getInstance()->get($this->parent_id)->parent_id) {
                return false;
            }
            return true;
        }
        return false;
    }

    public function getHighestRevision()
    {
        if (is_array($ar = $this->available_revisions) && !empty($ar)) {
            sort($ar, SORT_NUMERIC);
            return (int)end($ar);
        }
        return 0;
    }

    public function getPreviousRevision()
    {
        // sort resets keys! so we can use it
        if (is_array($ar = $this->available_revisions) && !empty($ar)) {
            sort($ar, SORT_NUMERIC);
            if (false !== ($idx = array_search($this->revision, $ar)) && $idx > 0) {
                return $ar[$idx - 1];
            }
        }
        return 0;
    }

    public function getContainerId(): ?string
    {
        return $this->parent_id;
    }

    protected $_notifyBC = null;
    public function notifyBroadcastHub(): bool
    {
        if (null === $this->_notifyBC) {
            if ($this->type !== Tinebase_Model_Tree_FileObject::TYPE_FILE && $this->type !==
                    Tinebase_Model_Tree_FileObject::TYPE_FOLDER) {
                $this->_notifyBC = false;
            } else {
                $path = Tinebase_Model_Tree_Node_Path::createFromStatPath(
                    Tinebase_FileSystem::getInstance()->getPathOfNode($this, true, true));

                $this->_notifyBC = $path->containerType === Tinebase_FileSystem::FOLDER_TYPE_PERSONAL ||
                    $path->containerType === Tinebase_FileSystem::FOLDER_TYPE_SHARED;
            }
        }
        return $this->_notifyBC;
    }

    public static function sortFieldManipulator(string $field): string
    {
        return 'tree_fileobjects.' . $field;
    }
}
