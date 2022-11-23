<?php
/**
 * @package     Felamimail
 * @subpackage  Config
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2012-2021 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * Felamimail config class
 *
 * @package     Felamimail
 * @subpackage  Config
 */
class Felamimail_Config extends Tinebase_Config_Abstract
{
    const APP_NAME = 'Felamimail';

    /**
     * is email body cached
     *
     * @var string
     */
    const CACHE_EMAIL_BODY = 'cacheEmailBody';

    /**
     * delete archived mail
     *
     * @var string
     */
    const DELETE_ARCHIVED_MAIL = 'deleteArchivedMail';

    /**
     * migrate system accounts
     *
     * @var string
     */
    const FEATURE_ACCOUNT_MIGRATION = 'accountMigration';

    /**
     * move notifications to a subfolder via sieve
     *
     * @var string
     */
    const FEATURE_ACCOUNT_MOVE_NOTIFICATIONS = 'accountMoveNotifications';

    /**
     * auto save drafts
     *
     * @const string
     */
    const FEATURE_AUTOSAVE_DRAFTS = 'autoSaveDrafts';

    /**
     * Tine 2.0 flag feature
     *
     * @var string
     * @see 0010576: show a tine20 icon on each message which was written in tine20
     */
    const FEATURE_TINE20_FLAG = 'tine20Flag';

    /**
     * allow only send password download link as attachment
     */
    const FEATURE_ONLY_PW_DOWNLOAD_LINK = 'onlyPwDownloadLink';

    /**
     * show reply-to field in message compose dialog
     *
     * @var string
     * @see https://github.com/tine20/tine20/issues/2172
     */
    const FEATURE_SHOW_REPLY_TO = 'showReplyTo';

    /**
     * Create template, trash, sent, draft and junks folders for system accounts
     *
     * @var string
     */
    const FEATURE_SYSTEM_ACCOUNT_AUTOCREATE_FOLDERS = 'systemAccountAutoCreateFolders';

    /**
     * spam suspicion strategy feature
     *
     * @var string
     */
    const FEATURE_SPAM_SUSPICION_STRATEGY = 'featureSpamSuspicionStrategy';

    /**
     * Create spam suspicion strategy
     *
     * @var string
     */
    const SPAM_SUSPICION_STRATEGY = 'spamSuspicionStrategy';

    /**
     * Create spam suspicion strategy config
     *
     * @var string
     */
    const SPAM_SUSPICION_STRATEGY_CONFIG = 'spamSuspicionStrategyConfig';

    /**
     * Create spam user processing pipeline config
     *
     * @var string
     */
    const SPAM_USERPROCESSING_PIPELINE = 'spamUserProcessingPipeline';

    /**
     * Create spam alert information config
     *
     * @var string
     */
    const SPAM_INFO_DIALOG_CONTENT = 'spamInfoDialogContent';
    
    /**
     * Tine 2.0 filter message uris (only allow <a> uris)
     *
     * @var string
     */
    const FILTER_EMAIL_URIS = 'filterEmailUris';

    /**
     * Prevent copying mails in the same account
     *
     * @var string
     */
    const PREVENT_COPY_OF_MAILS_IN_SAME_ACCOUNT = 'preventCopyOfMailsInSameAccount';

    /**
     * system account special folders
     *
     * @var string
     */
    const SYSTEM_ACCOUNT_FOLDER_DEFAULTS = 'systemAccountFolderDefaults';

    /**
     * id of (filsystem) container for vacation templates
     *
     * @var string
     */
    const VACATION_TEMPLATES_CONTAINER_ID = 'vacationTemplatesContainerId';

    /**
     * id of (filsystem) container for email notification templates
     *
     * @var string
     */
    const EMAIL_NOTIFICATION_TEMPLATES_CONTAINER_ID = 'emailNotificationTemplatesContainerId';

    /**
     * the email address to notifify about notification bounces
     *
     * @var string
     */
    const SIEVE_ADMIN_BOUNCE_NOTIFICATION_EMAIL = 'sieveAdminBounceNotificationEmail';

    /**
     * allow only sieve redirect rules to internal (primary/secondary) email addresses
     *
     * @var string
     */
    const SIEVE_REDIRECT_ONLY_INTERNAL = 'sieveRedirectOnlyInternal';

