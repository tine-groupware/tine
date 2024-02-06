<?php
/**
 * Tine 2.0
 * 
 * @package     Felamimail
 * @subpackage  Model
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2009-2020 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 * @todo        move more fields (like folder names) to xprops)
 * @todo        convert to MCV2
 */

/**
 * class to hold Account data
 * 
 * @property  string $trash_folder
 * @property  string $sent_folder
 * @property  string $drafts_folder
 * @property  string $templates_folder
 * @property  string $sieve_vacation_active
 * @property  string $display_format
 * @property  string $delimiter
 * @property  string $type
 * @property  string $signature_position
 * @property  string $email
 * @property  string $user_id
 * @property  string $sieve_notification_email
 * @property  string $sieve_notification_move
 * @property  string $sieve_notification_move_folder
 * @property  string $sieve_hostname
 * @property  string $migration_approved
 * @property  string $message_sent_copy_behavior
 * @property  Tinebase_Record_Abstract $contact_id
 * @property  Tinebase_Model_EmailUser $email_imap_user
 * @property  Tinebase_Model_EmailUser $email_smtp_user
 * @property  Tinebase_Record_RecordSet $aliases (Tinebase_Model_EmailUser_Alias)
 * @property  Tinebase_Record_RecordSet $forwards (Tinebase_Model_EmailUser_Forward)
 *
 * @package   Felamimail
 * @subpackage    Model
 */
class Felamimail_Model_Account extends Tinebase_EmailUser_Model_Account
{
    /**
     * holds the configuration object (must be declared in the concrete class)
     *
     * @var Tinebase_ModelConfiguration
     */
    protected static $_configurationObject = NULL;

    /**
     * external email user ids (for example in dovecot/postfix sql)
     */
    public const XPROP_EMAIL_USERID_IMAP = 'emailUserIdImap';
    public const XPROP_EMAIL_USERID_SMTP = 'emailUserIdSmtp';

    /**
     * hidden from addressbook
     *
     * @var string
     */
    const VISIBILITY_HIDDEN    = 'hidden';

    /**
     * visible in addressbook
     *
     * @var string
     */
    const VISIBILITY_DISPLAYED = 'displayed';

    /**
     * sieve notification move status active
     *
     * @var string
     */
    const SIEVE_NOTIFICATION_MOVE_ACTIVE = 'ACTIVE';

    /**
     * sieve notification move status auto
     *
     * @var string
     */
    const SIEVE_NOTIFICATION_MOVE_AUTO = 'AUTO';

    /**
     * sieve notification move status inactive
     *
     * @var string
     */
    const SIEVE_NOTIFICATION_MOVE_INACTIVE = 'INACTIVE';
    
    /**
     * 
     * copy message to configured sent folder, only available when compose/reply/forward message
     *
     * @var string
     */
    const MESSAGE_COPY_FOLDER_SENT = 'sent'; 

    /**
     * copy message to folder of source mail, only available when compose/reply/forward message
     *
     * @var string
     */
    const MESSAGE_COPY_FOLDER_SOURCE = 'source';

    /**
     * skip copy message to folder
     *
     * @var string
     */
    const MESSAGE_COPY_FOLDER_SKIP = 'skip';

