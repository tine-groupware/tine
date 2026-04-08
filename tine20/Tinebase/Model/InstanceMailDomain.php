<?php declare(strict_types=1);
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Ching En Cheng <c.cheng@metaways.de>
 * @copyright   Copyright (c) 2026 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * InstanceMailDomain Model
 *
 * @package     Tinebase
 */
class Tinebase_Model_InstanceMailDomain extends Tinebase_Record_NewAbstract
{
    public const MODEL_NAME_PART = 'InstanceMailDomain';
    public const TABLE_NAME = 'instance_mail_domain';

    public const FLD_INSTANCE_ID = 'instance_id';
    public const FLD_DOMAIN_NAME = 'domain_name';

    /**
     * Holds the model configuration (must be assigned in the concrete class)
     *
     * @var array
     */
    protected static $_modelConfiguration = [
        self::VERSION => 1,
        self::MODLOG_ACTIVE => true,
        self::EXPOSE_JSON_API => true,
        self::RECORD_NAME           => 'Instance Domain',
        self::RECORDS_NAME          => 'Instance Domains', // ngettext('Instance Domain', 'Instance Domains', n)
        self::TITLE_PROPERTY        => self::FLD_DOMAIN_NAME,

        self::APP_NAME              => Tinebase_Config::APP_NAME,
        self::MODEL_NAME            => self::MODEL_NAME_PART,

        self::TABLE => [
            self::NAME => self::TABLE_NAME,
        ],

        self::FIELDS                => [
            self::FLD_INSTANCE_ID         => [
                self::TYPE              => self::TYPE_RECORD,
                self::VALIDATORS        => [
                    Zend_Filter_Input::ALLOW_EMPTY => false,
                    Zend_Filter_Input::PRESENCE    => Zend_Filter_Input::PRESENCE_REQUIRED,
                ],
                self::CONFIG            => [
                    self::APP_NAME          => Tinebase_Config::APP_NAME,
                    self::MODEL_NAME        => Tinebase_Model_Instance::MODEL_NAME_PART,
                ],
                self::DISABLED          => true,
                self::LABEL                 => 'Instance ID', // _('Instance ID')
                self::QUERY_FILTER              => true,
            ],
            self::FLD_DOMAIN_NAME         => [
                self::TYPE                  => self::TYPE_STRING,
                self::VALIDATORS            => [
                    Zend_Filter_Input::ALLOW_EMPTY => false,
                    Zend_Filter_Input::PRESENCE    => Zend_Filter_Input::PRESENCE_REQUIRED
                ],
                self::LABEL                 => 'Domain Name', // _('Domain Name')
                self::QUERY_FILTER              => true,
            ],
        ]
    ];

    /**
     * holds the configuration object (must be declared in the concrete class)
     *
     * @var Tinebase_ModelConfiguration
     */
    protected static $_configurationObject = null;
}
