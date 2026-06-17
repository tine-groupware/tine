<?php
/**
 * Tine 2.0 - http://www.tine20.org
 *
 * @package     Felamimail
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2010-2020 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 *
 */

/**
 * Test class for Felamimail_Model_MessagePipeTest
 */
class Felamimail_Model_MessagePipeTest extends Felamimail_TestCase
{
    /**
     * test create duplicated folder from pipeline
     *
     * @throws Tinebase_Exception_Record_NotAllowed
     * @throws Exception
     */
    public function testMessagePipeCreateDuplicatedFolder()
    {
        $config = [
            'spam' => [
                'strategy' => 'copy',
                'config' => [
                    'target' => [
                        'folder' => 'spam'
                    ]
                ]
            ]
        ];

        $pipe = 'spam';

        // move to root
        $message = $this->_messagePipeTestHelper();
        $this->_executePipeLine($config[$pipe], $message);
        $message = $this->_messagePipeTestHelper();
        $this->_executePipeLine($config[$pipe], $message);

        // move to sub folder
        $config[$pipe]['config']['target']['folder'] = 'INBOX/SPAM';
        $message = $this->_messagePipeTestHelper();
        $this->_executePipeLine($config[$pipe], $message);
        $message = $this->_messagePipeTestHelper();
        $this->_executePipeLine($config[$pipe], $message);
    }
    
    /**
     * test message pipe spam/ham with copy mail
     * - copy mail to spam/ham folder in internal account
     * - copy mail to sub folder in internal account
     *
     * @throws Tinebase_Exception_Record_NotAllowed
     * @throws Exception
     */
    public function testMessagePipeCopyToInternalAccount()
    {
        // create shared account
        $config = [
            'spam' => [
                'strategy' => 'copy',
                'config' => [
                    'target' => [
                        'folder' => 'spam'
                    ]
                ]
            ],
            'ham' => [
                'strategy' => 'copy',
                'config' => [
                    'target' => [
                        'folder' => 'ham'
                    ]
                ]
            ]
        ];

        // move to root

        $pipe = 'spam';

        $message = $this->_messagePipeTestHelper();
        $this->_executePipeLine($config[$pipe], $message);
        $this->_assertMessageInFolder('spam', $message['subject']);

        // move to sub folder
        $config[$pipe]['config']['target']['folder'] = 'INBOX/SPAM';
        $message = $this->_messagePipeTestHelper();
        $this->_executePipeLine($config[$pipe], $message);
        $this->_assertMessageInFolder('INBOX.SPAM', $message['subject'] );

        $pipe = 'ham';

        $message = $this->_messagePipeTestHelper();
        $this->_executePipeLine($config[$pipe], $message);
        $this->_assertMessageInFolder('ham', $message['subject']);

        $config[$pipe]['config']['target']['folder'] = 'INBOX/HAM';
        $message = $this->_messagePipeTestHelper();
        $this->_executePipeLine($config[$pipe], $message);
        $this->_assertMessageInFolder('INBOX.HAM', $message['subject'] );
    }

    /**
     * test message pipe spam/ham with copy mail
     * - copy mail to spam/ham folder in shared account
     * - copy mail to sub folder in shared account
     * 
     * @throws Tinebase_Exception_Record_NotAllowed
     * @throws Exception
     */
    public function testMessagePipeCopyToAnotherAccount()
    {
        // create shared account
        $account = $this->_createSharedAccount();

        $config = [
            'spam' => [
                'strategy' => 'copy',
                'config' => [
                    'target' => [
                        'accountid' => $account['id'],
                        'folder' => 'spam'
                    ]
                ]
            ],
            'ham' => [
                'strategy' => 'copy',
                'config' => [
                    'target' => [
                        'accountid' => $account['id'],
                        'folder' => 'ham'
                    ]
                ]
            ]
        ];

        // move to root
        $pipe = 'spam';

        $message = $this->_messagePipeTestHelper($account);
        $this->_executePipeLine($config[$pipe], $message);
        $this->_assertMessageInFolder('spam', $message['subject'], $account);

        // move to sub folder
        $config[$pipe]['config']['target']['folder'] = 'INBOX/SPAM';
        $message = $this->_messagePipeTestHelper($account);
        $this->_executePipeLine($config[$pipe], $message);
        $this->_assertMessageInFolder('INBOX.SPAM', $message['subject'], $account);

        $pipe = 'ham';

        $message = $this->_messagePipeTestHelper($account);
        $this->_executePipeLine($config[$pipe], $message);
        $this->_assertMessageInFolder('ham', $message['subject']);

        $config[$pipe]['config']['target']['folder'] = 'INBOX/HAM';
        $message = $this->_messagePipeTestHelper($account);
        $this->_executePipeLine($config[$pipe], $message);
        $this->_assertMessageInFolder('INBOX.HAM', $message['subject'], $account);
    }

