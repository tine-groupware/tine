<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * this test class mainly tests the timeaccount grants and the controller functions
 * 
 * @package     Timetracker
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2008-2014 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * 
 * @todo        add test for manage_billable
 */

/**
 * Test class for Timetracker_ControllerTest
 */
class Timetracker_ControllerTest extends TestCase
{
    /**
     * @var Timetracker_Controller_Timeaccount
     */
    protected $_timeaccountController = array();
    
    /**
     * @var Timetracker_Controller_Timesheet
     */
    protected $_timesheetController = array();
    
    /**
     * role rights
     *
     * @var array
     */
    protected $_roleRights = array();

    /**
     * objects
     *
     * @var array
     */
    protected $_objects = array();

    protected $_deleteTimeAccounts = array();
    protected $_deleteTimeSheets = array();
    
    /**
     * Sets up the fixture.
     * This method is called before a test is executed.
     *
     * @access protected
     */
    protected function setUp(): void
{
        parent::setUp();

        Timetracker_Controller_Timesheet::unsetInstance();
        
        $this->_timeaccountController = Timetracker_Controller_Timeaccount::getInstance();
        $this->_timesheetController = Timetracker_Controller_Timesheet::getInstance();

        // get timesheet
        $this->_objects['timesheet'] = $this->_getTimesheet();
        $this->_objects['timeaccount'] = $this->_timeaccountController->get($this->_objects['timesheet']->timeaccount_id);
        
        Tinebase_Acl_Roles::getInstance()->resetClassCache();
        
        $this->_roleRights = self::removeManageAllRight();

        Tinebase_Acl_Roles::getInstance()->resetClassCache();

        $this->_deleteTimeAccounts = array();
        $this->_deleteTimeSheets = array();
    }
    
    /**
     * remove MANAGE_ALL & ADMIN for Timetracker right
     * 
     * @return array
     */
    public static function removeManageAllRight()
    {
        $role = Tinebase_Acl_Roles::getInstance()->getRoleByName('admin role');
        $app = Tinebase_Application::getInstance()->getApplicationByName('Timetracker');
        
        $currentRights = Tinebase_Acl_Roles::getInstance()->getRoleRights($role->getId());
        $rightsWithoutManageAll = array();
        foreach ($currentRights as $right) {
            if ($right['right'] != Timetracker_Acl_Rights::MANAGE_TIMEACCOUNTS
            && !($right['application_id'] == $app->getId() && $right['right'] == 'admin')
            ) {
                $rightsWithoutManageAll[] = $right;
            }
        }
        Tinebase_Acl_Roles::getInstance()->setRoleRights($role->getId(), $rightsWithoutManageAll);
        return $currentRights;
    }

    /**
     * Tears down the fixture
     * This method is called after a test is executed.
     *
     * @access protected
     */
    protected function tearDown(): void
{
        parent::tearDown();
        
        Tinebase_Acl_Roles::getInstance()->resetClassCache();

        if (count($this->_deleteTimeSheets) > 0 || count($this->_deleteTimeAccounts) > 0) {
            $role = Tinebase_Acl_Roles::getInstance()->getRoleByName('admin role');
            Tinebase_Acl_Roles::getInstance()->setRoleRights($role->getId(), $this->_roleRights);
            Tinebase_Acl_Roles::getInstance()->resetClassCache();

       /*     try {
                $grants = new Tinebase_Record_RecordSet('Timetracker_Model_TimeaccountGrants', array(array(
                    'account_id' => Tinebase_Core::getUser()->getId(),
                    'account_type' => 'user',
                    Tinebase_Model_Grants::GRANT_ADMIN => TRUE,
                )));
                Timetracker_Controller_Timeaccount::getInstance()->setGrants(
                    $this->_objects['timeaccount'],
                    $grants,
                    TRUE
                );
            } catch (Exception $e) {
            }*/
        }

        if (count($this->_deleteTimeSheets) > 0) {
            try {
                Timetracker_Controller_Timesheet::getInstance()->delete($this->_deleteTimeSheets);
            } catch (Exception $e) {}
        }

        if (count($this->_deleteTimeAccounts) > 0) {
            try {
                Timetracker_Controller_Timeaccount::getInstance()->delete($this->_deleteTimeAccounts);
            } catch (Exception $e) {}
        }
    }
    
