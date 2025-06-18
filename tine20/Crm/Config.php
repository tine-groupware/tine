<?php
/**
 * @package     Crm
 * @subpackage  Config
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2012-2023 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * Crm config class
 * 
 * @package     Crm
 * @subpackage  Config
 */
class Crm_Config extends Tinebase_Config_Abstract
{
    public const APP_NAME = 'Crm';

    /**
     * lead states available
     *
     * @var string
     */
    const LEAD_STATES = 'leadstates';

    /**
     * lead sources available
     *
     * @var string
     */
    const LEAD_SOURCES = 'leadsources';

    /**
     * lead types available
     *
     * @var string
     */
    const LEAD_TYPES = 'leadtypes';

    /**
     * lead import feature
     *
     * @var string
     */
    const FEATURE_LEAD_IMPORT = 'featureLeadImport';

    /**
     * lead notification confirmation
     *
     * @var string
     */
    const FEATURE_LEAD_NOTIFICATION_CONFIRMATION = 'featureLeadNotificationConfirmation';

    /**
     * lead import auto task
     *
     * @var string
     */
    const LEAD_IMPORT_AUTOTASK = 'leadImportAutoTask';

    /**
     * lead import notification mail
     *
     * @var string
     */
    const LEAD_IMPORT_NOTIFICATION = 'leadImportNotification';

    /**
     * fields for lead record duplicate check
     *
     * @var string
     */
    const LEAD_DUP_FIELDS = 'leadDupFields';

    /**
     * send nofifications to responsible/customer/partner
     */
    const SEND_NOTIFICATION = 'sendnotification';
    const SEND_NOTIFICATION_TO_RESPONSIBLE = 'sendnotificationtoresponsible';
    const SEND_NOTIFICATION_TO_CUSTOMER = 'sendnotificationtocustomer';
    const SEND_NOTIFICATION_TO_PARTNER = 'sendnotificationtopartner';
    const SEND_NOTIFICATION_TO_ALL_ACCESS = 'sendnotificationtoallaccess';


