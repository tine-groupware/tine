<?php

/**
 * Tine 2.0
 *
 * @package     Felamimail
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2018-2019 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 *
 * this ist 2019.11 (ONLY!)
 */
class Felamimail_Setup_Update_12 extends Setup_Update_Abstract
{
    const RELEASE012_UPDATE001 = __CLASS__ . '::update001';
    const RELEASE012_UPDATE002 = __CLASS__ . '::update002';
    const RELEASE012_UPDATE003 = __CLASS__ . '::update003';
    const RELEASE012_UPDATE004 = __CLASS__ . '::update004';
    const RELEASE012_UPDATE005 = __CLASS__ . '::update005';
    const RELEASE012_UPDATE006 = __CLASS__ . '::update006';
    const RELEASE012_UPDATE007 = __CLASS__ . '::update007';
    const RELEASE012_UPDATE008 = __CLASS__ . '::update008';
    const RELEASE012_UPDATE009 = __CLASS__ . '::update009';

    static protected $_allUpdates = [
        self::PRIO_NORMAL_APP_STRUCTURE     => [
            self::RELEASE012_UPDATE001          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update001',
            ],
            self::RELEASE012_UPDATE002          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update002',
            ],
            self::RELEASE012_UPDATE003          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update003',
            ],
            self::RELEASE012_UPDATE004          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update004',
            ],
            self::RELEASE012_UPDATE005          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update005',
            ],
            self::RELEASE012_UPDATE008          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update008',
            ],
            self::RELEASE012_UPDATE009          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update009',
            ],
        ],
        self::PRIO_NORMAL_APP_UPDATE => [
            self::RELEASE012_UPDATE006          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update006',
            ],
            self::RELEASE012_UPDATE007          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update007',
            ],
        ]
    ];

    public function update001()
    {
        $release11 = new Felamimail_Setup_Update_Release11($this->_backend);
        $release11->update_2();
        $this->addApplicationUpdate('Felamimail', '12.4', self::RELEASE012_UPDATE001);
    }

    public function update002()
    {
        // moved to update003
        $this->addApplicationUpdate('Felamimail', '12.5', self::RELEASE012_UPDATE002);
    }

    public function update003()
    {
        if (! $this->_backend->tableExists('felamimail_account_acl')) {
            $tableDefinition = new Setup_Backend_Schema_Table_Xml('<table>
                <name>felamimail_account_acl</name>
                <version>1</version>
                <declaration>
                    <field>
                        <name>id</name>
                        <type>text</type>
                        <length>40</length>
                        <notnull>true</notnull>
                    </field>
                    <field>
                        <name>record_id</name>
                        <type>text</type>
                        <length>40</length>
                        <notnull>true</notnull>
                    </field>
                    <field>
                        <name>account_type</name>
                        <type>text</type>
                        <length>32</length>
                        <default>user</default>
                        <notnull>true</notnull>
                    </field>
                    <field>
                        <name>account_id</name>
                        <type>text</type>
                        <length>40</length>
                        <notnull>true</notnull>
                    </field>
                    <field>
                        <name>account_grant</name>
                        <type>text</type>
                        <length>40</length>
                        <notnull>true</notnull>
                    </field>
                    <index>
                        <primary>true</primary>
                        <field>
                            <name>id</name>
                        </field>
                    </index>
                    <index>
                        <name>record_id-account-type-account_id-account_grant</name>
                        <unique>true</unique>
                        <field>
                            <name>record_id</name>
                        </field>
                        <field>
                            <name>account_type</name>
                        </field>
                        <field>
                            <name>account_id</name>
                        </field>
                        <field>
                            <name>account_grant</name>
                        </field>
                    </index>
                    <index>
                        <name>fmail_account_acl::record_id--fmail_account::id</name>
                        <field>
                            <name>record_id</name>
                        </field>
                        <foreign>true</foreign>
                        <reference>
                            <table>felamimail_account</table>
                            <field>id</field>
                            <ondelete>cascade</ondelete>
                            <onupdate>cascade</onupdate>
                        </reference>
                    </index>
                </declaration>
            </table>');

            $this->_backend->createTable($tableDefinition, 'Felamimail', 'felamimail_account_acl');
        }

        $accountCtrl = Felamimail_Controller_Account::getInstance();
        $oldValue = $accountCtrl->doContainerACLChecks(false);
        foreach ($accountCtrl->getAll() as $account) {
            $accountCtrl->getGrantsForRecord($account);
            $accountCtrl->setGrants($account);
        }
        $accountCtrl->doContainerACLChecks($oldValue);

        $this->addApplicationUpdate('Felamimail', '12.6', self::RELEASE012_UPDATE003);
    }

    /**
     * add signature table
     */
    public function update004()
    {
        $this->updateSchema('Felamimail', array(Felamimail_Model_Signature::class));
        $this->addApplicationUpdate('Felamimail', '12.7', self::RELEASE012_UPDATE004);
    }

    /**
     * move account signature to new signatures table
     */
    public function update005()
    {
        $this->updateSchema('Felamimail', array(Felamimail_Model_Signature::class));

        // fetch current signature
        $accountBackend = new Felamimail_Backend_Account();
        foreach ($accountBackend->search(null, null, true) as $accountId) {
            $account = $accountBackend->get($accountId);
            if (! empty($account->signature)) {
                $signature = new Felamimail_Model_Signature([
                    'account_id' => $accountId,
                    'name' => $account->name, // TODO leave it empty?
                    'is_default' => true,
                    'signature' => $account->signature,
                ]);
                Felamimail_Controller_Signature::getInstance()->create($signature);
            }
        }

        // drop old signature column
        if ($this->_backend->columnExists('signature', 'felamimail_account')) {
            $this->_backend->dropCol('felamimail_account', 'signature');
        }

        $this->addApplicationUpdate('Felamimail', '12.8', self::RELEASE012_UPDATE005);
    }

    /**
     * reset folder "support_condstore"
     */
    public function update006()
    {
        $folderBackend = new Felamimail_Backend_Folder();
        $data = [
            'supports_condstore' => null
        ];
        Tinebase_Core::getDb()->update($folderBackend->getTablePrefix() . $folderBackend->getTableName(), $data);

        $this->addApplicationUpdate('Felamimail', '12.9', self::RELEASE012_UPDATE006);
    }

    /**
     * add addGrant (send message) to accounts
     */
    public function update007()
    {
        $accountCtrl = Felamimail_Controller_Account::getInstance();
        $oldValue = $accountCtrl->doContainerACLChecks(false);
        foreach ($accountCtrl->getAll() as $account) {
            $accountCtrl->getGrantsForRecord($account);
            foreach ($account->grants as $grant) {
                if ($grant->readGrant) {
                    // use grant provides send grant
                    $grant->addGrant = true;
                }
            }
            $accountCtrl->setGrants($account);
        }
        $accountCtrl->doContainerACLChecks($oldValue);
        $this->addApplicationUpdate('Felamimail', '12.10', self::RELEASE012_UPDATE007);
    }

    public function update008()
    {
        if ($this->getTableVersion('felamimail_cache_message') < 11) {
            // truncate email cache to make this go faster
            Felamimail_Controller::getInstance()->truncateEmailCache();

            $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>message_id</name>
                <type>text</type>
            </field>
        ');

            $this->_backend->addCol('felamimail_cache_message', $declaration);

            $this->setTableVersion('felamimail_cache_message', 11);
        }

        $this->addApplicationUpdate('Felamimail', '12.11', self::RELEASE012_UPDATE008);
    }

    public function update009()
    {
        if ($this->getTableVersion('felamimail_account') < 25) {

            $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>migration_approved</name>
                <type>boolean</type>
                <default>false</default>
            </field>
        ');

            $this->_backend->addCol('felamimail_account', $declaration);

            $this->setTableVersion('felamimail_account', 25);
        }

        $this->addApplicationUpdate('Felamimail', '12.12', self::RELEASE012_UPDATE009);
    }
}
