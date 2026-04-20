<?php
/**
 * class to hold Timesheet data
 * 
 * @package     Timetracker
 * @license     https://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2026 Metaways Infosystems GmbH (https://www.metaways.de)
 * 
 */
class Timetracker_Model_TimesheetNotBillable extends Timetracker_Model_Timesheet
{
    public const MODEL_NAME_PART = 'TimesheetNotBillable';

    /**
     * holds the configuration object (must be declared in the concrete class)
     *
     * @var Tinebase_ModelConfiguration
     */
    protected static $_configurationObject = NULL;

    public static function inheritModelConfigHook(array &$_definition)
    {
        parent::inheritModelConfigHook($_definition);
        unset($_definition[self::VERSION]);
        unset($_definition[self::TABLE]);
        $_definition[self::MODEL_NAME] = self::MODEL_NAME_PART;
    }
    
    /**
     * returns the quantity of this billable
     *
     * @return float
     */
    public function getQuantity()
    {
        return $this->duration / 60;
    }
}
