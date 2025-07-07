<?php declare(strict_types=1);
/**
 * @package     EventManager
 * @subpackage  Model
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Tonia Leuschel <t.leuschel@metaways.de>
 * @copyright   Copyright (c) 2025 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * class to hold Event Manager File selection
 *
 * @package     EventManager
 * @subpackage  Model
 */
class EventManager_Model_Selections_File extends Tinebase_Record_NewAbstract
{
    public const MODEL_NAME_PART = 'Selections_File';
    public const FLD_FILE = 'file';

    /**
     * Holds the model configuration (must be assigned in the concrete class)
     *
     * @var array
     */
    protected static $_modelConfiguration = [
        self::CREATE_MODULE         => false,
        self::APP_NAME              => EventManager_Config::APP_NAME,
        self::MODEL_NAME            => self::MODEL_NAME_PART,
        self::RECORD_NAME           => 'File selection',
        self::RECORDS_NAME          => 'File selections', // ngettext('File selection', 'File selections', n)
        self::TITLE_PROPERTY        => self::FLD_FILE,

        self::FIELDS => [
            self::FLD_FILE     => [
                self::LABEL                 => 'File', // _('File') //Todo should be name of FileOption!
                self::TYPE                  => self::TYPE_ATTACHMENTS,
                self::DEFAULT_VAL           => false,
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