    /************ test functions follow **************/

    public function testNoGrantsValidatorDefaultValue()
    {
        Tinebase_Core::setUser($this->_personas['jmcblack']);

        $grants = new Tinebase_Record_RecordSet('Timetracker_Model_TimeaccountGrants', array(array(
            'account_id'    => Tinebase_Core::getUser()->getId(),
            'account_type'  => 'user',
            Timetracker_Model_TimeaccountGrants::VIEW_ALL      => TRUE,
        )));
        
        $this->assertEquals($grants->{Timetracker_Model_TimeaccountGrants::VIEW_ALL}, array(TRUE));
        $this->assertEquals($grants->{Timetracker_Model_TimeaccountGrants::BOOK_OWN}, array(FALSE));
        $this->assertEquals($grants->{Timetracker_Model_TimeaccountGrants::BOOK_ALL}, array(FALSE));
    }

    public function testNoGrantsTS()
    {
        Tinebase_Core::setUser($this->_personas['jmcblack']);

        $grants = new Tinebase_Record_RecordSet('Timetracker_Model_TimeaccountGrants', array(array(
            'account_id'    => Tinebase_Core::getUser()->getId(),
            'account_type'  => 'user',
            Timetracker_Model_TimeaccountGrants::VIEW_ALL        => TRUE,
            Timetracker_Model_TimeaccountGrants::MANAGE_BILLABLE => TRUE
        )));
        
        $this->_grantTestHelper($grants, 'create', 'Exception');
    }
    
    /**
     * test to create TS with book_own grant
     *
     */
    public function testBookOwnGrantTS()
    {
        Tinebase_Core::setUser($this->_personas['jmcblack']);

        $this->_objects['timesheet']['account_id'] = $this->_personas['jmcblack']->getId();
        $grants = new Tinebase_Record_RecordSet('Timetracker_Model_TimeaccountGrants', array(array(
            'account_id'    => Tinebase_Core::getUser()->getId(),
            'account_type'  => 'user',
            Timetracker_Model_TimeaccountGrants::BOOK_OWN      => TRUE,
        )));
        
        $this->_grantTestHelper($grants);
    }
    
    /**
     * test to create TS with book_all grant
     *
     */
    public function testBookAllGrantTS()
    {
        Tinebase_Core::setUser($this->_personas['jmcblack']);

        $grants = new Tinebase_Record_RecordSet('Timetracker_Model_TimeaccountGrants', array(array(
            'account_id'    => Tinebase_Core::getUser()->getId(),
            'account_type'  => 'user',
            Timetracker_Model_TimeaccountGrants::BOOK_ALL      => TRUE,
        )));
        
        $this->_grantTestHelper($grants);
    }
    
    /**
     * test to create not billable TS with manage_billable grant
     *
     */
    public function testManageClearingGrantTS()
    {
        Tinebase_Core::setUser($this->_personas['jmcblack']);

        $grants = new Tinebase_Record_RecordSet('Timetracker_Model_TimeaccountGrants', array(array(
            'account_id'      => Tinebase_Core::getUser()->getId(),
            'account_type'    => 'user',
            //Tinebase_Model_Grants::GRANT_ADMIN    => TRUE,
            Timetracker_Model_TimeaccountGrants::BOOK_ALL        => TRUE,
            Timetracker_Model_TimeaccountGrants::MANAGE_BILLABLE => TRUE,
        )));
        
        $ts = clone $this->_objects['timesheet'];
        $ts->is_billable = 0;
        
        $this->_grantTestHelper($grants, 'create', NULL, $ts);
    }

