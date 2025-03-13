<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2018-2021 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Stefanie Stamer <s.stamer@metaways.de>
 */

/**
 * BankAccount Model
 *
 * @package     Tinebase
 */
class Tinebase_Model_BankAccount extends Tinebase_Record_Abstract
{
    public const MODEL_NAME_PART = 'BankAccount';
    public const TABLE_NAME = 'bankaccounts';

    public const FLD_NAME = 'name';
    public const FLD_IBAN = 'iban';
    public const FLD_BIC = 'bic';
    public const FLD_DESCRIPTION = 'description';

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
        self::VERSION => 1,
        self::RECORD_NAME           => 'Bank Account',
        self::RECORDS_NAME          => 'Bank Accounts', // ngettext('Bank Account', 'Bank Accounts', n)
        self::TITLE_PROPERTY        => '{{ name }}: {{ iban }} - {{ description }}',
        self::DEFAULT_SORT_INFO     => [self::FIELD => self::FLD_NAME],

        self::HAS_RELATIONS => false,
        self::HAS_ATTACHMENTS => true,
        self::HAS_NOTES => true,
        self::HAS_TAGS => true,
        self::MODLOG_ACTIVE => true,
        self::EXPOSE_HTTP_API => true,
        self::EXPOSE_JSON_API => true,
        self::CREATE_MODULE => false,

        self::APP_NAME              => Tinebase_Config::APP_NAME,
        self::MODEL_NAME            => self::MODEL_NAME_PART,

        self::TABLE => [
            self::NAME => self::TABLE_NAME,
        ],

        self::FIELDS                => [
            self::FLD_NAME         => [
                self::TYPE                  => self::TYPE_STRING,
                self::VALIDATORS            => [
                    Zend_Filter_Input::ALLOW_EMPTY => false,
                    Zend_Filter_Input::PRESENCE    => Zend_Filter_Input::PRESENCE_REQUIRED
                ],
                self::LABEL                 => 'Name', // _('Name')
                self::QUERY_FILTER              => true,
            ],
            self::FLD_IBAN         => [
                self::TYPE                  => self::TYPE_STRING,
                self::VALIDATORS            => [
                    Zend_Filter_Input::ALLOW_EMPTY => false,
                    Zend_Filter_Input::PRESENCE    => Zend_Filter_Input::PRESENCE_REQUIRED
                ],
                self::LABEL                 => 'IBAN' // _('IBAN')
            ],
            self::FLD_BIC         => [
                self::TYPE                  => self::TYPE_STRING,
                self::NULLABLE => true,
                self::VALIDATORS => [
                    Zend_Filter_Input::ALLOW_EMPTY => true,
                ],
                self::INPUT_FILTERS => [Zend_Filter_Empty::class => null],
                self::LABEL                 => 'BIC' // _('BIC')
            ],
            self::FLD_DESCRIPTION           => [
                self::LABEL                     => 'Description', // _('Description')
                self::TYPE                      => self::TYPE_FULLTEXT,
                self::NULLABLE                  => true,
                self::VALIDATORS                => [Zend_Filter_Input::ALLOW_EMPTY => TRUE],
                self::QUERY_FILTER              => true,
            ],
        ]
    ];
}
