<?php
/**
 * Tine 2.0
 * @package     Timetracker
 * @subpackage  Frontend
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 *
 * This class handles all Json requests for the Timetracker application
 *
 * @package     Timetracker
 * @subpackage  Frontend
 */
class Timetracker_Frontend_Json extends Tinebase_Frontend_Json_Abstract
{
    /**
     * @see Tinebase_Frontend_Json_Abstract
     */
    protected $_relatableModels = array('Timetracker_Model_Timeaccount');

    /**
     * default model (needed for application starter -> defaultContentType)
     * @var string
     */
    protected $_defaultModel = 'Timesheet';

    /**
     * All configured models
     * @var array
     */
	protected $_configuredModels = array(
        Timetracker_Model_Timesheet::MODEL_NAME_PART,
        Timetracker_Model_Timeaccount::MODEL_NAME_PART,
        Timetracker_Model_TimeaccountGrants::MODEL_NAME_PART,
    );

    /**
     * the constructor
     *
     */
    public function __construct()
    {
        $this->_applicationName = 'Timetracker';
    }

    /************************************** protected helper functions **************************************/

    /**
     * calculate effective ts grants so the client doesn't need to calculate them
     *
     * @param  array  $TimeaccountGrantsArray
     * @param  int    $timesheetOwnerId
     * @return array
     */
    protected function _resolveTimesheetGrantsByTimeaccountGrants($timeaccountGrantsArray, $timesheetOwnerId)
    {
        $manageAllRight = Timetracker_Controller_Timeaccount::getInstance()->checkRight(Timetracker_Acl_Rights::MANAGE_TIMEACCOUNTS, FALSE);
        $currentUserId = Tinebase_Core::getUser()->getId();

        $modifyGrant = $manageAllRight || ($timeaccountGrantsArray[Timetracker_Model_TimeaccountGrants::BOOK_OWN]
            && $timesheetOwnerId == $currentUserId) || $timeaccountGrantsArray[Timetracker_Model_TimeaccountGrants::BOOK_ALL];

        $timeaccountGrantsArray[Tinebase_Model_Grants::GRANT_READ]   = true;
        $timeaccountGrantsArray[Tinebase_Model_Grants::GRANT_EDIT]   = $modifyGrant;
        $timeaccountGrantsArray[Tinebase_Model_Grants::GRANT_DELETE] = $modifyGrant;

        return $timeaccountGrantsArray;
    }

    /**
     * Return registry data for timeaccount favorites
     *
     * @return array
     * @throws \Tinebase_Exception_InvalidArgument
     */
    public function getTimeAccountFavoriteRegistry()
    {
        $appPrefs = Tinebase_Core::getPreference($this->_applicationName);

        // Get preference
        $quickTagPreferences = $appPrefs->search(
            new Tinebase_Model_PreferenceFilter([
                'name' => Timetracker_Preference::QUICKTAG
            ])
        );

        // There could be only one result, if not do nothing.
        if ($quickTagPreferences->count() !== 1) {
            return null;
        }

        $quickTagPreference = $quickTagPreferences->getFirstRecord();

        if ($quickTagPreference->value === false) {
            return null;
        }

        // Resolve tag by it's id
        $tag = Tinebase_Tags::getInstance()->get($quickTagPreference->value);

        $pref = array();
        $pref['quicktagId'] = $quickTagPreference->value;
        $pref['quicktagName'] = $tag->name;

        return $pref;
    }

    /**
     * Return registry data
     *
     * @return array
     * @throws \Tinebase_Exception_InvalidArgument
     */
    public function getRegistryData()
    {
        $registry = [];

        if (Timetracker_Config::getInstance()->featureEnabled(Timetracker_Config::FEATURE_TIMEACCOUNT_BOOKMARK)) {
            $registry = array_merge($registry, $this->getOwnTimeAccountBookmarks());
        }

        $timeaccountFavorites = $this->getTimeAccountFavoriteRegistry();

        if ($timeaccountFavorites !== null) {
            $registry = array_merge($registry, $this->getTimeAccountFavoriteRegistry());
        }

        return $registry;
    }

    /**
     * @return array
     */
    protected function getOwnTimeAccountBookmarks()
    {
        $ownFavoritesFilter = new Timetracker_Model_TimeaccountFavoriteFilter([
            'account_id' => Tinebase_Core::getUser()->accountId,
        ]);

        $timeAccountFavs = Timetracker_Controller_TimeaccountFavorites::getInstance()->search($ownFavoritesFilter);
        $timeAccountFavsArray = [];

        foreach($timeAccountFavs as $timeAccountFav) {
            $timeaccount = Timetracker_Controller_Timeaccount::getInstance()->get($timeAccountFav->timeaccount_id);

            // timeaccount will be used to set the defaults for opening new timesheet record in frontend
            // Resolve here to save loading time
            $timeAccountFavsArray[] = [
                'timeaccount' => $timeaccount->toArray(),
                'favId' => $timeAccountFav->id,
                'text' => $timeaccount->title,
                'leaf' => true,
                'iconCls' => 'task'
            ];
        }

        $pref = array();
        $pref['timeaccountFavorites'] = $timeAccountFavsArray;

        return $pref;
    }

