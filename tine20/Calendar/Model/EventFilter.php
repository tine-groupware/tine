<?php
/**
 * Tine 2.0
 * 
 * @package     Calendar
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2009-2025 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * Calendar Event Filter
 * 
 * @package Calendar
 */
class Calendar_Model_EventFilter extends Tinebase_Model_Filter_FilterGroup 
{
    /**
     * if this is set, the filtergroup will be created using the configurationObject for this model
     *
     * @var string
     */
    protected $_configuredModel = Calendar_Model_Event::class;
}
