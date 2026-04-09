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
 * Instance Model
 *
 * @package     Tinebase
 */
class Tinebase_Model_Instance extends Tinebase_Record_NewAbstract
{
    public const MODEL_NAME_PART = 'Instance';
    public const TABLE_NAME = 'instance';

    public const FLD_NAME = 'name';
    public const FLD_URL = 'url';
    public const FLD_MAIL_DOMAINS = 'mail_domains';

    /**
     * Holds the model configuration (must be assigned in the concrete class)
     *
     * @var array
     */
    protected static $_modelConfiguration = [
        self::VERSION => 1,
        self::MODLOG_ACTIVE => true,
        self::EXPOSE_JSON_API => true,
        self::RECORD_NAME           => 'Instance',
        self::RECORDS_NAME          => 'Instances', // ngettext('Instance', 'Instances', n)
        self::TITLE_PROPERTY        => '{{ name }}: {{ url }}',
        self::DEFAULT_SORT_INFO         => [self::FIELD => self::FLD_NAME],


        self::APP_NAME              => Tinebase_Config::APP_NAME,
        self::MODEL_NAME            => self::MODEL_NAME_PART,

        self::TABLE => [
            self::NAME => self::TABLE_NAME,
        ],

        self::JSON_EXPANDER             => [
            Tinebase_Record_Expander::EXPANDER_PROPERTIES => [
                self::FLD_MAIL_DOMAINS => [],
            ],
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
            self::FLD_URL         => [
                self::TYPE                  => self::TYPE_STRING,
                self::VALIDATORS            => [
                    Zend_Filter_Input::ALLOW_EMPTY => false,
                    Zend_Filter_Input::PRESENCE    => Zend_Filter_Input::PRESENCE_REQUIRED
                ],
                self::LABEL                 => 'URL' // _('URL')
            ],
            self::FLD_MAIL_DOMAINS         => [
                self::LABEL                 => 'Mail Domains', // _('URL')
                self::TYPE                  => self::TYPE_RECORDS,
                self::NULLABLE          => true,
                self::CONFIG            => [
                    self::APP_NAME          => Tinebase_Config::APP_NAME,
                    self::MODEL_NAME        => Tinebase_Model_InstanceMailDomain::MODEL_NAME_PART,
                    self::REF_ID_FIELD      => Tinebase_Model_InstanceMailDomain::FLD_INSTANCE_ID,
                    self::DEPENDENT_RECORDS         => true,
                ],
                self::UI_CONFIG                     => [
                    self::COLUMNS                     => [Tinebase_Model_InstanceMailDomain::FLD_DOMAIN_NAME],
                ],
            ],
        ]
    ];

    /**
     * holds the configuration object (must be declared in the concrete class)
     *
     * @var Tinebase_ModelConfiguration
     */
    protected static $_configurationObject = null;

    public function isReplicable()
    {
        return true;
    }
}
