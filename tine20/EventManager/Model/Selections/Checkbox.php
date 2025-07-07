<?php declare(strict_types=1);
/**
 * @package     EventManager
 * @subpackage  Model
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Tonia Leuschel <t.leuschel@metaways.de>
 * @copyright   Copyright (c) 2025 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * class to hold Event Manager Checkbox selection
 *
 * @package     EventManager
 * @subpackage  Model
 */
class EventManager_Model_Selections_Checkbox extends Tinebase_Record_NewAbstract
{
    public const MODEL_NAME_PART = 'Selections_Checkbox';
    public const FLD_BOOKED = 'booked';

    /**
     * Holds the model configuration (must be assigned in the concrete class)
     *
     * @var array
     */
    protected static $_modelConfiguration = [
        self::CREATE_MODULE         => false,
        self::APP_NAME              => EventManager_Config::APP_NAME,
        self::MODEL_NAME            => self::MODEL_NAME_PART,
        self::RECORD_NAME           => 'Checkbox',
        self::RECORDS_NAME          => 'Checkboxes', // ngettext('Checkbox', 'Checkboxes', n)
        self::TITLE_PROPERTY        => self::FLD_BOOKED,

        self::FIELDS => [
            self::FLD_BOOKED     => [
                self::LABEL                 => 'Booked', // _('Booked')
                self::TYPE                  => self::TYPE_BOOLEAN,
                self::DEFAULT_VAL           => false,
                self::NULLABLE              => true,
            ]
        ]
    ];

    /**
     * holds the configuration object (must be declared in the concrete class)
     *
     * @var Tinebase_ModelConfiguration
     */
    protected static $_configurationObject = null;
}

