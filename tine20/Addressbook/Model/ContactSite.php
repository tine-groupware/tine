<?php
/**
 * Tine 2.0
 *
 * @package     Addressbook
 * @subpackage  Model
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2020-2024 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Stefanie Stamer <s.stamer@metaways.de>
 */

use Doctrine\ORM\Mapping\ClassMetadataInfo;

/**
 * Model
 *
 * @package     Addressbook
 * @subpackage  Model
 */
class Addressbook_Model_ContactSite extends Tinebase_Record_NewAbstract
{
    const FLD_SITE = 'site';
    const FLD_CONTACT = 'contact';

    const MODEL_NAME_PART = 'ContactSite';
    const TABLE_NAME = 'adb_contact_sites';

    /**
     * Holds the model configuration (must be assigned in the concrete class)
     *
     * @var array
     */
    protected static $_modelConfiguration = [
        self::VERSION => 1,
        self::RECORD_NAME               => 'Site',  // ngettext('GENDER_Site')
        self::RECORDS_NAME              => 'Sites', // ngettext('Site', 'Sites', n)
        self::TITLE_PROPERTY            => "{{ renderTitle(site, 'Addressbook_Model_Contact') }}",
        self::DEFAULT_SORT_INFO         => [self::FIELD => self::FLD_SITE],
        self::HAS_RELATIONS             => false,
        self::HAS_CUSTOM_FIELDS         => false,
        self::HAS_SYSTEM_CUSTOM_FIELDS  => false,
        self::HAS_NOTES                 => false,
        self::HAS_TAGS                  => false,
        self::MODLOG_ACTIVE             => true,
        self::HAS_ATTACHMENTS           => false,

        self::CREATE_MODULE             => false,

        self::EXPOSE_HTTP_API           => true,
        self::EXPOSE_JSON_API           => true,

        self::APP_NAME                  => Addressbook_Config::APP_NAME,
        self::MODEL_NAME                => self::MODEL_NAME_PART,

        self::TABLE => [
            self::NAME      => self::TABLE_NAME,
            self::UNIQUE_CONSTRAINTS   => [
                self::FLD_SITE       => [
                    self::COLUMNS           => [self::FLD_SITE, self::FLD_CONTACT],
                ],
                self::FLD_CONTACT                => [
                    self::COLUMNS           => [self::FLD_CONTACT, self::FLD_SITE],
                ],
            ]
        ],

        self::ASSOCIATIONS => [
            \Doctrine\ORM\Mapping\ClassMetadataInfo::MANY_TO_ONE => [
                'group_fk' => [
                    'targetEntity' => Addressbook_Model_Contact::class,
                    'fieldName' => self::FLD_SITE,
                    'joinColumns' => [[
                        'name' => self::FLD_SITE,
                        'referencedColumnName'  => 'id'
                    ]],
                ],
            ],
        ],

        self::JSON_EXPANDER             => [
            Tinebase_Record_Expander::EXPANDER_PROPERTIES => [
                Addressbook_Model_ContactSite::FLD_SITE      => [],
                Addressbook_Model_ContactSite::FLD_CONTACT          => []
            ],
        ],

        self::FIELDS => [
            self::FLD_SITE      => [
                self::TYPE              => self::TYPE_RECORD,
                self::LENGTH            => 40,
                self::CONFIG            => [
                    self::APP_NAME          => Addressbook_Config::APP_NAME,
                    self::MODEL_NAME        => 'Contact',
                ],
                self::VALIDATORS        => [Zend_Filter_Input::ALLOW_EMPTY => false, 'presence' => 'required'],
                self::LABEL             => 'Site', // _('Site')
                self::QUERY_FILTER      => true,
            ],
            self::FLD_CONTACT            => [
                self::TYPE              => self::TYPE_RECORD,
                self::LENGTH            => 40,
                self::CONFIG            => [
                    self::APP_NAME          => Addressbook_Config::APP_NAME,
                    self::MODEL_NAME        => 'Contact',
                ],
                self::VALIDATORS        => [Zend_Filter_Input::ALLOW_EMPTY => false, 'presence' => 'required'],
                self::LABEL             => 'Contact', // _('Contact')
            ],
        ]
    ];

    /**
     * holds the configuration object (must be declared in the concrete class)
     *
     * @var Tinebase_ModelConfiguration
     */
    protected static $_configurationObject = NULL;
}