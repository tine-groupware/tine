<?php
/**
 * Tine 2.0
 *
 * the class provides functions to handle config options
 *
 * @package     Tinebase
 * @subpackage  Config
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2025 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 *
 * @todo remove all deprecated stuff
 */
class Tinebase_Config extends Tinebase_Config_Abstract
{
    public const APP_NAME = 'Tinebase';

    /**
     * the current Tinebase version
     *
     * @var int
     */
    public const TINEBASE_VERSION = 18;

    /**
     * access log rotation in days
     *
     * @var string
     */
    public const ACCESS_LOG_ROTATION_DAYS = 'accessLogRotationDays';

    /**
     * area locks
     *
     * @var string
     */
    public const AREA_LOCKS = 'areaLocks';

    /**
     * MFA providers
     *
     * @var string
     */
    public const MFA = 'mfa';

    /**
     * encourage MFA at login
     *
     * @var string
     */
    public const MFA_ENCOURAGE = 'mfa_encourage';

    public const MFA_BYPASS_NETMASKS = 'mfa_bypass_netmasks';

    /**
     * authentication backend config
     *
     * @var string
     */
    public const AUTHENTICATIONBACKEND = 'Tinebase_Authentication_BackendConfiguration';

    /**
     * authentication backend type config
     *
     * @var string
     */
    public const AUTHENTICATIONBACKENDTYPE = 'Tinebase_Authentication_BackendType';

    /**
     * allow authentication by email as optional replacement for username too
     *
     * @var string
     */
    public const AUTHENTICATION_BY_EMAIL = 'authenticationByEmail';

    public const AUTH_TOKEN_CHANNELS = 'authTokenChanels';
    public const AUTH_TOKEN_DEFAULT_TTL = 'authTokenDefaultTTL';

    /**
     * save automatic alarms when creating new record
     *
     * @var string
     */
    public const AUTOMATICALARM = 'automaticalarm';

    /**
     * availableLanguages
     *
     * @var string
     */
    public const AVAILABLE_LANGUAGES = 'availableLanguages';

    /**
     * build type
     *
     * @const string
     */
    public const BUILD_TYPE = 'buildtype';

    public const BROADCASTHUB = 'broadcasthub';
    public const BROADCASTHUB_ACTIVE = 'active';
    public const BROADCASTHUB_URL = 'url';
    public const BROADCASTHUB_REDIS = 'redis';
    public const BROADCASTHUB_REDIS_HOST = 'host';
    public const BROADCASTHUB_REDIS_PORT = 'port';
    public const BROADCASTHUB_PUBSUBNAME = 'pubsubname';

    /**
     * CACHE
     *
     * @var string
     */
    public const CACHE = 'caching';
    public const CREDENTIAL_CACHE_SHARED_KEY = 'credentialCacheSharedKey';

    public const DBLOGGER = 'dblogger';

    /**
     * DEFAULT_APP
     *
     * @var string
     */
    public const DEFAULT_APP = 'defaultApp';

    /**
     * DEFAULT_LOCALE
     *
     * @var string
     */
    public const DEFAULT_LOCALE = 'defaultLocale';

    /**
     * DEFAULT_LOCALE_AUTO
     *
     * @var string
     */
    public const DEFAULT_LOCALE_AUTO = 'auto';

    /**
     * default user role
     */
    public const DEFAULT_USER_ROLE_NAME = 'defaultUserRoleName';

    /**
     * default user role
     */
    public const DEFAULT_ADMIN_ROLE_NAME = 'defaulAdminRoleName';


    /**
     * the default currency
     *
     * @var string
     */
    public const DEFAULT_CURRENCY = 'defaultCurrency';

    /**
     * DELETED_DATA_RETENTION_TIME
     *
     * @var string
     */
    public const DELETED_DATA_RETENTION_TIME = 'deletedDataRetentionTime';

    /**
     * @var string
     */
    public const EVENT_HOOK_CLASS = 'eventHookClass';

    /**
     * @var string
     */
    public const EXTERNAL_DATABASE = 'externalDatabase';

    /**
     * emailUserIdInXprops
     */
    public const EMAIL_USER_ID_IN_XPROPS = 'emailUserIdInXprops';

    /**
     * database
     *
     * @var string
     */
    public const DATABASE = 'database';

    /**
     * INTERNET_PROXY
     *
     * @var string
     */
    public const INTERNET_PROXY = 'internetProxy';

    /**
     * imap conf name
     * 
     * @var string
     */
    public const IMAP = 'imap';

    /**
     * imap useSystemAccount
     *
     * @var string
     */
    public const IMAP_USE_SYSTEM_ACCOUNT = 'useSystemAccount';

    /**
     * logger
     *
     * @var string
     */
    public const LOGGER = 'logger';

    /**
     * suppress php exception traces
     *
     * @var string
     */
    public const SUPPRESS_EXCEPTION_TRACES = 'suppressExceptionTraces';

    /**
     * RATE_LIMITS
     *
     * @var string
     */
    public const RATE_LIMITS = 'rateLimits';
    public const RATE_LIMITS_USER = 'user';
    public const RATE_LIMITS_IP = 'ip';
    public const RATE_LIMITS_FRONTENDS = 'frontends';

    /**
     * default sales tax
     *
     * @var string
     */
    public const SALES_TAX = 'salesTax';

    /**
     * smtp conf name
     * 
     * @var string
     */
    public const SMTP = 'smtp';

    public const SMTP_CHECK_DUPLICATE_ALIAS = 'checkduplicatealias';
    public const SMTP_DESTINATION_IS_USERNAME = 'destinationisusername';
    public const SMTP_DESTINATION_ACCOUNTNAME = 'accountnamedestination';

    /**
     * sieve conf name
     * 
     * @var string
     */
    public const SIEVE = 'sieve';

    /**
     * trusted proxy config
     *
     * @var string
     */
    public const TRUSTED_PROXIES = 'trustedProxies';

    /**
     * user backend config
     * 
     * @var string
     */
    public const USERBACKEND = 'Tinebase_User_BackendConfiguration';

    public const USERBACKEND_UNAVAILABLE_SINCE = 'userBackendUnavailableSince';
    public const ACCOUNT_TWIG = 'accountTwig';
    public const ACCOUNT_TWIG_DISPLAYNAME = 'accountDisplayName';
    public const ACCOUNT_TWIG_FULLNAME = 'accountFullName';
    public const ACCOUNT_TWIG_LOGIN = 'accountLoginName';
    public const ACCOUNT_TWIG_EMAIL = 'accountEmailAddress';

    /**
     * sync options for user backend
     *
     * @var string
     */
    public const SYNCOPTIONS = 'syncOptions';

    public const PWD_CANT_CHANGE = 'pwdCantChange';

    /**
     * user backend writes user pw to sql
     *
     * @var string
     */
    public const USERBACKEND_WRITE_PW_TO_SQL = 'writePwToSql';

    /**
     * user backend type config
     * 
     * @var string
     */
    public const USERBACKENDTYPE = 'Tinebase_User_BackendType';

    /**
     * cron_disabled
     *
     * @var string
     */
    public const CRON_DISABLED = 'cron_disabled';

    /**
     * cronjob user id
     * 
     * @var string
     */
    public const CRONUSERID = 'cronuserid';

    /**
     * setup user id
     *
     * @var string
     */
    public const SETUPUSERID = 'setupuserid';

    /**
     * FEATURE_SHOW_ADVANCED_SEARCH
     *
     * @var string
     */
    public const FEATURE_SHOW_ADVANCED_SEARCH = 'featureShowAdvancedSearch';

    /**
     * FEATURE_SHOW_ADVANCED_SEARCH
     *
     * @const string
     */
    public const FEATURE_CREATE_PREVIEWS = 'featureCreatePreviews';

    /**
     * FEATURE_REMEMBER_POPUP_SIZE
     *
     * @var string
     */
    public const FEATURE_REMEMBER_POPUP_SIZE = 'featureRememberPopupSize';

    /**
     * FEATURE_FULLTEXT_INDEX
     *
     * @var string
     */
    public const FEATURE_FULLTEXT_INDEX = 'featureFullTextIndex';

    public const CACHED_CONFIG_PATH = 'cachedConfigPath';

    /**
     * FEATURE_PATH
     *
     * @var string
     */
    public const FEATURE_SEARCH_PATH = 'featureSearchPath';

    public const FEATURE_AUTODISCOVER = 'autodiscover';

    public const FEATURE_AUTODISCOVER_MAILCONFIG = 'autodiscoverMailConfig';

    /**
     * Community identification Number
     * 
     */
    public const FEATURE_COMMUNITY_IDENT_NR = 'municipalityKey';

    public const IMPORT_EXPORT_DEFAULT_CONTAINER = 'importExportDefaultContainer';


    /**
     * user defined page title postfix for browser page title
     * 
     * @var string
     */
    public const PAGETITLEPOSTFIX = 'pagetitlepostfix';

    /**
     * logout redirect url
     * 
     * @var string
     */
    public const REDIRECTURL = 'redirectUrl';
    
    /**
     * redirect always
     * 
     * @var string
     */
    public const REDIRECTALWAYS = 'redirectAlways';
    
    /**
     * Config key for Setting "Redirect to referring site if exists?"
     * 
     * @var string
     */
    public const REDIRECTTOREFERRER = 'redirectToReferrer';
    
    /**
     * Config key for configuring allowed origins of the json frontend
     *
     * @deprected use ALLOWEDORIGINS!
     * @var string
     */
    public const ALLOWEDJSONORIGINS = 'allowedJsonOrigins';
    public const ALLOWEDORIGINS = 'allowedOrigins';

    /**
     * Config key for configuring allowed health check ips
     *
     * @var string
     */
    public const ALLOWEDHEALTHCHECKIPS = 'allowedHealthCheckIPs';

    /**
     * Config key for acceptedTermsVersion
     * @var string
     */
    public const ACCEPTEDTERMSVERSION = 'acceptedTermsVersion';

    /**
     * Config key for using nominatim service
     * @var string
     */
    public const USE_NOMINATIM_SERVICE = 'useNominatimService';

    /**
     * Config key for nominatim service url
     * @var string
     */
    public const NOMINATIM_SERVICE_URL = 'nominatimServiceUrl';

    /**
     * Config key for using map panel
     * @var string
     */
    public const USE_MAP_SERVICE = 'useMapService';

    /**
     * Config key for map service url
     * @var string
     */
    public const MAP_SERVICE_URL = 'mapServiceUrl';

    /**
     * disable ldap certificate check
     *
     * @var string
     */
    public const LDAP_DISABLE_TLSREQCERT = 'ldapDisableTlsReqCert';

    /**
     * overwritten ldap fields
     *
     * @var string
     */
    public const LDAP_OVERWRITE_CONTACT_FIELDS = 'ldapOverwriteContactFields';

    /**
     * uri for sentry service (https://sentry.io)
     *
     * @var string
     */
    public const SENTRY_URI = 'sentryUri';

    /**
     * PHP error log level constant, like E_ALL, E_ERROR etc. E_ERROR | E_WARNING (error und warning),
     * E_ALL & ~E_NOTICE (E_ALL ohne E_NOTICE)
     *
     * value is an int! not a string "E_ALL"
     *
     * @var string
     */
    public const SENTRY_LOGLEVL = 'sentryLoglevel';

    /**
     * Sentry "environment" tag - default: AUTODETECT
     *
     * @var string
     */
    public const SENTRY_ENVIRONMENT = 'sentry_env';

    /**
     * configure if user account status data should be synced from sync backend, default no
     *
     * @var string
     */
    public const SYNC_USER_ACCOUNT_STATUS = 'syncUserAccountStatus';

    /**
     * configure hook class for user sync
     *
     * @var string
     */
    public const SYNC_USER_HOOK_CLASS = 'syncUserHookClass';

    public const SYNC_USER_DISABLED = 'syncUserDisabled';

    public const SYNC_USER_OF_GROUPS = 'syncUserOfGroups';
    public const SYNC_DEVIATED_PRIMARY_GROUP_UUID = 'syncDeviatedPrimaryGroupUUID';

    /**
     * configure if user contact data should be synced from sync backend, default yes
     *
     * @var string
     */
    public const SYNC_USER_CONTACT_DATA = 'syncUserContactData';

    /**
     * configure if user contact photo should be synced from sync backend, default yes
     *
     * @var string
     */
    public const SYNC_USER_CONTACT_PHOTO = 'syncUserContactPhoto';

    /**
     * configure if deleted users from sync back should be deleted in sql backend, default yes
     *
     * @var string
     */
    public const SYNC_DELETED_USER = 'syncDeletedUser';

    /**
     * configure when user should be removed from sql after it is removed from sync backend
     *
     * @var boolean
     */
    public const SYNC_USER_DELETE_AFTER = 'syncUserDeleteAfter';

    /**
     * Config key for session ip validation -> if this is set to FALSE no Zend_Session_Validator_IpAddress is registered
     * 
     * @var string
     */
    public const SESSIONIPVALIDATION = 'sessionIpValidation';
    
    /**
     * Config key for session user agent validation -> if this is set to FALSE no Zend_Session_Validator_HttpUserAgent is registered
     * 
     * @var string
     */
    public const SESSIONUSERAGENTVALIDATION = 'sessionUserAgentValidation';
    
    /**
     * filestore directory
     * 
     * @var string
     */
    public const FILESDIR = 'filesdir';
    
    /**
     * xls export config
     * 
     * @deprecated move to app config
     * @var string
     */
    public const XLSEXPORTCONFIG = 'xlsexportconfig';
    
    /**
     * app defaults
     *
     * @var string
     */
    public const APPDEFAULTS = 'appdefaults';
    
    /**
    * REUSEUSERNAME_SAVEUSERNAME
    *
    * @var string
    */
    public const REUSEUSERNAME_SAVEUSERNAME = 'saveusername';
        
    /**
    * PASSWORD_CHANGE
    *
    * @var string
    */
    public const PASSWORD_CHANGE = 'changepw';

    /**
     * ALLOW_BROWSER_PASSWORD_MANAGER
     *
     * @var string
     */
    public const ALLOW_BROWSER_PASSWORD_MANAGER = 'allowBrowserPasswordManager';
    
    /**
     * USER_PASSWORD_POLICY
     *
     * @var string
     */
    public const USER_PASSWORD_POLICY= 'userPwPolicy';

    public const CHECK_AT_LOGIN = 'checkAtLogin';

    /**
     * DOWNLOAD_PASSWORD_POLICY
     *
     * @var string
     */
    public const DOWNLOAD_PASSWORD_POLICY= 'downloadPwPolicy';

    /**
     * PASSWORD_MANDATORY
     *
     * @var string
     */
    public const PASSWORD_MANDATORY = 'pwIsMandatory';
    
    /**
     * PASSWORD_POLICY_ACTIVE
     *
     * @var string
     */
    public const PASSWORD_POLICY_ACTIVE = 'pwPolicyActive';
    
    /**
     * PASSWORD_POLICY_ONLYASCII
     *
     * @var string
     */
    public const PASSWORD_POLICY_ONLYASCII = 'pwPolicyOnlyASCII';
    
    /**
     * PASSWORD_POLICY_MIN_LENGTH
     *
     * @var string
     */
    public const PASSWORD_POLICY_MIN_LENGTH = 'pwPolicyMinLength';
    
    /**
     * PASSWORD_POLICY_MIN_WORD_CHARS
     *
     * @var string
     */
    public const PASSWORD_POLICY_MIN_WORD_CHARS = 'pwPolicyMinWordChars';
    
    /**
     * PASSWORD_POLICY_MIN_UPPERCASE_CHARS
     *
     * @var string
     */
    public const PASSWORD_POLICY_MIN_UPPERCASE_CHARS = 'pwPolicyMinUppercaseChars';
    
    /**
     * PASSWORD_POLICY_MIN_SPECIAL_CHARS
     *
     * @var string
     */
    public const PASSWORD_POLICY_MIN_SPECIAL_CHARS = 'pwPolicyMinSpecialChars';
    
    /**
     * PASSWORD_POLICY_MIN_NUMBERS
     *
     * @var string
     */
    public const PASSWORD_POLICY_MIN_NUMBERS = 'pwPolicyMinNumbers';
    
    /**
     * PASSWORD_POLICY_FORBID_USERNAME
     *
     * @var string
     */
    public const PASSWORD_POLICY_FORBID_USERNAME = 'pwPolicyForbidUsername';

    /**
     * PASSWORD_POLICY_CHANGE_AFTER
     *
     * @var string
     */
    public const PASSWORD_POLICY_CHANGE_AFTER = 'pwPolicyChangeAfter';

    /**
     * PASSWORD_SUPPORT_NTLMV2
     *
     * @var string
     */
    public const PASSWORD_SUPPORT_NTLMV2 = 'pwSupportNtlmV2';

    /**
     * PASSWORD_NTLMV2_ENCRYPTION_KEY
     *
     * @var string
     */
    public const PASSWORD_NTLMV2_ENCRYPTION_KEY = 'pwNtlmV2EncryptionKey';

    /**
     * PASSWORD_NTLMV2_HASH_UPDATE_ON_LOGIN
     *
     * @var string
     */
    public const PASSWORD_NTLMV2_HASH_UPDATE_ON_LOGIN = 'pwNtlmV2HashUpdateOnLogin';

    /**
     * license type
     *
     * @var string
     */
    public const LICENSE_TYPE = 'licenseType';

    /**
     * AUTOMATIC_BUGREPORTS
     *
     * @var string
     */
    public const AUTOMATIC_BUGREPORTS = 'automaticBugreports';
    
    /**
     * LAST_SESSIONS_CLEANUP_RUN
     *
     * @var string
     */
    public const LAST_SESSIONS_CLEANUP_RUN = 'lastSessionsCleanupRun';
    
    /**
     * WARN_LOGIN_FAILURES
     *
     * @var string
     */
    public const WARN_LOGIN_FAILURES = 'warnLoginFailures';

    public const SETUP_SKIP_UPDATE_MAX_USER_CHECK = 'setupSkipUpdateMaxUserCheck';

    /**
     * ANYONE_ACCOUNT_DISABLED
     *
     * @var string
     */
    public const ANYONE_ACCOUNT_DISABLED = 'anyoneAccountDisabled';
    
    /**
     * ALARMS_EACH_JOB
     *
     * @var string
     */
    public const ALARMS_EACH_JOB = 'alarmsEachJob';
    
    /**
     * ACCOUNT_DEACTIVATION_NOTIFICATION
     *
     * @var string
     */
    public const ACCOUNT_DEACTIVATION_NOTIFICATION = 'accountDeactivationNotification';

    /**
     * ACCOUNT_DELETION_EVENTCONFIGURATION
     *
     * @var string
     */
    public const ACCOUNT_DELETION_EVENTCONFIGURATION = 'accountDeletionEventConfiguration';
    public const ACCOUNT_DELETION_DELETE_PERSONAL_CONTAINER = '_deletePersonalContainers';
    public const ACCOUNT_DELETION_DELETE_PERSONAL_FOLDERS = '_deletePersonalFolders';
    public const ACCOUNT_DELETION_DELETE_EMAIL_ACCOUNTS = '_deleteEmailAccounts';
    public const ACCOUNT_DELETION_KEEP_AS_CONTACT = '_keepAsContact';
    public const ACCOUNT_DELETION_KEEP_ORGANIZER_EVENTS = '_keepOrganizerEvents';
    public const ACCOUNT_DELETION_KEEP_AS_EXTERNAL_ATTENDER = '_keepAttendeeEvents';
    public const ACCOUNT_DELETION_ADDITIONAL_TEXT = 'additionalText';

    /**
     * roleChangeAllowed
     *
     * @var string
     */
    public const ROLE_CHANGE_ALLOWED = 'roleChangeAllowed';

    public const SMS = 'sms';

    public const SMS_ADAPTERS = 'sms_adapters';
    public const SMS_MESSAGE_TEMPLATES = 'sms_message_templates';
    public const SMS_NEW_PASSWORD_TEMPLATE = 'sms_new_password_template';
    
    /**
     * max username length
     *
     * @var string
     */
    public const MAX_USERNAME_LENGTH = 'max_username_length';

    /**
     * USER_PIN
     *
     * @var string
     */
    public const USER_PIN = 'userPin';

    /**
     * USER_PIN_MIN_LENGTH
     *
     * @var string
     */
    public const USER_PIN_MIN_LENGTH = 'userPinMinLength';

    /**
     * conf.d folder name
     *
     * @var string
     */
    public const CONFD_FOLDER = 'confdfolder';

    /**
     * maintenance mode
     *
     * @var string
     */
    public const MAINTENANCE_MODE = 'maintenanceMode';
    public const MAINTENANCE_MODE_OFF = 'off';
    public const MAINTENANCE_MODE_ON = 'on';
    public const MAINTENANCE_MODE_NORMAL = 'normal';
    public const MAINTENANCE_MODE_ALL = 'all';
    public const MAINTENANCE_MODE_FLAGS = 'flags';
    public const MAINTENANCE_MODE_FLAG_SKIP_APPS = 'skipApps';
    public const MAINTENANCE_MODE_FLAG_ONLY_APPS = 'onlyApps';
    public const MAINTENANCE_MODE_FLAG_ALLOW_ADMIN_LOGIN = 'allowAdminLogin';

    /**
     * @var string
     */
    public const FAT_CLIENT_CUSTOM_JS = 'fatClientCustomJS';

    public const INSTALL_LOGO = 'install_logo'; // legacy
    public const INSTALL_LOGO_LIGHT_SVG = 'install_logo_light_svg';
    public const INSTALL_LOGO_LIGHT = 'install_logo_light';
    public const INSTALL_LOGO_DARK_SVG = 'install_logo_dark_svg';
    public const INSTALL_LOGO_DARK = 'install_logo_dark';
    public const WEBSITE_URL = 'website_url';

    public const BRANDING_LOGO = 'branding_logo'; // legacy
    public const BRANDING_LOGO_LIGHT_SVG = 'branding_logo_light_svg';
    public const BRANDING_LOGO_LIGHT = 'branding_logo_light';
    public const BRANDING_LOGO_DARK_SVG = 'branding_logo_dark_svg';
    public const BRANDING_LOGO_DARK = 'branding_logo_dark';
    public const BRANDING_FAVICON = 'branding_favicon';
    public const BRANDING_FAVICON_SVG = 'branding_favicon_svg';
    public const BRANDING_MASKICON_COLOR = 'branding_maskicon_color';
    public const BRANDING_TITLE = 'branding_title';
    public const BRANDING_WEBURL = 'branding_weburl';
    public const BRANDING_HELPURL = 'branding_helpUrl';
    public const BRANDING_SHOPURL = 'branding_shopUrl';
    public const BRANDING_BUGSURL = 'branding_bugreportUrl';
    public const BRANDING_DESCRIPTION = 'branding_description';

