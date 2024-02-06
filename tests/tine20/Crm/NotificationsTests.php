<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * Test class for Crm Notifications
 *
 * @package     Crm
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2013-2021 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */
class Crm_NotificationsTests extends Crm_AbstractTest
{
    /**
     * @var Crm_Controller_Lead controller under test
     */
    protected $_leadController;

    /**
     * (non-PHPdoc)
     * @see tests/tine20/Crm/AbstractTest::setUp()
     */
    public function setUp(): void
    {
        parent::setUp();

        Tinebase_Config::getInstance()->clearCache();
        $smtpConfig = Tinebase_Config::getInstance()->get(Tinebase_Config::SMTP, new Tinebase_Config_Struct())->toArray();
        if (empty($smtpConfig)) {
            $this->markTestSkipped('No SMTP config found: this is needed to send notifications.');
        }

        $this->_leadController = Crm_Controller_Lead::getInstance();
    }

    /**
     * @param false $addCf
     * @param bool $addTags
     * @param false $mute
     * @param string $name
     * @return Crm_Model_Lead
     */
    protected function _getLead($addCf = false, $addTags = true, $mute = false, $name = 'PHPUnit')
    {
        return parent::_getLead($addCf, $addTags, $mute, 'PHPUnit LEAD ' . Tinebase_Record_Abstract::generateUID(10));
    }

    /**
     * testNotification
     *
     * @return Crm_Model_Lead
     */
    public function testNotification()
    {
        self::flushMailer();
        $lead = $this->_getLead();

        $lead->relations = array(new Tinebase_Model_Relation(array(
            'type' => 'CUSTOMER',
            'related_id' => $this->_getCreatedContact()->getId(),
            'own_model' => 'Crm_Model_Lead',
            'own_backend' => 'Sql',
            'related_degree' => Tinebase_Model_Relation::DEGREE_SIBLING,
            'related_model' => 'Addressbook_Model_Contact',
            'related_backend' => Tinebase_Model_Relation::DEFAULT_RECORD_BACKEND,
        ), TRUE));

        $savedLead = $this->_leadController->create($lead);

        $messages = self::getMessages();
        $this->assertEquals(1, count($messages));
        $bodyText = $messages[0]->getBodyText()->getContent();
        $this->assertStringContainsString('PHPUnit LEAD', $bodyText);
        return $savedLead;
    }

    /**
     * testNotificationToResponsible
     */
    public function testNotificationToResponsible()
    {
        self::flushMailer();
        
        $lead = $this->_getLead();
        
        // give sclever access to lead container
        $this->_setPersonaGrantsForTestContainer($lead['container_id'], 'sclever');
        
        $lead->relations = array(new Tinebase_Model_Relation(array(
            'type'                   => 'RESPONSIBLE',
            'related_id'             => Addressbook_Controller_Contact::getInstance()->getContactByUserId(Tinebase_Core::getUser()->getId())->getId(),
            'own_model'              => 'Crm_Model_Lead',
            'own_backend'            => 'Sql',
            'related_degree'         => Tinebase_Model_Relation::DEGREE_SIBLING,
            'related_model'          => 'Addressbook_Model_Contact',
            'related_backend'        => Tinebase_Model_Relation::DEFAULT_RECORD_BACKEND,
        ), TRUE));
        $this->_leadController->create($lead);
        
        $messages = self::getMessages();
        $this->assertEquals(1, count($messages));
        $bodyText = $messages[0]->getBodyText()->getContent();
        $this->assertStringContainsString('PHPUnit LEAD', $bodyText);
    }

    /**
     * testNotificationToResponsible
     */
    public function testNotificationWithOutResponsibles()
    {
        Crm_Config::getInstance()->set(Crm_Config::SEND_NOTIFICATION_TO_ALL_ACCESS,true);

        self::flushMailer();
        $lead = $this->_getLead();
        // give sclever access to lead container
        $this->_setPersonaGrantsForTestContainer($lead['container_id'], 'sclever');
        $lead = $this->_leadController->create($lead);
        $messages = self::getMessages();
        $this->assertEquals(2, count($messages));
        $bodyText = $messages[0]->getBodyText()->getContent();
        $this->assertStringContainsString('PHPUnit LEAD', $bodyText);

        Crm_Config::getInstance()->set(Crm_Config::SEND_NOTIFICATION_TO_ALL_ACCESS,false);
        self::flushMailer();

        $lead['turnover'] = '235';
        $lead = $this->_leadController->update($lead);
        $messages = self::getMessages();
        // one message from the update user!
        $this->assertEquals(1, count($messages));
        $bodyText = $messages[0]->getBodyText()->getContent();
        $this->assertStringContainsString('PHPUnit LEAD', $bodyText);
        Crm_Config::getInstance()->set(Crm_Config::SEND_NOTIFICATION_TO_ALL_ACCESS,true);
    }
    /**
     * testNoNotificationConfig
     */
   public function testNoNotificationConfigResponsible()
   {
       $this->_notificationHelper(Crm_Config::SEND_NOTIFICATION_TO_RESPONSIBLE, 'RESPONSIBLE');
   }

