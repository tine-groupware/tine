<?php
/**
 * Tine 2.0
 *
 * @package     EventManager
 * @subpackage  Config
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2020-2021 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * EventManager config class
 *
 * @package     EventManager
 * @subpackage  Config
 *
 */
class EventManager_Config extends Tinebase_Config_Abstract
{
    use Tinebase_Controller_SingletonTrait;

    const APP_NAME = 'EventManager';
    public const EVENT_TYPE = 'eventType';
    public const EVENT_STATUS = 'eventStatus';
    public const REGISTRATION_FUNCTION = 'registrationFunction';
    public const REGISTRATION_SOURCE = 'registrationSource';
    public const REGISTRATION_STATUS = 'registrationStatus';
    public const REGISTRATION_MEMBER_STATUS = 'registrationMemberStatus';
    public const LANGUAGES_AVAILABLE = 'languagesAvailable';
    public const OPTION_LEVEL = 'optionLevel';
    public const OPTION_TYPE = 'optionType';



    /**
     * (non-PHPdoc)
     * @see tine20/Tinebase/Config/Abstract::$_appName
     */
    protected $_appName = self::APP_NAME;

    /**
     * (non-PHPdoc)
     * @see tine20/Tinebase/Config/Definition::$_properties
     */
    protected static $_properties = [
        self::EVENT_TYPE => [
            //_('Type')
            'label'                 => 'Type',
            //_('')
            'description'           => '',
            'type'                  => Tinebase_Config_Abstract::TYPE_KEYFIELD_CONFIG,
            'clientRegistryInclude' => true,
            'setByAdminModule'      => true,
            'default'               => [
                'records' => [
                    ['id' => 1,      'value' => 'Hauptveranstaltung'], //_('Hauptveranstaltung')
                    ['id' => 2,      'value' => 'Workshop'], //_('Workshop')
                    ['id' => 3,      'value' => 'Projektveranstaltung'], //_('Projektveranstaltung')
                ],
                'default' => 1
            ]
        ],
        self::EVENT_STATUS => [
            //_('Status')
            'label'                 => 'Status',
            //_('')
            'description'           => '',
            'type'                  => Tinebase_Config_Abstract::TYPE_KEYFIELD_CONFIG,
            'clientRegistryInclude' => true,
            'setByAdminModule'      => true,
            'default'               => [
                'records' => [
                    ['id' => 1,      'value' => 'Open'], //_('Open')
                    ['id' => 2,      'value' => 'Closed'], //_('Closed')
                    ['id' => 3,      'value' => 'Canceled'], //_('Canceled')
                    ['id' => 4,      'value' => 'Planning'], //_('Planning')
                ],
                'default' => 1
            ]
        ],
        self::REGISTRATION_FUNCTION => [
            //_('Function')
            'label'                 => 'Function',
            //_('')
            'description'           => '',
            'type'                  => Tinebase_Config_Abstract::TYPE_KEYFIELD_CONFIG,
            'clientRegistryInclude' => true,
            'setByAdminModule'      => true,
            'default'               => [
                'records' => [
                    ['id' => 1,      'value' => 'Attendee'], //_('Attendee')
                    ['id' => 2,      'value' => 'Speaker'], //_('Speaker')
                    ['id' => 3,      'value' => 'Moderator'], //_('Moderator')
                ],
                'default' => 1
            ]
        ],
        self::REGISTRATION_SOURCE => [
            //_('Source')
            'label'                 => 'Source',
            //_('')
            'description'           => '',
            'type'                  => Tinebase_Config_Abstract::TYPE_KEYFIELD_CONFIG,
            'clientRegistryInclude' => true,
            'setByAdminModule'      => true,
            'default'               => [
                'records' => [
                    ['id' => 1,      'value' => 'Online'], //_('Online')
                    ['id' => 2,      'value' => 'Manually'], //_('Manually')
                ],
                'default' => 1
            ]
        ],
        self::REGISTRATION_STATUS => [
            //_('Status')
            'label'                 => 'Status',
            //_('')
            'description'           => '',
            'type'                  => Tinebase_Config_Abstract::TYPE_KEYFIELD_CONFIG,
            'clientRegistryInclude' => true,
            'setByAdminModule'      => true,
            'default'               => [
                'records' => [
                    ['id' => 1,      'value' => 'Confirmed'], //_('Confirmed')
                    ['id' => 2,      'value' => 'Canceled'], //_('Canceled')
                ],
                'default' => 1
            ]
        ],
        self::REGISTRATION_MEMBER_STATUS => [
            //_('Member Status')
            'label'                 => 'Member Status',
            //_('')
            'description'           => '',
            'type'                  => Tinebase_Config_Abstract::TYPE_KEYFIELD_CONFIG,
            'clientRegistryInclude' => true,
            'setByAdminModule'      => true,
            'default'               => [
                'records' => [
                    ['id' => 1,      'value' => 'Member'], //_('Member')
                    ['id' => 2,      'value' => 'Not a Member'], //_('Not a Member')
                ],
                'default' => 1
            ]
        ],
        self::LANGUAGES_AVAILABLE => [
            self::LABEL                 => 'Languages Available', //_('Languages Available')
            self::DESCRIPTION           => 'List of languages available in the modules.', //_('List of languages available in the modules.')
            self::TYPE                  => self::TYPE_KEYFIELD_CONFIG,
            'localeTranslationList'     => 'Language',
            self::CLIENTREGISTRYINCLUDE => true,
            self::SETBYADMINMODULE      => true,
            self::DEFAULT_STR           => [
                self::RECORDS               => [
                    ['id' => 'de', 'value' => 'German'],
                    ['id' => 'en', 'value' => 'English'],
                ],
                self::DEFAULT_STR           => 'en',
            ],
        ],
        self::OPTION_LEVEL => [
            //_('Level')
            'label'                 => 'Level',
            //_('')
            'description'           => '',
            'type'                  => Tinebase_Config_Abstract::TYPE_KEYFIELD_CONFIG,
            'clientRegistryInclude' => true,
            'setByAdminModule'      => true,
            'default'               => [
                'records' => [
                    ['id' => 1,      'value' => 'Level 1'], //_('Level 1')
                    ['id' => 2,      'value' => 'Level 2'], //_('Level 2')
                    ['id' => 3,      'value' => 'Level 3'], //_('Level 3')
                    ['id' => 4,      'value' => 'Level 4'], //_('Level 4')
                    ['id' => 5,      'value' => 'Level 5'], //_('Level 5')
                    ['id' => 6,      'value' => 'Level 6'], //_('Level 6')
                ],
                'default' => 1
            ]
        ],
        self::OPTION_TYPE => [
            //_('Type')
            'label'                 => 'Type',
            //_('')
            'description'           => '',
            'type'                  => Tinebase_Config_Abstract::TYPE_KEYFIELD_CONFIG,
            'clientRegistryInclude' => true,
            'setByAdminModule'      => true,
            'default'               => [
                'records' => [
                    ['id' => 1,      'value' => 'Optional'], //_('Optional')
                    ['id' => 2,      'value' => 'Required'], //_('Required')
                    ['id' => 3,      'value' => 'No Option'], //_('No Option')
                ],
                'default' => 1
            ]
        ],
    ];

    /**
     * (non-PHPdoc)
     * @see tine20/Tinebase/Config/Abstract::getProperties()
     */
    public static function getProperties()
    {
        return self::$_properties;
    }
}
