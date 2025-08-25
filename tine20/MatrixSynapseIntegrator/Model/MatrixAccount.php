<?php
/**
 * class to hold MatrixAccount data
 * 
 * @package     MatrixSynapseIntegrator
 * @subpackage  Model
 * @license     https://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2025 Metaways Infosystems GmbH (http://www.metaways.de)
 * 
 */

/**
 * class to hold MatrixAccount data
 * 
 * @package     MatrixSynapseIntegrator
 * @subpackage  Model
 */
class MatrixSynapseIntegrator_Model_MatrixAccount extends Tinebase_Record_NewAbstract
{
    public const FLD_DESCRIPTION = 'description';
    public const FLD_ACCOUNT_ID = 'account_id';
    // public const FLD_ACTIVE = 'active';
    public const FLD_CC_ID = 'cc_id';
    public const FLD_MATRIX_ACCESS_TOKEN = 'matrix_access_token';
    public const FLD_MATRIX_DEVICE_ID = 'matrix_device_id';
    public const FLD_MATRIX_ID = 'matrix_id';
    public const FLD_MATRIX_RECOVERY_KEY = 'matrix_recovery_key';
    public const FLD_MATRIX_RECOVERY_PASSWORD = 'matrix_recovery_password';
    public const FLD_MATRIX_SESSION_KEY = 'matrix_session_key';

    public const MODEL_NAME_PART = 'MatrixAccount';
    public const TABLE_NAME = 'matrix_account';

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
        self::VERSION                   => 1,
        self::APP_NAME                  => MatrixSynapseIntegrator_Config::APP_NAME,
        self::MODEL_NAME                => self::MODEL_NAME_PART,

        self::RECORD_NAME               => 'Matrix Account', // _('Matrix Account') ngettext('Matrix Account', 'Matrix Accounts', n)
        self::RECORDS_NAME              => 'Matrix Accounts', // _('Matrix Accounts')
        self::TITLE_PROPERTY            => self::FLD_MATRIX_ID,

        self::HAS_RELATIONS             => true,
        self::HAS_CUSTOM_FIELDS         => true,
        self::HAS_SYSTEM_CUSTOM_FIELDS  => true,
        self::HAS_NOTES                 => true,
        self::HAS_TAGS                  => true,
        self::MODLOG_ACTIVE             => true,

        self::CREATE_MODULE             => true,
        self::EXPOSE_HTTP_API           => true,
        self::EXPOSE_JSON_API           => true,

        self::TABLE                     => [
            self::NAME                      => self::TABLE_NAME,
            self::INDEXES                   => [
                self::FLD_MATRIX_ID           => [
                    self::COLUMNS                   => [self::FLD_MATRIX_ID],
                ],
                self::FLD_DESCRIPTION           => [
                    self::COLUMNS                   => [self::FLD_DESCRIPTION],
                    self::FLAGS                     => [self::TYPE_FULLTEXT],
                ],
            ],
        ],

        self::EXPORT                    => [
            self::SUPPORTED_FORMATS         => ['csv'],
        ],

