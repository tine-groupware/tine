<?php

/**
 * Tine 2.0
 *
 * @package     Felamimail
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2022 Metaways Infosystems GmbH (http://www.metaways.de)
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


    static protected $_allUpdates = [
        self::PRIO_NORMAL_APP_STRUCTURE => [
            self::RELEASE016_UPDATE001          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update001',
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
        ],
    ];

    public function update000()
    {
        $this->addApplicationUpdate('Felamimail', '16.0', self::RELEASE016_UPDATE000);
    }

    public function update001()
    {
        $this->addApplicationUpdate('Felamimail', '16.1', self::RELEASE016_UPDATE001);
    }

    public function update002()
    {
        Tinebase_Core::getDb()->query('UPDATE ' . SQL_TABLE_PREFIX . 'preferences SET value = "messageAndAsAttachment" WHERE name = "emlForward" and value = "1"');
        Tinebase_Core::getDb()->query('UPDATE ' . SQL_TABLE_PREFIX . 'preferences SET value = "message" WHERE name = "emlForward" and value = "0"');
        
        $this->addApplicationUpdate('Felamimail', '16.2', self::RELEASE016_UPDATE002);   
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

        $this->addApplicationUpdate('Felamimail', '16.3', self::RELEASE016_UPDATE003);
    }

    public function update004()
    {
        $this->addApplicationUpdate('Felamimail', '16.4', self::RELEASE016_UPDATE004);
    }

    public function update005()
    {
        if (Tinebase_EmailUser::manages(Tinebase_Config::IMAP)) {
            Admin_Controller_EmailAccount::getInstance()->updateNotificationScripts();
        }

        $this->addApplicationUpdate('Felamimail', '16.5', self::RELEASE016_UPDATE005);
    }
}