    /**
     * Holds the model configuration (must be assigned in the concrete class)
     *
     * @var array
     */
    protected static $_modelConfiguration = [
        # TODO switch to mcv2
        # self::VERSION => 27,
        'recordName' => 'Account',
        'recordsName' => 'Accounts', // ngettext('Account', 'Accounts', n)
        'containerName' => 'Email Accounts', // ngettext('Email Account', 'Email Accounts', n)
        'containersName' => 'Email Accounts',
        'hasRelations' => false,
        'copyRelations' => false,
        'hasCustomFields' => false,
        'hasSystemCustomFields' => false,
        'hasNotes' => false,
        'hasTags' => false,
        'modlogActive' => true,
        'hasAttachments' => false,
        'createModule' => false,
        'exposeHttpApi' => false,
        'exposeJsonApi' => true,
        'multipleEdit' => false,
        self::HAS_XPROPS    => true,

        'titleProperty' => 'name',
        'appName' => 'Felamimail',
        'modelName' => 'Account',

        self::FIELDS => [
            'user_id' => [
                self::TYPE => self::TYPE_USER,
                self::LABEL => 'User', // _('User')
                self::NULLABLE => true,
                self::VALIDATORS => [
                    Zend_Filter_Input::ALLOW_EMPTY => true,
                    Zend_Filter_Input::DEFAULT_VALUE => null
                ],
                self::LENGTH => 40,
            ],
            'type' => [
                self::TYPE => self::TYPE_KEY_FIELD,
                self::LABEL => 'Type', // _('Type')
                self::NAME => 'mailAccountType',
                self::VALIDATORS => [
                    Zend_Filter_Input::ALLOW_EMPTY => false,
                    Zend_Filter_Input::DEFAULT_VALUE => Tinebase_EmailUser_Model_Account::TYPE_USER],
                     ['InArray', [
                         Tinebase_EmailUser_Model_Account::TYPE_USER_INTERNAL,
                         Tinebase_EmailUser_Model_Account::TYPE_USER,
                         Tinebase_EmailUser_Model_Account::TYPE_SHARED,
                         Tinebase_EmailUser_Model_Account::TYPE_ADB_LIST,
                         Tinebase_EmailUser_Model_Account::TYPE_SYSTEM,
                     ]]
            ],
            'name' => [
                self::TYPE => self::TYPE_STRING,
                self::LENGTH => 255,
                self::NULLABLE => true,
                self::LABEL => 'Account Name', // _('Account Name')
                self::VALIDATORS => [Zend_Filter_Input::ALLOW_EMPTY => true],
                self::QUERY_FILTER              => true,
            ],
            'migration_approved' => [
                self::TYPE => self::TYPE_BOOLEAN,
                self::NULLABLE => true,
                self::LABEL => 'Migration Approved', // _('Migration Approved')
                self::VALIDATORS => [
                    Zend_Filter_Input::ALLOW_EMPTY => true,
                    Zend_Filter_Input::DEFAULT_VALUE => false
                ],
            ],
            'host' => [
                self::TYPE => self::TYPE_STRING,
                self::LENGTH => 255,
                self::NULLABLE => true,
                self::LABEL => 'IMAP Host', // _('IMAP Host')
                self::VALIDATORS => [Zend_Filter_Input::ALLOW_EMPTY => true],
                self::QUERY_FILTER              => true,
            ],
            'port' => [
                self::TYPE => self::TYPE_INTEGER,
                self::NULLABLE => true,
                self::LABEL => 'IMAP Port', // _('IMAP Port')
                self::SHY => true,
                self::VALIDATORS => [
                    Zend_Filter_Input::ALLOW_EMPTY => true,
                    Zend_Filter_Input::DEFAULT_VALUE => 143
                ],
                self::INPUT_FILTERS             => [
                    Zend_Filter_Empty::class => null,
                ],
            ],
            'ssl' => [
                self::TYPE => self::TYPE_STRING,
                self::LENGTH => 32,
                self::LABEL => 'IMAP SSL', // _('IMAP SSL')
                self::SHY => true,
                self::VALIDATORS => [
                    Zend_Filter_Input::ALLOW_EMPTY => true,
                    Zend_Filter_Input::DEFAULT_VALUE => self::SECURE_TLS,
                    ['InArray', [self::SECURE_NONE, self::SECURE_SSL, self::SECURE_TLS]]
                ],
                self::INPUT_FILTERS             => [
                    Zend_Filter_Empty::class => self::SECURE_TLS,
                    Zend_Filter_StringTrim::class,
                    Zend_Filter_StringToLower::class
                ],
            ],
            'credentials_id' => [
                self::TYPE => self::TYPE_STRING,
                self::LENGTH => 40,
                # self::SYSTEM => true,
                self::VALIDATORS => [
                    Zend_Filter_Input::ALLOW_EMPTY => true,
                    Zend_Filter_Input::DEFAULT_VALUE => null,
                ],
                self::INPUT_FILTERS             => [
                    Zend_Filter_Empty::class => null,
                ],
                self::OMIT_MOD_LOG => true,
                self::NULLABLE                  => true,
            ],
            // imap username
            'user' => [
                self::TYPE => self::TYPE_STRING,
                self::SYSTEM => true, // ?
                self::IS_VIRTUAL => true,
                self::VALIDATORS => [
                    Zend_Filter_Input::ALLOW_EMPTY => true,
                ],
            ],
            // imap pw
            'password' => [
                self::TYPE => self::TYPE_STRING,
                self::SYSTEM => true, // ?
                self::IS_VIRTUAL => true,
                self::SPECIAL_TYPE => self::SPECIAL_TYPE_PASSWORD,
                self::VALIDATORS => [
                    Zend_Filter_Input::ALLOW_EMPTY => true,
                ],
            ],
            'sent_folder' => [
                self::TYPE => self::TYPE_STRING,
                self::LENGTH => 255,
                self::NULLABLE => true,
                self::LABEL => 'Sent Folder', // _('Sent Folder')
                self::SHY => true,
                self::VALIDATORS => [
                    Zend_Filter_Input::ALLOW_EMPTY => true,
                    Zend_Filter_Input::DEFAULT_VALUE => 'Sent'
                ],
            ],
            'trash_folder' => [
                self::TYPE => self::TYPE_STRING,
                self::LENGTH => 255,
                self::NULLABLE => true,
                self::LABEL => 'Trash Folder', // _('Trash Folder')
                self::SHY => true,
                self::VALIDATORS => [
                    Zend_Filter_Input::ALLOW_EMPTY => true,
                    Zend_Filter_Input::DEFAULT_VALUE => 'Trash'
                ],
            ],
            'drafts_folder' => [
                self::TYPE => self::TYPE_STRING,
                self::LENGTH => 255,
                self::NULLABLE => true,
                self::LABEL => 'Drafts Folder', // _('Drafts Folder')
                self::SHY => true,
                self::VALIDATORS => [
                    Zend_Filter_Input::ALLOW_EMPTY => true,
                    Zend_Filter_Input::DEFAULT_VALUE => 'Drafts'
                ],
            ],
            'templates_folder' => [
                self::TYPE => self::TYPE_STRING,
                self::LENGTH => 255,
                self::NULLABLE => true,
                self::LABEL => 'Templates Folder', // _('Templates Folder')
                self::SHY => true,
                self::VALIDATORS => [
                    Zend_Filter_Input::ALLOW_EMPTY => true,
                    Zend_Filter_Input::DEFAULT_VALUE => 'Templates'
                ],
            ],
            'has_children_support' => [
                self::TYPE => self::TYPE_BOOLEAN,
                self::SYSTEM => true,
                self::NULLABLE => true,
                self::VALIDATORS => [
                    Zend_Filter_Input::ALLOW_EMPTY => true,
                    Zend_Filter_Input::DEFAULT_VALUE => true
                ],
            ],
            'delimiter' => [
                self::TYPE => self::TYPE_STRING,
                self::LENGTH => 1,
                self::SYSTEM => true,
                self::NULLABLE => true,
                self::VALIDATORS => [
                    Zend_Filter_Input::ALLOW_EMPTY => true,
                    Zend_Filter_Input::DEFAULT_VALUE => '/'
                ],
            ],
            'display_format' => [
                self::TYPE => self::TYPE_STRING,
                self::LENGTH => 64,
                self::LABEL => 'Display Format', // _('Display Format')
                self::SHY => true,
                self::VALIDATORS => [
                    Zend_Filter_Input::ALLOW_EMPTY => true,
                    Zend_Filter_Input::DEFAULT_VALUE => self::DISPLAY_HTML,
                    ['InArray', [self::DISPLAY_HTML, self::DISPLAY_PLAIN, self::DISPLAY_CONTENT_TYPE]]
                ],
                self::INPUT_FILTERS             => [
                    Zend_Filter_Empty::class => self::DISPLAY_HTML,
                    Zend_Filter_StringTrim::class,
                    Zend_Filter_StringToLower::class
                ],
                self::NULLABLE                  => true,
            ],
            'compose_format' => [
                self::TYPE => self::TYPE_STRING,
                self::LENGTH => 64,
                self::LABEL => 'Compose Format', // _('Compose Format')
                self::SHY => true,
                self::VALIDATORS => [
                    Zend_Filter_Input::ALLOW_EMPTY => true,
                    Zend_Filter_Input::DEFAULT_VALUE => self::DISPLAY_HTML,
                    ['InArray', [self::DISPLAY_HTML, self::DISPLAY_PLAIN]]
                ],
                self::NULLABLE                  => true,
            ],
            'reply_to' => [
                self::TYPE => self::TYPE_STRING,
                self::LENGTH => 255,
                self::NULLABLE => true,
                self::LABEL => 'Reply-To', // _('Reply-To')
                self::SHY => true,
                self::VALIDATORS => [Zend_Filter_Input::ALLOW_EMPTY => true],
            ],
            'ns_personal' => [
                self::TYPE => self::TYPE_STRING,
                self::LENGTH => 255,
                self::NULLABLE => true,
                self::VALIDATORS => [Zend_Filter_Input::ALLOW_EMPTY => true],
            ],
            'ns_other' => [
                self::TYPE => self::TYPE_STRING,
                self::LENGTH => 255,
                self::NULLABLE => true,
                self::VALIDATORS => [Zend_Filter_Input::ALLOW_EMPTY => true],
            ],
            'ns_shared' => [
                self::TYPE => self::TYPE_STRING,
                self::LENGTH => 255,
                self::NULLABLE => true,
                self::VALIDATORS => [Zend_Filter_Input::ALLOW_EMPTY => true],
            ],
            'email' => [
                self::TYPE => self::TYPE_STRING,
                self::LENGTH => 255,
                self::NULLABLE => true,
                self::LABEL => 'E-Mail', // _('E-Mail')
                self::VALIDATORS => [Zend_Filter_Input::ALLOW_EMPTY => true],
                self::QUERY_FILTER              => true,
            ],
            // sql: from_email + from_name
            'from' => [
                self::TYPE => self::TYPE_STRING,
                self::LENGTH => 512,
                self::NULLABLE => true,
                self::LABEL => 'From', // _('From')
                self::VALIDATORS => [Zend_Filter_Input::ALLOW_EMPTY => true],
                self::QUERY_FILTER              => true,
            ],
            'organization' => [
                self::TYPE => self::TYPE_STRING,
                self::LENGTH => 255,
                self::NULLABLE => true,
                self::LABEL => 'Organization', // _('Organization')
                self::VALIDATORS => [Zend_Filter_Input::ALLOW_EMPTY => true],
                self::QUERY_FILTER              => true,
            ],
            // only used as "transport" field for currently selected/default signature
            'signature' => [
                self::TYPE => self::TYPE_VIRTUAL,
                self::NULLABLE => true,
                self::LABEL => 'Signature', // _('Signature')
                self::SHY => true,
            ],
            'signatures' => [
                self::VALIDATORS => array(Zend_Filter_Input::ALLOW_EMPTY => TRUE, Zend_Filter_Input::DEFAULT_VALUE => NULL),
                self::LABEL => 'Signatures', // _('Signatures')
                self::TYPE => self::TYPE_RECORDS,
                self::NULLABLE => true,
                self::DEFAULT_VAL => null,
                self::CONFIG => array(
                    self::APP_NAME  => 'Felamimail',
                    'modelName'        => 'Signature',
                    'refIdField'       => 'account_id',
                    'recordClassName' => Felamimail_Model_Signature::class,
                    'controllerClassName' => Felamimail_Controller_Signature::class,
                    'dependentRecords' => true
                ),
                'recursiveResolving' => true,
            ],
            'signature_position' => [
                self::TYPE => self::TYPE_STRING,
                self::LENGTH => 64,
                self::LABEL => 'Signature Position', // _('Signature Position')
                self::SHY => true,
                self::VALIDATORS => [
                    Zend_Filter_Input::ALLOW_EMPTY => true,
                    Zend_Filter_Input::DEFAULT_VALUE => self::SIGNATURE_ABOVE_QUOTE,
                    ['InArray', [self::SIGNATURE_ABOVE_QUOTE, self::SIGNATURE_BELOW_QUOTE]]
                ],
            ],
            'smtp_hostname' => [
                self::TYPE => self::TYPE_STRING,
                self::LENGTH => 255,
                self::NULLABLE => true,
                self::LABEL => 'SMTP Host', // _('SMTP Host')
                self::SHY => true,
                self::VALIDATORS => [Zend_Filter_Input::ALLOW_EMPTY => true],
                self::QUERY_FILTER              => true,
            ],
            'smtp_port' => [
                self::TYPE => self::TYPE_INTEGER,
                self::NULLABLE => true,
                self::LABEL => 'SMTP Port', // _('SMTP Port')
                self::SHY => true,
                self::VALIDATORS => [
                    Zend_Filter_Input::ALLOW_EMPTY => true,
                    Zend_Filter_Input::DEFAULT_VALUE => 25
                ],
                self::INPUT_FILTERS             => [
                    Zend_Filter_Empty::class => null,
                ],
            ],
            'smtp_ssl' => [
                self::TYPE => self::TYPE_STRING,
                self::LENGTH => 32,
                self::LABEL => 'SMTP SSL', // _('SMTP SSL')
                self::SHY => true,
                self::VALIDATORS => [
                    Zend_Filter_Input::ALLOW_EMPTY => true,
                    Zend_Filter_Input::DEFAULT_VALUE => self::SECURE_TLS,
                    ['InArray', [self::SECURE_NONE, self::SECURE_SSL, self::SECURE_TLS]]
                ],
                self::INPUT_FILTERS             => [
                    Zend_Filter_Empty::class => self::SECURE_TLS,
                    Zend_Filter_StringTrim::class,
                    Zend_Filter_StringToLower::class
                ],
            ],
            'smtp_auth' => [
                self::TYPE => self::TYPE_STRING,
                self::LENGTH => 32,
                self::LABEL => 'SMTP Authentication', // _('SMTP Authentication')
                self::SHY => true,
                self::VALIDATORS => [
                    Zend_Filter_Input::ALLOW_EMPTY => true,
                    Zend_Filter_Input::DEFAULT_VALUE => 'login',
                    ['InArray', ['none', 'plain', 'login']]
                ],
                self::NULLABLE                  => true,
            ],
            'smtp_credentials_id' => [
                self::TYPE => self::TYPE_STRING,
                self::LENGTH => 40,
                # self::SYSTEM => true,
                self::VALIDATORS => [
                    Zend_Filter_Input::ALLOW_EMPTY => true,
                    Zend_Filter_Input::DEFAULT_VALUE => null,
                ],
                self::OMIT_MOD_LOG => true,
                self::INPUT_FILTERS             => [
                    Zend_Filter_Empty::class => null,
                ],
                self::NULLABLE                  => true,
            ],
            'smtp_user' => [
                self::TYPE => self::TYPE_STRING,
                self::SYSTEM => true, // ?
                self::IS_VIRTUAL => true,
                self::VALIDATORS => [
                    Zend_Filter_Input::ALLOW_EMPTY => true,
                ],
            ],
            'smtp_password' => [
                self::TYPE => self::TYPE_STRING,
                self::SYSTEM => true, // ?
                self::IS_VIRTUAL => true,
                self::VALIDATORS => [
                    Zend_Filter_Input::ALLOW_EMPTY => true,
                ],
            ],
            'sieve_hostname' => [
                self::TYPE => self::TYPE_STRING,
                self::LENGTH => 255,
                self::NULLABLE => true,
                self::LABEL => 'Sieve Host', // _('Sieve Host')
                self::SHY => true,
                self::VALIDATORS => [Zend_Filter_Input::ALLOW_EMPTY => true],
            ],
            'sieve_port' => [
                self::TYPE => self::TYPE_INTEGER,
                self::NULLABLE => true,
                self::LABEL => 'Sieve Port', // _('Sieve Port')
                self::SHY => true,
                self::VALIDATORS => [
                    Zend_Filter_Input::ALLOW_EMPTY => true,
                    Zend_Filter_Input::DEFAULT_VALUE => 2000
                ],
                self::INPUT_FILTERS             => [
                    Zend_Filter_Empty::class => null,
                ],
            ],
            'sieve_ssl' => [
                self::TYPE => self::TYPE_STRING,
                self::LENGTH => 32,
                self::LABEL => 'Sieve SSL', // _('Sieve SSL')
                self::SHY => true,
                self::VALIDATORS => [
                    Zend_Filter_Input::ALLOW_EMPTY => true,
                    Zend_Filter_Input::DEFAULT_VALUE => self::SECURE_TLS,
                    ['InArray', [self::SECURE_NONE, self::SECURE_SSL, self::SECURE_TLS]]
                ],
                self::INPUT_FILTERS             => [
                    Zend_Filter_Empty::class => self::SECURE_TLS,
                    Zend_Filter_StringTrim::class,
                    Zend_Filter_StringToLower::class
                ],
                self::NULLABLE                  => true,
            ],
            'sieve_vacation_active' => [
                self::TYPE => self::TYPE_BOOLEAN,
                self::SYSTEM => true,
                self::VALIDATORS => [
                    Zend_Filter_Input::ALLOW_EMPTY => true,
                    Zend_Filter_Input::DEFAULT_VALUE => false,
                ],
            ],
            'sieve_notification_email' => [
                self::TYPE => self::TYPE_TEXT,
                self::LENGTH => 16000,
                self::SYSTEM => true,
                self::VALIDATORS => [
                    Zend_Filter_Input::ALLOW_EMPTY => true,
                    Zend_Filter_Input::DEFAULT_VALUE => null,
                ],
                self::INPUT_FILTERS             => [],
                self::NULLABLE                  => true,
            ],
            'sieve_notification_move' => [
                self::TYPE => self::TYPE_KEY_FIELD,
                self::LABEL => 'Auto-move notifications', // _('Auto-move notifications')
                self::VALIDATORS                => [Zend_Filter_Input::ALLOW_EMPTY => true],
                self::NAME => Felamimail_Config::SIEVE_NOTIFICATION_MOVE_STATUS,
            ],
            'sieve_vacation' => [
                self::TYPE => self::TYPE_VIRTUAL,
                self::VALIDATORS                => [
                    Zend_Filter_Input::ALLOW_EMPTY      => true,
                ],
                self::OMIT_MOD_LOG              => true,
                self::SYSTEM                    => true,
                self::SHY => true,
            ],
            'sieve_rules' => [
                self::TYPE => self::TYPE_VIRTUAL,
                self::VALIDATORS                => [
                    Zend_Filter_Input::ALLOW_EMPTY      => true,
                ],
                self::OMIT_MOD_LOG              => true,
                self::SYSTEM                    => true,
                self::SHY => true,
            ],
            'sieve_notification_move_folder' => [
                self::TYPE => self::TYPE_STRING,
                self::LABEL => 'Auto-move notifications folder', // _('Auto-move notifications folder')
                self::LENGTH => 255,
                self::SYSTEM => true,
                self::VALIDATORS => [
                    Zend_Filter_Input::ALLOW_EMPTY => true,
                    Zend_Filter_Input::DEFAULT_VALUE => null,
                ],
                self::NULLABLE                  => true
            ],
            'sieve_custom' => [
                self::TYPE => self::TYPE_VIRTUAL,
                self::VALIDATORS                => [
                    Zend_Filter_Input::ALLOW_EMPTY      => true,
                ],
                self::OMIT_MOD_LOG              => true,
                self::SYSTEM                    => true,
                self::SHY => true,
            ],
            'all_folders_fetched' => [
                self::TYPE => self::TYPE_BOOLEAN,
                // client only
                self::IS_VIRTUAL => true,
                self::SYSTEM => true,
                self::VALIDATORS => [
                    Zend_Filter_Input::ALLOW_EMPTY => true,
                    Zend_Filter_Input::DEFAULT_VALUE => false,
                ],
            ],
            'imap_status' => [
                self::TYPE => self::TYPE_STRING,
                // client only
                self::IS_VIRTUAL => true,
                self::SYSTEM => true,
                self::VALIDATORS => [
                    Zend_Filter_Input::ALLOW_EMPTY => true,
                    Zend_Filter_Input::DEFAULT_VALUE => 'success', // TODO an inArray validation with success|failure
                ],
            ],
            'grants'    => [
                self::TYPE => self::TYPE_VIRTUAL,
            ],
            'account_grants'    => [
                self::TYPE => self::TYPE_VIRTUAL,
            ],
            // data-transport for exports
            'email_imap_user' => [
                self::LABEL                 => 'Imap user',
                self::TYPE                  => self::TYPE_VIRTUAL,
            ],
            'email_smtp_user' => [
                self::LABEL                 => 'Smtp user',
                self::TYPE                  => self::TYPE_VIRTUAL,
            ],
            'visibility' => [
                self::TYPE =>               self::TYPE_STRING,
                self::SYSTEM => true,
                self::VALIDATORS => [
                    [Zend_Validate_InArray::class, [
                        self::VISIBILITY_HIDDEN,
                        self::VISIBILITY_DISPLAYED,
                    ]],
                    Zend_Filter_Input::ALLOW_EMPTY => false,
                    Zend_Filter_Input::DEFAULT_VALUE => self::VISIBILITY_HIDDEN
                ],
                self::INPUT_FILTERS             => [
                    Zend_Filter_Empty::class => self::VISIBILITY_HIDDEN,
                ],
            ],
            'contact_id'                    => [
                self::TYPE                      => self::TYPE_RECORD,
                self::CONFIG                    => [
                    self::APP_NAME                  => Addressbook_Config::APP_NAME,
                    self::MODEL_NAME                => Addressbook_Model_Contact::MODEL_PART_NAME,
                ],
                'validators'                    => [Zend_Filter_Input::ALLOW_EMPTY => true],
                self::NULLABLE => true,
            ],
            'message_sent_copy_behavior' => [
                self::TYPE =>               self::TYPE_STRING,
                self::SYSTEM => true,
                self::VALIDATORS => [
                    [Zend_Validate_InArray::class, [
                        self::MESSAGE_COPY_FOLDER_SKIP,
                        self::MESSAGE_COPY_FOLDER_SENT,
                        self::MESSAGE_COPY_FOLDER_SOURCE,
                    ]],
                    Zend_Filter_Input::ALLOW_EMPTY => false,
                    Zend_Filter_Input::DEFAULT_VALUE => self::MESSAGE_COPY_FOLDER_SENT
                ],
                self::INPUT_FILTERS             => [
                    Zend_Filter_Empty::class => self::MESSAGE_COPY_FOLDER_SENT,
                ],
            ],
        ]
    ];

