<?php declare(strict_types=1);
/**
 * class to handle poll -> site association
 *
 * @package     CrewScheduling
 * @subpackage  Record
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2025 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiß <c.cweiss@metaways.de>
 */

class CrewScheduling_Model_PollSite extends Tinebase_Record_NewAbstract
{
    public const MODEL_NAME_PART    = 'PollSite';
    public const TABLE_NAME         = 'cs_poll_site';

    public const FLD_POLL  = 'poll_id';
    public const FLD_SITE = 'site_id';
    
    /**
     * Holds the model configuration (must be assigned in the concrete class)
     *
     * @var array
     */
    protected static $_modelConfiguration = [
        self::VERSION                   => 1,
        self::APP_NAME                  => CrewScheduling_Config::APP_NAME,
        self::MODEL_NAME                => self::MODEL_NAME_PART,
        self::CONTAINER_PROPERTY        => null,
        self::RECORD_NAME               => 'Site',  // gettext('GENDER_Site')
        self::RECORDS_NAME              => 'Sites', // ngettext('Site', 'Sites', n)

        self::TABLE                     => [
            self::NAME                      => self::TABLE_NAME,
            self::INDEXES                   => [
                self::FLD_POLL                  => [
                    self::COLUMNS                   => [self::FLD_POLL],
                ],
                self::FLD_SITE               => [
                    self::COLUMNS                   => [self::FLD_SITE],
                ],
            ],
        ],

        self::ASSOCIATIONS              => [
            \Doctrine\ORM\Mapping\ClassMetadataInfo::MANY_TO_ONE => [
                self::FLD_SITE              => [
                    self::TARGET_ENTITY             => Addressbook_Model_Contact::class,
                    self::FIELD_NAME                => self::FLD_SITE,
                    self::JOIN_COLUMNS                  => [[
                        self::NAME                          => self::FLD_SITE,
                        self::REFERENCED_COLUMN_NAME        => self::ID,
                        self::ON_DELETE                     => self::CASCADE,
                    ]],
                ],
                self::FLD_POLL                  => [
                    self::TARGET_ENTITY             => CrewScheduling_Model_Poll::class,
                    self::FIELD_NAME                => self::FLD_POLL,
                    self::JOIN_COLUMNS                  => [[
                        self::NAME                          => self::FLD_POLL,
                        self::REFERENCED_COLUMN_NAME        => self::ID,
                        self::ON_DELETE                     => self::CASCADE,
                    ]],
                ],
            ],
        ],

        self::FIELDS                    => [
            self::FLD_POLL                  => [
                self::LABEL                     => 'Poll', // _('Poll')
                self::TYPE                      => self::TYPE_RECORD,
                self::QUERY_FILTER              => true,
                self::CONFIG                    => [
                    self::APP_NAME                  => CrewScheduling_Config::APP_NAME,
                    self::MODEL_NAME                => CrewScheduling_Model_Poll::MODEL_NAME_PART,
                ],
                self::VALIDATORS            => [
                    Zend_Filter_Input::ALLOW_EMPTY  => false,
                    Zend_Filter_Input::PRESENCE     => Zend_Filter_Input::PRESENCE_REQUIRED,
                ],
            ],
            self::FLD_SITE               => [
                self::LABEL                     => 'Site', // _('Site')
                self::TYPE                      => self::TYPE_RECORD,
                self::QUERY_FILTER              => true,
                self::CONFIG                    => [
                    self::APP_NAME                  => Addressbook_Config::APP_NAME,
                    self::MODEL_NAME                => Addressbook_Model_Contact::MODEL_NAME_PART,
                ],
                self::VALIDATORS                => [
                    Zend_Filter_Input::ALLOW_EMPTY  => false,
                    Zend_Filter_Input::PRESENCE     => Zend_Filter_Input::PRESENCE_REQUIRED,
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
