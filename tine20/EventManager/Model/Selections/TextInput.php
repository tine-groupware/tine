<?php declare(strict_types=1);
/**
 * @package     EventManager
 * @subpackage  Model
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Tonia Leuschel <t.leuschel@metaways.de>
 * @copyright   Copyright (c) 2025 Metaways Infosystems GmbH (http://www.metaways.de)
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
    //public const FLD_TEXT = 'text';
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
        self::RECORD_NAME           => 'TextInput',
        self::RECORDS_NAME          => 'TextInputs', // ngettext('TextInput', 'TextInputs', n)
        self::TITLE_PROPERTY        => self::FLD_RESPONSE,

        self::FIELDS => [
            /*self::FLD_TEXT       => [
                self::LABEL                 => 'Text', // _('Text')
                self::TYPE                  => self::TYPE_STRING,
                self::VALIDATORS            => [Zend_Filter_Input::ALLOW_EMPTY  => true,],
                self::DESCRIPTION           => 'This is the question asked in the TextInputOption',
                self::READ_ONLY             => true,
            ],*/ //todo: listener to show text input option question here
            self::FLD_RESPONSE     => [
                self::LABEL                 => 'Response', // _('Response')
                self::TYPE                  => self::TYPE_STRING,
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

