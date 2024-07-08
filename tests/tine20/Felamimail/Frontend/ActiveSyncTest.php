<?php
/**
 * Test class for Felamimail_Frontend_ActiveSync
 *
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Felamimail
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2010-2022 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 *
 * TODO extend Felamimail_TestCase
 */
class Felamimail_Frontend_ActiveSyncTest extends TestCase
{
    /**
     * email test class for checking emails on IMAP server
     * 
     * @var Felamimail_Controller_MessageTest
     */
    protected $_emailTestClass;
    
    /**
     * test controller name
     * 
     * @var string
     */
    protected $_controllerName = 'Felamimail_Frontend_ActiveSync';
    
    /**
     * @var ActiveSync_Frontend_Abstract controller
     */
    protected $_controller;
    
    /**
     * @var array test objects
     */
    protected $objects = array();

    protected $_testUser;
    
    /**
     * xml output
     * 
     * @var string
     */
    protected $_testXMLOutput = '<!DOCTYPE AirSync PUBLIC "-//AIRSYNC//DTD AirSync//EN" "http://www.microsoft.com/"><Sync xmlns="uri:AirSync" xmlns:AirSyncBase="uri:AirSyncBase" xmlns:Email="uri:Email"><Collections><Collection><Class>Email</Class><SyncKey>17</SyncKey><CollectionId>Inbox</CollectionId><Commands><Change><ClientId>1</ClientId><ApplicationData/></Change></Commands></Collection></Collections></Sync>';

    /**
     * folders to delete in tearDown()
     *
     * @var array
     */
    protected $_createdFolders = array();

    protected $_createdMessages;

    /**
     * set up test environment
     * 
     * @todo move setup to abstract test case
     */
    protected function setUp(): void
    {
        parent::setUp();
        
        $imapConfig = Tinebase_Config::getInstance()->get(Tinebase_Config::IMAP, new Tinebase_Config_Struct())->toArray();
        if (empty($imapConfig) || !(isset($imapConfig['useSystemAccount']) || array_key_exists('useSystemAccount', $imapConfig)) || $imapConfig['useSystemAccount'] != true) {
            $this->markTestSkipped('IMAP backend not configured');
        }
        $this->_testUser    = Tinebase_Core::getUser();

        $this->_emailTestClass = new Felamimail_Controller_MessageTest();
        $this->_emailTestClass->setup();
        $this->_createdMessages = new Tinebase_Record_RecordSet('Felamimail_Model_Message');
        
        $this->objects['devices'] = array();
        
        Syncroton_Registry::set(Syncroton_Registry::DEVICEBACKEND,       new Syncroton_Backend_Device(Tinebase_Core::getDb(), SQL_TABLE_PREFIX . 'acsync_'));
        Syncroton_Registry::set(Syncroton_Registry::FOLDERBACKEND,       new Syncroton_Backend_Folder(Tinebase_Core::getDb(), SQL_TABLE_PREFIX . 'acsync_'));
        Syncroton_Registry::set(Syncroton_Registry::SYNCSTATEBACKEND,    new Syncroton_Backend_SyncState(Tinebase_Core::getDb(), SQL_TABLE_PREFIX . 'acsync_'));
        Syncroton_Registry::set(Syncroton_Registry::CONTENTSTATEBACKEND, new Syncroton_Backend_Content(Tinebase_Core::getDb(), SQL_TABLE_PREFIX . 'acsync_'));
        Syncroton_Registry::set('loggerBackend',                         Tinebase_Core::getLogger());
        Syncroton_Registry::set(Syncroton_Registry::POLICYBACKEND,       new Syncroton_Backend_Policy(Tinebase_Core::getDb(), SQL_TABLE_PREFIX . 'acsync_'));
        
        Syncroton_Registry::setContactsDataClass('Addressbook_Frontend_ActiveSync');
        Syncroton_Registry::setCalendarDataClass('Calendar_Frontend_ActiveSync');
        Syncroton_Registry::setEmailDataClass('Felamimail_Frontend_ActiveSync');
        Syncroton_Registry::setTasksDataClass('Tasks_Frontend_ActiveSync');
    }

    /**
     * Tears down the fixture
     * This method is called after a test is executed.
     *
     * @access protected
     */
    protected function tearDown(): void
    {
        if ($this->_emailTestClass instanceof Felamimail_Controller_MessageTest) {
            $this->_emailTestClass->tearDown();
        }
        
        Felamimail_Controller_Message_Flags::getInstance()->addFlags($this->_createdMessages, array(Zend_Mail_Storage::FLAG_DELETED));
        Felamimail_Controller_Message::getInstance()->delete($this->_createdMessages->getArrayOfIds());

        if (count($this->_createdFolders) > 0) {
            foreach ($this->_createdFolders as $folderName) {
                try {
                    Felamimail_Controller_Folder::getInstance()->delete(TestServer::getInstance()->getTestEmailAccount(), $folderName, true);
                } catch (Zend_Mail_Storage_Exception $zmse) {
                    // already deleted
                } catch (Felamimail_Exception_IMAPFolderNotFound $zmse) {
                    // already deleted
                }
            }
        }
        parent::tearDown();
    }
    
    /**
     * validate getEntry
     */
    public function testGetEntry()
    {
        $controller = $this->_getController($this->_getDevice(Syncroton_Model_Device::TYPE_ANDROID_40));
        
        $message = $this->_createTestMessage();
        
        $syncrotonModelEmail = $controller->getEntry(
            new Syncroton_Model_SyncCollection(array('collectionId' => 'foobar', 'options' => array('bodyPreferences' => array('2' => array('type' => '2'))))), 
            $message->getId()
        );
        
        $this->assertEquals('9661', $syncrotonModelEmail->body->estimatedDataSize);
    }

    /**
     * validate getEntry with an Emoji
     */
    public function testGetEntryWithEmoji()
    {
        $controller = $this->_getController($this->_getDevice(Syncroton_Model_Device::TYPE_ANDROID_40));
    
        $message = $this->_createTestMessage('emoji.eml', 'emoji.eml');
    
        $syncrotonModelEmail = $controller->getEntry(
                new Syncroton_Model_SyncCollection(array('collectionId' => 'foobar', 'options' => array('bodyPreferences' => array('2' => array('type' => '2'))))),
                $message->getId()
        );
        
        $this->assertEquals('1744', $syncrotonModelEmail->body->estimatedDataSize);
    }

    /**
     * validate getEntry with winmail.dat
     */
    public function testGetEntryWithWinmailDat()
    {
        if (! Tinebase_Core::systemCommandExists('tnef') && ! Tinebase_Core::systemCommandExists('ytnef')) {
            $this->markTestSkipped('The (y)tnef command could not be found!');
        }

        $controller = $this->_getController($this->_getDevice(Syncroton_Model_Device::TYPE_ANDROID_40));

        $message = $this->_createTestMessage('winmail_dat_attachment.eml', 'winmail_dat_attachment.eml');

        $syncrotonModelEmail = $controller->getEntry(
            new Syncroton_Model_SyncCollection(array('collectionId' => 'foobar', 'options' => array('bodyPreferences' => array('2' => array('type' => '2'))))),
            $message->getId()
        );

        $path = Tinebase_Core::getTempDir() . '/winmail/' . $message->getId() . '/';
        $content = file_get_contents($path . 'bookmark.htm');
        $dataSize = strlen($content);
        $this->assertStringStartsWith('<!DOCTYPE NETSCAPE-Bookmark-file-1>', $content);

        self::assertEquals(2, count($syncrotonModelEmail->attachments), print_r($syncrotonModelEmail->attachments, true));

        $bookmarkAttachments = array_filter( $syncrotonModelEmail->attachments, function (Syncroton_Model_EmailAttachment $attachment) {
            if ($attachment->displayName === 'bookmark.htm') {
                return true;
            }
        });
        self::assertCount(1, $bookmarkAttachments, 'did not get bookmark.htm: '
            . print_r($syncrotonModelEmail->attachments, true)
        );
        $bookmarkAttachment = array_pop($bookmarkAttachments);
        $this->assertEquals($dataSize, $bookmarkAttachment->estimatedDataSize, print_r($bookmarkAttachments, true));

        // try to get file by reference
        $syncrotonFileReference = $controller->getFileReference($bookmarkAttachment->fileReference);
        $this->assertEquals('text/html', $syncrotonFileReference->contentType);
        $this->assertEquals($dataSize, strlen(stream_get_contents($syncrotonFileReference->data)));
    }

