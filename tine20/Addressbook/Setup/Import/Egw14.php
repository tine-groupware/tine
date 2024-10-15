<?php
/**
 * Tine 2.0
 * 
 * @package     Addressbook
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2009-2024 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * class to import addressbooks/contacts from egw14
 * 
 * 
 * @package     Addressbook
 * @subpackage  Setup
 */
class Addressbook_Setup_Import_Egw14 extends Tinebase_Setup_Import_Egw14_Abstract
{
    protected $_appname = 'Addressbook';
    protected $_egwTableName = 'egw_addressbook';
    protected $_egwOwnerColumn = 'contact_owner';
    protected $_defaultContainerConfigProperty = Addressbook_Preference::DEFAULTADDRESSBOOK;
    protected $_tineRecordModel = 'Addressbook_Model_Contact';
    protected $_tineRecordBackend = NULL;
    
    /**
     * country mapping
     * 
     * @var array
     */
    protected $_countryMapping = array(
        "BELGIEN" => "BE",
        "BULGARIEN" => "BG",
        "DEUTSCHLAND" => "DE",
        "FRANKREICH" => "FR",
        "GERMANY" => "DE",
        "GREAT BRITAIN" => "GB",
        "VEREINIGTES KÖNIGREICH" => "GB",
        "IRELAND" => "IE",
        "JAPAN" => "JP",
        "LUXEMBURG" => "LU",
        "NEW ZEALAND" => "NZ",
        "NIEDERLANDE" => "NL",
        "ÖSTERREICH" => "AT",
        "SCHWEIZ" => "CH",
        "SLOVAKEI" => "SK",
        "SPANIEN" => "ES",
        "SWEDEN" => "SE",
        "SCHWEDEN" => "SE",
        "USA" => "US",
        "VEREINIGTE STAATEN VON AMERIKA" => "US",
        "TÜRKEI" => "TR",
        "ITALIEN" => "IT",
        "SAUDI ARABIEN" => "SA",
        "FINLAND" => "FI",
        "CHINA" => "CN",
        "TUNISIEN" => "TN",
        "TUNESIEN" => "TN",
        "HONG KONG" => "HK",
        "TSCHECHISCHE REPUBLIK" => "CZ",
        "SÜDAFRIKA" => "ZA",
        "KANADA" => "CA",
        "POLEN" => "PL",
        "PORTUGAL" => "PT",
        "LIECHTENSTEIN" => "LI",
        "RUSSISCHE FÖDERATION" => "RU",
        "UNGARN" => "HU",
        "OMAN" => "OM",
        "AUSTRALIEN" => "AU",
        "SLOWAKEI" => "SK",
        "VEREINIGTEN ARABISCHEN EMIRATE" => "AE",
        "BOLIVIEN" => "BO",
        "WEISSRUSSLAND (BELORUSSLAND)" => "BY",
        "RUMÄNIEN" => "RO",
        "LETTLAND" => "LV",
        "SLOWENIEN" => "SI",
        "INDIEN" => "IN",
        "MAZEDONIEN, FRÜHERE JUGOSLAVISCHE REPUBLIK" => "MK",
        "ISRAEL" => "IL",
        "MONACO" => "MC",
        "ARGENTIEN" => "AR",
        "ARGENTINIEN" => "AR",
        "DÄNEMARK" => "DK",
        "MAROCCO" => "MA",
        "JORDANIEN" => "JO",
        "BRASILIEN" => "BR",
        "DJIBOUTI" => "DJ",
        "KOREA" => "KR",
        "KOREA REPUBLIC OF" => "KR",
        "NORWEGEN" => "NO",
        "ÄGYPTEN" => "EG",
        "GRIECHENLAND" => "GR",
        "ARMENIEN" => "AM",
        "KROATIEN" => "HR",
        "MAURITIUS" => "MU",
        "FAROE INSELN" => "FO",
        "ZYPERN" => "CY",
        "IRAQ" => "IQ",
        "BELIZE" => "BZ",
        "PERU" => "PE",
        "LIBANON" => "LB",
        "MEXICO" => "MX",
        "QATAR" => "QA",
        "SUDAN" => "SD",
        "VIETNAM" => "VN",
        "IRAN, ISLAMISCHE REPUBLIC" => "IR",
        "UKRAINE" => "UA",
        "PARAGUAY" => "PY",
        "ISLAND" => "IS",
        "SERBIA" => "RS",
        "KOSOVO" => "XK",
        "WEIHNACHTS INSEL" => "CX",
        "THAILAND" => "TH",
        "PHILIPPINEN" => "PH",
        "ASERBAIDSCHAN" => "AZ",
        "BOSNIEN UND HERZEGOVINA" => "BA",
        "ARUBA" => "AW",
        "BENIN" => "BJ",
        "UNITED STATES MINOR OUTLYING ISLANDS" => "UM",
        "LITAUEN" => "LT",
        "GEORGIEN" => "GE",
        "SINGAPUR" => "SG",
        "ALBANIEN" => "AL",
        "KUWAIT" => "KW",
        "JAMAICA" => "JM",
        "ALGERIEN" => "DZ",
        "PAKISTAN" => "PK",
        "SRI LANKA" => "LK",
        "CHILE" => "CL",
        "ESTONIEN" => "EE",
        "MALAYSIA" => "MY",
        "KOLUMBIEN" => "CO",
        "COSTA RICA" => "CR",
        "NEUSEELAND" => "NZ",
        "SYRIEN, ARABISCHE REPUBLIK" => "SY",
        "MALTA" => "MT",
        "NEPAL" => "NP",
    );
    