    /**
     * test to create not billable TS without manage_billable grant
     *
     */
    public function testManageClearingGrantTSNotSet()
    {
        Tinebase_Core::setUser($this->_personas['jmcblack']);

        $grants = new Tinebase_Record_RecordSet('Timetracker_Model_TimeaccountGrants', array(array(
            'account_id'      => Tinebase_Core::getUser()->getId(),
            'account_type'    => 'user',
            //Tinebase_Model_Grants::GRANT_ADMIN    => TRUE,
            Timetracker_Model_TimeaccountGrants::BOOK_ALL        => TRUE,
            Timetracker_Model_TimeaccountGrants::MANAGE_BILLABLE => FALSE,
        )));
        
        $ts = clone $this->_objects['timesheet'];
        $ts->is_billable = 0;
        
        $this->_grantTestHelper($grants, 'create', 'Exception', $ts);
    }
    
    /**
     * test to create TS with manage_all grant
     *
     */
    public function testManageAllGrantTS()
    {
        Tinebase_Core::setUser($this->_personas['jmcblack']);

        $grants = new Tinebase_Record_RecordSet('Timetracker_Model_TimeaccountGrants', array(array(
            'account_id'    => Tinebase_Core::getUser()->getId(),
            'account_type'  => 'user',
            Tinebase_Model_Grants::GRANT_ADMIN    => TRUE,
        )));
        
        $this->_grantTestHelper($grants);
    }
    
    /**
     * test to search TAs
     */
    public function testSearchTA()
    {
        Tinebase_Core::setUser($this->_personas['jmcblack']);

        foreach ([
                     Timetracker_Model_TimeaccountGrants::BOOK_ALL,
                     Timetracker_Model_TimeaccountGrants::BOOK_OWN,
                     Timetracker_Model_TimeaccountGrants::MANAGE_BILLABLE,
                     Timetracker_Model_TimeaccountGrants::READ_OWN,
                     Timetracker_Model_TimeaccountGrants::REQUEST_OWN,
                     Timetracker_Model_TimeaccountGrants::VIEW_ALL,
                     Tinebase_Model_Grants::GRANT_ADMIN,
                 ] as $grant) {
            $grants = new Tinebase_Record_RecordSet('Timetracker_Model_TimeaccountGrants', array(array(
                'account_id' => Tinebase_Core::getUser()->getId(),
                'account_type' => 'user',
                $grant => TRUE,
            )));

            $this->_grantTestHelper($grants, 'searchTA', 1);
        }
    }

    /**
     * test to search TAs (no grants)
     *
     */
    public function testSearchTAWithNoRights()
    {
        Tinebase_Core::setUser($this->_personas['jmcblack']);

        $grants = new Tinebase_Record_RecordSet('Timetracker_Model_TimeaccountGrants', array(array(
            'account_id'    => Tinebase_Core::getUser()->getId(),
            'account_type'  => 'user',
        )));
        
        $this->_grantTestHelper($grants, 'searchTA', 0);
    }

    /**
     * test to search TSs (view_all)
     */
    public function testSearchTS()
    {
        // activate fulltext query filter
        Tinebase_Config::getInstance()->{Tinebase_Config::FULLTEXT}
            ->{Tinebase_Config::FULLTEXT_QUERY_FILTER} = true;
        
        Tinebase_Core::setUser($this->_personas['jmcblack']);

        $this->_objects['timesheet']['account_id'] = $this->_personas['jmcblack']->getId();
        $grants = new Tinebase_Record_RecordSet('Timetracker_Model_TimeaccountGrants', array(array(
            'account_id'    => Tinebase_Core::getUser()->getId(),
            'account_type'  => 'user',
            Timetracker_Model_TimeaccountGrants::BOOK_OWN      => TRUE,
            Timetracker_Model_TimeaccountGrants::VIEW_ALL      => TRUE,
        )));
        
        $this->_grantTestHelper($grants, 'searchTS', 1);

        $filter = $this->_getTimesheetFilter();

        $be = new Timetracker_Backend_Timesheet();
        $result = $be->search($filter)->toArray();
        $this->assertArrayHasKey('is_billable_combined', $result[0]);

        $result = $this->_timesheetController->search($filter)->toArray();
        $this->assertArrayHasKey('is_billable_combined', $result[0]);

        // reset fulltext query filter
        Tinebase_Config::getInstance()->{Tinebase_Config::FULLTEXT}
            ->{Tinebase_Config::FULLTEXT_QUERY_FILTER} = false;
    }

