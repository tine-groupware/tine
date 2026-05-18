<?php
/**
 * convert functions for records from/to json (array) format
 * 
 * @package     EventManager
 * @subpackage  Convert
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * @copyright   Copyright (c) 2012-2026 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * convert functions for records from/to json (array) format
 *
 * @package     EventManager
 * @subpackage  Convert
 */
use Tinebase_ModelConfiguration_Const as MCC;
class EventManager_Convert_Event_Json extends Tinebase_Convert_Json
{
    protected function _resolveBeforeToArray($records, $modelConfiguration, $multiple = false)
    {
        $jsonExpander = $modelConfiguration->jsonExpander;
        foreach (Addressbook_Model_Contact::getAdditionalAddressFields() as $field) {
            $jsonExpander[Tinebase_Record_Expander::EXPANDER_PROPERTIES][EventManager_Model_Event::FLD_REGISTRATIONS]
                [Tinebase_Record_Expander::EXPANDER_PROPERTIES][EventManager_Model_Registration::FLD_PARTICIPANT]
                [Tinebase_Record_Expander::EXPANDER_PROPERTIES][$field] = [];
            $jsonExpander[Tinebase_Record_Expander::EXPANDER_PROPERTIES][EventManager_Model_Event::FLD_REGISTRATIONS]
            [Tinebase_Record_Expander::EXPANDER_PROPERTIES][EventManager_Model_Registration::FLD_REGISTRANT]
            [Tinebase_Record_Expander::EXPANDER_PROPERTIES][$field] = [];
        }

        $modelConfiguration->setJsonExpander($jsonExpander);

        parent::_resolveBeforeToArray($records, $modelConfiguration, $multiple);

        $this->_recursiveResolvingProtection = [];
        $this->_resolveRecursive($records, $modelConfiguration, $multiple);
    }

    /**
     * adds image property with image url like this:
     * 'index.php?method=Tinebase.getImage&application=Tinebase&location=vfs&id=e4b7de34e229672c0d5e22be0912779441e6e051'
     * @param $records
     */
    public function resolveAttachmentImage($records)
    {
        parent::resolveAttachmentImage($records);
        foreach ($records as $record) {
            $images = $record->{EventManager_Model_Event::FLD_IMAGES};
            if ($images) {
                if ($images->count() > 0) {
                    foreach ($record->attachments as $attachment) {
                        if (in_array($attachment->contenttype, Tinebase_ImageHelper::getSupportedImageMimeTypes())) {
                            $imageMetadata = $record[EventManager_Model_Event::FLD_IMAGES]->find(
                                EventManager_Model_ImageMetadata::FLD_NODE_ID,
                                $attachment->getId()
                            );
                            if ($imageMetadata && $imageMetadata[EventManager_Model_ImageMetadata::FLD_CONSENT] == 1) {
                                $imageMetadata->image_vfs = EventManager_Controller_ImageMetadata::getImageUrl(
                                    EventManager_Config::APP_NAME,
                                    $attachment->getId(),
                                    -1,
                                    -1
                                );
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * resolves child records after converting the record set to an array
     *
     * @param array $result
     * @param Tinebase_ModelConfiguration $modelConfiguration
     * @param boolean $multiple
     *
     * @return array
     */
    protected function _resolveAfterToArray($result, $modelConfiguration, $multiple = false)
    {
        $result = parent::_resolveAfterToArray($result, $modelConfiguration, $multiple);
        if ($multiple) {
            foreach ($result as $record) {
                $images = $record[EventManager_Model_Event::FLD_IMAGES];
                $updatedImages = [];
                if ($images) {
                    if (count($images) > 0) {
                        foreach ($record->attachments as $attachment) {
                            if (in_array($attachment['contenttype'], Tinebase_ImageHelper::getSupportedImageMimeTypes())) {
                                $imageMetadata = null;
                                foreach ($images as $image) {
                                    if ($image[EventManager_Model_ImageMetadata::FLD_NODE_ID] == $attachment['id']) {
                                        $imageMetadata = $image;
                                        break;
                                    }
                                }
                                if (
                                    $imageMetadata
                                    && $imageMetadata[EventManager_Model_ImageMetadata::FLD_CONSENT] == 1
                                ) {
                                    $imageMetadata['image_vfs'] = EventManager_Controller_ImageMetadata::getImageUrl(
                                        EventManager_Config::APP_NAME,
                                        $attachment['id'],
                                        -1,
                                        -1
                                    );
                                    $updatedImages[] = $imageMetadata;
                                }
                            }
                        }
                    }
                }
                $record[EventManager_Model_Event::FLD_IMAGES] = $updatedImages;
            }
        } else {
            $images = $result[EventManager_Model_Event::FLD_IMAGES];
            $updatedImages = [];
            if ($images) {
                if (count($images) > 0) {
                    foreach ($result['attachments'] as $attachment) {
                        if (in_array($attachment['contenttype'], Tinebase_ImageHelper::getSupportedImageMimeTypes())) {
                            $imageMetadata = null;
                            foreach ($images as $image) {
                                if ($image[EventManager_Model_ImageMetadata::FLD_NODE_ID] == $attachment['id']) {
                                    $imageMetadata = $image;
                                    break;
                                }
                            }
                            if ($imageMetadata && $imageMetadata[EventManager_Model_ImageMetadata::FLD_CONSENT] == 1) {
                                $imageMetadata['image_vfs'] = EventManager_Controller_ImageMetadata::getImageUrl(
                                    EventManager_Config::APP_NAME,
                                    $attachment['id'],
                                    -1,
                                    -1
                                );
                                $updatedImages[] = $imageMetadata;
                            }
                        }
                    }
                }
            }
            $result[EventManager_Model_Event::FLD_IMAGES] = $updatedImages;
        }
        return $result;
    }
}
