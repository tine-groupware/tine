<?php
/**
 * Event controller
 *
 * @package     EventManager
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2020 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * Event controller
 *
 * @package     EventManager
 * @subpackage  Controller
 */
class EventManager_Controller_Registration extends Tinebase_Controller_Record_Abstract
{
    use Tinebase_Controller_SingletonTrait;

    /**
     * the constructor
     *
     * don't use the constructor. use the singleton
     */
    protected function __construct()
    {
        $this->_applicationName = EventManager_Config::APP_NAME;
        $this->_modelName = EventManager_Model_Registration::class;
        $this->_backend = new Tinebase_Backend_Sql([
            Tinebase_Backend_Sql::MODEL_NAME    => EventManager_Model_Registration::class,
            Tinebase_Backend_Sql::TABLE_NAME    => EventManager_Model_Registration::TABLE_NAME,
            Tinebase_Backend_Sql::MODLOG_ACTIVE => false
        ]);

        $this->_purgeRecords = false;
        $this->_doContainerACLChecks = false;
    }

    /**
     * overwrite create function from Tinebase_Controller_Record_Abstract to add custom fields
     *
     * @param Tinebase_Record_Interface $_record
     * @param boolean $_duplicateCheck
     * @return  Tinebase_Record_Interface
     * @throws  Tinebase_Exception_AccessDenied
     */
    public function create(Tinebase_Record_Interface $_record, $_duplicateCheck = true)
    {
        $record = parent::create($_record, $_duplicateCheck);
        foreach ($record->booked_options as $booked_option) {
            if ($booked_option->selection_config->booked) {
                if (
                     isset($booked_option->option->option_config->available_places)
                    && isset($booked_option->option->option_config->booked_places)
                ) {
                    $booked_option->option->option_config->booked_places++;
                    $booked_option->option->option_config->available_places--;
                    EventManager_Controller_Option::getInstance()->update($booked_option->option);
                }
            }
        }
        return $record;
    }