    /**
     * test message pipe spam/ham with copy mail to local directory
     *
     * @throws Tinebase_Exception_Record_NotAllowed
     * @throws Exception
     */
    public function testMessagePipeCopyToLocalDir()
    {
        $this->_testNeedsTransaction();
        $tmp = Tinebase_Core::getTempDir();

        $config = [
            'spam' => [
                'strategy' => 'copy',
                'config' => [
                    'target' => [
                        'local_directory' => $tmp . '/spam'
                    ]
                ]
            ],
            'ham' => [
                'strategy' => 'copy',
                'config' => [
                    'target' => [
                        'local_directory' => $tmp . '/ham'
                    ]
                ]
            ]
        ];

        // send message and copy to spam dir
        $message = $this->_messagePipeTestHelper();
        $this->_executePipeLine($config['spam'], $message);
        $this->_assertMessageInFolder('INBOX', $message['subject']);
        // assert eml in $tmp . '/spam'
        self::assertTrue(is_dir($tmp . '/spam'), 'no spam dir found');
        
        $filename =  preg_replace("/[^\w\d@._-]|\.\./", "", $message->headers['message-id']);
        $filename = $tmp . '/spam/' . $filename . '.eml';
        self::assertTrue(file_exists($filename), 'eml file not found: ' . $filename);

        // send message and copy to ham dir
        $message = $this->_messagePipeTestHelper();
        $this->_executePipeLine($config['ham'], $message);
        $this->_assertMessageInFolder('INBOX', $message['subject']);
        // assert eml in $tmp . '/ham'
        self::assertTrue(is_dir($tmp . '/ham'), 'no ham dir found');
        
        $filename =  preg_replace("/[^\w\d@._-]|\.\./", "", $message->headers['message-id']);
        $filename = $tmp . '/ham/' . $filename . '.eml';
        self::assertTrue(file_exists($filename), 'eml file not found: ' . $filename);
    }

    /**
     * test message pipe spam copy strategy with invalid char
     *
     * @throws Tinebase_Exception_Record_NotAllowed
     * @throws Exception
     */
    public function testMessagePipeCopyWithInvalidChar()
    {
        $this->_testNeedsTransaction();
        $tmp = Tinebase_Core::getTempDir();
        
        $config = [
            'spam' => [
                'strategy' => 'copy',
                'config' => [
                    'target' => [
                        'local_directory' => $tmp . '/spam'
                    ]
                ]
            ]
        ];

        // char '/' is invalid
        $message = $this->_messagePipeTestHelper();
        $message->headers = [];
        $message->message_id = '<123abcABC._-@/\!=+>..';
        
        $this->_executePipeLine($config['spam'], $message);
        
        $filename = $tmp . '/spam/' . '123abcABC._-@.eml';
        self::assertTrue(file_exists($filename), 'eml file not found: ' . $filename);
    }

    public function testMessagePipeCopyWithCustomFlags()
    {
        $pipe = 'spam';
        $targetFolder = 'spam';
        $config = [
            'spam' => [
                'strategy' => 'copy',
                'config' => [
                    'target' => [
                        'folder' => 'spam'
                    ],
                    'addFlags' => ['SPAM']
                ]
            ]
        ];
        $message = $this->_messagePipeTestHelper(subject: 'SPAM? (15) *** testMessagePipeMoveWithCustomFlags');
        $this->_executePipeLine($config[$pipe], $message);

        $message = $this->_assertMessageInFolder($targetFolder, $message['subject']);
        $this->assertEquals('SPAM', $message['flags'][0]);
    }

