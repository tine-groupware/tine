<?php declare(strict_types=1);
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Model
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2025 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */

class Tinebase_Model_FileLocation extends Tinebase_Record_NewAbstract implements Tinebase_Model_FileLocation_Interface
{
    use Tinebase_Model_FileLocation_NoChgAfterInitTrait;
    use Tinebase_Model_FileLocation_DelegatorTrait;

    public const MODEL_NAME_PART = 'FileLocation';

    public const FLD_LOCATION = 'location';
    public const FLD_MODEL_NAME = 'model_name';


    protected static $_modelConfiguration = [
        self::APP_NAME                      => Tinebase_Config::APP_NAME,
        self::MODEL_NAME                    => self::MODEL_NAME_PART,
        self::RECORD_NAME                   => 'File Location', // ngettext('File Location', 'File Locations', n)
        self::RECORDS_NAME                  => 'File Locations', // gettext('GENDER_File Location')

        self::FIELDS                        => [
            self::FLD_LOCATION                  => [
                self::TYPE                          => self::TYPE_DYNAMIC_RECORD,
                self::CONFIG                        => [
                    self::REF_MODEL_FIELD               => self::FLD_MODEL_NAME,
                    self::PERSISTENT                    => true,
                ],
            ],
            self::FLD_MODEL_NAME            => [
                self::TYPE                      => self::TYPE_MODEL,
                self::CONFIG                    => [
                    self::AVAILABLE_MODELS          => [
                        Felamimail_Model_AttachmentCache_FileLocation::class,
                        Filemanager_Model_FileLocation::class,
                        Tinebase_Model_FileLocation_RecordAttachment::class,
                        Tinebase_Model_FileLocation_TreeNode::class,
                    ],
                ],
                self::VALIDATORS                    => [
                    Zend_Filter_Input::ALLOW_EMPTY      => false,
                    Zend_Filter_Input::PRESENCE         => Zend_Filter_Input::PRESENCE_REQUIRED,
                    [Zend_Validate_InArray::class, [
                        Felamimail_Model_AttachmentCache_FileLocation::class,
                        Filemanager_Model_FileLocation::class,
                        Tinebase_Model_FileLocation_RecordAttachment::class,
                        Tinebase_Model_FileLocation_TreeNode::class,
                    ]],
                ],
                self::UI_CONFIG                     => [
                    self::DISABLED                      => true,
                ],
            ],
        ],
    ];

    /**
     * holds the configuration object (must be declared in the concrete class)
     *
     * @var Tinebase_ModelConfiguration
     */
    protected static $_configurationObject = NULL;

    protected function _init(): void
    {
        if ($this->_init) {
            return;
        }
        $this->_init = true;
        $this->delegator = $this->{self::FLD_LOCATION};
    }
}