        self::FIELDS                    => [
            self::FLD_DESCRIPTION           => [
                self::TYPE                      => self::TYPE_FULLTEXT,
                self::NULLABLE                  => true,
                self::VALIDATORS                => [Zend_Filter_Input::ALLOW_EMPTY => true],
                self::LABEL                     => 'Description', // _('Description')
                self::QUERY_FILTER              => true,
            ],
           self::FLD_ACCOUNT_ID => [
               self::TYPE                      => self::TYPE_USER,
               self::NULLABLE                  => false,
               self::VALIDATORS                => [Zend_Filter_Input::ALLOW_EMPTY => false],
               self::LABEL                     => 'Account', // _('Account')
            ],
            self::FLD_CC_ID => [
                self::DISABLED      => true,
                self::NULLABLE      => true,
                self::TYPE          => self::TYPE_STRING,
            ],
            self::FLD_MATRIX_ACCESS_TOKEN => [
                self::TYPE                      => self::TYPE_STRING,
                self::LENGTH                    => 255,
                self::NULLABLE                  => true,
                self::VALIDATORS                => [Zend_Filter_Input::ALLOW_EMPTY => true],
                self::LABEL                     => 'Matrix Access Token', // _('Matrix Access Token')
            ],
            self::FLD_MATRIX_DEVICE_ID => [
                self::TYPE                      => self::TYPE_STRING,
                self::LENGTH                    => 255,
                self::NULLABLE                  => true,
                self::VALIDATORS                => [Zend_Filter_Input::ALLOW_EMPTY => true],
                self::LABEL                     => 'Matrix Device ID', // _('Matrix Device ID')
                self::QUERY_FILTER              => true,
            ],
            // device id is a transliterate from branding with an alphanumeric at the end
            self::FLD_MATRIX_ID                  => [
                self::TYPE                      => self::TYPE_STRING,
                self::LENGTH                    => 255,
                self::VALIDATORS                => [
                    Zend_Filter_Input::ALLOW_EMPTY  => false,
                    Zend_Filter_Input::PRESENCE     => Zend_Filter_Input::PRESENCE_REQUIRED,
                ],
                self::LABEL                     => 'Matrix ID', // _('Matrix ID')
                self::QUERY_FILTER              => true,
            ],
            self::FLD_MATRIX_RECOVERY_PASSWORD => [
                self::TYPE                      => self::TYPE_PASSWORD,
                self::CONFIG        => [
                    self::CREDENTIAL_CACHE => 'shared',
                    self::REF_ID_FIELD => self::FLD_CC_ID,
                ],
                self::NULLABLE                  => true,
                self::VALIDATORS                => [Zend_Filter_Input::ALLOW_EMPTY => true],
                self::LABEL                     => 'Matrix Recovery Password', // _('Matrix Recovery Password')
            ],
            self::FLD_MATRIX_RECOVERY_KEY => [
                self::TYPE                      => self::TYPE_PASSWORD,
                self::CONFIG        => [
                    self::CREDENTIAL_CACHE => 'shared',
                    self::REF_ID_FIELD => self::FLD_CC_ID,
                ],
                self::LENGTH                    => 255,
                self::NULLABLE                  => true,
                self::VALIDATORS                => [Zend_Filter_Input::ALLOW_EMPTY => true],
                self::LABEL                     => 'Matrix Recovery Key', // _('Matrix Recovery Key')
            ],
            // 32bytes of random base64 encode data. used as an aes key. does not need to be store in the secret
            // / password store. It only needs to be not accessible to the client/browser, if the user is not logged in.
            // (It is also ok if it is in the secret store. It is always used in conjunction with the access key.)
            // (It is used to "bind" the lifetime of the matrix index db to the session store)
            // example value: '2sPibjzJ8tbmmZFQ19Ncw9DMZuGqFlIQyG3zUTM3NCE='
            self::FLD_MATRIX_SESSION_KEY => [
                self::TYPE                      => self::TYPE_PASSWORD,
                self::CONFIG        => [
                    self::CREDENTIAL_CACHE => 'shared',
                    self::REF_ID_FIELD => self::FLD_CC_ID,
                ],
                self::LENGTH                    => 44,
                self::NULLABLE                  => true,
                self::VALIDATORS                => [Zend_Filter_Input::ALLOW_EMPTY => true],
                self::LABEL                     => 'Matrix Session Key', // _('Matrix Session Key')
            ],
            // TODO needed?
//            self::FLD_ACTIVE => [
//                self::TYPE                      => self::TYPE_BOOLEAN,
//                self::NULLABLE                  => false,
//                self::VALIDATORS                => [Zend_Filter_Input::ALLOW_EMPTY => true],
//                self::FILTER                    => [Zend_Filter_Empty::class => true],
//                self::LABEL                     => 'Active', // _('Active')
//                self::DEFAULT_VAL               => true,
//            ],
        ]
    ];
}