    /************************************** public API **************************************/

    /**
     * Search for records matching given arguments
     *
     * @param  array $filter
     * @param  array $paging
     * @return array
     */
    public function searchTimesheets($filter, $paging)
    {
        return $this->_search($filter, $paging, Timetracker_Controller_Timesheet::getInstance(), 'Timetracker_Model_TimesheetFilter', true);
    }

    /**
     * do search count request only when resultset is equal
     * to $pagination->limit or we are not on the first page
     *
     * @param $filter
     * @param $pagination
     * @param Tinebase_Controller_SearchInterface $controller the record controller
     * @param $totalCountMethod
     * @param integer $resultCount
     * @return array
     */
    protected function _getSearchTotalCount($filter, $pagination, $controller, $totalCountMethod, $resultCount)
    {
        if ($controller instanceof Timetracker_Controller_Timesheet) {
            $result = $controller->searchCountSum($filter);

            $totalresult = [];

            // add totalcounts of leadstates/leadsources/leadtypes
            $totalresult['totalcountbillable'] = $result['sum_is_billable'];
            $totalresult['totalsum'] = $result['sum_duration'];
            $totalresult['totalsumbillable'] = $result['sum_accounting_time_billable'];
            $totalresult['totalcount'] = $result['totalcount'];
            $totalresult['clearedAmount'] = (int)$result['sum_cleared_amount'];
            $totalresult['recordedAmount'] = (int)$result['sum_recorded_amount'];
            if (isset($result['turnOverGoal'])) {
                $totalresult['turnOverGoal'] = $result['turnOverGoal'];
            }
            if (isset($result['workingTimeTarget'])) {
                $totalresult['workingTimeTarget'] = $result['workingTimeTarget'];
            }

            return $totalresult;
        } else {
            return parent:: _getSearchTotalCount($filter, $pagination, $controller, $totalCountMethod, $resultCount);
        }
    }
    /**
     * Return a single record
     *
     * @param   string $id
     * @return  array record data
     */
    public function getTimesheet($id)
    {
        return $this->_get($id, Timetracker_Controller_Timesheet::getInstance());
    }

    /**
     * creates/updates a record
     *
     * @param  array $recordData
     * @param  array $context
     * @return array created/updated record
     */
    public function saveTimesheet($recordData, array $context = array())
    {
        Timetracker_Controller_Timesheet::getInstance()->setRequestContext($context);
        return $this->_save($recordData, Timetracker_Controller_Timesheet::getInstance(), 'Timesheet');
    }

    /**
     * deletes existing records
     *
     * @param  array $ids
     * @return string
     */
    public function deleteTimesheets($ids)
    {
        return $this->_delete($ids, Timetracker_Controller_Timesheet::getInstance());
    }

    /**
     * Search for records matching given arguments
     *
     * @param  array $filter
     * @param  array $paging
     * @return array
     */
    public function searchTimeaccounts($filter, $paging)
    {
        return $this->_search($filter, $paging, Timetracker_Controller_Timeaccount::getInstance(), 'Timetracker_Model_TimeaccountFilter', true);
    }

    /**
     * Return a single record
     *
     * @param   string $id
     * @return  array record data
     */
    public function getTimeaccount($id)
    {
        return $this->_get($id, Timetracker_Controller_Timeaccount::getInstance());
    }

    /**
     * creates/updates a record
     *
     * @param  array $recordData
     * @return array created/updated record
     */
    public function saveTimeaccount($recordData)
    {
        return $this->_save($recordData, Timetracker_Controller_Timeaccount::getInstance(), 'Timeaccount');
    }

    /**
     * deletes existing records
     *
     * @param  array  $ids
     * @return string
     */
    public function deleteTimeaccounts($ids)
    {
        return $this->_delete($ids, Timetracker_Controller_Timeaccount::getInstance());
    }

    /**
     * Add given timeaccount id as a users favorite
     *
     * @param $timeaccountId
     * @return Timetracker_Model_Timeaccount
     */
    public function addTimeAccountFavorite($timeaccountId)
    {
        $timeaccount = new Timetracker_Model_TimeaccountFavorite();
        $timeaccount->timeaccount_id = $timeaccountId;
        $timeaccount->account_id = Tinebase_Core::getUser()->accountId;

        Timetracker_Controller_TimeaccountFavorites::getInstance()->create($timeaccount);

        return $this->getOwnTimeAccountBookmarks();
    }

    /**
     * Delete given timeaccount favorite
     *
     * @param $favId
     * @return Tinebase_Record_RecordSet
     * @throws \Tinebase_Exception
     */
    public function deleteTimeAccountFavorite($favId)
    {
        Timetracker_Controller_TimeaccountFavorites::getInstance()->delete([
            $favId
        ]);

        return $this->getOwnTimeAccountBookmarks();
    }
}