    /**
     * do the import 
     */
    public function import()
    {
        $this->_log->notice(__METHOD__ . '::' . __LINE__ . ' starting egw import for Adressbook');
        
        $this->_migrationStartTime = Tinebase_DateTime::now();
        $this->_tineRecordBackend = new Addressbook_Backend_Sql();

        $page = 1;
        $pageSize = 100;
        $estimate = $this->_getEgwRecordEstimate();
        $numPages = ceil($estimate/$pageSize);
        $this->_log->notice(__METHOD__ . '::' . __LINE__
            . " found {$estimate} total contacts for migration ({$numPages} pages)");

        // for testing
        # $page = $numPages = 13;

        for (; $page <= $numPages; $page++) {
            $this->_log->info(__METHOD__ . '::' . __LINE__ . " starting migration page {$page} of {$numPages}");
            
            Tinebase_Core::setExecutionLifeTime($pageSize*10);
            
            $recordPage = $this->_getRawEgwRecordPage($page, $pageSize);
            $this->_migrateEgwRecordPage($recordPage);
        }
        
        $this->_log->notice(__METHOD__ . '::' . __LINE__ . ' ' . ($this->_importResult['totalcount']
                - $this->_importResult['failcount']) . ' contacts imported sucessfully '
            . ($this->_importResult['failcount'] ? " {$this->_importResult['failcount']} contacts skipped with failures" : ""));
    }
    
    /**
     * @TODO: egw can have groups as owner 
     * -> map this to shared folder
     * -> find appropriate creator / current_user for this
     * -> support maps for group => creator and owner => folder?
     */
    protected function _migrateEgwRecordPage($recordPage)
    {
        foreach ($recordPage as $egwContactData) {
            try {
                $this->_importResult['totalcount']++;
                $currentUser = Tinebase_Core::get(Tinebase_Core::USER);
                $owner = $egwContactData['contact_owner'] ? Tinebase_User::getInstance()->getFullUserById($this->mapAccountIdEgw2Tine($egwContactData['contact_owner'])) : $currentUser;
                Tinebase_Core::set(Tinebase_Core::USER, $owner);
                
                $contactData = array_merge($egwContactData, array(
                    'id'                    => $egwContactData['contact_id'],
                    'creation_time'         => $this->convertDate($egwContactData['contact_created']),
                    'created_by'            => $this->mapAccountIdEgw2Tine($egwContactData['contact_creator'], FALSE),
                    'last_modified_time'    => $egwContactData['contact_modified'] ? $this->convertDate($egwContactData['contact_modified']) : NULL,
                    'last_modified_by'      => $egwContactData['contact_modifier'] ? $this->mapAccountIdEgw2Tine($egwContactData['contact_modifier'], FALSE) : NULL,
                ));
                
                $contactData['created_by'] = $contactData['created_by'] ?: $owner;
                $contactData['$egwContactData'] = $contactData['last_modified_time'] && !$contactData['last_modified_by'] ?: $owner;
                
                // fix mandentory fields
                if (! ($egwContactData['org_name'] || $egwContactData['n_family'])) {
                    $contactData['org_name'] = 'N/A';
                }
                
                // add 'http://' if missing
                foreach(array('contact_url', 'contact_url_home') as $urlProperty) {
                    if ( !preg_match("/^http/i", $egwContactData[$urlProperty]) && !empty($egwContactData[$urlProperty]) ) {
                        $contactData[$urlProperty] = "http://" . $egwContactData[$urlProperty];
                    }
                }
                
                // normalize countynames
                $contactData['adr_one_countryname'] = $this->convertCountryname2Iso($egwContactData['adr_one_countryname']);
                $contactData['adr_two_countryname'] = $this->convertCountryname2Iso($egwContactData['adr_two_countryname']);
                
                // handle bday
                if ((isset($egwContactData['contact_bday']) || array_key_exists('contact_bday', $egwContactData))
                    && $egwContactData['contact_bday'] && $egwContactData['contact_bday'] !== 'NULL') {
                    // @TODO evaluate contact_tz
                    $contactData['bday'] = new Tinebase_DateTime($egwContactData['contact_bday'], $this->_config->birthdayDefaultTimezone);
                } else if ((isset($egwContactData['bday']) || array_key_exists('bday', $egwContactData)) && $egwContactData['bday']) {
                    // egw <= 1.4
                    $contactData['bday'] = $this->convertDate($egwContactData['bday']);
                }
                
                // handle tags
                $contactData['tags'] = $this->convertCategories($egwContactData['cat_id']);
                
                // @TODO handle photo
                
                // handle container
                if ($egwContactData['contact_owner'] && ! $egwContactData['account_id']) {
                    $contactData['container_id'] = $egwContactData['contact_private'] && $this->_config->setPersonalContainerGrants ? 
                        $this->getPrivateContainer($this->mapAccountIdEgw2Tine($egwContactData['contact_owner']))->getId() :
                        $this->getPersonalContainer($this->mapAccountIdEgw2Tine($egwContactData['contact_owner']))->getId();
                }

                $contactData['note'] = $this->_getInfoLogData($egwContactData['contact_id'], 'addressbook');
                $contactData['tags'] = array_merge($contactData['tags'],
                    $this->_getInfoLogTags($egwContactData['contact_id'], 'addressbook'));
                $contactData['customfields'] = $this->_getCustomFields($egwContactData['contact_id']);
                
                // finally create the record
                $tineContact = new Addressbook_Model_Contact($contactData);
                $this->saveTineRecord($tineContact);
                
            } catch (Exception $e) {
                $this->_importResult['failcount']++;
                Tinebase_Core::set(Tinebase_Core::USER, $currentUser);
                if (!Tinebase_Exception::isDbDuplicate($e)) {
                    $this->_log->err(__METHOD__ . '::' . __LINE__ . ' could not migrate contact "'
                        . $egwContactData['contact_id'] . '" cause: ' . $e->getMessage());
                }
                $this->_log->debug(__METHOD__ . '::' . __LINE__ . ' ' . $e);
            }
        }
    }
    
