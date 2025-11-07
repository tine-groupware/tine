<?php declare(strict_types=1);
/**
 * Tine 2.0
 * 
 * @package     Calendar
 * @subpackage  Frontend
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2011-2025 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * iMIP (RFC 6047) frontend for calendar
 * 
 * @package     Calendar
 * @subpackage  Frontend
 */
class Calendar_Frontend_iMIP
{
    public static bool $doIMIPSpoofProtection = true;

    public function autoProcess(Calendar_Model_iMIP $_iMIP, bool $_retry = true): void
    {
        // to fill method
        $_iMIP->getEvents();

        // replies
        // counter-replies maybe to be added later
        if ($_iMIP->method !== Calendar_Model_iMIP::METHOD_REPLY) return;

        $this->process($_iMIP, _retry: $_retry);
    }
    
    /**
     * manual process iMIP components and optionally set status
     */
    public function process(Calendar_Model_iMIP $_iMIP, null|string|Calendar_Model_Attender $_status = null, bool $_retry = true): void
    {
        // client spoofing protection - throws exception if spoofed
        // not sure why we do this?
        if (Tinebase_Application::getInstance()->isInstalled('Felamimail') && static::$doIMIPSpoofProtection) {
            if (null === ($reloadedIMIP = Felamimail_Controller_Message::getInstance()->getiMIP($_iMIP->getId()))) {
                throw new Tinebase_Exception_NotFound('imip message not found');
            }
            $_iMIP->ics = $reloadedIMIP->ics;
        }

        $onlyGenerateRaii = null;
        if ($_iMIP->{Calendar_Model_iMIP::FLD_EDIT_RESPONSE_EMAIL}) {
            Calendar_Controller_EventNotifications::getInstance()->resetGeneratedEmails();
            $oldOnlyGenerate = Calendar_Controller_Event::getInstance()->onlyGenerateNotificationsNoSend(true);
            $onlyGenerateRaii = new Tinebase_RAII(function() use ($oldOnlyGenerate) {
                Calendar_Controller_EventNotifications::getInstance()->resetGeneratedEmails();
                Calendar_Controller_Event::getInstance()->onlyGenerateNotificationsNoSend($oldOnlyGenerate);
            });
        }

        $_iMIP->event = null;
        /** @var Calendar_Model_Event $imipEvent */
        foreach ($_iMIP->getEvents() as $imipEvent) {
            $exdates = $imipEvent->exdate;
            $imipEvent->exdate = null;
            $events = $exdates?->getClone(true) ?? new Tinebase_Record_RecordSet(Calendar_Model_Event::class);
            $events->unshiftRecord($imipEvent);
            if (null === $_iMIP->event) {
                $_iMIP->event = $events->getFirstRecord();
            }
            foreach ($events as $event) {
                try {
                    $this->_process($_iMIP, $event, $_status);
                } catch (Zend_Db_Statement_Exception $zdbse) {
                    if ($_retry && strpos($zdbse->getMessage(), 'Deadlock') !== false) {
                        $this->_process($_iMIP, $event, $_status);
                    } else {
                        throw $zdbse;
                    }
                }
            }
            $imipEvent->exdate = $exdates;
        }

        $_iMIP->existing_events = null;
        $_iMIP->existing_event = null;

        if ($_iMIP->{Calendar_Model_iMIP::FLD_EDIT_RESPONSE_EMAIL}) {
            $_iMIP->{Calendar_Model_iMIP::FLD_RESPONSE_EMAILS} = Calendar_Controller_EventNotifications::getInstance()->getGeneratedEmails();
            unset($onlyGenerateRaii);
        }
    }
    
    /**
     * prepares iMIP component for client
     */
    public function prepareComponent(Calendar_Model_iMIP $_iMIP, bool $_throwException = false, null|string|Calendar_Model_Attender $status = null): void
    {
        $_iMIP->existing_event = null;
        $_iMIP->event = null;
        foreach ($_iMIP->getEvents() as $imipEvent) {
            $events = $imipEvent->exdate?->getClone(true) ?? new Tinebase_Record_RecordSet(Calendar_Model_Event::class);
            $events->unshiftRecord($imipEvent);
            if (null === $_iMIP->event) {
                $_iMIP->event = $events->getFirstRecord();
            }
            $method = '_prepareComponent' . ucfirst(strtolower($_iMIP->method ?: ''));
            foreach ($events as $event) {
                $this->_checkPreconditions($_iMIP, $event, $_throwException, $status);
                if (method_exists($this, $method)) {
                    $this->$method($_iMIP, $event, $_throwException, $status);
                }

                Calendar_Convert_Event_Json::resolveRelatedData($event);
                Tinebase_Model_Container::resolveContainerOfRecord($event);
                Tinebase_Model_Container::resolveContainerOfRecord($existingEvent = $_iMIP->getExistingEvent($event, _getDeleted: true));

                if (null !== $existingEvent) {
                    Calendar_Model_Attender::resolveAttendee($existingEvent->attendee, _events: $existingEvent);
                    $_iMIP->aggregateInternalAttendees($existingEvent->attendee);
                }
                $_iMIP->aggregateInternalAttendees($event->attendee); // $event has resolved attendees due to Calendar_Convert_Event_Json::resolveRelatedData($event);
            }
        }
        $_iMIP->finishInternalAttendeeAggregation();
        if (null !== $_iMIP->event) {
            $_iMIP->existing_event = $_iMIP->getExistingEvent($_iMIP->event, _getDeleted: true);
        }
        // existing events are resolved and present on $_iMIP
    }
    
