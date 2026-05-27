<?php

declare(strict_types=1);

/**
 * @package     EventManager
 * @subpackage  Model
 * @license     https://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Tonia Wulff <t.leuschel@metaways.de>
 * @copyright   Copyright (c) 2025 Metaways Infosystems GmbH (https://www.metaways.de)
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
    public const FLD_MULTIPLE_LINES = 'multiple_lines';
    public const FLD_MAX_CHAR = 'max_characters';
    public const FLD_ONLY_NUMBERS = 'only_numbers';

    /**
     * Holds the model configuration (must be assigned in the concrete class)
     *
     * @var array
     */
    protected static $_modelConfiguration = [
        self::CREATE_MODULE         => false,
        self::APP_NAME              => EventManager_Config::APP_NAME,
        self::MODEL_NAME            => self::MODEL_NAME_PART,
        self::RECORD_NAME           => 'Text Input', // gettext('GENDER_Text Input')
        self::RECORDS_NAME          => 'Text Input', // ngettext('Text Input', 'Text Input', n)

        self::FIELDS => [
            self::FLD_ONLY_NUMBERS    => [
                self::LABEL                 => 'Reply can only be a number', // _('Reply can only be a number')
                self::TYPE                  => self::TYPE_BOOLEAN,
                self::DEFAULT_VAL           => false,
            ],
            self::FLD_MULTIPLE_LINES  => [
                self::LABEL                 => 'Multiple lines reply allowed', // _('Multiple lines reply allowed')
                self::TYPE                  => self::TYPE_BOOLEAN,
                self::DEFAULT_VAL           => false,
            ],
            self::FLD_MAX_CHAR       => [
                self::LABEL                 => 'Maximal number of characters allowed',
                                            // _('Maximal number of characters allowed')
                self::TYPE                  => self::TYPE_INTEGER,
                self::NULLABLE              => true,
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

