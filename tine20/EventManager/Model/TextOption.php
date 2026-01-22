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
class EventManager_Model_TextOption  extends Tinebase_Record_NewAbstract
{
    public const MODEL_NAME_PART = 'TextOption';
    public const FLD_TEXT_OPTION = 'text_option';

    /**
     * Holds the model configuration (must be assigned in the concrete class)
     *
     * @var array
     */
    protected static $_modelConfiguration = [
        self::CREATE_MODULE         => false,
        self::APP_NAME              => EventManager_Config::APP_NAME,
        self::MODEL_NAME            => self::MODEL_NAME_PART,
        self::RECORD_NAME           => 'Text Output', // gettext('GENDER_Text Output')
        self::RECORDS_NAME          => 'Text Outputs', // ngettext('Text Output', 'Text Outputs', n)
        self::TITLE_PROPERTY        => self::FLD_TEXT_OPTION,

        self::FIELDS => [
            self::FLD_TEXT_OPTION       => [
                self::LABEL                 => 'Text', // _('Text')
                self::TYPE                  => self::TYPE_FULLTEXT,
                self::SPECIAL_TYPE          => self::SPECIAL_TYPE_MARKDOWN,
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

