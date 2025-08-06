<?php declare(strict_types=1);
/**
 * Tine 2.0
  * 
 * @package     SSO
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2025 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * class for SSO uninitialization
 *
 */
class SSO_Setup_Uninitialize extends Setup_Uninitialize
{
    protected function _uninitializeTasks(): void
    {
        $scheduler = Tinebase_Core::getScheduler();
        SSO_Scheduler_Task::removeDeleteExpiredTokensTask($scheduler);
    }
}
