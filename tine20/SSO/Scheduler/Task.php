<?php declare(strict_types=1);
/**
 * Tine 2.0
 * 
 * @package     SSO
 * @subpackage  Scheduler
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2025 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */

class SSO_Scheduler_Task extends Tinebase_Scheduler_Task
{
    public const SSO_DELETE_EXPIRED_TOKENS = 'SSOdeleteExpiredTokens';

    public static function addDeleteExpiredTokensTask(Tinebase_Scheduler $scheduler): void
    {
        if ($scheduler->hasTask(self::SSO_DELETE_EXPIRED_TOKENS)) {
            return;
        }

        $task = self::_getPreparedTask(self::SSO_DELETE_EXPIRED_TOKENS, self::TASK_TYPE_DAILY, [[
            self::CONTROLLER    => SSO_Controller_Token::class,
            self::METHOD_NAME   => 'deleteExpiredTokens',
        ]]);
        $scheduler->create($task);
        
        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
            . ' Saved task ' . self::SSO_DELETE_EXPIRED_TOKENS . ' in scheduler.');
    }

    public static function removeDeleteExpiredTokensTask(Tinebase_Scheduler $scheduler): void
    {
        $scheduler->removeTask(self::SSO_DELETE_EXPIRED_TOKENS);

        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
            . ' removed task ' . self::SSO_DELETE_EXPIRED_TOKENS . ' from scheduler.');
    }
}
