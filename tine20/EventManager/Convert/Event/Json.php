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
}
