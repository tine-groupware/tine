<?php

declare(strict_types=1);

/**
 * Image controller for EventManager application
 *
 * @package     EventManager
 * @subpackage  Controller
 * @license     https://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Tonia Wulff <t.wulff@metaways.de>
 * @copyright   Copyright (c) 2026 Metaways Infosystems GmbH (https://www.metaways.de)
 *
 */

/**
 * Image controller class for EventManager application
 *
 * @package     EventManager
 * @subpackage  Controller
 */
class EventManager_Controller_ImageMetadata extends Tinebase_Controller_Record_Abstract
{
    /** @use Tinebase_Controller_SingletonTrait<EventManager_Controller_ImageMetadata> */
    use Tinebase_Controller_SingletonTrait;

    protected function __construct()
    {
        $this->_applicationName = EventManager_Config::APP_NAME;
        $this->_backend = new Tinebase_Backend_Sql([
            Tinebase_Backend_Sql_Abstract::MODEL_NAME => EventManager_Model_ImageMetadata::class,
            Tinebase_Backend_Sql_Abstract::TABLE_NAME => EventManager_Model_ImageMetadata::TABLE_NAME,
            Tinebase_Backend_Sql_Abstract::MODLOG_ACTIVE => true,
        ]);
        $this->_modelName = EventManager_Model_ImageMetadata::class;
        $this->_purgeRecords = false;
        $this->_doContainerACLChecks = false;
    }

    /**
     * returns an image url
     * @param string     $appName    the name of the application
     * @param string     $id         the identifier
     * @param integer    $width      width
     * @param integer    $height     height
     * @param integer    $ratiomode  ratiomode
     */
    public static function getImageUrl($appName, $id, $width = -1, $height = -1, $ratiomode = 0)
    {
        return $appName . '/get/eventmanager/image/' . $id . '/' . $width . '/' . $height . '/' . $ratiomode;
    }
}
