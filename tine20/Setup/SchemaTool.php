<?php
/**
 * Tine 2.0
 *
 * @package     Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2016-2024 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 */

use Doctrine\Common\Cache\Psr6\DoctrineProvider;
use Doctrine\ORM\Tools\Setup;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\DBAL\Schema\Comparator;
use Doctrine\Persistence\Mapping\StaticReflectionService;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

/**
 * helper around docrine2/dbal schema tools
 */
class Setup_SchemaTool
{
    protected static $_dbParams = null;
    protected static $_uninstalledTables = [];

    public static function addUninstalledTable(string $table): void
    {
        static::$_uninstalledTables[$table] = $table;
    }

    public static function resetUninstalledTables(): void
    {
        static::$_uninstalledTables = [];
    }

    public static function setDBParams(array $dbParams)
    {
        static::$_dbParams = $dbParams;
    }

    /**
     * convert tine20config to dbal config
     *
     * @return array
     */
    public static function getDBParams()
    {
        if (null === static::$_dbParams) {
            $dbParams = Tinebase_Config::getInstance()->get('database')->toArray();
            $dbParams['driver'] = $dbParams['adapter'];
            $dbParams['user'] = $dbParams['username'];
            $db = Setup_Core::getDb();
            if ($db instanceof Zend_Db_Adapter_Pdo_Mysql) {
                if ($db->getConfig()['charset'] !== 'utf8' &&
                        Tinebase_Backend_Sql_Adapter_Pdo_Mysql::supportsUTF8MB4($db)) {
                    $dbParams['defaultTableOptions'] = [
                        'charset' => 'utf8mb4',
                        'collate' => 'utf8mb4_unicode_ci'
                    ];
                } else {
                    $dbParams['defaultTableOptions'] = [
                        'charset' => 'utf8',
                        'collate' => 'utf8_unicode_ci'
                    ];
                }
            }

            $dbParams['defaultTableOptions']['row_format'] = 'DYNAMIC';

            static::$_dbParams = $dbParams;
        }

        return static::$_dbParams;
    }

    /**
     * get orm config
     *
     * @return \Doctrine\ORM\Configuration
     * @throws Setup_Exception
     */
    public static function getConfig(array $models = [])
    {
        $mappingDriver = new Tinebase_Record_DoctrineMappingDriver();
        $tableNames = [];

        $config = self::getBasicConfig();
        $config->setMetadataDriverImpl($mappingDriver);

        try {
            $additionalModels = [];
            /** @var Tinebase_Record_Interface $modelName */
            foreach ($mappingDriver->getAllClassNames($models) as $modelName) {
                if (null !== ($modelConfig = $modelName::getConfiguration()) &&
                        null !== ($tblName = $modelConfig->getTableName())) {
                    if (!empty($models)) {
                        foreach ($modelConfig->getAssociations() as $assocType) {
                            foreach ($assocType as $assoc) {
                                if (($assoc['targetEntity'] ?? false) && !in_array($assoc['targetEntity'], $models)) {
                                    $additionalModels[] = $assoc['targetEntity'];
                                }
                            }
                        }
                    }
                    $tableNames[] = SQL_TABLE_PREFIX . $tblName;
                }
            }

            if (!empty($additionalModels)) {
                return self::getConfig(array_merge($models, $additionalModels));
            }

            $config->setFilterSchemaAssetsExpression('/' . implode('|', $tableNames) . '/');
        } catch (Zend_Db_Exception $zde) {
            $config->setFilterSchemaAssetsExpression('/' . SQL_TABLE_PREFIX . '/');
        }

        return $config;
    }

    /**
     * @return \Doctrine\ORM\Configuration
     */
    public static function getBasicConfig()
    {
        // TODO we could use the tine20 redis cache here if configured (see \Doctrine\ORM\Tools\Setup::createConfiguration)
        // but as createConfiguration() tries to setup a redis cache if redis extension is available, we need to
        // setup a manual ArrayCache for the moment
        $config = Setup::createConfiguration(/* isDevMode = */ false, /* $proxyDir = */ null, DoctrineProvider::wrap(new ArrayAdapter()));
        return $config;
    }

    public static function getEntityManager(array $models = [])
    {
        $em = EntityManager::create(self::getDBParams(), self::getConfig($models));

        // needed to prevent runtime reflection that needs private properties ...
        $em->getMetadataFactory()->setReflectionService(new StaticReflectionService());

        return $em;
    }

    public static function getMetadata(array $modelNames, ?EntityManager $em = null)
    {
        if (null === $em) {
            $em = self::getEntityManager();
        }

        $mappingDriver = new Tinebase_Record_DoctrineMappingDriver();
        $classes = [];
        foreach($modelNames as $modelName) {
            if (!$mappingDriver->isTransient($modelName)) {
                throw new Tinebase_Exception('can\'t get metadata of non transient model '. $modelName);
            }
            $mdInfo = $em->getClassMetadata($modelName);
            if (isset(self::$_uninstalledTables[$mdInfo->getTableName()])) {
                continue;
            }
            $classes[] = $mdInfo;
        }

        return $classes;
    }