    /**
     * validate fetching email by filereference(hashid-partid)
     */
    public function testGetFileReference()
    {
        $controller = $this->_getController($this->_getDevice(Syncroton_Model_Device::TYPE_WEBOS));
        
        $message = $this->_createTestMessage();
        
        $fileReference = $message->getId() . ActiveSync_Frontend_Abstract::LONGID_DELIMITER . '2';
        
        $syncrotonFileReference = $controller->getFileReference($fileReference);
        
        $this->assertEquals('text/plain', $syncrotonFileReference->contentType);
        $this->assertEquals(2787, strlen(stream_get_contents($syncrotonFileReference->data)));
    }
    
    /**
     * create test message with $this->_emailTestClass->messageTestHelper()
     * 
     * @return Felamimail_Model_Message
     */
    protected function _createTestMessage($emailFile = 'multipart_mixed.eml', $headerToReplace = 'multipart/mixed', $folder = null)
    {
        $testMessageId = Tinebase_Record_Abstract::generateUID();
        
        return $this->_emailTestClass->messageTestHelper(
            $emailFile,
            $testMessageId,
            $folder,
            array('X-Tine20TestMessage: ' . $headerToReplace, 'X-Tine20TestMessage: ' . $testMessageId)
        );
    }

    public function testDeleteMessageToTrashWithTrashDeleted()
    {
        $controller = $this->_getController($this->_getDevice(Syncroton_Model_Device::TYPE_ANDROID_40));
        $message = $this->_createTestMessage();

        // delete trash folder
        $folder  = Felamimail_Controller_Folder::getInstance()->get($message->folder_id);
        $trashFolder = Felamimail_Controller_Account::getInstance()->getSystemFolder($folder->account_id,
            Felamimail_Model_Folder::FOLDER_TRASH);
        Felamimail_Controller_Folder::getInstance()->delete($folder->account_id, $trashFolder->globalname);

        $xml = simplexml_load_string('<Collection><DeletesAsMoves>1</DeletesAsMoves></Collection>');
        $syncCol = new Syncroton_Model_SyncCollection();
        $syncCol->setFromSimpleXMLElement($xml);

        $controller->deleteEntry($message->folder_id, $message->getId(), $syncCol);
    }
    
    /**
     * test seen flag
     * 
     * @see 0007008: add test for seen flag
     */
    public function testMarkAsRead()
    {
        $controller = $this->_getController($this->_getDevice(Syncroton_Model_Device::TYPE_ANDROID_40));
        
        $message = $this->_createTestMessage();
        
        $controller->updateEntry(null, $message->getId(), new Syncroton_Model_Email(array('read' => 1)));
        
        $message = Felamimail_Controller_Message::getInstance()->get($message->getId());
        $this->assertEquals(array(Zend_Mail_Storage::FLAG_SEEN), $message->flags);
    }
    