    public function testSearchOwnTSNoGrant()
    {
        $this->_objects['timesheet']['account_id'] = $this->_personas['jmcblack']->getId();
        $ts = $this->_timesheetController->create($this->_objects['timesheet']);

        $this->_timeaccountController->setGrants(
            $this->_objects['timeaccount'],
            new Tinebase_Record_RecordSet('Timetracker_Model_TimeaccountGrants', array(array(
                'account_id'    => $this->_personas['jmcblack']->getId(),
                'account_type'  => 'user',
            ))),
            true
        );

        Tinebase_Core::setUser($this->_personas['jmcblack']);

        $result = $this->_timesheetController->search(Tinebase_Model_Filter_FilterGroup::getFilterForModel(
            Timetracker_Model_Timesheet::class, [
                ['field' => 'id', 'operator' => 'equals', 'value' => $ts->getId()],
        ]));
        $this->assertSame(0, $result->count());
    }

    public function testSearchOwnTS()
    {
        $this->_objects['timesheet']['account_id'] = $this->_personas['jmcblack']->getId();
        $ts = null;

        foreach ([
                     Timetracker_Model_TimeaccountGrants::BOOK_ALL,
                     Timetracker_Model_TimeaccountGrants::BOOK_OWN,
                     Timetracker_Model_TimeaccountGrants::MANAGE_BILLABLE,
                     Timetracker_Model_TimeaccountGrants::READ_OWN,
                     Timetracker_Model_TimeaccountGrants::REQUEST_OWN,
                     Timetracker_Model_TimeaccountGrants::VIEW_ALL,
                     Tinebase_Model_Grants::GRANT_ADMIN,
                 ] as $grant) {
            Tinebase_Core::setUser($this->_originalTestUser);
            $this->_timeaccountController->setGrants(
                $this->_objects['timeaccount'],
                new Tinebase_Record_RecordSet('Timetracker_Model_TimeaccountGrants', array(array(
                    'account_id' => $this->_originalTestUser->getId(),
                    'account_type' => 'user',
                    Tinebase_Model_Grants::GRANT_ADMIN => true,
                ))),
                true
            );
            if (null !== $ts) {
                $this->_timesheetController->delete($ts);
                $this->_objects['timesheet']->setId(null);
            }

            $ts = $this->_timesheetController->create($this->_objects['timesheet']);

            $this->_timeaccountController->setGrants(
                $this->_objects['timeaccount'],
                new Tinebase_Record_RecordSet('Timetracker_Model_TimeaccountGrants', array(array(
                    'account_id' => $this->_personas['jmcblack']->getId(),
                    'account_type' => 'user',
                    $grant => true,
                ))),
                true
            );

            Tinebase_Core::setUser($this->_personas['jmcblack']);

            $result = $this->_timesheetController->search(Tinebase_Model_Filter_FilterGroup::getFilterForModel(
                Timetracker_Model_Timesheet::class, [
                ['field' => 'id', 'operator' => 'equals', 'value' => $ts->getId()],
            ]));
            $this->assertSame(1, $result->count(), 'grant fail for: ' . $grant);
        }
    }