    /*public static function createSchema(array $modelNames)
    {
        $em = self::getEntityManager();
        $tool = new SchemaTool($em);
        $classes = self::getMetadata($modelNames);

        $tool->createSchema($classes);
        self::updateApplicationTable($modelNames);
    }*/

    public static function updateSchema(array $modelNames)
    {
        $em = self::getEntityManager($modelNames);
        $tool = new SchemaTool($em);
        $classes = self::getMetadata($modelNames, $em);

        $tool->updateSchema($classes, true);
        self::updateApplicationTable($modelNames);
    }

    public static function updateAllSchema()
    {
        $em = self::getEntityManager();
        $tool = new SchemaTool($em);
        $allModels = (new Tinebase_Record_DoctrineMappingDriver())->getAllClassNames();
        $classes = self::getMetadata($allModels, $em);

        $tool->updateSchema($classes, true);

        self::updateApplicationTable($allModels);
    }

    public static function updateApplicationTable(array $models)
    {
        try {
            Tinebase_Application::getInstance()->getApplicationByName(Tinebase_Config::APP_NAME);
        } catch (Tinebase_Exception_NotFound $tenf) {
            return;
        }

        /** @var Tinebase_Record_Interface $model */
        foreach ($models as $model) {
            if (($table = $model::getConfiguration()->getTableName()) &&
                    ($version = $model::getConfiguration()->getVersion())) {
                if (isset(self::$_uninstalledTables[SQL_TABLE_PREFIX . $table])) {
                    continue;
                }
                Tinebase_Application::getInstance()->removeApplicationTable($model::getConfiguration()->getAppName(),
                    $table);
                Tinebase_Application::getInstance()->addApplicationTable($model::getConfiguration()->getAppName(),
                    $table, $version);
            }
        }
    }

    public static function hasSchemaUpdates(bool $logError = false, bool $throwException = false): bool
    {
        $em = self::getEntityManager();
        $tool = new SchemaTool($em);
        $classes = [];

        $mappingDriver = new Tinebase_Record_DoctrineMappingDriver();
        foreach($mappingDriver->getAllClassNames() as $modelName) {
            $classes[] = $em->getClassMetadata($modelName);
        }

        $sqls = array_filter($tool->getUpdateSchemaSql($classes, true), function ($val) {
            return ((strpos($val, "CHANGE is_deleted is_deleted TINYINT(1) DEFAULT '0' NOT NULL") === false)
                || (strpos($val, ", CHANGE is_deleted is_deleted TINYINT(1) DEFAULT '0' NOT NULL") !== false)
                || (strpos($val, "CHANGE is_deleted is_deleted TINYINT(1) DEFAULT '0' NOT NULL,") !== false))
                && $val !== "ALTER TABLE " . SQL_TABLE_PREFIX . "tree_nodes CHANGE islink islink TINYINT(1) DEFAULT '0' NOT NULL, CHANGE is_deleted is_deleted TINYINT(1) DEFAULT '0' NOT NULL"
                && $val !== "ALTER TABLE " . SQL_TABLE_PREFIX . "sales_purchase_invoices CHANGE is_payed is_payed TINYINT(1) DEFAULT '0', CHANGE is_approved is_approved TINYINT(1) DEFAULT '0', CHANGE is_deleted is_deleted TINYINT(1) DEFAULT '0' NOT NULL"
                && $val !== "ALTER TABLE " . SQL_TABLE_PREFIX . "sales_sales_invoices CHANGE is_auto is_auto TINYINT(1) DEFAULT '0', CHANGE is_deleted is_deleted TINYINT(1) DEFAULT '0' NOT NULL";
        });

        if (!empty($sqls)) {
            $message = 'Pending schema updates found: ' . print_r($sqls, true);
            if ($throwException) {
                throw new Setup_Exception_InvalidSchema($message);
            } else {
                $logMethod = $logError ? 'err' : 'debug';
                Setup_Core::getLogger()->{$logMethod}(__METHOD__ . '::' . __LINE__ .
                    ' ' . $message);
            }
        }

        return !empty($sqls);
    }

    /**
     * compare two tine20 databases with each other
     *
     * @param $otherDbName
     * @return array of sql statements
     */
    public static function compareSchema($otherDbName, $otherUserName = null, $otherPassword = null)
    {
        $dbParams = self::getDBParams();

        $myConn = \Doctrine\DBAL\DriverManager::getConnection(
            $dbParams
        );
        $mySm = $myConn->getSchemaManager();

        $otherDbParams = $dbParams;
        $otherDbParams['dbname'] = $otherDbName;
        if (null !== $otherUserName) {
            $otherDbParams['user'] = $otherUserName;
        }
        if (null !== $otherPassword) {
            $otherDbParams['password'] = $otherPassword;
        }
        $otherConn = \Doctrine\DBAL\DriverManager::getConnection(
            $otherDbParams
        );
        $otherSm = $otherConn->getSchemaManager();

        $comparator = new Comparator();
        $schemaDiff = $comparator->compare($mySm->createSchema(), $otherSm->createSchema());

        return $schemaDiff->toSaveSql($myConn->getDatabasePlatform());
    }
}
