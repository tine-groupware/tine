<?php

/**
 * convert functions for records from/to json (array) format
 *
 * @package     EventManager
 * @subpackage  Convert
 * @license     https://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Tonia Wulff <t.wulff@metaways.de>
 * @copyright   Copyright (c) 2026 Metaways Infosystems GmbH (https://www.metaways.de)
 */

/**
 * convert functions for records from/to json (array) format
 *
 * @package     EventManager
 * @subpackage  Convert
 */
use Tinebase_ModelConfiguration_Const as MCC;
class EventManager_Convert_Image_Json extends Tinebase_Convert_Json
{
    /**
     * resolves child records after converting the record set to an array
     *
     * @param array $result
     * @param Tinebase_ModelConfiguration $modelConfiguration
     * @param boolean $multiple
     *
     * @return array
     */
    protected function _resolveAfterToArray($result, $modelConfiguration, $multiple = false) {
        $result = parent::_resolveAfterToArray($result, $modelConfiguration, $multiple);
        if ($multiple) {
            $new_result = [];
            foreach ($result as $record) {
                $imagesAttachments = [];
                if ($record['consent']) {
                    $imagesAttachments[] = EventManager_Controller_ImageMetadata::getImageUrl(
                        EventManager_Config::APP_NAME,
                        $record['node_id'],
                    );
                }
                $record['image_vfs'] = $imagesAttachments;
                $new_result[] = $record;
            }
            $result = $new_result;
        } else {
            $imagesAttachments = [];
            if ($result['consent']) {
                $imagesAttachments[] = EventManager_Controller_ImageMetadata::getImageUrl(
                    EventManager_Config::APP_NAME,
                    $result['node_id'],
                );
            }
            $result['image_vfs'] = $imagesAttachments;
        }
        return $result;
    }
}
