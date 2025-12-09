<?php declare(strict_types=1);
/**
 * @package     Tinebase
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2025 Metaways Infosystems GmbH (http://www.metaways.de)
 */
class Tinebase_Controller_Group implements Tinebase_Controller_Record_Interface, Tinebase_Controller_SearchInterface
{
    use Tinebase_Controller_SingletonTrait;


    /**
     * @param int|string $_id
     * @return Tinebase_Model_Group
     * @throws Tinebase_Exception_Record_NotDefined
     */
    public function get($_id)
    {
        return Tinebase_Group::getInstance()->getGroupById($_id);
    }

    public function getMultiple($_ids, $_ignoreACL = false, ?Tinebase_Record_Expander $_expander = null, $_getDeleted = false)
    {
        return Tinebase_Group::getInstance()->getMultiple($_ids);
    }

    public function getAll($_orderBy = 'id', $_orderDirection = 'ASC')
    {
        throw new Tinebase_Exception_NotImplemented();
    }

    public function create(Tinebase_Record_Interface $_record)
    {
        throw new Tinebase_Exception_NotImplemented();
    }

    public function update(Tinebase_Record_Interface $_record)
    {
        throw new Tinebase_Exception_NotImplemented();
    }

    public function updateMultiple($_filter, $_data, $_pagination = null)
    {
        throw new Tinebase_Exception_NotImplemented();
    }

    public function delete($_ids)
    {
        throw new Tinebase_Exception_NotImplemented();
    }

    public function has(array $_ids, $_getDeleted = false)
    {
        throw new Tinebase_Exception_NotImplemented();
    }

    public function getModel()
    {
        throw new Tinebase_Exception_NotImplemented();
    }

    public function copy(string $id, bool $persist): Tinebase_Record_Interface
    {
        throw new Tinebase_Exception_NotImplemented();
    }

    public function search(?Tinebase_Model_Filter_FilterGroup $_filter = NULL, ?Tinebase_Model_Pagination $_pagination = NULL, $_getRelations = FALSE, $_onlyIds = FALSE, $_action = 'get')
    {
        throw new Tinebase_Exception_NotImplemented();
    }

    public function searchCount(Tinebase_Model_Filter_FilterGroup $_filter, $_action = 'get'): int
    {
        throw new Tinebase_Exception_NotImplemented();
    }

    public function searchCountSum(Tinebase_Model_Filter_FilterGroup $_filter,
                                   string $_action = Tinebase_Controller_Record_Abstract::ACTION_GET): array
    {
        throw new Tinebase_Exception_NotImplemented();
    }
}
