<?php declare(strict_types=1);
/**
 * Tine 2.0
 *
 * @package     Calendar
 * @subpackage  Convert
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2024 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * calendar VCALENDAR converter abstract class
 *
 * @package     Calendar
 * @subpackage  Convert
 */
class Calendar_Convert_Event_VCalendar2_Abstract extends Tinebase_Convert_VCalendar_Abstract implements Calendar_Convert_Event_VCalendar2_Interface
{
    use Calendar_Convert_Event_VCalendar_AbstractTrait;

    protected $_modelName = Calendar_Model_Event::class;

    public function toTine20Models(string $blob, array $options = [], ?Tinebase_Record_RecordSet $mergeEvents = null): Tinebase_Record_RecordSet
    {
        // TODO FIXME, RFC says we may get vevents here, without a vcalendar surrounding it! sabre doesnt support that...
        // https://datatracker.ietf.org/doc/html/rfc6047#section-2.4
        // component=vevent -> part of content-type header
        $vcalendar = Tinebase_Convert_VCalendar_Abstract::getVObject($blob);

        // contains the VCALENDAR any VEVENTS
        if (!isset($vcalendar->VEVENT)) {
            throw new Tinebase_Exception_UnexpectedValue('no vevents found');
        }

        if (isset($vcalendar->METHOD)) {
            $this->setMethod($vcalendar->METHOD->getValue());
        }

        $records = new Tinebase_Record_RecordSet(Calendar_Model_Event::class);
        $exceptions = new Tinebase_Record_RecordSet(Calendar_Model_Event::class);

        foreach ($vcalendar->select('VEVENT') as $vevent) {
            $event = new Calendar_Model_Event([], true);
            $this->_convertVevent($vevent, $event, $options);

            if ($mergeEvents && ($mergeEvent = $mergeEvents->find(fn($e) => $e->uid === $event->uid && $e->recurid === $event->recurid, null))) {
                $this->_convertVevent($vevent, $mergeEvent, $options);
                $event = $mergeEvent;
            }

            if ($event->isRecurException()) {
                $exceptions->addRecord($event);
            } else {
                $records->addRecord($event);
            }
        }

        /** @var Calendar_Model_Event $exception */
        foreach ($exceptions as $exception) {
            /** @var Calendar_Model_Event $baseEvent */
            if (($baseEvent = $records->find('uid', $exception->uid)) && !$baseEvent->isRecurException()) {
                if (!$baseEvent->exdate instanceof Tinebase_Record_RecordSet) {
                    $baseEvent->exdate = new Tinebase_Record_RecordSet(Calendar_Model_Event::class, is_array($baseEvent->exdate) ? $baseEvent->exdate : []);
                }
                $baseEvent->exdate->addRecord($exception);
            } else {
                $records->addRecord($exception);
            }
        }

        return $records;
    }

    public function getMethod(): ?string
    {
        return $this->method;
    }

    public function setMethod(?string $method): void
    {
        $this->method = $method;
    }

    protected ?string $method = null;
}
