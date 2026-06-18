<?php
/**
 * Tine 2.0
 *
 * @package     Courses
 * @subpackage  Event
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 */

/**
 * thrown before a user account gets imported
 *
 */
class Admin_Event_BeforeImportUser extends Tinebase_Event_Abstract
{
    public function __construct(
        public Tinebase_Model_FullUser $_account,
        public array $_options,
    )
    {

        parent::__construct([]);
    }
}
