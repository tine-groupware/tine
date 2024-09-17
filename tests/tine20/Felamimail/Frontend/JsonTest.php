<?php
/**
 * Tine 2.0 - http://www.tine20.org
 *
 * @package     Felamimail
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2009-2024 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */

use Sabre\DAV;

/**
 * Test class for Felamimail_Frontend_Json
 */
class Felamimail_Frontend_JsonTest extends Felamimail_TestCase
{
    use GetProtectedMethodTrait;

    /**
     * paths in the vfs to delete
     *
     * @var array
     */
    protected $_pathsToDelete = array();

    protected function tearDown(): void
    {
        // vfs cleanup
        foreach ($this->_pathsToDelete as $path) {
            try {
                $webdavRoot = new DAV\Tree(new Tinebase_WebDav_Root());
                $webdavRoot->delete($path);
            } catch (Exception $e) {
            }
        }

        parent::tearDown();
    }

    /************************ test functions *********************************/

    /*********************** folder tests ****************************/

    /**
     * test search folders (check order of folders as well)
     */
    public function testSearchFolders()
    {
        $filter = $this->_getFolderFilter();
        $result = $this->_json->searchFolders($filter);

        $this->assertGreaterThan(1, $result['totalcount']);
        $expectedFolders = array('INBOX', $this->_testFolderName, $this->_account->trash_folder, $this->_account->sent_folder);

        $foundCount = 0;
        foreach ($result['results'] as $index => $folder) {
            if (in_array($folder['localname'], $expectedFolders)) {
                $foundCount++;
            }
        }
        $this->assertEquals(count($expectedFolders), $foundCount);
    }

    /**
     * clear test folder
     */
    public function testClearFolder()
    {
        $folderName = $this->_testFolderName;
        $folder = $this->_getFolder($this->_testFolderName);
        $folder = Felamimail_Controller_Folder::getInstance()->emptyFolder($folder->getId());

        $filter = $this->_getMessageFilter($folder->getId());
        $result = $this->_json->searchMessages($filter, '');

        $this->assertEquals(0, $result['totalcount'], 'Found too many messages in folder ' . $this->_testFolderName);
        $this->assertEquals(0, $folder->cache_totalcount);
    }

    /**
     * try to create some folders
     */
    public function testCreateFolders()
    {
        $filter = $this->_getFolderFilter();
        $result = $this->_json->searchFolders($filter);

        $foldernames = array('test' => 'test', 'Schlüssel' => 'Schlüssel', 'test//1' => 'test1', 'test\2' => 'test2');

        foreach ($foldernames as $foldername => $expected) {
            $result = $this->_json->addFolder($foldername, $this->_testFolderName, $this->_account->getId());
            $globalname = $this->_testFolderName . $this->_account->delimiter . $expected;
            $this->_createdFolders[] = $globalname;
            $this->assertEquals($expected, $result['localname']);
            $this->assertEquals($globalname, $result['globalname']);
            $this->assertEquals(Felamimail_Model_Folder::CACHE_STATUS_EMPTY, $result['cache_status']);
        }
    }

    /**
     * test emtpy folder (with subfolder)
     */
    public function testEmptyFolderWithSubfolder()
    {
        $folderName = $this->_testFolderName;
        $folder = $this->_getFolder($this->_testFolderName);
        $this->testCreateFolders();

        $folderArray = $this->_json->emptyFolder($folder->getId());
        $this->assertEquals(0, $folderArray['has_children']);

        $result = $this->_json->updateFolderCache($this->_account->getId(), $this->_testFolderName);
        $this->assertEquals(0, count($result));
    }

    /**
     * testUpdateFolderCache
     */
    public function testUpdateFolderCache()
    {
        $result = $this->_json->updateFolderCache($this->_account->getId(), '');

        // create folders directly on imap server
        $this->_imap->createFolder('test', $this->_testFolderName, $this->_account->delimiter);
        $this->_imap->createFolder('testsub', $this->_testFolderName . $this->_account->delimiter . 'test', $this->_account->delimiter);
        // if something goes wrong, we need to delete these folders in tearDown
        $this->_createdFolders[] = $this->_testFolderName . $this->_account->delimiter . 'test' . $this->_account->delimiter . 'testsub';
        $this->_createdFolders[] = $this->_testFolderName . $this->_account->delimiter . 'test';

        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
            . ' Update cache and check if folder is found');

