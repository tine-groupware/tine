<?php
/**
 * class to hold Sieve Forward data
 * 
 * @package     Felamimail
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Ching En Cheng <c.cheng@metaways.de>
 * @copyright   Copyright (c) 2024 Metaways Infosystems GmbH (http://www.metaways.de)
 * 
 */

/**
 * class to hold Forward data
 * 
 * @package     Felamimail
 */
class Felamimail_Model_Sieve_Forward extends Tinebase_Record_NewAbstract
{
    public const FLD_ACCOUNT_ID = 'account_id';
    public const FLD_EMAIL = 'email';

    public const MODEL_NAME_PART = 'Sieve_Forward';
    public const TABLE_NAME = 'felamimail_sieve_forward';

    /**
     * holds the configuration object (must be declared in the concrete class)
     *
     * @var Tinebase_ModelConfiguration
     */
    protected static $_configurationObject = null;

    /**
     * Holds the model configuration (must be assigned in the concrete class)
     *
     * @var array
     */
    protected static $_modelConfiguration = [
        self::VERSION                       => 1,
        self::MODLOG_ACTIVE                 => true,
        self::APP_NAME                  => Felamimail_Config::APP_NAME,
        self::MODEL_NAME                => self::MODEL_NAME_PART,

        self::TABLE => [
            self::NAME => self::TABLE_NAME,
            self::INDEXES => [
                self::FLD_ACCOUNT_ID => [
                    self::COLUMNS => [self::FLD_ACCOUNT_ID]
                ]
            ]
        ],

        self::FIELDS                        => [
            self::FLD_ACCOUNT_ID                 => [
                self::TYPE                          => self::TYPE_STRING,
                self::LENGTH                        => 255,
                self::NULLABLE                      => true,
            ],
            self::FLD_EMAIL                      => [
                self::TYPE                          => self::TYPE_STRING,
                self::LENGTH                        => 255,
                self::DISABLED                      => true,
                self::VALIDATORS                    => [
                    Zend_Filter_Input::ALLOW_EMPTY      => false,
                    Zend_Filter_Input::PRESENCE         => Zend_Filter_Input::PRESENCE_REQUIRED,
                ],
            ],
        ],
    ];

    /**
     * set from sieve forward object
     *
     * @param Felamimail_Sieve_Forward $fsf
     */
    public function setFromFSF(Felamimail_Sieve_Forward $fsf)
    {
        $data = $fsf->toArray();
        $this->setFromArray($data);
    }
}