    /**
     * test message pipe spam with move mail
     * - move mail configured trash folder of current user
     * - delete original message
     *
     * @throws Tinebase_Exception_Record_NotAllowed
     * @throws Exception
     */
    public function testMessagePipeMove()
    {
        $pipe = 'spam';
        $targetFolder = 'trash';
        $config = [
            'spam' => [
                'strategy' => 'move',
                'config' => [
                    'target' => [
                        'folder' => '#trash' ,
                    ]
                ]
            ]
        ];

        $inbox = $this->_getFolder('INBOX');
        $inboxBefore = $this->_json->updateMessageCache($inbox['id'], 30);

        $message = $this->_messagePipeTestHelper();
        $this->_executePipeLine($config[$pipe], $message);
        
        $inboxAfter = $this->_getFolder('INBOX');

        $this->assertEquals($inboxBefore['cache_unreadcount'], $inboxAfter['cache_unreadcount']);
        $this->assertEquals($inboxBefore['cache_totalcount'], $inboxAfter['cache_totalcount']);

        $this->_assertMessageInFolder($targetFolder, $message['subject']);
    }

    public function testMessagePipeMoveWithCustomFlags()
    {
        $pipe = 'spam';
        $targetFolder = 'trash';
        $config = [
            'spam' => [
                'strategy' => 'move',
                'config' => [
                    'target' => [
                        'folder' => '#trash' ,
                    ],
                    'addFlags' => ['SPAM']
                ]
            ]
        ];
        $message = $this->_messagePipeTestHelper();
        $this->_executePipeLine($config[$pipe], $message);

        $message = $this->_assertMessageInFolder($targetFolder, $message['subject']);
        $this->assertEquals('SPAM', $message['flags'][0]);
    }

    /**
     * test message pipe ham with rewrite subject
     *
     * @throws Tinebase_Exception_Record_NotAllowed
     * @throws Exception
     */
    public function testMessagePipeRewriteSubject()
    {
        $pipe = 'ham';
        $config = [
            'ham' => [
                'strategy' => 'rewrite_subject',
                'config' => [
                    'pattern' => '/SPAM\? \(.+\) \*\*\* /',
                    'replacement' => '',
                ]
            ]
        ];

        $message = $this->_messagePipeTestHelper();
        $this->_executePipeLine($config[$pipe], $message);

        $this->_assertMessageInFolder('INBOX', 'test messagePipe');
        $this->_assertMessageNotInFolder('INBOX', 'SPAM? (15) *** test messagePipe');
    }

