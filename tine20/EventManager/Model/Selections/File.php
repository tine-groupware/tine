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
        self::RECORD_NAME           => 'File selection',
        self::RECORDS_NAME          => 'File selections', // ngettext('File selection', 'File selections', n)
        self::TITLE_PROPERTY        => self::FLD_FILE_NAME,

        self::FIELDS => [
            self::FLD_NODE_ID                   => [
                self::LENGTH                        => 40,
                self::TYPE                          => self::TYPE_STRING,
                self::VALIDATORS                    => [
                    Zend_Filter_Input::ALLOW_EMPTY      => false,
                    Zend_Filter_Input::PRESENCE         => Zend_Filter_Input::PRESENCE_REQUIRED,
                ],
                self::UI_CONFIG                     => [
                    self::DISABLED                      => true,
                ],
            ],
            self::FLD_FILE_NAME => [
                self::LENGTH                       => 40,
                self::TYPE                          => self::TYPE_STRING,
                self::VALIDATORS                   => [
                    Zend_Filter_Input::ALLOW_EMPTY      => false,
                    Zend_Filter_Input::PRESENCE         => Zend_Filter_Input::PRESENCE_REQUIRED,
                ],
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
                self::VALIDATORS                   => [
                    Zend_Filter_Input::ALLOW_EMPTY      => false,
                    Zend_Filter_Input::PRESENCE         => Zend_Filter_Input::PRESENCE_REQUIRED,
                ],
                self::UI_CONFIG                     => [
                    self::DISABLED                      => true,
                ],
            ],
            self::FLD_FILE_ACKNOWLEDGMENT => [
                self::TYPE                          => self::TYPE_BOOLEAN,
                self::DEFAULT_VAL                   => false,
                self::LABEL                         =>
                    'Participant acknowledged the document',
                // _('Participant acknowledged the document')
            ],
            self::FLD_FILE_UPLOAD => [
                self::TYPE                          => self::TYPE_BOOLEAN,
                self::DEFAULT_VAL                   => false,
                self::LABEL                         => 'Participant uploaded a file',
                // _('Participant uploaded a file')
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