        $result = $this->_json->updateFolderCache($this->_account->getId(), $this->_testFolderName);
        $testfolder = $result[0];
        $this->assertGreaterThan(0, count($result));
        $this->assertEquals($this->_testFolderName . $this->_account->delimiter . 'test', $testfolder['globalname']);
        $this->assertEquals(TRUE, (bool)$testfolder['has_children'], 'should have children');

        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
            . ' Delete subfolder directly on imap server');

        $this->_imap->removeFolder($this->_testFolderName . $this->_account->delimiter . 'test' . $this->_account->delimiter . 'testsub');
        array_shift($this->_createdFolders);

        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
            . ' Check if has_children got updated and folder is removed from cache');

        $this->_json->updateFolderCache($this->_account->getId(), '');
        $testfolder = $this->_getFolder($this->_testFolderName . $this->_account->delimiter . 'test');
        $this->assertEquals(FALSE, (bool)$testfolder['has_children'], 'should have no children');

        return $testfolder;
    }

    /**
     * testUpdateFolderCacheOfNonexistantFolder
     *
     * @see 0009800: unselectable folder with subfolders disappears
     */
    public function testUpdateFolderCacheOfNonexistantFolder()
    {
        $testfolder = $this->testUpdateFolderCache();

        try {
            $folderName = $this->_testFolderName . $this->_account->delimiter . 'test' . $this->_account->delimiter . 'testsub';
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                . ' Trying to fetch deleted folder ' . $folderName);

            $testfoldersub = Felamimail_Controller_Folder::getInstance()->getByBackendAndGlobalName($this->_account->getId(), $folderName);
            $this->fail('Tinebase_Exception_NotFound expected when looking for folder ' . $folderName);
        } catch (Tinebase_Exception_NotFound $tenf) {
        }

        $this->_imap->removeFolder($this->_testFolderName . $this->_account->delimiter . 'test');
        array_shift($this->_createdFolders);

        // try to update message cache of nonexistant folder
        $removedTestfolder = $this->_json->updateMessageCache($testfolder['id'], 1);
        $this->assertEquals(0, $removedTestfolder['is_selectable'], 'Folder should not be selectable');

        // update cache and check if folder is deleted
        $result = $this->_json->updateFolderCache($this->_account->getId(), $this->_testFolderName);
        $this->assertEquals(0, count($result));
    }

    /*********************** accounts tests **************************/

    /**
     * test search for accounts and check default account from config
     */
    public function testSearchAccounts()
    {
        $system = $this->_getSystemAccount();

        $this->assertTrue(!empty($system), 'no accounts found');
        if (TestServer::getInstance()->getConfig()->mailserver) {
            $this->assertEquals(TestServer::getInstance()->getConfig()->mailserver, $system['host']);
            $this->assertEquals(TestServer::getInstance()->getConfig()->mailserver, $system['sieve_hostname']);
        }
    }

    /**
     * test search accounts and deactive sieve script if end date expired
     */
    public function testSearchAccountsCheckSieveEndDate()
    {
        $vacationData = self::getVacationData($this->_account);
        $vacationData['start_date'] = '2012-04-18';
        $vacationData['end_date'] = '2012-04-20';
        $this->_sieveTestHelper($vacationData);

        // check if script was deactivated
        $results = $this->_json->searchAccounts(array());
        $vacation = Felamimail_Controller_Sieve::getInstance()->getVacation($results['results'][0]['id']);
        $this->assertFalse((bool)$results['results'][0]['sieve_vacation_active']);
        $this->assertFalse((bool)$vacation['enabled']);
    }

    /**
     * get system account
     *
     * @return array
     */
    protected function _getSystemAccount()
    {
        $results = $this->_json->searchAccounts(array());

        $this->assertGreaterThan(0, $results['totalcount']);
        $system = array();
        foreach ($results['results'] as $result) {
            if ($result['name'] == Tinebase_Core::getUser()->accountLoginName . '@' . $this->_mailDomain) {
                $system = $result;
            }
        }

        return $system;
    }

    /**
     * test change / delete of account
     */
    public function testChangeSearchDeleteAccount()
    {
        $system = $this->_getSystemAccount();
        unset($system['id']);
        $system['type'] = Felamimail_Model_Account::TYPE_USER;

        $account = $this->_addSignature($system);

        // update signature
        $updatedSignature = 'my updated signature';
        $account['signatures'][0]['signature'] = $updatedSignature;
        $account = $this->_json->saveAccount($account);
        self::assertEquals($updatedSignature, $account['signature'], 'signature not updated: ' . print_r($account, true));

        // add new signature
        $account['signatures'][] = [
            'signature' => '', // empty sig should be possible
            'is_default' => 0,
            'name' => 'my other sig',
            'id' => Tinebase_Record_Abstract::generateUID(), // client also sends some random uuid
            'notes' => [],
            'account_id' => $account['id'],
        ];
        $account = $this->_json->saveAccount($account);
        self::assertEquals(2, count($account['signatures']));

        $accountRecord = new Felamimail_Model_Account($account, TRUE);
        $accountRecord->resolveCredentials(FALSE);
        if (TestServer::getInstance()->getConfig()->mailserver) {
            $this->assertEquals(TestServer::getInstance()->getConfig()->mailserver, $account['host']);
        }

        $this->_json->changeCredentials($account['id'], $accountRecord->user, 'neuespasswort');
        $account = $this->_json->getAccount($account['id']);

        $accountRecord = new Felamimail_Model_Account($account, TRUE);
        $accountRecord->resolveCredentials(FALSE);
        $this->assertEquals('neuespasswort', $accountRecord->password);

        $this->_json->deleteAccounts($account['id']);
    }

    protected function _addSignature($account, $signature = 'my new cool signature')
    {
        $account['signatures'] = [
            [
                'signature' => $signature,
                'is_default' => 1,
                'name' => 'my sig',
                'id' => Tinebase_Record_Abstract::generateUID(), // client also sends some random uuid
                'notes' => [],
            ]
        ];
        $account = $this->_json->saveAccount($account);
        self::assertTrue(isset($account['signatures']), 'no signatures found in account: ' . print_r($account, true));
        self::assertEquals(1, count($account['signatures']));
        self::assertTrue(isset($account['signature']), 'no signature found in account: ' . print_r($account, true));
        self::assertEquals($signature, $account['signature']);
        return $account;
    }

    /**
     * test add user account with signature
     */
    public function testSignatureInUserAccount()
    {
        $system = $this->_getSystemAccount();
        $this->_addSignature($system);
    }

    public function testApproveAccountMigration()
    {
        $result = $this->_json->approveAccountMigration($this->_account->getId());
        self::assertEquals('success', $result['status'], print_r($result, true));
        $account = $this->_json->getAccount($this->_account->getId());
        self::assertEquals(1, $account['migration_approved']);
    }

    /*********************** message tests ****************************/

    /**
     * test update message cache
     */
    public function testUpdateMessageCache()
    {
        $this->_sendMessage();
        $inbox = $this->_getFolder('INBOX');
        // update message cache and check result
        $result = $this->_json->updateMessageCache($inbox['id'], 30);

        if ($result['cache_status'] == Felamimail_Model_Folder::CACHE_STATUS_COMPLETE) {
            $this->assertEquals($result['imap_totalcount'], $result['cache_totalcount'], 'totalcounts should be equal');
        } else if ($result['cache_status'] == Felamimail_Model_Folder::CACHE_STATUS_INCOMPLETE) {
            $this->assertNotEquals(0, $result['cache_job_actions_est']);
        }
    }

    /**
     * test folder status
     */
    public function testGetFolderStatus()
    {
        $filter = $this->_getFolderFilter();
        $result = $this->_json->searchFolders($filter);
        $this->assertGreaterThan(1, $result['totalcount']);
        $expectedFolders = array('INBOX', $this->_testFolderName, $this->_account->trash_folder, $this->_account->sent_folder);

        foreach ($result['results'] as $folder) {
            $this->_json->updateMessageCache($folder['id'], 30);
        }

        $message = $this->_sendMessage();

        $status = $this->_json->getFolderStatus(array(array('field' => 'account_id', 'operator' => 'equals', 'value' => $this->_account->getId())));
        $this->assertEquals(1, count($status));
        $this->assertEquals($this->_account->sent_folder, $status[0]['localname']);
    }

    /**
     * test folder status of deleted folder
     *
     * @see 0007134: getFolderStatus should ignore non-existent folders
     */
    public function testGetFolderStatusOfDeletedFolder()
    {
        $this->testCreateFolders();
        // remove one of the created folders
        $removedFolder = $this->_createdFolders[0];
        $this->_imap->removeFolder(Felamimail_Model_Folder::encodeFolderName($removedFolder));

        $status = $this->_json->getFolderStatus(array(array('field' => 'account_id', 'operator' => 'equals', 'value' => $this->_account->getId())));
        $this->assertGreaterThan(2, count($status), 'Expected more than 2 folders that need an update: ' . print_r($status, TRUE));
        foreach ($status as $folder) {
            if ($folder['globalname'] == $removedFolder) {
                $this->fail('removed folder should not appear in status array!');
            }
        }
    }

    /**
     * test send message
     */
    public function testSendMessage()
    {
        // set email to unittest@tine20.org
        $contactFilter = new Addressbook_Model_ContactFilter(array(
            array('field' => 'n_family', 'operator' => 'equals', 'value' => 'Clever')
        ));
        $contactIds = Addressbook_Controller_Contact::getInstance()->search($contactFilter, NULL, FALSE, TRUE);
        $this->assertTrue(count($contactIds) > 0, 'sclever not found in addressbook');

        $contact = Addressbook_Controller_Contact::getInstance()->get($contactIds[0]);
        $originalEmail = $contact->email;
        $contact->email = $this->_account->email;

        /* @var $contact Addressbook_Model_Contact */
        $contact = Addressbook_Controller_Contact::getInstance()->update($contact, FALSE);

        // send email
        $messageToSend = $this->_getMessageData('unittestalias@' . $this->_mailDomain);
        $messageToSend['bcc'] = array(Tinebase_Core::getUser()->accountEmailAddress);

        $this->_json->saveMessage($messageToSend);
        $this->_foldersToClear = array('INBOX', $this->_account->sent_folder);

        // check if message is in sent folder
        $message = $this->_searchForMessageBySubject($messageToSend['subject'], $this->_account->sent_folder);
        $this->assertEquals($message['from_email'], $messageToSend['from_email']);
        $this->assertTrue(isset($message['to'][0]));
        
        $this->assertEquals($messageToSend['to'][0], $message['to'][0], 'recipient not found');
        $this->assertEquals($messageToSend['bcc'][0], $message['bcc'][0], 'bcc recipient not found');
        $this->assertEquals($message['subject'], $messageToSend['subject']);

        // reset sclevers original email address
        $contact->email = $originalEmail;
        Addressbook_Controller_Contact::getInstance()->update($contact, FALSE);
    }

    /**
     * @return void
     * @throws Tinebase_Exception_AccessDenied
     */
    public function testCreateSentFolderBySentMessage()
    {
        Felamimail_Controller_Folder::getInstance()->delete($this->_account->getId(), 'Sent');

        $messageToSend = $this->_getMessageData('unittestalias@' . $this->_mailDomain);
        $messageToSend['bcc'] = array(Tinebase_Core::getUser()->accountEmailAddress);
        $messageToSend['sent_copy_folder'] = [];

        $this->_json->saveMessage($messageToSend);

        Felamimail_Controller_Folder::getInstance()->getByBackendAndGlobalName($this->_account->getId(), 'Sent');

    }

    /**
     * test Send Message With Recipient Data
     */
    public function testSendMessageWithRecipientData()
    {
        // build message with wrong line end rfc822 part
        $testEmail = $this->_getEmailAddress();

        $adbJsonFE = new Addressbook_Frontend_Json();
        $result = $adbJsonFE->searchRecipientTokensByEmailArrays([$testEmail]);
        $messageToSend = $this->_getMessageData('unittestalias@' . $this->_mailDomain);
        $messageToSend['to'] =$result['results'];

        $message = $this->_json->saveMessage($messageToSend);

        $this->assertEquals($message['from_email'], $messageToSend['from_email']);
        $this->assertTrue(isset($message['to'][0]));
        $this->assertEquals($message['to'][0], $messageToSend['to'][0], 'recipient not found');
    }

    /**
     * test send message
     */
    public function testSendMessageInvalidMail()
    {
        // send email
        $messageToSend = $this->_getMessageData('unittestalias@' . $this->_mailDomain);
        $messageToSend['note'] = 1;
        $messageToSend['to'] = [
            sprintf(
                '%s <    %s     >',
                Tinebase_Core::getUser()->accountFullName,
                Tinebase_Core::getUser()->accountEmailAddress
            )
        ];
        $messageToSend['bcc'] = array(Tinebase_Core::getUser()->accountEmailAddress);

        $this->_json->saveMessage($messageToSend);
        $this->_foldersToClear = array('INBOX', $this->_account->sent_folder);
    }

    /**
     * test send message
     *
     * @see 0013264: Wrong name in "from:" in sent mail
     */
    public function testSendMessageWithFromName()
    {
        self::markTestSkipped('FIXME: this fails at random - improve/fix it!');

        // send email
        $messageToSend = $this->_getMessageData();
        $messageToSend['from_name'] = 'My Special Name';
        $message = $this->_sendMessage('INBOX', array(), '', 'test', $messageToSend);

        self::assertEquals($messageToSend['from_name'], $message['from_name'], print_r($message, true));
    }

    /**
     * test send message with sub-array in recipient-array
     */
    public function testSendMessageTooManyArrays()
    {
        $messageToSend = $this->_getMessageData();
        $messageToSend['to'] = [$messageToSend['to']];
        $this->_sendMessage('INBOX', array(), '', 'test', $messageToSend);
    }

    /**
     * test send message after moved
     */
    public function testSendMessageAfterMoved()
    {
        $message = $this->_sendMessage();
        $this->_foldersToClear = array('INBOX', $this->_account->sent_folder, $this->_account->drafts_folder);

        // move
        $testFolder = $this->_getFolder($this->_account->drafts_folder);
        $this->_json->moveMessages(array(array(
            'field' => 'id', 'operator' => 'in', 'value' => array($message['id'])
        )), $testFolder->getId());

        $replyMessage = $this->_getReply($message);
        $this->_json->saveMessage($replyMessage);
        $result = $this->_getMessages();
        
        // check if replied message is in inbox
        $message = $this->_searchForMessageBySubject($replyMessage['subject']);
        $this->assertEquals($replyMessage['subject'], $message['subject']);
    }

    /**
     * test mail sanitize
     */
    public function testSanitizeMail()
    {
        $expected = 'info@testest.de';
        $obfuscatedMail = '  info@testest.de  ';

        $reflectionMethod = $this->getProtectedMethod(Felamimail_Model_Message::class, 'sanitizeMailAddress');
        $result = $reflectionMethod->invokeArgs(new Felamimail_Model_Message(), [$obfuscatedMail]);

        $this->assertEquals($expected, $result);
    }

    /**
     * test send message to invalid recipient
     */
    public function testSendMessageToInvalidRecipient($invalidEmail = null, $toField = 'to', $expectedExceptionMessage = 'Recipient address rejected')
    {
        $this->markTestSkipped('FIXME: 0011802: Felamimail_Frontend_JsonTest::testSendMessageToInvalidRecipient fails');

        $messageToSend = $this->_getMessageData($this->_account->email);
        if ($invalidEmail === null) {
            $invalidEmail = 'invaliduser@' . $this->_mailDomain;
        }
        if ($toField !== 'to') {
            $messageToSend['to'] = array(Tinebase_Core::getUser()->accountEmailAddress);
        }
        $messageToSend[$toField] = array($invalidEmail);

        $translation = Tinebase_Translation::getTranslation('Felamimail');

        try {
            $this->_json->saveMessage($messageToSend);
            $this->fail('Tinebase_Exception_SystemGeneric expected');
        } catch (Tinebase_Exception_SystemGeneric $tesg) {
            $this->assertStringContainsString('>: ' . $translation->_($expectedExceptionMessage), $tesg->getMessage(),
                'exception message did not match: ' . $tesg->getMessage());
        }
    }

    /**
     * test send message to invalid recipients (invalid email addresses)
     *
     * @see 0012292: check and show invalid email addresses before sending mail
     */
    public function testSendMessageWithInvalidEmails()
    {
        $this->testSendMessageToInvalidRecipient('memyselfandi.de', 'to', 'Invalid address format');
        $this->testSendMessageToInvalidRecipient('ich bins <mymail@ ' . $this->_mailDomain . '>', 'cc', 'Invalid address format');
        $this->testSendMessageToInvalidRecipient('ich bins nicht <mymail\@' . $this->_mailDomain . '>', 'bcc', 'Invalid address format');
        $this->testSendMessageToInvalidRecipient('my@mail@' . $this->_mailDomain, 'bcc', 'Invalid address format');
    }

    /**
     * send to semicolon separated recipient list
     *
     * @param string $delimiter
     */
    public function testSendMessageWithDelimiterSeparatedEmails($delimiter = ';')
    {
        $message = $this->_getMessageData();

        foreach ([
                    $delimiter,
                    ' ' . $delimiter,
                    $delimiter . ' ',
                    ' ' . $delimiter . ' '
                 ] as $testDelimiter) {
            $message['to'] = [Tinebase_Core::getUser()->accountEmailAddress . $testDelimiter . $this->_personas['jsmith']->accountEmailAddress];
            $this->_sendMessage(
                'INBOX',
                array(),
                '',
                'test',
                $_messageToSend = $message
            );
        }
    }

    /**
     * send to semicolon separated recipient list
     */
    public function testSendMessageWithCommaSeparatedEmails()
    {
        $this->testSendMessageWithDelimiterSeparatedEmails(',');
    }

    /**
     * try to get a message from imap server (with complete body, attachments, etc)
     *
     * @see 0006300: add unique message-id header to new messages (for message-id check)
     * @see 0012436: message-id is not valid because of double brackets
     */
    public function testGetMessage()
    {
        $message = $this->_sendMessage();

        // get complete message
        $message = $this->_json->getMessage($message['id']);

        // check
        $this->assertTrue(isset($message['headers']) && $message['headers']['message-id']);
        $this->assertStringContainsString('@' . $this->_mailDomain, $message['headers']['message-id']);
        $this->assertStringNotContainsString('<<', $message['headers']['message-id']);
        $this->assertStringNotContainsString('>>', $message['headers']['message-id']);
        $this->assertGreaterThan(0, preg_match('/aaaaaä/', $message['body']));

        // delete message on imap server and check if correct exception is thrown when trying to get it
        $this->_imap->selectFolder('INBOX');
        $this->_imap->removeMessage($message['messageuid']);
        Tinebase_Core::getCache()->clean();
        $this->expectException('Felamimail_Exception_IMAPMessageNotFound');
        $message = $this->_json->getMessage($message['id']);
    }

    /**
     * try to get a message as plain/text
     */
    public function testGetPlainTextMessage()
    {
        $accountBackend = new Felamimail_Backend_Account();
        $message = $this->_sendMessage();

        // get complete message
        $this->_account->display_format = Felamimail_Model_Account::DISPLAY_PLAIN;
        $accountBackend->update($this->_account);
        $message = $this->_json->getMessage($message['id']);
        $this->_account->display_format = Felamimail_Model_Account::DISPLAY_HTML;
        $accountBackend->update($this->_account);

        // check
        $this->assertEquals("aaaaaä \n\r\n", $message['body']);
    }

    /**
     * try search for a message with path filter
     */
    public function testSearchMessageWithPathFilter()
    {
        $sentMessage = $this->_sendMessage();
        $filter = array(array(
            'field' => 'path', 'operator' => 'in', 'value' => '/' . $this->_account->getId()
        ));
        $result = $this->_json->searchMessages($filter, '');
        $message = $this->_getMessageFromSearchResult($result, $sentMessage['subject']);
        $this->assertTrue(!empty($message), 'Sent message not found with account path filter');

        $inbox = $this->_getFolder('INBOX');
        $filter = array(array(
            'field' => 'path', 'operator' => 'in', 'value' => '/' . $this->_account->getId() . '/' . $inbox->getId()
        ));
        $result = $this->_json->searchMessages($filter, '');
        $message = $this->_getMessageFromSearchResult($result, $sentMessage['subject']);
        $this->assertTrue(!empty($message), 'Sent message not found with path filter');
        foreach ($result['results'] as $mail) {
            $this->assertEquals($inbox->getId(), $mail['folder_id'], 'message is in wrong folder: ' . print_r($mail, TRUE));
        }
    }

    /**
     * try search for a message with to filter
     */
    public function testSearchMessageWithToFilter()
    {
        $sentMessage = $this->_sendMessage();
        $filter = [
            ['field' => 'to', 'operator' => 'contains', 'value' => Tinebase_Core::getUser()->accountEmailAddress],
            ['field' => 'query', 'operator' => 'contains', 'value' => ''],
        ];
        $result = $this->_json->searchMessages($filter, '');
        $message = $this->_getMessageFromSearchResult($result, $sentMessage['subject']);
        $this->assertTrue(!empty($message), 'Sent message not found with to/contains filter');
    }

    /**
     * try search for a message with globing filter
     */
    public function testSearchMessageWithGlobingFilter()
    {
        //https://toools.cloud/miscellaneous/glob-tester
        $this->_moveMessageToFolder('INBOX', false, $this->_account);
        $this->_moveMessageToFolder('1', false, $this->_account);
        $this->_moveMessageToFolder('1.2', false, $this->_account);
        $this->_moveMessageToFolder('1.2.3', false, $this->_account);
        $this->_json->updateFolderCache($this->_account->getId(), '');
        
        $shareAccount = $this->_createSharedAccount();
        $this->_moveMessageToFolder('1', false, $shareAccount);
        $this->_json->updateFolderCache($shareAccount->getId(), '');
        $systemFolders = ['INBOX', 'Trash', 'Drafts', 'Junk', 'Sent', 'Template'];
        $allFolderPath = Felamimail_Model_MessageFilter::PATH_ALLFOLDERS;
        $this->_assertMessageInFolderByGlobFilter($allFolderPath,    5, array_merge(['1', '1.2', '1.2.3'], $systemFolders));
        $this->_assertMessageInFolderByGlobFilter('/20/**',     3, array_merge(['1', '1.2', '1.2.3'], $systemFolders));
        $this->_assertMessageInFolderByGlobFilter('/*/*',       2, array_merge(['1'], $systemFolders));
        $this->_assertMessageInFolderByGlobFilter('/20/*',      1, array_merge(['1'], $systemFolders));
        $this->_assertMessageInFolderByGlobFilter('/*/1',       2, ['1']);
        $this->_assertMessageInFolderByGlobFilter('/*/1/*',     1, ['1.2']);
        $this->_assertMessageInFolderByGlobFilter('/20/1/*',    1, ['1.2']);
        $this->_assertMessageInFolderByGlobFilter('/20/1/2',    1, ['1.2']);
        $this->_assertMessageInFolderByGlobFilter('/20/1/2/*',  1, ['1.2.3']);
        $this->_assertMessageInFolderByGlobFilter('/20/1/**',   2, ['1.2', '1.2.3']);
        $this->_assertMessageInFolderByGlobFilter('/*/1/**',    2, ['1.2', '1.2.3']);
    }
    
    protected function _assertMessageInFolderByGlobFilter($path, $count, $folderGlobalNames)
    {
        $result = $this->_json->searchMessages([['field' => 'path', 'operator' => 'in', 'value' => $path]], '');
        $this->assertGreaterThanOrEqual($count, $result['totalcount']);
        foreach ($result['results'] as $message) {
            $folder = Felamimail_Controller_Folder::getInstance()->get($message['folder_id']);
            $this->assertContains($folder['globalname'], $folderGlobalNames);
        }
    }

    /**
     * try search for a message with all inboxes and flags filter
     */
    public function testSearchMessageWithAllInboxesFilter()
    {
        $sentMessage = $this->_sendMessage();
        $dateString = Tinebase_DateTime::now()->subDay(2)->toString();
        $filter = array(
            array('field' => 'path', 'operator' => 'in', 'value' => Felamimail_Model_MessageFilter::PATH_ALLINBOXES),
            array('field' => 'flags', 'operator' => 'notin', 'value' => Zend_Mail_Storage::FLAG_FLAGGED),
            array('field' => 'received', 'operator' => 'after', 'value' => $dateString)
        );
        $result = $this->_json->searchMessages($filter, []);
        $filter = array_filter($result['filter'], function($item) {
            return $item['field'] === 'received';
        });
        $this->assertEquals($dateString, $filter[0]['value']);
        $this->assertGreaterThan(0, $result['totalcount']);
        $this->assertEquals($result['totalcount'], count($result['results']));

        $message = $this->_getMessageFromSearchResult($result, $sentMessage['subject']);
        $this->assertTrue(!empty($message), 'Sent message not found with all inboxes filter');
    }

    /**
     * try search for a message with three cache filters to force a foreign relation join with at least 2 tables
     */
    public function testSearchMessageWithThreeCacheFilter()
    {
        $filter = array(
            array('field' => 'flags', 'operator' => 'in', 'value' => Zend_Mail_Storage::FLAG_ANSWERED),
            array('field' => 'to', 'operator' => 'contains', 'value' => 'testDOESNOTEXIST'),
            array('field' => 'subject', 'operator' => 'contains', 'value' => 'testDOESNOTEXIST'),
        );
        $result = $this->_json->searchMessages($filter, '');
        $this->assertEquals(0, $result['totalcount']);
    }

    /**
     * try search for a message with empty path filter
     */
    public function testSearchMessageEmptyPath()
    {
        $this->_sendMessage();

        $filter = array(
            array('field' => 'path', 'operator' => 'equals', 'value' => ''),
        );
        $result = $this->_json->searchMessages($filter, '');

        $accountFilterFound = FALSE;
        foreach ($result['filter'] as $filter) {
            if ($filter['field'] === 'account_id' && empty($filter['value'])) {
                $accountFilterFound = TRUE;
                break;
            }
        }
        $this->assertTrue($accountFilterFound);
    }

    /**
     * try search for a message with only query filter -> should switch to all innbox path filter
     */
    public function testSearchMessageEmptyQueryFilter()
    {
        foreach ([[
            'query' => '',
            'allinboxes' => false,
        ],[
            'query' => 'someemail@bla.com',
            'allinboxes' => false,
        ]] as $filterTest) {
            $filter = [[
                'field' => 'query',
                'operator' => 'contains',
                'value' => $filterTest['query'],
                'id' => 'quickFilter',
            ]];
            $result = $this->_json->searchMessages($filter, '');

            $allinboxesFilterFound = false;
            foreach ($result['filter'] as $filter) {
                if (isset($filter['field']) && $filter['field'] === 'path' && $filter['value'] === '/*/INBOX') {
                    $allinboxesFilterFound = true;
                    break;
                }
            }
            $this->assertEquals($filterTest['allinboxes'], $allinboxesFilterFound, print_r($result['filter'], true));
        }
    }
    
    public function testSearchMessageMixedQueryFilter()
    {
        $messageToSend = $this->_createMessageForQueryFilterTest();

        // fulltext, always a pleasure, needs commit ...
        $this->_testNeedsTransaction();

        // check if message is in sent folder
        $this->_searchForMessageBySubject($messageToSend['subject'], $this->_account->sent_folder);

        $this->_assertQueryFilter();
    }

    protected function _createMessageForQueryFilterTest(): array
    {
        $fromEmail = 'unittestalias@' . $this->_mailDomain;
        $messageToSend = $this->_getMessageData($fromEmail);

        $messageToSend['to'] = [$this->_personas['jsmith']->accountEmailAddress];
        $messageToSend['cc'] = [$this->_personas['jmcblack']->accountEmailAddress];
        $messageToSend['subject'] = 'subjectfilter';
        $this->_json->saveMessage($messageToSend);
        $this->_foldersToClear = array('INBOX', $this->_account->sent_folder);

        return $messageToSend;
    }

    protected function _assertQueryFilter()
    {
        $fromEmail = 'unittestalias@' . $this->_mailDomain;

        // search subject
        $result = $this->_json->searchMessages([['field' => 'query', 'operator' => 'wordstartswith', 'value' => 'subjectfilter']], []);
        $this->assertEquals('subjectfilter', $result['results'][0]['subject'], print_r($result['filter'], true));
        // search to email
        $result = $this->_json->searchMessages([['field' => 'query', 'operator' => 'wordstartswith', 'value' => $this->_personas['jsmith']->accountEmailAddress]], []);
        $this->assertEquals($this->_personas['jsmith']->accountEmailAddress, $result['results'][0]['to'][0], print_r($result['filter'], true));
        // search from email
        $result = $this->_json->searchMessages([['field' => 'query', 'operator' => 'wordstartswith', 'value' => $fromEmail]], []);
        $this->assertEquals($fromEmail, $result['results'][0]['from_email'], print_r($result['filter'], true));
        // search from cc
        $result = $this->_json->searchMessages([['field' => 'query', 'operator' => 'wordstartswith', 'value' => 'jmcblack']], []);
        $this->assertEquals($this->_personas['jmcblack']->accountEmailAddress, $result['results'][0]['cc'][0], print_r($result['results'][0]['cc'], true));
        // search from name #1
        $result = $this->_json->searchMessages([
            ['field' => 'query', 'operator' => 'wordstartswith', 'value' => $this->_originalTestUser->accountLastName],
        ], ['limit' => 1]);
        $this->assertCount(1, $result['results']);
        // search from name #2
        $result = $this->_json->searchMessages([
            ['field' => 'query', 'operator' => 'wordstartswith', 'value' => ($acf = trim(preg_replace('/\\W/', ' ', $this->_originalTestUser->accountFirstName)))]
        ], ['limit' => 1]);
        $this->assertCount(1, $result['results'], 'could not find mail by accountFirstName: "' . $acf . '"');
    }

    public function testSearchMessageMixedQuery_FilterFTOff()
    {
        $messageToSend = $this->_createMessageForQueryFilterTest();

        // fulltext, always a pleasure, needs commit ...
        $this->_testNeedsTransaction();

        // check if message is in sent folder
        $this->_searchForMessageBySubject($messageToSend['subject'], $this->_account->sent_folder);

        $oldValue = clone Tinebase_Config::getInstance()->{Tinebase_Config::ENABLED_FEATURES};#
        try {
            Tinebase_Config::getInstance()->{Tinebase_Config::ENABLED_FEATURES}->{Tinebase_Config::FEATURE_FULLTEXT_INDEX} = false;
            Tinebase_Config::getInstance()->clearCache();
            Tinebase_Cache_PerRequest::getInstance()->reset(Tinebase_Config::class);

            $this->_assertQueryFilter();
        } finally {
            Tinebase_Config::getInstance()->{Tinebase_Config::ENABLED_FEATURES} = $oldValue;
            Tinebase_Config::getInstance()->clearCache();
            Tinebase_Cache_PerRequest::getInstance()->reset(Tinebase_Config::class);
        }
    }

    /**
     * try search for a message with sorting by tags (should be handled by server - even if col does not exist)
     */
    public function testSearchMessageTagsSort()
    {
        $filter = array (
            1 =>
                array (
                    'field' => 'query',
                    'operator' => 'contains',
                    'value' => '',
                    'id' => 'quickFilter',
                ),
        );
        $paging = [
            'sort' => 'tags',
        ];
        $result = $this->_json->searchMessages($filter, $paging);
        self::assertGreaterThanOrEqual(0, $result['totalcount']);
    }

    /**
     * test flags (add + clear + deleted)
     */
    public function testAddAndClearFlags()
    {
        $message = $this->_sendMessage();
        $inboxBefore = $this->_getFolder('INBOX');

        $this->_json->addFlags($message['id'], Zend_Mail_Storage::FLAG_SEEN);

        // check if unread count got decreased
        $inboxAfter = $this->_getFolder('INBOX');
        $this->assertTrue($inboxBefore->cache_unreadcount - 1 == $inboxAfter->cache_unreadcount, 'wrong cache unreadcount');

        $message = $this->_json->getMessage($message['id']);
        $this->assertTrue(in_array(Zend_Mail_Storage::FLAG_SEEN, $message['flags']), 'seen flag not set');

        // try with a filter
        $filter = array(
            array('field' => 'id', 'operator' => 'in', 'value' => array($message['id']))
        );
        $this->_json->clearFlags($filter, Zend_Mail_Storage::FLAG_SEEN);

        $message = $this->_json->getMessage($message['id']);
        $this->assertFalse(in_array(Zend_Mail_Storage::FLAG_SEEN, $message['flags']), 'seen flag should not be set');

        $this->expectException('Tinebase_Exception_NotFound');
        $this->_json->addFlags(array($message['id']), Zend_Mail_Storage::FLAG_DELETED);
        $this->_json->getMessage($message['id']);
    }

    /**
     * testMarkFolderRead
     *
     * @see 0009812: mark folder as read does not work with pgsql
     */
    public function testMarkFolderRead()
    {
        $this->_sendMessage();
        $inboxBefore = $this->_getFolder('INBOX');
        $this->assertGreaterThan(0, $inboxBefore->cache_unreadcount);
        $filter = array(array(
            'field' => 'folder_id', 'operator' => 'equals', 'value' => $inboxBefore->getId()
        ), array(
            'field' => 'flags', 'operator' => 'notin', 'value' => array(Zend_Mail_Storage::FLAG_SEEN)
        ));
        $this->_json->addFlags($filter, Zend_Mail_Storage::FLAG_SEEN);

        $inboxAfter = $this->_getFolder('INBOX');
        $this->assertEquals(0, $inboxAfter->cache_unreadcount);
    }

    /**
     * test delete from trash
     */
    public function testDeleteFromTrashWithFilter()
    {
        $message = $this->_sendMessage();
        $this->_foldersToClear = array('INBOX', $this->_account->sent_folder, $this->_account->trash_folder);

        $trash = $this->_getFolder($this->_account->trash_folder);
        $result = $this->_json->moveMessages(array(array(
            'field' => 'id', 'operator' => 'in', 'value' => array($message['id'])
        )), $trash->getId());

        $messageInTrash = $this->_searchForMessageBySubject($message['subject'], $this->_account->trash_folder);

        // delete messages in trash with filter
        $this->_json->addFlags(array(array(
            'field' => 'folder_id', 'operator' => 'equals', 'value' => $trash->getId()
        ), array(
            'field' => 'id', 'operator' => 'in', 'value' => array($messageInTrash['id'])
        )), Zend_Mail_Storage::FLAG_DELETED);

        $this->expectException('Tinebase_Exception_NotFound');
        $this->_json->getMessage($messageInTrash['id']);
    }

    /**
     * move message to trash with trash folder constant (Felamimail_Model_Folder::FOLDER_TRASH)
     */
    public function testMoveMessagesToTrash()
    {
        $message = $this->_sendMessage();
        $this->_foldersToClear = array('INBOX', $this->_account->sent_folder, $this->_account->trash_folder);

        $result = $this->_json->moveMessages(array(array(
            'field' => 'id', 'operator' => 'in', 'value' => array($message['id'])
        )), Felamimail_Model_Folder::FOLDER_TRASH);

        $this->_searchForMessageBySubject($message['subject'], $this->_account->trash_folder);
        $messageInInbox = $this->_searchForMessageBySubject($message['subject'], 'INBOX', false);
        self::assertEquals([], $messageInInbox, 'message should be moved from inbox to trash');
    }

    /**
     * should be not moved messages without selected mails
     */
    public function testMoveMessagesWithoutSelectionToTrash()
    {
        $this->_sendMessage();
        $this->_foldersToClear = array('INBOX', $this->_account->sent_folder, $this->_account->trash_folder);
        $messageInInboxBeforeMove = $this->_getMessages();
        $messageInTrashBeforeMove = $this->_getMessages($this->_account->trash_folder);

        $this->_json->moveMessages(array(array(
            'field' => 'id', 'operator' => 'in', 'value' => array()
        )), Felamimail_Model_Folder::FOLDER_TRASH);

        $messageInTrash = $this->_getMessages($this->_account->trash_folder);
        $messageInInbox = $this->_getMessages();
        self::assertEquals($messageInTrashBeforeMove['totalcount'], $messageInTrash['totalcount'], 'message should be not moved from inbox to trash');
        self::assertEquals($messageInInboxBeforeMove['totalcount'], $messageInInbox['totalcount'], 'message should be not moved from inbox to trash');
    }

    /**
     * test reply mail and check some headers
     *
     * @see 0006106: Add References header / https://forge.tine20.org/mantisbt/view.php?id=6106
     */
    public function testReplyMessage()
    {
        self::markTestSkipped('FIXME: fails at random');

        $message = $this->_sendMessage();

        $replyMessage = $this->_getReply($message);
        $this->_json->saveMessage($replyMessage);

        $result = $this->_getMessages();

        $replyMessageFound = array();
        $originalMessage = array();
        foreach ($result['results'] as $mail) {
            if ($mail['subject'] == $replyMessage['subject']) {
                $replyMessageFound = $mail;
            }
            if ($mail['subject'] == $message['subject']) {
                $originalMessage = $mail;
            }
        }

        $this->assertTrue(isset($replyMessageFound['id']) && isset($originalMessage['id']), 'replied message not found');
        $replyMessageFound = $this->_json->getMessage($replyMessageFound['id']);
        $originalMessage = $this->_json->getMessage($originalMessage['id']);

        $this->assertTrue(!empty($replyMessageFound), 'replied message not found');
        $this->assertTrue(!empty($originalMessage), 'original message not found');
        // check headers
        $this->assertTrue(isset($replyMessageFound['headers']['in-reply-to']));
        $this->assertEquals($originalMessage['headers']['message-id'], $replyMessageFound['headers']['in-reply-to']);
        $this->assertTrue(isset($replyMessageFound['headers']['references']));
        $this->assertEquals($originalMessage['headers']['message-id'], $replyMessageFound['headers']['references']);

        // check answered flag
        $this->assertTrue(in_array(Zend_Mail_Storage::FLAG_ANSWERED, $originalMessage['flags'], 'could not find flag'));
    }

    /**
     * get reply message data
     *
     * @param array $_original
     * @return array
     */
    protected function _getReply($_original)
    {
        $replyMessage = $this->_getMessageData();
        $replyMessage['subject'] = 'Re: ' . $_original['subject'];
        $replyMessage['original_id'] = $_original['id'];
        $replyMessage['flags'] = Zend_Mail_Storage::FLAG_ANSWERED;
        return $replyMessage;
    }

    /**
     * test reply mail in sent folder
     */
    public function testReplyMessageInSentFolder()
    {
        self::markTestSkipped('FIXME: fails at random');

        $messageInSent = $this->_sendMessage($this->_account->sent_folder);
        $replyMessage = $this->_getReply($messageInSent);
        $returned = $this->_json->saveMessage($replyMessage);

        $result = $this->_getMessages();
        $sentMessage = $this->_getMessageFromSearchResult($result, $replyMessage['subject']);
        $this->assertTrue(!empty($sentMessage));
    }

    /**
     * test reply mail with long references header
     *
     * @see 0006644: "At least one mail header line is too long"
     */
    public function testReplyMessageWithLongHeader()
    {
        $messageInSent = $this->_sendMessage($this->_account->sent_folder, array(
            'references' => '<c95d8187-2c71-437e-adb8-5e1dcdbdc507@email.test.org>
   <2601bbfa-566e-4490-a3db-aad005733d32@email.test.org>
   <20120530154350.1854610131@ganymed.de>
   <7e393ce1-d193-44fc-bf5f-30c61a271fe6@email.test.org>
   <4FC8B49C.8040704@funk.de>
   <dba2ad5c-6726-4171-8710-984847c010a1@email.test.org>
   <20120601123551.5E98610131@ganymed.de>
   <f1cc3195-8641-46e3-8f20-f60f3e16b107@email.test.org>
   <20120619093658.37E4210131@ganymed.de>
   <CA+6Rn2PX2Q3tOk2tCQfCjcaC8zYS5XZX327OoyJfUb+w87vCLQ@mail.net.com>
   <20120619130652.03DD310131@ganymed.de>
   <37616c6a-4c47-4b54-9ca6-56875bc9205d@email.test.org>
   <20120620074843.42E2010131@ganymed.de>
   <CA+6Rn2MAb2x0qeSfcaW6F=0S7LEQL442Sx2ha9RtwMs4B0esBg@mail.net.com>
   <20120620092902.88C8C10131@ganymed.de>
   <c95d8187-2c71-437e-adb8-5e1dcdbdc507@email.test.org>
   <2601bbfa-566e-4490-a3db-aad005733d32@email.test.org>
   <20120530154350.1854610131@ganymed.de>
   <7e393ce1-d193-44fc-bf5f-30c61a271fe6@email.test.org>
   <4FC8B49C.8040704@funk.de>
   <dba2ad5c-6726-4171-8710-984847c010a1@email.test.org>
   <20120601123551.5E98610131@ganymed.de>
   <f1cc3195-8641-46e3-8f20-f60f3e16b107@email.test.org>
   <20120619093658.37E4210131@ganymed.de>
   <CA+6Rn2PX2Q3tOk2tCQfCjcaC8zYS5XZX327OoyJfUb+w87vCLQ@mail.net.com>
   <20120619130652.03DD310131@ganymed.de>
   <37616c6a-4c47-4b54-9ca6-56875bc9205d@email.test.org>
   <20120620074843.42E2010131@ganymed.de>
   <CA+6Rn2MAb2x0qeSfcaW6F=0S7LEQL442Sx2ha9RtwMs4B0esBg@mail.net.com>
   <20120620092902.88C8C10131@ganymed.de>'
        ));
        $replyMessage = $this->_getReply($messageInSent);
        $this->_json->saveMessage($replyMessage);

        $result = $this->_getMessages();
        $sentMessage = $this->_getMessageFromSearchResult($result, $replyMessage['subject']);
        $this->assertTrue(!empty($sentMessage));
    }

    /**
     * test move
     *
     * @param string $moveToFolderName
     */
    public function testMoveMessage($moveToFolderName = null)
    {
        if (! $moveToFolderName) {
            $moveToFolderName = $this->_testFolderName;
        }

        $inbox = $this->_getFolder('INBOX');
        $inboxBefore = $this->_json->updateMessageCache($inbox['id'], 30);

        $message = $this->_moveMessageToFolder($moveToFolderName);
        $inboxAfter = $this->_getFolder('INBOX');

        $this->assertEquals($inboxBefore['cache_unreadcount'], $inboxAfter['cache_unreadcount']);
        $this->assertEquals($inboxBefore['cache_totalcount'], $inboxAfter['cache_totalcount']);

        $movedMessage = $this->_assertMessageInFolder($moveToFolderName, $message['subject']);
        self::assertEquals($message['received'], $movedMessage['received'], 'received date different');
    }

    protected function _moveMessageToFolder($moveToFolderName, $keepOriginalMessages = false, $account = null)
    {
        $message = $this->_sendMessage();
        $this->_foldersToClear[] = $moveToFolderName;
        $this->_foldersToClear = array_unique($this->_foldersToClear);
        // move
        $testFolder = $this->_getFolder($moveToFolderName, true, $account);
        $this->_json->moveMessages(array(array(
            'field' => 'id', 'operator' => 'in', 'value' => array($message['id'])
        )), $testFolder->getId(), $keepOriginalMessages);

        // sleep for 2 secs because mailserver may be slower than expected
        sleep(2);

        return $message;
    }

    public function testMoveMessageToAnotherAccount()
    {
        // create shared account
        $account = $this->_createSharedAccount();

        $folders = $this->_json->updateFolderCache($account->getId(), '');
        $result = $this->_json->addFolder('Info Gemeindebüro', 'INBOX', $account->getId());

        // send message and move to other account folder "Info Gemeindebüro"
        $message = $this->_moveMessageToFolder('INBOX.Info Gemeindebüro', false, $account);
        $this->_assertMessageNotInFolder('INBOX', $message['subject']);
        $this->_assertMessageInFolder('INBOX.Info Gemeindebüro', $message['subject'], $account);
    }

    public function testCopyMessageToAnotherFolder()
    {
        $moveToFolderName = $this->_testFolderName;
        $message = $this->_moveMessageToFolder($moveToFolderName, true);

        $this->_assertMessageInFolder('INBOX', $message['subject']);
        $this->_assertMessageInFolder($moveToFolderName, $message['subject']);
    }

    public function testCopyMessageToAnotherFolderDisabled()
    {
        Felamimail_Config::getInstance()->set(Felamimail_Config::PREVENT_COPY_OF_MAILS_IN_SAME_ACCOUNT, true);

        try {
            $this->testCopyMessageToAnotherFolder();
            self::fail('copy should not be possible');
        } catch (Exception $e) {
            $translation = Tinebase_Translation::getTranslation('Felamimail');
            self::assertEquals($translation->_('It is not allowed to copy e-mails in the same account.'), $e->getMessage(), $e);
        }

        Felamimail_Config::getInstance()->set(Felamimail_Config::PREVENT_COPY_OF_MAILS_IN_SAME_ACCOUNT, false);
    }

    public function testMoveMessageToFolderWithUmlaut()
    {
        $this->_createdFolders = [$this->_testFolderName . '.Info Gemeindebüro'];
        $result = $this->_json->addFolder('Info Gemeindebüro', $this->_testFolderName, $this->_account->getId());
        $this->testMoveMessage($result['globalname']);
    }

    /**
     * forward message test
     *
     * @see 0007624: losing umlauts in attached filenames
     */
    public function testForwardMessageWithAttachment()
    {
        $message = $this->_appendMessageforForwarding();

        $fwdSubject = 'Fwd: ' . $message['subject'];
        $forwardMessageData = array(
            'account_id' => $this->_account->getId(),
            'subject' => $fwdSubject,
            'to' => array($this->_getEmailAddress()),
            'body' => "aaaaaä <br>",
            'headers' => array('X-Tine20TestMessage' => 'jsontest'),
            'original_id' => $message['id'],
            'attachments' => array(new Tinebase_Model_TempFile(array(
                'type' => Felamimail_Model_Message::CONTENT_TYPE_MESSAGE_RFC822,
                'name' => 'Verbessurüngsvorschlag',
            ), TRUE)),
            'flags' => Zend_Mail_Storage::FLAG_PASSED,
        );

        $this->_foldersToClear[] = 'INBOX';
        $this->_json->saveMessage($forwardMessageData);
        $forwardMessage = $this->_searchForMessageBySubject($fwdSubject);

        // check attachment name
        $forwardMessageComplete = $this->_json->getMessage($forwardMessage['id']);
        $this->assertEquals(1, count($forwardMessageComplete['attachments']));
        $this->assertEquals('Verbessurüngsvorschlag.eml', $forwardMessageComplete['attachments'][0]['filename'],
            'umlaut missing from attachment filename');

        $forwardMessage = Felamimail_Controller_Message::getInstance()->getCompleteMessage($forwardMessage['id']);
        $this->assertTrue((isset($forwardMessage['structure']) || array_key_exists('structure', $forwardMessage)),
            'structure should be set when fetching complete message: ' . print_r($forwardMessage, TRUE));
        $this->assertEquals(Felamimail_Model_Message::CONTENT_TYPE_MESSAGE_RFC822,
            $forwardMessage['structure']['parts'][2]['contentType']);

        $message = $this->_json->getMessage($message['id']);
        $this->assertTrue(in_array(Zend_Mail_Storage::FLAG_PASSED, $message['flags']),
            'forwarded flag missing in flags: ' . print_r($message, TRUE));
    }

    protected function _appendMessageforForwarding($file = 'multipart_related.eml', $subject = 'Tine 2.0 bei Metaways - Verbessurngsvorschlag')
    {
        $testFolder = $this->_getFolder($this->_testFolderName);
        $message = fopen(dirname(__FILE__) . '/../files/' . $file, 'r');
        Felamimail_Controller_Message::getInstance()->appendMessage($testFolder, $message);
        return $this->_searchForMessageBySubject($subject, $this->_testFolderName);
    }

    public function testForwardAttachmentCachePdf()
    {
        $pdfFile = $this->_createTestNode(
            'newline.pdf',
            dirname(__FILE__, 3) . '/Tinebase/files/multipage-text.pdf'
        );
        $subject = 'file attachment test';
        $messageToSend = $this->_getMessageData('unittestalias@' . $this->_mailDomain, $subject);
        $messageToSend['attachments'] = array(
            array(
                // @todo use constants?
                'type' => 'file',
                'attachment_type' => 'attachment',
                'path' => $pdfFile[0]['path'],
                'name' => $pdfFile[0]['name'],
                'id' => $pdfFile[0]['id'],
                'size'  => $pdfFile[0]['size'],
            )
        );
        $this->_json->saveMessage($messageToSend);
        $message = $this->_searchForMessageBySubject($subject);
        $message = Felamimail_Controller_Message::getInstance()->getCompleteMessage($message['id']);

        $forwardMessage = new Felamimail_Model_Message(array(
            'account_id'    => $this->_account->getId(),
            'subject'       => 'test forward with attachmnets',
            'to'            => array(Tinebase_Core::getUser()->accountEmailAddress),
            'body'          => 'aaaaaä <br>',
            'headers'       => array('X-Tine20TestMessage' => Felamimail_Model_Message::CONTENT_TYPE_MESSAGE_RFC822),
            'original_id'   => $message['id'],
            'attachments'   => $messageToSend['attachments']
        ));
        Felamimail_Controller_Message_Send::getInstance()->sendMessage($forwardMessage);

        $forwardMessage = $this->_searchForMessageBySubject('test forward with attachmnets');
        $this->_foldersToClear = array('INBOX', $this->_account->sent_folder);
        $fullMessage = Felamimail_Controller_Message::getInstance()->getCompleteMessage($forwardMessage['id']);
        self::assertTrue(count($fullMessage->attachments) === 1, 'attachment not found: ' . print_r($fullMessage->toArray(), true));

        $id = get_class($fullMessage) . ':' . $fullMessage->getId() . ':' . $fullMessage->attachments[0]['partId'];
        $cachedAttachment = $this->_json->getAttachmentCache($id);

        $this->assertCount(1, $cachedAttachment['attachments']);
        $this->assertStringContainsString($id . '/newline.pdf', $cachedAttachment['attachments'][0]['path']);
        $this->assertEquals($message['attachments'][0]['size'], $fullMessage['attachments'][0]['size']);
        $this->assertEquals(0, $cachedAttachment['attachments'][0]['preview_status']);
    }


    public function testAttachmentCache()
    {
        $pdfFile = $this->_createTestNode(
            'newline.pdf',
            dirname(__FILE__, 3) . '/Tinebase/files/multipage-text.pdf'
        );
        $subject = 'file attachment test';
        $messageToSend = $this->_getMessageData('unittestalias@' . $this->_mailDomain, $subject);
        $messageToSend['attachments'] = array(
            array(
                // @todo use constants?
                'type' => 'file',
                'attachment_type' => 'attachment',
                'path' => $pdfFile[0]['path'],
                'name' => $pdfFile[0]['name'],
                'id' => $pdfFile[0]['id'],
                'size'  => $pdfFile[0]['size'],
            )
        );
        $this->_json->saveMessage($messageToSend);
        $forwardMessage = $this->_searchForMessageBySubject($subject);
        $this->_foldersToClear = array('INBOX', $this->_account->sent_folder);

        $fullMessage = Felamimail_Controller_Message::getInstance()->getCompleteMessage($forwardMessage['id']);
        self::assertTrue(count($fullMessage->attachments) === 1, 'attachment not found: ' . print_r($fullMessage->toArray(), true));

        $id = get_class($fullMessage) . ':' . $fullMessage->getId() . ':' . $fullMessage->attachments[0]['partId'];
        $cachedAttachment = $this->_json->getAttachmentCache($id);

        $this->assertCount(1, $cachedAttachment['attachments']);
        $this->assertStringContainsString($id . '/newline.pdf', $cachedAttachment['attachments'][0]['path']);
        $this->assertEquals(17417, $cachedAttachment['attachments'][0]['size']);
        $this->assertEquals(0, $cachedAttachment['attachments'][0]['preview_status']);
    }

    public function testAttachmentCacheNode()
    {
        $result = $this->_createTestNode(
            'test.eml',
            dirname(__FILE__) . '/../files/multipart_related.eml'
        );

        $fullMessage = Felamimail_Controller_Message::getInstance()->getMessageFromNode($result[0]['id']);

        $id = Filemanager_Model_Node::class . ':' . $result[0]['id'] . ':' . $fullMessage['attachments'][0]['partId'];
        $cachedAttachment = $this->_json->getAttachmentCache($id);

        $this->assertCount(1, $cachedAttachment['attachments']);
        $this->assertStringContainsString($id . '/moz-screenshot-83.png', $cachedAttachment['attachments'][0]['path']);
        $this->assertEquals(25370, $cachedAttachment['attachments'][0]['size']);
        $this->assertEquals(0, $cachedAttachment['attachments'][0]['preview_status']);
    }

    /**
     * forward message test (eml attachment from Filemanager)
     */
    public function testForwardMessageWithEmlAttachmentFromFilemanager()
    {
        $result = $this->_createTestNode(
            'test.eml',
            dirname(__FILE__) . '/../files/multipart_related.eml'
        );
        $subject = 'file attachment test';
        $messageToSend = $this->_getMessageData('unittestalias@' . $this->_mailDomain, $subject);
        $messageToSend['attachments'] = array(
            array(
                // @todo use constants?
                'type' => 'file',
                'attachment_type' => 'attachment',
                'path' => $result[0]['path'],
                'name' => $result[0]['name'],
                'id' => $result[0]['id'],
            )
        );
        $this->_json->saveMessage($messageToSend);
        $forwardMessage = $this->_searchForMessageBySubject($subject);
        $this->_foldersToClear = array('INBOX', $this->_account->sent_folder);

        $fullMessage = $this->_json->getMessage($forwardMessage['id']);
        self::assertTrue(count($fullMessage['attachments']) === 1, 'attachment not found: ' . print_r($fullMessage, true));
        $attachment = $fullMessage['attachments'][0];
        self::assertEquals('message/rfc822', $attachment['content-type']);
        self::assertEquals('test.eml', $attachment['filename']);
        self::assertGreaterThanOrEqual(38506, $attachment['size']);
    }

    /**
     * testSendMessageWithAttachmentWithoutExtension
     *
     * @see 0008328: email attachment without file extension is not sent properly
     *
     * @return array
     */
    public function testSendMessageWithAttachmentWithoutExtension()
    {
        $subject = 'attachment test';
        $messageToSend = $this->_getMessageData('unittestalias@' . $this->_mailDomain, $subject);
        $tempfileName = 'jsontest' . Tinebase_Record_Abstract::generateUID(10);
        $tempFile = $this->_getTempFile(null, $tempfileName);
        $messageToSend['attachments'] = array(
            array('tempFile' => array('id' => $tempFile->getId(), 'type' => $tempFile->type))
        );
        $this->_json->saveMessage($messageToSend);
        $forwardMessage = $this->_searchForMessageBySubject($subject);
        $this->_foldersToClear = array('INBOX', $this->_account->sent_folder);

        $fullMessage = $this->_json->getMessage($forwardMessage['id']);
        $this->assertTrue(count($fullMessage['attachments']) === 1);
        $attachment = $fullMessage['attachments'][0];
        $this->assertStringContainsString($tempfileName, $attachment['filename'], 'wrong attachment filename: ' . print_r($attachment, TRUE));
        $this->assertEquals(24, $attachment['size'], 'wrong attachment size: ' . print_r($attachment, TRUE));

        return $fullMessage;
    }

    /**
     * testSendPlainMessageWithLessThenSign
     *
     * @todo add mantis issue
     */
    public function testSendPlainMessageWithLessThenSign()
    {
        $messageToSend = $this->_getMessageData();
        $messageToSend['content_type'] = 'text/plain';
        $messageToSend['body'] = 'lalala < logloff​';

        $this->_json->saveMessage($messageToSend);
        $message = $this->_searchForMessageBySubject('test');
        $this->_foldersToClear = array('INBOX', $this->_account->sent_folder);

        $fullMessage = $this->_json->getMessage($message['id']);

        self::assertStringContainsString('lalala &lt; logloff​', $fullMessage['body']);
    }

    /**
     * save message in folder (draft) test
     *
     * @see 0007178: BCC does not save the draft message
     */
    public function testSaveMessageInFolder()
    {
        $messageToSave = $this->_getMessageData();
        $messageToSave['bcc'] = array('bccaddress@email.org', 'bccaddress2@email.org');

        $this->_getFolder($this->_account->drafts_folder);
        $this->_json->saveMessageInFolder($this->_account->drafts_folder, $messageToSave);
        $this->_foldersToClear = array($this->_account->drafts_folder);

        // check if message is in drafts folder and recipients are present
        $message = $this->_searchForMessageBySubject($messageToSave['subject'], $this->_account->drafts_folder);
        self::assertEquals($messageToSave['subject'], $message['subject']);
        self::assertEquals($messageToSave['to'][0], $message['to'][0], 'recipient not found');
        self::assertEquals(2, count($message['bcc']), 'bcc recipient not found: ' . print_r($message, TRUE));
        self::assertStringContainsString('bccaddress', $message['bcc'][0], 'bcc recipient not found');
    }

    /**
     * testSaveMessageWithMixedRecipients
     */
    public function testSaveMessageWithMixedRecipients()
    {
        $subject = 'test ' . Tinebase_Record_Abstract::generateUID(16);
        $messageToSave = $this->_getMessageData('', $subject);
        $messageToSave['to'] = [
            [
                "email" => Tinebase_Core::getUser()->accountEmailAddress,
                "name" => '',
                "type" =>  '',
                "n_fileas" => '',
                "email_type_field" =>  '',
                "contact_record" => ''
            ], 'test String <testString@mail.test>',
            'test String 2 <testString2@mail.test>; test String 3 <testString3@mail.test>',
            [
                'email' => 'testArray@mail.test',
                'name' => 'test'
            ]
        ];
        $messageToSave['bcc'] = array('bccaddress@email.org', 'bccaddress2@email.org');

        $this->_getFolder($this->_account->drafts_folder);
        $this->_json->saveMessageInFolder($this->_account->drafts_folder, $messageToSave);
        $this->_foldersToClear = array($this->_account->drafts_folder);

        // check if message is in drafts folder and recipients are present
        $message = $this->_searchForMessageBySubject($messageToSave['subject'], $this->_account->drafts_folder);
        self::assertEquals($messageToSave['subject'], $message['subject']);
        self::assertTrue(in_array($messageToSave['to'][0]['email'], $message['to']), 'recipient not found: '
            . print_r($message, true));
        self::assertTrue(in_array('teststring@mail.test', $message['to']), 'recipient not found');
        self::assertTrue(in_array('teststring2@mail.test', $message['to']), 'recipient not found');
        self::assertTrue(in_array('teststring3@mail.test', $message['to']), 'recipient not found');
        self::assertEquals(2, count($message['bcc']), 'bcc recipient not found: '
            . print_r($message, true));
        self::assertStringContainsString('bccaddress', $message['bcc'][0], 'bcc recipient not found');
    }

    /**
     * testSendReadingConfirmation
     *
     * @see 0007736: ask user before sending reading confirmation
     * @see 0008402: Wrong recipient with read confirmation
     */
    public function testSendReadingConfirmation()
    {
        $messageToSave = $this->_getMessageData();
        $messageToSave['headers']['disposition-notification-to'] = '"' . Tinebase_Core::getUser()->accountFullName . '" <' . $this->_account->email . '>';
        $returned = $this->_json->saveMessageInFolder($this->_testFolderName, $messageToSave);
        $messageWithReadingConfirmationHeader = $this->_searchForMessageBySubject($messageToSave['subject'], $this->_testFolderName);
        $this->_messageIds[] = $messageWithReadingConfirmationHeader['id'];
        $this->_json->sendReadingConfirmation($messageWithReadingConfirmationHeader['id']);

        $translate = Tinebase_Translation::getTranslation('Felamimail');
        $subject = $translate->_('Reading Confirmation:') . ' ' . $messageToSave['subject'];
        $message = $this->_searchForMessageBySubject($subject);
        $this->_messageIds[] = $message['id'];

        $complete = $this->_json->getMessage($message['id']);
        $this->assertStringContainsString($translate->_('Was read by:') . ' ' . $this->_account->from, $complete['body']);
    }

    /**
     * save message in non-existant folder (templates) test
     *
     * @see 0008476: Drafts are not working
     */
    public function testSaveMessageInNonExistantTemplatesFolder()
    {
        $messageToSave = $this->_getMessageData();

        $templatesFolder = $this->_getFolder($this->_account->templates_folder, FALSE);
        if ($templatesFolder) {
            try {
                $this->_json->deleteFolder($templatesFolder['id'], $this->_account->getId());
            } catch (Felamimail_Exception_IMAPFolderNotFound $feifnf) {
                // do nothing
            }
        }
        $returned = $this->_json->saveMessageInFolder($this->_account->templates_folder, $messageToSave);
        $this->_foldersToClear = array($this->_account->templates_folder);

        // check if message is in templates folder
        $message = $this->_searchForMessageBySubject($messageToSave['subject'], $this->_account->templates_folder);
        $this->assertEquals($messageToSave['subject'], $message['subject']);
        $this->assertEquals($messageToSave['to'][0], $message['to'][0], 'recipient not found');
    }

    /**
     * testSaveMessageNoteWithInvalidChar
     *
     * @see 0008644: error when sending mail with note (wrong charset)
     */
    public function testSaveMessageWithInvalidChar()
    {
        $subject = "\xF0\x9F\x98\x8A"; // :-) emoji
        $messageData = $this->_getMessageData('', $subject);
        $this->_foldersToClear[] = 'INBOX';
        $this->_json->saveMessage($messageData);
        $this->_searchForMessageBySubject(Tinebase_Core::filterInputForDatabase($subject));
    }

    /**
     * @see 0012160: save emails in filemanager
     *
     * @param string $locationType one of: 'node', 'path', 'suggestion'
     * @return array
     *
     * @todo split up function
     */
    public function testFileMessagesAsNode($locationType = 'path')
    {
        $appName = 'Filemanager';
        $user = Tinebase_Core::getUser();
        $personalFilemanagerContainer = $this->_getPersonalContainerNode($appName, $user);
        $message = $this->_sendMessage(
            'INBOX',
            /* $addtionalHeaders */
            array(),
            /* $_emailFrom */
            '',
            /*$_subject */
            'testÄÖÜäöüß\test' // is converted to 'testÄÖÜäöüß_test'
        );
        $message2 = $this->_sendMessage(
            'INBOX',
            /* $addtionalHeaders */
            array(),
            /* $_emailFrom */
            '',
            /*$_subject */
            'abctest'
        );
        $filter = array(array(
            'field' => 'id', 'operator' => 'in', 'value' => array($message['id'], $message2['id'])
        ));
        $path = $this->_getPersonalFilemanagerPath($personalFilemanagerContainer);
        $location = $this->_getTestLocation($locationType, $personalFilemanagerContainer, $path);
        $result = $this->_json->fileMessages($filter, [$location]);
        $nodes = $this->_getTestNodes($path);
        $emlNode = $nodes->getFirstRecord();

        // assertions!
        self::assertStringContainsString('testÄÖÜäöüß_test', $emlNode->name);
        return $this->_assertFiledMessageNode($message, $result, $emlNode, $personalFilemanagerContainer);
    }

    protected function _assertFiledMessageNode($message, $result, $emlNode, $personalFilemanagerContainer)
    {
        self::assertTrue(isset($result['totalcount']));
        self::assertEquals(2, $result['totalcount'], 'message should be filed in Filemanager: ' . print_r($result, true));

        // check if message exists in Filemanager
        self::assertTrue($emlNode !== null, 'could not find eml file node');
        self::assertEquals(Tinebase_Model_Tree_FileObject::TYPE_FILE, $emlNode->type);
        self::assertEquals('message/rfc822', $emlNode->contenttype);
        self::assertTrue(preg_match('/[a-f0-9]{10}/', $emlNode->name) == 1, 'no message id hash in node name: ' . print_r($emlNode->toArray(), true));
        self::assertStringContainsString(Tinebase_Core::getUser()->accountEmailAddress, $emlNode->name);
        $now = Tinebase_DateTime::now();
        self::assertStringContainsString($now->toString('Y-m-d'), $emlNode->name);

        $nodeWithDescription = Filemanager_Controller_Node::getInstance()->get($emlNode['id']);
        self::assertTrue(isset($nodeWithDescription->description), 'description missing from node: ' . print_r($nodeWithDescription->toArray(), true));
        self::assertStringContainsString($message['received'], $nodeWithDescription->description);
        self::assertStringContainsString('aaaaaä', $nodeWithDescription->description);

        // assert MessageFileLocation
        $completeMessage = $this->_json->getMessage($message['id']);
        $messageIdHash = sha1($completeMessage['headers']['message-id']);
        $filter = Tinebase_Model_Filter_FilterGroup::getFilterForModel(
            Felamimail_Model_MessageFileLocation::class, [
                ['field' => 'record_id', 'operator' => 'equals', 'value' => $personalFilemanagerContainer->getId()],
                ['field' => 'message_id_hash', 'operator' => 'equals', 'value' => $messageIdHash],
            ]
        );
        $result = Felamimail_Controller_MessageFileLocation::getInstance()->search($filter);
        self::assertEquals(1, count($result), 'did not find location record: '
            . print_r($result->toArray(), true));
        $fileLocation = $result->getFirstRecord();
        self::assertNotNull($fileLocation->message_id);
        self::assertEquals(Felamimail_Model_MessageFileLocation::TYPE_NODE, $fileLocation->type);
        self::assertEquals($personalFilemanagerContainer->name, $fileLocation->record_title);

        return $completeMessage;
    }

    public function testFileMessagesAsNodeWithoutPath()
    {
        $this->testFileMessagesAsNode('node');
    }

    public function testFileMessagesAsNodeWithId()
    {
        $this->testFileMessagesAsNode('id');
    }

    /**
     * file a message, save the node, file the message again
     */
    public function testFileMessageAsNodeAndDeleteIt()
    {
        $this->testFileMessagesAsNode();

        // delete nodes
        $nodeController = Filemanager_Controller_Node::getInstance();
        $path = $this->_getPersonalFilemanagerPath();
        $testNodes = $this->_getTestNodes($path);
        $nodeController->deleteNodes($testNodes->path);

        // file them again
        $this->testFileMessagesAsNode();
    }

    /**
     * file a message, save the attachment, file the message again
     */
    public function testFileMessageAsAttachmentAndDeleteIt()
    {
        $this->testFileMessageAsAttachment();

        $contact = Addressbook_Controller_Contact::getInstance()->getContactByUserId(Tinebase_Core::getUser()->getId());
        // delete contact attachments
        Tinebase_FileSystem_RecordAttachments::getInstance()->deleteRecordAttachments($contact);

        // file it again
        $this->testFileMessagesAsNode();
    }

    public function testFileMessageAsNodeAndReplyToIt()
    {
        $appName = 'Filemanager';
        $user = Tinebase_Core::getUser();
        $personalFilemanagerContainer = $this->_getPersonalContainerNode($appName, $user);
        $message = $this->_sendMessage(
            'INBOX',
            /* $addtionalHeaders */
            array(),
            /* $_emailFrom */
            '',
            /*$_subject */
            'test\test' // is converted to 'test_test'
        );
        $filter = array(array(
            'field' => 'id', 'operator' => 'in', 'value' => array($message['id'])
        ));
        $path = $this->_getPersonalFilemanagerPath($personalFilemanagerContainer);
        $location = $this->_getTestLocation('path', $personalFilemanagerContainer, $path);
        $this->_json->fileMessages($filter, [$location]);
        $nodes = $this->_getTestNodes($path);
        $emlNode = $nodes->getFirstRecord();

        # print_r($emlNode->toArray());

        $messageToSend = $this->_getMessageData();
        $messageToSend['original_id'] = $emlNode->getId();
        $messageToSend['flags'] = '\\Answered';
        $this->_json->saveMessage($messageToSend);
        $this->_foldersToClear = array('INBOX', $this->_account->sent_folder);
        return $this->_assertMessageInFolder('INBOX', $messageToSend['subject']);
    }

    public function testFileAttachment()
    {
        $personalFilemanagerContainer = $this->_getPersonalContainerNode();
        $path = $this->_getPersonalFilemanagerPath($personalFilemanagerContainer);
        $location = $this->_getTestLocation('path', $personalFilemanagerContainer, $path);

        $message = $this->_sendMessage(
            'INBOX',
            [],
            '',
            'test file attachment',
            null,
            1
        );
        $message = $this->_json->getMessage($message['id']);
        $result = $this->_json->fileAttachments($message['id'], [$location], $message['attachments']);
        self::assertTrue($result['success']);

        $nodes = $this->_getTestNodes($path, 'test1.txt');
        $node = $nodes->getFirstRecord();

        // check if attachment exists in Filemanager
        self::assertTrue($node !== null, 'could not find attachment file node');
        self::assertEquals(Tinebase_Model_Tree_FileObject::TYPE_FILE, $node->type);
        self::assertEquals('text/plain', $node->contenttype);

        // check node contents
        $content = Tinebase_FileSystem::getInstance()->getNodeContents($node);
        self::assertEquals('test file content', $content);

        // test to file it again to the same location
        $result = $this->_json->fileAttachments($message['id'], [$location], $message['attachments']);
        self::assertTrue($result['success']);
    }

    /**
     * testMessageWithInvalidICS
     *
     * @see 0008786: broken ics causes js error when showing details
     */
    public function testMessageWithInvalidICS()
    {
        $inbox = $this->_getFolder('INBOX');
        $mailAsString = file_get_contents(dirname(__FILE__) . '/../files/invalidimip.eml');
        Felamimail_Controller_Message::getInstance()->appendMessage($inbox, $mailAsString);

        $this->_foldersToClear = array('INBOX');
        $message = $this->_searchForMessageBySubject('test invalid imip');

        $fullMessage = $this->_json->getMessage($message['id']);
        $this->assertFalse(empty($fullMessage['preparedParts']));
    }

    /**
     * testSendMailvelopeAPIMessage
     *
     * - envelope armored message into PGP MIME structure
     */
    public function testSendMailvelopeAPIMessage()
    {
        $subject = __FUNCTION__;
        $messageData = $this->_getMessageData('', $subject);
        $messageData['body'] = '-----BEGIN PGP MESSAGE-----
Version: Mailvelope v1.3.3
Comment: https://www.mailvelope.com

wcFMA/0LJF28pDbGAQ//YgtsmEZN+pgIJiBDb7iYwPEOchDRIEjGOx543KF6
5YigW9p39pfcJgvGfT8x9cUIrYGxyw5idPSOEftYXyjjGaOYGaKpRSR4hI83
OcJSlEHKq72xhg04mNpCjjJ8dLBstPcQ7tDtsA8Nfb4PwkUYB9IhIBnARg+n
NvrN8mSA2UnY9ElFCvf30sar8EuM5swAjbk64C8TIypMy/Bg4T93zRdxwik6
7BCcbOpm/2PTsiVYBOTcU4+XdG5eyTENXH58M6UTxTD4/g7Qi5PjN+PxyXqf
v2Y1k9F49Y1egf2QJ2r4PX0EWS8SaynSHiIoBsp1xb07nLwZwCdMPG1QNPpF
l2FqlS4dEuQTdkv0deMvd7gtiNynRTAVcJc1ZC6RuWJ+EH2jA49XWkn14eRC
e5jMtPPudkhubnN9Je5lwatGKbJGyuXh+IaM0E0WQMZ3dm8+ST1l4WpVuGbw
KozLUiTRJP9UoxWOcwpQOnzcSlc4rHmWdtF0y3usM9u9GPREqpNUWkEyEEuv
XdZE7rKKj22dJHLCXxAQEh3m29Y2WVaq50YbtEZ+SwwbrHhxP4+FJEru+byh
fiZ47sVW2KvYGJPvbFoSZHiSvMecxDg8BVwe+naZXww/Rwa/TlaX4bYyzpUG
KeJUAzWEfFpJ0+yAvMGQEC7psIJ9NCx149C4ujiQmajSwhUB3XANcmCGB0wm
JjcqC4AHvc7/t4MrQZm0F/W+nrMmNqbZk+gylVrPs9rFEqu7wbjwTmsFA3sS
LkenvQIxBali6uzCR+nd09REqcYirG9cLti39DW048lhhG/ml+gAxxNEaSpG
NbIoV/3w8n7sAIM1fjuHne8bX0gWG43TTjU8MwSMryG7tCOG5u+Cebh6TAoY
NzbX2dpDhOYq5zXdCgKU4P3eh0csSs4UrqFT3TdAxIGrQJ7KrXvB6+N8gRZo
FcUaR+zrRPJjPUZfi46ecP5SG/tM5ea1hqvkwEnEpqjLmCUxqB+rfxx46USX
hMZd2ukUv6kEKv3EUDsRYu1SlDLhDLhWNx8RJae5XkMR+eUUMyNNVwbeMQbB
VAcMcaPITTk84sH7XElr9eF6sCUN4V79OSBRPGY/aNGrcwcoDSD4Hwu+Lw9w
Q+1n8EQ66gAkbJzCNd5GaYMZR9echkBaD/rdWDS3ktcrMehra+h44MTQONV9
8W+RI+IT5jaIXtB4jePmGjsJjbC9aEhTRBRkUnPA7phgknc52dD74AY/6lzK
yd4uZ6S3vhurJW0Vt4iBWJzhFNiSODh5PzteeNzCVAkGMsQvy1IHk0d3uzcE
0tEuSh8fZOFGB4fvMx9Mk8oAU92wfj4J7AVpSo5oRdxMqAXfaYKqfr2Gn++q
E5LClhVIBbFXclCoe0RYNz4wtxjeeYbP40Bq5g0JvPutD/dBMp8hz8Qt+yyG
d8X4/KmQIXyFZ8aP17GMckE5GVVvY9y89eWnWuTUJdwM540hB/EJNeHHTE5y
N2FSLGcmNkvE+3H7BczQ2ZI1SZDhof+umbUst0qoQW+hHmY3CSma48yGAVox
52u2t7hosHCfpf631Ve/6fcICo8vJ2Qfufu2BGIMlSfx4WzUuaMQBynuxFSa
IbVx8ZTO7dJRKrg72aFmWTf0uNla7vicAhpiLWobyNYcZbIjrAGDfg==
=BaAn
-----END PGP MESSAGE-----';

        $this->_foldersToClear[] = 'INBOX';
        $this->_json->saveMessage($messageData);

        $message = $this->_searchForMessageBySubject(Tinebase_Core::filterInputForDatabase($subject));
        $fullMessage = Felamimail_Controller_Message::getInstance()->getCompleteMessage($message['id']);
        $this->assertStringContainsString('multipart/encrypted', $fullMessage['headers']['content-type']);
        $this->assertStringContainsString('protocol="application/pgp-encrypted"', $fullMessage['headers']['content-type']);
        $this->assertCount(2, $fullMessage['structure']['parts']);
        $this->assertEquals('application/pgp-encrypted', $fullMessage['structure']['parts'][1]['contentType']);
        $this->assertEquals('application/octet-stream', $fullMessage['structure']['parts'][2]['contentType']);

        return $fullMessage;
    }

    /**
     * testMessagePGPMime
     *
     * - prepare armored part of PGP MIME structure
     */
    public function testMessagePGPMime()
    {
        $fullMessage = $this->testSendMailvelopeAPIMessage();

        $this->assertEquals('application/pgp-encrypted', $fullMessage['preparedParts'][0]['contentType']);
        $this->assertStringContainsString('-----BEGIN PGP MESSAGE-----', $fullMessage['preparedParts'][0]['preparedData']);
    }

    public function testMessagePGPInline()
    {
        $inbox = $this->_getFolder('INBOX');
        $mailAsString = file_get_contents(dirname(__FILE__) . '/../files/multipart_alternative_pgp_inline.eml');
        Felamimail_Controller_Message::getInstance()->appendMessage($inbox, $mailAsString);

        $this->_foldersToClear = array('INBOX');
        $message = $this->_searchForMessageBySubject('Re: mailvelope und tine20');

        $fullMessage = $this->_json->getMessage($message['id']);
        $this->assertFalse(empty($fullMessage['preparedParts']));
    }

    /*********************** sieve tests ****************************/

    /**
     * set and get vacation sieve script
     *
     * @see 0007768: Sieve - Vacation notify frequency not being set (Cyrus)
     */
    public function testGetSetVacation()
    {
        $vacationData = self::getVacationData($this->_account);
        $this->_sieveTestHelper($vacationData);

        // check if script was activated
        $activeScriptName = Felamimail_Controller_Sieve::getInstance()->getActiveScriptName($this->_account->getId());
        $this->assertEquals($this->_testSieveScriptName, $activeScriptName);
        $updatedAccount = Felamimail_Controller_Account::getInstance()->get($this->_account->getId());
        $this->assertTrue((bool)$updatedAccount->sieve_vacation_active);
        $result = $this->_json->getVacation($this->_account->getId());

        $this->assertEquals($this->_account->email, $result['addresses'][0]);

        $sieveBackend = Felamimail_Backend_SieveFactory::factory($this->_account->getId());
        if (preg_match('/dbmail/i', $sieveBackend->getImplementation())) {
            $translate = Tinebase_Translation::getTranslation('Felamimail');
            $vacationData['subject'] = sprintf($translate->_('Out of Office reply from %1$s'), Tinebase_Core::getUser()->accountFullName);
        }

        foreach (array('reason', 'enabled', 'subject', 'from', 'days') as $field) {
            $this->assertEquals($vacationData[$field], $result[$field], 'vacation data mismatch: ' . $field);
        }

        $translation = Tinebase_Translation::getTranslation('Felamimail');
        $this->_assertHistoryNote($this->_account, $translation->_('Sieve vacation has been updated:') . ' ' .
            $translation->_('Vacation message is now active.'), Felamimail_Model_Account::class);
    }

    /**
     * get vacation data
     *
     * @return array
     */
    public static function getVacationData($_account)
    {
        return array(
            'id' => $_account->getId(),
            'subject' => 'unittest vacation subject',
            'from' => $_account->from . ' <' . $_account->email . '>',
            'days' => 3,
            'enabled' => TRUE,
            'reason' => 'unittest vacation message<br /><br />signature',
            'mime' => NULL,
        );
    }

    /**
     * test mime vacation sieve script
     */
    public function testMimeVacation()
    {
        $vacationData = self::getVacationData($this->_account);
        $vacationData['reason'] = "\n<html><body><h1>unittest vacation&nbsp;message</h1></body></html>";

        $_sieveBackend = Felamimail_Backend_SieveFactory::factory($this->_account->getId());
        if (!in_array('mime', $_sieveBackend->capability())) {
            $vacationData['mime'] = 'text/html';
        }

        $this->_sieveTestHelper($vacationData, true);
    }

    /**
     * test mime vacation sieve script (invalid namespace)
     */
    public function testMimeVacationWithInvalidNamespace()
    {
        $vacationData = self::getVacationData($this->_account);
        $vacationData['reason'] = '<p class="MsoNormal" style="font-family: tahoma; font-size: 11px;"><br></p><div style=""><span style="background: rgb(255, 255, 255);">
<p class="MsoNormal" style="font-family: tahoma; font-size: 11px; color: rgb(0, 0, 0);">Sehr geehrte Damen und Herren,&nbsp;</p><p class="MsoNormal" style="font-family: tahoma; font-size: 11px; color: rgb(0, 0, 0);">vielen Dank für Ihre Nachricht.<o:p></o:p></p>

<p class="MsoNormal" style="font-family: tahoma; font-size: 11px; color: rgb(0, 0, 0);">Ich mache bis zum 12.05.2023 Urlaub und werde anschließend Ihre Mail bearbeiten.&nbsp;<o:p></o:p></p>

<p class="MsoNormal" style="font-family: tahoma; font-size: 11px; color: rgb(0, 0, 0);">Meine E-Mails werden in meiner
Abwesenheit nicht gelesen und nicht weitergeleitet. unittest vacation<o:p></o:p></p>

<p class="MsoNormal" style="font-family: tahoma; font-size: 11px;">In dringenden Fällen wenden Sie
sich gerne an XXX unter <font color="#0000ff">mail@mail.de</font>&nbsp;oder 000<o:p></o:p></p>

    </span>';

        $_sieveBackend = Felamimail_Backend_SieveFactory::factory($this->_account->getId());
        if (!in_array('mime', $_sieveBackend->capability())) {
            $vacationData['mime'] = 'text/html';
        }

        $this->_sieveTestHelper($vacationData, true);
    }

    /**
     * test get/set of rules sieve script
     */
    public function testGetSetRules()
    {
        $ruleData = $this->_getRuleData();

        $this->_sieveTestHelper($ruleData);

        // check getRules
        $result = $this->_json->getRules($this->_account->getId());
        $this->assertEquals($result['totalcount'], count($ruleData));

        // check by sending mail
        $messageData = $this->_getMessageData('', 'viagra');
        $this->_json->saveMessage($messageData);
        $this->_foldersToClear = array('INBOX', $this->_testFolderName);
        // check if message is in test folder
        $this->_searchForMessageBySubject($messageData['subject'], $this->_testFolderName);

        $translation = Tinebase_Translation::getTranslation('Felamimail');
        $this->_assertHistoryNote($this->_account, $translation->_('Sieve rules have been updated.'), Felamimail_Model_Account::class);
    }

    /**
     * testRemoveRules
     *
     * @see 0006490: can not delete single filter rule
     */
    public function testRemoveRules()
    {
        $this->testGetSetRules();
        $this->_json->saveRules($this->_account->getId(), array());

        $result = $this->_json->getRules($this->_account->getId());
        $this->assertEquals(0, $result['totalcount'], 'found rules: ' . print_r($result, TRUE));
    }

    /**
     * get sieve rule data
     *
     * @return array
     */
    protected function _getRuleData()
    {
        return array(array(
            'id' => 1,
            'action_type' => Felamimail_Sieve_Rule_Action::FILEINTO,
            'action_argument' => $this->_testFolderName,
            'conjunction' => 'allof',
            'conditions' => array(array(
                'test' => Felamimail_Sieve_Rule_Condition::TEST_ADDRESS,
                'comperator' => Felamimail_Sieve_Rule_Condition::COMPERATOR_CONTAINS,
                'header' => 'From',
                'key' => '"abcd" <info@example.org>',
            )),
            'enabled' => 1,
        ), array(
            'id' => 2,
            'action_type' => Felamimail_Sieve_Rule_Action::FILEINTO,
            'action_argument' => $this->_testFolderName,
            'conjunction' => 'allof',
            'conditions' => array(array(
                'test' => Felamimail_Sieve_Rule_Condition::TEST_ADDRESS,
                'comperator' => Felamimail_Sieve_Rule_Condition::COMPERATOR_CONTAINS,
                'header' => 'From',
                'key' => 'info@example.org',
            )),
            'enabled' => 0,
        ), array(
            'id' => 3,
            'action_type' => Felamimail_Sieve_Rule_Action::FILEINTO,
            'action_argument' => $this->_testFolderName,
            'conjunction' => 'allof',
            'conditions' => array(array(
                'test' => Felamimail_Sieve_Rule_Condition::TEST_HEADER,
                'comperator' => Felamimail_Sieve_Rule_Condition::COMPERATOR_REGEX,
                'header' => 'subject',
                'key' => '[vV]iagra|cyalis',
            )),
            'enabled' => 1,
        ));
    }

    /**
     * test to set a forward rule to this accounts email address
     * -> should throw exception to prevent mail cycling
     */
    public function testSetForwardRuleToSelf()
    {
        $ruleData = $this->_getRedirectRuleData(array(
            'emails' => $this->_account->email,
            'copy' => 0,
        ));

        try {
            $this->_sieveTestHelper($ruleData);
            $this->assertTrue(FALSE, 'it is not allowed to set own email address for redirect!');
        } catch (Felamimail_Exception_Sieve $e) {
            $this->assertTrue(TRUE);
        }

        // this should work
        $ruleData[0]['enabled'] = 0;
        $this->_sieveTestHelper($ruleData);
    }

    /**
     * @see 0006222: Keep a copy from mails forwarded to another emailaddress
     */
    public function testSetForwardRuleWithCopy()
    {
        $ruleData = $this->_getRedirectRuleData(array(
            'emails' => 'someaccount@' . $this->_mailDomain,
            'copy' => 1,
        ));
        $this->_sieveTestHelper($ruleData);
    }

    public function testSetSieveRuleValidationFail()
    {
        $this->expectException(Tinebase_Exception_Record_Validation::class);
        $this->expectExceptionMessage('Some fields action_argument have invalid content');
        $this->_json->saveRules($this->_account->getId(), $this->_getRedirectRuleData(array(
            'emails' => join('', array_fill(0, 255, 'a')) . '@' . $this->_mailDomain,
            'copy' => 0,
        )));
    }

    /**
     * @see 0006222: Keep a copy from mails forwarded to another emailaddress
     */
    public function testSetForwardRuleWithoutCopy()
    {
        $ruleData = $this->_getRedirectRuleData(array(
            'emails' => 'someaccount@' . $this->_mailDomain,
            'copy' => 0,
        ));
        $this->_sieveTestHelper($ruleData);
    }

    /**
     * should throw an exception with FEATURE_SIEVE_RULE_PREVENT_EXTERNAL_FORWARD
     */
    public function testSetForwardRuleToExternal()
    {
        Felamimail_Config::getInstance()->set(Felamimail_Config::SIEVE_REDIRECT_ONLY_INTERNAL, true);

        $ruleData = $this->_getRedirectRuleData(array(
            'emails' => 'someaddress@external.com',
            'copy' => 0,
        ));
        try {
            $this->_sieveTestHelper($ruleData);
            $this->assertTrue(FALSE,
                'It is not allowed to set external email address for redirect (with FEATURE_SIEVE_RULE_PREVENT_EXTERNAL_FORWARD)!');
        } catch (Felamimail_Exception_Sieve $e) {
            $this->assertTrue(TRUE);
            $translate = Tinebase_Translation::getTranslation('Felamimail');
            self::assertEquals($translate->_('Redirects to external email domains are not allowed.'), $e->getMessage());
        }
    }

    protected function _getRedirectRuleData($actionArgument)
    {
        return array(array(
            'id' => '1',
            'action_type' => Felamimail_Sieve_Rule_Action::REDIRECT,
            'action_argument' => $actionArgument,
            'conjunction' => 'allof',
            'conditions' => array(array(
                'test' => Felamimail_Sieve_Rule_Condition::TEST_ADDRESS,
                'comperator' => Felamimail_Sieve_Rule_Condition::COMPERATOR_CONTAINS,
                'header' => 'From',
                'key' => 'info@example.org',
            )),
            'enabled' => 1,
        ));
    }

    /**
     * testGetVacationTemplates
     *
     * @return array
     */
    public function testGetVacationTemplates()
    {
        $this->_addVacationTemplateFile();
        $result = $this->_json->getVacationMessageTemplates();

        $this->assertTrue($result['totalcount'] > 0, 'no templates found');
        $found = FALSE;
        foreach ($result['results'] as $template) {
            if ($template['name'] === $this->_sieveVacationTemplateFile) {
                $found = TRUE;
                break;
            }
        }

        $this->assertTrue($found, 'wrong templates: ' . print_r($result['results'], TRUE));

        return $template;
    }

    /**
     * add vacation template file to vfs
     */
    protected function _addVacationTemplateFile()
    {
        $webdavRoot = new DAV\Tree(new Tinebase_WebDav_Root());
        $path = '/webdav/Felamimail/shared/Vacation Templates';
        $node = $webdavRoot->getNodeForPath($path);
        $this->_pathsToDelete[] = $path . '/' . $this->_sieveVacationTemplateFile;
        $node->createFile($this->_sieveVacationTemplateFile, fopen(dirname(__FILE__) . '/../files/' . $this->_sieveVacationTemplateFile, 'r'));
    }

    /**
     * testGetVacationMessage
     */
    public function testGetVacationMessage()
    {
        $result = $this->_getVacationMessageWithTemplate();
        $sclever = Tinebase_User::getInstance()->getFullUserByLoginName('sclever');
        $pwulf = Tinebase_User::getInstance()->getFullUserByLoginName('pwulf');
        $this->assertEquals("Ich bin vom 18.04.2012 bis zum 20.04.2012 im Urlaub. Bitte kontaktieren Sie" . 
        "<br /> Paul Wulf (pwulf@mail.test) +441273-3766-376 oder Susan Clever (sclever@mail.test) +441273-3766-373.<br />" . 
        "<br />I am on vacation until Apr 20, 2012. Please contact" .
        "<br /> Paul Wulf (pwulf@mail.test) +441273-3766-376 or Susan Clever (sclever@mail.test) +441273-3766-373 instead.<br /><br />" .
            Addressbook_Controller_Contact::getInstance()->getContactByUserId(Tinebase_Core::getUser()->getId())->n_fn . "<br />", 
            $result['message'],
        );
    }

    /**
     * testGetVacationMessage
     */
    public function testGetVacationMessageWithoutContacts()
    {
        $template = $this->testGetVacationTemplates();
        $result = $this->_json->getVacationMessage(array(
            'start_date' => '2012-04-18',
            'end_date' => '2012-04-20',
            'contact_ids' => array(
            ),
            'template_id' => $template['id'],
            'signature' => $this->_account->signature
        ));

        $this->assertEquals("Ich bin vom 18.04.2012 bis zum 20.04.2012 im Urlaub. Bitte kontaktieren Sie andere Kollegen.<br /><br /><br />" .
            "I am on vacation until Apr 20, 2012. Please contact other colleagues.<br /><br /><br />" .
            Addressbook_Controller_Contact::getInstance()->getContactByUserId(Tinebase_Core::getUser()->getId())->n_fn . "<br />",
            $result['message'],
        );
    }


    /**
     * get vacation message with template
     *
     * @return array
     */
    protected function _getVacationMessageWithTemplate()
    {
        $template = $this->testGetVacationTemplates();
        $sclever = Tinebase_User::getInstance()->getFullUserByLoginName('sclever');
        $result = $this->_json->getVacationMessage(array(
            'start_date' => '2012-04-18',
            'end_date' => '2012-04-20',
            'contact_ids' => array(
                Tinebase_User::getInstance()->getFullUserByLoginName('pwulf')->contact_id,
                $sclever->contact_id,
            ),
            'template_id' => $template['id'],
            'signature' => $this->_account->signature
        ));

        return $result;
    }

    /**
     * testGetVacationWithSignature
     *
     * @see 0006866: check signature linebreaks in vacation message from template
     */
    public function testGetVacationWithSignature()
    {
        $this->_sieveVacationTemplateFile = 'vacation_template_sig.tpl';

        // set signature with <br> + linebreaks
        $this->_account->signature = "llalala<br>\nxyz<br>\nblubb<br>";

        $result = $this->_getVacationMessageWithTemplate();
        $this->assertStringContainsString('-- <br />llalala<br />xyz<br />blubb<br />', $result['message'], 'wrong linebreaks or missing signature');
    }

    /**
     * testSetVacationWithStartAndEndDate
     *
     * @see 0006266: automatic deactivation of vacation message
     */
    public function testSetVacationWithStartAndEndDate()
    {
        $vacationData = self::getVacationData($this->_account);
        $vacationData['start_date'] = '2012-04-18';
        $vacationData['end_date'] = '2012-04-20';
        $result = $this->_sieveTestHelper($vacationData);
        $sieveBackend = Felamimail_Backend_SieveFactory::factory($this->_account->getId());
        $sieveScriptRules = $sieveBackend->getScript($this->_testSieveScriptName);

        $this->assertStringContainsString($vacationData['start_date'], $result['start_date']);
        $this->assertStringContainsString('currentdate', $sieveScriptRules);
    }

    public function testSetLongVacation()
    {
        $vacationData = self::getVacationData($this->_account);
        $vacationData['reason'] = 'Sehr geehrte Damen und Herren,<br><br>vielen Dank für Ihre E-Mail.<br><br>Ich bin bis einschließlich den 27.03.2023 nicht zu erreichen. '
            . '<br>Bitte wenden Sie sich während meiner Abwesenheit an xasxsaxsa sdvccds, x.xxxxxxx@masasxas.de, <br>Tel. +4911111111111 oder verwenden Sie unser Ticket System,'
            . ' asxasxs@masxasxs.net.<br> <br>Ihre E-Mail wird nicht weitergeleitet.<br><br>Mit freundlichen Grüßen<br>xxxa aaaaaaaaaaaa<br><br>--<br><br>Dear Sir or Madam,<br>'
            . '<br>Thank you for your e-mail.<br><br>I am not reachable until Mar 27, 2023.<br>During my absence please contact asxasxasx vsvsdvd, x.asxasxs@sdvcsdss.de,<br>'
            . 'phone +4921221212222 or use our ticket system, asxasxs@aassssys.net. <br>Your e-mail will not be forwarded.<br><br>Best regards<br>xxxx dfvdfvdfvfff<br><br>-- '
            . '<br><br>asxa xasxaxsxxxxx <br>Metaways Infosystems GmbH <br>Pickhuben 2, D-20457 Hamburg <br><br>E-Mail: x.asxasxasxsas@ssssssss.de<br>Web: http://www.metaways.de'
            . '<br>Tel: +49 (0)40 111112-222 <br>Fax: +49 (0)11 212122-222<br>Mobile: +49 (0)1222 1212-222 <br><br>Metaways Infosystems GmbH - Sitz: D-22967 Tremsbüttel <br>'
            . 'Handelsregister: Amtsgericht Lübeck HRB 4508 AH<br>Geschäftsführung: Hermann Thaele, Lüder-H.Thaele<br><br><br>';
        $this->_sieveTestHelper($vacationData, false, "Fax: +49 (0)11 212122-222\r\nMobile: +49 (0)1222 1212-222 ");
    }

    /**
     * testSieveRulesOrder
     *
     * @see 0007240: order of sieve rules changes when vacation message is saved
     */
    public function testSieveRulesOrder()
    {
        $this->_setTestScriptname();

        // disable vacation first
        $this->_setDisabledVacation();

        $sieveBackend = Felamimail_Backend_SieveFactory::factory($this->_account->getId());

        $ruleData = $this->_getRuleData();
        $ruleData[0]['id'] = $ruleData[2]['id'];
        $ruleData[2]['id'] = 11;
        $this->_json->saveRules($this->_account->getId(), $ruleData);
        $sieveScriptRules = $sieveBackend->getScript($this->_testSieveScriptName);

        $this->_setDisabledVacation();
        $sieveScriptVacation = $sieveBackend->getScript($this->_testSieveScriptName);

        // compare sieve scripts
        $this->assertStringContainsString($sieveScriptRules, $sieveScriptVacation, 'rule order changed');
    }

    /**
     * @group nogitlabciad
     */
    public function testSieveEmailNotification()
    {
        $this->_setTestScriptname();

        $this->_account->sieve_notification_email = 'test@test.de';
        $this->_account->sieve_notification_move = false;
        Felamimail_Controller_Account::getInstance()->update($this->_account);

        $script = new Felamimail_Sieve_Backend_Sql($this->_account->getId());
        $scriptParts = $script->getScriptParts();
        
        static::assertGreaterThan(0, $scriptParts->count(), 'at least 1 script part expected. script: '
            . $script->getSieve() . ' parts: '
            . print_r($scriptParts->toArray(), true)
        );
        
        foreach ($scriptParts as $scriptPart) {
            if ($scriptPart['type'] === Felamimail_Model_Sieve_ScriptPart::TYPE_NOTIFICATION) {
                $requires = ['"enotify"', '"variables"', '"copy"', '"body"'];
            }
            
            if ($scriptPart['type'] === Felamimail_Model_Sieve_ScriptPart::TYPE_AUTO_MOVE_NOTIFICATION) {
                $requires = ['"fileinto"', '"mailbox"'];
                static::assertStringContainsString('test@test.de', $script->getSieve());
            }

            static::assertTrue(count(array_intersect($requires, $scriptPart->xprops(Felamimail_Model_Sieve_ScriptPart::XPROPS_REQUIRES))) === sizeof($requires),
                print_r($scriptPart->xprops(Felamimail_Model_Sieve_ScriptPart::XPROPS_REQUIRES), true));
        }
    }

    /**
     * @group nogitlabciad
     */
    public function testSieveEmailNotificationMultiple()
    {
        $this->_setTestScriptname();

        $this->_account->sieve_notification_email = 'test@test.de,test2@test.de';
        $this->_account->sieve_notification_move = false;
        Felamimail_Controller_Account::getInstance()->update($this->_account);

        $script = new Felamimail_Sieve_Backend_Sql($this->_account->getId());
        
        static::assertStringContainsString('test@test.de', $script->getSieve());
        static::assertStringContainsString('test2@test.de', $script->getSieve());
        static::assertStringContainsString(':from "noreply@mail.test"', $script->getSieve());
    }

    /**
     * use another name for test sieve script
     */
    protected function _setTestScriptname()
    {
        $this->_oldActiveSieveScriptName = Felamimail_Controller_Sieve::getInstance()->getActiveScriptName($this->_account->getId());
        $this->_testSieveScriptName = 'Felamimail_Unittest';
        Felamimail_Controller_Sieve::getInstance()->setScriptName($this->_testSieveScriptName);
    }

    /**
     * set disabled vacation message
     */
    protected function _setDisabledVacation()
    {
        $vacationData = self::getVacationData($this->_account);
        $vacationData['enabled'] = FALSE;
        $this->_json->saveVacation($vacationData);
    }

    /**
     * search preferences by application felamimail
     *
     */
    public function testSearchFelamimailPreferences()
    {
        // search prefs
        $tfj = new Tinebase_Frontend_Json();
        $result = $tfj->searchPreferencesForApplication('Felamimail', '');

        // check results
        $this->assertTrue(isset($result['results']));
        $this->assertGreaterThan(0, $result['totalcount']);
    }

    /**
     * testGetRegistryData
     *
     * @see 0010251: do not send unused config data to client
     */
    public function testGetRegistryData()
    {
        $regData = $this->_json->getRegistryData();

        $this->assertFalse(isset($regData['defaults']));
        $this->assertFalse(isset($regData['accounts']));
        $supportedFlags = Felamimail_Config::getInstance()->featureEnabled(Felamimail_Config::FEATURE_TINE20_FLAG)
            ? 7
            : 6;
        $this->assertEquals($supportedFlags, $regData['supportedFlags']['totalcount']);
    }

    /**
     * @see 0002284: add reply-to setting to email account
     */
    public function testReplyToSetting()
    {
        $this->_account->reply_to = 'noreply@tine20.org';
        $this->_json->saveAccount($this->_account->toArray());

        $this->_foldersToClear[] = 'INBOX';
        $messageToSend = $this->_getMessageData();
        $this->_json->saveMessage($messageToSend);
        $message = $this->_searchForMessageBySubject($messageToSend['subject']);

        $complete = $this->_json->getMessage($message['id']);
        $this->assertTrue(isset($complete['headers']['reply-to']), print_r($complete, true));
        $this->assertEquals('"' . $complete['from_name'] . '" <noreply@tine20.org>', $complete['headers']['reply-to']);
    }

    /**
     * @see https://github.com/tine20/tine20/issues/2172
     */
    public function testReplyToInMessage()
    {
        $this->_account->reply_to = 'noreply@tine20.org';
        $this->_json->saveAccount($this->_account->toArray());

        $this->_foldersToClear[] = 'INBOX';
        $messageToSend = $this->_getMessageData();
        $messageToSend['reply_to'] = 'donotreply@tine20.org';
        $this->_json->saveMessage($messageToSend);
        $message = $this->_searchForMessageBySubject($messageToSend['subject']);

        $complete = $this->_json->getMessage($message['id']);
        $this->assertTrue(isset($complete['headers']['reply-to']), print_r($complete, true));
        $this->assertEquals('"' . $complete['from_name'] . '" <donotreply@tine20.org>', $complete['headers']['reply-to']);
    }

    /**
     * Its possible to choice the kind of attachment when adding it.
     *
     * type = tempfile: uploaded from harddisk, supposed to be a regular attachment
     *
     * @see 0012950: More attachment methods for mail
     */
    public function testAttachmentMethodAttachment()
    {
        $message = $this->_testAttachmentType('tempfile');

        self::assertTrue(isset($message['attachments']), 'no attachment set: ' . print_r($message, true));
        self::assertEquals(1, count($message['attachments']), 'no attachment set: ' . print_r($message, true));
        self::assertEquals('foobar1.txt', $message['attachments'][0]['filename']);
        self::assertEquals(24, $message['attachments'][0]['size']);
    }

    /**
     * attach winmail.dat without contents -> attachments should be empty!
     */
    public function testEmptyWinmailAttachment()
    {
        $messageToSend = $this->_getMessageData('' , __METHOD__);
        $tempFile = $this->_getTempFile(dirname(__FILE__) . '/../files/empty_winmail.dat', 'winmail.dat', 'application/ms-tnef');
        $messageToSend['attachments'] = array(
            array('tempFile' => array('id' => $tempFile->getId(), 'type' => 'application/ms-tnef'))
        );
        $this->_json->saveMessage($messageToSend);
        $message = $this->_searchForMessageBySubject($messageToSend['subject']);
        $complete = $this->_json->getMessage($message['id']);
        self::assertEquals(0, count($complete['attachments']));
    }

    /**
     * Its possible to choice the kind of attachment when adding it.
     *
     * type = download_public: uploaded from harddisk, supposed to be a public download link
     *
     * @see 0012950: More attachment methods for mail
     */
    public function testAttachmentMethodPublicDownloadLinkUpload()
    {
        Tinebase_Core::setLocale('en');
        $message = $this->_testAttachmentType('download_public');

        self::assertTrue(isset($message['attachments']), 'attachment set: ' . print_r($message, true));
        self::assertEquals(0, count($message['attachments']), 'attachment set: ' . print_r($message, true));
        self::assertStringContainsString('/download', $message['body'], 'no download link in body: ' . print_r($message, true));
    }

    /**
     * Its possible to choice the kind of attachment when adding it.
     *
     * type = download_public_fm: chosen from fm, supposed to be a public download link
     *
     * @see 0012950: More attachment methods for mail
     */
    public function testAttachmentMethodPublicDownloadLinkFromFilemanager()
    {
        $message = $this->_testAttachmentType('download_public_fm');

        self::assertTrue(isset($message['attachments']), 'attachment set: ' . print_r($message, true));
        self::assertEquals(0, count($message['attachments']), 'attachment set: ' . print_r($message, true));
        self::assertStringContainsString('/download', $message['body'], 'no download link in body: ' . print_r($message, true));
    }

    /**
     * Its possible to choice the kind of attachment when adding it.
     *
     * type = download_protected: uploaded from harddisk, supposed to be a protected download link
     *
     * @see 0012950: More attachment methods for mail
     */
    public function testAttachmentMethodProtectedDownloadLink()
    {
        Tinebase_Core::setLocale('en');
        $message = $this->_testAttachmentType('download_protected');

        self::assertTrue(isset($message['attachments']), 'attachment set: ' . print_r($message, true));
        self::assertEquals(0, count($message['attachments']), 'attachment set: ' . print_r($message, true));
        self::assertStringContainsString('/download', $message['body'], 'no download link in body: ' . print_r($message, true));
        self::assertStringContainsString('</a>', $message['body'],
            'link has no anchor tag: ' . $message['body']);

        // download link id is at the end of message body
        if (preg_match('@download/show/([a-z0-9]+)"@', $message['body'], $matches)) {
            $dl = Filemanager_Controller_DownloadLink::getInstance()->get($matches[1]);
            self::assertTrue(Filemanager_Controller_DownloadLink::getInstance()->validatePassword($dl, 'test'));
        } else {
            self::fail('no download link found in message: ' . print_r($message, true));
        }
    }

    /**
     * Its possible to choice the kind of attachment when adding it.
     *
     * type = filenode: chosen from fm, thats why type -> file, but the filemanager file
     *  is supposed to be used as a regular attachment
     *
     * @see 0012950: More attachment methods for mail
     */
    public function testAttachmentMethodFilemanagerNode()
    {
        $message = $this->_testAttachmentType('filenode');

        self::assertTrue(isset($message['attachments']), 'no attachment set: ' . print_r($message, true));
        self::assertEquals(1, count($message['attachments']), 'no attachment set: ' . print_r($message, true));
        self::assertEquals('test.txt', $message['attachments'][0]['filename']);
        if (PHP_VERSION_ID >= 70400) {
            self::assertEquals(24, $message['attachments'][0]['size']);
        } else {
            self::assertEquals(20, $message['attachments'][0]['size']);
        }
    }

    public function testAttachmentMethodFilemanagerSystemLink()
    {
        // make sure, we have a signature in the account
        $this->_account->signatures = [[
            'name' => 'signature',
            'signature' => 'my signature',
            'is_default' => 1,
        ]];
        Felamimail_Controller_Account::getInstance()->update($this->_account);

        $message = $this->_testAttachmentType('systemlink_fm', true);
        self::assertStringContainsString('testcontainer/test.txt', $message['body'],
            'system link missing from body - ' . print_r($message, true));
        // check if
        self::assertGreaterThan(
            strpos($message['body'], 'test.txt'),
            strpos($message['body'], 'my signature'),
            'file link should be above signature: '
            . print_r($message['body'], true)
        );
    }

    /**
     * @param $type
     * @param boolean $withSignature
     * @return array
     *
     * @throws Filemanager_Exception_NodeExists
     * @throws Tinebase_Exception_InvalidArgument
     */
    protected function _testAttachmentType($type, $withSignature = false)
    {
        $this->_foldersToClear = array('INBOX', $this->_account->sent_folder);
        $tempfile = $this->_getTempFile(null, 'foobar1.txt');

        if (in_array($type, array('tempfile', 'download_public', 'download_protected'))) {
            // attach uploaded tempfile
            $attachment = array(
                'tempFile' => $tempfile->toArray(),
                'name' => 'foobar1.txt',
                'size' => $tempfile->size,
                'type' => 'text/plain',
                'id' => 'eeabe57fd3712a9fe27a34df07cb44cab9e9afb3',
                'attachment_type' => $type,
            );

        } elseif (in_array($type, array('filenode', 'download_public_fm', 'download_protected_fm', 'systemlink_fm'))) {
            // attach existing file from filemanager
            $nodeController = Filemanager_Controller_Node::getInstance();
            $testPath = '/' . Tinebase_FileSystem::FOLDER_TYPE_PERSONAL . '/' . Tinebase_Core::getUser()->accountLoginName . '/testcontainer';
            $result = $nodeController->createNodes($testPath, Tinebase_Model_Tree_FileObject::TYPE_FOLDER, array(), FALSE);
            $personalNode = $result[0];
            $filepath = $personalNode->path . 'test.txt';

            // create empty file first (like the js frontend does)
            $nodeController->createNodes($filepath, Tinebase_Model_Tree_FileObject::TYPE_FILE, array(), FALSE);
            $fmFile = $nodeController->createNodes(
                $filepath,
                Tinebase_Model_Tree_FileObject::TYPE_FILE,
                (array)$tempfile->getId(), TRUE
            )->getFirstRecord();

            $attachment = [ // chosen from fm, thats why type -> file, but the filemanager file is supposed to be used as a regular attachment
                'name' => $fmFile->name,
                'path' => $fmFile->path,
                'size' => $fmFile->size,
                'type' => $fmFile->contenttype,
                'id' => $fmFile->getId(),
                'attachment_type' => $type,
            ];

        } else {
            throw new Tinebase_Exception_InvalidArgument('invalid type given');
        }

        if (in_array($type, array('download_protected_fm', 'download_protected'))) {
            $attachment['password'] = 'test';
        }

        $body = 'foobar';
        if ($withSignature) {
            $body .= '<br><br><span class="felamimail-body-signature">-- <br>'
                . $this->_account->signatures[0]['signature'] . '</span>';
        }

        $messageToSend = [
            'content_type' => 'text/html',
            'account_id' => $this->_account->getId(),
            'to' => [
                $this->_account->email
            ],
            'cc' => [],
            'bcc' => [],
            'subject' => 'attachment test [' . $type . ']',
            'body' => $body,
            'attachments' => [$attachment],
            'from_email' => 'vagrant@example.org',
            'customfields' => [],
            'headers' => array('X-Tine20TestMessage' => 'jsontest'),
        ];

        $this->_json->saveMessage($messageToSend);
        $message = $this->_searchForMessageBySubject($messageToSend['subject']);
        $complete = $this->_json->getMessage($message['id']);

        return $complete;
    }

    public function testGetMessageFromNode()
    {
        $result = $this->_createTestNode(
            'test.eml',
            dirname(__FILE__) . '/../files/multipart_related.eml'
        );

        $message = $this->_assertMessageFromNode($result[0]['id']);
        self::assertEquals(34504, $message['attachments'][0]['size']);
    }

    protected function _assertMessageFromNode(string $id): array
    {
        $message = $this->_json->getMessageFromNode($id);
        self::assertEquals('Christof Gacki', $message['from_name']);
        self::assertEquals('c.gacki@metaways.de', $message['from_email']);
        self::assertStringContainsString('wie gestern besprochen würde mich sehr freuen', $message['body']);
        self::assertEquals(Zend_Mime::TYPE_HTML, $message['body_content_type'], $message['body']);
        self::assertTrue(isset($message['attachments']), 'no attachments found');
        self::assertEquals(1, count($message['attachments']));
        self::assertEquals(0, $message['attachments'][0]['partId']);
        self::assertEquals('image/png', $message['attachments'][0]['content-type']);
        self::assertEquals('moz-screenshot-83.png', $message['attachments'][0]['filename']);
        self::assertInstanceOf(ZBateson\MailMimeParser\Stream\MessagePartStreamDecorator::class,
            $message['attachments'][0]['contentstream']);
        self::assertEquals('2010-05-05 16:25:40', $message['sent']);
        self::assertEquals($id, $message['id']);
        return $message;
    }

    public function testGetMessageFromNodeMsg()
    {
        $result = $this->_createTestNode(
            'test.msg',
            dirname(__FILE__) . '/../files/multipart_related_recipients.msg'
        );

        $message = $this->_assertMessageFromNode($result[0]['id']);
        self::assertEquals(2, count($message['cc']));
        self::assertEquals('c.weiss@metaways.de', $message['cc'][0]['email']);
        self::assertEquals('name@example.com', $message['cc'][1]['email']);
        self::assertEquals(35563, $message['attachments'][0]['size']);
    }

    /**
     * testGetFileSuggestionsSender
     */
    public function testGetFileSuggestionsSender()
    {
        $message = $this->_sendMessage();
        $result = $this->_json->getFileSuggestions($message);

        self::assertGreaterThanOrEqual(1, count($result));

        $senders = array_filter($result, function ($suggestion) {
            if ($suggestion['type'] === Felamimail_Model_MessageFileSuggestion::TYPE_SENDER) {
                return true;
            }
        });
        self::assertGreaterThanOrEqual(1, count($senders), 'did not get sender');
        $suggestion = array_pop($senders);

        self::assertTrue(isset($suggestion['record']));
        self::assertEquals($message['from_email'], $suggestion['record']['email']);
        self::assertTrue(isset($suggestion['model']));
        self::assertEquals(Addressbook_Model_Contact::class, $suggestion['model']);
    }

    /**
     * testGetFileSuggestionsLocation
     */
    public function testGetFileSuggestionsLocation()
    {
        $message = $this->testFileMessagesAsNode();
        $result = $this->_json->getFileSuggestions($message);

        self::assertGreaterThanOrEqual(2, count($result));

        $locations = array_filter($result, function ($suggestion) {
            if ($suggestion['type'] === Felamimail_Model_MessageFileSuggestion::TYPE_FILE_LOCATION) {
                return true;
            }
        });
        self::assertGreaterThanOrEqual(1, count($locations), 'did not get location');
        $suggestion = array_pop($locations);

        self::assertTrue(isset($suggestion['record']));
        self::assertTrue(isset($suggestion['model']));
        self::assertEquals(Felamimail_Model_MessageFileLocation::class, $suggestion['model']);
        self::assertEquals(Filemanager_Model_Node::class, $suggestion['record']['model']);
        self::assertStringContainsString(str_replace('%s', '',
            Tinebase_Translation::getDefaultTranslation()->_('%s\'s personal files')),
            $suggestion['record']['record_title']);
    }

    /**
     * testGetFileSuggestionsRecipient
     */
    public function testGetFileSuggestionsRecipient()
    {
        $message = [
            'to' => [
                Tinebase_Core::getUser()->accountEmailAddress
            ]
        ];
        $result = $this->_json->getFileSuggestions($message);
        $recipients = array_filter($result, function ($suggestion) {
            if ($suggestion['type'] === Felamimail_Model_MessageFileSuggestion::TYPE_RECIPIENT) {
                return true;
            }
        });
        self::assertGreaterThanOrEqual(1, count($recipients), 'did not get recipients');
        $suggestion = array_pop($recipients);

        self::assertTrue(isset($suggestion['record']));
        self::assertEquals(Tinebase_Core::getUser()->accountEmailAddress, $suggestion['record']['email'], print_r($suggestion['record'], true));
        self::assertTrue(isset($suggestion['model']));
        self::assertEquals(Addressbook_Model_Contact::class, $suggestion['model']);
    }

    /**
     * testGetFileSuggestionsOnCompose
     */
    public function testGetFileSuggestionsOnCompose()
    {
        $message = $this->testFileMessageAsAttachment();
        $messageToSend = [
            'to' => [
                Tinebase_Core::getUser()->accountEmailAddress
            ],
            'original_id' => $message['id'],
        ];
        $result = $this->_json->getFileSuggestions($messageToSend);
        $recipients = array_filter($result, function ($suggestion) {
            if ($suggestion['type'] === Felamimail_Model_MessageFileSuggestion::TYPE_RECIPIENT) {
                return true;
            }
        });
        // assert no (original) recipients
        self::assertGreaterThanOrEqual(0, count($recipients), 'should have no recipients');

        // also check location
        $locations = array_filter($result, function ($suggestion) {
            if ($suggestion['type'] === Felamimail_Model_MessageFileSuggestion::TYPE_FILE_LOCATION) {
                return true;
            }
        });
        self::assertGreaterThanOrEqual(1, count($locations), 'did not get location in suggestions: '
            . print_r($result, true));
        $suggestion = array_pop($locations);
        self::assertEquals(Felamimail_Model_MessageFileLocation::class, $suggestion['model']);
        self::assertEquals(Addressbook_Model_Contact::class, $suggestion['record']['model']);
        self::assertEquals(Tinebase_Core::getUser()->accountFullName, $suggestion['record']['record_title']);
    }

    /**
     * @param null|array $message
     * @return array|null
     */
    public function testFileMessageAsAttachment($message = null)
    {
        if (!$message) {
            $message = $this->_sendMessage();
        }
        // file message at current contact
        $filter = [[
            'field' => 'id', 'operator' => 'in', 'value' => [$message['id']]
        ]];
        $result = $this->_json->fileMessages($filter, [
            [
                'model' => Addressbook_Model_Contact::class,
                'record_id' => Addressbook_Controller_Contact::getInstance()->getContactByUserId(Tinebase_Core::getUser()->getId()),
                'type' => Felamimail_Model_MessageFileLocation::TYPE_ATTACHMENT
            ]
        ]);
        $this->assertTrue(isset($result['totalcount']));
        $this->assertEquals(1, $result['totalcount'], 'message should be filed in contact '
            . print_r($result, true));

        // check if message is attached to contact
        $contact = Addressbook_Controller_Contact::getInstance()->getContactByUserId(Tinebase_Core::getUser()->getId());
        $attachments = Tinebase_FileSystem_RecordAttachments::getInstance()->getRecordAttachments($contact);
        self::assertEquals(1, count($attachments), print_r($contact->toArray(), true));
        // check if email note is generated
        $notes = Tinebase_Notes::getInstance()->getNotesOfRecord(Addressbook_Model_Contact::class, $contact->getId());
        self::assertEquals(1, count($notes), 'record has no notes');
        $note = $notes->getFirstRecord();
        self::assertEquals(Tinebase_Model_Note::SYSTEM_NOTE_NAME_EMAIL, $note->note_type_id,
            '3 is email type ' . print_r($note->toArray(), true));
        return $message;
    }
    
    public function testFileMessageWithoutEditGrant()
    {
        Addressbook_Controller_Contact::getInstance()->doContainerACLChecks(true);
        Tinebase_Core::setUser($this->_originalTestUser);
        $sclever = $this->_personas['sclever'];
        $container = $this->_getTestContainer('Addressbook', 'Addressbook_Model_Contact');
        Tinebase_Container::getInstance()->addGrants($container, Tinebase_Acl_Rights::ACCOUNT_TYPE_USER,
            $sclever->getId(), [Tinebase_Model_Grants::GRANT_READ]);

        $contact = Addressbook_Controller_Contact::getInstance()->create(new Addressbook_Model_Contact([
            'n_given' => 'max',
            'n_family' => 'testContact',
            'container_id' => $container['id']
        ]));
        Tinebase_Core::setUser($sclever);
        $this->_account = Admin_Controller_EmailAccount::getInstance()->getSystemAccount($sclever);
        try {
            $message = $this->_sendMessage();
        } catch (Tinebase_Exception_SystemGeneric $tesg) {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(
                __METHOD__ . '::' . __LINE__ . ' ' . $tesg->getMessage());
            self::markTestSkipped('sclever email account missing');
        }

        $filter = [[
            'field' => 'id', 'operator' => 'in', 'value' => [$message['id']]
        ]];
        self::expectException(Tinebase_Exception_AccessDenied::class);
        
        $this->_json->fileMessages($filter, [
            [
                'model' => Addressbook_Model_Contact::class,
                'record_id' => $contact,
                'type' => Felamimail_Model_MessageFileLocation::TYPE_ATTACHMENT
            ]
        ]);
        Tinebase_Core::setUser($this->_originalTestUser);
    }

    public function testFileMessageInvalid()
    {
        $message = $this->_sendMessage();
        // file message at current contact
        $filter = [[
            'field' => 'id', 'operator' => 'in', 'value' => [$message['id']]
        ]];
        // try to send with wrong param structure
        self::expectException(Tinebase_Exception_Record_NotAllowed::class);
        $this->_json->fileMessages($filter, [
            'model' => Addressbook_Model_Contact::class,
            'record_id' => Addressbook_Controller_Contact::getInstance()->getContactByUserId(Tinebase_Core::getUser()->getId()),
            'type' => Felamimail_Model_MessageFileLocation::TYPE_ATTACHMENT
        ]);
    }

    /**
     * @throws Addressbook_Exception_AccessDenied
     * @throws Addressbook_Exception_NotFound
     * @throws Tinebase_Exception_InvalidArgument
     *
     * @group nogitlabci
     */
    public function testFileMessageOnSend()
    {
        $message = $this->_getMessageData('' , __METHOD__);
        $message['fileLocations'] = [
            [
                'model' => Addressbook_Model_Contact::class,
                'record_id' => Addressbook_Controller_Contact::getInstance()->getContactByUserId(Tinebase_Core::getUser()->getId()),
                'type' => Felamimail_Model_MessageFileLocation::TYPE_ATTACHMENT
            ]
        ];
        $this->_sendMessage('INBOX', [],'', 'test', $message);
        // check if message is attached to contact
        $contact = Addressbook_Controller_Contact::getInstance()->getContactByUserId(Tinebase_Core::getUser()->getId());
        $attachments = Tinebase_FileSystem_RecordAttachments::getInstance()->getRecordAttachments($contact);
        self::assertEquals(1, count($attachments), 'attachments not found in contact: ' . print_r($contact->toArray(), true));
    }

    /**
     * @throws Addressbook_Exception_AccessDenied
     * @throws Addressbook_Exception_NotFound
     * @throws Tinebase_Exception_InvalidArgument
     *
     * @group nogitlabci
     */
    public function testFileMessageOnSendWithEmail()
    {
        $message = $this->_getMessageData();
        $message['fileLocations'] = [
            [
                // class does not exist on the server
                'model' => 'Addressbook_Model_EmailAddress',
                'record_id' => [
                    'email' => Addressbook_Controller_Contact::getInstance()->getContactByUserId(
                        Tinebase_Core::getUser()->getId())->email
                ],
                'type' => Felamimail_Model_MessageFileLocation::TYPE_ATTACHMENT
            ]
        ];
        $this->_sendMessage('INBOX', [],'', 'test', $message);
        // check if message is attached to contact
        $contact = Addressbook_Controller_Contact::getInstance()->getContactByUserId(Tinebase_Core::getUser()->getId());
        $attachments = Tinebase_FileSystem_RecordAttachments::getInstance()->getRecordAttachments($contact);
        self::assertEquals(1, count($attachments), 'attachments not found in contact: ' . print_r($contact->toArray(), true));
    }

    /**
     * call testFileMessageAsAttachment twice: duplicate exception is catched...
     */
    public function testFileMessageDuplicate()
    {
        $message = $this->testFileMessageAsAttachment();
        $this->testFileMessageAsAttachment($message);
    }

    /**
     * testGetFileLocationsOfMessages
     */
    public function testGetFileLocationsOfMessages()
    {
        $message = $this->testFileMessageAsAttachment();

        // check search
        $filter = array(array(
            'field' => 'id', 'operator' => 'in', 'value' => array($message['id'])
        ));
        $result = $this->_json->searchMessages($filter, []);
        self::assertEquals(1, $result['totalcount']);
        $message = $result['results'][0];
        self::assertNotEmpty($message['message_id'], 'message id missing from cached message ' . print_r($message, true));
        self::assertTrue(isset($message['fileLocations']));
        self::assertEquals(1, count($message['fileLocations']), 'did not get message file location: '
            . print_r($message, true));

        // check get
        $message = $this->_json->getMessage($message['id']);
        self::assertTrue(isset($message['fileLocations']), 'fileLocations missing from message after get');
        self::assertEquals(1, count($message['fileLocations']), 'did not get message file location: '
            . print_r($message, true));
    }

    public function testSaveDraft()
    {
        $draft = $this->_saveDraft();

        // update draft message - old draft should be deleted
        $updatedDraft = $draft;
        $updatedDraft['subject'] = 'my updated draft';
        $updatedDraft = $this->_json->saveDraft($updatedDraft);
        $message = $this->_searchForMessageBySubject($updatedDraft['subject'], $this->_account->drafts_folder);
        self::assertEquals(2, count($message['bcc']), 'bcc recipient not found: ' . print_r($message, TRUE));

        $this->_assertDraftNotFound($draft);
    }

    public function testCleanupAutoSavedDrafts()
    {
        $draftFolder = Felamimail_Controller_Account::getInstance()->getSystemFolder($this->_account, Felamimail_Model_Folder::FOLDER_DRAFTS);
        $filter = [['field' => 'folder_id', 'operator' => 'equals', 'value' => $draftFolder->getId()]];
        
        $this->_saveDraft();
        $result = $this->_json->searchMessages($filter, []);
        self::assertEquals(1, count($result['results']), 'auto saved draft should be removed: ' . print_r($result, TRUE));

        $messageToSave = $this->_getMessageData('', 'test2');
        $this->_json->saveMessageInFolder($this->_account->drafts_folder, $messageToSave);
        $this->_searchForMessageBySubject($messageToSave['subject'], $this->_account->drafts_folder);
        $result = $this->_json->searchMessages($filter, []);
        self::assertEquals(2, count($result['results']), 'auto saved draft should be removed: ' . print_r($result, TRUE));

        Felamimail_Controller_Message::getInstance()->cleanupAutoSavedDrafts([$this->_account->getId()]);
        $result = $this->_json->searchMessages($filter, []);
        self::assertEquals(1, count($result['results']), 'auto saved draft should be removed: ' . print_r($result, TRUE));
    }

    /**
     * @return array
     * @throws Tinebase_Exception_Record_DefinitionFailure
     * @throws Tinebase_Exception_Record_Validation
     */
    protected function _saveDraft()
    {
        $messageToSave = $this->_getMessageData();
        $messageToSave['messageuid'] = '';
        $messageToSave['bcc'] = array('bccaddress@email.org', 'bccaddress2@email.org');
        $draft = $this->_json->saveDraft($messageToSave);
        $this->_foldersToClear = array($this->_account->drafts_folder);
        self::assertNotEmpty($draft['messageuid'], 'messageuid of draft message missing: ' . print_r($draft, true));

        // check if message is in drafts folder and recipients are present
        $message = $this->_searchForMessageBySubject($messageToSave['subject'], $this->_account->drafts_folder);
        self::assertEquals($messageToSave['subject'], $message['subject']);
        self::assertEquals($messageToSave['to'][0], $message['to'][0], 'recipient not found');
        self::assertTrue(in_array(Zend_Mail_Storage::FLAG_SEEN, $message['flags']), 'flags: ' . print_r($message['flags'], true));
        self::assertEquals(2, count($message['bcc']), 'bcc recipient not found: ' . print_r($message, TRUE));
        self::assertStringContainsString('bccaddress', $message['bcc'][0], 'bcc recipient not found');

        return $draft;
    }

    /**
     * @param $draft
     */
    protected function _assertDraftNotFound($draft)
    {
        $message = $this->_searchForMessageBySubject($draft['subject'], $this->_account->drafts_folder, false);
        self::assertEquals([], $message, 'old draft should be deleted: ' . print_r($draft, true));
    }

    public function testDeleteDraft()
    {
        $draft = $this->_saveDraft();
        $result = $this->_json->deleteDraft($draft['messageuid'], $draft['account_id']);
        self::assertTrue($result['success']);
        $this->_assertDraftNotFound($draft);
    }

    public function testSaveDraftWithForwardAttachment()
    {
        $message = $this->_appendMessageforForwarding();

        $subject = 'Verbessurüngsvorschlag';
        $fwdSubject = 'Fwd: ' . $subject;
        $forwardMessageData = array(
            'account_id' => $this->_account->getId(),
            'subject' => $fwdSubject,
            'to' => array($this->_getEmailAddress()),
            'body' => "aaaaaä <br>",
            'headers' => array('X-Tine20TestMessage' => 'jsontest'),
            'original_id' => $message['id'],
            'attachments' => [[
                'type' => Felamimail_Model_Message::CONTENT_TYPE_MESSAGE_RFC822,
                'name' => $subject,
                'size' => '9709', // needed?
                'id' => $message['id'],
                'attachment_type' => 'attachment',
            ]],
            'flags' => Zend_Mail_Storage::FLAG_PASSED,
        );

        $draft = $this->_json->saveDraft($forwardMessageData);
        $this->_foldersToClear = array($this->_account->drafts_folder);
        self::assertNotEmpty($draft['messageuid'], 'messageuid of draft message missing: ' . print_r($draft, true));
    }

    public function testUpdateUserAccountCredentials()
    {
        $account = $this->_createExternalUserAccount();
        // update credentials of account
        $account['password'] = 'updatedpass';
        $this->_json->saveAccount($account);
        $this->_assertPassword($account['id'], $account['password']);
    }

    protected function _assertPassword($accountId, $pass)
    {
        $fmailaccount = Felamimail_Controller_Account::getInstance()->get($accountId);
        $imapConfig = $fmailaccount->getImapConfig();
        self::assertEquals($pass, $imapConfig['password']);
    }

    protected function _createExternalUserAccount()
    {
        $pass = 'somepass';
        $account = $this->_json->saveAccount([
            'email' => Tinebase_Core::getUser()->accountEmailAddress,
            'type' => Felamimail_Model_Account::TYPE_USER,
            'user' => Tinebase_Core::getUser()->accountEmailAddress,
            'password' => $pass,
            'user_id' => Tinebase_Core::getUser()->toArray(),
        ]);
        $this->_assertPassword($account['id'], $pass);
        return $account;
    }

    public function testChangeUserAccountCredentials()
    {
        $account = $this->_createExternalUserAccount();
        $pass = 'newpass';
        $this->_json->changeCredentials($account['id'], Tinebase_Core::getUser()->accountEmailAddress, $pass);
        $this->_assertPassword($account['id'], $pass);
    }

    public function testDoMailsBelongToAccount()
    {
        $userMail = Tinebase_Core::getUser()->accountEmailAddress;
        $mails = [
            $userMail,
            'someexternal@mail.test',
        ];
        $result = $this->_json->doMailsBelongToAccount($mails);
        self::assertCount(1, $result);
    }

    public function testImapSettingsConnection()
    {
        $account = $this->_createSharedAccount();
        $config = $account->getImapConfig();

        $fields = [
            'host' => $config['host'],
            'port' => $config['port'],
            'ssl' => isset($config['ssl']) ? $config['ssl'] : 'none',
            'user' => '',
            'password' => ''
        ];

        try {
            $this->_json->testImapSettings($account->getId(), $fields);
        } catch (Tinebase_Exception_SystemGeneric $e) {
            $translation = Tinebase_Translation::getTranslation('Felamimail');
            $this->assertEquals($translation->_('IMAP Credentials missing'), $e->getMessage(), $e->getMessage());
        }

        $fields['user'] = $config['user'];
        $fields['password'] = $config['password'];

        // NOTE: this does not do the real connection test - it just delivers return as the account type is != USER
        $result = $this->_json->testImapSettings($account->getId(), $fields);
        self::assertEquals('success', $result['status'], 'connection failed');
    }

    public function testSmtpSettingsConnection()
    {
        $account = $this->_account;
        $config = $account->getImapConfig();
        $fields = [
            'smtp_hostname' => $account['smtp_hostname'],
            'smtp_port' => $account['smtp_port'],
            'smtp_ssl' => 'none',
            'smtp_auth' => 'none',
            'smtp_user' => '',
            'smtp_password' => '',
        ];

        $fields['user'] = $config['user'];
        $fields['password'] = $config['password'];

        $this->_json->testSmtpSettings($account->getId(), $fields);

        $result = $this->_json->testSmtpSettings($account->getId(), $fields);
        self::assertEquals('success', $result['status'], 'connection failed');
    }

    public function testImapSettingsConnectionWithoutAccountWrongHost()
    {
        $fields = [
            'host' => 'some.host.not.working',
            'port' => 143,
            'ssl' => 'none',
            'user' => 'somone',
            'password' => 'somepass'
        ];

        try {
            $this->_json->testImapSettings(null, $fields, true);
            self::fail('Should not work: mail server is not valid!');
        } catch (Tinebase_Exception_SystemGeneric $e) {
            self::assertStringContainsString("No connection to IMAP server.",
                $e->getMessage());
        }
    }

    public function testImapSettingsConnectionWithoutAccountCorrectCreds()
    {
        $imapConfig = Tinebase_Config::getInstance()->get(Tinebase_Config::IMAP);
        $creds = TestServer::getInstance()->getTestCredentials();
        $fields = [
            'host' => $imapConfig->host,
            'port' => $imapConfig->port,
            'ssl' => $imapConfig->ssl ? $imapConfig->ssl : 'none',
            'user' => Tinebase_EmailUser::getInstance()->getEmailUserName(Tinebase_Core::getUser()),
            'password' => $creds['password'],
        ];

        try {
            $result = $this->_json->testImapSettings(null, $fields, true);
            self::assertEquals('success', $result['status']);
        } catch (Tinebase_Exception_SystemGeneric $e) {
            // FIXME somehow this fails on our jenkins ci ... why?
            self::assertStringContainsString('No connection to IMAP server',
                $e->getMessage());
        }
    }

    public function testMoveFolder()
    {
        $filter = $this->_getFolderFilter($this->_testFolderName);
        $junkfoldersBefore = $this->_json->searchFolders($filter);
        $this->_json->addFolder('Info Gemeindebüro', 'INBOX', $this->_account->getId());
        $this->_createdFolders = ['INBOX.Info Gemeindebüro'];
        $newGlobalname = $this->_testFolderName . '.Info Gemeindebüro';
        $result = $this->_json->moveFolder($newGlobalname,
            'INBOX.Info Gemeindebüro', $this->_account->getId());
        $this->_createdFolders = [$newGlobalname];
        self::assertEquals($newGlobalname, $result['globalname']);
        $junkfoldersAfter = $this->_json->searchFolders($filter);
        self::assertGreaterThan($junkfoldersBefore['totalcount'], $junkfoldersAfter['totalcount'],
            'new folder missing from test folder');
        $testFolder = Felamimail_Controller_Folder::getInstance()->getByBackendAndGlobalName($this->_account, $this->_testFolderName);
        self::assertEquals(1, $testFolder->has_children, 'has_children should be 1');
    }

    /**
     * 1. Folder "A" unter der Inbox erstellen.
     * 2. Bei beiden Usern die Ordnerliste aktualisieren.
     * 3. Bei UserA den Folder "A" löschen.
     * 4. Bei UserB den Folder "B" unter den Folder "A" erstellen.
     * 5. Bei beiden Usern die Ordnerliste aktualisieren.
     *
     * @return void
     * @throws Tinebase_Exception_AccessDenied
     * @throws Tinebase_Exception_InvalidArgument
     * @throws Tinebase_Exception_NotFound
     * @throws Tinebase_Exception_Record_DefinitionFailure
     * @throws Tinebase_Exception_Record_Validation
     */
    public function testDeleteFolderWithAnotherUserInSharedAccount()
    {
        $parent = 'INBOX';
        $testFolder = 'subfolder';
        $jsmith = $this->_personas['jsmith'];
        $account = $this->_createSharedAccount(true, [
            'grants' => [
                [
                    'readGrant' => true,
                    'editGrant' => true,
                    'addGrant' => true,
                    'account_type' => 'user',
                    'account_id' => Tinebase_Core::getUser()->getId(),
                ], [
                    'readGrant' => true,
                    'editGrant' => true,
                    'addGrant' => true,
                    'account_type' => 'user',
                    'account_id' => $jsmith->getId(),
                ]
            ]
        ]);

        try {
            $this->_json->addFolder($parent, '', $account->getId());
        } catch (Tinebase_Exception_SystemGeneric $tesg) {
            // already exists
        }
        $folder = $this->_json->addFolder($testFolder, $parent, $account->getId());
        $testFolderGlobalname = $folder['globalname'];

        $this->_json->updateFolderCache($account->getId(), $parent);
        Tinebase_Core::setUser($jsmith);
        $this->_json->updateFolderCache($account->getId(), $parent);
        Tinebase_Core::setUser($this->_originalTestUser);
        $this->_json->deleteFolder($testFolderGlobalname, $account->getId());
        Tinebase_Core::setUser($jsmith);
        try {
            $this->_json->addFolder('subfolder2', $testFolderGlobalname, $account->getId());
            $result = $this->_json->updateFolderCache($account->getId(), $parent);
            $folder = $result[0];
            self::assertEquals(1, $folder['is_selectable'], 'folder should be selectable (or removed!): '
                . print_r($folder, true));
        } catch (Tinebase_Exception_NotFound $tenf) {
            self::assertEquals('Could not create folder: parent folder INBOX.subfolder not found',
                $tenf->getMessage());
        }
    }

    public function testRefreshFolder()
    {
        $folder = $this->_getFolder($this->_testFolderName);

        $this->_moveMessageToFolder($this->_testFolderName);
        $result = $this->_json->updateMessageCache($folder['id'], 30);
        $this->assertGreaterThan(0, $result['cache_totalcount']);
        $this->assertEquals(Felamimail_Model_Folder::CACHE_STATUS_COMPLETE, $result['cache_status']);
        $this->assertNotNull($result['cache_timestamp']);

        $this->_json->refreshFolder($folder->getId());
        $updatedFolder = $this->_getFolder($this->_testFolderName);
        $this->assertEquals(0, $updatedFolder['cache_totalcount']);
        $this->assertEquals(Felamimail_Model_Folder::CACHE_STATUS_EMPTY, $updatedFolder['cache_status']);
    }

    /**
     * Test if expected answer is set and saved in the database.
     *
     */
    public function testMessageExpectedAnswer()
    {
        $messageToSend = $this->_getMessageData();
        $date = Tinebase_DateTime::now()->addHour(1)->setTimezone(Tinebase_Core::getUserTimezone())->format(Tinebase_Record_Abstract::ISO8601LONG);
        $messageToSend['expected_answer'] = $date;
        $messageToSend['subject'] = Tinebase_Record_Abstract::generateUID();
        $message = $this->_sendMessage(_messageToSend: $messageToSend);

        // get complete message
        $message = $this->_json->getMessage($message['id']);

        $this->_checkExpectedAnswerInDb($message['headers']['message-id']);

        // reply to our mail - check if expected answer is removed from db
        $replyMessage = $this->_getReply($message);
        $this->_json->saveMessage($replyMessage);
        $this->_getMessages();
        $this->_checkExpectedAnswerInDb($message['headers']['message-id'], false);
    }

    /**
     * Check if the expected answer is saved in the database.
     *
     * @param string $message_id The ID of the message to check for.
     * @param bool $exists Flag to indicate whether the expected answer should exist in the database.
     * @throws PHPUnit_Framework_AssertionFailedError If the expected answer is not found when it should exist or vice versa.
     */protected function _checkExpectedAnswerInDb(string $message_id, bool $exists = true)
    {
        $filter = Tinebase_Model_Filter_FilterGroup::getFilterForModel(Felamimail_Model_MessageExpectedAnswer::class, [
            ['field' => 'message_id', 'operator' => 'equals', 'value' => $message_id]
        ]);
        $result = Felamimail_Controller_MessageExpectedAnswer::getInstance()->search($filter);
        $expected_answer = $result->getFirstRecord();
        if ($exists) {
            $this->assertNotNull($expected_answer, 'did not find expected answer for message id ' . $message_id);
        } else {
            $this->assertNull($expected_answer, 'did find expected answer for message id ' . $message_id);
        }
    }

    /**
     * Test to see if a reminder is sent if the expected answer is set and is no longer in the datebase.
     *
     */
    public function testAutomaticMailExpectedAnswer()
    {
        $this->_testNeedsTransaction();

        $messageToSend = $this->_getMessageData();
        $date = Tinebase_DateTime::now()->setTimezone(Tinebase_Core::getUserTimezone())->format(Tinebase_Record_Abstract::ISO8601LONG);
        $messageToSend['expected_answer'] = $date;
        $messageToSend['subject'] = Tinebase_Record_Abstract::generateUID();
        $message = $this->_sendMessage(_messageToSend: $messageToSend);
        Felamimail_Controller_MessageExpectedAnswer::getInstance()->checkExpectedAnswer();
        $message = $this->_json->getMessage($message['id']);
        // check that the entry is no longer in the db
        $this->_checkExpectedAnswerInDb($message['headers']['message-id'], false);
        // check if reminder was sent
        $result = $this->getMessages();
        $this->assertTrue(!empty($result));
    }
}