    public function testSearchOtherTSNoFind()
    {
        $this->_objects['timesheet']['account_id'] = $this->_personas['sclever']->getId();
        $ts = null;

        foreach ([
                     Timetracker_Model_TimeaccountGrants::BOOK_OWN,
                     Timetracker_Model_TimeaccountGrants::READ_OWN,
                     Timetracker_Model_TimeaccountGrants::REQUEST_OWN,
                 ] as $grant) {
            Tinebase_Core::setUser($this->_originalTestUser);
            $this->_timeaccountController->setGrants(
                $this->_objects['timeaccount'],
                new Tinebase_Record_RecordSet('Timetracker_Model_TimeaccountGrants', array(array(
                    'account_id' => $this->_originalTestUser->getId(),
                    'account_type' => 'user',
                    Tinebase_Model_Grants::GRANT_ADMIN => true,
                ))),
                true
            );
            if (null !== $ts) {
                $this->_timesheetController->delete($ts);
                $this->_objects['timesheet']->setId(null);
            }

            $ts = $this->_timesheetController->create($this->_objects['timesheet']);

            $this->_timeaccountController->setGrants(
                $this->_objects['timeaccount'],
                new Tinebase_Record_RecordSet('Timetracker_Model_TimeaccountGrants', array(array(
                    'account_id' => $this->_personas['jmcblack']->getId(),
                    'account_type' => 'user',
                    $grant => true,
                ))),
                true
            );

            Tinebase_Core::setUser($this->_personas['jmcblack']);

            $result = $this->_timesheetController->search(Tinebase_Model_Filter_FilterGroup::getFilterForModel(
                Timetracker_Model_Timesheet::class, [
                ['field' => 'id', 'operator' => 'equals', 'value' => $ts->getId()],
            ]));
            $this->assertSame(0, $result->count(), 'grant fail for: ' . $grant);
        }
    }

    public function testSearchOtherTSFind()
    {
        $this->_objects['timesheet']['account_id'] = $this->_personas['sclever']->getId();
        $ts = null;

        foreach ([
                     Timetracker_Model_TimeaccountGrants::BOOK_ALL,
                     Timetracker_Model_TimeaccountGrants::MANAGE_BILLABLE,
                     Timetracker_Model_TimeaccountGrants::VIEW_ALL,
                     Tinebase_Model_Grants::GRANT_ADMIN,
                 ] as $grant) {
            Tinebase_Core::setUser($this->_originalTestUser);
            $this->_timeaccountController->setGrants(
                $this->_objects['timeaccount'],
                new Tinebase_Record_RecordSet('Timetracker_Model_TimeaccountGrants', array(array(
                    'account_id' => $this->_originalTestUser->getId(),
                    'account_type' => 'user',
                    Tinebase_Model_Grants::GRANT_ADMIN => true,
                ))),
                true
            );
            if (null !== $ts) {
                $this->_timesheetController->delete($ts);
                $this->_objects['timesheet']->setId(null);
            }

            $ts = $this->_timesheetController->create($this->_objects['timesheet']);

            $this->_timeaccountController->setGrants(
                $this->_objects['timeaccount'],
                new Tinebase_Record_RecordSet('Timetracker_Model_TimeaccountGrants', array(array(
                    'account_id' => $this->_personas['jmcblack']->getId(),
                    'account_type' => 'user',
                    $grant => true,
                ))),
                true
            );

            Tinebase_Core::setUser($this->_personas['jmcblack']);

            $result = $this->_timesheetController->search(Tinebase_Model_Filter_FilterGroup::getFilterForModel(
                Timetracker_Model_Timesheet::class, [
                ['field' => 'id', 'operator' => 'equals', 'value' => $ts->getId()],
            ]));
            $this->assertSame(1, $result->count(), 'grant fail for: ' . $grant);
        }
    }

    /**
     * test to search TSs for export
     *
     */
    public function testSearchTSExport()
    {
        $ts = $this->_timesheetController->create($this->_objects['timesheet']);

        Tinebase_Core::setUser($this->_personas['jmcblack']);

        $grants = new Tinebase_Record_RecordSet('Timetracker_Model_TimeaccountGrants', array(array(
            'account_id'    => Tinebase_Core::getUser()->getId(),
            'account_type'  => 'user',
            Tinebase_Model_Grants::GRANT_EXPORT   => TRUE,
        )));
        
        $this->_grantTestHelper($grants, 'searchTSExport', 1, $ts);
    }