    public function testMessagePipeRemoveHeader()
    {
        $oldSpamSuspicionStrategy = Felamimail_Config::getInstance()->get(Felamimail_Config::SPAM_SUSPICION_STRATEGY, 'subject');

        Felamimail_Config::getInstance()->set(Felamimail_Config::SPAM_SUSPICION_STRATEGY, 'header');
        Felamimail_Config::getInstance()->set(Felamimail_Config::SPAM_SUSPICION_HEADER_STRATEGY_CONFIG, [
            'header' => 'X-Rspamd-Action',
            'value' => 'add header',
        ]);
        Felamimail_Config::getInstance()->set(Felamimail_Config::SPAM_MOVE_FOLDER, 'INBOX/Spamverdacht');
        $config = Felamimail_Config::getInstance()->{Felamimail_Config::SPAM_USERPROCESSING_PIPELINE};
        $pipe = 'ham';
        $config['ham'] = [
            'strategy' => 'remove_header',
            'config' => [
                'header' => 'x-rspamd-action',
            ]
        ];
        Felamimail_Config::getInstance()->set(Felamimail_Config::SPAM_USERPROCESSING_PIPELINE, $config);

        $this->_foldersToClear = ['INBOX', 'Sent', 'INBOX.Spamverdacht'];

        $account = Felamimail_Controller_Account::getInstance()->getSystemAccount(Tinebase_Core::getUser());
        $account->sieve_spam_move = true;
        $account = Felamimail_Controller_Account::getInstance()->update($account);
        $folderName = Felamimail_Config::getInstance()->get(Felamimail_Config::SPAM_MOVE_FOLDER);
        $spamMoveFolder = Felamimail_Model_MessagePipeConfig::getTargetFolder($account, $folderName);
        // add test email message to folder
        $emailTest = new Felamimail_Controller_MessageTest();
        $emailTest->setUp();
        $message = $emailTest->messageTestHelper('mw_newsletter_multipart_related.eml', null, $spamMoveFolder, ['X-Mailer: TYPO3', 'X-Rspamd-Action: add header']);
        $message = Felamimail_Controller_Message::getInstance()->getCompleteMessage($message['id']);

        self::assertArrayHasKey('x-rspamd-action', $message['headers'], print_r($message['headers'], true));
        $this->_executePipeLine($config[$pipe], $message);

        $message = $this->_assertMessageInFolder('INBOX', 'Newsletter 3 / 11.2012');
        $message = Felamimail_Controller_Message::getInstance()->getCompleteMessage($message['id']);
        self::assertArrayNotHasKey('x-rspamd-action', $message['headers']);
        self::assertStringContainsString('Felamimail.getResource', $message['body'], 'body should not be removed');
        self::assertEquals(12, count($message['attachments']), 'attachments should not be removed');

        Felamimail_Config::getInstance()->set(Felamimail_Config::SPAM_SUSPICION_STRATEGY, $oldSpamSuspicionStrategy);
    }

    public function _executePipeLine($_config, $_message)
    {
        // create and execute pipeLine
        $pipeLineRecord = Felamimail_Model_MessagePipeConfig::factory($_config);
        $rs = new Tinebase_Record_RecordSet(Felamimail_Model_MessagePipeConfig::class);
        $rs->addRecord(new Felamimail_Model_MessagePipeConfig([
            Felamimail_Model_MessagePipeConfig::FLDS_CLASSNAME => get_class($pipeLineRecord),
            Felamimail_Model_MessagePipeConfig::FLDS_CONFIG_RECORD => $pipeLineRecord]));

        $pipeLine = new Tinebase_BL_Pipe($rs);
        $pipeLine->execute($_message);
    }

    /**
     * message pipe helper function
     * - set spam strategy config
     * - appends message from created messages
     * - adds appended message to cache for move/copy strategy
     * - execute pipeLine
     *
     * @param array $_config
     * @param string $_folderName
     * @param Felamimail_Model_Account|null $_account
     *
     * @return Felamimail_Model_Message|NULL
     *
     * @throws Tinebase_Exception_InvalidArgument
     * @throws Tinebase_Exception_NotFound
     * @throws Tinebase_Exception_Record_DefinitionFailure
     * @throws Tinebase_Exception_Record_NotAllowed
     * @throws Tinebase_Exception_Record_Validation
     */
    public function _messagePipeTestHelper($_account = null, $subject = 'SPAM? (15) *** test messagePipe')
    {
        // set spam strategy config
        $this->_setFeatureForTest(Felamimail_Config::getInstance(), Felamimail_Config::FEATURE_SPAM_SUSPICION_STRATEGY);
        Felamimail_Config::getInstance()->set(Felamimail_Config::SPAM_SUSPICION_STRATEGY, 'subject');
        $config = [
            'pattern' => '/SPAM\? \(.+\) \*\*\* /',
        ];
        Felamimail_Config::getInstance()->set(Felamimail_Config::SPAM_SUSPICION_STRATEGY_CONFIG, $config);

        $this->_getFolder('INBOX', true, $_account);
        $this->_foldersToClear = ['INBOX', 'Sent', 'Trash'];

        $message = $this->_sendMessage(
            'INBOX',
            [],
            '',
            $subject);

        $message = Felamimail_Controller_Message::getInstance()->getCompleteMessage($message['id']);

        return $message;
    }
}