    /**
     * test invalid chars
     */
    public function testInvalidBodyChars()
    {
        $device = $this->_getDevice(Syncroton_Model_Device::TYPE_WEBOS);
        
        $controller = $this->_getController($device);
        
        $message = $this->_emailTestClass->messageTestHelper('invalid_body_chars.eml', 'invalidBodyChars');
        
        $syncrotonEmail = $controller->toSyncrotonModel($message, array('mimeSupport' => Syncroton_Command_Sync::MIMESUPPORT_SEND_MIME, 'bodyPreferences' => array(4 => array('type' => 4))));
        
        $syncrotonEmail->subject = "Hallo\x0E";
        
        $imp                   = new DOMImplementation();
        $dtd                   = $imp->createDocumentType('AirSync', "-//AIRSYNC//DTD AirSync//EN", "http://www.microsoft.com/");
        $testDoc               = $imp->createDocument('uri:AirSync', 'Sync', $dtd);
        $testDoc->documentElement->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:Syncroton', 'uri:Syncroton');
        $testDoc->formatOutput = true;
        $testDoc->encoding     = 'utf-8';
        
        $syncrotonEmail->appendXML($testDoc->documentElement, $device);
        
        $xml = $testDoc->saveXML();
        
        $this->assertEquals(preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', $xml), $xml);
        
        self::encodeXml($testDoc);
    }

    /**
     * try to encode XML until we have wbxml tests
     *
     * @param $testDoc
     * @return string returns encoded/decoded xml string
     */
    public static function encodeXml($testDoc)
    {
        $outputStream = fopen("php://temp", 'r+');
        $encoder = new Syncroton_Wbxml_Encoder($outputStream, 'UTF-8', 3);
        $encoder->encode($testDoc);

        rewind($outputStream);
        $decoder = new Syncroton_Wbxml_Decoder($outputStream);
        $xml = $decoder->decode();

        return $xml->saveXML();
    }
    
    /**
     * validate fetching email by filereference(hashid-partid)
     */
    public function testToSyncrotonModel()
    {
        $controller = $this->_getController($this->_getDevice(Syncroton_Model_Device::TYPE_WEBOS));
        
        $message = $this->_createTestMessage();
        $message->flags = array(
            Zend_Mail_Storage::FLAG_SEEN, 
            Zend_Mail_Storage::FLAG_ANSWERED
        );
        
        $syncrotonEmail = $controller->toSyncrotonModel($message, array('mimeSupport' => Syncroton_Command_Sync::MIMESUPPORT_SEND_MIME, 'bodyPreferences' => array(4 => array('type' => 4))));
        
        $this->assertEquals('[gentoo-dev] Automated Package Removal and Addition Tracker, for the week ending 2009-04-12 23h59 UTC', $syncrotonEmail->subject);
        // size of the body
        $this->assertEquals(9661, $syncrotonEmail->body->estimatedDataSize);
        // size of the attachment
        $this->assertEquals(2787, $syncrotonEmail->attachments[0]->estimatedDataSize);
        $this->assertEquals(Syncroton_Model_Email::LASTVERB_REPLYTOSENDER, $syncrotonEmail->lastVerbExecuted, 'reply flag missing');
    }
    
    /**
     * validate fetching email by filereference(hashid-partid)
     */
    public function testToSyncrotonModelTruncated()
    {
        $controller = $this->_getController($this->_getDevice(Syncroton_Model_Device::TYPE_WEBOS));
        
        $message = $this->_createTestMessage();
        
        $syncrotonEmail = $controller->toSyncrotonModel($message, array('mimeSupport' => Syncroton_Command_Sync::MIMESUPPORT_SEND_MIME, 'bodyPreferences' => array(4 => array('type' => 4, 'truncationSize' => 2000))));
        
        #foreach ($syncrotonEmail->body as $key => $value) {echo "$key => "; var_dump($value);}
        
        $this->assertEquals(1, $syncrotonEmail->body->truncated);
        $this->assertEquals(2000, strlen($syncrotonEmail->body->data));
    }

    protected function _addSignature(string $signaturePosition = Felamimail_Model_Account::SIGNATURE_BELOW_QUOTE)
    {
        $account = TestServer::getInstance()->getTestEmailAccount();
        $account->signatures = new Tinebase_Record_RecordSet(Felamimail_Model_Signature::class, [[
            'signature' => 'my special signature',
            'is_default' => 1,
            'name' => 'my sig',
            'id' => Tinebase_Record_Abstract::generateUID(), // client also sends some random uuid
            'notes' => []
        ]]);
        $account->signature_position = $signaturePosition;
        Felamimail_Controller_Account::getInstance()->update($account);
    }

    /**
     * testSendEmail
     */
    public function testSendEmail()
    {
        $this->_addSignature();

        $controller = $this->_getController($this->_getDevice(Syncroton_Model_Device::TYPE_ANDROID_40));
        
        $email = file_get_contents(dirname(__FILE__) . '/../../Felamimail/files/text_plain.eml');
        $email = str_replace('gentoo-dev@lists.gentoo.org, webmaster@changchung.org',
            $this->_emailTestClass->getEmailAddress(), $email);
        $email = str_replace('gentoo-dev+bounces-35440-lars=kneschke.de@lists.gentoo.org',
            $this->_emailTestClass->getEmailAddress(), $email);
        
        $controller->sendEmail($email, true);
        
        // check if mail is in INBOX of test account
        $inbox = $this->_emailTestClass->getFolder('INBOX');
        $testHeaderValue = 'text/plain';
        $message = $this->_emailTestClass->searchAndCacheMessage($testHeaderValue, $inbox);
        $this->_createdMessages->addRecord($message);
        $this->assertEquals("Re: [gentoo-dev] `paludis --info' is not like `emerge --info'", $message->subject);

        // check duplicate headers
        $completeMessage = Felamimail_Controller_Message::getInstance()->getCompleteMessage($message);

        self::assertTrue(is_array($completeMessage->headers), 'headers are no array: '
            . print_r($completeMessage->toArray(), true));
        self::assertEquals('1.0', $completeMessage->headers['mime-version']);
        self::assertEquals('text/plain; charset=ISO-8859-1', $completeMessage->headers['content-type']);

        // check signature
        self::assertStringContainsString('my special signature', $completeMessage->body);
    }

    /**
     * testSendEmailAndroid
     * 
     * @see 0008844: Mails sent without content (NIL)
     */
    public function testSendEmailAndroid()
    {
        $controller = $this->_getController($this->_getDevice(Syncroton_Model_Device::TYPE_ANDROID_40));
        
        $email = file_get_contents(dirname(__FILE__) . '/../../Felamimail/files/Android.eml');
        $email = str_replace('p.schuele@metaways.de', $this->_emailTestClass->getEmailAddress(), $email);
        
        $controller->sendEmail($email, true);
        
        // check if mail is in INBOX of test account
        $inbox = $this->_emailTestClass->getFolder('INBOX');
        $testHeaderValue = 'Android.eml';
        $message = $this->_emailTestClass->searchAndCacheMessage($testHeaderValue, $inbox);
        $this->_createdMessages->addRecord($message);
        $this->assertEquals("Test", $message->subject);
        
        // check content
        $completeMessage = Felamimail_Controller_Message::getInstance()->getCompleteMessage($message);
        $this->assertStringContainsString('Test', $completeMessage->body);

        // check if mail is in sent folder
        $emailAccount = TestServer::getInstance()->getTestEmailAccount();
        $sent = $this->_emailTestClass->getFolder($emailAccount->sent_folder);
        $message = $this->_emailTestClass->searchAndCacheMessage($testHeaderValue, $sent);
        $this->_createdMessages->addRecord($message);
        $this->assertEquals("Test", $message->subject);
    }
    
    /**
     * Test whether Base64Decoded Messages can be send or not
     * 
     * @see 0008572: email reply text garbled
     */
    public function testSendBase64DecodedMessage()
    {
        $messageId = '<j4wxaho1t8ggvk5cef7kqc6i.1373048280847@email.android.com>';
        
        $email = '<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE AirSync PUBLIC "-//AIRSYNC//DTD AirSync//EN" "http://www.microsoft.com/">
<SendMail xmlns="uri:ComposeMail">
  <ClientId>SendMail-158383807994574</ClientId>
  <SaveInSentItems/>
  <Mime>Date: Fri, 05 Jul 2013 20:18:00 +0200&#13;
Subject: Fgh&#13;
Message-ID: ' . htmlspecialchars($messageId) . '&#13;
From: l.kneschke@metaways.de&#13;
To: ' . $this->_emailTestClass->getEmailAddress() . '&gt;&#13;
MIME-Version: 1.0&#13;
Content-Type: text/plain; charset=utf-8&#13;
Content-Transfer-Encoding: base64&#13;
&#13;
dGVzdAo=&#13;
</Mime>
</SendMail>';
        
        $stringToCheck = 'test';
        
        $this->_sendMailTestHelper($email, $messageId, $stringToCheck, "Syncroton_Command_SendMail");
    }

    /**
     * Test whether Base64Decoded Messages can be send or not
     *
     * @see 0012320: Too much linebreaks using Nine Client
     */
    public function testSendBase64DecodedMessageNine()
    {
        $messageId = '<j4wxaho1t8ggvk5cef7kqc6i.1373048280847@email.android.com>';

        $email = '<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE AirSync PUBLIC "-//AIRSYNC//DTD AirSync//EN" "http://www.microsoft.com/">
<SendMail xmlns="uri:ComposeMail">
  <ClientId>36d4de51-539a-4dd3-a54f-7891e5bf053a-1</ClientId>
  <SaveInSentItems/>
  <Mime>Date: Thu, 01 Dec 2016 14:30:32 +0100&#13;
Subject: Test Mail&#13;
Message-ID: ' . htmlspecialchars($messageId) . '&#13;
From: l.kneschke@metaways.de&#13;
To: ' . $this->_emailTestClass->getEmailAddress() . '&gt;&#13;
MIME-Version: 1.0&#13;
Content-Type: multipart/alternative; boundary=--_com.ninefolders.hd3.email_118908611723655_alt&#13;
&#13;
----_com.ninefolders.hd3.email_118908611723655_alt&#13;
Content-Type: text/plain; charset=utf-8&#13;
Content-Transfer-Encoding: base64&#13;
&#13;
SGksClBsZWFzZSBUaW5lMjAgYW5zd2VyIG1lLgpCZXN0CgoKCg==&#13;
----_com.ninefolders.hd3.email_118908611723655_alt&#13;
Content-Type: text/html; charset=utf-8&#13;
Content-Transfer-Encoding: base64&#13;
&#13;
PGRpdiBzdHlsZT0iZm9udC1mYW1pbHk6SGVsdmV0aWNhLCBBcmlhbCwgc2Fucy1zZXJpZjsgZm9u&#13;
dC1zaXplOjEyLjBwdDsgbGluZS1oZWlnaHQ6MS4zOyBjb2xvcjojMDAwMDAwIj5IaSw8YnI+UGxl&#13;
YXNlIFRpbmUyMCBhbnN3ZXIgbWUuPGJyPkJlc3Q8YnI+PGJyPjxkaXYgaWQ9InNpZ25hdHVyZS14&#13;
IiBzdHlsZT0iLXdlYmtpdC11c2VyLXNlbGVjdDpub25lOyBmb250LWZhbWlseTpIZWx2ZXRpY2Es&#13;
IEFyaWFsLCBzYW5zLXNlcmlmOyBmb250LXNpemU6MTIuMHB0OyBjb2xvcjojMDAwMDAwIiBjbGFz&#13;
cyA9ICJzaWduYXR1cmVfZWRpdG9yIj48ZGl2Pjxicj48L2Rpdj48L2Rpdj48L2Rpdj4gPGJyIHR5&#13;
cGU9J2F0dHJpYnV0aW9uJz4=&#13;
----_com.ninefolders.hd3.email_118908611723655_alt--&#13;
</Mime>
</SendMail>';

        $stringToCheck = 'Please Tine20 answer me.';

        $this->_sendMailTestHelper($email, $messageId, $stringToCheck, "Syncroton_Command_SendMail");
    }

    /**
     * Test whether Base64Decoded Messages can be send or not
     *
     * TODO reply?
     * @see TODO add mantis
     */
    public function testSendBase64EncodedMessage ()
    {
        $messageId = '<j4wxaho1t8ggvk5cef7kqc6i.1373048280847@email.ipad>';

        $email = '<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE AirSync PUBLIC "-//AIRSYNC//DTD AirSync//EN" "http://www.microsoft.com/">
<SendMail xmlns="uri:ComposeMail">
  <ClientId>C3918F20-DAEA-48CD-963D-56E2CA6EC013</ClientId>
  <SaveInSentItems/>
  <Mime>Content-Type: multipart/signed;&#13;
	boundary=Apple-Mail-3CB7E652-FD9A-4AAF-B60E-B7101AC3752F;&#13;
	protocol="application/pkcs7-signature";&#13;
	micalg=sha1&#13;
Content-Transfer-Encoding: 7bit&#13;
From: l.kneschke@metaways.de&#13;
To: ' . $this->_emailTestClass->getEmailAddress() . '&gt;&#13;
Mime-Version: 1.0 (1.0)&#13;
Subject: Re: testmail&#13;
Message-ID: ' . htmlspecialchars($messageId) . '&#13;
Date: Thu, 12 Jan 2017 11:07:13 +0100&#13;
&#13;
&#13;
--Apple-Mail-3CB7E652-FD9A-4AAF-B60E-B7101AC3752F&#13;
Content-Type: text/plain;&#13;
	charset=utf-8&#13;
Content-Transfer-Encoding: base64&#13;
&#13;
PGRpdiBzdHlsZT0iZm9udC1mYW1pbHk6SGVsdmV0aWNhLCBBcmlhbCwgc2Fucy1zZXJpZjsgZm9u&#13;
dC1zaXplOjEyLjBwdDsgbGluZS1oZWlnaHQ6MS4zOyBjb2xvcjojMDAwMDAwIj5IaSw8YnI+UGxl&#13;
YXNlIFRpbmUyMCBhbnN3ZXIgbWUuPGJyPkJlc3Q8YnI+PGJyPjxkaXYgaWQ9InNpZ25hdHVyZS14&#13;
IiBzdHlsZT0iLXdlYmtpdC11c2VyLXNlbGVjdDpub25lOyBmb250LWZhbWlseTpIZWx2ZXRpY2Es&#13;
IEFyaWFsLCBzYW5zLXNlcmlmOyBmb250LXNpemU6MTIuMHB0OyBjb2xvcjojMDAwMDAwIiBjbGFz&#13;
cyA9ICJzaWduYXR1cmVfZWRpdG9yIj48ZGl2Pjxicj48L2Rpdj48L2Rpdj48L2Rpdj4gPGJyIHR5&#13;
cGU9J2F0dHJpYnV0aW9uJz4=&#13;
&#13;
--Apple-Mail-3CB7E652-FD9A-4AAF-B60E-B7101AC3752F&#13;
Content-Type: application/pkcs7-signature;&#13;
	name=smime.p7s&#13;
Content-Disposition: attachment;&#13;
	filename=smime.p7s&#13;
Content-Transfer-Encoding: base64&#13;
&#13;
PGRpdiBzdHlsZT0iZm9udC1mYW1pbHk6SGVsdmV0aWNhLCBBcmlhbCwgc2Fucy1zZXJpZjsgZm9u&#13;
dC1zaXplOjEyLjBwdDsgbGluZS1oZWlnaHQ6MS4zOyBjb2xvcjojMDAwMDAwIj5IaSw8YnI+UGxl&#13;
YXNlIFRpbmUyMCBhbnN3ZXIgbWUuPGJyPkJlc3Q8YnI+PGJyPjxkaXYgaWQ9InNpZ25hdHVyZS14&#13;
IiBzdHlsZT0iLXdlYmtpdC11c2VyLXNlbGVjdDpub25lOyBmb250LWZhbWlseTpIZWx2ZXRpY2Es&#13;
IEFyaWFsLCBzYW5zLXNlcmlmOyBmb250LXNpemU6MTIuMHB0OyBjb2xvcjojMDAwMDAwIiBjbGFz&#13;
cyA9ICJzaWduYXR1cmVfZWRpdG9yIj48ZGl2Pjxicj48L2Rpdj48L2Rpdj48L2Rpdj4gPGJyIHR5&#13;
cGU9J2F0dHJpYnV0aW9uJz4=&#13;
--Apple-Mail-3CB7E652-FD9A-4AAF-B60E-B7101AC3752F--&#13;
</Mime>
</SendMail>';

        $stringToCheck = 'Please Tine20 answer me.';

        $this->_sendMailTestHelper($email, $messageId, $stringToCheck, "Syncroton_Command_SendMail", Syncroton_Model_Device::TYPE_IPHONE);
    }

    /**
     * @see 0011556: sending mails to multiple recipients fails
     */
    public function testSendMessageToMultipleRecipients()
    {
        $messageId = '<j5wxaho1t8ggvk5cef7kqc6i.1373048280847@email.android.com>';
        $email = $this->_createSendMailPayload($messageId,
            $this->_emailTestClass->getEmailAddress() . ', ' . $this->_emailTestClass->getEmailAddress(),
            null,
            'text/plain');
        $stringToCheck = 'test';

        $this->_sendMailTestHelper($email, $messageId, $stringToCheck, "Syncroton_Command_SendMail");
    }

    protected function _createSendMailPayload($messageId, $to = null, $content = null, $contentType = 'text/html')
    {
        if (! $to) {
            $to = $this->_emailTestClass->getEmailAddress();
        }

        if (! $content) {
            $content = 'dGVzdAo=&#13;';
        }

        return '<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE AirSync PUBLIC "-//AIRSYNC//DTD AirSync//EN" "http://www.microsoft.com/">
<SendMail xmlns="uri:ComposeMail">
  <ClientId>SendMail-158383807994574</ClientId>
  <SaveInSentItems/>
  <Mime>Date: Fri, 05 Jul 2013 20:18:00 +0200&#13;
Subject: Fgh&#13;
Message-ID: ' . htmlspecialchars($messageId) . '&#13;
From: l.kneschke@metaways.de&#13;
To: ' . $to . '&gt;&#13;
MIME-Version: 1.0&#13;
Content-Type: ' . $contentType . '; charset=utf-8&#13;
Content-Transfer-Encoding: base64&#13;
&#13;
' . $content . '
</Mime>
</SendMail>';
    }

    /**
     * testCalendarInvitation (should not be sent)
     * 
     * @see 0007568: do not send iMIP-messages via ActiveSync
     * 
     * @group longrunning
     */
    public function testCalendarInvitation()
    {
        $controller = $this->_getController($this->_getDevice(Syncroton_Model_Device::TYPE_ANDROID_40));
        
        $email = file_get_contents(dirname(__FILE__) . '/../../Felamimail/files/iOSInvitation.eml');
        $email = str_replace('unittest@tine20.org', $this->_emailTestClass->getEmailAddress(), $email);
        $stream = fopen('data://text/plain;base64,' . base64_encode($email), 'r');
        
        $controller->sendEmail($email, true);
        
        $inbox = $this->_emailTestClass->getFolder('INBOX');
        $testHeaderValue = 'iOSInvitation.eml';
        $message = $this->_emailTestClass->searchAndCacheMessage($testHeaderValue, $inbox, FALSE);
        
        $this->assertTrue(empty($message), 'message found: ' . var_export($message, TRUE));
    }
    
    /**
     * forward email test
     * 
     * @see 0007328: Answered flags were not synced by activesync
     * @see 0007456: add mail body on Forward via ActiveSync
     */
    public function testForwardEmail()
    {
        $controller = $this->_getController($this->_getDevice(Syncroton_Model_Device::TYPE_ANDROID_40));
        
        $originalMessage = $this->_createTestMessage();
        
        $email = file_get_contents(dirname(__FILE__) . '/../../Felamimail/files/text_plain.eml');
        $email = str_replace('gentoo-dev@lists.gentoo.org, webmaster@changchung.org', $this->_emailTestClass->getEmailAddress(), $email);
        $email = str_replace('gentoo-dev+bounces-35440-lars=kneschke.de@lists.gentoo.org', $this->_emailTestClass->getEmailAddress(), $email);
        
        $controller->forwardEmail(array('collectionId' => 'foobar', 'itemId' => $originalMessage->getId()), $email, true, false);
        
        // check if mail is in INBOX of test account
        $inbox = $this->_emailTestClass->getFolder('INBOX');
        $testHeaderValue = 'text/plain';
        $message = $this->_emailTestClass->searchAndCacheMessage($testHeaderValue, $inbox);
        $this->_createdMessages->addRecord($message);
        
        $this->assertEquals("Re: [gentoo-dev] `paludis --info' is not like `emerge --info'", $message->subject);
        $this->assertEquals(1, $message->has_attachment, 'attachment failure');
        
        // check duplicate headers
        $completeMessage = Felamimail_Controller_Message::getInstance()->getCompleteMessage($message);
        $this->assertEquals(1, count((array)$completeMessage->headers['mime-version']));
        $this->assertEquals(1, count((array)$completeMessage->headers['content-type']));
        
        // check forward flag
        $originalMessage = Felamimail_Controller_Message::getInstance()->get($originalMessage->getId());
        $this->assertTrue(in_array(Zend_Mail_Storage::FLAG_PASSED, $originalMessage->flags), 'forward flag missing in original message: ' . print_r($originalMessage->toArray(), TRUE));
        
        // check body
        $this->assertStringContainsString("The attached list notes all of the packages that were added or removed", $completeMessage->body);
    }
    
    /**
     * reply email test
     * 
     * @see 0007512: SmartReply with HTML message fails
     * @see 0009390: linebreaks missing when replying or forwarding mail
     */
    public function testReplyEmailWithSignature()
    {
        $this->_addSignature(Felamimail_Model_Account::SIGNATURE_ABOVE_QUOTE);

        $controller = $this->_getController($this->_getDevice(Syncroton_Model_Device::TYPE_ANDROID_40));
        $originalMessage = $this->_createTestMessage();
        
        $email = file_get_contents(dirname(__FILE__) . '/../../Felamimail/files/text_html.eml');
        $email = str_replace('gentoo-dev@lists.gentoo.org, webmaster@changchung.org', $this->_emailTestClass->getEmailAddress(), $email);
        $email = str_replace('gentoo-dev+bounces-35440-lars=kneschke.de@lists.gentoo.org', $this->_emailTestClass->getEmailAddress(), $email);
        
        $controller->replyEmail($originalMessage->getId(), $email, FALSE, FALSE);
        
        $inbox = $this->_emailTestClass->getFolder('INBOX');
        $testHeaderValue = 'text_html.eml';
        $message = $this->_emailTestClass->searchAndCacheMessage($testHeaderValue, $inbox);
        $this->_createdMessages->addRecord($message);
        
        $this->assertEquals("Re: [gentoo-dev] `paludis --info' is not like `emerge --info'", $message->subject);
        $completeMessage = Felamimail_Controller_Message::getInstance()->getCompleteMessage($message);
        $this->assertStringContainsString('The attached list notes all of the packages that were added or removed<br />from the tree, for the week ending 2009-04-12 23h59 UTC.<br />', $completeMessage->body,
            'reply body has not been appended correctly');

        // check signature
        self::assertStringContainsString('<br />−−<br />my special signature', $completeMessage->body, 'body: ' . $completeMessage->body);
    }

    public function testReplyEmailWithSignatureAndroidAbove()
    {
        $quoteHtml = '<div class="gmail_extra"><br><div class="gmail_quote">Am 27.12.2022 15:16 schrieb Philipp Schüle &lt;p.schuele@metaways.de&gt;:<br type="attribution"><blockquote class="quote">CITE</blockquote>';
        $this->_checkSignatureQuote($quoteHtml, Syncroton_Model_Device::TYPE_IPHONE);
    }

    protected function _checkSignatureQuote($quoteHtml, $device = Syncroton_Model_Device::TYPE_ANDROID, $position = Felamimail_Model_Account::SIGNATURE_ABOVE_QUOTE)
    {
        $this->_addSignature($position);
        $messageId = '<' . Tinebase_Record_Abstract::generateUID(30) . '@email.com>';
        $content = str_replace("\n", "&#13;\n", base64_encode('
        <div>MAILBODY</div>
        ' . $quoteHtml . '
        '));
        $email = $this->_createSendMailPayload($messageId, null, $content);
        if ($position === Felamimail_Model_Account::SIGNATURE_ABOVE_QUOTE) {
            $stringToCheck = '<div>MAILBODY</div>
        <br />−−<br />my special signature';
        } else {
            $stringToCheck = '>CITE</blockquote>
        <br />−−<br />my special signature<br /><br />';
        }

        $this->_sendMailTestHelper($email, $messageId, $stringToCheck, "Syncroton_Command_SendMail", $device);
    }

    public function testReplyEmailWithSignatureAndroidBelow()
    {
        $quoteHtml = '<div class="gmail_extra"><br><div class="gmail_quote">Am 27.12.2022 15:16 schrieb Philipp Schüle &lt;p.schuele@metaways.de&gt;:<br type="attribution"><blockquote class="quote">CITE</blockquote>';
        $this->_checkSignatureQuote($quoteHtml, Syncroton_Model_Device::TYPE_IPHONE, Felamimail_Model_Account::SIGNATURE_BELOW_QUOTE);
    }

    public function testReplyEmailWithSignatureIOSAbove()
    {
        $quoteHtml = '<div dir="ltr"><br>   <blockquote type="cite">CITE</blockquote>';
        $this->_checkSignatureQuote($quoteHtml, Syncroton_Model_Device::TYPE_IPHONE);
    }

    /**
     * testReplyEmailNexus
     *
     * @see 0008572: email reply text garbled
     *
     * @group longrunning
     */
    protected function _testReplyEmailOutlook($testFolder)
    {
        $account = TestServer::getInstance()->getTestEmailAccount();
        $subject = 'test send outlook ' . Tinebase_Record_Abstract::generateUID();
        $message = new Felamimail_Model_Message(array(
            'account_id'    => $account->getId(),
            'subject'       => $subject,
            'to'            => $this->_emailTestClass->getEmailAddress(),
            'body'          => 'aaaaaä <br>',
            'headers' => array('X-Tine20TestMessage' => 'jsontest'),
        ));
        //sen message first
        Felamimail_Controller_Message_Send::getInstance()->sendMessage($message);
        $inbox = $this->_emailTestClass->getFolder('INBOX');
        $message = $this->_emailTestClass->searchAndCacheMessage('jsontest', $inbox);
        $this->_createdMessages->addRecord($message);
        $mailAsString = Felamimail_Controller_Message::getInstance()->getMessageRawContent($message);

        //copy message to non-system folder
        Felamimail_Controller_Message::getInstance()->appendMessage($testFolder, $mailAsString);
        $originalMessage = $this->_emailTestClass->searchAndCacheMessage('jsontest', $testFolder);
        $this->_createdMessages->addRecord($originalMessage);
        
        $xml = '<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE AirSync PUBLIC "-//AIRSYNC//DTD AirSync//EN" "http://www.microsoft.com/">
<SendMail xmlns="uri:ComposeMail">
  <ClientId>{D55BCFD0-0039-46B6-A956-4EE537BA31C2}</ClientId>
  <SaveInSentItems/>
  <Mime>From: l.kneschke@metaways.de&#13;
To: ' . $this->_emailTestClass->getEmailAddress() . '&gt;&#13;
References: ' . htmlentities($originalMessage->message_id) .'
In-Reply-To: ' . htmlentities($originalMessage->message_id) . '&#13;
Subject: Re: ' . $subject . '&#13;
Date: Wed, 14 Jun 2023 09:49:16 +0200&#13;
Message-ID: &lt;hw6umldu85v6efjai6i9vqci.1373008455202@email.android.com&gt;&#13;
MIME-Version: 1.0&#13;
X-Tine20TestMessage: smartreply.eml&#13;
Content-Type: text/plain; charset=utf-8&#13;
Content-Transfer-Encoding: base64&#13;
&#13;
TW9pbiEKCk1hbCB3YXMgbWl0IMOWIQoKTGFycwoKUGhpbGlwcCBTY2jDvGxlIDxwLnNjaHVlbGVA&#13;
bWV0YXdheXMuZGU+IHNjaHJpZWI6Cgo=&#13;
</Mime>
</SendMail>';
        $messageId = '<hw6umldu85v6efjai6i9vqci.1373008455202@email.android.com>';
        $stringToCheck = 'Mal was mit Ö!';

        $completeMessage = $this->_sendMailTestHelper($xml,
            $messageId,
            $stringToCheck,
            "Syncroton_Command_SendMail",
            Syncroton_Model_Device::TYPE_ANDROID_40,
            $testFolder['globalname']);
        $this->assertStringContainsString('Re: ' . $subject, $completeMessage->subject);
    }


    /**
     * testReplyEmailNexus
     * 
     * @see 0008572: email reply text garbled
     * 
     * @group longrunning
     */
    protected function _testReplyEmailNexus1($testFolder)
    {
        $originalMessage = $this->_createTestMessage('multipart_mixed.eml', 'multipart/mixed', $testFolder);
        
        $xml = '<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE AirSync PUBLIC "-//AIRSYNC//DTD AirSync//EN" "http://www.microsoft.com/">
<SmartReply xmlns="uri:ComposeMail">
  <ClientId>SendMail-78543534540370</ClientId>
  <SaveInSentItems/>
  <Source>
    <ItemId>' . $originalMessage->getId() . '</ItemId>
    <FolderId>' . $originalMessage->folder_id .  '</FolderId>
  </Source>
  <Mime>Date: Fri, 05 Jul 2013 09:14:15 +0200&#13;
Subject: Re: email test&#13;
Message-ID: &lt;hw6umldu85v6efjai6i9vqci.1373008455202@email.android.com&gt;&#13;
From: l.kneschke@metaways.de&#13;
To: ' . $this->_emailTestClass->getEmailAddress() . '&gt;&#13;
MIME-Version: 1.0&#13;
X-Tine20TestMessage: smartreply.eml&#13;
Content-Type: text/plain; charset=utf-8&#13;
Content-Transfer-Encoding: base64&#13;
&#13;
TW9pbiEKCk1hbCB3YXMgbWl0IMOWIQoKTGFycwoKUGhpbGlwcCBTY2jDvGxlIDxwLnNjaHVlbGVA&#13;
bWV0YXdheXMuZGU+IHNjaHJpZWI6Cgo=&#13;
</Mime>
</SmartReply>';
        $messageId = '<hw6umldu85v6efjai6i9vqci.1373008455202@email.android.com>';
        $stringToCheck = 'Mal was mit Ö!';
        
        $this->_sendMailTestHelper($xml, 
            $messageId, 
            $stringToCheck, 
            "Syncroton_Command_SmartReply", 
            Syncroton_Model_Device::TYPE_ANDROID_40,
            $testFolder['globalname']);
    }
    
    /**
     * testReplyEmailNexus
     * 
     * @see 0008572: email reply text garbled
     */
    public function testReplyEmailNexus2()
    {
        $originalMessage = $this->_createTestMessage();
        
        $xml = '<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE AirSync PUBLIC "-//AIRSYNC//DTD AirSync//EN" "http://www.microsoft.com/">
<SmartReply xmlns="uri:ComposeMail">
  <ClientId>SendMail-90061070551109</ClientId>
  <SaveInSentItems/>
  <Source>
    <ItemId>' . $originalMessage->getId() . '</ItemId>
    <FolderId>' . $originalMessage->folder_id .  '</FolderId>
  </Source>
  <Mime>Date: Fri, 05 Jul 2013 13:14:19 +0200&#13;
Subject: Re: email test&#13;
Message-ID: &lt;xs9f5842m44v6exce8v8swox.1373022859201@email.android.com&gt;&#13;
From: l.kneschke@metaways.de&#13;
To: ' . $this->_emailTestClass->getEmailAddress() . '&#13;
MIME-Version: 1.0&#13;
Content-Type: text/plain; charset=utf-8&#13;
Content-Transfer-Encoding: base64&#13;
&#13;
TGFycyBsw7ZzY2h0IG5peC4uLgoKV2lya2xpY2ghCgpQaGlsaXBwIFNjaMO8bGUgPHAuc2NodWVs&#13;
ZUBtZXRhd2F5cy5kZT4gc2NocmllYjoKCg==&#13;
</Mime>
</SmartReply>';
        $messageId = '<xs9f5842m44v6exce8v8swox.1373022859201@email.android.com>';
        
        $stringToCheck = 'Lars löscht nix...';
        
        $this->_sendMailTestHelper($xml, $messageId, $stringToCheck);
    }
    
    /**
     * _sendMailTestHelper
     * 
     * @param string $xml
     * @param string $messageId
     * @param string $stringToCheck
     * @param string $command
     * @param string $device
     * @return Felamimail_Model_Message
     */
    protected function _sendMailTestHelper($xml,
                                           $messageId,
                                           $stringToCheck,
                                           $command = "Syncroton_Command_SmartReply",
                                           $device = Syncroton_Model_Device::TYPE_ANDROID_40, 
                                           $targetFolderName = 'INBOX')
    {
        $doc = new DOMDocument();
        $doc->loadXML($xml);
        $device = $this->_getDevice($device);
        $sync = new $command($doc, $device, $device->policykey);
        
        $sync->handle();
        $sync->getResponse();

        $account = TestServer::getInstance()->getTestEmailAccount();

        $targetFolder = $this->_emailTestClass->getFolder($targetFolderName);
        $message = $this->_emailTestClass->searchAndCacheMessage($messageId, $targetFolder, TRUE, 'Message-ID');
        $this->_createdMessages->addRecord($message);
        
        $completeMessage = Felamimail_Controller_Message::getInstance()->getCompleteMessage($message);

        // echo $completeMessage->body;
        $this->assertStringContainsString($stringToCheck, $completeMessage->body);

        $emailAccount = TestServer::getInstance()->getTestEmailAccount();

        if ($emailAccount->message_sent_copy_behavior === Felamimail_Model_Account::MESSAGE_COPY_FOLDER_SOURCE) {
            $this->assertEquals($message->folder_id, $targetFolder->getId());
        }
        if ($emailAccount->message_sent_copy_behavior === Felamimail_Model_Account::MESSAGE_COPY_FOLDER_SENT) {
            $sentFolder = $this->_emailTestClass->getFolder($emailAccount->sent_folder);
            $message = $this->_emailTestClass->searchAndCacheMessage($messageId, $sentFolder, TRUE, 'Message-ID');
            $this->assertEquals($message->folder_id, $sentFolder->getId());
        }
        return $completeMessage;
    }
    
    /**
     * testForwardEmailiPhone
     * 
     * @see 0008572: email reply text garbled
     */
    public function testForwardEmailiPhone()
    {
        $originalMessage = $this->_createTestMessage();
        
        $xml = '<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE AirSync PUBLIC "-//AIRSYNC//DTD AirSync//EN" "http://www.microsoft.com/">
<SmartForward xmlns="uri:ComposeMail">
  <ClientId>1F7C3F2D-B920-404F-97FE-27FE721A9E08</ClientId>
  <SaveInSentItems/>
  <ReplaceMime/>
  <Source>
    <FolderId>' . $originalMessage->folder_id .  '</FolderId>
    <ItemId>' . $originalMessage->getId() . '</ItemId>
  </Source>
  <Mime>Content-Type: multipart/alternative;&#13;
        boundary=Apple-Mail-31383BDF-6B42-495A-89DE-A608A255C644&#13;
Content-Transfer-Encoding: 7bit&#13;
Subject: Fwd: AW: Termin&#13;
From: l.kneschke@metaways.de&#13;
Message-Id: &lt;1F7C3F2D-B920-404F-97FE-27FE721A9E08@tine20.org&gt;&#13;
Date: Wed, 7 Aug 2013 15:27:46 +0200&#13;
To: ' . $this->_emailTestClass->getEmailAddress() . '&#13;
Mime-Version: 1.0 (1.0)&#13;
&#13;
&#13;
--Apple-Mail-31383BDF-6B42-495A-89DE-A608A255C644&#13;
Content-Type: text/plain;&#13;
        charset=utf-8&#13;
Content-Transfer-Encoding: base64&#13;
&#13;
TGFycyBsw7ZzY2h0IG5peC4uLgoKV2lya2xpY2ghCgpQaGlsaXBwIFNjaMO8bGUgPHAuc2NodWVs&#13;
ZUBtZXRhd2F5cy5kZT4gc2NocmllYjoKCg==&#13;
&#13;
--Apple-Mail-31383BDF-6B42-495A-89DE-A608A255C644&#13;
Content-Type: text/html;&#13;
        charset=utf-8&#13;
Content-Transfer-Encoding: base64&#13;
&#13;
TGFycyBsw7ZzY2h0IG5peC4uLgoKV2lya2xpY2ghCgpQaGlsaXBwIFNjaMO8bGUgPHAuc2NodWVs&#13;
ZUBtZXRhd2F5cy5kZT4gc2NocmllYjoKCg==&#13;
--Apple-Mail-31383BDF-6B42-495A-89DE-A608A255C644--&#13;
</Mime>
</SmartForward>';
        $messageId = '<1F7C3F2D-B920-404F-97FE-27FE721A9E08@tine20.org>';
        
        $stringToCheck = 'Lars löscht nix...';

        $this->_sendMailTestHelper($xml, $messageId, $stringToCheck, 'Syncroton_Command_SmartForward', Syncroton_Model_Device::TYPE_IPHONE);
    }

    /**
     * test Forward SaveInSentItems Source Mode
     *
     */
    public function testForwardSaveInSentItemsSourceMode()
    {
        // check if mail is in sent folder
        $emailAccount = TestServer::getInstance()->getTestEmailAccount();
        $emailAccount->message_sent_copy_behavior = Felamimail_Model_Account::MESSAGE_COPY_FOLDER_SOURCE;
        $emailAccount = Felamimail_Controller_Account::getInstance()->update($emailAccount);
        
        $this->testForwardEmailiPhone();
        
        $emailAccount->message_sent_copy_behavior = Felamimail_Model_Account::MESSAGE_COPY_FOLDER_SENT;
        Felamimail_Controller_Account::getInstance()->update($emailAccount);
    }
    
    /**
     * test Reply SaveInSentItems Source Mode
     *
     */
    public function testReplySaveInSentItemsSourceModeNexus()
    {
        // check if mail is in source folder
        $emailAccount = TestServer::getInstance()->getTestEmailAccount();
        $emailAccount->message_sent_copy_behavior = Felamimail_Model_Account::MESSAGE_COPY_FOLDER_SOURCE;
        $emailAccount = Felamimail_Controller_Account::getInstance()->update($emailAccount);
        $account = TestServer::getInstance()->getTestEmailAccount();
        $folderName = 'outlook';
        $this->_createdFolders[] = $folderName;
        $folder = Felamimail_Controller_Folder::getInstance()->create($account->getId(), $folderName, '');
        Felamimail_Controller_Cache_Folder::getInstance()->update($account['id']);

        $this->_testReplyEmailNexus1($folder);

        $emailAccount->message_sent_copy_behavior = Felamimail_Model_Account::MESSAGE_COPY_FOLDER_SENT;
        Felamimail_Controller_Account::getInstance()->update($emailAccount);
    }

    /**
     * test Reply SaveInSentItems Source Mode
     *
     */
    public function testReplySaveInSentItemsSourceModeOutlook()
    {
        // check if mail is in sent folder
        $emailAccount = TestServer::getInstance()->getTestEmailAccount();
        $emailAccount->message_sent_copy_behavior = Felamimail_Model_Account::MESSAGE_COPY_FOLDER_SOURCE;
        $emailAccount = Felamimail_Controller_Account::getInstance()->update($emailAccount);
        $account = TestServer::getInstance()->getTestEmailAccount();
        $this->_createdFolders[] = 'outlook';
        $testFolder = Felamimail_Controller_Folder::getInstance()->create($account->getId(), 'outlook', '');

        $this->_testReplyEmailOutlook($testFolder);

        $emailAccount->message_sent_copy_behavior = Felamimail_Model_Account::MESSAGE_COPY_FOLDER_SENT;
        Felamimail_Controller_Account::getInstance()->update($emailAccount);
    }

    /**
     * validate getAllFolders
     * 
     * @see 0007206: ActiveSync doesn't show all folder tree until it's fully viewed in web-interface
     */
    public function testGetAllFolders()
    {
        // create a subfolder of INBOX
        $emailAccount = TestServer::getInstance()->getTestEmailAccount();
        try {
            $subfolder = Felamimail_Controller_Folder::getInstance()->create($emailAccount->getId(), 'sub', 'INBOX');
            $this->_createdFolders[] = $subfolder->globalname;
        } catch (Zend_Mail_Storage_Exception $zmse) {
            if (Tinebase_Core::isLogLevel(Zend_Log::WARN)) Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . " " . $zmse);
        }
        
        $controller = $this->_getController($this->_getDevice(Syncroton_Model_Device::TYPE_IPHONE));
        
        $folders = $controller->getAllFolders();
        
        $this->assertGreaterThanOrEqual(5, count($folders));
        $foundFolderTypes = array();
        foreach ($folders as $folder) {
            $foundFolderTypes[] = $folder->type;
        }
        $this->assertContains(Syncroton_Command_FolderSync::FOLDERTYPE_DRAFTS,       $foundFolderTypes, 'Drafts folder missing:' . print_r($foundFolderTypes, TRUE));
        $this->assertContains(Syncroton_Command_FolderSync::FOLDERTYPE_DELETEDITEMS, $foundFolderTypes, 'Trash folder missing:' .  print_r($foundFolderTypes, TRUE));
        $this->assertContains(Syncroton_Command_FolderSync::FOLDERTYPE_SENTMAIL,     $foundFolderTypes, 'Sent folder missing:' .   print_r($foundFolderTypes, TRUE));
        $this->assertContains(Syncroton_Command_FolderSync::FOLDERTYPE_OUTBOX,       $foundFolderTypes, 'Outbox folder missing:' . print_r($foundFolderTypes, TRUE));
        
        $this->assertTrue(array_pop($folders) instanceof Syncroton_Model_Folder);
        
        // look for 'INBOX/sub'
        $inbox = $this->_emailTestClass->getFolder('INBOX');
        $found = FALSE;
        $foundFolders = array();
        foreach ($folders as $folder) {
            $foundFolders[] = $folder->displayName;
            if ($folder->displayName === 'sub' && $folder->parentId === $inbox->getId()) {
                $found = TRUE;
                break;
            }
        }
        
        try {
            Felamimail_Controller_Folder::getInstance()->delete($emailAccount->getId(), 'INBOX/sub');
        } catch (Felamimail_Exception_IMAPFolderNotFound $feifnf) {
            if (Tinebase_Core::isLogLevel(Zend_Log::WARN)) Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . " " . $feifnf);
        }
        $this->assertTrue($found, 'could not find INBOX/sub with getAllFolders(): ' . print_r($foundFolders, TRUE));
    }
    
    /**
     * test if changed folders got returned
     * 
     * @see 0007786: changed email folder names do not sync to device
     * 
     * @todo implement
     */
    public function testGetChangedFolders()
    {
        $this->markTestIncomplete('not yet implemented in controller/felamimail');
        
        $syncrotonFolder = $this->testUpdateFolder();
        
        $controller = Syncroton_Data_Factory::factory($this->_class, $this->_getDevice(Syncroton_Model_Device::TYPE_IPHONE), new Tinebase_DateTime(null, null, 'de_DE'));
        
        $changedFolders = $controller->getChangedFolders(Tinebase_DateTime::now()->subMinute(1), Tinebase_DateTime::now());
        
        //var_dump($changedFolders);
        
        $this->assertEquals(1, count($changedFolders));
        $this->assertArrayHasKey($syncrotonFolder->serverId, $changedFolders);
    }
    
    /**
     * test search for emails
     */
    public function testSearch()
    {
        $controller = $this->_getController($this->_getDevice(Syncroton_Model_Device::TYPE_IPHONE));
        
        $message = $this->_createTestMessage();
        
        $request = new Syncroton_Model_StoreRequest(array(
            'query' => array(
                'and' => array(
                    'freetext'     => 'Removal',
                    'classes'      => array('Email'),
                    'collections'  => array($this->_emailTestClass->getFolder()->getId())
                )
            ),
            'options' => array(
                'mimeSupport' => 0,
                'bodyPreferences' => array(
                    array(
                        'type' => 2,
                        'truncationSize' => 20000
                    )
                ),
                'range' => array(0,9)
            )
        ));
        
        $result = $controller->search($request);
    }
    
    /**
     * return active device
     * 
     * @param string $_deviceType
     * @return ActiveSync_Model_Device
     */
    protected function _getDevice($_deviceType)
    {
        if (isset($this->objects['devices'][$_deviceType])) {
            return $this->objects['devices'][$_deviceType];
        }
        
        $this->objects['devices'][$_deviceType] = Syncroton_Registry::getDeviceBackend()->create(
            ActiveSync_TestCase::getTestDevice($_deviceType)
        );

        return $this->objects['devices'][$_deviceType];
    }
    
    /**
     * get application activesync controller
     * 
     * @param ActiveSync_Model_Device $_device
     * @return Felamimail_Frontend_ActiveSync
     */
    protected function _getController(Syncroton_Model_IDevice $_device): Felamimail_Frontend_ActiveSync
    {
        if ($this->_controller === null) {
            $this->_controller = new $this->_controllerName($_device, new Tinebase_DateTime(null, null, 'de_DE'));
        } 
        
        return $this->_controller;
    }
    
    /**
     * testGetCountOfChanges (inbox folder cache should be updated here by _inspectGetServerEntries fn)
     * 
     * @see 0006232: Emails get only synched, if the user is logged on with an browser
     */
    public function testGetCountOfChanges()
    {
        $controller = $this->_getController($this->_getDevice(Syncroton_Model_Device::TYPE_IPHONE));

        // set inbox timestamp a long time ago (15 mins)
        $inbox = $this->_emailTestClass->getFolder('INBOX');
        $inbox->cache_timestamp = Tinebase_DateTime::now()->subMinute(15);
        $folderBackend = new Felamimail_Backend_Folder();
        $folderBackend->update($inbox);
        
        $numberOfChanges = $controller->getCountOfChanges(
            Syncroton_Registry::getContentStateBackend(), 
            new Syncroton_Model_Folder(array(
                'id'             => Tinebase_Record_Abstract::generateUID(),
                'serverId'       => $inbox->getId(),
                'lastfiltertype' => Syncroton_Command_Sync::FILTER_NOTHING
            )), 
            new Syncroton_Model_SyncState(array(
                'lastsync' => Tinebase_DateTime::now()->subHour(1)
            ))
        );
        
        $inbox = $this->_emailTestClass->getFolder('INBOX');
        
        $this->assertEquals(1, $inbox->cache_timestamp->compare(
            Tinebase_DateTime::now()->subSecond(15)),
            'inbox cache has not been updated: ' . print_r($inbox, TRUE)
        );
    }

    /**
     * testGetCountOfChanges for fake folder (outbox)
     */
    public function testGetCountOfChangesFakeFolder()
    {
        $controller = $this->_getController($this->_getDevice(Syncroton_Model_Device::TYPE_IPHONE));
        
        $numberOfChanges = $controller->getCountOfChanges(
            Syncroton_Registry::getContentStateBackend(), 
            new Syncroton_Model_Folder(array(
                'id'             => Tinebase_Record_Abstract::generateUID(),
                'serverId'       => 'fake-' . Syncroton_Command_FolderSync::FOLDERTYPE_OUTBOX,
                'lastfiltertype' => Syncroton_Command_Sync::FILTER_NOTHING
            )), 
            new Syncroton_Model_SyncState(array(
                'lastsync' => Tinebase_DateTime::now()->subHour(1)
            ))
        );
        
        $this->assertEquals(0, $numberOfChanges);
    }

    /**
     * testSendMailWithoutSubject
     * 
     * @see 0007870: Can't send mail without subject
     */
    public function testSendMailWithoutSubject()
    {
        $controller = $this->_getController($this->_getDevice(Syncroton_Model_Device::TYPE_ANDROID_40));
        
        $email = file_get_contents(dirname(__FILE__) . '/../../Felamimail/files/text_plain.eml');
        $email = str_replace('gentoo-dev@lists.gentoo.org, webmaster@changchung.org', $this->_emailTestClass->getEmailAddress(), $email);
        $email = str_replace('gentoo-dev+bounces-35440-lars=kneschke.de@lists.gentoo.org', $this->_emailTestClass->getEmailAddress(), $email);
        $email = str_replace("Subject: Re: [gentoo-dev] `paludis --info' is not like `emerge --info'\n", '', $email);
        
        $controller->sendEmail($email, true);
        
        // check if mail is in INBOX of test account
        $inbox = $this->_emailTestClass->getFolder('INBOX');
        $testHeaderValue = 'text/plain';
        $message = $this->_emailTestClass->searchAndCacheMessage($testHeaderValue, $inbox);
        $this->_createdMessages->addRecord($message);
        $this->assertTrue(empty($message->subject));
    }

    /**
     * check if recipient addresses are split correctly
     */
    public function testSendMailToRecipientsWithComma()
    {
        $messageId = '<2248dca3-809b-4bb9-8643-2e732c43e639@email.android.com>';
      
        $email = '<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE AirSync PUBLIC "-//AIRSYNC//DTD AirSync//EN" "http://www.microsoft.com/">
<SendMail xmlns="uri:ComposeMail">
  <ClientId>SendMail-519120675237184</ClientId>
  <SaveInSentItems/>
  <Mime>Date: Thu, 01 Nov 2018 13:52:55 +0100&#13;
Subject: =?UTF-8?Q?10_=E2=82=AC_geliehen?=&#13;
Message-ID: ' . htmlspecialchars($messageId) . '&#13;
X-Android-Message-ID: &lt;2248dca3-809b-4bb9-8643-2e732c43e639@email.android.com&gt;&#13;
In-Reply-To: &lt;6c62aeff-b1f7-4d45-a9a7-443b5764be21@email.android.com&gt;&#13;
From: p.schuele@metaways.de&#13;
To: =?ISO-8859-1?Q?Sch=FCle=2C_Philipp?= &lt;' . $this->_emailTestClass->getEmailAddress() . '&gt;, some&#13;
 one &lt;' . $this->_emailTestClass->getEmailAddress() . '&gt;&#13;
Importance: Normal&#13;
X-Priority: 3&#13;
X-MSMail-Priority: Normal&#13;
MIME-Version: 1.0&#13;
Content-Type: text/html; charset=utf-8&#13;
Content-Transfer-Encoding: base64&#13;
&#13;
PGRpdiBkaXI9J2F1dG8nPjxkaXY+PGJyPjxkaXYgY2xhc3M9ImdtYWlsX3F1b3RlIj4tLS0tLS0t&#13;
LS0tIFdlaXRlcmdlbGVpdGV0ZSBOYWNocmljaHQgLS0tLS0tLS0tLTxicj5Wb246IHAuc2NodWVs&#13;
ZUBtZXRhd2F5cy5kZTxicj5EYXR1bTogMzAuMTAuMjAxOCAxMjoxNTxicj5CZXRyZWZmOiAxMCDi&#13;
gqwgZ2VsaWVoZW48YnI+QW46IENocmlzdGlhbiBGZWl0bCAmbHQ7Yy5mZWl0bEBtZXRhd2F5cy5k&#13;
ZSZndDssIlNjaMO8bGUsIFBoaWxpcHAiICZsdDtwLnNjaHVlbGVAbWV0YXdheXMuZGUmZ3Q7PGJy&#13;
PkNjOiA8YnI+PGJyIHR5cGU9ImF0dHJpYnV0aW9uIj48YmxvY2txdW90ZSBjbGFzcz0icXVvdGUi&#13;
IHN0eWxlPSJtYXJnaW46MCAwIDAgLjhleDtib3JkZXItbGVmdDoxcHggI2NjYyBzb2xpZDtwYWRk&#13;
aW5nLWxlZnQ6MWV4Ij48ZGl2IGRpcj0iYXV0byI+PC9kaXY+PC9ibG9ja3F1b3RlPjwvZGl2Pjxi&#13;
cj48L2Rpdj48L2Rpdj4=&#13;
</Mime>
</SendMail>';

        $stringToCheck = 'geliehen';

        $message = $this->_sendMailTestHelper($email, $messageId, $stringToCheck, "Syncroton_Command_SendMail");
        self::assertEquals(1, count($message->to), 'message should have 1 recipient: ' . print_r($message->to, true));
    }

    /**
     * @param string $name
     * @return Felamimail_Model_Folder
     */
    public function testCreateFolder($name = 'syncroTestFolder')
    {
        $controller = $this->_getController($this->_getDevice(Syncroton_Model_Device::TYPE_ANDROID_40));
        $inbox = $this->_emailTestClass->getFolder('INBOX');
        $folder = new Syncroton_Model_Folder([
            'parentId' => $inbox->getId(),
            'displayName' => $name,
        ]);
        $this->_createdFolders[] = 'INBOX.' . $folder->displayName;
        $newFolder = $controller->createFolder($folder);

        $fmailFolder = Felamimail_Controller_Folder::getInstance()->get($newFolder->serverId);
        self::assertEquals($folder->displayName, $fmailFolder->localname);
        return $fmailFolder;
    }

    public function testMoveFolder()
    {
        $folder1 = $this->testCreateFolder();
        $folder2 = $this->testCreateFolder('subfolder');

        $controller = $this->_getController($this->_getDevice(Syncroton_Model_Device::TYPE_ANDROID_40));
        // move folder2 into folder1
        $folder = new Syncroton_Model_Folder([
            'parentId' => $folder1->getId(),
            'serverId' => $folder2->getId(),
            'displayName' => $folder2->localname,
        ]);
        $newGlobalName = 'INBOX.' . $folder1->localname . '.' . $folder2->localname;
        $this->_createdFolders[] = 'INBOX.' . $folder1->localname;
        $newFolder = $controller->updateFolder($folder);

        $fmailFolder = Felamimail_Controller_Folder::getInstance()->get($newFolder->serverId);
        self::assertEquals($folder->displayName, $fmailFolder->localname);
        self::assertEquals($newGlobalName, $fmailFolder->globalname);
    }

    public function testMoveMessage()
    {
        $emailFileHeader = 'multipart/mixed';
        $originalMessage = $this->_emailTestClass->messageTestHelper(
            'multipart_mixed.eml',
            $emailFileHeader
        );

        $controller = $this->_getController($this->_getDevice(Syncroton_Model_Device::TYPE_ANDROID_40));

        // move message to folder
        $folder = $this->testCreateFolder();
        $serverId = $controller->moveItem(null, $originalMessage->getId(), $folder->getId());
        $message = Felamimail_Controller_Message::getInstance()->search(
            Tinebase_Model_Filter_FilterGroup::getFilterForModel(Felamimail_Model_Message::class, [[
                'field' => 'folder_id', 'operator' => 'equals', 'value' => $folder->getId()
        ]]))->getFirstRecord();
        $this->_createdMessages->addRecord($message);

        self::assertEquals($message->getId(), $serverId, 'returned server id should be the cache message id');

        $updatedFolder = Felamimail_Controller_Cache_Folder::getInstance()->getIMAPFolderCounter($folder);
        self::assertEquals(1, $updatedFolder->imap_totalcount, print_r($updatedFolder->toArray(), true));
    }

    public function testMoveFolderToRoot()
    {
        $folder1 = $this->testCreateFolder();

        $controller = $this->_getController($this->_getDevice(Syncroton_Model_Device::TYPE_ANDROID_40));
        // move folder1 to root
        $folder = new Syncroton_Model_Folder([
            'parentId' => '',
            'serverId' => $folder1->getId(),
            'displayName' => $folder1->localname,
        ]);
        $newGlobalName = $folder1->localname;
        $this->_createdFolders = [$folder1->localname];
        $newFolder = $controller->updateFolder($folder);

        $fmailFolder = Felamimail_Controller_Folder::getInstance()->get($newFolder->serverId);
        self::assertEquals($folder->displayName, $fmailFolder->localname);
        self::assertEquals($newGlobalName, $fmailFolder->globalname);

        // try to rename folder via tine afterwards
        $newName = 'abcde';
        $renamed = Felamimail_Controller_Folder::getInstance()->rename($fmailFolder->account_id, $newName, $fmailFolder->globalname);
        $this->_createdFolders = [$newName];
        self::assertEquals($newName, $renamed->globalname);
    }
}
