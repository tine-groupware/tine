<?php declare(strict_types=1);
/**
 * @package     EventManager
 * @subpackage  Model
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Tonia Leuschel <t.leuschel@metaways.de>
 * @copyright   Copyright (c) 2025 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * class to hold Event Manager Text option
 *
 * @package     EventManager
 * @subpackage  Model
 */
class EventManager_Model_TextInputOption extends Tinebase_Record_NewAbstract
{
    public const MODEL_NAME_PART = 'TextInputOption';
    public const FLD_TEXT = 'text';

    /**
     * Holds the model configuration (must be assigned in the concrete class)
     *
     * @var array
     */
    protected static $_modelConfiguration = [
        self::CREATE_MODULE         => false,
        self::APP_NAME              => EventManager_Config::APP_NAME,
        self::MODEL_NAME            => self::MODEL_NAME_PART,
        self::RECORD_NAME           => 'Text Input Option ',
        self::RECORDS_NAME          => 'Text Input Options', // ngettext('Text Input Option ', 'Text Input Options', n)
        self::TITLE_PROPERTY        => self::FLD_TEXT,

        self::FIELDS => [
            self::FLD_TEXT       => [
                self::LABEL                 => 'Text', // _('Text')
                self::TYPE                  => self::TYPE_STRING,
                self::NULLABLE              => true,
                self::DESCRIPTION           => 'Here you can ask the participant a question relevant for the event'
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

