<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Record
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2024 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 */

/**
 * defines the datatype for one container
 * 
 * @package     Tinebase
 * @subpackage  Record
 *
 * @property    string $application_id
 * @property    string $type
 * @property    string $owner_id
 * @property    string $id
 * @property    string $name
 * @property    string $hierarchy
 * @property    string $backend
 * @property    string $order
 * @property    string $color
 * @property    string $account_grants
 * @property    string $path
 * @property    string $model
 * @property    string $uuid

// only gets updated in increaseContentSequence() + readonly in normal record context
'content_seq'       => array('allowEmpty' => true),
 * 
 * NOTE: container class is in the transition from int based grants to string based
 *       grants! In the next refactoring step of container class, int based grants 
 *       will be replaced. Also the grants will not longer be part of container class!
 *       This way apps can define their own grants
 */
class Tinebase_Model_Container extends Tinebase_Record_Abstract
{
    /**
     * type for personal containers
     */
    const TYPE_PERSONAL = 'personal';
    
    /**
     * type for shared container
     */
    const TYPE_SHARED = 'shared';
    
    /**
     * type for shared container
     */
    const TYPE_OTHERUSERS = 'otherUsers';

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
    protected static $_modelConfiguration = array(
        self::VERSION       => 15,
        'recordName'        => 'Container',
        'recordsName'       => 'Containers', // ngettext('Container', 'Containers', n)
        'hasRelations'      => FALSE,
        'hasCustomFields'   => FALSE,
        'hasNotes'          => FALSE,
        'hasTags'           => FALSE,
        'modlogActive'      => TRUE,
        self::HAS_DELETED_TIME_UNIQUE =>  true,
        'hasAttachments'    => FALSE,
        'hasXProps'         => TRUE,
        'createModule'      => FALSE,

        'titleProperty'     => 'name',
        'appName'           => 'Tinebase',
        'modelName'         => 'Container',
        self::TABLE         => [
            self::NAME                  => 'container',
            self::INDEXES               => [
                'type'                      => [
                    self::COLUMNS               => ['type', 'application_id', 'owner_id'],
                ],
                'owner_id'                  => [ // is this really required? most (all?) queries will include a type?
                    self::COLUMNS               => ['owner_id', 'application_id'],
                ],
            ],
            self::UNIQUE_CONSTRAINTS    => [
                'name'                      => [
                    self::COLUMNS               => [
                        'application_id',
                        'name',
                        'owner_id',
                        'model',
                        'deleted_time'
                    ]
                ]
            ],
        ],

        'associations' => [
            \Doctrine\ORM\Mapping\ClassMetadataInfo::MANY_TO_ONE => [
                'container_id_fk' => [
                    'targetEntity' => Tinebase_Model_Application::class,
                    'fieldName' => 'application_id',
                    'joinColumns' => [[
                        'name' => 'application_id',
                        'referencedColumnName'  => 'id'
                    ]],
                ],
            ],
        ],

        'fields' => array(
            'name'              => array(
                'label'             => 'Name', //_('Name')
                self::TYPE          => self::TYPE_STRING,
                self::LENGTH        => 255,
                self::NULLABLE      => false,
                'queryFilter'       => TRUE,
                'validators'        => array(Zend_Filter_Input::ALLOW_EMPTY => false, 'presence' => 'required'),
                'inputFilters'      => array('Zend_Filter_StringTrim' => NULL),
            ),
            'hierarchy'         => array(
                'label'             => 'Hierarchy', //_('Hierarchy')
                self::TYPE          => self::TYPE_TEXT,
                self::LENGTH        => \Doctrine\DBAL\Platforms\MySqlPlatform::LENGTH_LIMIT_MEDIUMTEXT,
                self::NULLABLE      => true,
                'queryFilter'       => true,
                'validators'        => array(Zend_Filter_Input::ALLOW_EMPTY => true),
                self::INPUT_FILTERS     => [Zend_Filter_Empty::class => null],
                self::FILTER_DEFINITION => [],
            ),
            // TODO should be an enum
            'type'              => array(
                'label'             => 'Type', //_('Type')
                self::TYPE          => self::TYPE_STRING,
                self::LENGTH        => 32,
                self::NULLABLE      => false,
                self::DEFAULT_VAL   => self::TYPE_PERSONAL,
                'queryFilter'       => TRUE,
                'validators'        => array(array('InArray', array(self::TYPE_PERSONAL, self::TYPE_SHARED))),
            ),
            'owner_id'           => array(
                'label'             => 'Owner', // _('Owner')
                self::TYPE          => self::TYPE_STRING,
                self::LENGTH        => 40,
                self::NULLABLE      => false,
                self::DEFAULT_VAL   => '',
                self::CONVERTERS    => [
                    Tinebase_Model_Converter_StringFakeNull::class
                ],
                'validators'        => array('allowEmpty' => true),
            ),
            'color'              => array(
                'label'             => 'Color', // _('Color')
                self::TYPE          => self::TYPE_STRING,
                self::LENGTH        => 7,
                self::NULLABLE      => true,
                'validators'        => array('allowEmpty' => true, array('regex', '/^#[0-9a-fA-F]{6}$/')),
            ),
            'order'              => array(
                'label'             => 'Order', // _('Order')
                self::TYPE          => self::TYPE_INTEGER,
                self::UNSIGNED      => true,
                self::NULLABLE      => true,
                self::DEFAULT_VAL   => 0,
                'validators'        => array('allowEmpty' => true),
            ),
            'backend'            => array(
                self::TYPE          => self::TYPE_STRING,
                self::LENGTH        => 64,
                self::NULLABLE      => false,
                'validators'        => array('presence' => 'required'),
            ),
            'application_id'     => array(
                'label'             => 'Application', // _('Application')
                self::TYPE          => self::TYPE_STRING,
                self::LENGTH        => 40,
                self::NULLABLE      => false,
                'validators'        => array('Alnum', 'presence' => 'required'),
            ),
            // only gets updated in increaseContentSequence() + readonly in normal record context
            'content_seq'        => array(
                self::TYPE          => self::TYPE_BIGINT,
                self::UNSIGNED      => true,
                self::NULLABLE      => true,
                'readOnly'          => true,
                'validators'        => array('allowEmpty' => true),
            ),
            'model'              => array(
                'label'             => 'Model', // _('Model')
                self::TYPE          => self::TYPE_STRING,
                self::LENGTH        => 64,
                self::NULLABLE      => false,
                self::DEFAULT_VAL   => '',
                self::CONVERTERS    => [
                    Tinebase_Model_Converter_StringFakeNull::class
                ],
                'validators'        => array(Zend_Filter_Input::ALLOW_EMPTY => false, 'presence' => 'required'),
                'inputFilters'      => array('Zend_Filter_StringTrim' => NULL),
            ),
            'uuid'               => array(
                'label'             => 'UUID', // _('UUID')
                self::TYPE          => self::TYPE_STRING,
                self::LENGTH        => 64,
                self::NULLABLE      => true,
                self::DEFAULT_VAL   => null,
                'validators'        => array('allowEmpty' => true),
            ),

            'account_grants'     => array(
                self::TYPE          => 'string',
                self::IS_VIRTUAL    => true,
                'validators'        => array('allowEmpty' => true),
            ),
            'path'               => array(
                self::TYPE          => 'virtual',
                'validators'        => array('allowEmpty' => true),
            ),
        )
    );
    
