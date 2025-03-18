<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  ActionQueue
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2012-2016 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * the class does not queue the message but executes them immediately
 * 
 * @package     Tinebase
 * @subpackage  ActionQueue
 */
class Tinebase_ActionQueue_Backend_Direct implements Tinebase_ActionQueue_Backend_Interface
{
    /**
     * Constructor
     *
     * @param array $_options An array having configuration data
     */
    public function __construct(protected $_options)
    {
    }

    /**
     * Send a message to the queue
     *
     * @param  mixed $message Message to send to the active queue
     */
    public function send($message)
    {
        Tinebase_ActionQueue::getInstance()->executeAction($message);
    }
    
    /**
     * return queue length
     * @return int the queue length
     */
    public function getQueueSize()
    {
        return 0;
    }

    /**
     * wait for a new job in queue
     * @return mixed false on timeout or job id
     */
    public function waitForJob()
    {
        return FALSE;
    }

    /**
     * get one job from the queue
     *
     * @param  integer  $jobId  the id of the job
     * @throws Tinebase_Exception_NotImplemented
     * @return never
     */
    public function receive($jobId): never
    {
        throw new Tinebase_Exception_NotImplemented(__METHOD__ . ' is not implemented');
    }

    /**
     * Delete a job from the queue
     *
     * @param  string  $jobId  the id of the job
     * @throws Tinebase_Exception_NotImplemented
     */
    public function delete($jobId): never
    {
        throw new Tinebase_Exception_NotImplemented(__METHOD__ . ' is not implemented');
    }

    /**
     * @return boolean|string
     */
    public function peekJobId()
    {
        return false;
    }

    /**
     * check if the backend is async
     *
     * @return boolean true if queue backend is async
     */
    public function hasAsyncBackend()
    {
        return false;
    }
}