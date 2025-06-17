<?php

/**
 * Tine 2.0
 *
 * @package     Addressbook
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2023 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 *
 * this is 2024.11 (ONLY!)
 */
class Addressbook_Setup_Update_17 extends Setup_Update_Abstract
{
    const RELEASE017_UPDATE000 = __CLASS__ . '::update000';
    const RELEASE017_UPDATE001 = __CLASS__ . '::update001';
    const RELEASE017_UPDATE002 = __CLASS__ . '::update002';
    const RELEASE017_UPDATE003 = __CLASS__ . '::update003';
    const RELEASE017_UPDATE004 = __CLASS__ . '::update004';
    const RELEASE017_UPDATE005 = __CLASS__ . '::update005';


    static protected $_allUpdates = [
        self::PRIO_TINEBASE_BEFORE_STRUCT   => [
            // order matters!
            self::RELEASE017_UPDATE002          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update002',
            ],
        ],
        self::PRIO_NORMAL_APP_STRUCTURE     => [
            self::RELEASE017_UPDATE003          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update003',
            ],
        ],
        self::PRIO_NORMAL_APP_UPDATE        => [
            self::RELEASE017_UPDATE000          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update000',
            ],
            self::RELEASE017_UPDATE001          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update001',
            ],
            self::RELEASE017_UPDATE004          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update004',
            ],
            self::RELEASE017_UPDATE005          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update005',
            ],
        ],
    ];

    public function update000()
    {
        $this->addApplicationUpdate(Addressbook_Config::APP_NAME, '17.0', self::RELEASE017_UPDATE000);
    }

    public function update001()
    {
        Setup_SchemaTool::updateSchema([
            Addressbook_Model_Contact::class,
            Tinebase_Model_Container::class,
        ]);
        $this->addApplicationUpdate(Addressbook_Config::APP_NAME, '17.1', self::RELEASE017_UPDATE001);
    }
    
    public function update002()
    {
        Setup_SchemaTool::updateSchema([
            Addressbook_Model_Contact::class,
        ]);

        $this->addApplicationUpdate(Addressbook_Config::APP_NAME, '17.2', self::RELEASE017_UPDATE002);
    }

    public function update003()
    {
        Setup_SchemaTool::updateSchema([
            Addressbook_Model_Contact::class,
            Addressbook_Model_ContactSite::class
        ]);

        Tinebase_Config::getInstance()->set(Tinebase_Config::SITE_FILTER, Addressbook_Config::getInstance()->get(Addressbook_Config::SITE_FILTER));
        
        $this->addApplicationUpdate(Addressbook_Config::APP_NAME, '17.3', self::RELEASE017_UPDATE003);
    }

    public function update004()
    {
        Setup_SchemaTool::updateSchema([
            Addressbook_Model_ContactProperties_Definition::class
        ]);

        $this->addApplicationUpdate(Addressbook_Config::APP_NAME, '17.4', self::RELEASE017_UPDATE004);
    }

    public function update005()
    {
        Tinebase_TransactionManager::getInstance()->rollBack();
        $emailFields = array_keys(Addressbook_Model_Contact::getEmailFields());
        $db = $this->getDb();

        $caseConditions = [];
        foreach ($emailFields as $field) {
            $quotedField = $db->quoteIdentifier($field);
            $caseConditions[] = "WHEN {$quotedField} IS NOT NULL AND {$quotedField} != '' THEN '{$quotedField}'";
        }
        $caseStatement = implode(' ', $caseConditions);

        $emailCheckConditions = [];
        foreach ($emailFields as $field) {
            $quotedField = $db->quoteIdentifier($field);
            $emailCheckConditions[] = "(preferred_email = {$quotedField} AND ({$quotedField} IS NULL OR {$quotedField} = ''))";
        }
        $invalidPreferredCondition = implode(' OR ', $emailCheckConditions);

        $db->query('UPDATE ' . SQL_TABLE_PREFIX . Addressbook_Model_Contact::TABLE_NAME .
            ' SET preferred_email = CASE ' . $caseStatement . ' ELSE "email" END' .
            ' WHERE ' . $invalidPreferredCondition);


        $stateRepo = new Tinebase_Backend_Sql(array(
            'modelName' => 'Tinebase_Model_State',
            'tableName' => 'state',
        ));

        $states = $stateRepo->search(new Tinebase_Model_StateFilter(array(
            array('field' => 'state_id', 'operator' => 'in', 'value' => [
                "Addressbook-Contact-GridPanel-Grid_large",
                "Addressbook-Contact-GridPanel-Grid_big",
            ]),
        )));

        foreach ($states as $state) {
            $decodedState = Tinebase_State::decode($state->data);
            $columns = $decodedState['columns'];
            $visibleEmailFields = array_filter($columns, function ($column) use ($emailFields) {
                return in_array($column['id'], $emailFields) && !$column['hidden'];
            });
            if (count($visibleEmailFields) === 1) {
                $column = array_pop($visibleEmailFields);
                if ($column['id'] === 'email') {
                    $columns['preferred_email']['hidden'] = false;
                    $columns['email']['hidden'] = true;
                }
            }
            $decodedState['columns'] = $columns;
            $state->data = Tinebase_State::encode($decodedState);
            $stateRepo->update($state);
        }

        $this->addApplicationUpdate('Addressbook', '17.5', self::RELEASE017_UPDATE005);
    }
}
