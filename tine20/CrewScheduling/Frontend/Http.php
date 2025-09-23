<?php
/**
 * backend class for Tinebase_Http_Server
 *
 * @package     CrewScheduling
 * @subpackage  Server
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2017-2017 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * backend class for Tinebase_Http_Server
 *
 * This class handles all Http requests for the crewscheduling application
 *
 * @package     CrewScheduling
 * @subpackage  Server
 */
class CrewScheduling_Frontend_Http extends Tinebase_Frontend_Http_Abstract
{
    protected $_applicationName = 'CrewScheduling';

    /**
     * export events
     *
     * @param string $filter JSON encoded string with items ids for multi export or item filter
     * @param string $options format or export definition id
     */
    public function exportEvents($filter, $options)
    {
        $decodedFilter = empty($filter) ? null : Zend_Json::decode($filter);
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Export filter: ' . print_r($decodedFilter, TRUE));

        if (! is_array($decodedFilter)) {
            $decodedFilter = array(array('field' => 'id', 'operator' => 'equals', 'value' => $decodedFilter));
        }

        $filter = new Calendar_Model_EventFilter();
        $filter->setFromArrayInUsersTimezone($decodedFilter);

        Tinebase_Export::doPdfLegacyHandling(false);
        
        parent::_export($filter, Zend_Json::decode($options), Calendar_Controller_Event::getInstance());
    }
}
