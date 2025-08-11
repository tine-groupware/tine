<?php declare(strict_types=1);
/**
 * @package     Tinebase
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2025 Metaways Infosystems GmbH (http://www.metaways.de)
 */
class Tinebase_Controller_Role implements Tinebase_Controller_Record_Interface, Tinebase_Controller_SearchInterface
{
    use Tinebase_Controller_SingletonTrait;


    /**
     * @param string|int $_id
     * @return Tinebase_Model_Role
     * @throws Tinebase_Exception_AccessDenied
     * @throws Tinebase_Exception_NotFound
     */
    public function get($_id)
    {
        return Tinebase_Role::getInstance()->get($_id);
    }

    public function getMultiple($_ids, $_ignoreACL = false, ?Tinebase_Record_Expander $_expander = null, $_getDeleted = false)
    {
        return Tinebase_Role::getInstance()->getMultiple($_ids, $_ignoreACL, $_expander, $_getDeleted);
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

    public function searchCount(Tinebase_Model_Filter_FilterGroup $_filter, $_action = 'get')
    {
        throw new Tinebase_Exception_NotImplemented();
    }
}