    /**
     * @var string
     */
    public const USE_LOGINNAME_AS_FOLDERNAME = 'useLoginnameAsFoldername';

    /**
     * @var string
     */
    public const DENY_WEBDAV_CLIENT_LIST = 'denyWebDavClientList';

    /**
     * @var string
     */
    public const VERSION_CHECK = 'versionCheck';

    /**
     * WEBDAV_SYNCTOKEN_ENABLED
     *
     * @var string
     */
    public const WEBDAV_SYNCTOKEN_ENABLED = 'webdavSynctokenEnabled';

    public const WEBFINGER_REL_HANDLER = 'webfingerRelHandler';

    /**
     * @var string
     */
    public const REPLICATION_MASTER = 'replicationMaster';

    /**
     * @var string
     */
    public const REPLICATION_SLAVE = 'replicationSlave';

    /**
     * @var string
     */
    public const REPLICATION_USER_PASSWORD = 'replicationUserPassword';

    public const REPLICATION_IS_PRIMARY = 'replicationIsPrimary';

    /**
     * @var string
     */
    public const STATUS_INFO = 'statusInfo';

    /**
     * @var string
     */
    public const MASTER_URL = 'masterURL';

    /**
     * @var string
     */
    public const MASTER_USERNAME = 'masterUsername';

    /**
     * @var string
     */
    public const MASTER_PASSWORD = 'masterPassword';

    public const NUM_OF_MODLOGS = 'numOfModlogs';

    /**
     * var string
     */
    public const STATUS_API_KEY = 'statusApiKey';

    /**
     * var string
     */
    public const METRICS_API_KEY = 'metricsApiKey';

    /**
     * @var string
     */
    public const ERROR_NOTIFICATION_LIST = 'errorNotificationList';

    public const FULLTEXT = 'fulltext';
    public const FULLTEXT_BACKEND = 'backend';
    public const FULLTEXT_JAVABIN = 'javaBin';
    public const FULLTEXT_TIKAJAR = 'tikaJar';
    public const FULLTEXT_QUERY_FILTER = 'queryFilter';

    public const FILESYSTEM = 'filesystem';
    public const FILESYSTEM_DEFAULT_GRANTS = 'defaultGrants';
    public const FILESYSTEM_MODLOGACTIVE = 'modLogActive';
    public const FILESYSTEM_NUMKEEPREVISIONS = 'numKeepRevisions';
    public const FILESYSTEM_MONTHKEEPREVISIONS = 'monthKeepRevisions';
    public const FILESYSTEM_INDEX_CONTENT = 'index_content';
    public const FILESYSTEM_CREATE_PREVIEWS = 'createPreviews';
    public const FILESYSTEM_PREVIEW_SERVICE_URL = 'previewServiceUrl';
    public const FILESYSTEM_PREVIEW_SERVICE_VERSION = 'previewServiceVersion';
    public const FILESYSTEM_PREVIEW_SERVICE_VERIFY_SSL = 'previewServiceVerifySsl';
    public const FILESYSTEM_PREVIEW_MAX_FILE_SIZE = 'previewMaxFileSize';
    public const FILESYSTEM_PREVIEW_MAX_ERROR_COUNT = 'previewMaxErrorCount';
    public const FILESYSTEM_PREVIEW_THUMBNAIL_SIZE_X = 'previewThumbnailSizeX';
    public const FILESYSTEM_PREVIEW_THUMBNAIL_SIZE_Y = 'previewThumbnailSizeY';
    public const FILESYSTEM_PREVIEW_DOCUMENT_PREVIEW_SIZE_X = 'previewDocumentPreviewSizeX';
    public const FILESYSTEM_PREVIEW_DOCUMENT_PREVIEW_SIZE_Y = 'previewDocumentPreviewSizeY';
    public const FILESYSTEM_PREVIEW_IMAGE_PREVIEW_SIZE_X = 'previewImagePreviewSizeX';
    public const FILESYSTEM_PREVIEW_IMAGE_PREVIEW_SIZE_Y = 'previewImagePreviewSizeY';
    public const FILESYSTEM_PREVIEW_IGNORE_PROXY = 'previewPreviewIgnoreProxy';
    public const FILESYSTEM_ENABLE_NOTIFICATIONS = 'enableNotifications';
    public const FILESYSTEM_AVSCAN_MAXFSIZE = 'maxFSize';
    public const FILESYSTEM_AVSCAN_MODE = 'avscanMode';
    public const FILESYSTEM_AVSCAN_URL = 'avscanURL';
    public const FILESYSTEM_AVSCAN_NOTIFICATION_ROLE = 'avscanNotificationRole';
    public const FILESYSTEM_AVSCAN_QUEUE_FSIZE = 'avscanQueueSize';
    public const FILESYSTEM_SHOW_CURRENT_USAGE = 'showCurrentUsage';
    public const FILESYSTEM_FLYSYSTEM_LOCAL_BASE_PATHS = 'flySystemLocalBasePaths';

    public const ACTIONQUEUE = 'actionqueue';
    public const ACTIONQUEUE_ACTIVE = 'active';
    public const ACTIONQUEUE_BACKEND = 'backend';
    public const ACTIONQUEUE_CLEAN_DS = 'cleanDS';
    public const ACTIONQUEUE_CLEAN_DS_LONG_RUNNING = 'cleanDSlongRunning';
    public const ACTIONQUEUE_HOST = 'host';
    public const ACTIONQUEUE_LONG_RUNNING = 'longRunning';
    public const ACTIONQUEUE_PORT = 'port';
    public const ACTIONQUEUE_NAME = 'queueName';
    public const ACTIONQUEUE_MONITORING_DURATION_WARN = 'durationWarn';
    public const ACTIONQUEUE_MONITORING_LASTUPDATE_WARN = 'lastUpdateWarn';
    public const ACTIONQUEUE_MONITORING_DURATION_CRIT = 'durationCrit';
    public const ACTIONQUEUE_MONITORING_LASTUPDATE_CRIT = 'lastUpdateCrit';
    public const ACTIONQUEUE_MONITORING_DAEMONSTRCTSIZE_CRIT = 'daemonStructSizeCrit';
    public const ACTIONQUEUE_LR_MONITORING_DURATION_WARN = 'LRdurationWarn';
    public const ACTIONQUEUE_LR_MONITORING_LASTUPDATE_WARN = 'LRlastUpdateWarn';
    public const ACTIONQUEUE_LR_MONITORING_DURATION_CRIT = 'LRdurationCrit';
    public const ACTIONQUEUE_LR_MONITORING_LASTUPDATE_CRIT = 'LRlastUpdateCrit';
    public const ACTIONQUEUE_LR_MONITORING_DAEMONSTRCTSIZE_CRIT = 'LRdaemonStructSizeCrit';
    public const ACTIONQUEUE_QUEUES = 'queues';

    public const QUOTA = 'quota';
    public const QUOTA_SHOW_UI = 'showUI';
    public const QUOTA_INCLUDE_REVISION = 'includeRevision';
    public const QUOTA_SOFT_QUOTA = 'softQuota';
    public const QUOTA_SQ_NOTIFICATION_ROLE = 'softQuotaNotificationRole';
    public const QUOTA_SKIP_IMAP_QUOTA = 'skipImapQuota';
    public const QUOTA_TOTALINMB = 'totalInMB';
    public const QUOTA_FILESYSTEM_TOTALINMB = 'filesystemTotalInMB';
    public const QUOTA_TOTALBYUSERINMB = 'totalByUserInMB';
    public const QUOTA_NOTIFICATION_ADDRESSES = 'quotaNotificationAddresses';
    public const QUOTA_MONITORING = 'quotaMonitoring';

    public const TINE20_URL = 'tine20URL';
    public const TINE20_URL_USEFORJSCLIENT = 'tine20URLUseForJSClient';

    public const FILTER_SYNC_TOKEN = 'filterSyncToken';
    public const FILTER_SYNC_TOKEN_CLEANUP_MAX_TOTAL = 'cleanUpMaxTotal';
    public const FILTER_SYNC_TOKEN_CLEANUP_MAX_FILTER = 'cleanUpMaxFilter';
    public const FILTER_SYNC_TOKEN_CLEANUP_MAX_AGE = 'cleanUpMaxAge';

    public const NOTE_TYPE = 'noteType';

    const SUPPORT_REQUEST_NOTIFICATION_ROLE = 'supportRequestNotificationRole';

    /**
     * Grad der Verstädterung (CummunityIdentificationNumber)
     * 
     * @var string
     */
    public const GRAD_DER_VERSTAEDTERUNG = 'gradVerstaedterung';

    /**
     * fields for lead record duplicate check
     *
     * @var string
     */
    public const MUNICIPALITYKEY_DUP_FIELDS = 'municipalityKeyDupFields';
    

    /**
     * action log types
     *
     * @var string
     */
    public const ACTION_LOG_TYPES = 'actionLogTypes';

    /**
     * user types
     *
     * @var string
     */
    public const USER_TYPES = 'userTypes';

    /**
     * Filter configuration for site record pickers
     * @var string
     */
    public const SITE_FILTER = 'siteFilter';
    public const FEATURE_SITE = 'featureSite';

    /**
     * scheduler user task fail notification threshold
     */
    public const SCHEDULER_USER_TASK_FAIL_NOTIFICATION_THRESHOLD = 'userTaskFailNotificationThreshold';


