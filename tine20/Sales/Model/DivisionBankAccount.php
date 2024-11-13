<?php declare(strict_types=1);
/**
 * class to hold Division Bank Account relation
 *
 * @package     Sales
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2024 Metaways Infosystems GmbH (http://www.metaways.de)
 */

class Sales_Model_DivisionBankAccount extends Tinebase_Record_NewAbstract
{
    public const MODEL_NAME_PART    = 'DivisionBankAccount';
    public const TABLE_NAME         = 'sales_division_bank_account';
    public const FLD_BANK_ACCOUNT  = 'bank_account';
    public const FLD_DIVISION  = 'division';

    /**
     * Holds the model configuration (must be assigned in the concrete class)
     *
     * @var array
     */
    protected static $_modelConfiguration = [
        self::VERSION                   => 1,
        self::APP_NAME                  => Sales_Config::APP_NAME,
        self::MODEL_NAME                => self::MODEL_NAME_PART,
        self::MODLOG_ACTIVE             => true,
        self::HAS_DELETED_TIME_UNIQUE   => true,
        self::IS_DEPENDENT              => true, // TODO FIXME: write a json fe test and see that its not FEable!
        self::CONTAINER_PROPERTY        => null,
        self::DELEGATED_ACL_FIELD       => self::FLD_DIVISION,

        self::TABLE                     => [
            self::NAME                      => self::TABLE_NAME,
            self::UNIQUE_CONSTRAINTS        => [
                self::FLD_DIVISION              => [
                    self::COLUMNS                   => [self::FLD_DIVISION, self::FLD_BANK_ACCOUNT, self::FLD_DELETED_TIME],
                ],
            ],
        ],

        self::ASSOCIATIONS              => [
            \Doctrine\ORM\Mapping\ClassMetadataInfo::MANY_TO_ONE => [
                self::FLD_BANK_ACCOUNT          => [
                    self::TARGET_ENTITY             => Tinebase_Model_BankAccount::class,
                    self::FIELD_NAME                => self::FLD_BANK_ACCOUNT,
                    self::JOIN_COLUMNS                  => [[
                        self::NAME                          => self::FLD_BANK_ACCOUNT,
                        self::REFERENCED_COLUMN_NAME        => self::ID,
                    ]],
                ],
                self::FLD_DIVISION              => [
                    self::TARGET_ENTITY             => Sales_Model_Division::class,
                    self::FIELD_NAME                => self::FLD_DIVISION,
                    self::JOIN_COLUMNS                  => [[
                        self::NAME                          => self::FLD_DIVISION,
                        self::REFERENCED_COLUMN_NAME        => self::ID,
                        self::ON_DELETE                     => self::CASCADE,
                    ]],
                ],
            ],
        ],

        self::FIELDS                    => [
            self::FLD_BANK_ACCOUNT          => [
                self::LABEL                     => 'Bank Account', // _('Bank Account')
                self::TYPE                      => self::TYPE_RECORD,
                self::VALIDATORS                => [
                    Zend_Filter_Input::ALLOW_EMPTY  => false,
                    Zend_Filter_Input::PRESENCE     => Zend_Filter_Input::PRESENCE_REQUIRED,
                ],
                self::CONFIG                    => [
                    self::APP_NAME                  => Tinebase_Config::APP_NAME,
                    self::MODEL_NAME                => Tinebase_Model_BankAccount::MODEL_NAME_PART,
                ],
            ],
            self::FLD_DIVISION              => [
                self::TYPE                      => self::TYPE_RECORD,
                self::VALIDATORS                => [
                    Zend_Filter_Input::ALLOW_EMPTY  => false,
                    Zend_Filter_Input::PRESENCE     => Zend_Filter_Input::PRESENCE_REQUIRED,
                ],
                self::CONFIG                    => [
                    self::APP_NAME                  => Sales_Config::APP_NAME,
                    self::MODEL_NAME                => Sales_Model_Division::MODEL_NAME_PART,
                ],
            ],
        ],
    ];

    /**
     * holds the configuration object (must be declared in the concrete class)
     *
     * @var Tinebase_ModelConfiguration
     */
    protected static $_configurationObject = null;
}
