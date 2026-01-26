<?php

declare(strict_types=1);

/**
 * Tine 2.0 - https://www.tine20.org
 *
 * @package     EventManager
 * @license     https://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2025 Metaways Infosystems GmbH (https://www.metaways.de)
 * @author      Tonia Wulff <t.leuschel@metaways.de>
 *
 */

use Aschmelyun\BasicFeeds\Feed;

class EventManager_Frontend_RssFeed
{
    use Tinebase_Controller_SingletonTrait;

    public function publicApiGetRssFeed()
    {
        $assertAclUsage = EventManager_Controller_Event::getInstance()->assertPublicUsage();
        try {
            $feed = $this->createRssWithGenerator();
            $xml = $feed->asRss();
            $response = new \Laminas\Diactoros\Response\XmlResponse($xml);
        } catch (Tinebase_Exception_NotFound $tenf) {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) {
                Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . $tenf);
            }
            $response = new \Laminas\Diactoros\Response('php://memory', 404);
            $response->getBody()->write(json_encode($tenf->getMessage()));
        } catch (Tinebase_Exception_Record_NotAllowed $terna) {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) {
                Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . $terna);
            }
            $response = new \Laminas\Diactoros\Response('php://memory', 401);
            $response->getBody()->write(json_encode($terna->getMessage()));
        } catch (Tinebase_Exception_AccessDenied $e) {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) {
                Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . $e);
            }
            $response = new \Laminas\Diactoros\Response('php://memory', 403);
            $response->getBody()->write(json_encode($e->getMessage()));
        } finally {
            $assertAclUsage();
        }
        return $response;
    }

    public function createRssWithGenerator()
    {
        $translate = Tinebase_Translation::getTranslation('EventManager');
        $feed = Feed::create([
            'authors' => 'Copyright (c) 2025 Metaways Infosystems GmbH (https://www.metaways.de)',
            'title' => $translate->_('Event Manager | RSS'),
            'description' => $translate->_('All Events in Event Manager'),
            'link' => Tinebase_Core::getUrl() . '/EventManager/events/rss',
            'language' => 'de-DE',
            'pudDate' => Tinebase_DateTime::today(),
        ]);

        $events = EventManager_Controller_Event::getInstance()->getAll();
        $ab_controller = Addressbook_Controller_Contact::getInstance();
        $ab_controller->assertPublicUsage();
        foreach ($events as $event) {
            Tinebase_CustomField::getInstance()->resolveRecordCustomFields($event);
            $converter = Tinebase_Convert_Factory::factory($event);
            $event = $converter->fromTine20Model($event);
            $title = $event['name'];
            $description = $event['description'] ?? '';
            try {
                $location = $ab_controller->get($event['location']);
                $location = implode(', ', array_filter([
                    $location['n_fileas'] ?? '',
                    trim(($location['adr_one_street'] ?? '') . ' ' . ($location['adr_one_street2'] ?? '')),
                    trim(($location['adr_one_postalcode'] ?? '') . ' ' . ($location['adr_one_locality'] ?? ''))
                ]));
            } catch (Tinebase_Exception_NotFound $tenf) {
                if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) {
                    Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__
                        . ' Location (ID: ' . $event['location'] . ') of task ' . $event['id'] . ' not found!');
                }
                continue;
            }
            $start = $event['start'];
            $end = $event['end'];
            $type = EventManager_Config::getInstance()
                ->get(EventManager_Config::EVENT_TYPE)->records->getById($event['type'])->value;
            $type = $translate->_($type);
            $status = EventManager_Config::getInstance()
                ->get(EventManager_Config::EVENT_STATUS)->records->getById($event['status'])->value;
            $status = $translate->_($status);
            $fee = $event['fee'];
            $total_places = $event['total_places'];
            $booked_places = $event['booked_places'];
            $available_places = $event['available_places'];
            $registration_possible_until = $event['registration_possible_until'];
            $last_modified_time = $event['last_modified_time'] ?? $event['creation_time'];
            $event_id = $event['id'];
            $url = Tinebase_Core::getUrl() . '/EventManager/view/#/event/' . $event_id;

            $feed->entry([
                'title' => $title,
                'description' => $description,
                'start' => $start,
                'end' => $end,
                'location' => $location,
                'type' => $type,
                'status' => $status,
                'fee' => $fee,
                'total_places' => $total_places,
                'booked_places' => $booked_places,
                'available_places' => $available_places,
                'registration_possible_until' => $registration_possible_until,
                'pubDate' => $last_modified_time,
                'link' => $url,
            ]);
        }
        return $feed;
    }
}