    /**
     * @param string $configToSet
     * @param string $type
     * @throws Tinebase_Exception_AccessDenied
     * @throws Tinebase_Exception_NotFound
     * @throws Tinebase_Exception_Record_DefinitionFailure
     * @throws Tinebase_Exception_Record_Validation
     */
   protected function _notificationHelper($configToSet, $type)
   {
       Tinebase_Core::getPreference('Crm')->setValue(
           Crm_Preference::SEND_NOTIFICATION_OF_OWN_ACTIONS,
           Crm_Controller_Lead::NOTIFICATION_WITHOUT);

       self::flushMailer();

       $lead = $this->_getLead();

       $contact = $this->_getContact();
       $savedContact = Addressbook_Controller_Contact::getInstance()->create($contact, FALSE);

       $lead->relations = array(new Tinebase_Model_Relation(array(
           'type' => $type,
           'related_id' => $savedContact->getId(),
           'own_model' => 'Crm_Model_Lead',
           'own_backend' => 'Sql',
           'related_degree' => Tinebase_Model_Relation::DEGREE_SIBLING,
           'related_model' => 'Addressbook_Model_Contact',
           'related_backend' => Tinebase_Model_Relation::DEFAULT_RECORD_BACKEND,
       ), TRUE));

       $this->_leadController->create($lead);

       $messages = self::getMessages();
       $this->assertEquals($type === 'RESPONSIBLE' ? 1 : 0, count($messages));
       self::flushMailer();
       Crm_Config::getInstance()->set($configToSet, $type === 'RESPONSIBLE' ? false : true);

       $lead['turnover'] = '100';

       try {
           $this->_leadController->update($lead);
           $messages = self::getMessages();
           $this->assertEquals($type === 'RESPONSIBLE' ? 0 : 1, count($messages));

           Crm_Config::getInstance()->set($configToSet, $type === 'RESPONSIBLE' ? true : false);
           Tinebase_Core::getPreference('Crm')->setValue(
               Crm_Preference::SEND_NOTIFICATION_OF_OWN_ACTIONS,
               'all');
       } catch (Tinebase_Exception_Record_NotDefined $ternd) {
           // FIXME sometime the relations get lost ... :(
           // maybe another test interfering with this one
       }
   }

    /**
     * testNoNotificationConfig
     */
    public function testNoNotificationConfigPartner()
    {
        Crm_Config::getInstance()->set(Crm_Config::SEND_NOTIFICATION_TO_PARTNER, false);
        $this->_notificationHelper(Crm_Config::SEND_NOTIFICATION_TO_PARTNER, 'PARTNER');
    }

    /**
     * testNoNotificationConfig
     */
    public function testNoNotificationConfigCustomer()
    {
        Crm_Config::getInstance()->set(Crm_Config::SEND_NOTIFICATION_TO_CUSTOMER, false);
        $this->_notificationHelper(Crm_Config::SEND_NOTIFICATION_TO_CUSTOMER, 'CUSTOMER');
    }

    /**
     * testNotificationToResponsible
     */
    public function testNotificationOnDelete()
    {
        $lead = $this->_getLead();

        // give sclever access to lead container
        $this->_setPersonaGrantsForTestContainer($lead['container_id'], 'sclever');

        $lead->relations = array(new Tinebase_Model_Relation(array(
            'type' => 'RESPONSIBLE',
            'related_id' => Addressbook_Controller_Contact::getInstance()->getContactByUserId(Tinebase_Core::getUser()->getId())->getId(),
            'own_model' => 'Crm_Model_Lead',
            'own_backend' => 'Sql',
            'related_degree' => Tinebase_Model_Relation::DEGREE_SIBLING,
            'related_model' => 'Addressbook_Model_Contact',
            'related_backend' => Tinebase_Model_Relation::DEFAULT_RECORD_BACKEND,
        ), TRUE));
        $savedLead = $this->_leadController->create($lead);

        self::flushMailer();

        $this->_leadController->delete($savedLead->getId());

        $messages = self::getMessages();
        $this->assertEquals(1, count($messages));
        $bodyText = $messages[0]->getBodyText()->getContent();
        $this->assertStringContainsString('PHPUnit LEAD', $bodyText);
    }

    /**
     * @see 0011694: show tags and history / latest changes in lead notification mail
     */
    public function testTagAndHistory()
    {
        $lead = $this->testNotification();

        self::flushMailer();

        $tag = new Tinebase_Model_Tag(array(
            'type' => Tinebase_Model_Tag::TYPE_SHARED,
            'name' => 'testNotificationTag',
            'description' => 'testNotificationTag',
            'color' => '#009B31',
        ));
        $lead->tags = array($tag);
        $lead->description = 'updated description';
        $this->_leadController->update($lead);

        $messages = self::getMessages();
        $this->assertEquals(1, count($messages));
        $bodyText = quoted_printable_decode($messages[0]->getBodyText()->getContent());

        $translate = Tinebase_Translation::getTranslation('Crm');
        $changeMessage = $translate->_("'%s' changed from '%s' to '%s'.");

        $this->assertStringContainsString("testNotificationTag\n", $bodyText);
        $this->assertStringContainsString(sprintf($changeMessage, 'description', 'Description', 'updated description'), $bodyText);
    }

    public function testMuteNotification()
    {
        self::flushMailer();
        $lead = $this->_getLead(false, false, true);
        $lead->relations = array(new Tinebase_Model_Relation(array(
            'type' => 'CUSTOMER',
            'related_id' => $this->_getCreatedContact()->getId(),
            'own_model' => 'Crm_Model_Lead',
            'own_backend' => 'Sql',
            'related_degree' => Tinebase_Model_Relation::DEGREE_SIBLING,
            'related_model' => 'Addressbook_Model_Contact',
            'related_backend' => Tinebase_Model_Relation::DEFAULT_RECORD_BACKEND,
        ), TRUE));

        $savedLead = $this->_leadController->create($lead);

        $messages = self::getMessages();
        $this->assertEquals(0, count($messages));
    }
}
