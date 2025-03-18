<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Event
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2008-2021 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 */

/**
 * class to handle events between the applications
 *
 * @package     Tinebase
 * @subpackage  Event
 */
class Tinebase_Event
{
    /**
     * keeps a list of currently processed events
     * 
     * @var array
     */
    static public $events = array();
    static protected $history = [];
    
    /**
     * calls the handleEvent function in the controller of all enabled applications 
     *
     * @param  Tinebase_Event_Abstract $_eventObject  the event object
     * @return boolean success (false if event handler throws an exception)
     */
    static public function fireEvent(Tinebase_Event_Abstract $_eventObject)
    {
        self::$events[$_eventObject::class][$_eventObject->getId()] = $_eventObject;
        $historyOffset = count(static::$history);
        static::$history[$historyOffset] = ['event' => $_eventObject];
        
        if (self::isDuplicateEvent($_eventObject)) {
            // do nothing
            return true;
        }
        
        foreach (Tinebase_Application::getInstance()->getApplicationsByState(Tinebase_Application::ENABLED) as $application) {
            try {
                $controller = Tinebase_Core::getApplicationInstance($application, NULL, TRUE);
                if ($controller instanceof Tinebase_Event_Interface) {
                    static::$history[$historyOffset][$application->getId()] = true;
                    $controller->handleEvent($_eventObject);
                }
            } catch (Tinebase_Exception_NotFound $tenf) {
                // application has no controller or is not usable at all OR record not found...
                if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) Tinebase_Core::getLogger()->notice(__METHOD__ . ' '
                    . __LINE__ . ' ' . (string) $application . ' threw an exception: '
                    . $tenf->getMessage()
                );
                continue;
            } catch (Tinebase_Exception_AccessDenied $tead) {
                if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) Tinebase_Core::getLogger()->notice(
                    __METHOD__ . ' ' . __LINE__ . ' Access denied to app (or record) ' . $application->name
                    . ' exception: ' . $tead->getMessage());
                continue;
            } catch (Exception $e) {
                if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) Tinebase_Core::getLogger()->notice(__METHOD__ . ' '
                    . __LINE__ . ' ' . (string) $application . ' threw an exception: '
                    . $e->getMessage()
                );
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . ' '
                    . __LINE__ . ' ' . $e->getTraceAsString());
                
                if ($e instanceof Tinebase_Exception_Confirmation) {
                    throw $e;
                }
                
                return false;
            }
        }
        
        // try custom user defined listeners
        $customEventHook = Tinebase_Config::getInstance()->getHookClass(Tinebase_Config::EVENT_HOOK_CLASS,
            'handleEvent');
        if ($customEventHook) {
            try {
                Tinebase_Core::getLogger()->info(__METHOD__ . ' ' . __LINE__
                    . ' About to process user defined event hook for ' . $_eventObject::class);
                $customEventHook->handleEvent($_eventObject);
            } catch (Exception $e) {
                Tinebase_Core::getLogger()->info(__METHOD__ . ' ' . __LINE__
                    . ' Failed to process user defined event hook with message: ' . $e);
                return false;
            }
        }
        
        unset(self::$events[$_eventObject::class][$_eventObject->getId()]);

        return true;
    }
    
    /**
     * checks if an event is duplicate
     * 
     * @todo   implement logic
     * @param  Tinebase_Event_Abstract  $_eventObject  the event object
     * @return boolean
     */
    static public function isDuplicateEvent(Tinebase_Event_Abstract $_eventObject)
    {
        return false;
    }

    static public function reFireForNewApplications()
    {
        foreach (static::$history as $data) {
            $event = $data['event'];
            foreach (Tinebase_Application::getInstance()->getApplicationsByState(Tinebase_Application::ENABLED) as $application) {
                try {
                    $controller = Tinebase_Core::getApplicationInstance($application, NULL, TRUE);
                } catch (Tinebase_Exception_NotFound $e) {
                    // application has no controller or is not usable at all
                    continue;
                }
                if (isset($data[$application->getId()])) {
                    continue;
                }
                if ($controller instanceof Tinebase_Event_Interface) {
                    if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . ' '
                        . __LINE__ . ' calling eventhandler for event ' . $event::class . ' of application ' . (string) $application);

                    try {
                        $controller->handleEvent($event);
                    } catch (Exception $e) {
                        if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) Tinebase_Core::getLogger()->notice(__METHOD__ . ' '
                            . __LINE__ . ' ' . (string) $application . ' threw an exception: '
                            . $e->getMessage()
                        );
                        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . ' '
                            . __LINE__ . ' ' . $e->getTraceAsString());
                    }
                }
            }
        }
    }
}