    /**
     * check precondtions
     *
     * @throws Calendar_Exception_iMIP
     * 
     * @todo add iMIP record to exception when it extends the Data exception
     */
    protected function _checkPreconditions(Calendar_Model_iMIP $_iMIP, Calendar_Model_Event $_event, bool $_throwException = false, null|string|Calendar_Model_Attender $_status = null): bool
    {
        $key = $_event->getRecurIdOrUid();
        if ($_iMIP->preconditionsChecked[$key] ?? false) {
            if (empty($_iMIP->preconditions[$key] ?? []) || ! $_throwException) {
                return true;
            } else {
                throw new Calendar_Exception_iMIP('iMIP preconditions failed: ' . implode(', ', array_keys($_iMIP->preconditions)));
            }
        }
        
        $method = $_iMIP->method ? ucfirst(strtolower($_iMIP->method)) : 'MISSINGMETHOD';
        
        $preconditionMethodName  = '_check'     . $method . 'Preconditions';
        if (method_exists($this, $preconditionMethodName)) {
            $preconditionCheckSuccessful = $this->{$preconditionMethodName}($_iMIP, $_event, $_status);
        } else {
            $preconditionCheckSuccessful = true;
            if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__
                . " No preconditions check fn found for method " . $method);
        }
        
        $_iMIP->xprops('preconditionsChecked')[$key] = true;
        
        if ($_throwException && ! $preconditionCheckSuccessful) {
            throw new Calendar_Exception_iMIP('iMIP preconditions failed: ' . implode(', ', array_keys($_iMIP->preconditions[$key])));
        }
        
