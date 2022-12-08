<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2008-2019 Metaways Infosystems GmbH (http://www.metaways.de)
 * 
 */

/**
 * backend for import/export definitions
 *
 * @package     Tinebase
 * @subpackage  Controller
 */
class Tinebase_ImportExportDefinition extends Tinebase_Controller_Record_Abstract
{
    // FIXME why is this duplicated here? belongs to the model! i.e. \Tinebase_Model_ImportExportDefinition::SCOPE_SINGLE
    const SCOPE_SINGLE = 'single';
    const SCOPE_MULTI = 'multi';
    const SCOPE_HIDDEN = 'hidden';
    const SCOPE_REPORT = 'report';

    /**
     * holds the instance of the singleton
     *
     * @var Tinebase_ImportExportDefinition
     */
    private static $_instance = NULL;
        
    /**
     * the constructor
     *
     * don't use the constructor. use the singleton 
     */
    private function __construct() {
        $this->_modelName = 'Tinebase_Model_ImportExportDefinition';
        $this->_applicationName = 'Tinebase';
        $this->_purgeRecords = FALSE;

        // set backend with activated modlog
        $this->_backend = new Tinebase_Backend_Sql(array(
            'modelName'     => $this->_modelName,
            'tableName'     => 'importexport_definition',
            'modlogActive'  => TRUE,
        ));
    }
    
    /**
     * the singleton pattern
     *
     * @return Tinebase_ImportExportDefinition
     */
    public static function getInstance()
    {
        if (self::$_instance === NULL) {
            self::$_instance = new Tinebase_ImportExportDefinition();
        }
        return self::$_instance;
    }

    /**
     * get definition by name
     *
     * @param string $_name
     * @return Tinebase_Model_ImportExportDefinition
     *
     * @todo replace this with search function
     * @throws Tinebase_Exception_NotFound
     */
    public function getByName($_name, $_getDeleted = false)
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->_backend->getByProperty($_name, 'name', $_getDeleted);
    }
    
    /**
     * get application export definitions
     *
     * @param Tinebase_Model_Application $_application
     * @return Tinebase_Record_RecordSet of Tinebase_Model_ImportExportDefinition
     */
    public function getExportDefinitionsForApplication(Tinebase_Model_Application $_application)
    {
        $filter = Tinebase_Model_Filter_FilterGroup::getFilterForModel(Tinebase_Model_ImportExportDefinition::class, array(
            array('field' => 'application_id',  'operator' => 'equals',  'value' => $_application->getId()),
            array('field' => 'type',            'operator' => 'equals',  'value' => 'export'),
        ));
        $result = $this->search($filter);

        $fileSystem = Tinebase_FileSystem::getInstance();
        $toRemove = new Tinebase_Record_RecordSet('Tinebase_Model_ImportExportDefinition');
        /** @var Tinebase_Model_ImportExportDefinition $definition */
        foreach ($result as $definition) {
            if ($definition->plugin_options) {
                try {
                    $config = Tinebase_ImportExportDefinition::getInstance()->getOptionsAsZendConfigXml(
                        $definition, array()
                    );
                } catch (Tinebase_Exception_InvalidArgument $teia) {
                    if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) Tinebase_Core::getLogger()->notice(
                        __METHOD__ . '::' . __LINE__
                        . ' Removing export definition because'
                        . ' exception was thrown: ' . $teia->getMessage());
                    $toRemove[] = $definition;
                }
                if (!empty($config->template)) {
                    if (strpos($config->template, 'tine20://') === false) {
                        continue;
                    }
                    try {
                        $node = $fileSystem->stat(substr($config->template, 9));
                        if (false === $fileSystem->hasGrant(Tinebase_Core::getUser()->getId(), $node->getId(),
                                Tinebase_Model_Grants::GRANT_READ)) {
                            if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) Tinebase_Core::getLogger()->notice(
                                __METHOD__ . '::' . __LINE__
                                . ' Removing export definition because'
                                . ' user has no read grant on template file ' . $config->template);
                            $toRemove[] = $definition;
                        }
                    } catch (Exception $e) {
                        if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) Tinebase_Core::getLogger()->notice(
                            __METHOD__ . '::' . __LINE__
                            . ' Removing export definition because'
                            . ' exception was thrown: ' . $e->getMessage());
                        $toRemove[] = $definition;
                    }
                } elseif (!empty($config->templateFileId)) {
                    if (false === $fileSystem->hasGrant(Tinebase_Core::getUser()->getId(), $config->templateFileId,
                            Tinebase_Model_Grants::GRANT_READ)) {
                        if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) Tinebase_Core::getLogger()->notice(
                            __METHOD__ . '::' . __LINE__
                            . ' Removing export definition because'
                            . ' user has no read grant on template file id ' . $config->templateFileId);
                        $toRemove[] = $definition;
                    }
                }
            }
        }

        $result->removeRecords($toRemove);
        
        return $result;
    }

    /**
     * get definition from file
     *
     * @param string $_filename
     * @param string $_applicationId
     * @param string $_name [optional]
     * @return Tinebase_Model_ImportExportDefinition
     * @throws Tinebase_Exception_NotFound
     * @throws Tinebase_Exception_Record_DefinitionFailure
     * @throws Zend_Config_Exception
     * @throws Tinebase_Exception_InvalidArgument
     */
    public function getFromFile($_filename, $_applicationId, $_name = NULL)
    {
        if (file_exists($_filename)) {
            $basename = basename($_filename);
            $content = file_get_contents($_filename);
            $config = new Zend_Config_Xml($_filename);
            
            if ($_name === NULL) {
                $name = ($config->name) ? $config->name : preg_replace("/\.xml/", '', $basename);
            } else {
                $name = $_name;
            }

            $format = null;
            if (class_exists($config->plugin)) {
                try {
                    $plugin = $config->plugin;
                    if (is_subclass_of($plugin, 'Tinebase_Export_Abstract')) {
                        $format = $plugin::getDefaultFormat();
                    }
                } catch(Exception $e) {}
            }
            
            if ($config->overrideApplication) {
                $_applicationId = Tinebase_Application::getInstance()->getApplicationByName($config->overrideApplication)->getId();
            }
            
            return new Tinebase_Model_ImportExportDefinition(array(
                'application_id'              => $_applicationId,
                'name'                        => $name,
                'label'                       => empty($config->label) ? $name : $config->label,
                'description'                 => $config->description,
                'type'                        => $config->type,
                'model'                       => $config->model,
                'plugin'                      => $config->plugin,
                'icon_class'                  => $config->icon_class,
                'scope'                       => (empty($config->scope) ||
                        !in_array($config->scope, [
                            self::SCOPE_SINGLE,
                            self::SCOPE_MULTI,
                            self::SCOPE_HIDDEN,
                            self::SCOPE_REPORT,
                        ])) ? '' : $config->scope,
                'plugin_options'              => $content,
                'filename'                    => $basename,
                'favorite'                    => false == $config->favorite ? 0 : 1,
                'format'                      => $format,
                'order'                       => (int)$config->order,
                'mapUndefinedFieldsEnable'    => $config->mapUndefinedFieldsEnable,
                'mapUndefinedFieldsTo'        => $config->mapUndefinedFieldsTo,
                'postMappingHook'             => $config->postMappingHook,
                'filter'                      => $config->filter,
                Tinebase_Model_ImportExportDefinition::FLDS_SKIP_UPSTREAM_UPDATES => $config->skipUpstreamUpdates ?: false,
            ));
        } else {
            throw new Tinebase_Exception_NotFound('Definition file "' . $_filename . '" not found.');
        }
    }
    
    /**
     * get config options as Zend_Config_Xml object
     * 
     * @param Tinebase_Model_ImportExportDefinition $_definition
     * @param array $_additionalOptions additional options
     * @return Zend_Config_Xml|boolean
     */
    public static function getOptionsAsZendConfigXml(Tinebase_Model_ImportExportDefinition $_definition, $_additionalOptions = array())
    {
        $cacheId = 'ZendConfigXml_' . md5($_definition);
        $cache = Tinebase_Core::getCache();
        if (! $cache->test($cacheId)) {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ 
                . ' Generate new Zend_Config_Xml object' . $cacheId);

            $xmlConfig = (empty($_definition->plugin_options))
                ? '<?xml version="1.0" encoding="UTF-8"?><config></config>'
                : $_definition->plugin_options;
            try {
                $config = new Zend_Config_Xml($xmlConfig, /* section = */ null, /* runtime mods allowed = */ true);
            } catch (Throwable $t) {
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                    . ' Invalid XML: ' . $xmlConfig . ' Exception: ' . $t);
                throw new Tinebase_Exception_InvalidArgument('XML config is invalid');
            }
            $cache->save($config, $cacheId);
        } else {
            $config = $cache->load($cacheId);
        }
        
        if (! empty($_additionalOptions)) {
            $config->merge(new Zend_Config($_additionalOptions));
        }
        
        return $config;
    }

    /**
     * update existing definition or create new from file
     * - use backend functions (create/update) directly because we do not want any default controller handling here
     * - calling function needs to make sure that user has admin right!
     *
     * @param string $_filename
     * @param Tinebase_Model_Application $_application
     * @param string $_name
     * @return Tinebase_Model_ImportExportDefinition
     * @throws Exception
     * @throws Tinebase_Exception_InvalidArgument
     * @throws Tinebase_Exception_NotFound
     * @throws Tinebase_Exception_Record_DefinitionFailure
     * @throws Tinebase_Exception_Record_Validation
     * @throws Zend_Config_Exception
     */
    public function updateOrCreateFromFilename($_filename, $_application, $_name = NULL)
    {
        $definition = $this->getFromFile(
            $_filename,
            $_application->getId(),
            $_name
        );

        if ($definition->{Tinebase_Model_ImportExportDefinition::FLDS_SKIP_UPSTREAM_UPDATES}) {
            Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Skip updating definition: ' . $definition->name);
            return $definition;
        }

        // try to get definition and update if it exists
        try {
            // also update deleted
            $existing = $this->getByName($definition->name, true);
            Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Updating definition: ' . $definition->name);
            $definition->setId($existing->getId());
            $definition->is_deleted = $existing->is_deleted;
            $definition->container_id = $existing->container_id;
            $definition->seq = $existing->seq++;
            $result = $this->update($definition, true, true);
            
        } catch (Tinebase_Exception_NotFound $tenf) {
            // does not exist
            Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Creating import/export definion from file: ' . $_filename);
            $result = $this->create($definition);
        }
        
        return $result;
    }

    /**
     * repair definitions tables
     * 
     * - fixes application_ids
     * 
     * @todo should be moved to generic (?) backend
     */
    public function repairTable()
    {
        $definitions = $this->_backend->getAll();
        
        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
            . ' Got ' . count($definitions) . ' definitions. Checking definitions table ...');
        
        foreach ($definitions as $definition) {
            $appName = substr($definition->model, 0, strpos($definition->model, '_Model'));
            echo $appName;
            $application = Tinebase_Application::getInstance()->getApplicationByName($appName);
            if ($application->getId() !== $definition->application_id) {
                if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
                    . ' Fixing application_id for definition ' . $definition->name);
                $definition->application_id = $application->getId();
                $this->update($definition);
            }
        }
    }

    /**
     * get generic import definition
     *
     * @param string $model
     * @return Tinebase_Model_ImportExportDefinition
     */
    public function getGenericImport($model)
    {
        $extract = Tinebase_Application::extractAppAndModel($model);
        $appName = $extract['appName'];

        $xmlOptions = '<?xml version="1.0" encoding="UTF-8"?>
        <config>
            <headline>1</headline>
            <dryrun>0</dryrun>
            <delimiter>,</delimiter>
            <extension>csv</extension>
        </config>';
        $definition = new Tinebase_Model_ImportExportDefinition(array(
            'application_id'              => Tinebase_Application::getInstance()->getApplicationByName($appName)->getId(),
            'name'                        => $model . '_tine_generic_import_csv',
            // TODO translate
            'label'                       => 'Tine 2.0 ' . $model . ' import',
            'type'                        => 'import',
            'model'                       => $model,
            'plugin'                      => 'Tinebase_Import_Csv_Generic',
            'headline'                    => 1,
            'delimiter'                   => ',',
            'plugin_options'              => $xmlOptions,
//            'description'                 => $config->description,
//            'filename'                    => $basename,
        ));

        return $definition;
    }

    /**
     * sets personal container id if container id is missing in record - can be overwritten to set a different container
     *
     * @param $_record
     * @throws Tinebase_Exception_SystemGeneric
     */
    protected function _setContainer(Tinebase_Record_Interface $_record)
    {
        if (!$_record->container_id) {
            $_record->container_id = self::getDefaultImportExportContainer()->getId();
        }
    }

    protected static $defaultContainer = null;

    public static function resetDefaultContainer()
    {
        static::$defaultContainer = null;
    }

    public static function getDefaultImportExportContainer(): Tinebase_Model_Container
    {
        if (null === static::$defaultContainer) {
            if (!($containerId = Tinebase_Config::getInstance()->{Tinebase_Config::IMPORT_EXPORT_DEFAULT_CONTAINER})) {
                static::$defaultContainer = self::createDefaultImportExportContainer();
                Tinebase_Config::getInstance()->{Tinebase_Config::IMPORT_EXPORT_DEFAULT_CONTAINER} = static::$defaultContainer->getId();
            } else {
                static::$defaultContainer = Tinebase_Container::getInstance()->getContainerById($containerId);
            }
        }

        return static::$defaultContainer;
    }

    protected static function createDefaultImportExportContainer(): Tinebase_Model_Container
    {
        if (Tinebase_Core::isReplica()) {
            try {
                return Tinebase_Container::getInstance()->getContainerByName(Tinebase_Model_ImportExportDefinition::class,
                    'Internal Import/Export Container', Tinebase_Model_Container::TYPE_SHARED);
            } catch (Tinebase_Exception_NotFound $tenf) {
                throw new Tinebase_Exception_Record_NotAllowed('on replicas, default container needs to replicated from master');
            }
        }
        $container = new Tinebase_Model_Container(array(
            'name'              => 'Internal Import/Export Container',
            'type'              => Tinebase_Model_Container::TYPE_SHARED,
            'backend'           => 'Sql',
            'application_id'    => Tinebase_Core::getTinebaseId(),
            'model'             => Tinebase_Model_ImportExportDefinition::class,
        ));

        $grants = Tinebase_Model_Grants::getDefaultGrants([
            Tinebase_Model_Grants::GRANT_ADD => true,
            Tinebase_Model_Grants::GRANT_EDIT => true,
            Tinebase_Model_Grants::GRANT_DELETE => true,
        ]);
        $anybody = $grants->find('account_id', Tinebase_Core::getUser()->getId());
        if (Tinebase_Config::getInstance()->get(Tinebase_Config::ANYONE_ACCOUNT_DISABLED)) {
            $grants->removeRecord($anybody);
        } else {
            $anybody->account_id = 0;
            $anybody->account_type = Tinebase_Acl_Rights::ACCOUNT_TYPE_ANYONE;
            $anybody->{Tinebase_Model_Grants::GRANT_ADMIN} = false;
        }

        return Tinebase_Container::getInstance()->addContainer($container, $grants, true);
    }
}