    /**
     * get title
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->name;
    }

    /**
     * get imap config array
     * - decrypt pwd/user with user password
     *
     * @return array
     * @throws Felamimail_Exception
     * @throws Exception
     */
    public function getImapConfig()
    {
        $this->resolveCredentials(false);

        $result = array();
        foreach (array('host', 'port', 'user', 'password') as $field) {
            $result[$field] = $this->{$field};
        }
        
        if ($this->ssl && $this->ssl !== Felamimail_Model_Account::SECURE_NONE) {
            $result['ssl'] = strtoupper($this->ssl);
        }
        
        return $result;
    }
    
    /**
     * get smtp config
     *
     * @return array
     */
    public function getSmtpConfig()
    {
        if (! $this->smtp_user || ! $this->smtp_password) {
            $this->resolveCredentials(FALSE, TRUE, TRUE);
        }
        
        $result = array();
        
        // get values from account
        if ($this->smtp_hostname) {
            $result['hostname'] = $this->smtp_hostname;
        }
        if ($this->smtp_user) {
            $result['username'] = $this->smtp_user;
        }
        if ($this->smtp_password) {
            $result['password'] = $this->smtp_password;
        }
        if ($this->smtp_auth) {
            $result['auth'] = $this->smtp_auth;
        }
        if ($this->smtp_ssl) {
            $result['ssl'] = $this->smtp_ssl;
        }
        if ($this->smtp_port) {
            $result['port'] = $this->smtp_port;
        }
        
        if (isset($result['auth']) && $result['auth'] === 'none') {
            unset($result['username']);
            unset($result['password']);
            unset($result['auth']);
        }
        if ((isset($result['ssl']) || array_key_exists('ssl', $result)) && $result['ssl'] == 'none') {
            unset($result['ssl']);
        }
        
        return $result;
    }

