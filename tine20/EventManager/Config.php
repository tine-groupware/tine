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
    public const APPOINTMENT_STATUS = 'appointmentStatus';
    public const OPTION_LEVEL = 'optionLevel';
    public const DISPLAY_TYPE = 'displayType';
    public const OPTION_REQUIRED_TYPE = 'optionRequiredType';
    public const RULE_TYPE = 'ruleType';
    public const CRITERIA_TYPE = 'criteriaType';
    public const DEFAULT_CONTACT_EVENT_CONTAINER = 'defaultContactEventContainer';



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
                    ['id' => 1,      'value' => 'Main Event'], //_('Main Event')
                    ['id' => 2,      'value' => 'Workshop'], //_('Workshop')
                    ['id' => 3,      'value' => 'Project Event'], //_('Project Event')
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
                    ['id' => 2,      'value' => 'Waiting list'], //_('Waiting list')
                    ['id' => 3,      'value' => 'Canceled'], //_('Canceled')
                ],
                'default' => 1
            ]
        ],
        self::APPOINTMENT_STATUS => [
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
                    ['id' => 2,      'value' => 'Rescheduled'], //_('Rescheduled')
                    ['id' => 3,      'value' => 'Canceled'], //_('Canceled')
                ],
                'default' => 1
            ]
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
        self::OPTION_REQUIRED_TYPE => [
            self::LABEL                 => 'Option Required Type', //_('Option Required Type')
            self::DESCRIPTION           => 'List of all option required type available', //_('List of all option required type available')
            self::TYPE                  => self::TYPE_KEYFIELD_CONFIG,
            self::CLIENTREGISTRYINCLUDE => true,
            self::SETBYADMINMODULE      => true,
            self::DEFAULT_STR           => [
                self::RECORDS               => [
                    ['id' => 1, 'value' => 'Yes'], //_('Yes')
                    ['id' => 2, 'value' => 'No'], //_('No')
                    ['id' => 3, 'value' => 'If'], //_('If')
                ],
                self::DEFAULT_STR => 1
            ],
        ],
        self::DISPLAY_TYPE => [
            self::LABEL                 => 'Display Type', //_('Display Type')
            self::DESCRIPTION           => 'List of all display type available', //_('List of all display type available')
            self::TYPE                  => self::TYPE_KEYFIELD_CONFIG,
            self::CLIENTREGISTRYINCLUDE => true,
            self::SETBYADMINMODULE      => true,
            self::DEFAULT_STR           => [
                self::RECORDS               => [
                    ['id' => 1, 'value' => 'Always'], //_('Always')
                    ['id' => 2, 'value' => 'If'], //_('If')
                ],
                self::DEFAULT_STR => 1
            ],
        ],
        self::RULE_TYPE => [
            self::LABEL                 => 'Rule Type', //_('Rule Type')
            self::DESCRIPTION           => 'List of all rule type available', //_('List of all rule type available')
            self::TYPE                  => self::TYPE_KEYFIELD_CONFIG,
            self::CLIENTREGISTRYINCLUDE => true,
            self::SETBYADMINMODULE      => true,
            self::DEFAULT_STR           => [
                self::RECORDS               => [
                    ['id' => 1, 'value' => 'One or more conditions are fulfilled'], //_('One or more conditions are fulfilled')
                    ['id' => 2, 'value' => 'All conditions are fulfilled'], //_('All conditions are fulfilled')
                ],
                self::DEFAULT_STR => 1
            ],
        ],
        self::CRITERIA_TYPE => [
            self::LABEL                 => 'Criteria Type', //_('Criteria Type')
            self::DESCRIPTION           => 'List of all criteria type available', //_('List of all criteria type available')
            self::TYPE                  => self::TYPE_KEYFIELD_CONFIG,
            self::CLIENTREGISTRYINCLUDE => true,
            self::SETBYADMINMODULE      => true,
            self::DEFAULT_STR           => [
                self::RECORDS               => [
                    ['id' => 1, 'value' => 'Yes'], //_('Yes')
                    ['id' => 2, 'value' => 'No'], //_('No')
                    ['id' => 3, 'value' => 'is'], //_('is')
                    ['id' => 4, 'value' => 'is not'], //_('is not')
                    ['id' => 5, 'value' => 'greater or equal to'], //_('greater or equal to')
                    ['id' => 6, 'value' => 'smaller than'], //_('smaller than')
                ],
                self::DEFAULT_STR => 1
            ],
        ],
        self::DEFAULT_CONTACT_EVENT_CONTAINER => [
            //_('Default Container for Contacts of an Event')
            'label'                 => 'Default Container for Contacts of an Event',
            //_('The container where new contacts are created.')
            'description'           => 'The container where new contacts are created.',
            'type'                  => Tinebase_Config_Abstract::TYPE_STRING,
            'clientRegistryInclude' => true,
            'setByAdminModule'      => true,
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
