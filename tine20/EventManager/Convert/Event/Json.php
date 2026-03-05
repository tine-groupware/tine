<?php
/**
 * convert functions for records from/to json (array) format
 * 
 * @package     EventManager
 * @subpackage  Convert
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * @copyright   Copyright (c) 2012-2019 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * convert functions for records from/to json (array) format
 *
 * @package     EventManager
 * @subpackage  Convert
 */
class EventManager_Convert_Event_Json extends Tinebase_Convert_Json
{
    protected function _resolveBeforeToArray($records, $modelConfiguration, $multiple = false)
    {
        parent::_resolveBeforeToArray($records, $modelConfiguration, $multiple);

        $contactFields = [
            EventManager_Model_Registration::FLD_PARTICIPANT,
            EventManager_Model_Registration::FLD_REGISTRANT,
        ];

        foreach ($records as $event) {
            foreach ($event['registrations'] as $registration) {
                foreach ($contactFields as $contact) {
                    if (!empty($registration[$contact])) {
                        $registration[$contact]['container_id'] =
                            Tinebase_Container::getInstance()->getContainerById(
                                $registration[$contact]['container_id']
                            )->toArray();
                    }
                }
            }
        }
    }
}