    /**
     * get sieve config array
     *
     * @return array
     * 
     * @todo add sieve credentials? this uses imap credentials atm.
     */
    public function getSieveConfig()
    {
        $this->resolveCredentials(FALSE);
        
        return array(
            'host'      => $this->sieve_hostname,
            'port'      => $this->sieve_port, 
            'ssl'       => ($this->sieve_ssl && $this->sieve_ssl !== self::SECURE_NONE) ? $this->sieve_ssl : FALSE,
            'username'  => $this->user,
            'password'  => $this->password,
        );
    }
    
    /**
     * to array
     *
     * @param boolean $_recursive
     * @return array
     */
    public function toArray($_recursive = TRUE)
    {
        $result = parent::toArray($_recursive);

        // don't show password
        unset($result['password']);
        unset($result['smtp_password']);
        
        return $result;
    }

    /**
     * resolve imap or smtp credentials
     *
     * @param boolean $_onlyUsername
     * @param boolean $_throwException
     * @param boolean $_smtp
     * @return boolean
     * @throws Felamimail_Exception
     * @throws Exception
     *
     * @refactor split this up
     */
    public function resolveCredentials($_onlyUsername = TRUE, $_throwException = FALSE, $_smtp = FALSE)
    {
        if ($_smtp) {
            $passwordField      = 'smtp_password';
            $userField          = 'smtp_user';
            $credentialsField   = 'smtp_credentials_id';
        } else {
            $passwordField      = 'password';
            $userField          = 'user';
            $credentialsField   = 'credentials_id';
        }

        $credentials = new Tinebase_Model_CredentialCache();
        if (! $this->{$userField} || (! $this->{$passwordField} && ! $_onlyUsername)) {

            $credentialsBackend = Tinebase_Auth_CredentialCache::getInstance();

            if ($this->type === self::TYPE_SYSTEM || $this->type === self::TYPE_USER || $this->type === self::TYPE_USER_INTERNAL) {
                $credentials = Tinebase_Core::getUserCredentialCache();
                if (! $credentials) {
                    if (Tinebase_Core::isLogLevel(Zend_Log::WARN)) {
                        Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ .
                            ' No user credential cache found (adapter: '
                            . get_class(Tinebase_Auth_CredentialCache::getInstance()->getCacheAdapter()) . ')');
                    }
                    return false;
                }
                try {
                    $credentialsBackend->getCachedCredentials($credentials);
                } catch (Exception $e) {
                    Tinebase_Core::getLogger()->crit(__METHOD__ . '::' . __LINE__
                        . ' Something went wrong with the CredentialsCache');
                    if ($_throwException) {
                        throw $e;
                    }
                    return false;
                }
                $credentialCachePwd = substr($credentials->password, 0, 24);
            } elseif ($this->type === self::TYPE_SHARED || $this->type === self::TYPE_ADB_LIST) {
                $credentialCachePwd = Tinebase_Auth_CredentialCache_Adapter_Shared::getKey();
            } else {
                throw new Tinebase_Exception_UnexpectedValue('type ' . $this->type . ' unknown or empty');
            }

            // TYPE_SYSTEM + TYPE_USER_INTERNAL never has its own credential cache, it uses the users one
            if (! in_array($this->type, [
                self::TYPE_SYSTEM,
                self::TYPE_USER_INTERNAL
            ])) {
                try {
                    // NOTE: cache cleanup process might have removed the cache
                    $credentials = $credentialsBackend->get($this->{$credentialsField});
                    $credentials->key = $credentialCachePwd;
                    $credentialsBackend->getCachedCredentials($credentials);
                } catch (Tinebase_Exception_NotFound $tenf) {
                    // try shared credentials key if external account + configured
                    if ($this->type === self::TYPE_USER) {
                        $credentials->key = Tinebase_Auth_CredentialCache_Adapter_Shared::getKey();
                        try {
                            $credentialsBackend->getCachedCredentials($credentials);
                        } catch (Tinebase_Exception_NotFound $tenf2) {
                            if ($_throwException) {
                                throw $tenf2;
                            }
                            return false;
                        }
                    } else {
                        // try to use imap credentials & reset smtp credentials if different
                        if ($_smtp) {
                            // TODO ask user for smtp creds if this fails
                            if ($this->smtp_credentials_id !== $this->credentials_id) {
                                $this->smtp_credentials_id = $this->credentials_id;
                                Felamimail_Controller_Account::getInstance()->update($this);
                                return $this->resolveCredentials($_onlyUsername, $_throwException, $_smtp);
                            }
                        }

                        if ($_throwException) {
                            throw $tenf;
                        }
                        return false;
                    }
                } catch (Exception $e) {
                    if ($_throwException) {
                        throw $e;
                    }
                    return false;
                }
            } else {
                // just use tine user credentials to connect to mailserver / or use credentials from config if set
                $imapConfig = Tinebase_Config::getInstance()->get(Tinebase_Config::IMAP,
                    new Tinebase_Config_Struct())->toArray();

                // allow to set credentials in config
                if (isset($imapConfig['user']) && isset($imapConfig['password']) && !empty($imapConfig['user'])) {
                    if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) {
                        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ .
                            ' Using credentials from config for system account.');
                    }
                    $credentials->username = $imapConfig['user'];
                    $credentials->password = $imapConfig['password'];
                }

