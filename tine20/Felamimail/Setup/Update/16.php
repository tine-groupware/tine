<?php

/**
 * Tine 2.0
 *
 * @package     Felamimail
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2022-2023 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 *
 * this is 2023.11 (ONLY!)
 */
class Felamimail_Setup_Update_16 extends Setup_Update_Abstract
{
    const RELEASE016_UPDATE000 = __CLASS__ . '::update000';
    const RELEASE016_UPDATE001 = __CLASS__ . '::update001';
    const RELEASE016_UPDATE002 = __CLASS__ . '::update002';
    const RELEASE016_UPDATE003 = __CLASS__ . '::update003';
    const RELEASE016_UPDATE004 = __CLASS__ . '::update004';
    const RELEASE016_UPDATE005 = __CLASS__ . '::update005';
    const RELEASE016_UPDATE006 = __CLASS__ . '::update006';
    const RELEASE016_UPDATE007 = __CLASS__ . '::update007';
    const RELEASE016_UPDATE008 = __CLASS__ . '::update008';
    const RELEASE016_UPDATE009 = __CLASS__ . '::update009';


    static protected $_allUpdates = [
        self::PRIO_NORMAL_APP_STRUCTURE => [
            self::RELEASE016_UPDATE001          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update001',
            ],
            self::RELEASE016_UPDATE006          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update006',
            ],
            self::RELEASE016_UPDATE007          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update007',
            ],
            self::RELEASE016_UPDATE008          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update008',
            ],
        ],
        self::PRIO_NORMAL_APP_UPDATE        => [
            self::RELEASE016_UPDATE000          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update000',
            ],
            self::RELEASE016_UPDATE002          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update002',
            ],
            self::RELEASE016_UPDATE003          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update003',
            ],
            self::RELEASE016_UPDATE004          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update004',
            ],
            self::RELEASE016_UPDATE005          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update005',
            ],
            self::RELEASE016_UPDATE009          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update009',
            ],
        ],
    ];

    public function update000()
    {
        $this->addApplicationUpdate(Felamimail_Config::APP_NAME, '16.0', self::RELEASE016_UPDATE000);
    }

    public function update001()
    {
        $this->addApplicationUpdate(Felamimail_Config::APP_NAME, '16.1', self::RELEASE016_UPDATE001);
    }

    public function update002()
    {
        Tinebase_Core::getDb()->query('UPDATE ' . SQL_TABLE_PREFIX . 'preferences SET value = "messageAndAsAttachment" WHERE name = "emlForward" and value = "1"');
        Tinebase_Core::getDb()->query('UPDATE ' . SQL_TABLE_PREFIX . 'preferences SET value = "message" WHERE name = "emlForward" and value = "0"');
        
        $this->addApplicationUpdate(Felamimail_Config::APP_NAME, '16.2', self::RELEASE016_UPDATE002);
    }
    
    public function update003()
    {
        if (! Tinebase_Core::isFilesystemAvailable()) {
            if (Tinebase_Core::isLogLevel(Zend_Log::WARN)) Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__
                . ' Skipping update of sieve notification template');
        } else {
            $basepath = Tinebase_FileSystem::getInstance()->getApplicationBasePath(
                'Felamimail',
                Tinebase_FileSystem::FOLDER_TYPE_SHARED
            );

            if (false === ($fh = Tinebase_FileSystem::getInstance()->fopen(
                    $basepath . '/Email Notification Templates/defaultForwarding.sieve', 'w'))) {
                if (Tinebase_Core::isLogLevel(Zend_Log::ERR)) {
                    Tinebase_Core::getLogger()->err(__METHOD__ . '::' . __LINE__
                        . ' Could not open defaultForwarding.sieve file');
                }
            } else {

                fwrite($fh, <<<'sieveFile'
require ["enotify", "variables", "copy", "body"];

if header :contains "Return-Path" "<>" {
    if body :raw :contains "X-Tine20-Type: Notification" {
        notify :message "there was a notification bounce"
              "mailto:ADMIN_BOUNCE_EMAIL";
    }
} elsif header :contains "X-Tine20-Type" "Notification" {
    REDIRECT_EMAILS_SCRIPT
} else {
    if header :matches "Subject" "*" {
        set "subject" "${1}";
    }
    if header :matches "From" "*" {
        set "from" "${1}";
    }
    set :encodeurl "message" "TRANSLATE_SUBJECT${from}: ${subject}";

    NOTIFY_EMAILS_SCRIPT
}
sieveFile
                );

                if (true !== Tinebase_FileSystem::getInstance()->fclose($fh)) {
                    if (Tinebase_Core::isLogLevel(Zend_Log::ERR)) {
                        Tinebase_Core::getLogger()->err(__METHOD__ . '::' . __LINE__
                            . ' Could not close defaultForwarding.sieve file');
                    }
                }
            }
        }

        $this->addApplicationUpdate(Felamimail_Config::APP_NAME, '16.3', self::RELEASE016_UPDATE003);
    }

    public function update004()
    {
        $this->addApplicationUpdate(Felamimail_Config::APP_NAME, '16.4', self::RELEASE016_UPDATE004);
    }

    public function update005()
    {
        if (Tinebase_EmailUser::manages(Tinebase_Config::IMAP)) {
            Admin_Controller_EmailAccount::getInstance()->updateNotificationScripts();
        }

        $this->addApplicationUpdate(Felamimail_Config::APP_NAME, '16.5', self::RELEASE016_UPDATE005);
    }

    public function update006()
    {
        Tinebase_TransactionManager::getInstance()->rollBack();

        if ($this->getTableVersion('felamimail_cache_message') < 16) {
            $this->setTableVersion('felamimail_cache_message', 16);
        }

        $schema = $this->_backend->getExistingSchema('felamimail_cache_message');
        $truncated = false;

        if (!isset($schema->indicesByName['to_list'])) {
            if (!$truncated) {
                // truncate email cache to make this go faster
                Felamimail_Controller::getInstance()->truncateEmailCache();
                $truncated = true;
            }
            $declaration = new Setup_Backend_Schema_Index_Xml('
                    <index>
                        <name>to_list</name>
                        <fulltext>true</fulltext>
                        <field>
                            <name>to_list</name>
                        </field>
                    </index>');
            $this->_backend->addIndex('felamimail_cache_message', $declaration);
        }

        if (!isset($schema->indicesByName['cc_list'])) {
            if (!$truncated) {
                // truncate email cache to make this go faster
                Felamimail_Controller::getInstance()->truncateEmailCache();
                $truncated = true;
            }
            $declaration = new Setup_Backend_Schema_Index_Xml('
                    <index>
                        <name>cc_list</name>
                        <fulltext>true</fulltext>
                        <field>
                            <name>cc_list</name>
                        </field>
                    </index>');
            $this->_backend->addIndex('felamimail_cache_message', $declaration);
        }

        if (!isset($schema->indicesByName['bcc_list'])) {
            if (!$truncated) {
                // truncate email cache to make this go faster
                Felamimail_Controller::getInstance()->truncateEmailCache();
                $truncated = true;
            }
            $declaration = new Setup_Backend_Schema_Index_Xml('
                    <index>
                        <name>bcc_list</name>
                        <fulltext>true</fulltext>
                        <field>
                            <name>bcc_list</name>
                        </field>
                    </index>');
            $this->_backend->addIndex('felamimail_cache_message', $declaration);
        }

        if (!isset($schema->indicesByName['subject'])) {
            if (!$truncated) {
                // truncate email cache to make this go faster
                Felamimail_Controller::getInstance()->truncateEmailCache();
                //$truncated = true;
            }
            $declaration = new Setup_Backend_Schema_Index_Xml('
                    <index>
                        <name>subject</name>
                        <fulltext>true</fulltext>
                        <field>
                            <name>subject</name>
                        </field>
                    </index>');
            $this->_backend->addIndex('felamimail_cache_message', $declaration);
        }

        $this->addApplicationUpdate(Felamimail_Config::APP_NAME, '16.6', self::RELEASE016_UPDATE006);
    }

    public function update007()
    {
        Tinebase_TransactionManager::getInstance()->rollBack();

        if ($this->getTableVersion('felamimail_cache_message') < 17) {
            $this->setTableVersion('felamimail_cache_message', 17);
        }

        $schema = $this->_backend->getExistingSchema('felamimail_cache_message');

        if (!isset($schema->indicesByName['from_email_ft'])) {
            $this->_backend->addIndex('felamimail_cache_message', new Setup_Backend_Schema_Index_Xml('
                    <index>
                        <name>from_email_ft</name>
                        <fulltext>true</fulltext>
                        <field>
                            <name>from_email</name>
                        </field>
                    </index>'));
        }

        if (!isset($schema->indicesByName['from_name_ft'])) {
            $this->_backend->addIndex('felamimail_cache_message', new Setup_Backend_Schema_Index_Xml('
                    <index>
                        <name>from_name_ft</name>
                        <fulltext>true</fulltext>
                        <field>
                            <name>from_name</name>
                        </field>
                    </index>'));
        }

        $this->addApplicationUpdate(Felamimail_Config::APP_NAME, '16.7', self::RELEASE016_UPDATE007);
    }

    public function update008()
    {
        Tinebase_TransactionManager::getInstance()->rollBack();

        if ($this->getTableVersion('felamimail_cache_message') < 18) {
            $this->setTableVersion('felamimail_cache_message', 18);
        }

        $schema = $this->_backend->getExistingSchema('felamimail_cache_message');

        if (isset($schema->indicesByName['received'])) {
            $this->_backend->dropIndex('felamimail_cache_message', 'received');
        }
        if (isset($schema->indicesByName['account_id-folder_id'])) {
            $this->_backend->dropIndex('felamimail_cache_message', 'account_id-folder_id');
        }

        if (!isset($schema->indicesByName['account_id-folder_id-received'])) {
            $this->_backend->addIndex('felamimail_cache_message', new Setup_Backend_Schema_Index_Xml('
                    <index>
                        <name>account_id-folder_id-received</name>
                        <field>
                            <name>account_id</name>
                        </field>
                        <field>
                            <name>folder_id</name>
                        </field>
                        <field>
                            <name>received</name>
                        </field>
                    </index>'));
        }

        if (!isset($schema->indicesByName['account_id-received'])) {
            $this->_backend->addIndex('felamimail_cache_message', new Setup_Backend_Schema_Index_Xml('
                    <index>
                        <name>account_id-received</name>
                        <field>
                            <name>account_id</name>
                        </field>
                        <field>
                            <name>received</name>
                        </field>
                    </index>'));
        }

        $this->addApplicationUpdate(Felamimail_Config::APP_NAME, '16.8', self::RELEASE016_UPDATE008);
    }

    public function update009()
    {
        $this->getDb()->query('DELETE FROM ' . SQL_TABLE_PREFIX .
            'state where `state_id` like "Felamimail-Message-GridPanel%" or `state_id` = "Felamimail_detailspanelregion"');

        $this->addApplicationUpdate('Felamimail', '16.9', self::RELEASE016_UPDATE009);
    }
}