    public function testTsClearedAmountTest(): void
    {
        $price = 85.5;
        $this->_objects['timeaccount']->price = $price;

        Tinebase_Acl_Roles::getInstance()->resetClassCache();
        $role = Tinebase_Acl_Roles::getInstance()->getRoleByName('admin role');
        Tinebase_Acl_Roles::getInstance()->setRoleRights($role->getId(), $this->_roleRights);
        Tinebase_Acl_Roles::getInstance()->resetClassCache();

        Timetracker_Controller_Timeaccount::getInstance()->update($this->_objects['timeaccount']);

        $orgDelegator = Timetracker_Config::getInstance()->{Timetracker_Config::TS_CLEARED_AMOUNT_DELEGATOR};
        $raii = new Tinebase_RAII(fn() => Timetracker_Config::getInstance()->{Timetracker_Config::TS_CLEARED_AMOUNT_DELEGATOR} = $orgDelegator);
        Timetracker_Config::getInstance()->{Timetracker_Config::TS_CLEARED_AMOUNT_DELEGATOR} = [];

        $this->_objects['timesheet']->is_cleared = true;
        $ts = Timetracker_Controller_Timesheet::getInstance()->create($this->_objects['timesheet']);

        $this->assertSame(round(($ts->accounting_time / 60) * $price, 2), $ts->{Timetracker_Model_Timesheet::FLD_CLEARED_AMOUNT});

        $this->_objects['timesheet']->is_cleared = false;
        $ts = Timetracker_Controller_Timesheet::getInstance()->update($this->_objects['timesheet']);
        $this->assertNull($ts->{Timetracker_Model_Timesheet::FLD_CLEARED_AMOUNT});

        Timetracker_Config::getInstance()->{Timetracker_Config::TS_CLEARED_AMOUNT_DELEGATOR} = [self::class, 'tsDelegatorTestFunc'];
        $this->_objects['timesheet']->is_cleared = true;
        $ts = Timetracker_Controller_Timesheet::getInstance()->update($this->_objects['timesheet']);
        $this->assertSame(42.43, $ts->{Timetracker_Model_Timesheet::FLD_CLEARED_AMOUNT});

        unset($raii);
    }

    public static function tsDelegatorTestFunc(Timetracker_Model_Timesheet $record, Timetracker_Model_Timesheet $oldRecord): void
    {
        $record->{Timetracker_Model_Timesheet::FLD_CLEARED_AMOUNT} = 42.43;
    }

    /**
     * try to add a Timesheet exceeding deadline
     *
     */
    public function testAddTimesheetExceedingDeadline()
    {
        $this->markTestSkipped('random failures, maybe Sales Test have random transaction issues?');
        
        // TODO should work without invoices feature, too ...
        if (! Sales_Config::getInstance()->featureEnabled(Sales_Config::FEATURE_INVOICES_MODULE)) {
            $this->markTestSkipped('needs enabled invoices module');
        }

        $grants = new Tinebase_Record_RecordSet('Timetracker_Model_TimeaccountGrants', array(array(
            'account_id'    => Tinebase_Core::getUser()->getId(),
            'account_type'  => 'user',
            Timetracker_Model_TimeaccountGrants::BOOK_OWN      => TRUE,
        )));
        
        $this->_grantTestHelper($grants, 'create_deadline');
    }
    
    /************ protected helper funcs *************/
    
