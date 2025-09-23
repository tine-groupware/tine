<?php
/**
 * Tine 2.0
 *
 * @package     CrewScheduling
 * @subpackage  Import
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Jan Evers <j.evers@metaways.de>
 * @copyright   Copyright (c) 2025 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

use Tinebase_Core;
use Tinebase_DateTime;

/**
 * @package CrewScheduling
 * @subpackage Import
 */
class CrewScheduling_Import_Poll_Csv extends \Tinebase_Import_Csv_Abstract
{
    protected $_additionalOptions = [
        'relativeDates' => ['from', 'until', 'deadline'],
        'site' => ['site'],
        'role' => ['scheduling_role']
    ];

    /**
     * do conversions
     *
     * @param array $_data
     * @return array
     */
    protected function _doConversions($_data)
    {
        $_data = parent::_doConversions($_data);

        $_data = $this->_convertRelativeDates($_data);
        $_data = $this->_convertSite($_data);
        $_data = $this->_convertRole($_data);

        return $_data;
    }

    protected function _convertRelativeDates($_data)
    {
        foreach ($this->_additionalOptions['relativeDates'] as $date) {
            if (!isset($_data[$date]) || !is_string($_data[$date]) || $_data[$date] === '') {
                continue;
            }

            $new = new Tinebase_DateTime();
            $new->modify($_data[$date]);
            $_data[$date] = $new;
        }
        return $_data;
    }

    private function _convertSite(array $_data)
    {
        foreach ($this->_additionalOptions['site'] as $site) {
            if (!isset($_data[$site]) || !is_string($_data[$site]) || $_data[$site] === '') {
                continue;
            }

            $siteRecord = \Addressbook_Controller_Contact::getInstance()->getRecordByTitleProperty(trim($_data[$site]));
            if ($siteRecord) {
                $_data[$site] = $siteRecord;
            }
        }
        return $_data;
    }

    private function _convertRole(array $_data)
    {
        foreach ($this->_additionalOptions['role'] as $role) {
            if (!isset($_data[$role]) || !is_string($_data[$role]) || $_data[$role] === '') {
                continue;
            }

            $roleRecord = \CrewScheduling_Controller_SchedulingRole::getInstance()->getRecordByTitleProperty(trim($_data[$role]));
            if ($roleRecord) {
                $_data[$role] = $roleRecord;
            }
        }
        return $_data;
    }
}
