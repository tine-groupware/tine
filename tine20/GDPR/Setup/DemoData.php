<?php
/**
 * Tine 2.0
 *
 * @package     GDPR
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Ching En Cheng <c.cheng@metaways.de>
 * @copyright   Copyright (c) 2023 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * class for GDPR initialization
 *
 * @package     Setup
 */
class GDPR_Setup_DemoData extends Tinebase_Setup_DemoData_Abstract
{
    /**
     * holds the instance of the singleton
     *
     * @var GDPR_Setup_DemoData
     */
    private static $_instance = NULL;

    /**
     * the application name to work on
     *
     * @var string
     */
    protected $_appName = GDPR_Config::APP_NAME;

    /**
     * required apps
     *
     * @var array
     */
    protected static $_requiredApplications = array('Admin','Addressbook');

    /**
     * models to work on
     * @var array
     */
    protected $_models = array(GDPR_Model_DataIntendedPurpose::MODEL_NAME_PART);
    
    /**
     * the constructor
     *
     */
    public function __construct()
    {
    }

    /**
     * this is required for other applications needing demo data of this application
     * if this returns true, this demodata has been run already
     * 
     * @return boolean
     */
    public static function hasBeenRun()
    {
        $c = GDPR_Controller_DataIntendedPurpose::getInstance();
        return $c->getAll()->count() > 1;
    }

    /**
     * the singleton pattern
     *
     * @return GDPR_Setup_DemoData
     */
    public static function getInstance()
    {
        if (self::$_instance === NULL) {
            self::$_instance = new self();
        }

        return self::$_instance;
    }

    /**
     * unsets the instance to save memory, be aware that hasBeenRun still needs to work after unsetting!
     *
     */
    public function unsetInstance()
    {
        if (self::$_instance !== NULL) {
            self::$_instance = null;
        }
    }

    /**
     * @see Tinebase_Setup_DemoData_Abstract
     */
    protected function _onCreate()
    {
        $this->_createDataIntendedPurposes();
    }

    protected function _createDataIntendedPurposes()
    {
        $dip1 = GDPR_Controller_DataIntendedPurpose::getInstance()->create(new GDPR_Model_DataIntendedPurpose([
            GDPR_Model_DataIntendedPurpose::FLD_NAME => [[
                    GDPR_Model_DataIntendedPurposeLocalization::FLD_LANGUAGE => 'en',
                    GDPR_Model_DataIntendedPurposeLocalization::FLD_TEXT => 'Newsletter',
                ], [
                    GDPR_Model_DataIntendedPurposeLocalization::FLD_LANGUAGE => 'de',
                    GDPR_Model_DataIntendedPurposeLocalization::FLD_TEXT => 'Newsletter',
                ]
            ],
            GDPR_Model_DataIntendedPurpose::FLD_DESCRIPTION => [[
                    GDPR_Model_DataIntendedPurposeLocalization::FLD_LANGUAGE => 'en',
                    GDPR_Model_DataIntendedPurposeLocalization::FLD_TEXT => 'I consent to receive newsletter with my email',
                ], [
                    GDPR_Model_DataIntendedPurposeLocalization::FLD_LANGUAGE => 'de',
                    GDPR_Model_DataIntendedPurposeLocalization::FLD_TEXT => 'Ich möchte den Newsletter mit meiner E-Mail erhalten',
                ]
            ],
        ]));

        $dip2 = GDPR_Controller_DataIntendedPurpose::getInstance()->create(new GDPR_Model_DataIntendedPurpose([
            GDPR_Model_DataIntendedPurpose::FLD_NAME => [[
                    GDPR_Model_DataIntendedPurposeLocalization::FLD_LANGUAGE => 'en',
                    GDPR_Model_DataIntendedPurposeLocalization::FLD_TEXT => 'Telephone marketing',
                ],[
                    GDPR_Model_DataIntendedPurposeLocalization::FLD_LANGUAGE => 'de',
                    GDPR_Model_DataIntendedPurposeLocalization::FLD_TEXT => 'Telefonmarketing',
                ]
            ],
            GDPR_Model_DataIntendedPurpose::FLD_DESCRIPTION => [[
                    GDPR_Model_DataIntendedPurposeLocalization::FLD_LANGUAGE => 'en',
                    GDPR_Model_DataIntendedPurposeLocalization::FLD_TEXT => 'I consent to receive newsletter with my phone number, you can call me anytime',
                ],[
                    GDPR_Model_DataIntendedPurposeLocalization::FLD_LANGUAGE => 'de',
                    GDPR_Model_DataIntendedPurposeLocalization::FLD_TEXT => 'Ich möchte den Newsletter mit meiner Telefonnummer erhalten, Sie können mich jederzeit anrufen',
                ]
            ],
        ]));
        
        Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Creating 2 test ' . GDPR_Model_DataIntendedPurpose::MODEL_NAME_PART);
        $user = Tinebase_Core::getUser();
        $filter = new Addressbook_Model_ContactFilter(array(
            array('field' => 'n_fileas',      'operator' => 'equals', 'value' => $user->accountDisplayName),
        ));
        $contact = Addressbook_Controller_Contact::getInstance()->search($filter)->getFirstRecord();
        $contact[GDPR_Controller_DataIntendedPurposeRecord::ADB_CONTACT_CUSTOM_FIELD_NAME] = [
            new GDPR_Model_DataIntendedPurposeRecord([
                'intendedPurpose' => $dip1->getId(),
                'record' => $contact->getId(),
                'agreeDate' => Tinebase_DateTime::now(),
            ], true),
            new GDPR_Model_DataIntendedPurposeRecord([
                'intendedPurpose' => $dip2->getId(),
                'record' => $contact->getId(),
                'agreeDate' => Tinebase_DateTime::now(),
            ], true)
        ];
        
        Addressbook_Controller_Contact::getInstance()->update($contact);
    }
}