    /**
     * converts a int, string or Tinebase_Model_Container to a containerid
     *
     * @param   int|string|Tinebase_Model_Container $_containerId the containerid to convert
     * @return  string
     * @throws  Tinebase_Exception_InvalidArgument
     */
    static public function convertContainerId($_containerId)
    {
        // null will be string casted to empty string
        if ($_containerId instanceof Tinebase_Model_Container) {
            $id = (string)$_containerId->getId();
        } elseif(is_array($_containerId)) {
            if (isset($_containerId['id'])) {
                $id = $_containerId['id'];
            } else {
                $id = null;
            }
        } else {
            $id = (string)$_containerId;
        }

        // ctype_alnum returns false on empty string
        if (false === ctype_alnum($id)) {
            throw new Tinebase_Exception_InvalidArgument('No container id set.');
        }

        return $id;
    }

    /**
     * sets the record related properties from user generated input.
     *
     * Input-filtering and validation by Zend_Filter_Input can enabled and disabled
     *
     * @param array $_data            the new data to set
     * @throws Tinebase_Exception_Record_Validation when content contains invalid or missing data
     */
    public function setFromArray(array &$_data)
    {
        parent::setFromArray($_data);
        
        switch ($this->type) {
            case Tinebase_Model_Container::TYPE_SHARED:
                $this->path = "/{$this->type}/{$this->getId()}";
                break;
                
            case Tinebase_Model_Container::TYPE_PERSONAL:
                if (!empty($this->owner_id)) {
                    $this->path = "/{$this->type}/{$this->owner_id}/{$this->getId()}";
                }
                break;
        }
    }
    