    /**
     * try to add a Timesheet with different grants
     * 
     * @param   Tinebase_Record_RecordSet $_grants
     * @param   string $_action
     * @param   mixed $_expect
     * @param   Timetracker_Model_Timesheet
     */
    protected function _grantTestHelper($_grants, $_action = 'create', $_expect = NULL, $_ts = NULL)
    {
        // take default ts?
        $ts = $_ts ? $_ts : $this->_objects['timesheet'];
        
        // remove BOOK_OWN + BOOK_ALL + ADMIN grant
        Timetracker_Controller_Timeaccount::getInstance()->setGrants(
            $this->_objects['timeaccount'],
            $_grants,
            TRUE
        );
        
        // try to create timesheet
        switch ($_action) {
            case 'create':
                if ($_expect === 'Exception') {
                    $this->expectException('Tinebase_Exception_AccessDenied');
                    $this->_timesheetController->create($ts);
                } else {
                    $ts = $this->_timesheetController->create($ts);
                    $this->assertEquals(Tinebase_Core::getUser()->getId(), $ts->created_by);
                }
                break;
            case 'create_deadline':
                // date is before deadline
                $date = new Tinebase_DateTime();
                $date->sub(8, Tinebase_DateTime::MODIFIER_DAY);
                $ts->start_date = $date->toString('Y-m-d');
                $this->expectException('Timetracker_Exception_Deadline');
                $this->_timesheetController->create($ts);
                break;
            case 'search_bookable':
                $this->_deleteTimeSheets[] = $this->_objects['timesheet']->getId();
                $this->_deleteTimeAccounts[] = $this->_objects['timeaccount']->getId();

                Tinebase_TransactionManager::getInstance()->commitTransaction($this->_transactionId);
                $this->_transactionId = Tinebase_TransactionManager::getInstance()->startTransaction(Tinebase_Core::getDb());

                $filter = $this->_getTimeaccountFilter(TRUE);
                $result = $this->_timeaccountController->search($filter);
                $this->assertEquals($_expect, count($result));
                break;
            case 'searchTA':

                $this->_deleteTimeSheets[] = $this->_objects['timesheet']->getId();
                $this->_deleteTimeAccounts[] = $this->_objects['timeaccount']->getId();

                Tinebase_TransactionManager::getInstance()->commitTransaction($this->_transactionId);
                $this->_transactionId = Tinebase_TransactionManager::getInstance()->startTransaction(Tinebase_Core::getDb());

                $filter = $this->_getTimeaccountFilter();
                $result = $this->_timeaccountController->search($filter);
                $this->assertEquals($_expect, count($result));
                break;
            case 'searchTS':
                $ts = $this->_timesheetController->create($ts);

                $this->_deleteTimeSheets[] = $this->_objects['timesheet']->getId();
                $this->_deleteTimeSheets[] = $ts->getId();
                $this->_deleteTimeAccounts[] = $this->_objects['timeaccount']->getId();

                Tinebase_TransactionManager::getInstance()->commitTransaction($this->_transactionId);
                $this->_transactionId = Tinebase_TransactionManager::getInstance()->startTransaction(Tinebase_Core::getDb());

                $filter = $this->_getTimesheetFilter();

                $result = $this->_timesheetController->search($filter);
                $this->assertEquals($_expect, count($result));

                $filter = new Timetracker_Model_TimesheetFilter(array(
                    array(
                        'field' => 'query',
                        'operator' => 'contains',
                        'value' => 'zzzzz'
                    ),
                ));
                $result = $this->_timesheetController->search($filter);
                $this->assertEquals(0, count($result));
                break;
            case 'searchTSExport':

                $this->_deleteTimeSheets[] = $this->_objects['timesheet']->getId();
                $this->_deleteTimeAccounts[] = $this->_objects['timeaccount']->getId();

                Tinebase_TransactionManager::getInstance()->commitTransaction($this->_transactionId);
                $this->_transactionId = Tinebase_TransactionManager::getInstance()->startTransaction(Tinebase_Core::getDb());

                $filter = $this->_getTimesheetFilter();
                $result = $this->_timesheetController->search($filter, NULL, FALSE, FALSE, 'export');
                $this->assertEquals($_expect, count($result));
                break;
            default:
                echo "nothing tested.";
        }

        // delete (set delete grant first)
        $grants = new Tinebase_Record_RecordSet('Timetracker_Model_TimeaccountGrants', array(array(
            'account_id'    => Tinebase_Core::getUser()->getId(),
            'account_type'  => 'user',
            Timetracker_Model_TimeaccountGrants::MANAGE_BILLABLE  => TRUE,
            Tinebase_Model_Grants::GRANT_ADMIN      => TRUE,
            Timetracker_Model_TimeaccountGrants::BOOK_ALL      => TRUE,
            Timetracker_Model_TimeaccountGrants::BOOK_OWN      => TRUE,
            Timetracker_Model_TimeaccountGrants::VIEW_ALL      => TRUE,
            Tinebase_Model_Grants::GRANT_EXPORT  => TRUE,
        )));
        Timetracker_Controller_Timeaccount::getInstance()->setGrants(
            $this->_objects['timeaccount'],
            $grants,
            TRUE
        );
    }
    
