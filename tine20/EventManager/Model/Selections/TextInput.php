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
 * class to hold Event Manager TextInput selection
 *
 * @package     EventManager
 * @subpackage  Model
 */
class EventManager_Model_Selections_TextInput extends Tinebase_Record_NewAbstract
{
    public const MODEL_NAME_PART = 'Selections_TextInput';
    public const FLD_RESPONSE = 'response';

    /**
     * Holds the model configuration (must be assigned in the concrete class)
     *
     * @var array
     */
    protected static $_modelConfiguration = [
        self::CREATE_MODULE         => false,
        self::APP_NAME              => EventManager_Config::APP_NAME,
        self::MODEL_NAME            => self::MODEL_NAME_PART,
        self::RECORD_NAME           => 'TextInput', // gettext('GENDER_TextInput')
        self::RECORDS_NAME          => 'TextInputs', // ngettext('TextInput', 'TextInputs', n)
        self::TITLE_PROPERTY        => self::FLD_RESPONSE,

        self::FIELDS => [
            self::FLD_RESPONSE     => [
                self::LABEL                 => 'Response', // _('Response')
                self::TYPE                  => self::TYPE_FULLTEXT,
                self::SPECIAL_TYPE          => self::SPECIAL_TYPE_MARKDOWN,
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