        return $preconditionCheckSuccessful;
    }

    protected function _process(Calendar_Model_iMIP $_iMIP, Calendar_Model_Event $_event, null|string|Calendar_Model_Attender $_status = null): void
    {
        if (empty($_iMIP->method)) {
            if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) Tinebase_Core::getLogger()->notice(
                __METHOD__ . '::' . __LINE__ . ' iMIP method empty ... assuming "REQUEST"');
            $_iMIP->method = 'REQUEST';
        }
        $method = ucfirst(strtolower($_iMIP->method));
        $processMethodName = '_process' . $method;

        if (!method_exists($this, $processMethodName)) {
            throw new Tinebase_Exception_UnexpectedValue("Method {$_iMIP->method} not supported");
        }

        $this->_checkPreconditions($_iMIP, $_event, _throwException: true, _status: $_status);
        $this->{$processMethodName}($_iMIP, $_event, $_status);
    }
    
    /**
     * @todo implement
     */
    protected function _checkPublishPreconditions(Calendar_Model_iMIP $_iMIP, Calendar_Model_Event $_event, null|string|Calendar_Model_Attender $_status): bool
    {
        $_iMIP->addFailedPrecondition($_event->getRecurIdOrUid(), Calendar_Model_iMIP::PRECONDITION_SUPPORTED, 'processing published events is not supported yet');
        return false;
    }
    
    /**
     * @todo implement
     */
    protected function _processPublish(Calendar_Model_iMIP $_iMIP, Calendar_Model_Event $_event, null|string|Calendar_Model_Attender $_status): void
    {
        // add/update event (if outdated) / no status stuff / DANGER of duplicate UIDs
        // -  no notifications!
    }

    protected function _checkRequestPreconditions(Calendar_Model_iMIP $_iMIP, Calendar_Model_Event $_event, null|string|Calendar_Model_Attender $_status): bool
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
            . ' Checking REQUEST preconditions of iMIP ...');

        $existingEvent = $_iMIP->getExistingEvent($_event, _getDeleted: true);
        $ownAttender = Calendar_Model_Attender::getOwnAttender($existingEvent ? $existingEvent->attendee : $_event->attendee);
        if ($_status instanceof Calendar_Model_Attender && (!$ownAttender || $ownAttender->user_id !== $ownAttender->user_id)) {
            $result = $this->_assertAttender($_iMIP, $_event, $_status);
        } else {
            $result = $this->_assertOwnAttender($_iMIP, $_event);
            $result = $this->_assertOrganizer($_iMIP, $_event, _assertNotOwn: true) && $result;
        }

        if ($existingEvent) {
            $isObsoleted = false;
            if (! $existingEvent->hasExternalOrganizer() && $_event->isObsoletedBy($existingEvent)) {
                $isObsoleted = true;
            } elseif ($_event->external_seq < $existingEvent->external_seq) {
                $isObsoleted = true;
            }

            // allow if not rescheduled and not deleted
            if ($isObsoleted && ($_event->isRescheduled($existingEvent) || $_event->is_deleted)) {
                $_iMIP->addFailedPrecondition($_event->getRecurIdOrUid(), Calendar_Model_iMIP::PRECONDITION_RECENT, "old iMIP message");
                $result = false;
            }
        }

        return $result;
    }

    /**
     * process request
     *
     * TODO on multi process, only process exceptions that have the same start/end as baseevent
     */
    protected function _processRequest(Calendar_Model_iMIP $_iMIP, Calendar_Model_Event $_event, null|string|Calendar_Model_Attender $_status = null): void
    {
        $existingEvent = $_iMIP->getExistingEvent($_event, _getDeleted: true);
        if ($_status instanceof Calendar_Model_Attender) {
            $attendee = Calendar_Model_Attender::getAttendee($existingEvent?->attendee ?: $_event->attendee, $_status);
            $_status = $_status->status;
        } else {
            $attendee = Calendar_Model_Attender::getOwnAttender($existingEvent?->attendee ?: $_event->attendee) ?:
                Calendar_Model_Attender::getOwnAttender($_event->attendee);
        }
        $organizer = $existingEvent?->resolveOrganizer() ?: $_event->resolveOrganizer();
        
        // internal organizer:
        //  - event is up-to-date
        //  - status change could also be done by calendar method
        //  - normal notifications 
        if ($organizer?->account_id) {
            if (! $existingEvent || $existingEvent->is_deleted) {
                if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
                        . ' Organizer has an account but no event exists!');
                return;
            }
            
            if ($attendee && $_status && $_status != $attendee->status) {
                $attendee->status = $_status;
                Calendar_Controller_Event::getInstance()->attenderStatusUpdate($existingEvent, $attendee, $attendee->status_authkey);
            }
        }
        
        // external organizer:
        else {
            $sendNotifications = Calendar_Controller_Event::getInstance()->sendNotifications(false);
            $calCtrl = Calendar_Controller_Event::getInstance();
            $mergeIntoExistingSeries = function(Calendar_Model_Event $_event) use($calCtrl) {
                if ($_event->isRecurException()) {
                    // merge this event into the existing event series, if it does exist
                    $invitationContainer = Calendar_Controller::getInstance()->getInvitationContainer($_event->organizer_email ? null : $_event->resolveOrganizer(), $_event->organizer_email)->getId();
                    $_event->base_event_id = $calCtrl->search(new Calendar_Model_EventFilter([
                        ['field' => 'container_id', 'operator' => 'equals', 'value' => $invitationContainer],
                        ['field' => 'uid', 'operator' => 'equals', 'value' => $_event->uid],
                        ['field' => 'recurid', 'operator' => 'isnull', 'value' => null],
                        ['field' => 'base_event_id', 'operator' => 'isnull', 'value' => null],
                        ['field' => 'rrule', 'operator' => 'notnull', 'value' => null],
                    ]))->getFirstRecord()?->getId();
                }
            };
            $collectExistingExceptions = function(Calendar_Model_Event $_event) use($calCtrl) {
                if (null !== $_event->rrule) {
                    // merge existing exceptions into this event series
                    foreach ($calCtrl->search(new Calendar_Model_EventFilter([
                        ['field' => 'container_id', 'operator' => 'equals', 'value' => $_event->container_id],
                        ['field' => 'uid', 'operator' => 'equals', 'value' => $_event->uid],
                        ['field' => 'id', 'operator' => 'not', 'value' => $_event->getId()],
                        ['field' => 'base_event_id', 'operator' => 'isnull', 'value' => null],
                    ])) as $exceptionToMerge) {
                        $exceptionToMerge->base_event_id = $_event->getId();
                        $calCtrl->update($exceptionToMerge);
                    }
                }
            };
            if (! $existingEvent) {
                $mergeIntoExistingSeries($_event);
                $_event = Calendar_Controller_MSEventFacade::getInstance()->create($_event, true);
                $collectExistingExceptions($_event);
            } else {
                if ($_event->external_seq > $existingEvent->external_seq ||
                        (isset($_event->xprops()[Calendar_Model_Event::XPROPS_IMIP_PROPERTIES]['LAST-MODIFIED']) &&
                        isset($existingEvent->xprops()[Calendar_Model_Event::XPROPS_IMIP_PROPERTIES]['LAST-MODIFIED'])
                        && $_event->xprops()[Calendar_Model_Event::XPROPS_IMIP_PROPERTIES]['LAST-MODIFIED'] >
                            $existingEvent->xprops()[Calendar_Model_Event::XPROPS_IMIP_PROPERTIES]['LAST-MODIFIED']) ||
                        (isset($_event->xprops()[Calendar_Model_Event::XPROPS_IMIP_PROPERTIES]['DTSTAMP']) &&
                            isset($existingEvent->xprops()[Calendar_Model_Event::XPROPS_IMIP_PROPERTIES]['DTSTAMP'])
                            && $_event->xprops()[Calendar_Model_Event::XPROPS_IMIP_PROPERTIES]['DTSTAMP'] >
                            $existingEvent->xprops()[Calendar_Model_Event::XPROPS_IMIP_PROPERTIES]['DTSTAMP'])) {

                    if ($existingEvent->is_deleted) {
                        $calCtrl->unDelete($existingEvent);
                        $existingEvent = $calCtrl->get($existingEvent->getId());
                    }
                    // updates event with .ics
                    $_event->id = $existingEvent->id;
                    $_event->last_modified_time = $existingEvent->last_modified_time;
                    $_event->seq = $existingEvent->seq;
                    $mergeIntoExistingSeries($_event);
                    $_event = Calendar_Controller_MSEventFacade::getInstance()->update($_event);
                    $collectExistingExceptions($_event);
                } else {
                    // event is current
                    $_event = $existingEvent;
                }
            }
            
            Calendar_Controller_Event::getInstance()->sendNotifications($sendNotifications);

            $attendee = Calendar_Model_Attender::getAttendee($_event->attendee, $attendee);
            
            // NOTE: we do the status update in a separate call to trigger the right notifications
            if ($attendee && $_status) {
                $attendee->status = $_status;
                Calendar_Controller_Event::getInstance()->attenderStatusUpdate($_event, $attendee, $attendee->status_authkey);
            }
        }
    }
    
    /**
     * @TODO an internal reply should trigger a RECENT precondition
     * @TODO distinguish RECENT and PROCESSED preconditions?
     */
    protected function _checkReplyPreconditions(Calendar_Model_iMIP $_iMIP, Calendar_Model_Event $_event): bool
    {
        $result = true;
        
        $existingEvent = $_iMIP->getExistingEvent($_event);
        if (! $existingEvent) {
            if ($_event->isRecurException()) {
                $tmpEvent = clone $_event;
                $tmpEvent->recurid = null;
                $existingEvent = $_iMIP->getExistingEvent($tmpEvent);
            }
            if (! $existingEvent) {
                $_iMIP->addFailedPrecondition($_event->getRecurIdOrUid(), Calendar_Model_iMIP::PRECONDITION_EVENTEXISTS, "cannot process REPLY to non existent/invisible event");
                return false;
            }
        }
        
        $iMIPAttenderIdx = $_event->attendee instanceof Tinebase_Record_RecordSet ? array_search(
            strtolower($_iMIP->originator),
            array_map('strtolower', $_event->attendee->getEmail())
        ) : false;
        /** @var Calendar_Model_Attender $iMIPAttender */
        $iMIPAttender = $iMIPAttenderIdx !== false ? $_event->attendee[$iMIPAttenderIdx] : null;
        $iMIPAttenderStatus = $iMIPAttender ? $iMIPAttender->status : null;
        $eventAttenderIdx = $existingEvent->attendee instanceof Tinebase_Record_RecordSet ? array_search(
            strtolower($_iMIP->originator),
            array_map('strtolower', $existingEvent->attendee->getEmail())
        ) : false;
        /** @var Calendar_Model_Attender $eventAttender */
        $eventAttender = $eventAttenderIdx !== false ? $existingEvent->attendee[$eventAttenderIdx] : null;
        $eventAttenderStatus = $eventAttender ? $eventAttender->status : null;

        if ($_event->isRecurException() && !$existingEvent->isRecurException()) {
            $orgException = Calendar_Model_Rrule::computeRecurrenceSet($existingEvent, $existingEvent->exdate, $_event->dtstart->getClone(), $_event->dtend->getClone())
                ->getFirstRecord();

            if (null === $orgException || !$orgException->dtstart->equals($_event->dtstart) ||
                    !$orgException->dtend->equals($_event->dtend)) {
                $_iMIP->addFailedPrecondition($_event->getRecurIdOrUid(), Calendar_Model_iMIP::PRECONDITION_RECENT, "event was rescheduled");
                $result = false;
            }
        } elseif ($_event->isRescheduled($existingEvent)) {
            $_iMIP->addFailedPrecondition($_event->getRecurIdOrUid(), Calendar_Model_iMIP::PRECONDITION_RECENT, "event was rescheduled");
            $result = false;
        }
        
        if (! $eventAttender) {
            $_iMIP->addFailedPrecondition($_event->getRecurIdOrUid(), Calendar_Model_iMIP::PRECONDITION_ORIGINATOR, "originator is not attendee in existing event -> party crusher?");
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                . ' originator is not attendee in existing event - originator: ' . print_r($_iMIP->originator, true));
            $result = false;
        } elseif (isset($eventAttender->xprops()[Calendar_Model_Attender::XPROP_REPLY_SEQUENCE])) {
            if ($eventAttender->xprops()[Calendar_Model_Attender::XPROP_REPLY_SEQUENCE] > $_event->seq ||
                ($eventAttender->xprops()[Calendar_Model_Attender::XPROP_REPLY_SEQUENCE] == $_event->seq &&
                    (!$_event->last_modified_time instanceof Tinebase_DateTime ||
                        !isset($eventAttender->xprops()[Calendar_Model_Attender::XPROP_REPLY_DTSTAMP]) ||
                        $_event->last_modified_time->isEarlierOrEquals(new Tinebase_DateTime(
                        $eventAttender->xprops()[Calendar_Model_Attender::XPROP_REPLY_DTSTAMP]))))) {
                $_iMIP->addFailedPrecondition($_event->getRecurIdOrUid(), Calendar_Model_iMIP::PRECONDITION_RECENT, "old iMIP message");
                $result = false;
            }
        }

        if (! is_null($iMIPAttenderStatus) && $iMIPAttenderStatus == $eventAttenderStatus) {
            $_iMIP->addFailedPrecondition($_event->getRecurIdOrUid(), Calendar_Model_iMIP::PRECONDITION_TOPROCESS, "this REPLY was already processed");
            $result = false;
        }
        
        if (! $iMIPAttender) {
            $_iMIP->addFailedPrecondition($_event->getRecurIdOrUid(), Calendar_Model_iMIP::PRECONDITION_ORIGINATOR,
                "originator is not attendee in iMIP transaction -> spoofing attempt?");
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                . ' originator is not attendee in iMIP transaction - originator: ' . print_r($_iMIP->originator, true));
            $result = false;
        }

        if (! $this->_assertOrganizer($_iMIP, $_event, _assertOwn: true)) {
            $result = false;
        }
        
        return $result;
    }
    
    /**
     * some attender replied to my request (I'm Organizer) -> update status (seq++) / send notifications!
     * 
     * NOTE: only external replies should be processed here
     *       @todo check silence for internal replies
     */
    protected function _processReply(Calendar_Model_iMIP $_iMIP, Calendar_Model_Event $_event): void
    {
        // merge ics attendee status into existing events attendee
        $existingEvent = $_iMIP->getExistingEvent($_event);
        if (! $existingEvent) {
            if ($_event->isRecurException()) {
                $tmpEvent = clone $_event;
                $tmpEvent->recurid = null;
                $existingEvent = $_iMIP->getExistingEvent($tmpEvent);
            }
            if (!$existingEvent) {
                return;
            }

            $event = Calendar_Controller_Event::getInstance()->get($existingEvent->getId());
            $event->dtstart = $_event->dtstart->getClone();
            $event->dtend = $_event->dtend->getClone();
            $event->setRecurId($event->getId());
            $event->setId(null);
            $doSendNotifications =  Calendar_Controller_Event::getInstance()->sendNotifications(false);
            try {
                $existingEvent = Calendar_Controller_Event::getInstance()->createRecurException($event);
            } finally {
                Calendar_Controller_Event::getInstance()->sendNotifications($doSendNotifications);
            }
        }

        /** @var Calendar_Model_Attender $existingAttendee */
        $existingAttendee = $existingEvent->attendee[array_search(strtolower($_iMIP->originator),
            array_map('strtolower', $existingEvent->attendee->getEmail()))];
        /** @var Calendar_Model_Attender $attendee */
        $attendee = $_event->attendee[array_search(strtolower($_iMIP->originator),
            array_map('strtolower', $_event->attendee->getEmail()))]; /** @phpstan-ignore method.notFound */

        $existingAttendee->status = $attendee->status;
        // _checkReplyPreconditions checks on seq and ts, we do not do that here again
        $existingAttendee->xprops()[Calendar_Model_Attender::XPROP_REPLY_SEQUENCE] = $_event->seq;
        if ($_event->last_modified_time instanceof Tinebase_DateTime) {
            $existingAttendee->xprops()[Calendar_Model_Attender::XPROP_REPLY_DTSTAMP] = $_event->last_modified_time->toString();
        } else {
            unset($existingAttendee->xprops()[Calendar_Model_Attender::XPROP_REPLY_DTSTAMP]);
        }

        // NOTE: if current user has no rights to the calendar, status update is not applied
        Calendar_Controller_MSEventFacade::getInstance()->attenderStatusUpdate($existingEvent, $existingAttendee);
    }
    
    /**
    * @todo implement
    */
    protected function _checkAddPreconditions(Calendar_Model_iMIP $_iMIP, Calendar_Model_Event $_event): bool
    {
        $_iMIP->addFailedPrecondition($_event->getRecurIdOrUid(), Calendar_Model_iMIP::PRECONDITION_SUPPORTED, 'processing add requests is not supported yet');
        return false;
    }
    
    /**
    * @todo implement
    */
    protected function _processAdd(Calendar_Model_iMIP $_iMIP, Calendar_Model_Event $_event): void
    {
        // organizer added a meeting/recurrance to an existing event -> update event
        // internal organizer:
        //  - event is up to date nothing to do
        // external organizer:
        //  - update event
        //  - the iMIP is already the notification mail!
    }

    protected function _checkCancelPreconditions(Calendar_Model_iMIP $_iMIP, Calendar_Model_Event $_event): bool
    {
        $existingEvent = $_iMIP->getExistingEvent($_event, _getDeleted: true);
        $result = true;

        if ($existingEvent) {
            if (Calendar_Model_Event::STATUS_CANCELED === $_event->status) {
                if ($existingEvent->is_deleted) {
                    $_iMIP->addFailedPrecondition($_event->getRecurIdOrUid(), Calendar_Model_iMIP::PRECONDITION_NOTDELETED, "old iMIP message is deleted");
                    $result = false;
                }
            } elseif (!$this->_assertOwnAttender($_iMIP, $_event)) {
                $result = false;
            }
            if (! $existingEvent->hasExternalOrganizer()) {
                $_iMIP->addFailedPrecondition($_event->getRecurIdOrUid(), Calendar_Model_iMIP::PRECONDITION_RECENT, "old iMIP message");
                $result = false;
            }
        } elseif (!$_event->hasExternalOrganizer()) {
            $_iMIP->addFailedPrecondition($_event->getRecurIdOrUid(), Calendar_Model_iMIP::PRECONDITION_RECENT, "old iMIP message");
            $result = false;
        }
        return $result;
    }

    protected function _processCancel(Calendar_Model_iMIP $_iMIP, Calendar_Model_Event $_event): void
    {
        if (!$_event->hasExternalOrganizer()) {
            return;
        }

        // organizer cancelled meeting/recurrence of an existing event -> update event
        // the iMIP is already the notification mail!
        $existingEvent = $_iMIP->getExistingEvent($_event, _getDeleted: true);
        if ($existingEvent && !$existingEvent->hasExternalOrganizer()) {
            return;
        }

        $sendNotifications = Calendar_Controller_Event::getInstance()->sendNotifications(FALSE);
        $notificationRaii = new Tinebase_RAII(fn() => Calendar_Controller_Event::getInstance()->sendNotifications($sendNotifications));
        if (!$existingEvent) {
            // create a [to be] cancelled event
            $existingEvent = Calendar_Controller_MSEventFacade::getInstance()->create($_event);
        }

        if (! $existingEvent->is_deleted) {
            if (Calendar_Model_Event::STATUS_CANCELED === $_event->status) {
                if (Calendar_Model_Event::STATUS_CANCELED !== $existingEvent->status) {
                    $existingEvent->status = Calendar_Model_Event::STATUS_CANCELED;
                    Calendar_Controller_MSEventFacade::getInstance()->update($existingEvent);
                }
            } else {
                // Attendee cancelled
                if ($ownAttendee = Calendar_Model_Attender::getOwnAttender($existingEvent->attendee)) {
                    $existingEvent->attendee->removeRecord($ownAttendee);
                    Calendar_Controller_MSEventFacade::getInstance()->update($existingEvent);
                }
            }
        }

        unset($notificationRaii);
    }
    
    /**
    * refresh precondition
    *
    * @param  Calendar_Model_iMIP   $_iMIP
    * @return boolean
    *
    * @todo implement
    */
    protected function _checkRefreshPreconditions(Calendar_Model_iMIP $_iMIP, Calendar_Model_Event $_event): bool
    {
        $_iMIP->addFailedPrecondition($_event->getRecurIdOrUid(), Calendar_Model_iMIP::PRECONDITION_SUPPORTED, 'processing REFRESH is not supported yet');
        return false;
    }
    
    /**
    * @todo implement
    */
    protected function _processRefresh(Calendar_Model_iMIP $_iMIP, Calendar_Model_Event $_event): void
    {
        // always internal organizer
        //  - send message
        //  - mark iMIP message ANSWERED
    }

    protected function _checkCounterPreconditions(Calendar_Model_iMIP $_iMIP, Calendar_Model_Event $_event): bool
    {
        $result = $this->_assertOrganizer($_iMIP, $_event, _assertOwn: true);

        if (null === $_iMIP->getExistingEvent($_event, _getDeleted: true)) {
            $_iMIP->addFailedPrecondition($_event->getRecurIdOrUid(), Calendar_Model_iMIP::PRECONDITION_EVENTEXISTS, 'counter can only processed for existing events');
            $result = false;
        }

        return $result;
    }

    protected function _processCounter(Calendar_Model_iMIP $_iMIP, Calendar_Model_Event $_event, ?string $_status = null): void
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
            . " with status " . $_status);

        if (null === ($existingEvent = $_iMIP->getExistingEvent($_event, _getDeleted: true))) {
            return;
        }

        // some attendee suggests to change the event
        // status: ACCEPT => update event, send notifications to all
        // status: DECLINE => send DECLINECOUNTER to originator
        if (Calendar_Model_Attender::STATUS_ACCEPTED === $_status) {
            $_iMIP->mergeEvents(new Tinebase_Record_RecordSet(Calendar_Model_Event::class, [$existingEvent]));
            if ($existingEvent->isDirty()) {
                Calendar_Controller_MSEventFacade::getInstance()->update($existingEvent);
            }

        } elseif (Calendar_Model_Attender::STATUS_DECLINED === $_status) {
            Calendar_Model_Attender::resolveAttendee($existingEvent->attendee);
            if ($attendee = Calendar_Model_Attender::getAttendeeByEmail($existingEvent->attendee, $_iMIP->originator)) {

                Calendar_Controller_EventNotifications::getInstance()->sendNotificationToAttender(
                    _attender: $attendee,
                    _event: $existingEvent,
                    _updater: Tinebase_Core::getUser(),
                    _action: 'declineCounter',
                    _notificationLevel: null
                );
            } else {
                if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__
                    . ' originator ' . $_iMIP->originator . ' not found in attendee list, no decline counter email sent');
            }

        } else {
            throw new Tinebase_Exception_UnexpectedValue('status ' . $_status . ' not supported in COUNTER');
        }
    }

    public function _prepareComponentCounter(Calendar_Model_iMIP $_iMIP, Calendar_Model_Event $event, bool $_throwException = false, null|string|Calendar_Model_Attender $status = null): void
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__);

        if (null === ($existingEvent = $_iMIP->getExistingEvent($event, _getDeleted: true))) {
            return;
        }

        $omit = array_diff_key(array_fill_keys(Calendar_Model_Event::getConfiguration()->fieldKeys, true), [
            'dtstart' => false,
            'dtend' => false,
            'summary' => false,
        ]);
        // TODO FIXME handle ATTACHE => attachments?
        /* TODO FIXME spec says attendee can be countered ... but google calendar only sends the countering attendee as attendee -> we ignore it, maybe allow by client header, for ms365 etc.?
         *if ($event->attendee) {
         *   unset($omit['attendee']);
        *}
         */
        // TODO FIXME handle CATEGORIES => tags
        // TODO FIXME handle CLASS => it defaults to public, so we dont know if it came or not...
        if (null !== $event->description) {
            unset($omit['description']);
        }
        if ($event->exdate && $_iMIP->existing_event->exdate) {
            unset($omit['exdate']);
        }
        if ($event->location) {
            unset($omit['location']);
        }
        if ($event->rrule) {
            unset($omit['rrule']);
        }
        if ($event->rrule_until) {
            unset($omit['rrule_until']);
        }
        if ($event->status) {
            unset($omit['status']);
        }
        // TODO FIXME handle TRANSP => it defaults to TRANSP_OPAQUE, so we dont know if it came or not...
        if ($event->url) {
            unset($omit['url']);
        }
        if ($event->alarms) {
            unset($omit['alarms']);
        }
        /*
         * IANA-PROPERTY
            X-PROPERTY
        IANA-COMPONENT
        X-COMPONENT
         */

        $diff = $existingEvent->diff($event, array_keys($omit));
        $_iMIP->xprops()['counterDiff'] = array_keys($diff->diff);
    }
    
    /**
    * @todo implement
    */
    protected function _checkDeclinecounterPreconditions(Calendar_Model_iMIP $_iMIP, Calendar_Model_Event $_event): bool
    {
        $_iMIP->addFailedPrecondition($_event->getRecurIdOrUid(), Calendar_Model_iMIP::PRECONDITION_SUPPORTED, 'processing DECLINECOUNTER is not supported yet');
        return false;
    }
    
    /**
    * @todo implement
    */
    protected function _processDeclinecounter(Calendar_Model_iMIP $_iMIP, Calendar_Model_Event $_event)
    {
        // organizer declined my counter request of an existing event -> update event
    }

    protected function _assertAttender(Calendar_Model_iMIP $_iMIP, Calendar_Model_Event $_event, Calendar_Model_Attender $attendee): bool
    {
        $result = true;
        $existingEvent = $_iMIP->getExistingEvent($_event, _getDeleted: true);
        if (!($foundAttendee = Calendar_Model_Attender::getAttendee($existingEvent ? $existingEvent->attendee : $_event->attendee, $attendee))) {
            $_iMIP->addFailedPrecondition($_event->getRecurIdOrUid(), Calendar_Model_iMIP::PRECONDITION_ATTENDEE, "processing {$_iMIP->method} for non attendee is not supported");
            $result = false;
        } else {
            // TODO FIXME check rights on attendees display_cal?
        }

        return $result;
    }

    protected function _assertOwnAttender(Calendar_Model_iMIP $_iMIP, Calendar_Model_Event $_event): bool
    {
        $result = true;
        $existingEvent = $_iMIP->getExistingEvent($_event, _getDeleted: true);
        $ownAttender = Calendar_Model_Attender::getOwnAttender($existingEvent ? $existingEvent->attendee : $_event->attendee);

        if (!$ownAttender && (!$existingEvent || !Calendar_Model_Attender::getOwnAttender($_event->attendee))) {
            $_iMIP->addFailedPrecondition($_event->getRecurIdOrUid(), Calendar_Model_iMIP::PRECONDITION_ATTENDEE, "processing {$_iMIP->method} for non attendee is not supported");
            $result = false;
        }

        return $result;
    }

    protected function _assertOrganizer(Calendar_Model_iMIP $_iMIP, Calendar_Model_Event $_event, bool $_assertAccount = false, bool $_assertOwn = false, bool $_assertNotOwn = false): bool
    {
        $result = true;

        $existingEvent = $_iMIP->getExistingEvent($_event, _getDeleted: true);
        $organizer = $existingEvent?->resolveOrganizer() ?: ($existingEvent?->organizer_email ?: ($_event->resolveOrganizer() ?: $_event->organizer_email));

        if (!$organizer) {
            $_iMIP->addFailedPrecondition($_event->getRecurIdOrUid(), Calendar_Model_iMIP::PRECONDITION_ORGANIZER, "processing {$_iMIP->method} without organizer is not possible");
            $result = false;
        }

        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__
            . ' Organizer: ' . ($organizer ? print_r($organizer->toArray(), true) : 'not found'));

        // config setting overwrites method param
        $assertAccount = Calendar_Config::getInstance()->get(Calendar_Config::DISABLE_EXTERNAL_IMIP, $_assertAccount);
        if ($assertAccount && (!$organizer instanceof Addressbook_Model_Contact || !$organizer->account_id)) {
            $_iMIP->addFailedPrecondition($_event->getRecurIdOrUid(), Calendar_Model_iMIP::PRECONDITION_ORGANIZER, "processing {$_iMIP->method} without organizer user account is not possible");
            $result = false;
        }

        if ($_assertOwn && (!$organizer instanceof Addressbook_Model_Contact || $organizer->getIdFromProperty('account_id') !== Tinebase_Core::getUser()->getId())) {
            $_iMIP->addFailedPrecondition($_event->getRecurIdOrUid(), Calendar_Model_iMIP::PRECONDITION_ORGANIZER, "processing {$_iMIP->method} requires to be organizer");
            $result = false;
        }

        if ($_assertNotOwn && $organizer instanceof Addressbook_Model_Contact && $organizer->getIdFromProperty('account_id') === Tinebase_Core::getUser()->getId()) {
            $_iMIP->addFailedPrecondition($_event->getRecurIdOrUid(), Calendar_Model_iMIP::PRECONDITION_ORGANIZER, "processing {$_iMIP->method} requires not to be organizer");
            $result = false;
        }

        return $result;
    }
}