    /**
     * get Timesheet
     * @param array $data
     * @return Timetracker_Model_Timeaccount
     */
    protected function _getTimeaccount($data = array())
    {
        return new Timetracker_Model_Timeaccount(array_merge(array(
            'title'         => Tinebase_Record_Abstract::generateUID(),
            'description'   => 'blabla',
            'is_open'       => 1,
            'deadline'      => Timetracker_Model_Timeaccount::DEADLINE_LASTWEEK
        ),$data), TRUE);
    }
    
    /**
     * get Timesheet (create timeaccount as well)
     *
     * @return Timetracker_Model_Timesheet
     */
    protected function _getTimesheet()
    {
        $timeaccount = Timetracker_Controller_Timeaccount::getInstance()->create($this->_getTimeaccount());
        
        return new Timetracker_Model_Timesheet(array(
            'account_id'        => Tinebase_Core::getUser()->getId(),
            'timeaccount_id'    => $timeaccount->getId(),
            'description'       => 'blabla',
            'start_date'        => Tinebase_DateTime::now()->toString('Y-m-d'),
            'duration'          => 30,
        ), TRUE);
    }

    /**
     * get Timeaccount filter
     *
     * @param bool $bookable
     * @return Timetracker_Model_TimeaccountFilter
     */
    protected function _getTimeaccountFilter($bookable = FALSE)
    {
        $result = new Timetracker_Model_TimeaccountFilter(array(
            array(
                'field' => 'description', 
                'operator' => 'contains', 
                'value' => 'blabla'
            ),
        ));
        
        if ($bookable) {
            $result->isBookable = TRUE;
        }
        
        return ($result);
    }
    
    /**
     * get Timesheet filter
     *
     * @return Timetracker_Model_TimesheetFilter
     */
    protected function _getTimesheetFilter()
    {
        return new Timetracker_Model_TimesheetFilter(array(
            array(
                'field' => 'query', 
                'operator' => 'contains', 
                'value' => 'blabla'
            ),
        ));
    }
    
    /**
     * tests the findTimesheetsByTimeaccountAndPeriod method of the timesheet controller
     */
    public function testFindTimesheetsByTimeaccountAndPeriod()
    {
        $i=0;
        
        $now = Tinebase_DateTime::now();
        $data = array('account_id' => Tinebase_Core::getUser()->getId(), 'is_cleared' => true, 'timeaccount_id' => $this->_objects['timeaccount']->getId(), 'description' => 'test', 'duration' => 1, 'title' => 'test');
        
        while ($i<10) {
            $data['start_date'] = $now->addHour(1);
            $this->_timesheetController->create(new Timetracker_Model_Timesheet($data));
            $i++;
        }
        
        $sdate = Tinebase_DateTime::now();
        $sdate->subDay(1);
        $edate = $now->addDay(1);
        
        $result = $this->_timesheetController->findTimesheetsByTimeaccountAndPeriod($this->_objects['timeaccount']->getId(), $sdate, $edate);
        
        $this->assertEquals(10, count($result));
    }

    /**
     * Tests if a time account favorite can be created
     */
    public function testCreateTimeaccountFavorite()
    {
        $timeaccount = Timetracker_Controller_Timeaccount::getInstance()->create($this->_getTimeaccount());
        $user = Tinebase_Core::getUser();

        $timeaccountFavorite = new Timetracker_Model_TimeaccountFavorite([
            'account_id' => $user->accountId,
            'timeaccount_id' => $timeaccount->getId()
        ]);

        $timeaccountCreated = Timetracker_Controller_TimeaccountFavorites::getInstance()->create($timeaccountFavorite);

        static::assertEquals($timeaccountCreated->account_id, $user->getId());
        static::assertEquals($timeaccountCreated->timeaccount_id, $timeaccount->id);
    }
}
