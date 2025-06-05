<?php
/**
 * Tine 2.0
 *
 * @package     Timetracker
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Ching En Cheng <c.cheng@metaways.de>
 * @copyright   Copyright (c) 2025 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

use Tinebase_Model_Filter_Abstract as TMFA;

/**
 * class for Timetracker uninitialization
 *
 * @package     Timetracker
 */
class Timetracker_Setup_Uninitialize extends Setup_Uninitialize
{

    protected function _uninitializePersistentObserver()
    {
        Tinebase_Record_PersistentObserver::getInstance()->removeObserverByIdentifier('calculateBudgetUpdate');
        Tinebase_Record_PersistentObserver::getInstance()->removeObserverByIdentifier('calculateBudgetDelete');
    }
}
