<?php declare(strict_types=1);

class Admin_Controller_EmailAccountAdmin extends Admin_Controller_EmailAccount
{
    use Tinebase_Controller_SingletonTrait;

    protected function __construct()
    {
        parent::__construct();
    }

    public function get($_id, $_EmailAccountId = NULL, $_getRelatedData = TRUE, $_getDeleted = FALSE, $_aclProtect = true)
    {
        return new Admin_Model_EmailAccountAdmin(
            parent::get($_id, $_EmailAccountId, $_getRelatedData, $_getDeleted, $_aclProtect)->toArray()
        );
    }

    public function search(Tinebase_Model_Filter_FilterGroup $_filter = NULL, Tinebase_Model_Pagination $_pagination = NULL, $_getRelations = FALSE, $_onlyIds = FALSE, $_action = 'get')
    {
        return new Tinebase_Record_RecordSet(Admin_Model_EmailAccountAdmin::class,
            parent::search($_filter, $_pagination, $_getRelations, $_onlyIds, $_action)->toArray()
        );
    }
}
