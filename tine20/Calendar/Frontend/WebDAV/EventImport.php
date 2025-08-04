<?php
/**
 * Tine 2.0
 *
 * @package     Calendar
 * @subpackage  Frontend
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2022 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * class to handle a single event
 *
 * This class handles the creation, update and deletion of vevents
 *
 * @package     Calendar
 * @subpackage  Frontend
 */
class Calendar_Frontend_WebDAV_EventImport extends Calendar_Frontend_WebDAV_Event
{
    protected bool $doExternalSeqUpdateCheck = false;

    protected bool $keepExistingAttendee = false;

    public function setDoExternalSeqUpdateCheck(bool $value): void
    {
        $this->doExternalSeqUpdateCheck = $value;
    }

    public function setKeepExistingAttendee(bool $value): void
    {
        $this->keepExistingAttendee = $value;
    }

    /**
     * Updates the VCard-formatted object
     *
     * @param string $cardData
     * @param bool $retry
     * @return string
     */
    public function put($cardData, $retry = true)
    {
        Calendar_Controller_MSEventFacade::getInstance()->assertEventFacadeParams($this->_container);
        if ($this->getRecord()->getIdFromProperty('container_id') !== $this->_container->getId()) {
            throw new Tinebase_Exception_AccessDenied('container mismatch, should not happen -> fix it, understand what happened here!');
        }

        $this->_vevent = null;
        if (is_resource($cardData)) {
            $cardData = stream_get_contents($cardData);
        }
        // Converting to UTF-8, if needed
        $cardData = Sabre\DAV\StringUtil::ensureUTF8($cardData);
        $vobject = Calendar_Convert_Event_VCalendar_Abstract::getVObject($cardData);

        // clone, otherwise $this->_event and $event would be the same object
        $event = $this->_getConverter()->toTine20Model($vobject, clone $this->getRecord(), array(
            Calendar_Convert_Event_VCalendar_Abstract::OPTION_USE_SERVER_MODLOG => true,
        ));
        $event->container_id = $this->_container->getId();
        foreach (static::$_eventDeserializedHooks as $closure) {
            $closure($event);
        }

        if ($this->keepExistingAttendee) {
            Calendar_Import_Abstract::checkForExistingAttendee($event, $this->getRecord());
        }

        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG))
            Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " " . print_r($event->toArray(), true));

        if (!$this->doExternalSeqUpdateCheck || (int)$this->getRecord()->external_seq < (int)$event->external_seq) {
            try {
                $this->update($event, $cardData);

                // in case we have a deadlock, retry operation once
            } catch (Zend_Db_Statement_Exception $zdbse) {
                if ($retry && strpos($zdbse->getMessage(), 'Deadlock') !== false) {
                    Tinebase_TransactionManager::getInstance()->rollBack();
                    return $this->put($cardData, false);
                } else {
                    throw $zdbse;
                }
            }
        }
        
        return $this->getETag();
    }
}
