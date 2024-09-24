<?php
/**
 * Tine 2.0
 * @package     Addressbook
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2008-2019 Metaways Infosystems GmbH (http://www.metaways.de)
 * 
 */

/**
 * cli server for addressbook
 *
 * This class handles cli requests for the addressbook
 *
 * @package     Addressbook
 */
class Addressbook_Frontend_Cli extends Tinebase_Frontend_Cli_Abstract
{
    /**
     * the internal name of the application
     *
     * @var string
     */
    protected $_applicationName = 'Addressbook';
    
    /**
     * import config filename
     *
     * @var string
     */
    protected $_configFilename = 'importconfig.inc.php';
    /**
     * import demodata default definitions
     *
     * @var array
     */
    protected $_defaultDemoDataDefinition = [
        'Addressbook_Model_Contact' => 'adb_tine_import_csv'
    ];

    /**
     * help array with function names and param descriptions
     */
    protected $_help = array(
        'import' => array(
            'description'   => 'Import new contacts into the addressbook.',
            'params'        => array(
                'filenames'   => 'Filename(s) of import file(s) [required]',
                'definition'  => 'Name of the import definition or filename [required] -> for example admin_user_import_csv(.xml)',
            )
        ),
        'export' => array(
            'description'   => 'Exports contacts as csv data to stdout',
            'params'        => array(
                'addressbookId' => 'only export contcts of the given addressbook',
                'tagId'         => 'only export contacts having the given tag'
            )
        ),
        'syncbackends' => array(
            'description'   => 'Syncs all contacts to the sync backends',
            'params'        => array(),
        ),
        'resetAllSyncBackends' => array(
            'description'   => 'resets the syncbackendids property of all contacts. This does NOT delete the contacts in the syncbackend. Only the sync state will be reset. Be aware of side effects when resyncing!',
            'params'        => array(),
        )
    );

    public function resetAllSyncBackends()
    {
        $this->_checkAdminRight();

        (new Addressbook_Backend_Sql())->resetAllSyncBackendIds();
    }

    public function syncbackends($_opts)
    {
        $sqlBackend = new Addressbook_Backend_Sql();
        $controller = Addressbook_Controller_Contact::getInstance();
        $syncBackends = $controller->getSyncBackends();

        foreach ($sqlBackend->getAll() as $contact) {
            $oldRecordBackendIds = $contact->syncBackendIds;
            if (is_string($oldRecordBackendIds)) {
                $oldRecordBackendIds = explode(',', $contact->syncBackendIds);
            } else {
                $oldRecordBackendIds = array();
            }

            $updateSyncBackendIds = false;
            
            foreach($syncBackends as $backendId => $backendArray)
            {
                if (isset($backendArray['filter'])) {
                    $oldACL = $controller->doContainerACLChecks(false);

                    $filter = new Addressbook_Model_ContactFilter($backendArray['filter']);
                    $filter->addFilter(new Addressbook_Model_ContactIdFilter(
                        array('field' => $contact->getIdProperty(), 'operator' => 'equals', 'value' => $contact->getId())
                    ));

                    if ($controller->searchCount($filter) !== 1) {

                        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                            . ' record did not match filter of syncBackend "' . $backendId . '"');

                        // record is stored in that backend, so we remove it from there
                        if (in_array($backendId, $oldRecordBackendIds)) {

                            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                                . ' deleting record from syncBackend "' . $backendId . '"');

                            try {
                                $backendArray['instance']->delete($contact);

                                $contact->syncBackendIds = trim(preg_replace('/(^|,)' . $backendId . '($|,)/', ',', $contact->syncBackendIds), ',');

                                $updateSyncBackendIds = true;
                            } catch (Exception $e) {
                                Tinebase_Core::getLogger()->err(__METHOD__ . '::' . __LINE__ . ' could not delete record from sync backend "' .
                                    $backendId . '": ' . $e->getMessage());
                                Tinebase_Exception::log($e, false);
                            }
                        }

                        $controller->doContainerACLChecks($oldACL);

                        continue;
                    }
                    $controller->doContainerACLChecks($oldACL);
                }

                // if record is in this syncbackend, update it
                if (in_array($backendId, $oldRecordBackendIds)) {

                    if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                        . ' update record in syncBackend "' . $backendId . '"');

                    try {
                        $backendArray['instance']->update($contact);
                    } catch (Exception $e) {
                        Tinebase_Core::getLogger()->err(__METHOD__ . '::' . __LINE__ . ' could not update record in sync backend "' .
                            $backendId . '": ' . $e->getMessage());
                        Tinebase_Exception::log($e, false);
                    }

                    // else create it
                } else {

                    if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                        . ' create record in syncBackend "' . $backendId . '"');