    /**
     * gets path of this container
     *
     * @return string path
     */
    public function getPath()
    {
        switch ($this->type) {
            case Tinebase_Model_Container::TYPE_PERSONAL:
                $this->path = "/{$this->type}/{$this->getOwner()}/{$this->getId()}";
                break;
        }
        
        return $this->path;
    }
    
    /**
     * returns owner of this container
     * 
     * @throws Exception
     */
    public function getOwner()
    {
        if ($this->type == self::TYPE_SHARED) {
            return NULL;
        }
        
        if (! $this->owner_id) {
            // we need to find out who has admin grant
            $allGrants = Tinebase_Container::getInstance()->getGrantsOfContainer($this, true);
            
            // pick the first user with admin grants
            foreach ($allGrants as $grants) {
                if ($grants->{Tinebase_Model_Grants::GRANT_ADMIN} === true) {
                    $this->owner_id = $grants->account_id;
                    break;
                }
            }
            if (! $this->owner_id) {
                throw new Tinebase_Exception_NotFound('Could not find container admin');
            }
        }
        
        return $this->owner_id;
    }
    
    /**
     * checks if container is a personal container of given account
     * 
     * @param mixed $account
     * @return bool
     */
    public function isPersonalOf($account)
    {
        return $this->type == Tinebase_Model_Container::TYPE_PERSONAL 
            && $this->getOwner() == Tinebase_Model_User::convertUserIdToInt($account);
    }
    
    /**
     * returns containerId if given path represents a (single) container
     * 
     * @static
     * @param  String path
     * @return String|Bool
     */
    public static function pathIsContainer($_path)
    {
        // NOTE: path may contain "virtual" parts e.g. /shared/foo/bar/....
        if (preg_match("/^\/personal(?:\/.*)*\/[0-9a-z_\-]+\/([a-f0-9]+)|^\/shared(?:\/.*)*\/([a-f0-9]+)/i", $_path, $matches)) {
            return (isset($matches[2]) || array_key_exists(2, $matches)) ? $matches[2] : $matches[1];
        }
        
        return false;
    }

    /**
     * resolves container_id property
     * 
     * @param Tinebase_Record_Interface $_record
     * @param string $_containerProperty
     */
    public static function resolveContainerOfRecord($_record, $_containerProperty = 'container_id')
    {
        if (! $_record instanceof Tinebase_Record_Interface) {
            return;
        }
        
        if (! $_record->has($_containerProperty) || empty($_record->{$_containerProperty})) {
            return;
        }
        
        try {
            $container = Tinebase_Container::getInstance()->getContainerById($_record->{$_containerProperty});
        } catch (Tinebase_Exception_NotFound $tenf) {
            if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__ . ' ' . $tenf);
            return;
        }
        
        $container->resolveGrantsAndPath();
        
        $_record->{$_containerProperty} = $container;
    }
    
    /**
     * resolves container grants and path
     */
    public function resolveGrantsAndPath()
    {
        $this->account_grants = Tinebase_Container::getInstance()->getGrantsOfAccount(Tinebase_Core::getUser(), $this);
        $this->path = $this->getPath();
    }
    
    /**
     * returns owner id if given path represents a personal _node_
     * 
     * @static
     * @param  String $_path
     * @return String|Bool
     */
    public static function pathIsPersonalNode($_path)
    {
        if (preg_match("/^\/personal\/([0-9a-z_\-]+)$/i", $_path, $matches)) {
            // transform current user 
            return $matches[1] == Tinebase_Model_User::CURRENTACCOUNT ? Tinebase_Core::getUser()->getId() : $matches[1];
        }
        
        return false;
    }
    
    /**
     * returns containername
     *
     * @return string
     */
    public function __toString()
    {
        return $this->name;
    }

    /**
     * @return bool
     */
    public function isReplicable()
    {
        if (is_a($this->model, Tinebase_Container_NotReplicable::class, true)) {
            return false;
        }
        return true;
    }

    /**
     * @return string
     */
    public function getGrantClass()
    {
        if (isset($this->xprops()['Tinebase']['Container']['GrantsModel'])) {
            return $this->xprops()['Tinebase']['Container']['GrantsModel'];
        }

        $class = $this->model . 'Grants';
        if (class_exists($class)) {
            return $class;
        }

        return Tinebase_Model_Grants::class;
    }
}
