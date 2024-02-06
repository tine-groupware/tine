<?php declare(strict_types=1);
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2023 Metaways Infosystems GmbH (http://www.metaways.de)
 */

class Tinebase_Model_Tree_FlySystem_AdapterConfig_Local extends Tinebase_Record_NewAbstract implements Tinebase_Model_Tree_FlySystem_AdapterConfig_Interface
{
    public const MODEL_NAME_PART = 'Tree_FlySystem_AdapterConfig_Local';

    public const FLD_BASE_PATH = 'base_path';
    public const FLD_NEVER_EMPTY = 'never_empty';

    /**
     * Holds the model configuration (must be assigned in the concrete class)
     *
     * @var array
     */
    protected static $_modelConfiguration = [
        self::APP_NAME      => Tinebase_Config::APP_NAME,
        self::MODEL_NAME    => self::MODEL_NAME_PART,

        self::FIELDS        => [
            self::FLD_BASE_PATH => [
                self::TYPE          => self::TYPE_STRING,
                self::LENGTH        => 255,
                self::VALIDATORS    => [
                    Zend_Filter_Input::PRESENCE => Zend_Filter_Input::PRESENCE_REQUIRED,
                ],
            ],
            self::FLD_NEVER_EMPTY => [
                self::TYPE          => self::TYPE_BOOLEAN,
            ],
        ],
    ];

    /**
     * holds the configuration object (must be declared in the concrete class)
     *
     * @var Tinebase_ModelConfiguration
     */
    protected static $_configurationObject = null;

    public function getFlySystemAdapter(): \League\Flysystem\FilesystemAdapter
    {
        $adapter = new \League\Flysystem\Local\LocalFilesystemAdapter($this->{self::FLD_BASE_PATH});
        if ($this->{self::FLD_NEVER_EMPTY}) {
            $success = false;
            if ($adapter->directoryExists('/')) {
                foreach ($adapter->listContents('/', false) as $smth) {
                    $success = true;
                    break;
                }
            }
            if (!$success) {
                throw new Tinebase_Exception_Backend('flysystem must not be empty, but it is');
            }
        }
        return $adapter;
    }
}