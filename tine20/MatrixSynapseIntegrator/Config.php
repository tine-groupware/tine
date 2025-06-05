<?php

/**
 * Tine 2.0
 *
 * @package     MatrixSynapseIntegrator
 * @subpackage  Config
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2020-2025 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * MatrixSynapseIntegrator config class
 *
 * @package     MatrixSynapseIntegrator
 * @subpackage  Config
 *
 */
class MatrixSynapseIntegrator_Config extends Tinebase_Config_Abstract
{
    public const APP_NAME = 'MatrixSynapseIntegrator';

    public const MATRIX_DOMAIN = 'matrixDomain';
    public const HOME_SERVER_URL = 'homeServerUrl';
    public const CORPORAL_SHARED_AUTH_TOKEN = 'corporalSharedAuthToken';

    public const USER_XPROP_MATRIX_ID = 'matrixId';
    public const USER_XPROP_MATRIX_ACTIVE = 'matrixActive';

    public const MATRIX_DIRECTORY_ENABLED = 'matrixDirectoryEnabled';
    public const MATRIX_DIRECTORY_DATABASE_URL = 'matrixDirectoryDatabaseUrl';
    public const MATRIX_DIRECTORY_DATABASE_USERNAME = 'matrixDirectoryDatabaseUsername';
    public const MATRIX_DIRECTORY_DATABASE_PASSWORD = 'matrixDirectoryDatabasePassword';
    public const MATRIX_SYNAPSE_TENANT_NAME = 'matrixSynapseTenantName';
    public const MATRIX_SYNAPSE_DATABASE_URL = 'matrixSynapseDatabaseUrl';
    public const MATRIX_SYNAPSE_DATABASE_USERNAME = 'matrixSynapseDatabaseUsername';
    public const MATRIX_SYNAPSE_DATABASE_PASSWORD = 'matrixSynapseDatabasePassword';

    /**
     * (non-PHPdoc)
     * @see tine20/Tinebase/Config/Abstract::$_appName
     */
    protected $_appName = self::APP_NAME;