    /**
     * (non-PHPdoc)
     * @see tine20/Tinebase/Config/Definition::$_properties
     */
    protected static $_properties = array(
        self::ACCESS_LOG_ROTATION_DAYS => [
            //_('Accesslog rotation in days')
            self::LABEL => 'Accesslog rotation in days',
            //_('Accesslog rotation in days')
            self::DESCRIPTION => 'Accesslog rotation in days',
            self::TYPE => self::TYPE_INT,
            self::DEFAULT_STR => 7,
            self::CLIENTREGISTRYINCLUDE => FALSE,
            self::SETBYADMINMODULE => TRUE,
            self::SETBYSETUPMODULE => TRUE,
        ],
        /**
         * TODO add more options (like move to another container)
         */
        self::ACCOUNT_DELETION_EVENTCONFIGURATION => array(
            //_('Account Deletion Event')
            self::LABEL => 'Account Deletion Event',
            //_('Configure what should happen to data of deleted users')
            self::DESCRIPTION => 'Configure what should happen to data of deleted users',
            self::TYPE => 'object',
            self::CLASSNAME => Tinebase_Config_Struct::class,
            self::CLIENTREGISTRYINCLUDE => FALSE,
            self::SETBYADMINMODULE => TRUE,
            self::SETBYSETUPMODULE => TRUE,
            self::CONTENT               => [
                self::ACCOUNT_DELETION_DELETE_PERSONAL_CONTAINER  => [
                    //_('Delete personal containers')
                    self::LABEL                         => 'Delete personal containers',
                    self::DESCRIPTION                   => 'Delete personal containers',
                    self::TYPE                          => self::TYPE_BOOL,
                    self::DEFAULT_STR                   => false,
                ],
                self::ACCOUNT_DELETION_DELETE_PERSONAL_FOLDERS  => [
                    //_('Delete file folders and files')
                    self::LABEL                         => 'Delete file folders and files',
                    self::DESCRIPTION                   => 'Delete file folders and files in filesystem',
                    self::TYPE                          => self::TYPE_BOOL,
                    self::DEFAULT_STR                   => false,
                ],
                self::ACCOUNT_DELETION_DELETE_EMAIL_ACCOUNTS  => [
                    //_('Delete Email accounts')
                    self::LABEL                         => 'Delete Email accounts',
                    self::DESCRIPTION                   => 'Delete Email accounts',
                    self::TYPE                          => self::TYPE_BOOL,
                    self::DEFAULT_STR                   => false,
                ],
                self::ACCOUNT_DELETION_KEEP_AS_CONTACT  => [
                    //_('Keep account as contact in the Addressbook')
                    self::LABEL                         => 'Keep account as contact in the Addressbook',
                    self::DESCRIPTION                   => 'Keep account as contact in the Addressbook',
                    self::TYPE                          => self::TYPE_BOOL,
                    self::DEFAULT_STR                   => false,
                ],
                self::ACCOUNT_DELETION_KEEP_ORGANIZER_EVENTS  => [
                    //_('Keep accounts organizer events as external events in the Calendar')
                    self::LABEL                         => 'Keep accounts organizer events as external events in the Calendar',
                    self::DESCRIPTION                   => 'Keep accounts organizer events as external events in the Calendar',
                    self::TYPE                          => self::TYPE_BOOL,
                    self::DEFAULT_STR                   => false,
                ],
                self::ACCOUNT_DELETION_KEEP_AS_EXTERNAL_ATTENDER  => [
                    //_('Keep accounts Calendar event attendee as external attendee')
                    self::LABEL                         => 'Keep accounts Calendar event attendee as external attender',
                    self::DESCRIPTION                   => 'Keep accounts Calendar event attendee as external attender',
                    self::TYPE                          => self::TYPE_BOOL,
                    self::DEFAULT_STR                   => false,
                ],
                self::ACCOUNT_DELETION_ADDITIONAL_TEXT  => [
                    //_('Additional text')
                    self::LABEL                         => 'Additional text',
                    self::DESCRIPTION                   => 'Additional text shows in confirmation dialog',
                    self::TYPE                          => self::TYPE_STRING,
                    self::DEFAULT_STR                   => '',
                ],
            ],
        ),
        /**
         * lock certain areas of tine20 (apps, login, data safe, ...) with additional auth (pin, privacy idea, ...)
         */
        self::AREA_LOCKS => array(
            //_('Area Locks')
            self::LABEL => 'Area Locks',
            //_('Configured Area Locks')
            self::DESCRIPTION => 'Configured Area Locks',
            self::TYPE => 'keyFieldConfig',
            self::OPTIONS => array('recordModel' => Tinebase_Model_AreaLockConfig::class),
            self::CLIENTREGISTRYINCLUDE => true, // this will be cleaned in getClientRegistryConfig()! // TODO make this as a hook or something
            self::SETBYSETUPMODULE => false,
            self::SETBYADMINMODULE => false,
            self::DEFAULT_STR => [
                'records' => [[
                    'area_name'         => 'login',
                    'areas'             => ['Tinebase_login'],
                    'mfas'              => ['Authenticator App', 'FIDO2'],
                    'validity'          => 'session',
                ]]
            ],
        ),
        self::DATABASE => [
            //_('Database Configuration')
            self::LABEL => 'Database Configuration',
            self::DESCRIPTION => 'Database Configuration',
            self::TYPE => self::TYPE_OBJECT,
            self::CLASSNAME => Tinebase_Config_Struct::class,
            self::CLIENTREGISTRYINCLUDE => false,
            self::SETBYADMINMODULE => false,
            self::SETBYSETUPMODULE => false,
        ],
        /**
         * example:
         *
         * 'externalDatabase' => [
         *      'TineSaas' => [
         *          'useUtf8mb4' => true,
         *          'dbname' => 'db',
         *          'username' => 'tinesaas',
         *          'password' => 'pass',
         *          'host' => 'db.host',
         *      ]
         * ],
         */
        self::EXTERNAL_DATABASE => [
            //_('External Database Configuration')
            self::LABEL => 'External Database Configuration',
            self::DESCRIPTION => 'External Database Configuration',
            self::TYPE => self::TYPE_OBJECT,
            self::CLASSNAME => Tinebase_Config_Struct::class,
            self::CLIENTREGISTRYINCLUDE => false,
            self::SETBYADMINMODULE => false,
            self::SETBYSETUPMODULE => false,
        ],
        self::LOGGER => [
            //_('Logger Configuration')
            self::LABEL => 'Logger Configuration',
            self::DESCRIPTION => 'Logger Configuration',
            self::TYPE => self::TYPE_OBJECT,
            self::CLASSNAME => Tinebase_Config_Struct::class,
            self::CLIENTREGISTRYINCLUDE => false,
            self::SETBYADMINMODULE => false,
            self::SETBYSETUPMODULE => false,
        ],
        self::SUPPRESS_EXCEPTION_TRACES => [
            //_('Suppress Exception Traces')
            self::LABEL => 'Suppress Exception Traces',
            self::DESCRIPTION => 'Do not send exception traces to log files or json client',
            self::TYPE => self::TYPE_BOOL,
            self::CLIENTREGISTRYINCLUDE => false,
            self::SETBYADMINMODULE => false,
            self::SETBYSETUPMODULE => true,
            self::DEFAULT_STR => false,
        ],
        self::MFA_BYPASS_NETMASKS => [
            self::LABEL             => 'MFA Bypass Netmasks', // _('MFA Bypass Netmasks')
            self::DESCRIPTION       => 'MFA Bypass Netmasks', // _('MFA Bypass Netmasks')
            self::TYPE              => self::TYPE_ARRAY,
            self::CLIENTREGISTRYINCLUDE => false,
            self::SETBYSETUPMODULE  => true,
            self::SETBYADMINMODULE  => true,
            self::DEFAULT_STR       => [],
        ],
        self::MFA => array(
            //_('MFA')
            self::LABEL => 'MFA',
            //_('Configured MFAs')
            self::DESCRIPTION => 'Configured MFAs',
            self::TYPE => 'keyFieldConfig',
            self::OPTIONS => array('recordModel' => Tinebase_Model_MFA_Config::class),
            self::CLIENTREGISTRYINCLUDE => false,
            self::SETBYSETUPMODULE => false,
            self::SETBYADMINMODULE => false,
            self::DEFAULT_STR => [
                'records' => [[
                    'id'                    => 'Authenticator App',
                    'allow_self_service'    => true,
                    'provider_config_class' => 'Tinebase_Model_MFA_TOTPConfig',
                    'provider_config'       => [

                    ],
                    'provider_class'        => 'Tinebase_Auth_MFA_HTOTPAdapter',
                    'user_config_class'     => 'Tinebase_Model_MFA_TOTPUserConfig'
                ], [
                    'id'                    => 'FIDO2',
                    'allow_self_service'    => true,
                    'provider_config_class' => 'Tinebase_Model_MFA_WebAuthnConfig',
                    'provider_config'       => [
                        'authenticator_attachment' => 'cross-platform', // may be null, platform, cross-platform
                        'user_verification_requirement' => 'required', // may be required, preferred, discouraged
                        'resident_key_requirement' => null, // may be null, required, preferred, discouraged
                    ],
                    'provider_class'        => 'Tinebase_Auth_MFA_WebAuthnAdapter',
                    'user_config_class'     => 'Tinebase_Model_MFA_WebAuthnUserConfig'
                ], [
                    'id'                    => 'YUBICO',
                    'allow_self_service'    => true,
                    'provider_config_class' => 'Tinebase_Model_MFA_YubicoOTPConfig',
                    'provider_config'       => [],
                    'provider_class'        => 'Tinebase_Auth_MFA_YubicoOTPAdapter',
                    'user_config_class'     => 'Tinebase_Model_MFA_YubicoOTPUserConfig'
                ]]
            ],
        ),
        /**
         * for example: array('en', 'de')
         */
        self::AVAILABLE_LANGUAGES => array(
            //_('Available Languages')
            self::LABEL => 'Available Languages',
            //_('Whitelist available languages that can be chosen in the GUI')
            self::DESCRIPTION => 'Whitelist available languages that can be chosen in the GUI',
            self::TYPE => 'array',
            self::CLIENTREGISTRYINCLUDE => TRUE,
            self::SETBYADMINMODULE => TRUE,
            self::SETBYSETUPMODULE => TRUE,
        ),
        /**
         * encourage MFA at login
         */
        self::MFA_ENCOURAGE => [
            //_('Encourage MFA at login')
            self::LABEL => 'Encourage MFA at login',
            //_('Encourage user at login to configure a mfa device')
            self::DESCRIPTION => 'Encourage user at login to configure a mfa device',
            self::TYPE => 'bool',
            // we need this to disable any convert actions in the GUI
            self::CLIENTREGISTRYINCLUDE => true,
            self::SETBYADMINMODULE => true,
            self::SETBYSETUPMODULE => false,
            // TODO add a checkbox/preference for users to allow to hide the dlg and switch to true again
            self::DEFAULT_STR => false,
        ],
        /**
         * One of: AUTODETECT, DEBUG, DEVELOPMENT, RELEASE
         */
        self::BUILD_TYPE => array(
            //_('Build Type')
            self::LABEL => 'Build Type',
            //_('One of: AUTODETECT, DEBUG, DEVELOPMENT, RELEASE')
            self::DESCRIPTION => 'One of: AUTODETECT, DEBUG, DEVELOPMENT, RELEASE',
            self::TYPE => 'string',
            self::CLIENTREGISTRYINCLUDE => false,
            self::SETBYADMINMODULE => false,
            self::SETBYSETUPMODULE => false,
            self::DEFAULT_STR => 'DEVELOPMENT',
        ),
        self::DBLOGGER => [
            //_('DB logger configuration')
            self::LABEL => 'DB logger configuration',
            self::DESCRIPTION => 'DB logger configuration',
            self::TYPE => 'object',
            self::CLASSNAME => Tinebase_Config_Struct::class,
            self::CLIENTREGISTRYINCLUDE => false,
            self::SETBYADMINMODULE => false,
            self::SETBYSETUPMODULE => true,
            self::CONTENT => [
                'active' => [
                    self::TYPE => Tinebase_Config::TYPE_BOOL,
                ],
                // values from '0' to '7' are supported - see Tinebase_Log
                'priority' => [
                    self::TYPE => Tinebase_Config::TYPE_STRING,
                    self::DEFAULT_STR => '5',
                ],
            ]
        ],
        self::DEFAULT_APP => array(
            //_('Default application')
            self::LABEL => 'Default application',
            //_('Default application for this installation.')
            self::DESCRIPTION => 'Default application for this installation.',
            self::TYPE => 'string',
            self::DEFAULT_STR => 'Addressbook',
            self::CLIENTREGISTRYINCLUDE => TRUE,
            self::SETBYADMINMODULE => FALSE,
            self::SETBYSETUPMODULE => TRUE,
        ),
        /**
         * for example: 'de' / 'auto' uses fallback from Zend_Locale
         */
        self::DEFAULT_LOCALE => array(
            //_('Default Locale')
            self::LABEL => 'Default Locale',
            //_('Default locale for this installation.')
            self::DESCRIPTION => 'Default locale for this installation.',
            self::TYPE => 'string',
            self::DEFAULT_STR => self::DEFAULT_LOCALE_AUTO,
            self::CLIENTREGISTRYINCLUDE => true,
            self::SETBYADMINMODULE => false,
            self::SETBYSETUPMODULE => true,
        ),
        /**
         * config keys (see Zend_Http_Client_Adapter_Proxy):
         *
         * 'proxy_host' => 'proxy.com',
         * 'proxy_port' => 3128,
         * 'proxy_user' => 'user',
         * 'proxy_pass' => 'pass'
         */
        self::INTERNET_PROXY => array(
            //_('Internet proxy config')
            self::LABEL => 'Internet proxy config',
            self::DESCRIPTION => 'Internet proxy config',
            self::TYPE => 'array',
            self::DEFAULT_STR => array(),
            self::CLIENTREGISTRYINCLUDE => false,
            self::SETBYADMINMODULE => true,
            self::SETBYSETUPMODULE => true,
        ),
        /**
         * config keys:
         *
         * useSystemAccount (bool)
         * domain (string)
         * instanceName (string)
         * useEmailAsUsername (bool) - default: false
         * preventSecondaryDomainUsername (bool) - default: false
         * host (string)
         * port (integer)
         * ssl (bool)
         * user (string) ?
         * backend (string) - see Tinebase_EmailUser::$_supportedBackends
         * verifyPeer (bool)
         * "allowOverwrite": false (bool)
         * "allowExternalEmail": false (bool)
         *
         * TODO make this a structured config with subconfig keys
         */
        self::IMAP => array(
                                   //_('System IMAP')
            self::LABEL => 'System IMAP',
                                   //_('System IMAP server configuration.')
            self::DESCRIPTION => 'System IMAP server configuration.',
            self::TYPE => 'object',
            self::CLASSNAME => Tinebase_Config_Struct::class,
            self::CLIENTREGISTRYINCLUDE => FALSE,
            self::SETBYADMINMODULE => FALSE,
            self::SETBYSETUPMODULE => TRUE,
        ),
        /**
         * config keys:
         *
         * "backend":"postfix" (string)
         * "hostname":"smtphost" (string)
         * "port":"25" (integer)
         * "ssl":"none" (string)
         * "auth":"none" (string)
         * "primarydomain":"mail.test" (string)
         * "secondarydomains":"second.test,third.test" (string - comma separated)
         * "additionaldomains":"another.test,onemore.test" (string - comma separated)
         * "instanceName":"tine.test" (string)
         * "accountnamedestination":true (boolean) - false by default (see \Tinebase_EmailUser_Smtp_Postfix::_createDefaultDestinations)
         * "destinationisusername": false (boolean) - false by default (see \Tinebase_EmailUser_Smtp_Postfix::_createAliasDestinations)
         * "checkduplicatealias": true (boolean) - true by default (see \Tinebase_EmailUser_Smtp_Postfix::_checkIfDestinationExists)
         * "from":"notifications@tine.test" (string) - notification sender address
         * "allowOverwrite": false (bool)
         * "preventSecondaryDomainUsername": true
         *
         * TODO make this a structured config with subconfig keys
         */
        self::SMTP => array(
                                   //_('System SMTP')
            self::LABEL => 'System SMTP',
                                   //_('System SMTP server configuration.')
            self::DESCRIPTION => 'System SMTP server configuration.',
            self::TYPE => 'object',
            self::CLASSNAME => Tinebase_Config_Struct::class,
            self::CLIENTREGISTRYINCLUDE => FALSE,
            self::SETBYADMINMODULE => FALSE,
            self::SETBYSETUPMODULE => TRUE,
        ),
        self::SIEVE => array(
                                   //_('System SIEVE')
            self::LABEL => 'System SIEVE',
                                   //_('System SIEVE server configuration.')
            self::DESCRIPTION => 'System SIEVE server configuration.',
            self::TYPE => 'object',
            self::CLASSNAME => Tinebase_Config_Struct::class,
            self::CLIENTREGISTRYINCLUDE => FALSE,
            self::SETBYADMINMODULE => FALSE,
            self::SETBYSETUPMODULE => TRUE,
        ),
        // TODO remove this config and all old code in 2021.11
        self::EMAIL_USER_ID_IN_XPROPS => [
            //_('Use record XPROPS to save email user id')
            self::LABEL => 'Use record XPROPS to save email user id',
            //_('Use record XPROPS to save email user id')
            self::DESCRIPTION => 'Use record XPROPS to save email user id',
            self::TYPE => 'bool',
            // we need this to disable any convert actions in the GUI
            self::CLIENTREGISTRYINCLUDE => true,
            self::SETBYADMINMODULE => false,
            self::SETBYSETUPMODULE => true,
            self::DEFAULT_STR => true,
        ],
        self::TRUSTED_PROXIES => array(
            //_('Trusted Proxies')
            self::LABEL => 'Trusted Proxies',
            //_('If this is set, the HTTP_X_FORWARDED_FOR header is used.')
            self::DESCRIPTION => 'If this is set, the HTTP_X_FORWARDED_FOR header is used.',
            self::TYPE => 'array',
            self::CLIENTREGISTRYINCLUDE => FALSE,
            self::SETBYADMINMODULE => FALSE,
            self::SETBYSETUPMODULE => TRUE,
        ),
        self::AUTHENTICATIONBACKENDTYPE => array(
                                   //_('Authentication Backend')
            self::LABEL => 'Authentication Backend',
                                   //_('Backend adapter for user authentication.')
            self::DESCRIPTION => 'Backend adapter for user authentication.',
            self::TYPE => 'string',
            self::CLIENTREGISTRYINCLUDE => FALSE,
            self::SETBYADMINMODULE => FALSE,
            self::SETBYSETUPMODULE => TRUE,
        ),
        self::AUTHENTICATIONBACKEND => array(
                                   //_('Authentication Configuration')
            self::LABEL => 'Authentication Configuration',
                                   //_('Authentication backend configuration.')
            self::DESCRIPTION => 'Authentication backend configuration.',
            self::TYPE => 'object',
            self::CLASSNAME => Tinebase_Config_Struct::class,
            self::CLIENTREGISTRYINCLUDE => FALSE,
            self::SETBYADMINMODULE => FALSE,
            self::SETBYSETUPMODULE => TRUE,
        ),
        self::AUTHENTICATION_BY_EMAIL => [
            self::LABEL                 => 'Authentication by Email',
            self::DESCRIPTION           => 'Authentication by Email', // _('Authentication by Email')
            self::TYPE                  => self::TYPE_BOOL,
            self::DEFAULT_STR           => false,
            self::CLIENTREGISTRYINCLUDE => true,
            self::SETBYADMINMODULE      => true,
            self::SETBYSETUPMODULE      => true,
        ],
        self::CACHED_CONFIG_PATH => [
            self::LABEL                 => 'Path for cached config file',
            self::DESCRIPTION           => 'Path for cached config file (defaults to tmpdir if empty)', // _('Path for cached config file (defaults to tmpdir if empty)')
            self::TYPE                  => self::TYPE_STRING,
            self::DEFAULT_STR           => null,
            self::CLIENTREGISTRYINCLUDE => false,
            self::SETBYADMINMODULE      => false,
            self::SETBYSETUPMODULE      => true,
        ],
        self::SETUP_SKIP_UPDATE_MAX_USER_CHECK => [
            self::LABEL                 => 'Skip maximum license user check in setup',
            self::DESCRIPTION           => 'Skip maximum license user check in setup', // _('Skip maximum license user check in setup')
            self::TYPE                  => self::TYPE_BOOL,
            self::DEFAULT_STR           => false,
            self::CLIENTREGISTRYINCLUDE => false,
            self::SETBYADMINMODULE      => false,
            self::SETBYSETUPMODULE      => true,
        ],
        self::USERBACKENDTYPE => array(
                                   //_('User Backend')
            self::LABEL => 'User Backend',
                                   //_('Backend adapter for user data.')
            self::DESCRIPTION => 'Backend adapter for user data.',
            self::TYPE => 'string',
            self::CLIENTREGISTRYINCLUDE => FALSE,
            self::SETBYADMINMODULE => FALSE,
            self::SETBYSETUPMODULE => TRUE,
        ),
        self::REPLICATION_MASTER => array(
            //_('Replication master configuration')
            self::LABEL => 'Replication master configuration',
            //_('Replication master configuration.')
            self::DESCRIPTION => 'Replication master configuration.',
            self::TYPE => 'object',
            self::CLASSNAME => Tinebase_Config_Struct::class,
            self::CLIENTREGISTRYINCLUDE => FALSE,
            self::SETBYADMINMODULE => FALSE,
            self::SETBYSETUPMODULE => TRUE,
            self::CONTENT => array(
                self::REPLICATION_USER_PASSWORD     => array(
                    self::TYPE => Tinebase_Config::TYPE_STRING
                ),
                self::REPLICATION_IS_PRIMARY        => [
                    self::TYPE                          => self::TYPE_BOOL
                ],
            ),
        ),
        self::REPLICATION_SLAVE => array(
            //_('Replication slave configuration')
            self::LABEL => 'Replication slave configuration',
            //_('Replication slave configuration.')
            self::DESCRIPTION => 'Replication slave configuration.',
            self::TYPE => 'object',
            self::CLASSNAME => Tinebase_Config_Struct::class,
            self::CLIENTREGISTRYINCLUDE => FALSE,
            self::SETBYADMINMODULE => FALSE,
            self::SETBYSETUPMODULE => TRUE,
            self::CONTENT => array(
                self::MASTER_URL                => array(
                    self::TYPE => Tinebase_Config::TYPE_STRING,
                ),
                self::MASTER_USERNAME           => array(
                    self::TYPE => Tinebase_Config::TYPE_STRING,
                ),
                self::MASTER_PASSWORD           => array(
                    self::TYPE => Tinebase_Config::TYPE_STRING,
                ),
                self::ERROR_NOTIFICATION_LIST   => array(
                    self::TYPE => Tinebase_Config::TYPE_ARRAY,
                ),
                // number of mod logs to apply in each run
                self::NUM_OF_MODLOGS => [
                    self::TYPE => Tinebase_Config::TYPE_INT,
                    self::DEFAULT_STR => 100,
                ]
            )
        ),
        self::FULLTEXT => array(
            //_('Full text configuration')
            self::LABEL => 'Full text configuration',
            //_('Full text configuration.')
            self::DESCRIPTION => 'Full text configuration.',
            self::TYPE => 'object',
            self::CLASSNAME => Tinebase_Config_Struct::class,
            self::CLIENTREGISTRYINCLUDE => FALSE,
            self::SETBYADMINMODULE => FALSE,
            self::SETBYSETUPMODULE => TRUE,
            self::CONTENT => array(
                self::FULLTEXT_BACKEND          => array(
                    self::TYPE => Tinebase_Config::TYPE_STRING,
                    self::DEFAULT_STR => 'Sql'
                ),
                self::FULLTEXT_JAVABIN          => array(
                    self::TYPE => Tinebase_Config::TYPE_STRING,
                    self::DEFAULT_STR => 'java'
                ),
                self::FULLTEXT_TIKAJAR          => array(
                    self::TYPE => Tinebase_Config::TYPE_STRING,
                ),
                // shall we include fulltext fields in the query filter?
                self::FULLTEXT_QUERY_FILTER     => array(
                    self::TYPE => Tinebase_Config::TYPE_BOOL,
                    self::DEFAULT_STR => true
                ),
            ),
            self::DEFAULT_STR => array()
        ),
        self::ACTIONQUEUE => [
            //_('Action queue configuration')
            self::LABEL => 'Action queue configuration',
            //_('Action queue configuration.')
            self::DESCRIPTION => 'Action queue configuration.',
            self::TYPE => 'object',
            self::CLASSNAME => Tinebase_Config_Struct::class,
            self::CLIENTREGISTRYINCLUDE => FALSE,
            self::SETBYADMINMODULE => FALSE,
            self::SETBYSETUPMODULE => TRUE,
            self::CONTENT => [
                self::ACTIONQUEUE_ACTIVE        => [
                    self::TYPE => Tinebase_Config::TYPE_BOOL,
                    self::DEFAULT_STR => false,
                ],
                self::ACTIONQUEUE_BACKEND       => [
                    self::TYPE => Tinebase_Config::TYPE_STRING,
                    self::DEFAULT_STR => 'Direct',
                ],
                self::ACTIONQUEUE_CLEAN_DS_LONG_RUNNING => [
                    self::TYPE => Tinebase_Config::TYPE_INT,
                    self::DEFAULT_STR => 5 * 60 * 60, // 5 hours
                ],
                self::ACTIONQUEUE_HOST          => [
                    self::TYPE => Tinebase_Config::TYPE_STRING,
                    self::DEFAULT_STR => 'localhost',
                ],
                self::ACTIONQUEUE_LONG_RUNNING  => [
                    self::TYPE => Tinebase_Config::TYPE_STRING,
                    self::DEFAULT_STR => '',
                ],
                self::ACTIONQUEUE_PORT          => [
                    self::TYPE => Tinebase_Config::TYPE_INT,
                    self::DEFAULT_STR => 6379,
                ],
                self::ACTIONQUEUE_NAME          => [
                    self::TYPE => Tinebase_Config::TYPE_STRING,
                    self::DEFAULT_STR => 'TinebaseQueue',
                ],
                self::ACTIONQUEUE_MONITORING_DURATION_WARN       => [
                    self::TYPE => Tinebase_Config::TYPE_INT,
                    self::DEFAULT_STR => 60,
                ],
                self::ACTIONQUEUE_MONITORING_LASTUPDATE_WARN     => [
                    self::TYPE => Tinebase_Config::TYPE_INT,
                    self::DEFAULT_STR => 180,
                ],
                self::ACTIONQUEUE_MONITORING_DURATION_CRIT       => [
                    self::TYPE => Tinebase_Config::TYPE_INT,
                    self::DEFAULT_STR => 3600,
                ],
                self::ACTIONQUEUE_MONITORING_LASTUPDATE_CRIT     => [
                    self::TYPE => Tinebase_Config::TYPE_INT,
                    self::DEFAULT_STR => 3600,
                ],
                self::ACTIONQUEUE_MONITORING_DAEMONSTRCTSIZE_CRIT   => [
                    self::TYPE => Tinebase_Config::TYPE_INT,
                    self::DEFAULT_STR => 30,
                ],
                self::ACTIONQUEUE_LR_MONITORING_DURATION_WARN       => [
                    self::TYPE => Tinebase_Config::TYPE_INT,
                    self::DEFAULT_STR => 360,
                ],
                self::ACTIONQUEUE_LR_MONITORING_LASTUPDATE_WARN     => [
                    self::TYPE => Tinebase_Config::TYPE_INT,
                    self::DEFAULT_STR => 1000,
                ],
                self::ACTIONQUEUE_LR_MONITORING_DURATION_CRIT       => [
                    self::TYPE => Tinebase_Config::TYPE_INT,
                    self::DEFAULT_STR => 3600,
                ],
                self::ACTIONQUEUE_LR_MONITORING_LASTUPDATE_CRIT     => [
                    self::TYPE => Tinebase_Config::TYPE_INT,
                    self::DEFAULT_STR => 3600,
                ],
                self::ACTIONQUEUE_LR_MONITORING_DAEMONSTRCTSIZE_CRIT=> [
                    self::TYPE => Tinebase_Config::TYPE_INT,
                    self::DEFAULT_STR => 3,
                ],
                self::ACTIONQUEUE_QUEUES            => [
                    self::TYPE => Tinebase_Config::TYPE_ARRAY,
                    self::DEFAULT_STR => [],
                ],
            ],
            self::DEFAULT_STR => [],
        ],
        self::ACCOUNT_TWIG                 => [
            self::TYPE                      => self::TYPE_OBJECT,
            self::CLASSNAME                 => Tinebase_Config_Struct::class,
            self::CLIENTREGISTRYINCLUDE     => true,
            self::CONTENT                   => [
                self::ACCOUNT_TWIG_DISPLAYNAME     => [
                    self::TYPE                      => self::TYPE_STRING,
                    self::DEFAULT_STR               => '{{ account.accountLastName|trim }}{% if account.accountLastName|trim|length > 0 and account.accountFirstName|trim|length > 0 %}, {% endif %}{{ account.accountFirstName|trim }}',
                ],
                self::ACCOUNT_TWIG_FULLNAME        => [
                    self::TYPE                      => self::TYPE_STRING,
                    self::DEFAULT_STR               => '{{ account.accountFirstName|trim }}{% if account.accountLastName|trim|length > 0 and account.accountFirstName|trim|length > 0 %} {% endif %}{{ account.accountLastName|trim }}',
                ],
                self::ACCOUNT_TWIG_LOGIN           => [
                    self::TYPE                      => self::TYPE_STRING,
                    self::DEFAULT_STR               => '{{ account.accountFirstName|transliterate|removeSpace|accountLoginChars|trim[0:1]|lower }}{{ account.accountLastName|transliterate|removeSpace|accountLoginChars|lower }}',
                ],
                self::ACCOUNT_TWIG_EMAIL           => [
                    self::TYPE                      => self::TYPE_STRING,
                    self::DEFAULT_STR               => '{{ account.accountLoginName }}@{{ email.primarydomain }}',
                ],
            ],
            self::DEFAULT_STR               => [],
        ],
        self::USERBACKEND_UNAVAILABLE_SINCE => [
            self::TYPE => self::TYPE_INT,
            self::DEFAULT_STR => 0,
        ],
        self::USERBACKEND => array(
                                   //_('User Configuration')
            self::LABEL => 'User Configuration',
                                   //_('User backend configuration.')
            self::DESCRIPTION => 'User backend configuration.',
            self::TYPE => 'object',
            self::CLASSNAME => Tinebase_Config_Struct::class,
            self::CLIENTREGISTRYINCLUDE => FALSE,
            self::SETBYADMINMODULE => FALSE,
            self::SETBYSETUPMODULE => TRUE,
            self::CONTENT => array(
                Tinebase_User::DEFAULT_USER_GROUP_NAME_KEY => array(
                    self::TYPE => Tinebase_Config::TYPE_STRING,
                ),
                Tinebase_User::DEFAULT_ADMIN_GROUP_NAME_KEY => array(
                    self::TYPE => Tinebase_Config::TYPE_STRING,
                ),
                'host'                      => array(
                    self::TYPE => Tinebase_Config::TYPE_STRING,
                ),
                'port'                      => array(
                    self::TYPE => Tinebase_Config::TYPE_INT,
                ),
                'useSsl'                    => array(
                    self::TYPE => Tinebase_Config::TYPE_BOOL,
                ),
                'username'                  => array(
                    self::TYPE => Tinebase_Config::TYPE_STRING,
                ),
                'password'                  => array(
                    self::TYPE => Tinebase_Config::TYPE_STRING,
                ),
                'bindRequiresDn'            => array(
                    self::TYPE => Tinebase_Config::TYPE_BOOL,
                ),
                'baseDn'                    => array(
                    self::TYPE => Tinebase_Config::TYPE_STRING,
                ),
                'accountCanonicalForm'      => array(
                    self::TYPE => Tinebase_Config::TYPE_STRING,
                ),
                'accountDomainName'         => array(
                    self::TYPE => Tinebase_Config::TYPE_STRING,
                ),
                'accountDomainNameShort'    => array(
                    self::TYPE => Tinebase_Config::TYPE_STRING,
                ),
                'accountFilterFormat'       => array(
                    self::TYPE => Tinebase_Config::TYPE_STRING,
                ),
                'allowEmptyPassword'        => array(
                    self::TYPE => Tinebase_Config::TYPE_STRING,
                ),
                'useStartTls'               => array(
                    self::TYPE => Tinebase_Config::TYPE_BOOL,
                ),
                'optReferrals'              => array(
                    self::TYPE => Tinebase_Config::TYPE_STRING,
                ),
                'tryUsernameSplit'          => array(
                    self::TYPE => Tinebase_Config::TYPE_BOOL,
                ),
                'groupUUIDAttribute'        => array(
                    self::TYPE => Tinebase_Config::TYPE_STRING,
                ),
                'groupsDn'                  => array(
                    self::TYPE => Tinebase_Config::TYPE_STRING,
                ),
                'useRfc2307bis'             => array(
                    self::TYPE => Tinebase_Config::TYPE_BOOL,
                ),
                'userDn'                    => array(
                    self::TYPE => Tinebase_Config::TYPE_STRING,
                ),
                'userFilter'                => array(
                    self::TYPE => Tinebase_Config::TYPE_STRING,
                ),
                'userSearchScope'           => array(
                    self::TYPE => Tinebase_Config::TYPE_STRING,
                ),
                'groupFilter'               => array(
                    self::TYPE => Tinebase_Config::TYPE_STRING,
                ),
                'groupSearchScope'          => array(
                    self::TYPE => Tinebase_Config::TYPE_STRING,
                ),
                'pwEncType'                 => array(
                    self::TYPE => Tinebase_Config::TYPE_STRING,
                ),
                'minUserId'                 => array(
                    self::TYPE => Tinebase_Config::TYPE_INT,
                ),
                'maxUserId'                 => array(
                    self::TYPE => Tinebase_Config::TYPE_INT,
                ),
                'minGroupId'                => array(
                    self::TYPE => Tinebase_Config::TYPE_INT,
                ),
                'maxGroupId'                => array(
                    self::TYPE => Tinebase_Config::TYPE_INT,
                ),
                'userUUIDAttribute'         => array(
                    self::TYPE => Tinebase_Config::TYPE_STRING,
                ),
                'readonly'                  => array(
                    self::TYPE => Tinebase_Config::TYPE_BOOL,
                ),
                'useRfc2307'                => array(
                    self::TYPE => Tinebase_Config::TYPE_BOOL,
                ),
                'emailAttribute'            => array(
                    self::TYPE => Tinebase_Config::TYPE_STRING,
                    self::DEFAULT_STR => 'mail',
                ),
                self::USERBACKEND_WRITE_PW_TO_SQL => [
                    self::TYPE                  => Tinebase_Config::TYPE_BOOL,
                    self::DEFAULT_STR           => false,
                ],
                self::SYNCOPTIONS           => array(
                    self::TYPE => 'object',
                    self::CLASSNAME => Tinebase_Config_Struct::class,
                    self::CONTENT => array(
                        self::PWD_CANT_CHANGE => [
                            self::TYPE => self::TYPE_BOOL,
                            self::CLIENTREGISTRYINCLUDE => false,
                            self::SETBYADMINMODULE => false,
                            self::SETBYSETUPMODULE => false,
                            self::DEFAULT_STR => false
                        ],
                        self::SYNC_USER_CONTACT_DATA => array(
                            //_('Sync contact data from sync backend')
                            self::LABEL => 'Sync contact data from sync backend',
                            //_('Sync user contact data from sync backend')
                            self::DESCRIPTION => 'Sync user contact data from sync backend',
                            self::TYPE => 'bool',
                            self::CLIENTREGISTRYINCLUDE => FALSE,
                            self::SETBYADMINMODULE => FALSE,
                            self::SETBYSETUPMODULE => FALSE,
                            self::DEFAULT_STR => TRUE
                        ),
                        self::SYNC_USER_CONTACT_PHOTO => array(
                            //_('Sync contact photo from sync backend')
                            self::LABEL => 'Sync contact photo from sync backend',
                            //_('Sync user contact photo from sync backend')
                            self::DESCRIPTION => 'Sync user contact photo from sync backend',
                            self::TYPE => 'bool',
                            self::CLIENTREGISTRYINCLUDE => FALSE,
                            self::SETBYADMINMODULE => FALSE,
                            self::SETBYSETUPMODULE => FALSE,
                            self::DEFAULT_STR => TRUE
                        ),
                        self::SYNC_DELETED_USER => array(
                            //_('Sync deleted users from sync backend')
                            self::LABEL => 'Sync deleted users from sync backend',
                            //_('Sync deleted users from sync backend')
                            self::DESCRIPTION => 'Sync deleted users from sync backend',
                            self::TYPE => 'bool',
                            self::CLIENTREGISTRYINCLUDE => FALSE,
                            self::SETBYADMINMODULE => FALSE,
                            self::SETBYSETUPMODULE => FALSE,
                            self::DEFAULT_STR => TRUE
                        ),
                        self::SYNC_USER_ACCOUNT_STATUS => array(
                            //_('Sync user account status from sync backend')
                            self::LABEL => 'Sync user account status from sync backend',
                            //_('Sync user account status from sync backend')
                            self::DESCRIPTION => 'Sync user account status from sync backend',
                            self::TYPE => 'bool',
                            self::CLIENTREGISTRYINCLUDE => FALSE,
                            self::SETBYADMINMODULE => FALSE,
                            self::SETBYSETUPMODULE => FALSE,
                            self::DEFAULT_STR => FALSE
                        ),
                        self::SYNC_USER_DISABLED => [
                            //_('Sync user accounts / groups disabled')
                            self::LABEL => 'Sync user accounts / groups disabled',
                            //_('Sync user accounts / groups disabled')
                            self::DESCRIPTION => 'Sync user accounts / groups disabled',
                            self::TYPE => 'bool',
                            self::CLIENTREGISTRYINCLUDE => false,
                            self::SETBYADMINMODULE => false,
                            self::SETBYSETUPMODULE => false,
                            self::DEFAULT_STR => false,
                        ],
                        self::SYNC_USER_OF_GROUPS => [
                            //_('Sync user accounts to sync backend if member of one of these groups')
                            self::LABEL => 'Sync user accounts to sync backend if member of one of these groups',
                            //_('Sync user accounts to sync backend if member of one of these groups')
                            self::DESCRIPTION => 'Sync user accounts to sync backend if member of one of these groups',
                            self::TYPE => self::TYPE_ARRAY,
                            self::CLIENTREGISTRYINCLUDE => false,
                            self::SETBYADMINMODULE => false,
                            self::SETBYSETUPMODULE => false,
                            self::DEFAULT_STR => [],
                        ],
                        self::SYNC_DEVIATED_PRIMARY_GROUP_UUID => [
                            //_('Sync deviated user accounts default group')
                            self::LABEL => 'Sync deviated user accounts default group',
                            //_('Sync deviated user accounts default group')
                            self::DESCRIPTION => 'Sync deviated user accounts default group',
                            self::TYPE => self::TYPE_STRING,
                            self::CLIENTREGISTRYINCLUDE => false,
                            self::SETBYADMINMODULE => false,
                            self::SETBYSETUPMODULE => false,
                            self::DEFAULT_STR => '',
                        ],
                    ),
                    self::DEFAULT_STR => array(),
                ),
            ),
        ),
        self::ENABLED_FEATURES => [
            //_('Enabled Features')
            self::LABEL                 => 'Enabled Features',
            self::DESCRIPTION           => 'Enabled Features',
            self::TYPE                  => self::TYPE_OBJECT,
            self::CLASSNAME             => Tinebase_Config_Struct::class,
            self::CLIENTREGISTRYINCLUDE => true,
            self::CONTENT               => [
                self::FEATURE_SHOW_ADVANCED_SEARCH  => array(
                    self::LABEL                         => 'Show Advanced Search', //_('Show Advanced Search')
                    self::DESCRIPTION                   =>
                        'Show toggle button to switch on or off the advanced search for the quickfilter',
                    //_('Show toggle button to switch on or off the advanced search for the quickfilter')
                    self::TYPE                          => self::TYPE_BOOL,
                    self::DEFAULT_STR                   => true,
                ),
                self::FEATURE_REMEMBER_POPUP_SIZE   => array(
                    self::LABEL                         => 'Remeber Popup Size', //_('Remeber Popup Size')
                    self::DESCRIPTION                   => 'Save edit dialog size in state',
                    //_('Save edit dialog size in state')
                    self::TYPE                          => self::TYPE_BOOL,
                    self::DEFAULT_STR                   => true,
                ),
                self::FEATURE_CREATE_PREVIEWS => [
                    self::LABEL                         => 'Create File Previews', // _('Create File Previews')
                    self::DESCRIPTION                   => 'Create File Previews',
                    self::TYPE                          => self::TYPE_BOOL,
                    self::DEFAULT_STR                   => false,
                ],
                self::FEATURE_FULLTEXT_INDEX              => [
                    self::LABEL                         => 'Create FullText Indices', // _('Create FullText Indices')
                    self::DESCRIPTION                   => 'Create FullText Indices',
                    self::TYPE                          => self::TYPE_BOOL,
                    self::DEFAULT_STR                   => true,
                ],
                self::FEATURE_SEARCH_PATH           => array(
                    self::LABEL                         => 'Search Paths',
                    self::DESCRIPTION                   => 'Search Paths',
                    self::TYPE                          => self::TYPE_BOOL,
                    self::DEFAULT_STR                   => true,
                ),
                self::FEATURE_AUTODISCOVER  => [
                    self::LABEL                 => 'Autodiscover',
                    //_('Autodiscover')
                    self::DESCRIPTION           => 'Autodiscover',
                    //_('Autodiscover')
                    self::TYPE                  => self::TYPE_BOOL,
                    self::DEFAULT_STR           => true,
                ],
                self::FEATURE_AUTODISCOVER_MAILCONFIG  => [
                    self::LABEL                 => 'Autodiscover mail config',
                    //_('Autodiscover mail config')
                    self::DESCRIPTION           => 'Autodiscover mail config',
                    //_('Autodiscover mail config')
                    self::TYPE                  => self::TYPE_BOOL,
                    self::DEFAULT_STR           => true,
                ],
                self::FEATURE_COMMUNITY_IDENT_NR  => [
                    self::LABEL                 => 'Municipality Key',
                    //_('Municipality Key')
                    self::DESCRIPTION           => 'Show the Municipality Key in the Coredata',
                    //_('Show the Municipality Key in the Coredata')
                    self::TYPE                  => self::TYPE_BOOL,
                    self::DEFAULT_STR           => false,
                ],
                self::FEATURE_SITE => array(
                    self::LABEL             => 'Activate Sites',
                    //_('Activate Sites')
                    self::DESCRIPTION       => 'Sites are addressbook contacts matching the siteFilter config.',
                    //_('Sites are addressbook contacts matching the siteFilter config.')
                    self::TYPE              => self::TYPE_BOOL,
                    self::DEFAULT_STR       => false,
                ),
            ],
            self::DEFAULT_STR => [],
        ],
        self::DEFAULT_ADMIN_ROLE_NAME => array(
            //_('Default Admin Role Name')
            self::LABEL => 'Default Admin Role Name',
            self::DESCRIPTION => 'Default Admin Role Name',
            self::TYPE => 'string',
            self::CLIENTREGISTRYINCLUDE => false,
            self::SETBYADMINMODULE => false,
            self::SETBYSETUPMODULE => true,
            self::DEFAULT_STR => 'admin role'
        ),
        self::DEFAULT_USER_ROLE_NAME => array(
            //_('Default User Role Name')
            self::LABEL => 'Default User Role Name',
            self::DESCRIPTION => 'Default User Role Name',
            self::TYPE => 'string',
            self::CLIENTREGISTRYINCLUDE => false,
            self::SETBYADMINMODULE => false,
            self::SETBYSETUPMODULE => true,
            self::DEFAULT_STR => 'user role'
        ),
        self::DEFAULT_CURRENCY => array(
            // _('Default Currency')
            'label'                 => 'Default Currency',
            // _('The currency defined here is used as default currency in the customerd edit dialog.')
            'description'           => 'The currency defined here is used as default currency in the customerd edit dialog.',
            'type'                  => 'string',
            self::CLIENTREGISTRYINCLUDE => true,
            self::SETBYADMINMODULE => false,
            self::SETBYSETUPMODULE => true,
            'default'               => 'EUR'
        ),
        self::DELETED_DATA_RETENTION_TIME => [
            self::LABEL                 => 'Deleted Data Retention Time', // _('Deleted Data Retention Time')
            self::DESCRIPTION           => 'Deleted Data Retention Time (in months)',
            self::TYPE                  => self::TYPE_INT,
            self::CLIENTREGISTRYINCLUDE => false,
            self::SETBYADMINMODULE      => true,
            self::SETBYSETUPMODULE      => false,
            self::DEFAULT_STR           => 12,
        ],
        self::CRON_DISABLED => [
            //_('Cronjob Disabled')
            self::LABEL => 'Cronjob Disabled',
            //_('triggerAsyncEvents does not do anything and monitoringCheckCron does not alert')
            self::DESCRIPTION => 'triggerAsyncEvents does not do anything and monitoringCheckCron does not alert',
            self::TYPE => 'bool',
            self::CLIENTREGISTRYINCLUDE => false,
            self::SETBYADMINMODULE => false,
            self::SETBYSETUPMODULE => true,
            self::DEFAULT_STR => false,
        ],
        self::CRONUSERID => array(
                                   //_('Cronuser ID')
            self::LABEL => 'Cronuser ID',
                                   //_('User ID of the cron user.')
            self::DESCRIPTION => 'User ID of the cron user.',
            self::TYPE => 'string',
            self::CLIENTREGISTRYINCLUDE => FALSE,
            self::SETBYADMINMODULE => TRUE,
            self::SETBYSETUPMODULE => TRUE,
        ),
        self::IMPORT_EXPORT_DEFAULT_CONTAINER => [
            self::LABEL                 => 'Import/Export Default Container', // _('Import/Export Default Container')
            self::DESCRIPTION           => 'Import/Export Default Container',
            self::TYPE                  => self::TYPE_STRING,
            self::CLIENTREGISTRYINCLUDE => true,
            self::SETBYADMINMODULE      => true,
            self::SETBYSETUPMODULE      => false,
        ],
        self::PAGETITLEPOSTFIX => array(
                                   //_('Title Postfix')
            self::LABEL => 'Title Postfix',
                                   //_('Postfix string appended to the title of this installation.')
            self::DESCRIPTION => 'Postfix string appended to the title of this installation.',
            self::TYPE => 'string',
            self::CLIENTREGISTRYINCLUDE => FALSE,
            self::SETBYADMINMODULE => TRUE,
            self::SETBYSETUPMODULE => TRUE,
        ),
        self::REDIRECTURL => array(
                                   //_('Redirect URL')
            self::LABEL => 'Redirect URL',
                                   //_('Redirect to this URL after logout.')
            self::DESCRIPTION => 'Redirect to this URL after logout.',
            self::TYPE => 'string',
            self::CLIENTREGISTRYINCLUDE => true,
            self::SETBYADMINMODULE => FALSE,
            self::SETBYSETUPMODULE => TRUE,
        ),
        self::REDIRECTTOREFERRER => array(
                                   //_('Redirect to Referrer')
            self::LABEL => 'Redirect to Referrer',
                                   //_('Redirect to referrer after logout.')
            self::DESCRIPTION => 'Redirect to referrer after logout.',
            self::TYPE => 'bool',
            self::CLIENTREGISTRYINCLUDE => FALSE,
            self::SETBYADMINMODULE => FALSE,
            self::SETBYSETUPMODULE => TRUE,
        ),
        self::REDIRECTALWAYS => array(
                                   //_('Redirect Always')
            self::LABEL => 'Redirect Always',
                                   //_('Redirect to configured redirect URL also for login.')
            self::DESCRIPTION => 'Redirect to configured redirect URL also for login.',
            self::TYPE => 'bool',
            self::CLIENTREGISTRYINCLUDE => true,
            self::SETBYADMINMODULE => FALSE,
            self::SETBYSETUPMODULE => TRUE,
        ),
        self::BROADCASTHUB  => [
            self::TYPE                  => self::TYPE_OBJECT,
            self::CLASSNAME             => Tinebase_Config_Struct::class,
            self::CLIENTREGISTRYINCLUDE => true,
            self::SETBYADMINMODULE      => false,
            self::SETBYSETUPMODULE      => false,
            self::CONTENT               => [
                self::BROADCASTHUB_ACTIVE   => [
                    self::TYPE                  => self::TYPE_BOOL,
                    self::CLIENTREGISTRYINCLUDE => true,
                    self::DEFAULT_STR           => false,
                ],self::BROADCASTHUB_URL   => [
                    self::TYPE                  => self::TYPE_STRING,
                    self::CLIENTREGISTRYINCLUDE => true,
                    self::DEFAULT_STR           => '//:4003',
                ],
                self::BROADCASTHUB_PUBSUBNAME => [
                    self::TYPE                  => self::TYPE_STRING,
                    self::CLIENTREGISTRYINCLUDE => false,
                    self::DEFAULT_STR           => 'broadcasthub',
                ],
                self::BROADCASTHUB_REDIS    => [
                    self::TYPE                  => self::TYPE_OBJECT,
                    self::CLASSNAME             => Tinebase_Config_Struct::class,
                    self::CLIENTREGISTRYINCLUDE => false,
                    self::CONTENT               => [
                        self::BROADCASTHUB_REDIS_HOST   => [
                            self::TYPE                      => self::TYPE_STRING,
                            self::DEFAULT_STR               => 'localhost',
                        ],
                        self::BROADCASTHUB_REDIS_PORT   => [
                            self::TYPE                      => self::TYPE_INT,
                            self::DEFAULT_STR               => 6379,
                        ]
                    ],
                    self::DEFAULT_STR           => [],
                ]
            ],
            self::DEFAULT_STR           => [],
        ],
        self::STATUS_INFO => array(
            //_('Status Info')
            self::LABEL => 'Status Info',
            //_('If this is enabled, Tine 2.0 provides status information on https://tine20.domain/Tinebase/_status')
            self::DESCRIPTION => 'If this is enabled, Tine 2.0 provides status information on https://tine20.domain/Tinebase/_status',
            self::TYPE => 'bool',
            self::DEFAULT_STR => false,
            self::CLIENTREGISTRYINCLUDE => false,
            self::SETBYADMINMODULE => false,
            self::SETBYSETUPMODULE => TRUE,
        ),
        self::ALLOWEDORIGINS => array(
            //_('Allowed Origins')
            self::LABEL => 'Allowed Origins',
            //_('Allowed Origins')
            self::DESCRIPTION => 'Allowed Origins',
            self::TYPE => 'array',
            self::CLIENTREGISTRYINCLUDE => false,
            self::SETBYADMINMODULE => false,
            self::SETBYSETUPMODULE => false,
        ),
        self::ALLOWEDJSONORIGINS => array(
                                   //_('Allowed Origins')
            self::LABEL => 'Allowed Origins',
                                   //_('Allowed Origins for the JSON API.')
            self::DESCRIPTION => 'Allowed Origins for the JSON API.',
            self::TYPE => 'array',
            self::CLIENTREGISTRYINCLUDE => false,
            self::SETBYADMINMODULE => false,
            self::SETBYSETUPMODULE => false,
        ),
        self::ALLOWEDHEALTHCHECKIPS => array(
            //_('Allowed Health Check IPs')
            self::LABEL => 'Allowed Health Check IPs',
            //_('Hosts that are allowed to access the TINEURL/health API')
            self::DESCRIPTION => 'Hosts that are allowed to access the TINEURL/health API',
            self::TYPE => 'array',
            self::DEFAULT_STR => ['127.0.0.1'],
            self::CLIENTREGISTRYINCLUDE => false,
            self::SETBYADMINMODULE => false,
            self::SETBYSETUPMODULE => false,
        ),
        self::ACCEPTEDTERMSVERSION => array(
                                   //_('Accepted Terms Version')
            self::LABEL => 'Accepted Terms Version',
                                   //_('Accepted version number of the terms and conditions document.')
            self::DESCRIPTION => 'Accepted version number of the terms and conditions document.',
            self::TYPE => 'int',
            self::CLIENTREGISTRYINCLUDE => FALSE,
            self::SETBYADMINMODULE => FALSE,
            self::SETBYSETUPMODULE => FALSE,
        ),
        self::USE_NOMINATIM_SERVICE => [
            //_('Use Nominatim Geocoding Services')
            self::LABEL => 'Use Nominatim Geocoding Services',
            //_('Use of external Nominatim Geocoding service is allowed.')
            self::DESCRIPTION => 'Use of external Nominatim Geocoding service is allowed.',
            self::TYPE => 'bool',
            self::CLIENTREGISTRYINCLUDE => true,
            self::SETBYADMINMODULE => true,
            self::SETBYSETUPMODULE => true,
            self::DEFAULT_STR => false,
        ],
        self::USE_MAP_SERVICE => [
            //_('Use map service')
            self::LABEL => 'Use map service',
            //_('Use map service')
            self::DESCRIPTION => 'Use of external map services is allowed',
            self::TYPE => 'bool',
            self::CLIENTREGISTRYINCLUDE => true,
            self::SETBYADMINMODULE => true,
            self::SETBYSETUPMODULE => true,
            self::DEFAULT_STR => false,
        ],
        self::NOMINATIM_SERVICE_URL => [
            //_('Nominatim Service URL')
            self::LABEL => 'Nominatim Service URL',
            //_('Nominatim Service URL')
            self::DESCRIPTION => 'Nominatim Service URL',
            self::TYPE => Tinebase_Config_Abstract::TYPE_STRING,
            self::CLIENTREGISTRYINCLUDE => true,
            self::SETBYADMINMODULE => true,
            self::SETBYSETUPMODULE => true,
            self::DEFAULT_STR => 'https://nominatim.openstreetmap.org/',
        ],self::MAP_SERVICE_URL => [
            //_('Map Service URL')
            self::LABEL => 'Map Service URL',
            //_('Map Service URL')
            self::DESCRIPTION => 'Map Service URL',
            self::TYPE => Tinebase_Config_Abstract::TYPE_STRING,
            self::CLIENTREGISTRYINCLUDE => true,
            self::SETBYADMINMODULE => true,
            self::SETBYSETUPMODULE => true,
            self::DEFAULT_STR => 'https://tile.openstreetmap.org/',
        ],
        // TODO should this be added to LDAP config array/struct?
        self::LDAP_DISABLE_TLSREQCERT => array(
                                   //_('Disable LDAP TLS Certificate Check')
            self::LABEL => 'Disable LDAP TLS Certificate Check',
                                   //_('LDAP TLS Certificate should not be checked')
            self::DESCRIPTION => 'LDAP TLS Certificate should not be checked',
            self::TYPE => 'bool',
            self::CLIENTREGISTRYINCLUDE => false,
            self::SETBYADMINMODULE => false,
            self::SETBYSETUPMODULE => true,
            self::DEFAULT_STR => false
        ),
        // TODO should this be added to LDAP config array/struct?
        // TODO does this depend on LDAP readonly option?
        self::LDAP_OVERWRITE_CONTACT_FIELDS => array(
            //_('Contact fields overwritten by LDAP')
            self::LABEL => 'Contact fields overwritten by LDAP',
            //_('These fields are overwritten during LDAP sync if empty')
            self::DESCRIPTION => 'These fields are overwritten during LDAP sync if empty',
            self::TYPE => 'array',
            self::CLIENTREGISTRYINCLUDE => false,
            self::SETBYADMINMODULE => false,
            self::SETBYSETUPMODULE => true,
            self::DEFAULT_STR => array()
        ),
        /**
         * Configure rate limits by user / ip / frontends
         *
         * - group priority : user > IP > frontends
         * - both rate limit key and method are validated by regex , exact match has higher priority than wildcard '*'
         * - example config:
         *
         *  $key   => [
         *      [
         *          Tinebase_Model_RateLimit::FLD_METHOD            =>  'Filemanager.save*', // all save methods in Filemanager_Frontend_Json
         *          Tinebase_Model_RateLimit::FLD_MAX_REQUESTS      =>  100, // times
         *          Tinebase_Model_RateLimit::FLD_PERIOD            =>  3600 // minutes
         *      ]
         *  ]
         *
         * - frontend method regex examples:
         *      Tinebase_Server_Expressive   => 'GDPR_Controller_DataIntendedPurposeRecord.publicApiPostManageConsent',
         *      Tinebase_Server_Json         => 'Felamimail.search*',
         *      Tinebase_Server_WebDAV       => // todo: support methods or request uri regex like 'PROPFIND.calendar*'
         *      Tinebase_Server_Http         => 'Felamimail.getResource',
         *
        **/
        self::RATE_LIMITS => [
            self::LABEL                 => 'Rate Limits',
            self::DESCRIPTION           => 'Configure rate limits by user and method',
            self::TYPE                  => self::TYPE_OBJECT,
            self::CLASSNAME             => Tinebase_Config_Struct::class,
            self::CLIENTREGISTRYINCLUDE => false,
            self::SETBYADMINMODULE      => false,
            self::SETBYSETUPMODULE      => false,
            self::CONTENT               => [
                // key is the user login name, eg: 'tine20admin' or Tinebase_Core::USER_ANONYMOUS
                self::RATE_LIMITS_USER   => [
                    self::TYPE                  => self::TYPE_ARRAY,
                    self::CLIENTREGISTRYINCLUDE => false,
                    self::DEFAULT_STR           => [],
                ],
                // key is the netmask, eg: '10.0.10.10',
                self::RATE_LIMITS_IP   => [
                    self::TYPE                  => self::TYPE_ARRAY,
                    self::CLIENTREGISTRYINCLUDE => false,
                    self::DEFAULT_STR           => [],
                ],
                self::RATE_LIMITS_FRONTENDS   => [
                    self::TYPE                  => self::TYPE_ARRAY,
                    self::CLIENTREGISTRYINCLUDE => false,
                    self::DEFAULT_STR           => [
                        'Tinebase_Server_Expressive'   => [],
                        'Tinebase_Server_Json'      => [],
                        'Tinebase_Server_WebDAV'    => [],
                        'Tinebase_Server_Http'     => [],
                        // possible fallback config for all frontends
                        /*
                        '*'     => [
                            [
                                Tinebase_Model_RateLimit::FLD_METHOD            =>  '*',
                                Tinebase_Model_RateLimit::FLD_MAX_REQUESTS      =>  10000,
                                Tinebase_Model_RateLimit::FLD_PERIOD            =>  3600
                            ],
                        ],
                        */
                    ],
                ],
            ]
        ],

        self::SALES_TAX => array(
            //_('Sales Tax Default')
            self::LABEL => 'Sales Tax Default',
            //_('Sales tax that is used as default value in Tine 2.0 apps like Sales.')
            self::DESCRIPTION => 'Sales tax that is used as default value in Tine 2.0 apps like Sales.',
            self::TYPE => Tinebase_Config_Abstract::TYPE_FLOAT,
            self::CLIENTREGISTRYINCLUDE => true,
            self::SETBYADMINMODULE => true,
            self::SETBYSETUPMODULE => true,
            Tinebase_Config_Abstract::DEFAULT_STR => 19.0,
        ),
        self::SENTRY_URI => array(
            //_('Sentry service URI')
            self::LABEL => 'Sentry service URI',
            //_('URI of the sentry service in the following format: https://<key>:<secret>@mysentry.domain/<project>')
            self::DESCRIPTION => 'URI of the sentry service in the following format: https://<key>:<secret>@mysentry.domain/<project>',
            self::TYPE => 'string',
            self::CLIENTREGISTRYINCLUDE => false,
            self::SETBYADMINMODULE => false,
            self::SETBYSETUPMODULE => true,
        ),
        self::SENTRY_LOGLEVL => [
            //_('Sentry Loglevel Bitmask')
            self::LABEL                 => 'Sentry Loglevel Bitmask',
            //_('Sentry Loglevel Bitmask')
            self::DESCRIPTION           => 'Sentry Loglevel Bitmask',
            self::TYPE                  => self::TYPE_INT,
            self::CLIENTREGISTRYINCLUDE => false,
            self::SETBYADMINMODULE      => false,
            self::SETBYSETUPMODULE      => false,
            self::DEFAULT_STR           => E_ALL,
        ],
        self::SENTRY_ENVIRONMENT => array(
            //_('Sentry Environment')
            self::LABEL => 'Sentry Environment',
            //_('One of: AUTODETECT, PRODUCTION, TEST, DEVELOPMENT')
            self::DESCRIPTION => 'One of: AUTODETECT, PRODUCTION, TEST, DEVELOPMENT',
            self::TYPE => 'string',
            self::CLIENTREGISTRYINCLUDE => false,
            self::SETBYADMINMODULE => false,
            self::SETBYSETUPMODULE => false,
            self::DEFAULT_STR => 'AUTODETECT',
        ),
        self::STATUS_API_KEY => array(
            //_('API key to access status URI')
            self::LABEL => 'API key to access status URI',
            //_('API key to access status URI')
            self::DESCRIPTION => 'API key to access status URIs',
            self::TYPE => 'string',
            self::CLIENTREGISTRYINCLUDE => false,
            self::SETBYADMINMODULE => false,
            self::SETBYSETUPMODULE => true,
        ),
        self::METRICS_API_KEY => array(
            //_('API key to access status metrics URI')
            self::LABEL => 'API key to access status metrics URI',
            //_('API key to access status metrics URI')
            self::DESCRIPTION => 'API key to access status metrics URIs',
            self::TYPE => 'string',
            self::CLIENTREGISTRYINCLUDE => false,
            self::SETBYADMINMODULE => false,
            self::SETBYSETUPMODULE => true,
        ),
        self::EVENT_HOOK_CLASS => array(
            //_('Custom event handling hook')
            self::LABEL                 => 'Custom event handling hook',
            //_('Configure PHP hook class for custom event handling')
            self::DESCRIPTION           => 'Configure PHP hook class for custom event handling',
            self::TYPE                  => self::TYPE_STRING,
            self::CLIENTREGISTRYINCLUDE => false,
            self::SETBYADMINMODULE      => false,
            self::SETBYSETUPMODULE      => true,
        ),
        self::SYNC_USER_HOOK_CLASS => array(
                                   //_('Configure hook class for user sync')
            self::LABEL                 => 'Configure hook class for user sync',
                                   //_('Allows to change data after fetching user from sync backend')
            self::DESCRIPTION           => 'Allows to change data after fetching user from sync backend',
            self::TYPE                  => self::TYPE_STRING,
            self::CLIENTREGISTRYINCLUDE => false,
            self::SETBYADMINMODULE      => false,
            self::SETBYSETUPMODULE      => true,
        ),
        self::SYNC_USER_CONTACT_DATA => array(
            //_('Sync contact data from sync backend')
            self::LABEL => 'Sync contact data from sync backend',
            //_('Sync user contact data from sync backend')
            self::DESCRIPTION => 'Sync user contact data from sync backend',
            self::TYPE => 'bool',
            self::CLIENTREGISTRYINCLUDE => FALSE,
            self::SETBYADMINMODULE => FALSE,
            self::SETBYSETUPMODULE => FALSE,
            self::DEFAULT_STR => TRUE
        ),
        self::SYNC_USER_DELETE_AFTER => array(
            //_('Sync user: delete after X months)
            self::LABEL => 'Sync user: delete after X months',
            //_('Removed users should be deleted after X months')
            self::DESCRIPTION => 'Removed users should be deleted after X months',
            self::TYPE => self::TYPE_INT,
            self::CLIENTREGISTRYINCLUDE => FALSE,
            self::SETBYADMINMODULE => FALSE,
            self::SETBYSETUPMODULE => FALSE,
            self::DEFAULT_STR => 12,
        ),
        self::SESSIONIPVALIDATION => array(
                                   //_('IP Session Validator')
            self::LABEL => 'IP Session Validator',
                                   //_('Destroy session if the users IP changes.')
            self::DESCRIPTION => 'Destroy session if the users IP changes.',
            self::TYPE => 'bool',
            self::CLIENTREGISTRYINCLUDE => FALSE,
            self::SETBYADMINMODULE => FALSE,
            self::SETBYSETUPMODULE => TRUE,
        ),
        self::SESSIONUSERAGENTVALIDATION => array(
                                   //_('UA Session Validator')
            self::LABEL => 'UA Session Validator',
                                   //_('Destroy session if the users user agent string changes.')
            self::DESCRIPTION => 'Destroy session if the users user agent string changes.',
            self::TYPE => 'bool',
            self::CLIENTREGISTRYINCLUDE => FALSE,
            self::SETBYADMINMODULE => FALSE,
            self::SETBYSETUPMODULE => TRUE,
        ),
        // TODO move to FILESYSTEM
        self::FILESDIR => array(
                                   //_('Files Directory')
            self::LABEL => 'Files Directory',
                                   //_('Directory with web server write access for user files.')
            self::DESCRIPTION => 'Directory with web server write access for user files.',
            self::TYPE => 'string',
            self::CLIENTREGISTRYINCLUDE => FALSE,
            self::SETBYADMINMODULE => FALSE,
            self::SETBYSETUPMODULE => TRUE,
        ),
        self::REUSEUSERNAME_SAVEUSERNAME => array(
            //_('Reuse last username logged')
            self::LABEL => 'Reuse last username logged',
            //_('Reuse last username logged')            
            self::DESCRIPTION => 'Reuse last username logged',
            self::TYPE => 'bool',
            self::CLIENTREGISTRYINCLUDE => FALSE,
            self::SETBYADMINMODULE => FALSE,
            self::SETBYSETUPMODULE => TRUE,
        ),
        self::PASSWORD_CHANGE => array(
        //_('User may change password')
            self::LABEL => 'User may change password',
        //_('User may change password')
            self::DESCRIPTION => 'User may change password',
            self::TYPE => 'bool',
            self::CLIENTREGISTRYINCLUDE => TRUE,
            self::SETBYADMINMODULE => FALSE,
            self::SETBYSETUPMODULE => TRUE,
            self::DEFAULT_STR => TRUE
        ),
        self::ALLOW_BROWSER_PASSWORD_MANAGER => array(
            //_('Browser password manager can be used')
            self::LABEL => 'Browser password manager can be used',
            self::DESCRIPTION => 'Browser password manager can be used',
            self::TYPE => 'bool',
            self::CLIENTREGISTRYINCLUDE => true,
            self::SETBYADMINMODULE => true,
            self::SETBYSETUPMODULE => true,
            self::DEFAULT_STR => true
        ),
        self::PASSWORD_SUPPORT_NTLMV2 => array(
            //_('Support NTLM V2 authentication')
            self::LABEL => 'Support NTLM V2 authentication',
            //_('Support NTLM V2 authentication and store account password as ntlm v2 hash')
            self::DESCRIPTION => 'Support NTLM V2 authentication and store account password as ntlm v2 hash',
            self::TYPE => 'bool',
            self::CLIENTREGISTRYINCLUDE => false,
            self::SETBYADMINMODULE => true,
            self::SETBYSETUPMODULE => true,
            self::DEFAULT_STR => false
        ),
        self::PASSWORD_NTLMV2_HASH_UPDATE_ON_LOGIN => array(
            //_('Update NTLM V2 password has on login')
            self::LABEL => 'Update NTLM V2 password has on login',
            //_('Update NTLM V2 password has on login')
            self::DESCRIPTION => 'Update NTLM V2 password has on login',
            self::TYPE => 'bool',
            self::CLIENTREGISTRYINCLUDE => false,
            self::SETBYADMINMODULE => true,
            self::SETBYSETUPMODULE => true,
            self::DEFAULT_STR => false
        ),
        self::PASSWORD_NTLMV2_ENCRYPTION_KEY => array(
            //_('NTLM V2 password hash encryption key')
            self::LABEL => 'NTLM V2 password hash encryption key',
            //_('Encryption key used to encrypt and decrypt the NTLM V2 password hash when stored in the database.')
            self::DESCRIPTION => 'Encryption key used to encrypt and decrypt the NTLM V2 password hash when stored in the database.',
            self::TYPE => 'string',
            self::CLIENTREGISTRYINCLUDE => false,
            self::SETBYADMINMODULE => true,
            self::SETBYSETUPMODULE => true,
            self::DEFAULT_STR => null
        ),
        self::USER_PASSWORD_POLICY => array(
            //_('User password policy')
            self::LABEL => 'User password policy',
            //_('User password policy settings.')
            self::DESCRIPTION => 'User password policy settings.',
            self::TYPE => 'object',
            self::CLASSNAME => Tinebase_Config_Struct::class,
            self::CLIENTREGISTRYINCLUDE => TRUE,
            self::SETBYADMINMODULE => FALSE,
            self::SETBYSETUPMODULE => true,
            self::CONTENT => [
                self::CHECK_AT_LOGIN    => [
                    // _('Check password policies at login')
                    self::LABEL             => 'Check password policies at login',
                    self::DESCRIPTION       => 'Check password policies at login',
                    self::TYPE              => self::TYPE_BOOL,
                    self::CLIENTREGISTRYINCLUDE => true,
                    self::SETBYSETUPMODULE  => true,
                    self::SETBYADMINMODULE  => false,
                ],
                self::PASSWORD_MANDATORY => array(
                    //_('A password must be set')
                    self::LABEL => 'A password must be set',
                    //_('A password must be set')
                    self::DESCRIPTION => 'A password must be set',
                    self::TYPE => 'bool',
                    self::CLIENTREGISTRYINCLUDE => TRUE,
                    self::SETBYADMINMODULE => FALSE,
                    self::SETBYSETUPMODULE => TRUE,
                ),
                self::PASSWORD_POLICY_ACTIVE => array(
                    //_('Enable password policy')
                    self::LABEL => 'Enable password policy',
                    //_('Enable password policy')
                    self::DESCRIPTION => 'Enable password policy',
                    self::TYPE => 'bool',
                    self::CLIENTREGISTRYINCLUDE => TRUE,
                    self::SETBYADMINMODULE => FALSE,
                    self::SETBYSETUPMODULE => TRUE,
                ),
                self::PASSWORD_POLICY_ONLYASCII => array(
                    //_('Only ASCII')
                    self::LABEL => 'Only ASCII',
                    //_('Only ASCII characters are allowed in passwords.')
                    self::DESCRIPTION => 'Only ASCII characters are allowed in passwords.',
                    self::TYPE => 'bool',
                    self::CLIENTREGISTRYINCLUDE => FALSE,
                    self::SETBYADMINMODULE => FALSE,
                    self::SETBYSETUPMODULE => TRUE,
                ),
                self::PASSWORD_POLICY_MIN_LENGTH => array(
                    //_('Minimum length')
                    self::LABEL => 'Minimum length',
                    //_('Minimum password length')
                    self::DESCRIPTION => 'Minimum password length',
                    self::TYPE => 'int',
                    self::CLIENTREGISTRYINCLUDE => TRUE,
                    self::SETBYADMINMODULE => FALSE,
                    self::SETBYSETUPMODULE => TRUE,
                ),
                self::PASSWORD_POLICY_MIN_WORD_CHARS => array(
                    //_('Minimum word chars')
                    self::LABEL => 'Minimum word chars',
                    //_('Minimum word chars in password')
                    self::DESCRIPTION => 'Minimum word chars in password',
                    self::TYPE => 'int',
                    self::CLIENTREGISTRYINCLUDE => TRUE,
                    self::SETBYADMINMODULE => FALSE,
                    self::SETBYSETUPMODULE => TRUE,
                ),
                self::PASSWORD_POLICY_MIN_UPPERCASE_CHARS => array(
                    //_('Minimum uppercase chars')
                    self::LABEL => 'Minimum uppercase chars',
                    //_('Minimum uppercase chars in password')
                    self::DESCRIPTION => 'Minimum uppercase chars in password',
                    self::TYPE => 'int',
                    self::CLIENTREGISTRYINCLUDE => TRUE,
                    self::SETBYADMINMODULE => FALSE,
                    self::SETBYSETUPMODULE => TRUE,
                ),
                self::PASSWORD_POLICY_MIN_SPECIAL_CHARS => array(
                    //_('Minimum special chars')
                    self::LABEL => 'Minimum special chars',
                    //_('Minimum special chars in password')
                    self::DESCRIPTION => 'Minimum special chars in password',
                    self::TYPE => 'int',
                    self::CLIENTREGISTRYINCLUDE => TRUE,
                    self::SETBYADMINMODULE => FALSE,
                    self::SETBYSETUPMODULE => TRUE,
                ),
                self::PASSWORD_POLICY_MIN_NUMBERS => array(
                    //_('Minimum numbers')
                    self::LABEL => 'Minimum numbers',
                    //_('Minimum numbers in password')
                    self::DESCRIPTION => 'Minimum numbers in password',
                    self::TYPE => 'int',
                    self::CLIENTREGISTRYINCLUDE => TRUE,
                    self::SETBYADMINMODULE => FALSE,
                    self::SETBYSETUPMODULE => TRUE,
                ),
                self::PASSWORD_POLICY_FORBID_USERNAME => array(
                    //_('Forbid part of username')
                    self::LABEL => 'Forbid part of username',
                    //_('Forbid part of username in password')
                    self::DESCRIPTION => 'Forbid part of username in password',
                    self::TYPE => 'bool',
                    self::CLIENTREGISTRYINCLUDE => FALSE,
                    self::SETBYADMINMODULE => FALSE,
                    self::SETBYSETUPMODULE => TRUE,
                ),
                self::PASSWORD_POLICY_CHANGE_AFTER => array(
                    //_('Change Password After ... Days')
                    self::LABEL => 'Change Password After ... Days',
                    //_('Users need to change their passwords after defined number of days')
                    self::DESCRIPTION => 'Users need to change their passwords after defined number of days',
                    self::TYPE => 'integer',
                    self::CLIENTREGISTRYINCLUDE => FALSE,
                    self::SETBYADMINMODULE => FALSE,
                    self::SETBYSETUPMODULE => TRUE,
                    self::DEFAULT_STR => 0,
                ),
            ],
        ),
        self::WEBFINGER_REL_HANDLER => [
            self::LABEL                 => 'Webfinder Rel Handler',
            self::TYPE                  => self::TYPE_ARRAY,
            self::CLIENTREGISTRYINCLUDE => false,
            self::SETBYADMINMODULE      => false,
            self::SETBYSETUPMODULE      => false,
            self::DEFAULT_STR           => [],
        ],
        self::DOWNLOAD_PASSWORD_POLICY => array(
            //_('Download password policy')
            self::LABEL => 'Download password policy',
            //_('Download password policy settings.')
            self::DESCRIPTION => 'Download password policy settings.',
            self::TYPE => 'object',
            self::CLASSNAME => Tinebase_Config_Struct::class,
            self::CLIENTREGISTRYINCLUDE => TRUE,
            self::SETBYADMINMODULE => FALSE,
            self::SETBYSETUPMODULE => true,
            // TODO move to class constant when we no longer need to support php 5.5
            self::CONTENT => [
                self::PASSWORD_MANDATORY => array(
                    //_('A password must be set')
                    self::LABEL => 'A password must be set',
                    //_('A password must be set')
                    self::DESCRIPTION => 'A password must be set',
                    self::TYPE => 'bool',
                    self::CLIENTREGISTRYINCLUDE => TRUE,
                    self::SETBYADMINMODULE => FALSE,
                    self::SETBYSETUPMODULE => TRUE,
                ),
                self::PASSWORD_POLICY_ACTIVE => array(
                    //_('Enable password policy')
                    self::LABEL => 'Enable password policy',
                    //_('Enable password policy')
                    self::DESCRIPTION => 'Enable password policy',
                    self::TYPE => 'bool',
                    self::CLIENTREGISTRYINCLUDE => TRUE,
                    self::SETBYADMINMODULE => FALSE,
                    self::SETBYSETUPMODULE => TRUE,
                ),
                self::PASSWORD_POLICY_ONLYASCII => array(
                    //_('Only ASCII')
                    self::LABEL => 'Only ASCII',
                    //_('Only ASCII characters are allowed in passwords.')
                    self::DESCRIPTION => 'Only ASCII characters are allowed in passwords.',
                    self::TYPE => 'bool',
                    self::CLIENTREGISTRYINCLUDE => FALSE,
                    self::SETBYADMINMODULE => FALSE,
                    self::SETBYSETUPMODULE => TRUE,
                ),
                self::PASSWORD_POLICY_MIN_LENGTH => array(
                    //_('Minimum length')
                    self::LABEL => 'Minimum length',
                    //_('Minimum password length')
                    self::DESCRIPTION => 'Minimum password length.',
                    self::TYPE => 'int',
                    self::CLIENTREGISTRYINCLUDE => TRUE,
                    self::SETBYADMINMODULE => FALSE,
                    self::SETBYSETUPMODULE => TRUE,
                ),
                self::PASSWORD_POLICY_MIN_WORD_CHARS => array(
                    //_('Minimum word chars')
                    self::LABEL => 'Minimum word chars',
                    //_('Minimum word chars in password')
                    self::DESCRIPTION => 'Minimum word chars in password',
                    self::TYPE => 'int',
                    self::CLIENTREGISTRYINCLUDE => TRUE,
                    self::SETBYADMINMODULE => FALSE,
                    self::SETBYSETUPMODULE => TRUE,
                ),
                self::PASSWORD_POLICY_MIN_UPPERCASE_CHARS => array(
                    //_('Minimum uppercase chars')
                    self::LABEL => 'Minimum uppercase chars',
                    //_('Minimum uppercase chars in password')
                    self::DESCRIPTION => 'Minimum uppercase chars in password',
                    self::TYPE => 'int',
                    self::CLIENTREGISTRYINCLUDE => TRUE,
                    self::SETBYADMINMODULE => FALSE,
                    self::SETBYSETUPMODULE => TRUE,
                ),
                self::PASSWORD_POLICY_MIN_SPECIAL_CHARS => array(
                    //_('Minimum special chars')
                    self::LABEL => 'Minimum special chars',
                    //_('Minimum special chars in password')
                    self::DESCRIPTION => 'Minimum special chars in password',
                    self::TYPE => 'int',
                    self::CLIENTREGISTRYINCLUDE => TRUE,
                    self::SETBYADMINMODULE => FALSE,
                    self::SETBYSETUPMODULE => TRUE,
                ),
                self::PASSWORD_POLICY_MIN_NUMBERS => array(
                    //_('Minimum numbers')
                    self::LABEL => 'Minimum numbers',
                    //_('Minimum numbers in password')
                    self::DESCRIPTION => 'Minimum numbers in password',
                    self::TYPE => 'int',
                    self::CLIENTREGISTRYINCLUDE => TRUE,
                    self::SETBYADMINMODULE => FALSE,
                    self::SETBYSETUPMODULE => TRUE,
                ),
                self::PASSWORD_POLICY_FORBID_USERNAME => array(
                    //_('Forbid part of username')
                    self::LABEL => 'Forbid part of username',
                    //_('Forbid part of username in password')
                    self::DESCRIPTION => 'Forbid part of username in password',
                    self::TYPE => 'bool',
                    self::CLIENTREGISTRYINCLUDE => FALSE,
                    self::SETBYADMINMODULE => FALSE,
                    self::SETBYSETUPMODULE => TRUE,
                ),
                self::PASSWORD_POLICY_CHANGE_AFTER => array(
                    //_('Change Password After ... Days')
                    self::LABEL => 'Change Password After ... Days',
                    //_('Users need to change their passwords after defined number of days')
                    self::DESCRIPTION => 'Users need to change their passwords after defined number of days',
                    self::TYPE => 'integer',
                    self::CLIENTREGISTRYINCLUDE => FALSE,
                    self::SETBYADMINMODULE => FALSE,
                    self::SETBYSETUPMODULE => TRUE,
                    self::DEFAULT_STR => 0,
                ),
            ],
        ),
        self::AUTOMATIC_BUGREPORTS => array(
            //_('Automatic bugreports')
            self::LABEL => 'Automatic bugreports',
            //_('Always send bugreports, even on timeouts and other exceptions / failures.')
            self::DESCRIPTION => 'Always send bugreports, even on timeouts and other exceptions / failures.',
            self::TYPE => 'bool',
            self::CLIENTREGISTRYINCLUDE => TRUE,
            self::SETBYADMINMODULE => FALSE,
            self::SETBYSETUPMODULE => TRUE,
        ),
        self::LICENSE_TYPE => array(
                                   //_('License Type')
            self::LABEL => 'License Type',
                                   //_('License Type')
            self::DESCRIPTION => 'License Type',
            self::TYPE => 'string',
            self::CLIENTREGISTRYINCLUDE => FALSE,
            self::SETBYADMINMODULE => FALSE,
            self::SETBYSETUPMODULE => FALSE,
            self::DEFAULT_STR => 'BusinessEdition'
        ),
        self::LAST_SESSIONS_CLEANUP_RUN => array(
            //_('Last sessions cleanup run')
            self::LABEL => 'Last sessions cleanup run',
            //_('Stores the timestamp of the last sessions cleanup task run.')
            self::DESCRIPTION => 'Stores the timestamp of the last sessions cleanup task run.',
            self::TYPE => self::TYPE_DATETIME,
            self::CLIENTREGISTRYINCLUDE => FALSE,
            self::SETBYADMINMODULE => FALSE,
            self::SETBYSETUPMODULE => FALSE,
        ),
        self::WARN_LOGIN_FAILURES => array(
            //_('Warn after X login failures')
            self::LABEL => 'Warn after X login failures',
            //_('Maximum allowed login failures before writing warn log messages')
            self::DESCRIPTION => 'Maximum allowed login failures before writing warn log messages',
            self::TYPE => 'int',
            self::CLIENTREGISTRYINCLUDE => FALSE,
            self::SETBYADMINMODULE => FALSE,
            self::SETBYSETUPMODULE => TRUE,
            self::DEFAULT_STR => 4
        ),
        self::ANYONE_ACCOUNT_DISABLED => array(
                                   //_('Disable Anyone Account')
            self::LABEL => 'Disable Anyone Account',
                                   //_('Disallow anyone account in grant configurations')
            self::DESCRIPTION => 'Disallow anyone account in grant configurations',
            self::TYPE => 'bool',
            self::CLIENTREGISTRYINCLUDE => TRUE,
            self::SETBYADMINMODULE => FALSE,
            self::SETBYSETUPMODULE => TRUE,
        ),
        self::ALARMS_EACH_JOB => array(
                                   //_('Alarms sent each job')
            self::LABEL => 'Alarms sent each job',
                                   //_('Allows to configure the maximum number of alarm notifications in each run of sendPendingAlarms (0 = no limit)')
            self::DESCRIPTION => 'Allows to configure the maximum number of alarm notifications in each run of sendPendingAlarms (0 = no limit)',
            self::TYPE => 'integer',
            self::CLIENTREGISTRYINCLUDE => FALSE,
            self::SETBYADMINMODULE => FALSE,
            self::SETBYSETUPMODULE => TRUE,
        ),
        self::ACCOUNT_DEACTIVATION_NOTIFICATION => array(
            //_('Account deactivation notfication')
            self::LABEL => 'Account deactivation notfication',
            //_('Send E-Mail to user if the account is deactivated or the user tries to login with deactivated account')
            self::DESCRIPTION => 'Send E-Mail to User if the account is deactivated or the user tries to login with deactivated account',
            self::TYPE => 'bool',
            self::CLIENTREGISTRYINCLUDE => FALSE,
            self::SETBYADMINMODULE => FALSE,
            self::SETBYSETUPMODULE => TRUE,
        ),
        self::ROLE_CHANGE_ALLOWED => array(
                                   //_('Role change allowed')
            self::LABEL => 'Role change allowed',
                                   //_('Allows to configure which user is allowed to switch to another users account')
            self::DESCRIPTION => 'Allows to configure which user is allowed to switch to another users account',
            self::TYPE => Tinebase_Config_Struct::class,
            self::CLIENTREGISTRYINCLUDE => TRUE,
            self::SETBYADMINMODULE => FALSE,
            self::SETBYSETUPMODULE => TRUE,
        ),
        self::MAX_USERNAME_LENGTH => array(
            //_('Max username length')
            self::LABEL => 'Max username length',
            //_('Max username length')
            self::DESCRIPTION => 'Max username length',
            self::TYPE => 'int',
            self::DEFAULT_STR => NULL,
            self::CLIENTREGISTRYINCLUDE => FALSE,
        ),
        self::USER_PIN => array(
            //_('User PIN')
            self::LABEL => 'User PIN',
            //_('Users can have a PIN')
            self::DESCRIPTION => 'Users can have a PIN',
            self::TYPE => 'boolean',
            self::DEFAULT_STR => false,
            self::CLIENTREGISTRYINCLUDE => true,
        ),
        self::USER_PIN_MIN_LENGTH => array(
            //_('User PIN minimum length')
            self::LABEL => 'User PIN minimum length',
            //_('User PIN minimum length')
            self::DESCRIPTION => 'User PIN minimum length',
            self::TYPE => 'integer',
            self::DEFAULT_STR => 4,
            self::CLIENTREGISTRYINCLUDE => true,
        ),
        self::CONFD_FOLDER => array(
            //_('conf.d folder name')
            self::LABEL => 'conf.d folder name',
            //_('Folder for additional config files (conf.d) - NOTE: this is only used if set in config.inc.php!')
            self::DESCRIPTION => 'Folder for additional config files (conf.d) - NOTE: this is only used if set in config.inc.php!',
            self::TYPE => 'string',
            self::DEFAULT_STR => '',
            self::CLIENTREGISTRYINCLUDE => FALSE,
            self::SETBYADMINMODULE => FALSE,
            self::SETBYSETUPMODULE => FALSE,
        ),
        self::MAINTENANCE_MODE => array(
            //_('Maintenance mode enabled')
            self::LABEL => 'Maintenance mode enabled',
            //_('Set Tine 2.0 maintenance mode. Possible values: "off", "on" (only users having the maintenance right can login) and "all"')
            self::DESCRIPTION => 'Set Tine 2.0 maintenance mode. Possible values: "off", "on" (only users having the maintenance right can login) and "all"',
            self::TYPE => 'string',
            self::DEFAULT_STR => '',
            self::CLIENTREGISTRYINCLUDE => FALSE,
            self::SETBYADMINMODULE => TRUE,
            self::SETBYSETUPMODULE => TRUE,
        ),
        self::VERSION_CHECK => array(
            //_('Version check enabled')
            self::LABEL => 'Version check enabled',
            self::DESCRIPTION => 'Version check enabled',
            self::TYPE => 'bool',
            self::DEFAULT_STR => true,
            self::CLIENTREGISTRYINCLUDE => true,
            self::SETBYADMINMODULE => false,
            self::SETBYSETUPMODULE => false,
        ),
        self::FAT_CLIENT_CUSTOM_JS => array(
            // NOTE: it's possible to deliver customjs vom vfs by using the tine20:// streamwrapper
            //       tine20://<applicationid>/folders/shared/<containerid>/custom.js
            //_('Custom Javascript includes for Fat-Client')
            self::LABEL => 'Custom Javascript includes for Fat-Client',
            //_('An array of javascript files to include for the fat client. This files might be stored outside the docroot of the webserver.')
            self::DESCRIPTION => "An array of javascript files to include for the fat client. This files might be stored outside the docroot of the webserver.",
            self::TYPE => 'array',
            self::DEFAULT_STR => array(),
            self::CLIENTREGISTRYINCLUDE => FALSE,
            self::SETBYADMINMODULE => FALSE,
            self::SETBYSETUPMODULE => FALSE,
        ),
        self::BRANDING_TITLE => array(
            //_('custom title')
            self::LABEL => 'custom title',
            //_('Custom title for branding.')
            self::DESCRIPTION => 'Custom title for branding.',
            self::TYPE => 'string',
            self::DEFAULT_STR => "tine ®",
            self::CLIENTREGISTRYINCLUDE => FALSE,
            self::SETBYADMINMODULE => FALSE,
            self::SETBYSETUPMODULE => FALSE,
        ),
        // legacy
        self::BRANDING_LOGO => [
            //_('custom logo path')
            self::LABEL => 'custom logo path - legacy use light/dark variants',
            //_('Path to custom logo.')
            self::DESCRIPTION => 'Path to custom logo.',
            self::TYPE => 'string',
            self::DEFAULT_STR => './images/tine_logo.png',
            self::CLIENTREGISTRYINCLUDE => FALSE,
            self::SETBYADMINMODULE => FALSE,
            self::SETBYSETUPMODULE => FALSE,
        ],
        // use Tinebase_Core::getLogo('b', $colorSchema)
        self::BRANDING_LOGO_LIGHT_SVG => [
            //_('custom logo path')
            self::LABEL => 'custom logo - light, scaleable',
            //_('Path to custom logo.')
            self::DESCRIPTION => 'Path to custom logo - light variant, scaleable.',
            self::TYPE => 'string',
            self::DEFAULT_STR => './images/tine_logo.svg',
            self::CLIENTREGISTRYINCLUDE => FALSE,
            self::SETBYADMINMODULE => FALSE,
            self::SETBYSETUPMODULE => FALSE,
        ],
        // use Tinebase_Core::getLogo('b', $colorSchema)
        self::BRANDING_LOGO_LIGHT => [
            //_('custom logo path')
            self::LABEL => 'custom logo - light',
            //_('Path to custom logo.')
            self::DESCRIPTION => 'Path to custom logo - light variant.',
            self::TYPE => 'string',
            self::DEFAULT_STR => './images/tine_logo.png',
            self::CLIENTREGISTRYINCLUDE => FALSE,
            self::SETBYADMINMODULE => FALSE,
            self::SETBYSETUPMODULE => FALSE,
        ],
        // use Tinebase_Core::getLogo('b', $colorSchema)
        self::BRANDING_LOGO_DARK_SVG => [
            //_('custom logo path')
            self::LABEL => 'custom logo - dark, scaleable',
            //_('Path to custom logo.')
            self::DESCRIPTION => 'Path to custom logo - dark variant, scaleable.',
            self::TYPE => 'string',
            self::DEFAULT_STR => './images/tine_logo.svg',
            self::CLIENTREGISTRYINCLUDE => FALSE,
            self::SETBYADMINMODULE => FALSE,
            self::SETBYSETUPMODULE => FALSE,
        ],
        // use Tinebase_Core::getLogo('b', $colorSchema)
        self::BRANDING_LOGO_DARK => [
            //_('custom logo path')
            self::LABEL => 'custom logo - dark',
            //_('Path to custom logo.')
            self::DESCRIPTION => 'Path to custom logo - dark variant.',
            self::TYPE => 'string',
            self::DEFAULT_STR => './images/tine_logo_dark.png',
            self::CLIENTREGISTRYINCLUDE => FALSE,
            self::SETBYADMINMODULE => FALSE,
            self::SETBYSETUPMODULE => FALSE,
        ],
        self::BRANDING_DESCRIPTION => array(
            //_('custom description')
            self::LABEL => 'custom description',
            //_('Custom description for branding.')
            self::DESCRIPTION => 'Custom description for branding.',
            self::TYPE => 'string',
            self::DEFAULT_STR => '',
            self::CLIENTREGISTRYINCLUDE => FALSE,
            self::SETBYADMINMODULE => FALSE,
            self::SETBYSETUPMODULE => FALSE,
        ),
        self::BRANDING_WEBURL => array(
            //_('custom weburl')
            self::LABEL => 'custom weburl',
            //_('Custom weburl for branding.')
            self::DESCRIPTION => 'Custom weburl for branding.',
            self::TYPE => 'string',
            self::DEFAULT_STR => 'https://github.com/tine20/tine20',
            self::CLIENTREGISTRYINCLUDE => FALSE,
            self::SETBYADMINMODULE => FALSE,
            self::SETBYSETUPMODULE => FALSE
        ),
        self::BRANDING_HELPURL => array(
            //_('custom help url')
            self::LABEL => 'custom help url',
            //_('Custom url for help.')
            self::DESCRIPTION => 'Custom url for help.',
            self::TYPE => 'string',
            self::DEFAULT_STR => 'https://tine-docu.s3web.rz1.metaways.net/',
            self::CLIENTREGISTRYINCLUDE => FALSE,
            self::SETBYADMINMODULE => FALSE,
            self::SETBYSETUPMODULE => FALSE
        ),
        self::BRANDING_SHOPURL => array(
            //_('custom shop url')
            self::LABEL => 'custom shop url',
            //_('Custom url for the shop.')
            self::DESCRIPTION => 'Custom url for the shop.',
            self::TYPE => 'string',
            self::DEFAULT_STR => 'https://www.tine-groupware.de',
            self::CLIENTREGISTRYINCLUDE => FALSE,
            self::SETBYADMINMODULE => FALSE,
            self::SETBYSETUPMODULE => FALSE
        ),
        self::BRANDING_BUGSURL => array(
            //_('custom bugreport url')
            self::LABEL => 'custom bugreport url',
            //_('Custom bugreport url.')
            self::DESCRIPTION => 'Custom bugreport url.',
            self::TYPE => 'string',
            self::DEFAULT_STR => 'https://api.tine20.net/bugreport.php',
            self::CLIENTREGISTRYINCLUDE => FALSE,
            self::SETBYADMINMODULE => FALSE,
            self::SETBYSETUPMODULE => FALSE
        ),
        self::BRANDING_FAVICON => array(
            //_('custom favicon paths')
            self::LABEL => 'custom favicon paths',
            //_('Paths to custom favicons.')
            self::DESCRIPTION => 'Paths to custom favicons.',
            self::TYPE => 'array',
            self::DEFAULT_STR => [
                 16 => './images/favicon.png',
                 30 => './images/favicon30.png',
                300 => './images/favicon300.png',
            ],
            self::CLIENTREGISTRYINCLUDE => FALSE,
            self::SETBYADMINMODULE => FALSE,
            self::SETBYSETUPMODULE => FALSE,
        ),
        self::BRANDING_FAVICON_SVG => array(
            //_('custom svg favicon paths')
            self::LABEL => 'custom svg favicon paths',
            //_('Paths to custom svg favicon.')
            self::DESCRIPTION => 'Paths to custom svg favicon.',
            self::TYPE => 'string',
            self::DEFAULT_STR => './images/favicon.svg',
            self::CLIENTREGISTRYINCLUDE => FALSE,
            self::SETBYADMINMODULE => FALSE,
            self::SETBYSETUPMODULE => FALSE,
        ),
        self::BRANDING_MASKICON_COLOR => array(
            //_('Mask Icon Color')
            self::LABEL => 'Mask Icon Color',
            //_('Background color of mask icon (safari pinned tab).')
            self::DESCRIPTION => 'Background color of mask icon (safari pinned tab).',
            self::TYPE => 'string',
            self::DEFAULT_STR => '#0082ca',
            self::CLIENTREGISTRYINCLUDE => FALSE,
            self::SETBYADMINMODULE => FALSE,
            self::SETBYSETUPMODULE => FALSE,
        ),
        // Retrieve via Tinebase_Core::getInstallLogo(), never use directly!
        self::INSTALL_LOGO => [
            //_('Installation logo')
            self::LABEL => 'Installation logo (legacy, use light/dark variants)',
            //_('Path to custom installation logo.')
            self::DESCRIPTION => 'Path to custom installation logo.',
            self::TYPE => 'string',
            self::DEFAULT_STR => false,
            self::CLIENTREGISTRYINCLUDE => true,
            self::SETBYADMINMODULE => FALSE,
            self::SETBYSETUPMODULE => FALSE,
        ],
        // Retrieve via Tinebase_Core::getInstallLogo(), never use directly!
        self::INSTALL_LOGO_LIGHT_SVG => [
            //_('Installation logo')
            self::LABEL => 'Installation logo - light, scalable.',
            //_('Path to custom installation logo.')
            self::DESCRIPTION => 'Path to custom installation logo - light variant, scalable.',
            self::TYPE => 'string',
            self::DEFAULT_STR => false,
            self::CLIENTREGISTRYINCLUDE => true,
            self::SETBYADMINMODULE => FALSE,
            self::SETBYSETUPMODULE => FALSE,
        ],
        // Retrieve via Tinebase_Core::getInstallLogo(), never use directly!
        self::INSTALL_LOGO_LIGHT => [
            //_('Installation logo')
            self::LABEL => 'Installation logo - light.',
            //_('Path to custom installation logo.')
            self::DESCRIPTION => 'Path to custom installation logo - light variant.',
            self::TYPE => 'string',
            self::DEFAULT_STR => false,
            self::CLIENTREGISTRYINCLUDE => true,
            self::SETBYADMINMODULE => FALSE,
            self::SETBYSETUPMODULE => FALSE,
        ],
        // Retrieve via Tinebase_Core::getInstallLogo(), never use directly!
        self::INSTALL_LOGO_DARK_SVG => [
            //_('Installation logo')
            self::LABEL => 'Installation logo - dark, scalable.',
            //_('Path to custom installation logo.')
            self::DESCRIPTION => 'Path to custom installation logo - dark variant, scalable.',
            self::TYPE => 'string',
            self::DEFAULT_STR => false,
            self::CLIENTREGISTRYINCLUDE => true,
            self::SETBYADMINMODULE => FALSE,
            self::SETBYSETUPMODULE => FALSE,
        ],
        // Retrieve via Tinebase_Core::getInstallLogo(), never use directly!
        self::INSTALL_LOGO_DARK => [
            //_('Installation logo')
            self::LABEL => 'Installation logo - dark.',
            //_('Path to custom installation logo.')
            self::DESCRIPTION => 'Path to custom installation logo - dark variant.',
            self::TYPE => 'string',
            self::DEFAULT_STR => false,
            self::CLIENTREGISTRYINCLUDE => true,
            self::SETBYADMINMODULE => FALSE,
            self::SETBYSETUPMODULE => FALSE,
        ],
        self::WEBSITE_URL => array(
            //_('custom website url')
            self::LABEL => 'custom website url',
            //_('Custom url used for logo on login page.')
            self::DESCRIPTION => 'Custom url used for logo on login page.',
            self::TYPE => 'string',
            self::DEFAULT_STR => '',
            self::CLIENTREGISTRYINCLUDE => FALSE,
            self::SETBYADMINMODULE => FALSE,
            self::SETBYSETUPMODULE => FALSE,
        ),
        self::USE_LOGINNAME_AS_FOLDERNAME => array(
        //_('Use login name instead of full name')
            self::LABEL => 'Use login name instead of full name',
        //_('Use login name instead of full name for webdav.')
            self::DESCRIPTION => 'Use login name instead of full name for webdav.',
            self::TYPE => 'bool',
            self::CLIENTREGISTRYINCLUDE => FALSE,
            self::SETBYADMINMODULE => FALSE,
            self::SETBYSETUPMODULE => FALSE,
            self::DEFAULT_STR => FALSE,
        ),
        self::DENY_WEBDAV_CLIENT_LIST  => array(
            //_('List of WebDav agent strings that will be denied')
            self::LABEL => 'List of WebDav agent strings that will be denied',
            //_('List of WebDav agent strings that will be denied. Expects a list of regular expressions - like this: ["/iPhone/","/iOS/","/Android/"]')
            self::DESCRIPTION => 'List of WebDav agent strings that will be denied. Expects a list of regular expressions - like this: ["/iPhone/","/iOS/","/Android/"]',
            self::TYPE => 'array',
            self::CLIENTREGISTRYINCLUDE => FALSE,
            self::SETBYADMINMODULE => FALSE,
            self::SETBYSETUPMODULE => FALSE,
            self::DEFAULT_STR => NULL,
        ),
        self::WEBDAV_SYNCTOKEN_ENABLED => array(
        //_('Enable SyncToken plugin')
            self::LABEL => 'Enable SyncToken plugin',
        //_('Enable the use of the SyncToken plugin.')
            self::DESCRIPTION => 'Enable the use of the SyncToken plugin.',
            self::TYPE => 'bool',
            self::CLIENTREGISTRYINCLUDE => FALSE,
            self::SETBYADMINMODULE => FALSE,
            self::SETBYSETUPMODULE => FALSE,
            self::DEFAULT_STR => TRUE,
        ),
        self::SUPPORT_REQUEST_NOTIFICATION_ROLE => [
            self::LABEL => 'Role receiving support requests', //_('Role receiving support requests')
            self::DESCRIPTION => 'Role receiving support requests',
            self::TYPE => self::TYPE_STRING,
            self::CLIENTREGISTRYINCLUDE => false,
            self::SETBYADMINMODULE => true,
            self::SETBYSETUPMODULE => true,
        ],
        self::FILESYSTEM => array(
            //_('Filesystem settings')
            self::LABEL => 'Filesystem settings',
            //_('Filesystem settings.')
            self::DESCRIPTION => 'Filesystem settings.',
            self::TYPE => 'object',
            self::CLASSNAME => Tinebase_Config_Struct::class,
            self::CLIENTREGISTRYINCLUDE => TRUE,
            self::SETBYADMINMODULE => FALSE,
            self::SETBYSETUPMODULE => FALSE,
            self::CONTENT => array(
                self::FILESYSTEM_FLYSYSTEM_LOCAL_BASE_PATHS => [
                    //_('FlySystems Local Adapter Base Paths')
                    self::LABEL => 'FlySystems Local Adapter Base Paths',
                    //_('FlySystems Local Adapter Base Paths')
                    self::DESCRIPTION => 'FlySystems Local Adapter Base Paths',
                    self::TYPE => self::TYPE_ARRAY,
                    self::CLIENTREGISTRYINCLUDE => false,
                    self::SETBYADMINMODULE => true,
                    self::SETBYSETUPMODULE => true,
                    self::DEFAULT_STR => [],
                ],
                self::FILESYSTEM_MODLOGACTIVE => array(
                    //_('Filesystem history')
                    self::LABEL => 'Filesystem history',
                    //_('Filesystem keeps history, default is false.')
                    self::DESCRIPTION => 'Filesystem keeps history, default is false.',
                    self::TYPE => 'bool',
                    self::CLIENTREGISTRYINCLUDE => TRUE,
                    self::SETBYADMINMODULE => FALSE,
                    self::SETBYSETUPMODULE => FALSE,
                    self::DEFAULT_STR => FALSE,
                ),
                self::FILESYSTEM_DEFAULT_GRANTS => [
                    self::LABEL                     => 'Filesystem default grants', // _('Filesystem default grants')
                    self::DESCRIPTION               => 'Filesystem default grants', // _('Filesystem default grants')
                    self::TYPE                      => self::TYPE_ARRAY,
                    self::CLIENTREGISTRYINCLUDE     => false,
                    self::SETBYADMINMODULE          => false,
                    self::SETBYSETUPMODULE          => false,
                    self::DEFAULT_STR               => [
                        '[^/]+/folders/shared/[^/]+' => [
                            [
                                'account_id' => [
                                    ['field' => 'id', 'operator' => 'equals', 'value' => Tinebase_Model_User::CURRENTACCOUNT],
                                ],
                                'account_type' => 'user',
                                Tinebase_Model_Grants::GRANT_READ                   => true,
                                Tinebase_Model_Grants::GRANT_ADD                    => true,
                                Tinebase_Model_Grants::GRANT_EDIT                   => true,
                                Tinebase_Model_Grants::GRANT_DELETE                 => true,
                                Calendar_Model_EventPersonalGrants::GRANT_PRIVATE   => true,
                                Tinebase_Model_Grants::GRANT_EXPORT                 => true,
                                Tinebase_Model_Grants::GRANT_SYNC                   => true,
                                Tinebase_Model_Grants::GRANT_ADMIN                  => true,
                                Calendar_Model_EventPersonalGrants::GRANT_FREEBUSY  => true,
                                Tinebase_Model_Grants::GRANT_DOWNLOAD               => true,
                                Tinebase_Model_Grants::GRANT_PUBLISH                => true,
                            ], [
                                'account_id' => [
                                    ['field' => 'id', 'operator' => 'equals', 'value' => Tinebase_Group::DEFAULT_ADMIN_GROUP],
                                ],
                                'account_type' => 'group',
                                Tinebase_Model_Grants::GRANT_READ                   => true,
                                Tinebase_Model_Grants::GRANT_ADD                    => true,
                                Tinebase_Model_Grants::GRANT_EDIT                   => true,
                                Tinebase_Model_Grants::GRANT_DELETE                 => true,
                                Calendar_Model_EventPersonalGrants::GRANT_PRIVATE   => true,
                                Tinebase_Model_Grants::GRANT_EXPORT                 => true,
                                Tinebase_Model_Grants::GRANT_SYNC                   => true,
                                Tinebase_Model_Grants::GRANT_ADMIN                  => true,
                                Calendar_Model_EventPersonalGrants::GRANT_FREEBUSY  => true,
                                Tinebase_Model_Grants::GRANT_DOWNLOAD               => true,
                                Tinebase_Model_Grants::GRANT_PUBLISH                => true,
                            ],
                            /*, [
                                'account_id' => [
                                    ['field' => 'id', 'operator' => 'equals', 'value' => Tinebase_Group::DEFAULT_USER_GROUP],
                                ],
                                'account_type' => 'group',
                                Tinebase_Model_Grants::GRANT_READ   => true,
                                Tinebase_Model_Grants::GRANT_SYNC   => true,
                            ]*/
                        ],
                        '[^/]+/folders/personal/([^/]+)/[^/]+' => [
                            [
                                'account_id' => '$1',
                                'account_type' => 'user',
                                Tinebase_Model_Grants::GRANT_READ                   => true,
                                Tinebase_Model_Grants::GRANT_ADD                    => true,
                                Tinebase_Model_Grants::GRANT_EDIT                   => true,
                                Tinebase_Model_Grants::GRANT_DELETE                 => true,
                                Calendar_Model_EventPersonalGrants::GRANT_PRIVATE   => true,
                                Tinebase_Model_Grants::GRANT_EXPORT                 => true,
                                Tinebase_Model_Grants::GRANT_SYNC                   => true,
                                Tinebase_Model_Grants::GRANT_ADMIN                  => true,
                                Calendar_Model_EventPersonalGrants::GRANT_FREEBUSY  => true,
                                Tinebase_Model_Grants::GRANT_DOWNLOAD               => true,
                                Tinebase_Model_Grants::GRANT_PUBLISH                => true,
                            ]
                        ]
                    ],
                ],
                self::FILESYSTEM_NUMKEEPREVISIONS => array(
                    //_('Filesystem number of revisions')
                    self::LABEL => 'Filesystem number of revisions',
                    //_('Filesystem number of revisions being kept before they are automatically deleted.')
                    self::DESCRIPTION => 'Filesystem number of revisions being kept before they are automatically deleted.',
                    self::TYPE => 'integer',
                    self::CLIENTREGISTRYINCLUDE => TRUE,
                    self::SETBYADMINMODULE => FALSE,
                    self::SETBYSETUPMODULE => FALSE,
                    self::DEFAULT_STR => 100,
                ),
                self::FILESYSTEM_MONTHKEEPREVISIONS => array(
                    //_('Filesystem months of revisions')
                    self::LABEL => 'Filesystem months of revisions',
                    //_('Filesystem number of months revisions being kept before they are automatically deleted.')
                    self::DESCRIPTION => 'Filesystem number of months revisions being kept before they are automatically deleted.',
                    self::TYPE => 'integer',
                    self::CLIENTREGISTRYINCLUDE => TRUE,
                    self::SETBYADMINMODULE => FALSE,
                    self::SETBYSETUPMODULE => FALSE,
                    self::DEFAULT_STR => 60,
                ),
                self::FILESYSTEM_INDEX_CONTENT => array(
                    //_('Filesystem index content')
                    self::LABEL => 'Filesystem index content',
                    //_('Filesystem index content.')
                    self::DESCRIPTION => 'Filesystem index content.',
                    self::TYPE => 'bool',
                    self::CLIENTREGISTRYINCLUDE => TRUE,
                    self::SETBYADMINMODULE => FALSE,
                    self::SETBYSETUPMODULE => FALSE,
                    self::DEFAULT_STR => FALSE,
                ),
                self::FILESYSTEM_ENABLE_NOTIFICATIONS => array(
                    //_('Filesystem enable notifications')
                    self::LABEL => 'Filesystem enable notifications',
                    //_('Filesystem enable notifications.')
                    self::DESCRIPTION => 'Filesystem enable notifications.',
                    self::TYPE => 'bool',
                    self::CLIENTREGISTRYINCLUDE => TRUE,
                    self::SETBYADMINMODULE => FALSE,
                    self::SETBYSETUPMODULE => FALSE,
                    self::DEFAULT_STR => FALSE,
                ),
                self::FILESYSTEM_CREATE_PREVIEWS => array(
                    //_('Filesystem create previews')
                    self::LABEL => 'Filesystem create previews',
                    //_('Filesystem create previews.')
                    self::DESCRIPTION => 'Filesystem create previews.',
                    self::TYPE => 'bool',
                    self::CLIENTREGISTRYINCLUDE => TRUE,
                    self::SETBYADMINMODULE => FALSE,
                    self::SETBYSETUPMODULE => FALSE,
                    self::DEFAULT_STR => FALSE,
                ),
                self::FILESYSTEM_PREVIEW_SERVICE_URL => array(
                    //_('URL of preview service')
                    self::LABEL => 'URL of preview service',
                    //_('URL of preview service.')
                    self::DESCRIPTION => 'URL of preview service.',
                    self::TYPE => 'string',
                    self::CLIENTREGISTRYINCLUDE => FALSE,
                    self::SETBYADMINMODULE => FALSE,
                    self::SETBYSETUPMODULE => FALSE,
                    self::DEFAULT_STR => NULL,
                ),
                self::FILESYSTEM_PREVIEW_SERVICE_VERSION => array(
                    //_('Class for preview service')
                    self::LABEL => 'Version for preview service',
                    //_('Class to use, to connect to preview service.')
                    self::DESCRIPTION => 'Version of preview service api.',
                    self::TYPE => 'int',
                    self::CLIENTREGISTRYINCLUDE => FALSE,
                    self::SETBYADMINMODULE => FALSE,
                    self::SETBYSETUPMODULE => FALSE,
                    self::DEFAULT_STR => 1,
                ),
                self::FILESYSTEM_PREVIEW_SERVICE_VERIFY_SSL => array(
                    //_('Class for preview service')
                    self::LABEL => 'Verify ssl cert',
                    //_('Class to use, to connect to preview service.')
                    self::DESCRIPTION => 'Verify preview service servers ssl cert',
                    self::TYPE => 'bool',
                    self::CLIENTREGISTRYINCLUDE => false,
                    self::SETBYADMINMODULE => false,
                    self::SETBYSETUPMODULE => false,
                    self::DEFAULT_STR => false,
                ),
                self::FILESYSTEM_PREVIEW_MAX_FILE_SIZE => array(
                    //_('Max file size for preview service')
                    self::LABEL => 'Max file size for preview service',
                    //_('Max file size for preview service.')
                    self::DESCRIPTION => 'Max file size for preview service.',
                    self::TYPE => self::TYPE_INT,
                    self::CLIENTREGISTRYINCLUDE => true,
                    self::SETBYADMINMODULE => true,
                    self::SETBYSETUPMODULE => FALSE,
                    self::DEFAULT_STR => 51904512, // == 49.5 * 1024 * 1024,
                ),
                self::FILESYSTEM_PREVIEW_MAX_ERROR_COUNT => array(
                    //_('Max per preview preview service error count, for trying to generate preview.')
                    self::LABEL => 'Max perp review preview service error count, for trying to generate preview.',
                    //_('Max per preview preview service error count, for trying to generate preview.')
                    self::DESCRIPTION => 'Max per preview preview service error count, for trying to generate preview.',
                    self::TYPE => self::TYPE_INT,
                    self::CLIENTREGISTRYINCLUDE => FALSE,
                    self::SETBYADMINMODULE => FALSE,
                    self::SETBYSETUPMODULE => FALSE,
                    self::DEFAULT_STR => 5,
                ),
                self::FILESYSTEM_PREVIEW_THUMBNAIL_SIZE_X => array(
                    //_('X size of thumbnail images.')
                    self::LABEL => 'X size of thumbnail images.',
                    //_('X size of thumbnail images.')
                    self::DESCRIPTION => 'X size of thumbnail images.',
                    self::TYPE => self::TYPE_INT,
                    self::CLIENTREGISTRYINCLUDE => FALSE,
                    self::SETBYADMINMODULE => FALSE,
                    self::SETBYSETUPMODULE => FALSE,
                    self::DEFAULT_STR => 142,
                ),
                self::FILESYSTEM_PREVIEW_THUMBNAIL_SIZE_Y => array(
                    //_('Y size of thumbnail images.')
                    self::LABEL => 'Y size of thumbnail images.',
                    //_('Y size of thumbnail images.')
                    self::DESCRIPTION => 'Y size of thumbnail images.',
                    self::TYPE => self::TYPE_INT,
                    self::CLIENTREGISTRYINCLUDE => FALSE,
                    self::SETBYADMINMODULE => FALSE,
                    self::SETBYSETUPMODULE => FALSE,
                    self::DEFAULT_STR => 200,
                ),
                self::FILESYSTEM_PREVIEW_DOCUMENT_PREVIEW_SIZE_X => array(
                    //_('X size of preview images.')
                    self::LABEL => 'X size of preview images for documents.',
                    //_('X size of preview images.')
                    self::DESCRIPTION => 'X size of preview images for documents.',
                    self::TYPE => self::TYPE_INT,
                    self::CLIENTREGISTRYINCLUDE => FALSE,
                    self::SETBYADMINMODULE => FALSE,
                    self::SETBYSETUPMODULE => FALSE,
                    self::DEFAULT_STR => 1416,
                ),
                self::FILESYSTEM_PREVIEW_DOCUMENT_PREVIEW_SIZE_Y => array(
                    //_('Y size of preview images.')
                    self::LABEL => 'Y size of preview images for documents.',
                    //_('Y size of preview images.')
                    self::DESCRIPTION => 'Y size of preview images for documents.',
                    self::TYPE => self::TYPE_INT,
                    self::CLIENTREGISTRYINCLUDE => FALSE,
                    self::SETBYADMINMODULE => FALSE,
                    self::SETBYSETUPMODULE => FALSE,
                    self::DEFAULT_STR => 2000,
                ),
                self::FILESYSTEM_PREVIEW_IMAGE_PREVIEW_SIZE_X => array(
                    //_('X size of preview images.')
                    self::LABEL => 'X size of preview images for images.',
                    //_('X size of preview images.')
                    self::DESCRIPTION => 'X size of preview images for images.',
                    self::TYPE => self::TYPE_INT,
                    self::CLIENTREGISTRYINCLUDE => FALSE,
                    self::SETBYADMINMODULE => FALSE,
                    self::SETBYSETUPMODULE => FALSE,
                    self::DEFAULT_STR => 708,
                ),
                self::FILESYSTEM_PREVIEW_IMAGE_PREVIEW_SIZE_Y => array(
                    //_('Y size of preview images.')
                    self::LABEL => 'Y size of preview images for images..',
                    //_('Y size of preview images.')
                    self::DESCRIPTION => 'Y size of preview images for images..',
                    self::TYPE => self::TYPE_INT,
                    self::CLIENTREGISTRYINCLUDE => FALSE,
                    self::SETBYADMINMODULE => FALSE,
                    self::SETBYSETUPMODULE => FALSE,
                    self::DEFAULT_STR => 1000,
                ),
                self::FILESYSTEM_PREVIEW_IGNORE_PROXY => array(
                    //_('Ignore Proxy config for preview service')
                    self::LABEL => 'Ignore Proxy config for preview service',
                    //_('Ignore Proxy config for preview service')
                    self::DESCRIPTION => 'Ignore Proxy config for preview service',
                    self::TYPE => self::TYPE_BOOL,
                    self::CLIENTREGISTRYINCLUDE => false,
                    self::SETBYADMINMODULE => false,
                    self::SETBYSETUPMODULE => false,
                    self::DEFAULT_STR => false,
                ),
                self::FILESYSTEM_AVSCAN_MAXFSIZE => [
                    //_('Antivirus Scan Max File Size')
                    self::LABEL                 => 'Antivirus Scan Max File Size',
                    //_('Antivirus Scan Max File Size')
                    self::DESCRIPTION           => 'Antivirus Scan Max File Size',
                    self::TYPE                  => self::TYPE_INT,
                    self::CLIENTREGISTRYINCLUDE => false,
                    self::SETBYADMINMODULE      => true,
                    self::SETBYSETUPMODULE      => true,
                    self::DEFAULT_STR           => 25 * 1024 * 1024,
                ],
                self::FILESYSTEM_AVSCAN_QUEUE_FSIZE => [
                    //_('Antivirus Scan Queue File Size')
                    self::LABEL                 => 'Antivirus Scan Queue File Size',
                    //_('Antivirus Scan Queue File Size in bytes, scan immediately if file size is smaller than the config')
                    self::DESCRIPTION           => 'Antivirus Scan Queue File Size in bytes, scan immediately if file size is smaller than the config',
                    self::TYPE                  => self::TYPE_INT,
                    self::CLIENTREGISTRYINCLUDE => false,
                    self::SETBYADMINMODULE      => true,
                    self::SETBYSETUPMODULE      => true,
                    self::DEFAULT_STR           => 5 * 1024 * 1024,
                ],
                self::FILESYSTEM_AVSCAN_MODE => [
                    //_('Antivirus Scan Mode')
                    self::LABEL                 => 'Antivirus Scan Mode',
                    //_('Antivirus Scan Mode')
                    self::DESCRIPTION           => 'Antivirus Scan Mode',
                    self::TYPE                  => self::TYPE_STRING,
                    self::CLIENTREGISTRYINCLUDE => false,
                    self::SETBYADMINMODULE      => true,
                    self::SETBYSETUPMODULE      => true,
                    // possible values: 'off', 'quahog'
                    self::DEFAULT_STR           => 'off', // don't use constant here, we would just include more source
                                                          // files in the bootstrap of everything
                ],
                self::FILESYSTEM_AVSCAN_URL => [
                    //_('Antivirus Scan URL')
                    self::LABEL                 => 'Antivirus Scan URL',
                    //_('Antivirus Scan URL')
                    self::DESCRIPTION           => 'Antivirus Scan URL',
                    self::TYPE                  => self::TYPE_STRING,
                    self::CLIENTREGISTRYINCLUDE => false,
                    self::SETBYADMINMODULE      => true,
                    self::SETBYSETUPMODULE      => true,
                ],
                self::FILESYSTEM_AVSCAN_NOTIFICATION_ROLE => [
                    //_('Antivirus Scan Notification Role')
                    self::LABEL                 => 'Antivirus Scan Notification Role',
                    //_('Antivirus Scan Notification Role')
                    self::DESCRIPTION           => 'Antivirus Scan Notification Role',
                    self::TYPE                  => self::TYPE_STRING,
                    self::CLIENTREGISTRYINCLUDE => false,
                    self::SETBYADMINMODULE      => true,
                    self::SETBYSETUPMODULE      => true,
                ],
                self::FILESYSTEM_SHOW_CURRENT_USAGE => [
                    //_('Filesystem show current usage')
                    self::LABEL                 => 'Filesystem show current usage',
                    //_('Show fileSystem nodes current usage in grid panel, it affects Admin and Filemanager. check Tinebase_Tree_Node for possible values.')
                    self::DESCRIPTION           => 'Show fileSystem nodes current usage in grid panel, it affects Admin and Filemanager. check Tinebase_Tree_Node for possible values.',
                    self::TYPE                  => self::TYPE_ARRAY,
                    self::CLIENTREGISTRYINCLUDE => TRUE,
                    self::SETBYADMINMODULE      => TRUE,
                    self::SETBYSETUPMODULE      => FALSE,
                    self::DEFAULT_STR           => ['size', 'revision_size']
                ]
            ),
            self::DEFAULT_STR => array(),
        ),
        self::FILTER_SYNC_TOKEN => [
            //_('Filter sync token settings')
            self::LABEL                 => 'Filter sync token settings',
            //_('Filter sync token settings')
            self::DESCRIPTION           => 'Filter sync token settings',
            self::TYPE                  => self::TYPE_OBJECT,
            self::CLASSNAME             => Tinebase_Config_Struct::class,
            self::CLIENTREGISTRYINCLUDE => false,
            self::SETBYADMINMODULE      => true,
            self::SETBYSETUPMODULE      => false,
            self::CONTENT               => [
                self::FILTER_SYNC_TOKEN_CLEANUP_MAX_AGE     => [
                    //_('Max age in days')
                    self::LABEL                 => 'Max age in days',
                    //_('Max age in days')
                    self::DESCRIPTION           => 'Max age in days',
                    self::TYPE                  => self::TYPE_INT,
                    self::CLIENTREGISTRYINCLUDE => false,
                    self::SETBYADMINMODULE      => true,
                    self::SETBYSETUPMODULE      => false,
                    self::DEFAULT_STR           => 750, // 2 years
                ],
                self::FILTER_SYNC_TOKEN_CLEANUP_MAX_TOTAL   => [
                    //_('Max amount in total')
                    self::LABEL                 => 'Max amount in total',
                    //_('Max amount in total')
                    self::DESCRIPTION           => 'Max amount in total',
                    self::TYPE                  => self::TYPE_INT,
                    self::CLIENTREGISTRYINCLUDE => false,
                    self::SETBYADMINMODULE      => true,
                    self::SETBYSETUPMODULE      => false,
                    self::DEFAULT_STR           => 10000000, // 10 mio
                ],
                self::FILTER_SYNC_TOKEN_CLEANUP_MAX_FILTER => [
                    //_('Max amount per filter')
                    self::LABEL                 => 'Max amount per filter',
                    //_('Max amount per filter')
                    self::DESCRIPTION           => 'Max amount per filter',
                    self::TYPE                  => self::TYPE_INT,
                    self::CLIENTREGISTRYINCLUDE => false,
                    self::SETBYADMINMODULE      => true,
                    self::SETBYSETUPMODULE      => false,
                    self::DEFAULT_STR           => 100000, // 100k
                ],
            ],
            self::DEFAULT_STR           => [],
        ],
        self::QUOTA => array(
            //_('Quota settings')
            self::LABEL => 'Quota settings',
            //_('Quota settings')
            self::DESCRIPTION => 'Quota settings',
            self::TYPE => 'object',
            self::CLASSNAME => Tinebase_Config_Struct::class,
            self::CLIENTREGISTRYINCLUDE => true,
            self::SETBYADMINMODULE => false,
            self::SETBYSETUPMODULE => false,
            self::CONTENT => array(
                self::QUOTA_SHOW_UI => array(
                    //_('Show UI')
                    self::LABEL => 'Show UI',
                    //_('Should the quota UI elements be rendered or not.')
                    self::DESCRIPTION => 'Should the quota UI elements be rendered or not.',
                    self::TYPE => 'bool',
                    self::CLIENTREGISTRYINCLUDE => true,
                    self::SETBYADMINMODULE => true,
                    self::SETBYSETUPMODULE => true,
                    self::DEFAULT_STR => true,
                ),
                self::QUOTA_INCLUDE_REVISION => array(
                    //_('Include revisions')
                    self::LABEL => 'Include revisions',
                    //_('Should all revisions be used to calculate total usage?')
                    self::DESCRIPTION => 'Should all revisions be used to calculate total usage?',
                    self::TYPE => 'bool',
                    self::CLIENTREGISTRYINCLUDE => true,
                    self::SETBYADMINMODULE => true,
                    self::SETBYSETUPMODULE => false,
                    self::DEFAULT_STR => false,
                ),
                self::QUOTA_TOTALINMB => array(
                    //_('Total quota in MB')
                    self::LABEL => 'Total quota in MB',
                    //_('Total quota in MB (0 for unlimited)')
                    self::DESCRIPTION => 'Total quota in MB (0 for unlimited)',
                    self::TYPE => 'integer',
                    self::CLIENTREGISTRYINCLUDE => true,
                    self::SETBYADMINMODULE => true,
                    self::SETBYSETUPMODULE => false,
                    self::DEFAULT_STR => 0,
                ),
                self::QUOTA_FILESYSTEM_TOTALINMB => array(
                    //_('Filesystem total quota in MB')
                    self::LABEL => 'Filesystem total quota in MB',
                    //_('Filesystem total quota in MB (0 for unlimited)')
                    self::DESCRIPTION => 'Filesystem total quota in MB (0 for unlimited)',
                    self::TYPE => 'integer',
                    self::CLIENTREGISTRYINCLUDE => true,
                    self::SETBYADMINMODULE => true,
                    self::SETBYSETUPMODULE => false,
                    self::DEFAULT_STR => 0,
                ),
                self::QUOTA_TOTALBYUSERINMB => array(
                    //_('Total quota by user in MB')
                    self::LABEL => 'Total quota by user in MB',
                    //_('Total quota by user in MB (0 for unlimited)')
                    self::DESCRIPTION => 'Total quota by user in MB (0 for unlimited)',
                    self::TYPE => 'integer',
                    self::CLIENTREGISTRYINCLUDE => true,
                    self::SETBYADMINMODULE => true,
                    self::SETBYSETUPMODULE => false,
                    self::DEFAULT_STR => 0,
                ),
                self::QUOTA_SOFT_QUOTA => array(
                    //_('Soft quota in %')
                    self::LABEL => 'Soft quota in %',
                    //_('Soft quota in % (0-100, 0 means off)')
                    self::DESCRIPTION => 'Soft quota in % (0-100, 0 means off)',
                    self::TYPE => 'integer',
                    self::CLIENTREGISTRYINCLUDE => true,
                    self::SETBYADMINMODULE => true,
                    self::SETBYSETUPMODULE => false,
                    self::DEFAULT_STR => 90,
                ),
                self::QUOTA_SQ_NOTIFICATION_ROLE => array(
                    //_('Soft quota notification role')
                    self::LABEL => 'Soft quota notification role',
                    //_('Name of the role to notify if soft quota is exceeded')
                    self::DESCRIPTION => 'Name of the role to notify if soft quota is exceeded',
                    self::TYPE => 'string',
                    self::CLIENTREGISTRYINCLUDE => true,
                    self::SETBYADMINMODULE => true,
                    self::SETBYSETUPMODULE => false,
                    self::DEFAULT_STR => 'soft quota notification',
                ),
                self::QUOTA_SKIP_IMAP_QUOTA => array(
                    //_('Skip Imap Quota Notfication')
                    self::LABEL => 'Skip Imap Quota Notfication',
                    //_('Skip Imap Quota Notfication')
                    self::DESCRIPTION => 'Skip Imap Quota Notfication',
                    self::TYPE => 'bool',
                    self::CLIENTREGISTRYINCLUDE => true,
                    self::SETBYADMINMODULE => true,
                    self::SETBYSETUPMODULE => false,
                    self::DEFAULT_STR => false,
                ),
                self::QUOTA_NOTIFICATION_ADDRESSES => [
                    //_('Quota notification addresses')
                    self::LABEL                 => 'Quota notification addresses',
                    //_('Addresses for sending quota notification email')
                    self::DESCRIPTION           => 'Addresses for sending quota notification email',
                    self::TYPE                  => self::TYPE_ARRAY,
                    self::DEFAULT_STR           => [],
                    self::CLIENTREGISTRYINCLUDE => false,
                    self::SETBYADMINMODULE      => true,
                    self::SETBYSETUPMODULE      => true
                ],
                self::QUOTA_MONITORING => [
                    //_('Quota Monitoring')
                    'label'                 => 'Quota Monitoring',
                    //_('Quota Monitoring')
                    'description'           => 'Quota Monitoring',
                    'type'                  => self::TYPE_BOOL,
                    'clientRegistryInclude' => false,
                    'setByAdminModule'      => false,
                    'setBySetupModule'      => false,
                    'default'               => false,
                    ]
            ),
            self::DEFAULT_STR => array(),
        ),
        self::TINE20_URL => [
            //_('Tine20 URL')
            self::LABEL => 'Tine20 URL',
            //_('The full URL including scheme, hostname, optional port and optional uri part under which tine20 is reachable.')
            self::DESCRIPTION => 'The full URL including scheme, hostname, optional port and optional uri part under which tine20 is reachable.',
            self::TYPE => 'string',
            self::DEFAULT_STR => null,
            self::CLIENTREGISTRYINCLUDE => true,
            self::SETBYADMINMODULE => true,
            self::SETBYSETUPMODULE => true,
        ],
        self::TINE20_URL_USEFORJSCLIENT => [
            //_('Tine20 URL Used For JS Client')
            self::LABEL => 'Tine20 URL Used For JS Client',
            //_('see https://github.com/tine20/tine20/issues/7218')
            self::DESCRIPTION => 'see https://github.com/tine20/tine20/issues/7218',
            self::TYPE                  => self::TYPE_BOOL,
            self::DEFAULT_STR           => true,
            self::CLIENTREGISTRYINCLUDE => false,
            self::SETBYADMINMODULE      => true,
            self::SETBYSETUPMODULE      => true,
        ],
        self::AUTH_TOKEN_CHANNELS => [
            self::TYPE                  => self::TYPE_KEYFIELD_CONFIG,
            self::OPTIONS               => [
                'recordModel'               => Tinebase_Model_AuthTokenChannelConfig::class
            ],
            self::DEFAULT_STR           => [
                'records'                   => [
                    [ Tinebase_Model_AuthTokenChannelConfig::FLDS_NAME => 'broadcasthub', ],
                    [ Tinebase_Model_AuthTokenChannelConfig::FLDS_NAME => Tinebase_Export_Abstract::class . '::expressiveApi', ],
                ],
            ],
            self::CLIENTREGISTRYINCLUDE => false,
            self::SETBYADMINMODULE      => false,
            self::SETBYSETUPMODULE      => false,
        ],
        self::AUTH_TOKEN_DEFAULT_TTL => [
            //_('auth token default and maximum TTL in seconds')
            self::LABEL                 => 'auth token default and maximum TTL in seconds',
            //_('auth token default and maximum TTL in seconds')
            self::DESCRIPTION           => 'auth token default and maximum TTL in seconds',
            self::TYPE                  => self::TYPE_INT,
            self::DEFAULT_STR           => 12 * 60 * 60, // 12 hours
            self::CLIENTREGISTRYINCLUDE => true,
            self::SETBYADMINMODULE      => true,
            self::SETBYSETUPMODULE      => true,
        ],
        self::CREDENTIAL_CACHE_SHARED_KEY => [
            //_('shared credential cache cryptographic key')
            self::LABEL                 => 'shared credential cache cryptographic key',
            //_('shared credential cache cryptographic key')
            self::DESCRIPTION           => 'shared credential cache cryptographic key',
            self::TYPE                  => self::TYPE_STRING,
            self::DEFAULT_STR           => null,
            self::CLIENTREGISTRYINCLUDE => false,
            self::SETBYADMINMODULE      => false,
            self::SETBYSETUPMODULE      => false,
        ],
        self::GRAD_DER_VERSTAEDTERUNG => array(
            //_('Grad der Verstädterung')
            self::LABEL              => 'Grad der Verstädterung',
            //_('')
            self::DESCRIPTION        => '',
            self::TYPE               => self::TYPE_KEYFIELD_CONFIG,
            self::CLIENTREGISTRYINCLUDE => true,
            self::SETBYADMINMODULE      => true,
            self::SETBYSETUPMODULE      => false,
            self::DEFAULT_STR           => array(
                'records' => array(
                    array('id' => '01',    'value' => 'dicht besiedelt', 'system' => true), //_('dicht besiedelt')
                    array('id' => '02',    'value' => 'mittlere Besiedlungsdichte', 'system' => true), //_('mittlere Besiedlungsdichte')
                    array('id' => '03',    'value' => 'gering besiedelt', 'system' => true), //_('gering besiedelt')
                )
            )
        ),
        self::MUNICIPALITYKEY_DUP_FIELDS => array(
            //_('Municipality Key duplicate check fields')
            self::LABEL => 'Municipality Key duplicate check fields',
            //_('These fields are checked when a new Municipality Key is created. If a record with the same data in the fields is found, a duplicate exception is thrown.')
            self::DESCRIPTION => 'These fields are checked when a new Municipality Key is created. If a record with the same data in the fields is found, a duplicate exception is thrown.',
            self::TYPE => 'array',
            'contents'              => 'array',
            self::CLIENTREGISTRYINCLUDE => TRUE,
            self::DEFAULT_STR => array('arsCombined'),
        ),
        self::NOTE_TYPE => [
            self::LABEL                 => 'Note Type', //_('Note Type')
            self::DESCRIPTION           => 'Available Note types for modlog history', //_('Available Note types for modlog history')
            self::TYPE                  => self::TYPE_KEYFIELD_CONFIG,
            self::OPTIONS               => [
                'recordModel'               => Tinebase_Model_NoteType::class,
            ],
            self::CLIENTREGISTRYINCLUDE => true,
            self::DEFAULT_STR           => [
                self::RECORDS               => [
                    ['id' => Tinebase_Model_Note::SYSTEM_NOTE_NAME_NOTE, 'value' => 'Note', 'is_user_type' => 1, 
                        'icon' => 'images/icon-set/icon_note.svg', 'icon_class' => 'notes_noteIcon', 'system' => true], // _('Note')
                    ['id' => Tinebase_Model_Note::SYSTEM_NOTE_NAME_TELEPHONE, 'value' => 'Telephone', 'is_user_type' => 1,
                        'icon' => 'images/icon-set/icon_phone.svg', 'icon_class' => 'notes_telephoneIcon', 'system' => true], // _('Telephone')
                    ['id' => Tinebase_Model_Note::SYSTEM_NOTE_NAME_EMAIL, 'value' => 'E-Mail', 'is_user_type' => 1,
                        'icon' => 'images/icon-set/icon_email.svg', 'icon_class' => 'notes_emailIcon', 'system' => true], // _('E-Mail')
                    ['id' => Tinebase_Model_Note::SYSTEM_NOTE_NAME_CREATED, 'value' => 'Created', 'is_user_type' => 0,
                        'icon' => 'images/icon-set/icon_star_out.svg', 'icon_class' => 'notes_createdIcon', 'system' => true], // _('Created')
                    ['id' => Tinebase_Model_Note::SYSTEM_NOTE_NAME_CHANGED, 'value' => 'Changed', 'is_user_type' => 0,
                        'icon' => 'images/icon-set/icon_file.svg', 'icon_class' => 'notes_changedIcon', 'system' => true], // _('Changed')
                    ['id' => Tinebase_Model_Note::SYSTEM_NOTE_REVEAL_PASSWORD, 'value' => 'Reveal Password', 'is_user_type' => 0,
                        'icon' => 'images/icon-set/icon_preview.svg', 'icon_class' => 'notes_revealPasswordIcon', 'system' => true], // _('Reveal password')
                ],
                self::DEFAULT_STR           => Tinebase_Model_Note::SYSTEM_NOTE_NAME_NOTE,
            ],
        ],
        self::SMS => [
            self::LABEL                 => 'SMS Config', //_('SMS Config')
            self::DESCRIPTION           => 'SMS Config',
            self::TYPE                  => self::TYPE_OBJECT,
            self::CLASSNAME             => Tinebase_Config_Struct::class,
            self::CLIENTREGISTRYINCLUDE => true,
            self::SETBYADMINMODULE      => true,
            self::SETBYSETUPMODULE      => true,
            self::CONTENT               => [
                self::SMS_ADAPTERS          => [
                    self::LABEL                 => 'SMS Adapter Configs', //_('SMS Adapter Configs')
                    self::DESCRIPTION           => 'SMS Adapter Configs',
                    self::TYPE                  => self::TYPE_OBJECT,
                    self::CLASSNAME             => Tinebase_Config_Struct::class,
                    self::CONTENT_CLASS         => Tinebase_Model_Sms_AdapterConfigs::class,
                    self::CLIENTREGISTRYINCLUDE => false,
                    self::DEFAULT_STR           => [],
                ],
                self::SMS_MESSAGE_TEMPLATES          => [
                    self::LABEL                 => 'SMS Message Templates', //_('SMS Message Templates')
                    self::DESCRIPTION           => 'SMS Message Templates', //_('SMS Message Templates')
                    self::TYPE                  => self::TYPE_OBJECT,
                    self::CLASSNAME             => Tinebase_Config_Struct::class,
                    self::CONTENT           => [
                        self::SMS_NEW_PASSWORD_TEMPLATE          => [
                            //_('Template for SMS New Password')
                            self::LABEL                 => 'Template for SMS New Password',
                            //_('Template for SMS New Password with parameters: app, user, contact, password')
                            self::DESCRIPTION           => 'Template for SMS New Password with parameters: app, user, contact, password',
                            self::TYPE                  => self::TYPE_ARRAY,
                            self::CLIENTREGISTRYINCLUDE => true,
                            self::SETBYADMINMODULE      => true,
                            self::SETBYSETUPMODULE      => true,
                            self::DEFAULT_STR           => [
                                'de' => 'Ihr neues {{ app.branding.title }} Passwort ist: {{ password }}',
                                'en' => 'Your new {{ app.branding.title }} password is: {{ password }}'
                            ],
                        ]
                    ],
                    self::DEFAULT_STR           => [],
                ],
            ],
            self::DEFAULT_STR           => [],
        ],
        self::USER_TYPES => [
            //_('User type')
            self::LABEL              => 'User type',
            self::DESCRIPTION        => 'User types',
            self::TYPE               => self::TYPE_KEYFIELD_CONFIG,
            self::CLIENTREGISTRYINCLUDE => true,
            self::SETBYADMINMODULE      => false,
            self::SETBYSETUPMODULE      => false,
            self::DEFAULT_STR           => [
                self::RECORDS => [
                    [
                        'id' => Tinebase_Model_FullUser::USER_TYPE_SYSTEM,
                        'value' => 'System User', //_('System User')
                        'icon' => null,
                        'system' => true
                    ],
                    [
                        'id' => Tinebase_Model_FullUser::USER_TYPE_USER,
                        'value' => 'User', //_('User')
                        'icon' => null,
                        'system' => true
                    ],
                    [
                        'id' => Tinebase_Model_FullUser::USER_TYPE_VOLUNTEER,
                        'value' => 'Volunteer', //_('Volunteer')
                        'icon' => null,
                        'system' => true
                    ],
                ],
                self::DEFAULT_STR => Tinebase_Model_FullUser::USER_TYPE_USER,
            ],
        ],
        self::ACTION_LOG_TYPES => [
            //_('Action log type')
            self::LABEL              => 'Action log type',
            self::DESCRIPTION        => 'Action log type',
            self::TYPE               => self::TYPE_KEYFIELD_CONFIG,
            self::CLIENTREGISTRYINCLUDE => true,
            self::SETBYADMINMODULE      => false,
            self::SETBYSETUPMODULE      => false,
            self::DEFAULT_STR           => [
                self::RECORDS => [
                    ['id' => Tinebase_Model_ActionLog::TYPE_ADD_USER_CONFIRMATION,   'value' => 'Add User Confirmation', 'icon' => null, 'system' => true], //_('Add User Confirmation')
                    ['id' => Tinebase_Model_ActionLog::TYPE_DELETION,                'value' => 'Deletion',              'icon' => null, 'system' => true], //_('Deletion')
                    ['id' => Tinebase_Model_ActionLog::TYPE_EMAIL_NOTIFICATION,      'value' => 'Email Notification',    'icon' => null, 'system' => true], //_('Email Notification')
                    ['id' => Tinebase_Model_ActionLog::TYPE_SUPPORT_REQUEST,         'value' => 'Support Request',       'icon' => null, 'system' => true], //_('Support Request')
                ]
            ],
        ],
        self::SITE_FILTER => array(
            // _('Site Filter')
            self::LABEL                 => 'Site Filter',
            // _('Filter configuration for site record pickers. Sites can be a special type of contacts/groups for example defined by this filter.')
            self::DESCRIPTION           => 'Filter configuration for site record pickers. Sites can be a special type of contacts/groups for example defined by this filter.',
            self::TYPE                  => Tinebase_Config_Abstract::TYPE_ARRAY,
            self::CLIENTREGISTRYINCLUDE => true,
            self::SETBYADMINMODULE      => false,
        ),
        self::SCHEDULER_USER_TASK_FAIL_NOTIFICATION_THRESHOLD => [
            self::LABEL                 => 'User Task Fail Notification Threshold',
            self::DESCRIPTION           => 'User Task Fail Notification Threshold',
            self::TYPE                  => self::TYPE_INT,
            self::DEFAULT_STR           => 3,
            self::CLIENTREGISTRYINCLUDE => false,
            self::SETBYADMINMODULE      => true,
            self::SETBYSETUPMODULE      => false
        ],
    );