    /**
     * save tine20 record to db
     * 
     * @param Tinebase_Record_Interface $record
     */
    public function saveTineRecord(Tinebase_Record_Interface $record)
    {
        $savedRecord = null;
        if (! $record->account_id) {
            $savedRecord = $this->_tineRecordBackend->create($record);
        } else if ($this->_config->updateAccountRecords) {
            $accountId = $this->mapAccountIdEgw2Tine($record->account_id);
            $account = Tinebase_User::getInstance()->getUserById($accountId);
            
            if (! ($account && $account->contact_id)) {
                $this->_log->warn(__METHOD__ . '::' . __LINE__
                    . " could not migrate account contact for {$record->n_fn} - no contact found");
                return;
            }

            try {
                $contact = $this->_tineRecordBackend->get($account->contact_id);
            } catch (Tinebase_Exception_NotFound $tenf) {
                $this->_log->warn(__METHOD__ . '::' . __LINE__
                    . " could not migrate account contact for {$record->n_fn} - no contact found");
                return;
            }
            $record->setId($account->contact_id);
            $record->container_id = $contact->container_id;
            
            $savedRecord = $this->_tineRecordBackend->update($record);
        }
        
        if ($savedRecord) {
            $this->attachTags($record->tags, $savedRecord->getId());
        }
    }
    
    /**
     * get iso code of localised country name
     * 
     * @TODO iterate zend_translate
     */
    public function convertCountryname2Iso($countryname)
    {
        // normalize empty
        if (!$countryname) return NULL;
        
        $countryname = strtoupper(trim($countryname));
        
        if (! (isset($this->_countryMapping[$countryname]) || array_key_exists($countryname, $this->_countryMapping))) {
            $this->_log->warn(__METHOD__ . '::' . __LINE__ . " could not get country code for {$countryname}");
            
            return NULL;
        }
        
        return $this->_countryMapping[$countryname];
    }

