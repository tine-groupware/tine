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
 * class to hold Event Manager File option
 *
 * @package     EventManager
 * @subpackage  Model
 */
class EventManager_Model_FileOption extends Tinebase_Record_NewAbstract
{
    public const MODEL_NAME_PART = 'FileOption';
    public const FLD_NODE_ID = 'node_id';
    public const FLD_FILE_NAME = 'file_name';
    public const FLD_FILE_SIZE = 'file_size';
    public const FLD_FILE_TYPE = 'file_type';
    public const FLD_FILE_ACKNOWLEDGMENT = 'file_acknowledgement';
    public const FLD_FILE_UPLOAD = 'file_upload';

    /**
     * Holds the model configuration (must be assigned in the concrete class)
     *
     * @var array
     */
    protected static $_modelConfiguration = [
        self::CREATE_MODULE         => false,
        self::APP_NAME              => EventManager_Config::APP_NAME,
        self::MODEL_NAME            => self::MODEL_NAME_PART,
        self::RECORD_NAME           => 'File', // gettext('GENDER_File')
        self::RECORDS_NAME          => 'File', // ngettext('File', 'Files', n)
        self::TITLE_PROPERTY        => self::FLD_FILE_NAME,

        self::FIELDS => [
            self::FLD_NODE_ID                   => [
                self::LENGTH                        => 40,
                self::TYPE                          => self::TYPE_STRING,
                self::UI_CONFIG                     => [
                    self::DISABLED                      => true,
                ],
            ],
            self::FLD_FILE_NAME => [
                self::LENGTH                       => 40,
                self::TYPE                          => self::TYPE_STRING,
                self::UI_CONFIG                     => [
                    self::DISABLED                      => true,
                ],
            ],
            self::FLD_FILE_SIZE => [
                self::TYPE                          => self::TYPE_INTEGER,
                self::UI_CONFIG                     => [
                    self::DISABLED                      => true,
                ],
            ],
            self::FLD_FILE_TYPE => [
                self::LENGTH                       => 40,
                self::TYPE                          => self::TYPE_STRING,
                self::UI_CONFIG                     => [
                    self::DISABLED                      => true,
                ],
            ],
            self::FLD_FILE_ACKNOWLEDGMENT => [
                self::TYPE                          => self::TYPE_BOOLEAN,
                self::DEFAULT_VAL                   => true,
                self::LABEL                         =>
                    'Only participant acknowledgement is necessary (no participant document upload)',
                // _('Only participant acknowledgement is necessary (no participant document upload)')
            ],
            self::FLD_FILE_UPLOAD => [
                self::TYPE                          => self::TYPE_BOOLEAN,
                self::DEFAULT_VAL                   => false,
                self::LABEL                         => 'Participant should upload a file',
                // _('Participant should upload a file')
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