    /**
     * overwrite create function from Tinebase_Controller_Record_Abstract to update custom fields
     *
     * @param Tinebase_Record_Interface $_record
     * @param boolean $_duplicateCheck
     * @param boolean $_updateDeleted
     * @return  Tinebase_Record_Interface
     * @throws  Tinebase_Exception_AccessDenied
     *
     */
    public function update(Tinebase_Record_Interface $_record, $_duplicateCheck = true, $_updateDeleted = false)
    {
        $record_old = $this->get($_record->getId());
        $record = parent::update($_record, $_duplicateCheck, $_updateDeleted);
        $still_existing = [];
        $already_existing = false;
        if (!$record->booked_options) {
            $record->booked_options = [];
        }
        foreach ($record->booked_options as $booked_option) {
            if (
                isset($booked_option->option->option_config->available_places)
                && isset($booked_option->option->option_config->booked_places)
                && isset($booked_option->id)
                && isset($booked_option->selection_config)
            ) {
                // Value of existing booking has changed
                foreach ($record_old->booked_options as $old_booked_option) {
                    if ($old_booked_option->id == $booked_option->id) {
                        $old_booked = $old_booked_option->selection_config->booked;
                        $booked = $booked_option->selection_config->booked;
                        if ($booked !== $old_booked) {
                            if ($booked) {
                                $booked_option->option->option_config->booked_places++;
                                $booked_option->option->option_config->available_places--;
                            } else {
                                $booked_option->option->option_config->booked_places--;
                                $booked_option->option->option_config->available_places++;
                            }
                            try {
                                EventManager_Controller_Option::getInstance()->update($booked_option->option);
                            } catch (Tinebase_Exception_NotFound $tenf) {
                                if (Tinebase_Core::isLogLevel(Zend_Log::WARN)) {
                                    Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__
                                        . ' ' . $tenf->getMessage());
                                }
                            }
                        }
                        $already_existing = true;
                        $still_existing[] = $old_booked_option;
                    }
                }
                // New booking was added
                if (!$already_existing && $booked_option->selection_config->booked) {
                    if (!is_string($booked_option->option)) {
                        $booked_option->option->option_config->booked_places++;
                        $booked_option->option->option_config->available_places--;
                        try {
                            EventManager_Controller_Option::getInstance()->update($booked_option->option);
                        } catch (Tinebase_Exception_NotFound $tenf) {
                            if (Tinebase_Core::isLogLevel(Zend_Log::WARN)) {
                                Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__
                                    . ' ' . $tenf->getMessage());
                            }
                        }
                    }
                }
            }
        }

        // Booked option was deleted
        foreach ($record_old->booked_options as $old_booked_option) {
            if (!array_key_exists($old_booked_option->option->id, $still_existing)) {
                try {
                    $option = EventManager_Controller_Option::getInstance()->get($old_booked_option->option->id);
                } catch (Tinebase_Exception_NotFound $tenf) {
                    if (Tinebase_Core::isLogLevel(Zend_Log::WARN)) {
                        Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__
                            . ' ' . $tenf->getMessage());
                    }
                    continue;
                }
                if (
                    !empty($option->getData())
                    && isset($option->option_config->available_places)
                    && isset($option->option_config->booked_places)
                    && !$already_existing
                ) {
                    $option->option_config->booked_places--;
                    $option->option_config->available_places++;
                    EventManager_Controller_Option::getInstance()->update($option);
                }
            }
        }
        return $record;
    }

    /**
     * Deletes a set of records.
     *
     * If one of the records could not be deleted, no record is deleted
     *
     * @param  array|Tinebase_Record_Interface|Tinebase_Record_RecordSet $_ids array of record identifiers
     * @return Tinebase_Record_RecordSet
     * @throws Exception
     */
    public function delete($_ids)
    {
        $records = parent::delete($_ids);
        foreach ($records as $record) {
            foreach ($record->booked_options as $booked_option) {
                if (
                    isset($booked_option->option->option_config->available_places)
                    && isset($booked_option->option->option_config->booked_places)
                ) {
                    $booked_option->option->option_config->booked_places--;
                    $booked_option->option->option_config->available_places++;
                    EventManager_Controller_Option::getInstance()->update($booked_option->option);
                }
            }
        }
        return $records;
    }

    public function publicApiPostRegistration($eventId) // todo: do this right
    {
        $assertAclUsage = $this->assertPublicUsage();

        try {
            $request = json_decode(Tinebase_Core::get(Tinebase_Core::REQUEST)->getContent(), true);
            $response = new \Laminas\Diactoros\Response();
            $contact = new Addressbook_Model_Contact([
                'adr_one_countryname'   => $request['country'],
                'adr_one_locality'      => $request['city'],
                'adr_one_postalcode'    => $request['postalCode'],
                'adr_one_region'        => $request['region'],
                'adr_one_street'        => $request['street'],
                'adr_one_street2'       => $request['houseNumber'],
                'bday'                  => $request['birthday'], //'1975-01-02 03:04:05', // new Tinebase_DateTime???
                'email'                 => $request['email'],
                'title'                 => $request['title'],
                'n_family'              => $request['lastName'],
                //'n_fileas'              => 'Kneschke, Lars',
                'n_given'               => $request['firstName'],
                'n_middle'              => $request['middleName'],
                'n_prefix'              => $request['salutation'],
                'org_name'              => $request['company'],
                'tel_cell'              => $request['mobile'],
                //'tel_cell_private'      => '+49TELCELLPRIVATE',
                'tel_home'              => $request['telephone'],
                //'tel_work'              => '+49TELWORK',
            ]);
            $contact = Addressbook_Controller_Contact::getInstance()->create($contact);
            $registration = new EventManager_Model_Registration([
                'event_id' => EventManager_Controller_Event::getInstance()->get($eventId),
                'name' => $contact,
                'function' => EventManager_Config::getInstance()->get(EventManager_Config::REGISTRATION_FUNCTION, 'Attendee'),
                'source' => EventManager_Config::getInstance()->get(EventManager_Config::REGISTRATION_SOURCE, 'Online'),
                'status' => EventManager_Config::getInstance()->get(EventManager_Config::REGISTRATION_STATUS, 'Waiting list'),
                'booked_options' => $request['bookedOptions'], //todo: registration answers here in the correct selection_config
                'description' => $request['description'],
            ], true);
            $registration = EventManager_Controller_Registration::getInstance()->create($registration);
            $response->getBody()->write(json_encode($registration->toArray()));
        } catch (Tinebase_Exception_Record_Validation $terv) {
            $response = new \Laminas\Diactoros\Response('php://memory', 404);
            $response->getBody()->write(json_encode($terv->getMessage()));
        } catch (Tinebase_Exception_NotFound $tenf) {
            $response = new \Laminas\Diactoros\Response('php://memory', 404);
            $response->getBody()->write(json_encode($tenf->getMessage()));
        } catch (Tinebase_Exception_Record_NotAllowed $terna) {
            $response = new \Laminas\Diactoros\Response('php://memory', 401);
            $response->getBody()->write(json_encode($terna->getMessage()));
        } finally {
            $assertAclUsage();
        }
        return $response;
    }

}