    /**
     * (non-PHPdoc)
     * @see tine20/Tinebase/Config/Definition::$_properties
     */
    protected static $_properties   = [
        self::MATRIX_DOMAIN            => [
            //_('Matrix Domain')
            self::LABEL                     => 'Matrix Domain',
            //_('Matrix Domain')
            self::DESCRIPTION               => 'Matrix Domain',
            self::TYPE                      => self::TYPE_STRING,
            self::CLIENTREGISTRYINCLUDE     => true,
            self::SETBYADMINMODULE          => true,
            self::SETBYSETUPMODULE          => true,
        ],
        self::HOME_SERVER_URL            => [
            //_('Home Server URL')
            self::LABEL                     => 'Home Server URL',
            //_('Home Server URL')
            self::DESCRIPTION               => 'Home Server URL',
            self::TYPE                      => self::TYPE_STRING,
            self::CLIENTREGISTRYINCLUDE     => true,
            self::SETBYADMINMODULE          => true,
            self::SETBYSETUPMODULE          => true,
            self::DEFAULT_STR               => 'https://matrix.mydomain',
        ],
        self::CORPORAL_SHARED_AUTH_TOKEN     => [
            //_('Corporal Shared Auth Token')
            self::LABEL                     => 'Corporal Shared Auth Token',
            //_('Corporal Shared Auth Token')
            self::DESCRIPTION               => 'Corporal Shared Auth Token',
            self::TYPE                      => self::TYPE_STRING,
            self::CLIENTREGISTRYINCLUDE     => false,
            self::SETBYADMINMODULE          => false,
            self::SETBYSETUPMODULE          => false,
            self::DEFAULT_STR               => 'abc',
        ],
        self::MATRIX_DIRECTORY_ENABLED => [
            //_('Synapse directory export enabled')
            self::LABEL                 => 'Synapse directory export enabled',
            //_('Synapse directory export enabled')
            self::DESCRIPTION           => 'Synapse directory export enabled',
            self::TYPE                  => self::TYPE_BOOL,
            self::CLIENTREGISTRYINCLUDE => false,
            self::SETBYADMINMODULE      => false,
            self::SETBYSETUPMODULE      => true,
            self::DEFAULT_STR           => false,
        ],
        self::MATRIX_DIRECTORY_DATABASE_URL => [
            //_('Synapse directory database postgres url')
            self::LABEL                 => 'Synapse directory database postgres url',
            //_('Synapse directory database postgres url')
            self::DESCRIPTION           => 'Synapse directory database postgres url',
            self::TYPE                  => self::TYPE_STRING,
            self::CLIENTREGISTRYINCLUDE => false,
            self::SETBYADMINMODULE      => false,
            self::SETBYSETUPMODULE      => true,
        ],
        self::MATRIX_DIRECTORY_DATABASE_USERNAME => [
            //_('Synapse directory database postgres username')
            self::LABEL                 => 'Synapse directory database postgres username',
            //_('Synapse directory database postgres username')
            self::DESCRIPTION           => 'Synapse directory database postgres username',
            self::TYPE                  => self::TYPE_STRING,
            self::CLIENTREGISTRYINCLUDE => false,
            self::SETBYADMINMODULE      => false,
            self::SETBYSETUPMODULE      => true,
        ],
        self::MATRIX_DIRECTORY_DATABASE_PASSWORD => [
            //_('Synapse directory database postgres password')
            self::LABEL                 => 'Synapse directory database postgres password',
            //_('Synapse directory database postgres password')
            self::DESCRIPTION           => 'Synapse directory database postgres password',
            self::TYPE                  => self::TYPE_STRING,
            self::CLIENTREGISTRYINCLUDE => false,
            self::SETBYADMINMODULE      => false,
            self::SETBYSETUPMODULE      => true,
        ],
        self::MATRIX_SYNAPSE_TENANT_NAME => [
            //_('Synapse tenant name')
            self::LABEL                 => 'Synapse tenant name',
            //_('Synapse tenant name')
            self::DESCRIPTION           => 'Synapse tenant name',
            self::TYPE                  => self::TYPE_STRING,
            self::CLIENTREGISTRYINCLUDE => false,
            self::SETBYADMINMODULE      => false,
            self::SETBYSETUPMODULE      => true,
        ],
        self::MATRIX_SYNAPSE_DATABASE_URL => [
            //_('Synapse database postgres url')
            self::LABEL                 => 'Synapse database postgres url',
            //_('Synapse database postgres url')
            self::DESCRIPTION           => 'Synapse database postgres url',
            self::TYPE                  => self::TYPE_STRING,
            self::CLIENTREGISTRYINCLUDE => false,
            self::SETBYADMINMODULE      => false,
            self::SETBYSETUPMODULE      => true,
        ],
        self::MATRIX_SYNAPSE_DATABASE_USERNAME => [
            //_('Synapse database postgres username')
            self::LABEL                 => 'Synapse database postgres username',
            //_('Synapse database postgres username')
            self::DESCRIPTION           => 'Synapse database postgres username',
            self::TYPE                  => self::TYPE_STRING,
            self::CLIENTREGISTRYINCLUDE => false,
            self::SETBYADMINMODULE      => false,
            self::SETBYSETUPMODULE      => true,
        ],
        self::MATRIX_SYNAPSE_DATABASE_PASSWORD => [
            //_('Synapse database postgres password')
            self::LABEL                 => 'Synapse database postgres password',
            //_('Synapse database postgres password')
            self::DESCRIPTION           => 'Synapse database postgres password',
            self::TYPE                  => self::TYPE_STRING,
            self::CLIENTREGISTRYINCLUDE => false,
            self::SETBYADMINMODULE      => false,
            self::SETBYSETUPMODULE      => true,
        ],
    ];

    /**
     * holds the instance of the singleton
     *
     * @var MatrixSynapseIntegrator_Config
     */
    private static $_instance = NULL;

    /**
     * the constructor
     *
     * don't use the constructor. use the singleton
     */
    private function __construct()
    {
    }

    /**
     * the constructor
     *
     * don't use the constructor. use the singleton
     */
    private function __clone()
    {
    }

    /**
     * Returns instance of DFCom_Config
     *
     * @return MatrixSynapseIntegrator_Config
     */
    public static function getInstance()
    {
        if (self::$_instance === NULL) {
            self::$_instance = new self();
        }

        return self::$_instance;
    }

    /**
     * (non-PHPdoc)
     * @see tine20/Tinebase/Config/Abstract::getProperties()
     */
    public static function getProperties()
    {
        return self::$_properties;
    }
}
