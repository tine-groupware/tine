<?php
/**
 * convert functions for records from/to json (array) format
 *
 * @package     Tinebase
 * @subpackage  Convert
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Ching En Cheng <c.cheng@metaways.de>
 * @copyright   Copyright (c) 2024 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * convert functions for records from/to json (array) format
 *
 * @package     Tinebase
 * @subpackage  Convert
 */
class Tinebase_Convert_Tree_FlySystem_Json extends Tinebase_Convert_Json
{
    /**
     * converts Tinebase_Record_Interface to external format
     *
     * @param  Tinebase_Record_Interface $_record
     * @return mixed
     */
    public function fromTine20Model(Tinebase_Record_Interface $_record)
    {
        $result = parent::fromTine20Model($_record);
        if ($result[Tinebase_Model_Tree_FlySystem::FLD_MOUNT_POINT]) {
            $fmNode = Filemanager_Controller_Node::getInstance()->get($result[Tinebase_Model_Tree_FlySystem::FLD_MOUNT_POINT]['id']);
            $result[Tinebase_Model_Tree_FlySystem::FLD_MOUNT_POINT] = $fmNode->toArray();
        }
        return $result;
    }

    /**
     * @param Tinebase_Record_RecordSet|NULL $_records
     * @param Tinebase_Model_Filter_FilterGroup $_filter
     * @param Tinebase_Model_Pagination $_pagination
     * @return array|array[]|mixed
     * @throws Tinebase_Exception_InvalidArgument
     */
    public function fromTine20RecordSet(?\Tinebase_Record_RecordSet $_records = null, $_filter = null, $_pagination = null)
    {
        foreach ($_records as $record) {
            if ($treeNode = Tinebase_Controller_Tree_FlySystem::getInstance()->getFlySystemMountPoint($record)) {
                $fmNode = Filemanager_Controller_Node::getInstance()->get($treeNode->getId());
                $record->{Tinebase_Model_Tree_FlySystem::FLD_MOUNT_POINT} = $fmNode;
            }
        }
        return parent::fromTine20RecordSet($_records, $_filter, $_pagination);
    }
}
