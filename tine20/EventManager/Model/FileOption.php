<?php declare(strict_types=1);
/**
 * @package     EventManager
 * @subpackage  Model
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Tonia Leuschel <t.leuschel@metaways.de>
 * @copyright   Copyright (c) 2025 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * class to hold Event Manager File option
 *
 * @package     EventManager
 * @subpackage  Model
 */
class EventManager_Model_FileOption extends Tinebase_Record_NewAbstract
{
    public const MODEL_NAME_PART = 'FileOption';

    /**
     * Holds the model configuration (must be assigned in the concrete class)
     *
     * @var array
     */
    protected static $_modelConfiguration = [
        self::CREATE_MODULE         => false,
        self::APP_NAME              => EventManager_Config::APP_NAME,
        self::MODEL_NAME            => self::MODEL_NAME_PART,
        self::RECORD_NAME           => 'File Option',
        self::RECORDS_NAME          => 'File Options', // ngettext('File Option', 'File Options', n)

        self::FIELDS => [
           /* self::FLD_NODE_ID => [
                self::TYPE => self::TYPE_RECORD,
                self::LENGTH => 40,
                self::OMIT_MOD_LOG => true,
                self::VALIDATORS => [
                    Zend_Filter_Input::ALLOW_EMPTY => false,
                    Zend_Filter_Input::PRESENCE => Zend_Filter_Input::PRESENCE_REQUIRED
                ],
                self::CONFIG => [
                    self::APP_NAME => Tinebase_Config::APP_NAME,
                    self::MODEL_NAME => 'Tree_Node',
                ]
            ],*/
        ]
    ];

    /**
     * holds the configuration object (must be declared in the concrete class)
     *
     * @var Tinebase_ModelConfiguration
     */
    protected static $_configurationObject = null;
}