    /**
     * (non-PHPdoc)
     * @see tine20/Tinebase/Config/Definition::$_properties
     */
    protected static $_properties = array(
        self::SEND_NOTIFICATION => array(
            'label' => 'send notification', //_('send notification')
            'description' => 'Controls the sending of notifications about leads', //_('Controls the sending of notifications about leads')
            'type' => Tinebase_Config_Abstract::TYPE_BOOL,
            'default' => true,
            'setByAdminModule' => true,
            'clientRegistryInclude' => TRUE,
        ),
        self::SEND_NOTIFICATION_TO_ALL_ACCESS => array(
            'label' => 'Send notification to all access containers', //_('Send notification to all access containers')
            'description' => 'Controls the sending of notifications about leads', //_('Controls the sending of notifications about leads')
            'type' => Tinebase_Config_Abstract::TYPE_BOOL,
            'default' => true,
            'setByAdminModule' => true,
            'clientRegistryInclude' => TRUE,
        ),
        self::SEND_NOTIFICATION_TO_RESPONSIBLE => array(
            'label' => 'Send notification to the responsible person', //_('Send notification to the responsible person')
            'description' => 'Send notification to the responsible person', //_('Send notification to the responsible person')
            'type' => Tinebase_Config_Abstract::TYPE_BOOL,
            'default' => true,
            'setByAdminModule' => true,
            'clientRegistryInclude' => TRUE,
        ),
        self::SEND_NOTIFICATION_TO_CUSTOMER => array(
            'label' => 'Send notification to the customer', //_('Send notification to the customer')
            'description' => 'Send notification to the customer', //_('Send notification to the customer')
            'type' => Tinebase_Config_Abstract::TYPE_BOOL,
            'default' => false,
            'setByAdminModule' => true,
            'clientRegistryInclude' => TRUE,
        ),
        self::SEND_NOTIFICATION_TO_PARTNER => array(
            'label' => 'Send notification to the partner', //_('Send notification to the partner')
            'description' => 'Send notification to the partner', //_('Send notification to the partner')
            'type' => Tinebase_Config_Abstract::TYPE_BOOL,
            'default' => false,
            'setByAdminModule' => true,
            'clientRegistryInclude' => TRUE,
        ),

        self::LEAD_STATES => array(
            //_('Lead States Available')
            'label'                 => 'Lead States Available',
            //_('Possible lead status, with their associated turnover probabilities. If a status is flagged, leads with this status are treated as closed.')
            'description'           => 'Possible lead status, with their associated turnover probabilities. If a status is flagged, leads with this status are treated as closed.',
            'type'                  => Tinebase_Config_Abstract::TYPE_KEYFIELD_CONFIG,
            'options'               => array('recordModel' => 'Crm_Model_LeadState'),
            'clientRegistryInclude' => TRUE,
            'setByAdminModule'      => TRUE,
            'default'               => array(
                'records' => array(
                    array('id' => 1, 'value' => 'open',                  'probability' => 0,     'endslead' => 0,   'readonly' => 0), //_('open')
                    array('id' => 2, 'value' => 'contacted',             'probability' => 10,    'endslead' => 0,   'readonly' => 0), //_('contacted')
                    array('id' => 3, 'value' => 'waiting for feedback',  'probability' => 30,    'endslead' => 0,   'readonly' => 0), //_('waiting for feedback')
                    array('id' => 4, 'value' => 'quote sent',            'probability' => 50,    'endslead' => 0,   'readonly' => 0), //_('quote sent')
                    array('id' => 5, 'value' => 'accepted',              'probability' => 100,   'endslead' => 1,   'readonly' => 0), //_('accepted')
                    array('id' => 6, 'value' => 'lost',                  'probability' => 0,     'endslead' => 1,   'readonly' => 0), //_('lost')
                    array('id' => 7, 'value' => 'read-only',             'probability' => 100,   'endslead' => 1,   'readonly' => 1), //_('read-only')
                ),
                'default' => 1
            )
        ),

        self::LEAD_SOURCES => array(
            //_('Lead Sources Available')
            'label'                 => 'Lead Sources Available',
            //_('Possible lead sources. If a source is marked as archived, leads from that source are treated as closed.')
            'description'           => 'Possible lead sources. If a source is marked as archived, leads from that source are treated as closed.',
            'type'                  => Tinebase_Config_Abstract::TYPE_KEYFIELD_CONFIG,
            'options'               => array('recordModel' => 'Crm_Model_LeadSource'),
            'clientRegistryInclude' => TRUE,
            'setByAdminModule'      => TRUE,
            'default'               => array (
                'records' => array(
                    array('id' => 1, 'value' => 'Market'),
                    array('id' => 2, 'value' => 'Email'),
                    array('id' => 3, 'value' => 'Telephone'),
                    array('id' => 4, 'value' => 'Website'),
                ),
                'default' => 1
            )
        ),

        self::LEAD_TYPES => array(
            //_('Lead Types Available')
            'label'                 => 'Lead Types Available',
            //_('Possible lead types.')
            'description'           => 'Possible lead types.',
            'type'                  => Tinebase_Config_Abstract::TYPE_KEYFIELD_CONFIG,
            'options'               => array('recordModel' => 'Crm_Model_LeadType'),
            'clientRegistryInclude' => TRUE,
            'setByAdminModule'      => TRUE,
            'default'               => array(
                'records' => array(
                    array('id' => 1, 'value' => 'Customer'),   //_('Customer')
                    array('id' => 2, 'value' => 'Partner'),    //_('Partner')
                    array('id' => 3, 'value' => 'Reseller'),   //_('Reseller')
                ),
                'default' => 1
            )
        ),

        /**
         * enabled Crm features
         */
        self::ENABLED_FEATURES => [
            //_('Enabled features')
            self::LABEL                 => 'Enabled features',
            //_('Enabled features in CRM application.')
            self::DESCRIPTION           => 'Enabled features in CRM application.',
            self::TYPE                  => self::TYPE_OBJECT,
            self::CLASSNAME             => Tinebase_Config_Struct::class,
            self::CLIENTREGISTRYINCLUDE => true,
            self::CONTENT               => [
                self::FEATURE_LEAD_IMPORT   => [
                    self::LABEL         => 'Lead Import', //_('Lead Import')
                    self::DESCRIPTION   => 'Lead Import',
                    self::TYPE          => self::TYPE_BOOL,
                    self::DEFAULT_STR   => true,
                ],
                self::FEATURE_LEAD_NOTIFICATION_CONFIRMATION   => [
                    self::LABEL         => 'Lead Notification Confirmation', //_('Lead Notification Confirmation')
                    self::DESCRIPTION   => 'Lead Notification Confirmation',
                    self::TYPE          => self::TYPE_BOOL,
                    self::DEFAULT_STR   => false,
                ],
            ],
            self::DEFAULT_STR => [],
        ],
        self::LEAD_IMPORT_AUTOTASK => array(
            //_('Add a new task on lead import')
            'label'                 => 'Add new task on lead import',
            //_('Automatically creates a task for the responsible person when a new lead is imported.')
            'description'           => 'Automatically creates a task for the responsible person when a new lead is imported.',
            'type'                  => 'boolean',
            'clientRegistryInclude' => false,
            'default'               => false,
        ),
        self::LEAD_IMPORT_NOTIFICATION => array(
            //_('Send notification email on lead import')
            'label'                 => 'Send notification email on lead import',
            //_('Sends an email to all responsible persons for the imported leads.')
            'description'           => 'Sends an email to all responsible persons for the imported leads.',
            'type'                  => 'boolean',
            'clientRegistryInclude' => false,
            'default'               => false,
        ),
        self::LEAD_DUP_FIELDS => array(
            //_('Lead duplicate check fields')
            'label'                 => 'Lead duplicate check fields',
            //_('These fields are checked when a new lead is created. If a record with the same data in these fields is found, a duplicate exception is thrown.')
            'description'           => 'These fields are checked when a new lead is created. If a record with the same data in these fields is found, a duplicate exception is thrown.',
            'type'                  => 'array',
            'contents'              => 'array',
            'clientRegistryInclude' => TRUE,
            'default'               => array('lead_name'),
        ),
    );
    
    /**
     * (non-PHPdoc)
     * @see tine20/Tinebase/Config/Abstract::$_appName
     */
    protected $_appName = 'Crm';
    
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
