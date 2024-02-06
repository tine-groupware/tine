<?php
/**
 * convert functions for records from/to json (array) format
 * 
 * @package     Addressbook
 * @subpackage  Convert
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * @copyright   Copyright (c) 2012-2019 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * convert functions for records from/to json (array) format
 *
 * @package     Addressbook
 * @subpackage  Convert
 */
class Addressbook_Convert_Contact_Json extends Tinebase_Convert_Json
{
    protected function _resolveBeforeToArray($records, $modelConfiguration, $multiple = false)
    {
        parent::_resolveBeforeToArray($records, $modelConfiguration, $multiple);

        Tinebase_Record_Expander::expandRecords($records);

        if($multiple !== true) {
            $contact = $records->getFirstRecord();
            if ($contact->groups instanceof Tinebase_Record_RecordSet) {
                $memberRoles = Addressbook_Controller_ListMemberRole::getInstance()->search(Tinebase_Model_Filter_FilterGroup::getFilterForModel(Addressbook_Model_ListMemberRole::class, [
                    ['field' => 'list_id', 'operator' => 'in', 'value' => $contact->groups->getId()],
                    ['field' => 'contact_id', 'operator' => 'equals', 'value' => $contact->getid()],
                ]));
                foreach ($contact->groups as $list) {
                    $list->memberroles = $memberRoles->filter('list_id', $list->getId());
                }
                \Addressbook_Convert_List_Json::resolveMemberroles($contact->groups);
            }
        }
    }

    /**
    * parent converts Tinebase_Record_RecordSet to external format
    * this resolves Image Paths
    *
    * @param Tinebase_Record_RecordSet  $_records
    * @param Tinebase_Model_Filter_FilterGroup $_filter
    * @param Tinebase_Model_Pagination $_pagination
    * @return mixed
    */
    public function fromTine20RecordSet(Tinebase_Record_RecordSet $_records = NULL, $_filter = NULL, $_pagination = NULL)
    {
        if (count($_records) == 0) {
            return array();
        }

        // TODO: Can be removed when "0000284: modlog of contact images / move images to vfs" is resolved.
        // TODO: https://github.com/tine20/tine20/issues/235
        Addressbook_Frontend_Json::resolveImages($_records);

        $this->_appendRecordPaths($_records, $_filter);

        // TODO container + account_grants of duplicate records need to be dehydrated, too
        // @see \Addressbook_JsonTest::testDuplicateCheck
        Tinebase_Record_Expander::expandRecords($_records);

        $dehydrator = Tinebase_Record_Hydration_Factory::createDehydrator(Tinebase_Record_Hydration_Factory::TYPE_ARRAY,
            Addressbook_Model_Contact::class, [
                Tinebase_Record_Dehydrator_Strategy::DEF_FLAT               => true,
                Tinebase_Record_Dehydrator_Strategy::DEF_SUB_DEFINITIONS    => [
                    'paths'                                                     => [
                        Tinebase_Record_Dehydrator_Strategy::DEF_FLAT               => true,
                    ],
                    'container_id'                                              => [
                        Tinebase_Record_Dehydrator_Strategy::DEF_FLAT               => true,
                    ],
                    'tags'                                                      => [
                        Tinebase_Record_Dehydrator_Strategy::DEF_FLAT               => true,
                    ],
                    'attachments'                                               => [
                        Tinebase_Record_Dehydrator_Strategy::DEF_FLAT               => true,
                    ],
                    'created_by'                                                => [
                        Tinebase_Record_Dehydrator_Strategy::DEF_FLAT               => true,
                    ],
                    'last_modified_by'                                          => [
                        Tinebase_Record_Dehydrator_Strategy::DEF_FLAT               => true,
                    ],
                ]
            ]);

        foreach ($_records as $record) {
            if ($record['GDPR_DataIntendedPurposeRecord']) {
                $record['GDPR_DataIntendedPurposeRecord'] = $record['GDPR_DataIntendedPurposeRecord']->toArray();
            }
        }
        
        return $dehydrator->dehydrate($_records);
    }

    /**
     * append record paths (if path filter is set)
     *
     * @param Tinebase_Record_RecordSet $_records
     * @param Tinebase_Model_Filter_FilterGroup $_filter
     *
     * @deprecated
     * TODO move to expander
     */
    protected function _appendRecordPaths($_records, $_filter)
    {
//        if ($_filter && $_filter->getFilter('path', /* $_getAll = */ false, /* $_recursive = */ true) !== null &&
//                true === Tinebase_Config::getInstance()->featureEnabled(Tinebase_Config::FEATURE_SEARCH_PATH)) {
        if (true === Tinebase_Config::getInstance()->featureEnabled(Tinebase_Config::FEATURE_SEARCH_PATH)) {
            $pathController = Tinebase_Record_Path::getInstance();
            foreach ($_records as $record) {
                $record->paths = $pathController->getPathsForRecord($record);
                $pathController->cutTailAfterRecord($record, $record->paths);
            }
        }
    }
}