                // allow to set pw suffix in config
                if (isset($imapConfig['pwsuffix']) && !preg_match('/' . preg_quote($imapConfig['pwsuffix'], '/') . '$/',
                        $credentials->password)) {
                    if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) {
                        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ .
                            ' Appending configured pwsuffix to system account password.');
                    }
                    $credentials->password .= $imapConfig['pwsuffix'];
                }

                if (!isset($imapConfig['user']) || empty($imapConfig['user'])) {
                    $credentials->username = Tinebase_EmailUser::getAccountUsername($this, $credentials->username);
                }
            }

            if (!$this->{$userField}) {
                $this->{$userField} = $credentials->username;
            }

            if (!$this->{$passwordField} && !$_onlyUsername) {
                $this->{$passwordField} = $credentials->password;
            }

        }
        return true;
    }

    /**
     * returns TRUE if account has capability (i.e. QUOTA, CONDSTORE, ...)
     * 
     * @param string $_capability
     * @return boolean
     */
    public function hasCapability($_capability)
    {
        $capabilities = Felamimail_Controller_Account::getInstance()->updateCapabilities($this);
        
        return ($capabilities && in_array($_capability, $capabilities['capabilities']));
    }

    public function setSignatureText()
    {
        if ($this->signature) {
            return;
        }
        $converter = Tinebase_Convert_Factory::factory($this);
        $json = $converter->fromTine20Model(clone $this);
        $this->signature = isset($json['signature']) ? $json['signature'] : null;
    }
}
