<?php
/**
 * Tine 2.0
 *
 * @package     CrewScheduling
 * @subpackage  Model
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2020 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Stefanie Stamer <s.stamer@metaways.de>
 */

use Doctrine\ORM\Mapping\ClassMetadataInfo;

/**
 * Model
 *
 * @package     CrewScheduling
 * @subpackage  Model
 */
class CrewScheduling_Model_RequiredGroups extends Tinebase_Record_NewAbstract
{
    const FLD_GROUP = 'group';
    const FLD_RECORD = 'record';

    const MODEL_NAME_PART = 'RequiredGroups';
    const TABLE_NAME = 'cs_required_groups';

    /**
     * Holds the model configuration (must be assigned in the concrete class)
     *
     * @var array
     */
    protected static $_modelConfiguration = [
        self::VERSION => 1,
        self::RECORD_NAME               => 'Required Group',  // gettext('GENDER_Required Group')
        self::RECORDS_NAME              => 'Required Groups', // ngettext('Required Group', 'Required Groups', n)
        self::TITLE_PROPERTY            => self::FLD_GROUP,
        self::HAS_RELATIONS             => false,
        self::HAS_CUSTOM_FIELDS         => false,
        self::HAS_SYSTEM_CUSTOM_FIELDS  => false,
        self::HAS_NOTES                 => false,
        self::HAS_TAGS                  => false,
        self::MODLOG_ACTIVE             => false,
        self::HAS_ATTACHMENTS           => false,

        self::CREATE_MODULE             => false,

        self::EXPOSE_HTTP_API           => true,
        self::EXPOSE_JSON_API           => true,

        self::APP_NAME                  => CrewScheduling_Config::APP_NAME,
        self::MODEL_NAME                => self::MODEL_NAME_PART,

        self::TABLE => [
            self::NAME      => self::TABLE_NAME,
            self::UNIQUE_CONSTRAINTS   => [
                self::FLD_GROUP       => [
                    self::COLUMNS           => [self::FLD_GROUP, self::FLD_RECORD],
                ],
                self::FLD_RECORD                => [
                    self::COLUMNS           => [self::FLD_RECORD, self::FLD_GROUP],
                ],
            ]
        ],

        self::ASSOCIATIONS => [
            \Doctrine\ORM\Mapping\ClassMetadataInfo::MANY_TO_ONE => [
                'group_fk' => [
                    'targetEntity' => Addressbook_Model_List::class,
                    'fieldName' => self::FLD_GROUP,
                    'joinColumns' => [[
                        'name' => self::FLD_GROUP,
                        'referencedColumnName'  => 'id'
                    ]],
                ],
            ],
        ],

        self::JSON_EXPANDER             => [
            Tinebase_Record_Expander::EXPANDER_PROPERTIES => [
                'group'      => [],
                'record'       => []
            ],
        ],

        self::FIELDS => [
            self::FLD_GROUP      => [
                self::TYPE              => self::TYPE_RECORD,
                self::LENGTH            => 40,
                self::CONFIG            => [
                    self::APP_NAME          => Addressbook_Config::APP_NAME,
                    self::MODEL_NAME        => Addressbook_Model_List::MODEL_NAME_PART,
                ],
                self::VALIDATORS        => [Zend_Filter_Input::ALLOW_EMPTY => false, 'presence' => 'required'],
                self::LABEL             => 'Required Group', // _('Required Group')
                self::QUERY_FILTER      => true,
            ],
            self::FLD_RECORD            => [
                self::TYPE              => self::TYPE_RECORD,
                self::LENGTH            => 40,
                self::CONFIG            => [
                    self::APP_NAME          => CrewScheduling_Config::APP_NAME,
//                    self::MODEL_NAME        => CrewScheduling_Model_EventTypeConfig::MODEL_NAME_PART, // NOTE: it's used for both
                    self::MODEL_NAME        => CrewScheduling_Model_SchedulingRole::MODEL_NAME_PART,
                ],
                self::VALIDATORS        => [Zend_Filter_Input::ALLOW_EMPTY => false, 'presence' => 'required'],
                self::LABEL             => 'Scheduling Role', // _('Scheduling Role')
                self::DISABLED          => true,
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
