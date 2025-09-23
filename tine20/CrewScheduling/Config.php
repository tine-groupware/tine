<?php
/**
 * @package     CrewScheduling
 * @subpackage  Config
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2012-2020 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * CrewScheduling config class
 *
 * @package     CrewScheduling
 * @subpackage  Config
 */
class CrewScheduling_Config extends Tinebase_Config_Abstract
{
    const APP_NAME = 'CrewScheduling';

    /**
     * crewscheduling_roles
     *
     * @var string
     */
    const CREWSHEDULING_ROLES = 'crewscheduling_roles';

    /**
     * event field name for cs role configs
     *
     * @var string
     */
    const EVENT_ROLES_CONFIGS = 'cs_roles_configs';

    /**
     * cs_role_configs
     *
     * @var string
     */
    const CS_ROLE_CONFIGS = 'cs_role_configs';

    /**
     * shortfall_actions
     *
     * @var string
     */
    const SHORTFALL_ACTIONS = 'shortfall_actions';

    /**
     * exceedance_actions
     *
     * @var string
     */
    const EXCEEDANCE_ACTIONS = 'exceedance_actions';

    /**
     * same_role_same_attendee
     *
     * @var string
     */
    const SAME_ROLE_SAME_ATTENDEE = 'same_role_same_attendee';

    /**
     * group_operators
     *
     * @var string
     */
    const GROUP_OPERATORS = 'group_operators';

    const ACTION_NONE = 'none';
    const ACTION_FORBIDDEN = 'forbidden';
    const ACTION_EVENT_TENTATIVE = 'tentative';

    const OPERATOR_ONE_OF = 'OR';
    const OPERATOR_ALL_OF = 'AND';

    const OPTION_MAY = 'may';
    const OPTION_MUST = 'must';
    const OPTION_MUST_NOT = 'must_not';



    /**
     * (non-PHPdoc)
     * @see tine20/Tinebase/Config/Definition::$_properties
     */
    protected static $_properties = [
        self::SHORTFALL_ACTIONS => [
            //_('Shortfall Actions')
            'label' => 'Shortfall Actions',
            //_('Possible Shortfall Actions for role.')
            'description' => 'Possible Shortfall Actions for role.',
            'type' => 'keyFieldConfig',
            'clientRegistryInclude' => TRUE,
            'default' => [
                'records' => [
                    ['id' => self::ACTION_NONE, 'value' => 'None', 'system' => true], // _('None')
                    ['id' => self::ACTION_FORBIDDEN, 'value' => 'Forbidden', 'system' => true], // _('Forbidden')
                    ['id' => self::ACTION_EVENT_TENTATIVE, 'value' => 'Event tentative', 'system' => true], // _('Event tentative')
                ],
                'default' => self::ACTION_NONE
            ]
        ],
        self::EXCEEDANCE_ACTIONS => [
            //_('Exceedance Actions')
            'label' => 'Exceedance Actions',
            //_('Possible Exceedance Actions for role.')
            'description' => 'Possible Exceedance Actions for role.',
            'type' => 'keyFieldConfig',
            'clientRegistryInclude' => TRUE,
            'default' => [
                'records' => [
                    ['id' => self::ACTION_NONE, 'value' => 'None', 'system' => true], // _('None')
                    ['id' => self::ACTION_FORBIDDEN, 'value' => 'Forbidden', 'system' => true], // _('Forbidden')
                    ['id' => self::ACTION_EVENT_TENTATIVE, 'value' => 'Event tentative', 'system' => true], // _('Event tentative')
                ],
                'default' => self::ACTION_NONE
            ]
        ],
        self::SAME_ROLE_SAME_ATTENDEE => [
            //_('Same role same attendee')
            'label' => 'Same role same attendee',
            //_('Behavior if same role is also required from other event types')
            'description' => 'Behavior if same role is also required from other event types',
            'type' => 'keyFieldConfig',
            'clientRegistryInclude' => TRUE,
            'default' => [
                'records' => [
                    ['id' => self::OPTION_MAY, 'value' => 'May be filled with the same attendee', 'system' => true], // _('May be filled with the same attendee')
                    ['id' => self::OPTION_MUST, 'value' => 'Must be filled with the same attendee', 'system' => true], // _('Must be filled with the same attendee')
                    ['id' => self::OPTION_MUST_NOT, 'value' => 'Must not be filled with the same attendee', 'system' => true], // _('Must not be filled with the same attendee')
                ],
                'default' => self::OPTION_MAY
            ]
        ],
        self::GROUP_OPERATORS => [
            //_('Group Operators')
            'label' => 'Group Operators',
            //_('Possible Group Operators.')
            'description' => 'Possible Group Operators.',
            'type' => 'keyFieldConfig',
            'clientRegistryInclude' => TRUE,
            'default' => [
                'records' => [
                    ['id' => self::OPERATOR_ONE_OF, 'value' => 'Participant must be a member of one of the groups', 'system' => true], // _('Participant must be a member of one of the groups')
                    ['id' => self::OPERATOR_ALL_OF, 'value' => 'Participant must be a member of all of the groups', 'system' => true], // _('Participant must be a member of all of the groups')
                ],
                'default' => self::OPERATOR_ONE_OF
            ]
        ],
    ];

    /**
     * (non-PHPdoc)
     * @see tine20/Tinebase/Config/Abstract::$_appName
     */
    protected $_appName = 'CrewScheduling';

    private static $_instance = null;

    private function __construct()
    {
    }

    private function __clone()
    {
    }

    public static function getInstance()
    {
        if (self::$_instance === null) {
            self::$_instance = new self();
        }

        return self::$_instance;
    }

    public static function getProperties()
    {
        return self::$_properties;
    }
}
