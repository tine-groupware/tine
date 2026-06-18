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
 * thrown before a teacher account gets created
 *
 */
class Courses_Event_BeforeAddTeacher extends Tinebase_Event_Abstract
{
    public function __construct(
        public Tinebase_Model_FullUser $_account,
        public Courses_Model_Course $_course,
    )
    {

        parent::__construct([]);
    }
}