                    try {
                        $backendArray['instance']->create($contact);

                        $contact->syncBackendIds = (empty($contact->syncBackendIds)?'':$contact->syncBackendIds . ',') . $backendId;

                        $updateSyncBackendIds = true;
                    } catch (Exception $e) {
                        Tinebase_Core::getLogger()->err(__METHOD__ . '::' . __LINE__ . ' could not create record in sync backend "' .
                            $backendId . '": ' . $e->getMessage());
                        Tinebase_Exception::log($e, false);
                    }
                }
            }

            if (true === $updateSyncBackendIds) {
                $sqlBackend->updateSyncBackendIds($contact->getId(), $contact->syncBackendIds);
            }
        }
    }

    /**
     * import contacts
     *
     * @param Zend_Console_Getopt $_opts
     */
    public function import($_opts)
    {
        parent::_import($_opts);
    }

    /**
     * exports contacts as VCARDs / VCF file
     * examples:
     *      --method Addressbook.exportVCard -- container_id=CONTAINER_ID filename=/my/export/file.vcf
     *      --method Addressbook.exportVCard -- container_id=CONTAINER_ID stdout=1
     *      --method Addressbook.exportVCard -- type=personal
     *
     * @param $_opts
     * @return boolean
     */
    public function exportVCard($_opts)
    {
        return $this->_exportVObject($_opts, Addressbook_Model_Contact::class, Addressbook_Export_VCard::class);
    }

    /**
     * export contacts csv to STDOUT
     * 
     * NOTE: exports contacts in container id 1 by default. id needs to be changed in the code.
     *
     * //@ param Zend_Console_Getopt $_opts
     * 
     * @todo allow to pass container id (and maybe more filter options) as param
     */
    public function export(/*$_opts*/)
    {
        $containerId = 1;
        
        $filter = new Addressbook_Model_ContactFilter(array(
            array('field' => 'container_id',     'operator' => 'equals',   'value' => $containerId     )
        ));

        $csvExporter = new Addressbook_Export_Csv($filter, null, array('toStdout' => true));
        
        $csvExporter->generate();
    }

    /**
     * remove autogenerated contacts
     *
     * @param Zend_Console_Getopt $opts
     *
     * @throws Addressbook_Exception
     * @throws Tinebase_Exception_InvalidArgument
     * @todo use OR filter for different locales
     */
    public function removeAutogeneratedContacts($opts)
    {
        if (! Tinebase_Application::getInstance()->isInstalled('Calendar')) {
            throw new Addressbook_Exception('Calendar application not installed');
        }
        
        $params = $this->_parseArgs($opts);
        
        $languages = isset($params['languages']) ? $params['languages'] : array('en', 'de');
        
        $contactBackend = new Addressbook_Backend_Sql();
        
        foreach ($languages as $language) {
            $locale = new Zend_Locale($language);
            
            $translation = Tinebase_Translation::getTranslation('Calendar', $locale);
            // search all contacts with note "This contact has been automatically added by the system as an event attender"
            $noteFilter = new Addressbook_Model_ContactFilter(array(
                array('field' => 'note', 'operator' => 'equals', 'value' => 
                    $translation->_('This contact has been automatically added by the system as an event attender')),
            ));
            $contactIdsToDelete = $contactBackend->search($noteFilter, null, Tinebase_Backend_Sql_Abstract::IDCOL);
            
            if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
                    . " About to delete " . count($contactIdsToDelete) . ' contacts ...');
            
            $number = $contactBackend->delete($contactIdsToDelete);
            
            if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
                    . " Deleted " . $number . ' autogenerated contacts for language ' . $language);
        }
    }

    /**
     * update geodata - only updates addresses without geodata for adr_one
     *
     * opts: "tag" update only contact with tagging
     *       "containerId" update only contacts in defined container
     *
     * @param Zend_Console_Getopt $opts
     * @throws Tinebase_Exception_InvalidArgument
     * @throws Tinebase_Exception_Record_DefinitionFailure
     * @throws Tinebase_Exception_Record_Validation
     */
    public function updateContactGeodata($opts)
    {
        $params = $this->_parseArgs($opts);

        $filter = [
            ['field' => 'adr_one_lon', 'operator' => 'isnull', 'value' => null]
        ];

        if (isset($params['containerId'])) {
            $filter[] = ['field' => 'container_id', 'operator' => 'equals', 'value' => $params['containerId']];
        }

        if (isset($params['tag'])) {
            $tag = Tinebase_Tags::getInstance()->searchTags(
                new Tinebase_Model_TagFilter(array(
                    'name' => $params['tag'],
                    'application' => $this->_applicationName,
                ))
            );
            $filter[] = ['field' => 'tag', 'operator' => 'equals', 'value' => $tag->getId()];
        }

        $filter = new Addressbook_Model_ContactFilter($filter);
        $oldACL = Addressbook_Controller_Contact::getInstance()->doContainerACLChecks(false);
        $records = Addressbook_Controller_Contact::getInstance()->search($filter);
        echo 'Found records: ' . $records->count() . "\n";
        Addressbook_Controller_Contact::getInstance()->setGeoDataForContacts(true);
        $result = Addressbook_Controller_Contact::getInstance()->updateMultiple($filter, array());
        echo 'Updated ' . $result['totalcount'] . ' Record(s)' . "\n";
        Addressbook_Controller_Contact::getInstance()->doContainerACLChecks($oldACL);
    }

    const ROLE_ID = 'roleId';
    const ROLE_NAME = 'roleName';

    public function setListRoleIdByName($opts)
    {
        $params = $this->_parseArgs($opts, [self::ROLE_ID, self::ROLE_NAME]);

        $listRoleBackend = new Tinebase_Backend_Sql([
            'modelName' => Addressbook_Model_ListRole::class,
            'tableName' => 'addressbook_list_role',
        ]);

        $roles = $listRoleBackend->search(new Addressbook_Model_ListRoleFilter([
            ['field' => 'name', 'operator' => 'equals', 'value' => $params[self::ROLE_NAME]],
        ]));

        if ($roles->count() === 0) {
            $listRoleBackend->create(new Addressbook_Model_ListRole([
                'id'    => $params[self::ROLE_ID],
                'name'  => $params[self::ROLE_NAME],
            ]));

            echo 'created role as it didn\'t exist yet' . PHP_EOL;
        } else {
            $db = Tinebase_Core::getDb();
            $roleWithId = $roles->find('id', $params[self::ROLE_ID]);
            if (null === $roleWithId) {
                $roleWithId = $roles->getFirstRecord();
                // there is a cascade foreign key constraint on membership => update is enough to get it done
                $db->update(SQL_TABLE_PREFIX . 'addressbook_list_role',
                    ['id' => $params[self::ROLE_ID]],
                    'id = ' . $db->quote($roleWithId->getId()));
                $roleWithId->setId($params[self::ROLE_ID]);
            }
            $roles->removeRecord($roleWithId);

            while ($roles->count() > 0) {
                $role = $roles->getFirstRecord();
                $roles->removeRecord($role);

                $db->update(SQL_TABLE_PREFIX . 'adb_list_m_role',
                    ['list_role_id' => $params[self::ROLE_ID]],
                    'list_role_id = ' . $db->quote($role->getId()));

                $listRoleBackend->delete($role->getId());
            }
        }
    }

    /**
     * delete duplicate contacts
     *  - allowed params:
     *      created_by=USER (equals)
     *      fields=FIELDS (equals)
     *      -d (dry run)
     *    e.g. php tine20.php --method=Addressbook.searchDuplicatesContactByUser -d created_by=test fields=n_fileas,adr_one_region
     *
     * @param Zend_Console_Getopt $opts
     * @return integer
     **/
    public function searchDuplicatesContactByUser($opts)
    {
        $be = new Addressbook_Backend_Sql;

        // @ToDo
        //$this->_addOutputLogWriter(6);

        $args = $this->_parseArgs($opts);
        if (isset($args['created_by'])) {
            $user = Tinebase_User::getInstance()->getUserByLoginName($args['created_by']);
        }else {
            return 1;
        }
        $filterData = array(array(
            'field' => 'created_by',
            'operator' => 'equals',
            'value' => $user->getId(),
        ));
        if (isset($args['container_id']))
        {
            $filterData[] = [
                'field' => 'container_id',
                'operator' => 'equals',
                'value' => $args['container_id'],
            ];
        }

        isset($args['fields']) ? $duplicateFields = $args['fields'] : $duplicateFields = array('n_fileas');

        $filter = new Addressbook_Model_ContactFilter($filterData);

        $be->deleteDuplicateRecords($filter, $duplicateFields, $opts->d);

        return 0;
    }

    /**
     * updates addressbook shared containers: set privateData grant for all!
     *
     * TODO generalize: give set of grants and allow to update containers of all models (move to Tinebase)
     *
     * @param Zend_Console_Getopt $opts
     * @throws Tinebase_Exception_AccessDenied
     * @throws Tinebase_Exception_Backend
     * @throws Tinebase_Exception_InvalidArgument
     * @throws Tinebase_Exception_NotFound
     * @throws Tinebase_Exception_SystemGeneric
     */
    public function setPrivateGrantForAll($opts)
    {
        $containerController = Tinebase_Container::getInstance();

        if ($opts->v) {
            print_r($opts->d ? '(DRYRUN) Setting private grants for:' . PHP_EOL : 'Setting private grants for:' . PHP_EOL);
        }

        $filter = [
            ['field' => 'type', 'operator' => 'equals', 'value' => Tinebase_Model_Container::TYPE_SHARED],
            ['field' => 'model', 'operator' => 'equals', 'value' => Addressbook_Model_Contact::class],
            ['field' => 'application_id', 'operator' => 'equals',
                'value' => Tinebase_Application::getInstance()->getApplicationByName('Addressbook')->getId()],
        ];
        $containerController->doSearchAclFilter(false);
        $containers = $containerController->search(
            new Tinebase_Model_ContainerFilter($filter)
        );
        $containerController->doSearchAclFilter(true);

        $counter = 0;
        foreach ($containers as $container) {
            $allgrants = $containerController->getGrantsOfContainer($container, true);
            $toUpdate = false;
            foreach ($allgrants as $grant) {
                if (!$grant->privateDataGrant) {
                    $toUpdate = true;
                    $grant->privateDataGrant = true;
                }
            }
            if ($toUpdate) {
                if ($opts->v) {
                    echo "- " . $container->name . PHP_EOL;
                }
                if (!$opts->d) {
                    $containerController->setGrants($container, $allgrants, TRUE);
                }
                $counter++;
            }
        }

        echo "Added privateData grant to $counter shared containers" . PHP_EOL;
    }


    /**
     * @param Zend_Console_Getopt $opts
     * $opts -> container_id, fields and sprecialChar
     * e.x -- --method=Addressbook.removeSpecialChar container_id='1234' fields='n_given,n_family' specialChar='\\'
     * all Contacts with '\' in n_given and n_family remove '\'
     * default fields n_given n_middle n_family
     * @throws Tinebase_Exception_AccessDenied
     * @throws Tinebase_Exception_InvalidArgument
     */
    public function removeSpecialChar(Zend_Console_Getopt $opts)
    {
        $args = $this->_parseArgs($opts);
        $filter = [];
        if (!isset($args['fields'])) {
            $args['fields'] = $fields = ['n_given', 'n_middle', 'n_family'];
        }

        if (isset($args['container_id'])) {
            $filter[] = [['field' => 'container_id', 'operator' => 'equals', 'value' => $args['container_id']]];
        }

        $fieldsFilter = [];
        foreach ($args['fields'] as $field) {
            $fieldsFilter[] = ['field' => $field, 'operator' => 'contains', 'value' => $args['specialChar']];
        }

        $filter[] = ['condition' => 'OR', 'filters' => $fieldsFilter];

        $filter = Tinebase_Model_Filter_FilterGroup::getFilterForModel(Addressbook_Model_Contact::class, $filter);
        $results = Addressbook_Controller_Contact::getInstance()->search($filter);

        foreach ($results as $record) {
            foreach ($args['fields'] as $field) {
                if (isset($record[$field])) {
                    $record[$field] = str_replace($args['specialChar'], '', $record[$field]);
                }
            }
            Addressbook_Controller_Contact::getInstance()->update($record);
        }

    }

    /**
     * usage: method=Addressbook.importMailList csv=test.csv domain=secondarydomains
     *csv file = "listname","listEmailAdress"
     * @param Zend_Console_Getopt $opts
     * @return false
     * @throws Tinebase_Exception_AccessDenied
     * @throws Tinebase_Exception_InvalidArgument
     */
    public function importMailList(Zend_Console_Getopt $opts)
    {
        $args = $this->_parseArgs($opts);
        $csv = $args['csv'];

        $config = Tinebase_Config::getInstance()->get(Tinebase_Config::SMTP)->toArray();
        $domain = isset($args['domain']) ? $config[$args['domain']]: $config['primarydomain'];
        if (!$csv) {
            return 2;
        }

        $stream = fopen($csv, 'r');
        if (!$stream) {
            echo "file could not be opened: " . $csv . "\n";
            return 2;
        }
        while ($line = fgetcsv($stream, 0, ',')) {
            $list = Addressbook_Controller_List::getInstance()->search(Tinebase_Model_Filter_FilterGroup::getFilterForModel(
                Addressbook_Model_List::class, [
                ['field' => 'name', 'operator' => 'equals', 'value' => $line[0],
            ]]))->getFirstRecord();

            if(!$list) {
                $list = Addressbook_Controller_List::getInstance()->create(new Addressbook_Model_List(
                    ['name' => $line[0]]));
            }

            if( !isset($list->email) || $list->email === '') {
                $list->email = $line[1] . '@' . $domain;
            }
            $list->list_type = 'MAILINGLIST';
            $list->xprops()[Addressbook_Model_List::XPROP_USE_AS_MAILINGLIST] = 1;
            Addressbook_Controller_List::getInstance()->update($list);
        }
        fclose($stream);
        return 0;
    }

    /**
     * @param Zend_Console_Getopt $opts
     * usage: method=Addressbook.importGroupMailingList csv="verteilerliste.csv" list="TestList" containerId="5c3906b5976985252145b25d7f6f4afe279d1993" duplicate=false
     * csv file
     * liste string
     * containerId string
     * duplicate bool
     * @return int
     * @throws Tinebase_Exception_AccessDenied
     * @throws Tinebase_Exception_InvalidArgument
     * @throws Tinebase_Exception_NotFound
     * @throws Tinebase_Exception_Record_DefinitionFailure
     * @throws Tinebase_Exception_Record_Validation
     */
    public function importGroupMailingList(Zend_Console_Getopt $opts)
    {
        $args = $this->_parseArgs($opts);
        $csv = $args['csv'];
        $listName = $args['list'];
        $containerId = $args['containerId'];
        $duplicate = ($args['duplicate']) ? $args['duplicate'] : true;

        if (!$csv || !$containerId || !$listName) {
            return 2;
        }

        $stream = fopen($csv, 'r');
        if (!$stream) {
            echo "file could not be opened: " . $csv . "\n";
            return 2;
        }

        $list = Addressbook_Controller_List::getInstance()->search(Tinebase_Model_Filter_FilterGroup::getFilterForModel(
            Addressbook_Model_List::class, [
            ['field' => 'name', 'operator' => 'equals', 'value' => $listName]]))
            ->getFirstRecord();

        if(!$list) {
            $list = Addressbook_Controller_List::getInstance()->create(new Addressbook_Model_List(
                ['name' => $listName]));
        }

        while ($line = fgetcsv($stream, 0, ';')) {
            $contact = Addressbook_Controller_Contact::getInstance()->search(Tinebase_Model_Filter_FilterGroup::getFilterForModel(
                Addressbook_Model_Contact::class, [
                ['field' => 'n_fileas', 'operator' => 'equals', 'value' => $line[0]]]))
                ->getFirstRecord();
            if (!$contact) {
                    $contact = Addressbook_Controller_Contact::getInstance()->create(new Addressbook_Model_Contact(
                    ['n_fileas' => $line[0],
                        'email' => $line[1],
                        'container_id' => $containerId]
                ), false);
            }
            Addressbook_Controller_List::getInstance()->addListMember($list, $contact, $duplicate);
        }

        fclose($stream);
        return 0;
    }

    /**
     *    -d (dry run)
     *    e.g. php tine20.php --method=Addressbook.clearUserContactsWithoutUser -d
     * @param Zend_Console_Getopt $opts
     * @return int
     */
    public function clearUserContactsWithoutUser(Zend_Console_Getopt $opts) {
        $count = 0;
        $backend = new Addressbook_Backend_Sql();

        if ($opts->d) {
            echo "Dry-run \n";;
        }

        $filter = Tinebase_Model_Filter_FilterGroup::getFilterForModel(Addressbook_Model_Contact::class,
            [['field' => 'type', 'operator' => 'equals', 'value' => 'user']]);

        $userContacts = Addressbook_Controller_Contact::getInstance()->search($filter);
        foreach ($userContacts as $userContact) {
            try {
                Admin_Controller_User::getInstance()->get($userContact['account_id']);
            }catch(Tinebase_Exception_NotFound $e) {
                if ($opts->d) {
                    echo "will remove contact: " . $userContact['n_fileas'] . " id: " . $userContact->getId() . "\n";
                    continue;
                }
                $backend->delete($userContact->getId());
                echo "remove contact: " . $userContact['n_fileas'] . "\n";
                $count++;
            }
        }
        echo "remove: " . $count . " contacts \n";
        return 0;
    }
}