    /**
     * @param int $recordId
     * @return array
     *
     * TODO support owner?
     */
    protected function _getCustomFields(int $recordId): array
    {
        $config = $this->_getEgwCustomFieldConfig('addressbook');

        // TODO get label from config.ini & make this optional
        $config['info_startdate'] = [
            'type' => 'date',
            'label' => 'Wiedervorlage',
        ];

        $select = $this->_egwDb->select()
            ->from('egw_addressbook_extra')
            ->where($this->_egwDb->quoteInto($this->_egwDb->quoteIdentifier('contact_id') . ' = ?', $recordId));
        $egwCustomfieldData = $this->_egwDb->fetchAll($select, null, Zend_Db::FETCH_ASSOC);

        // also add info_startdate from infologs
        // TODO add config for this
        // TODO handle multiple occurrences?
        // TODO fix TZ (egw data is in UTC, tine expects date/00:00:00)
        $infologs = $this->_fetchInfoLogs($recordId, 'addressbook');
        foreach ($infologs as $infolog) {
            if (! empty($infolog['info_startdate']) && $infolog['info_status'] === 'ongoing') {
                $egwCustomfieldData[] = [
                    'contact_name' => 'info_startdate',
                    'contact_value' => new Tinebase_DateTime('@' . $infolog['info_startdate']),
                ];
            }
        }

        $result = [];
        foreach ($egwCustomfieldData as $egwCF) {
            $name = $egwCF['contact_name'];
            $this->_createCustomFieldIfMissing($name, $config[$name] ?? []);
            $result[$name] = $egwCF['contact_value'];
        }

        return $result;
    }

    protected function _createCustomFieldIfMissing(string $name, array $config = []): void
    {
        $application = Tinebase_Application::getInstance()->getApplicationByName('Addressbook');
        $cf = Tinebase_CustomField::getInstance()->getCustomFieldByNameAndApplication($application->getId(),
                $name, Addressbook_Model_Contact::class);
        if (! $cf) {
            $additionalDefinition = [];
            if (isset($config['type'])) {
                switch($config['type']) {
                    case 'select':
                        $type = 'keyField';
                        $records = [];
                        foreach ($config['values'] as $key => $value) {
                            $records[] = [
                                'id' => $key,
                                'value' => $value,
                            ];
                        }
                        $additionalDefinition = [
                            'keyFieldConfig' => [
                                'value' => [
                                    'records' => $records,
                                ],
                            ],
                        ];
                        break;
                    default:
                        $type = $config['type'];
                }
            } else {
                $type = 'string';
            }

            $cfc = array(
                'name' => $name,
                'application_id' => $application->getId(),
                'model' => Addressbook_Model_Contact::class,
                'definition' => array_merge([
                    'label' => $config['label'] ?? ucfirst($name),
                    'uiconfig' => $config['uiconfig'] ?? null,
                    'type' => $type,
                ], $additionalDefinition),
            );
            $cf = new Tinebase_Model_CustomField_Config($cfc);
            Tinebase_CustomField::getInstance()->addCustomField($cf);
            $this->_log->notice(__METHOD__ . '::' . __LINE__ . ' Added new customfield: "' . $name . '"');
        }
    }

    public function importMissingInfologs($dryRun = false)
    {
        $query = "select link_id2 as contact_id from egw_links where link_app2='addressbook' and link_app1='infolog' group by link_id2 having count(link_id2) > 1";
        $result1 = $this->_egwDb->fetchAll($query);
        echo "Found: " . count($result1) ." adb links with multiple infologs\n";

        $query = "select link_id1 as contact_id from egw_links where link_app1='addressbook' and link_app2='infolog'";
        $result2 = $this->_egwDb->fetchAll($query);
        echo "Found: " . count($result2) ." reversed adb links\n";

        $result = array_merge($result1, $result2);

        $updateCount = 0;
        $noteChangedCount = 0;
        $missingCount = 0;
        foreach ($result as $record) {
            $contactId = $record['contact_id'];
            // print_r($record);

            try {
                // check contact
                // add all infologs to contact if note not changed
                // only add missing infologs to existing note
                $contact = Addressbook_Controller_Contact::getInstance()->getBackend()->get($contactId);
                // print_r($contact->toArray());

                // find out if note has changed since the import
                $notes = Tinebase_Notes::getInstance()->getNotesOfRecord(Addressbook_Model_Contact::class, $contactId, _onlyNonSystemNotes: false);
                $changed = false;

                if (count($notes) > 0) {
                    // check for changed notes & set $changed
                    foreach ($notes->filter('note_type_id', 'changed') as $note) {
                        if (str_contains($note->note, 'Notiz')) {
                            $changed = true;
                            $noteChangedCount++;
                            break;
                        }
                    }
                }

                $infoLog = $this->_getInfoLogData($contactId, 'addressbook', $changed);

                if (! $dryRun) {
                    if ($changed) {
                        $contact->note = $contact->note . "\n" . $infoLog;
                    } else {
                        $contact->note = $infoLog;
                    }
                    Addressbook_Controller_Contact::getInstance()->getBackend()->update($contact);
                }
                // echo $infoLog;
                $updateCount++;
            } catch (Tinebase_Exception_NotFound $tenf) {
                if ($dryRun) {
                    echo "Contact not found: " . $contactId ."\n";
                }
                $missingCount++;
            }
        }

        echo "Updated $updateCount contacts\n";
        echo "Note already updated in $noteChangedCount contacts\n";
        echo "Did not find $missingCount contacts\n";
    }
}