    /**
     * (non-PHPdoc)
     * @see tine20/Tinebase/Config/Abstract::$_appName
     */
    protected $_appName = 'Tinebase';
    
    /**
     * holds the instance of the singleton
     *
     * @var Tinebase_Config
     */
    private static $_instance = NULL;

    /**
     * server classes
     *
     * @var array
     */
    protected static $_serverPlugins = array(
        Tinebase_Server_Plugin_Cors::class              => 10,
        Tinebase_Server_Plugin_Json::class              => 40,
        Tinebase_Server_Plugin_WebDAV::class            => 50,
        Tinebase_Server_Plugin_Cli::class               => 100,
        Tinebase_Server_Plugin_Http::class              => 150,
        Tinebase_Server_Plugin_Expressive::class        => 200,
        Tinebase_Server_Plugin_WebDAVCatchAll::class    => 250,
    );

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
            self::$_instance = new Tinebase_Config();
        }
        
        return self::$_instance;
    }

    public static function destroyInstance()
    {
        static::_destroyBackend();
        self::$_instance = null;
    }

    /**
     * (non-PHPdoc)
     * @see tine20/Tinebase/Config/Abstract::getProperties()
     */
    public static function getProperties()
    {
        return self::$_properties;
    }

    public static function resolveRecordValue($val, $definition)
    {
        if ($val && isset($definition['type']) && Tinebase_Config::TYPE_RECORD === $definition['type']) {
            try {
                if (isset($definition[Tinebase_Config::TYPE_RECORD_CONTROLLER])) {
                        $val = $definition[Tinebase_Config::TYPE_RECORD_CONTROLLER]::getInstance()->get($val);
                } elseif (isset($definition[Tinebase_Config::OPTIONS][Tinebase_Config::APPLICATION_NAME]) &&
                    isset($definition[Tinebase_Config::OPTIONS][Tinebase_Config::MODEL_NAME])) {
                    $ctrlName = $definition[Tinebase_Config::OPTIONS][Tinebase_Config::APPLICATION_NAME] .
                        '_Controller_' .
                        $definition[Tinebase_Config::OPTIONS][Tinebase_Config::MODEL_NAME];
                    if (class_exists($ctrlName)) {
                        $val = $ctrlName::getInstance()->get($val);
                    }
                }
            } catch (Tinebase_Exception_AccessDenied $tead) {
                if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) Tinebase_Core::getLogger()->notice(
                    __METHOD__ . '::' . __LINE__  . ' ' . $tead->getMessage() . ' for '
                    . print_r($definition, TRUE));
            } catch (Exception $e) {
                Tinebase_Exception::log($e);
            }
        }

        return $val;
    }

    /**
     * get config for client registry
     * 
     * @return Tinebase_Config_Struct
     */
    public function getClientRegistryConfig(): Tinebase_Config_Struct
    {
        // get all config names to be included in registry
        $clientProperties = new Tinebase_Config_Struct(array());
        $userApplications = Tinebase_Core::getUser()->getApplications(true);
        foreach ($userApplications as $application) {
            $config = Tinebase_Config_Abstract::factory($application->name);
            if ($config) {
                $clientProperties[$application->name] = new Tinebase_Config_Struct(array());
                $properties = $config->getProperties();
                foreach ((array) $properties as $name => $definition) {
                    
                    if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(
                        __METHOD__ . '::' . __LINE__  . ' ' . print_r($definition, true));
                    
                    if (isset($definition['clientRegistryInclude']) && $definition['clientRegistryInclude'] === true)
                    {
                        // add definition here till we have a better place
                        try {
                            $type = $definition['type'] ?? null;
                            if ($type) {
                                $val = static::resolveRecordValue($config->{$name}, $definition);
                                $configRegistryItem = new Tinebase_Config_Struct(array(
                                    'value' => $val,
                                    'definition' => new Tinebase_Config_Struct($definition),
                                ), null, null, array(
                                    'value' => array(self::TYPE => $definition['type']),
                                    'definition' => array(self::TYPE => Tinebase_Config_Abstract::TYPE_ARRAY, self::CLASSNAME => Tinebase_Config_Struct::class)
                                ));
                                if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__
                                    . ' ' . print_r($configRegistryItem->toArray(), true));
                                $clientProperties[$application->name][$name] = $configRegistryItem;
                            } else {
                                if (Tinebase_Core::isLogLevel(Zend_Log::ERR)) Tinebase_Core::getLogger()->err(__METHOD__ . '::' . __LINE__
                                    . ' Type missing from definition: ' . print_r($definition, true));
                            }
                        } catch (Tinebase_Exception_AccessDenied $tead) {
                            if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) Tinebase_Core::getLogger()->notice(
                                __METHOD__ . '::' . __LINE__. ' ' . $tead->getMessage() . ' for '
                                . print_r($definition, true));
                        } catch (Tinebase_Exception_NotFound $tenf) {
                            if (Tinebase_Core::isLogLevel(Zend_Log::WARN)) Tinebase_Core::getLogger()->warn(
                                __METHOD__ . '::' . __LINE__. ' ' . $tenf->getMessage() . ' for '
                                . print_r($definition, true));
                            Tinebase_Exception::log($tenf);
                        } catch (Exception $e) {
                            Tinebase_Exception::log($e);
                        }
                    }
                }
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ 
                    . ' Got ' . count($clientProperties[$application->name]) . ' config items for ' . $application->name . '.');
            }
        }

        // TODO replace this with a hook in the config itself or something
        if (isset($clientProperties[self::APP_NAME][self::AREA_LOCKS]['value']) &&
                $clientProperties[self::APP_NAME][self::AREA_LOCKS]['value']->records) {
            $clientProperties[self::APP_NAME][self::AREA_LOCKS]['value'] = clone
                $clientProperties[self::APP_NAME][self::AREA_LOCKS]['value'];
            /** @var Tinebase_Model_AreaLockConfig $record */
            foreach($clientProperties[self::APP_NAME][self::AREA_LOCKS]['value']->records as $record) {
                $result = [];
                /** @var Tinebase_Model_MFA_UserConfig $usrCfg */
                foreach ($record->getUserMFAIntersection(Tinebase_Core::getUser()) as $usrCfg) {
                    $result[] = $usrCfg->toFEArray();
                }
                $record->{Tinebase_Model_AreaLockConfig::FLD_MFAS} = $result;
            }
        }
        
        return $clientProperties;
    }
    
    /**
     * get application config
     *
     * @param  string  $applicationName Application name
     * @return Tinebase_Config_Abstract  $configClass
     */
    public static function getAppConfig($applicationName)
    {
        $configClassName = $applicationName . '_Config';
        if (@class_exists($configClassName)) {
            /** @noinspection PhpUndefinedMethodInspection */
            return $configClassName::getInstance();
        } else {
            if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
                . ' Application ' . $applicationName . ' has no config.');
            return NULL;
        }
    }
}