    /**
     * user can set custom vacation message
     *
     * @var string
     */
    const VACATION_CUSTOM_MESSAGE_ALLOWED = 'vacationMessageCustomAllowed';

    /**
     * allow self signed tls cert for IMAP connection
     *
     * @see 0009676: activate certificate check for TLS/SSL
     * @var string
     */
    const IMAP_ALLOW_SELF_SIGNED_TLS_CERT = 'imapAllowSelfSignedTlsCert';

    const FLAG_ICON_OWN_DOMAIN = 'flagIconOwnDomain';
    const FLAG_ICON_OTHER_DOMAIN = 'flagIconOtherDomain';
    const FLAG_ICON_OTHER_DOMAIN_REGEX = 'flagIconOtherDomainRegex';

    const SIEVE_MAILINGLIST_REJECT_REASON = 'sieveMailingListRejectReason';

    const ATTACHMENT_CACHE_TTL = 'attachmentCacheTTL';
    /**
     * keyFieldConfig vor mail account types
     */
    const MAIL_ACCOUNT_TYPE = 'mailAccountType';

    const SIEVE_NOTIFICATION_MOVE_STATUS = 'sieveNotificationMoveStatus';

    /**
     * (non-PHPdoc)
     * @see tine20/Tinebase/Config/Definition::$_properties
     */
    protected static $_properties = array(
        self::MAIL_ACCOUNT_TYPE => array(
            //_('Mail Type')
            self::LABEL              => 'Type',
            //_('Possible mail types.')
            self::DESCRIPTION        => 'Possible mail types.',
            self::TYPE               => self::TYPE_KEYFIELD_CONFIG,
            self::OPTIONS               => array('recordModel' => Felamimail_Model_MailType::class),
            self::CLIENTREGISTRYINCLUDE => true,
            self::SETBYADMINMODULE      => true,
            self::SETBYSETUPMODULE      => false,
            self::DEFAULT_STR           => array(
                'records' => array(
                    array('id' => Tinebase_EmailUser_Model_Account::TYPE_USER,    'value' => 'Additional Personal External Account', 'system' => true), //_('Additional Personal External Account')
                    array('id' => Tinebase_EmailUser_Model_Account::TYPE_SHARED,    'value' => 'Shared System Account',  'system' => true), //_('Shared System Account')
                    array('id' => Tinebase_EmailUser_Model_Account::TYPE_USER_INTERNAL,   'value' => 'Additional Personal System Account',  'system' => true), //_('Additional Personal System Account')
                    array('id' => Tinebase_EmailUser_Model_Account::TYPE_SYSTEM,   'value' => 'Default Personal System Account',   'system' => true), //_('Default Personal System Account')
                    array('id' => Tinebase_EmailUser_Model_Account::TYPE_ADB_LIST,   'value' => 'Mailinglist',   'system' => true), //_('Mailinglist')
                ),
                self::DEFAULT_STR => Tinebase_EmailUser_Model_Account::TYPE_USER
            )
        ),
        self::SIEVE_MAILINGLIST_REJECT_REASON => [
            self::LABEL                 => 'Mailinglist Reject Reason', // _('Mailinglist Reject Reason')
            self::DESCRIPTION           => 'Mailinglist Reject Reason',
            self::TYPE                  => self::TYPE_STRING,
            self::DEFAULT_STR           => 'Your email has been rejected', // _('Your email has been rejected')
            self::CLIENTREGISTRYINCLUDE => false,
            self::SETBYADMINMODULE      => true,
            self::SETBYSETUPMODULE      => true,
        ],
        self::VACATION_TEMPLATES_CONTAINER_ID => array(
        //_('Vacation Templates Node ID')
            self::LABEL                 => 'Vacation Templates Node ID',
            self::DESCRIPTION           => 'Vacation Templates Node ID',
            self::TYPE                  => Tinebase_Config_Abstract::TYPE_STRING,
            self::CLIENTREGISTRYINCLUDE => FALSE,
            self::SETBYADMINMODULE      => FALSE,
            self::SETBYSETUPMODULE      => FALSE,
        ),
        self::EMAIL_NOTIFICATION_TEMPLATES_CONTAINER_ID => array(
            //_('Email Notification Templates Node ID')
            self::LABEL                 => 'Email Notification Templates Node ID',
            self::DESCRIPTION           => 'Email Notification Templates Node ID',
            self::TYPE                  => Tinebase_Config_Abstract::TYPE_STRING,
            self::CLIENTREGISTRYINCLUDE => FALSE,
            self::SETBYADMINMODULE      => FALSE,
            self::SETBYSETUPMODULE      => FALSE,
        ),
        self::VACATION_CUSTOM_MESSAGE_ALLOWED => array(
        //_('Custom Vacation Message')
            self::LABEL                 => 'Custom Vacation Message',
        // _('User is allowed to set custom vacation message for system account')
            self::DESCRIPTION           => 'User is allowed to set custom vacation message for system account',
            self::TYPE                  => Tinebase_Config_Abstract::TYPE_INT,
            self::CLIENTREGISTRYINCLUDE => TRUE,
            self::SETBYADMINMODULE      => FALSE,
            self::SETBYSETUPMODULE      => FALSE,
            self::DEFAULT_STR           => 1,
        ),
        self::CACHE_EMAIL_BODY => array(
        //_('Cache email body')
            self::LABEL                 => 'Cache email body',
        // _('Should the email body be cached (recommended for slow IMAP server connections)')
            self::DESCRIPTION           => 'Should the email body be cached (recommended for slow IMAP server connections)',
            self::TYPE                  => Tinebase_Config_Abstract::TYPE_INT,
            self::CLIENTREGISTRYINCLUDE => FALSE,
            self::SETBYADMINMODULE      => FALSE,
            self::SETBYSETUPMODULE      => TRUE,
            self::DEFAULT_STR           => 1,
        ),
        self::DELETE_ARCHIVED_MAIL => array(
            //_('Delete Archived Mail')
            self::LABEL                 => 'Delete Archived Mail',
            self::DESCRIPTION           => 'Delete Archived Mail',
            self::TYPE                  => Tinebase_Config_Abstract::TYPE_BOOL,
            self::CLIENTREGISTRYINCLUDE => FALSE,
            self::SETBYADMINMODULE      => FALSE,
            self::SETBYSETUPMODULE      => TRUE,
            self::DEFAULT_STR           => false,
        ),
        self::PREVENT_COPY_OF_MAILS_IN_SAME_ACCOUNT => array(
            //_('Prevent copying mails in the same account')
            self::LABEL                 => 'Prevent copying mails in the same account',
            self::DESCRIPTION           => 'Prevent copying mails in the same account',
            self::TYPE                  => Tinebase_Config_Abstract::TYPE_BOOL,
            self::CLIENTREGISTRYINCLUDE => FALSE,
            self::SETBYADMINMODULE      => true,
            self::SETBYSETUPMODULE      => true,
            self::DEFAULT_STR           => false,
        ),
        self::ATTACHMENT_CACHE_TTL => [
            //_('Attachment cache TTL in seconds')
            self::LABEL                 => 'Attachment cache TTL in seconds',
            //_('Attachment cache TTL in seconds')
            self::DESCRIPTION           => 'Attachment cache TTL in seconds',
            self::TYPE                  => self::TYPE_INT,
            self::CLIENTREGISTRYINCLUDE => true,
            self::SETBYADMINMODULE      => true,
            self::SETBYSETUPMODULE      => true,
            self::DEFAULT_STR           => 2 * 7 * 24 * 3600, // 2 weeks
        ],
        self::ENABLED_FEATURES => [
            //_('Enabled Features')
            self::LABEL                 => 'Enabled Features',
            //_('Enabled Features in Felamimail Application.')
            self::DESCRIPTION           => 'Enabled Features in Felamimail Application.',
            self::TYPE                  => self::TYPE_OBJECT,
            self::CLASSNAME             => Tinebase_Config_Struct::class,
            self::CLIENTREGISTRYINCLUDE => true,
            self::CONTENT               => [
                self::FEATURE_ACCOUNT_MIGRATION => [
                    self::LABEL                 => 'Account Migration',
                    //_('Account Migration')
                    self::DESCRIPTION           => 'Shows context menu for system accounts to approve migration',
                    //_('Shows context menu for system accounts to approve migration')
                    self::TYPE                  => self::TYPE_BOOL,
                    self::DEFAULT_STR           => false,
                ],
                self::FEATURE_ACCOUNT_MOVE_NOTIFICATIONS => [
                    self::LABEL                 => 'Move notifications',
                    //_('Move notifications')
                    self::DESCRIPTION           => 'Move notifications to a subfolder via sieve',
                    //_('Move notifications to a subfolder via sieve')
                    self::TYPE                  => self::TYPE_BOOL,
                    self::DEFAULT_STR           => false,
                ],
                self::FEATURE_AUTOSAVE_DRAFTS   => [
                    self::LABEL                 => 'Auto-Save Drafts',
                    //_('Auto-Save Drafts')
                    self::DESCRIPTION           => 'Save drafts automatically while editing an email',
                    //_('Save drafts automatically while editing an email')
                    self::TYPE                  => self::TYPE_BOOL,
                    self::DEFAULT_STR           => true,
                ],
                self::FEATURE_ONLY_PW_DOWNLOAD_LINK   => [
                    self::LABEL                 => 'Only password download link',
                    //_('Only password download link')
                    self::DESCRIPTION           => 'Allow only password download link',
                    //_('Allow only password download link')
                    self::TYPE                  => self::TYPE_BOOL,
                    self::DEFAULT_STR           => false,
                ],
                self::FEATURE_TINE20_FLAG   => [
                    self::LABEL                 => 'Tine 2.0 Flag',
                    //_('Tine 2.0 Flag')
                    self::DESCRIPTION           => 'Add a Tine 2.0 flag to sent messages',
                    //_('Add a Tine 2.0 flag to sent messages')
                    self::TYPE                  => self::TYPE_BOOL,
                    self::DEFAULT_STR           => true,
                ],
                self::FEATURE_SHOW_REPLY_TO   => [
                    self::LABEL                 => 'Show Reply-To',
                    //_('Show Reply-To')
                    self::DESCRIPTION           => 'Show Reply-To field in message compose dialog',
                    //_('Show Reply-To field in message compose dialog')
                    self::TYPE                  => self::TYPE_BOOL,
                    self::DEFAULT_STR           => false,
                ],
                self::FEATURE_SYSTEM_ACCOUNT_AUTOCREATE_FOLDERS => [
                    self::LABEL                 => 'Auto-Create Folders',
                    //_('Auto-Create Folders')
                    self::DESCRIPTION           => 'Create template, trash, sent, draft and junks folders for system accounts',
                    //_('Create template, trash, sent, draft and junks folders for system accounts')
                    self::TYPE                  => self::TYPE_BOOL,
                    self::DEFAULT_STR           => true,
                ],
                self::FEATURE_SPAM_SUSPICION_STRATEGY   => [
                    self::LABEL                 => 'Enable Spam Suspicion Strategy',
                    //_('Enable Spam Suspicion Strategy')
                    self::DESCRIPTION           => 'Enable Spam Suspicion Strategy',
                    //_('SEnable pam Suspicion Strategy')
                    self::TYPE                  => self::TYPE_BOOL,
                    self::DEFAULT_STR           => false,
                ],
            ],
            self::DEFAULT_STR => [],
        ],
        self::SPAM_SUSPICION_STRATEGY => array(
            //_('Spam Suspicion Strategy')
            'label'                 => 'Spam Suspicion Strategy',
            // _('Spam Suspicion Strategy')
            'description'           => 'Spam Suspicion Strategy',
            'type'                  => Tinebase_Config_Abstract::TYPE_STRING,
            'clientRegistryInclude' => FALSE,
            'setByAdminModule'      => TRUE,
            'setBySetupModule'      => FALSE,
            'default'               => null,
        ),
        self::SPAM_SUSPICION_STRATEGY_CONFIG => array(
            //_('Spam Suspicion Strategy Config')
            'label'                 => 'Spam Suspicion Strategy Config',
            // _('Spam Suspicion Strategy Config')
            'description'           => 'Spam Suspicion Strategy Config',
            'type'                  => Tinebase_Config_Abstract::TYPE_ARRAY,
            'clientRegistryInclude' => FALSE,
            'setByAdminModule'      => TRUE,
            'setBySetupModule'      => FALSE,
            'default'               => null,
        ),
        self::SPAM_USERPROCESSING_PIPELINE => array(
            //_('Spam User Processing Pipeline')
            'label'                 => 'Spam User Processing Pipeline',
            // _('Spam User Processing Pipeline')
            'description'           => 'Spam User Processing Pipeline',
            'type'                  => Tinebase_Config_Abstract::TYPE_ARRAY,
            'clientRegistryInclude' => FALSE,
            'setByAdminModule'      => TRUE,
            'setBySetupModule'      => FALSE,
            'default'               => null,
        ),
        self::SPAM_INFO_DIALOG_CONTENT => array(
            //_('Confirm SPAM Suspicion Message')
            'label'                 => 'Confirm SPAM Suspicion Message',
            // _('Message for users when they press the info button in a SPAM suspicion toolbar.')
            'description'           => 'Message for users when they press the info button in a SPAM suspicion toolbar.',
            'type'                  => Tinebase_Config_Abstract::TYPE_STRING,
            'clientRegistryInclude' => TRUE,
            'setByAdminModule'      => TRUE,
            'setBySetupModule'      => FALSE,
            'default'               => null,
        ),
        self::FILTER_EMAIL_URIS => array(
            //_('Filter E-Mail URIs')
            self::LABEL                 => 'Filter E-Mail URIs',
            // _('Should the email body uris be filtered. Only anchors with URIs are allowed if this is turned on')
            self::DESCRIPTION           => 'Should the email body uris be filtered. Only anchors with URIs are allowed if this is turned on',
            self::TYPE                  => Tinebase_Config_Abstract::TYPE_BOOL,
            self::CLIENTREGISTRYINCLUDE => FALSE,
            self::SETBYADMINMODULE      => FALSE,
            self::SETBYSETUPMODULE      => TRUE,
            self::DEFAULT_STR           => true,
        ),
        /**
         * possible keys/values::
         *
         * 'sent_folder'       => 'Sent',
         * 'trash_folder'      => 'Trash',
         * 'drafts_folder'     => 'Drafts',
         * 'templates_folder'  => 'Templates',
         */
        self::SYSTEM_ACCOUNT_FOLDER_DEFAULTS => array(
            //_('System Account Folder Defaults')
            self::LABEL                 => 'System Account Folder Defaults',
            // _('Paths of the special folders (like Sent, Trash, ...)')
            self::DESCRIPTION           => 'Paths of the special folders (like Sent, Trash, ...)',
            self::TYPE                  => Tinebase_Config_Abstract::TYPE_ARRAY,
            self::CLIENTREGISTRYINCLUDE => FALSE,
            self::SETBYADMINMODULE      => TRUE,
            self::SETBYSETUPMODULE      => TRUE,
            self::DEFAULT_STR           => null,
        ),
        self::IMAP_ALLOW_SELF_SIGNED_TLS_CERT => array(
            //_('Allow self signed TLS cert for IMAP connection')
            self::LABEL                 => 'Allow self signed TLS cert for IMAP connection',
            self::DESCRIPTION           => '',
            self::TYPE                  => Tinebase_Config_Abstract::TYPE_BOOL,
            self::CLIENTREGISTRYINCLUDE => FALSE,
            self::SETBYADMINMODULE      => FALSE,
            self::SETBYSETUPMODULE      => TRUE,
            self::DEFAULT_STR           => false,
        ),
        self::SIEVE_REDIRECT_ONLY_INTERNAL => array(
            //_('Sieve Redirect Only Internal')
            self::LABEL                 => 'Sieve Redirect Only Internal',
            // _('Allow only sieve redirect rules to internal (primary/secondary) email addresses')
            self::DESCRIPTION           => 'Allow only sieve redirect rules to internal (primary/secondary) email addresses',
            self::TYPE                  => Tinebase_Config_Abstract::TYPE_BOOL,
            self::CLIENTREGISTRYINCLUDE => true,
            self::SETBYADMINMODULE      => true,
            self::SETBYSETUPMODULE      => false,
            self::DEFAULT_STR           => false,
        ),
        self::SIEVE_ADMIN_BOUNCE_NOTIFICATION_EMAIL => array(
            //_('Sieve Notification Bounces Reporting Email')
            self::LABEL                 => 'Sieve Notification Bounces Reporting Email',
            // _('Sieve Notification Bounces Reporting Email')
            self::DESCRIPTION           => 'Sieve Notification Bounces Reporting Email',
            self::TYPE                  => Tinebase_Config_Abstract::TYPE_STRING,
            self::CLIENTREGISTRYINCLUDE => false,
            self::SETBYADMINMODULE      => true,
            self::SETBYSETUPMODULE      => false,
            self::DEFAULT_STR           => null,
        ),
        self::FLAG_ICON_OWN_DOMAIN => array(
            //_('URL icon path for own domain')
            self::LABEL                 => 'URL icon path for own domain',
            //_('Used to mark messages from configured primary and secondary domains')
            self::DESCRIPTION           => 'Used to mark messages from configured primary and secondary domains',
            self::TYPE                  => 'string',
            self::DEFAULT_STR           => 'favicon/svg',
            self::CLIENTREGISTRYINCLUDE => true,
            self::SETBYADMINMODULE      => false,
            self::SETBYSETUPMODULE      => false,
        ),
        self::FLAG_ICON_OTHER_DOMAIN => array(
            //_('URL icon path for other domains')
            self::LABEL                 => 'URL icon path for other domains',
            //_('Used to mark messages from all other domains')
            self::DESCRIPTION           => 'Used to mark messages from all other domains',
            self::TYPE                  => 'string',
            self::DEFAULT_STR           => 'favicon/svg',
            self::CLIENTREGISTRYINCLUDE => true,
            self::SETBYADMINMODULE      => false,
            self::SETBYSETUPMODULE      => false,
        ),
        self::FLAG_ICON_OTHER_DOMAIN_REGEX => array(
            //_('Other domain regex for FLAG_ICON_OTHER_DOMAIN')
            self::LABEL                 => 'Other domain regex for FLAG_ICON_OTHER_DOMAIN',
            //_('Other domain regex for FLAG_ICON_OTHER_DOMAIN')
            self::DESCRIPTION           => 'Other domain regex for FLAG_ICON_OTHER_DOMAIN',
            self::TYPE                  => 'string',
            self::CLIENTREGISTRYINCLUDE => true,
            self::SETBYADMINMODULE      => false,
            self::SETBYSETUPMODULE      => false,
        ),
        self::SIEVE_NOTIFICATION_MOVE_STATUS => [
            self::LABEL                 => 'Auto-move sieve notifications status',
            self::DESCRIPTION           => 'Available auto-move sieve notifications status',
            self::TYPE                  => self::TYPE_KEYFIELD_CONFIG,
            self::CLIENTREGISTRYINCLUDE => true,
            self::DEFAULT_STR           => [
                self::RECORDS               => [
                    ['id' => Felamimail_Model_Account::SIEVE_NOTIFICATION_MOVE_ACTIVE, 'value' => 'Active', 'system' => true], // _('Active')
                    ['id' => Felamimail_Model_Account::SIEVE_NOTIFICATION_MOVE_AUTO, 'value' => 'Auto', 'system' => true], // _('Auto')
                    ['id' => Felamimail_Model_Account::SIEVE_NOTIFICATION_MOVE_INACTIVE, 'value' => 'Inactive', 'system' => true], // _('Inactive')
                ],
                self::DEFAULT_STR => Felamimail_Model_Account::SIEVE_NOTIFICATION_MOVE_AUTO,
            ],
        ],
    );

    /**
     * (non-PHPdoc)
     * @see tine20/Tinebase/Config/Abstract::$_appName
     */
    protected $_appName = 'Felamimail';

    /**
     * holds the instance of the singleton
     *
     * @var Tinebase_Config
     */
    private static $_instance = NULL;

    /**
     * the constructor
     *
     * don't use the constructor. use the singleton
     */
    private function __construct() {}

    /**
     * the constructor
     *
     * don't use the constructor. use the singleton
     */
    private function __clone() {}

    /**
     * Returns instance of Tinebase_Config
     *
     * @return Tinebase_Config
     */
    public static function getInstance()
    {
        if (self::$_instance === NULL) {
            self::$_instance = new self();
        }

        return self::$_instance;
    }

    /**
     * (non-PHPdoc)
     * @see tine20/Tinebase/Config/Abstract::getProperties()
     */
    public static function getProperties()
    {
        return self::$_properties;
    }
}
