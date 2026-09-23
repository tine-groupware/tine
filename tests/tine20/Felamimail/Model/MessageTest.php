<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Felamimail
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2010-2015 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * 
 */

/**
 * Test helper
 */
require_once dirname(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR . 'TestHelper.php';

/**
 * Test class for Felamimail_Model_MessageTest
 */
class Felamimail_Model_MessageTest extends \PHPUnit\Framework\TestCase
{
    /**
     * Runs the test methods of this class.
     *
     * @access public
     * @static
     */
    public static function main()
    {
        $suite  = new \PHPUnit\Framework\TestSuite('Tine 2.0 Felamimail Message Model Tests');
        PHPUnit_TextUI_TestRunner::run($suite);
    }

    /**
     * Sets up the fixture.
     * This method is called before a test is executed.
     *
     * @access protected
     */
    protected function setUp(): void
{
    }

    /**
     * Tears down the fixture
     * This method is called after a test is executed.
     *
     * @access protected
     */
    protected function tearDown(): void
{
    }

    /********************************* test funcs *************************************/
    
    /**
     * test conversion to plain text (blockquotes to quotes) / tests linebreaks, too
     */
    public function testGetPlainTextBody()
    {
        $message = new Felamimail_Model_Message(array(
            'body'  =>  'blabla<br/><blockquote class="felamimail-body-blockquote">lalülüüla<br/><br/><div>lala</div><br/><blockquote class="felamimail-body-blockquote">xyz</blockquote></blockquote><br/><br/>jojo<br/>' .
                        'lkjlhk<div><br></div><div><br><div>jjlöjlö</div><div><font face="arial"><br></font></div></div><div><font face="arial">Pickhuben 2-4, 20457 Hamburg</font></div>'
        ));
        
        $result = $message->getPlainTextBody();
        //echo $result;
        
        $this->assertEquals("blabla\n" .
            "> lalülüüla\n" .
            "> \n" .
            "> lala\n" .
            "> \n" . 
            "> > xyz\n" .
            "\n\n" .
            "jojo\n" .
            "lkjlhk\n\n\n" .
            "jjlöjlö\n\n" .
            "Pickhuben 2-4, 20457 Hamburg\n", $result);
    }

    /**
     * test conversion from plain text to html (quotes ('> > ...') to blockquotes)
     * 
     * @see 0005334: convert plain text quoting ("> ") to html blockquotes
     */
    public function testTextToHtml()
    {
        $plaintextMessage = "blabla\n" .
            "> lalülüüla\n" .
            "> \n" .
            "> > >lala\n" .
            ">  >\n" . 
            ">  > xyz\n" .
            "\n\n" .
            "> jojo\n" .
            "jojo\n" ;
        
        $result = Tinebase_Mail::convertFromTextToHTML($plaintextMessage, 'felamimail-body-blockquote');
        
        $this->assertEquals('blabla<br /><blockquote class="felamimail-body-blockquote">lalülüüla<br /><br />'
            . '<blockquote class="felamimail-body-blockquote"><blockquote class="felamimail-body-blockquote">lala<br />'
            . '</blockquote><br />xyz<br /></blockquote></blockquote><br /><br /><blockquote class="felamimail-body-blockquote">jojo<br /></blockquote>jojo<br />', $result);
    }
    
    /**
     * testReplaceUris
     * 
     * @see 0008020: link did not get an anchor in html mail
     */
    public function testReplaceUrisAndMails()
    {
        $message = new Felamimail_Model_Message(array(
            'body'  =>  'http://www.facebook.com/media/set/?set=a.164136103742229.1073741825.100004375207149&type=1&l=692e495b17'
                . " Klicken Sie bitte noch auf den folgenden Link, um Ihre Teilnahme zu bestätigen:\n"
                . 'http://www.kieler-linuxtage.de/vortragsplaner/wsAnmeldung.php?fkt=best&wsID=111&code=xxxx&eMail=abc@efh.com'
                . '   &lt;http://my.serveer.com/job/job1/137/display/redirect?page=changes&gt;'
        ));
        
        $result = Felamimail_Message::replaceUris($message->body);
        $result = Felamimail_Message::replaceEmails($result);

        $this->assertStringContainsString('a href="http://www.facebook.com/media/set/', $result);
        $this->assertStringContainsString('a href="http://www.kieler-linuxtage.de/', $result);
        $this->assertStringContainsString('eMail=abc@efh.com', $result);
        $this->assertStringContainsString('a href="http://my.serveer.com/job/job1/137/display/redirect?page=changes"', $result);
    }

    /**
     * test spam suspicion subject strategy
     */
    public function testSpamSuspicionSubjectStrategy()
    {
        Felamimail_Config::getInstance()->set(Felamimail_Config::FEATURE_SPAM_SUSPICION_STRATEGY, TRUE);
        Felamimail_Config::getInstance()->set(Felamimail_Config::SPAM_SUSPICION_STRATEGY, 'subject');

        $config = [
            'pattern' => '/SPAM\? \(.+\) \*\*\* /',
        ];

        Felamimail_Config::getInstance()->set(Felamimail_Config::SPAM_SUSPICION_STRATEGY_CONFIG, $config);

        $message = new Felamimail_Model_Message([
            'subject' => 'SPAM? (Score = 14.53 / 15) *** Super preise',
        ]);

        $strategy = Felamimail_Spam_SuspicionStrategy_Factory::factory();
        $message->is_spam_suspicions = $strategy->apply($message);

        static::assertTrue($message->is_spam_suspicions, 'set the spam suspicion strategy failed');

        $message['subject'] = 'test non spam suspicion subject';
        $message->is_spam_suspicions = $strategy->apply($message);

        static::assertFalse($message->is_spam_suspicions, 'set the spam non-suspicion strategy failed');
    }

    /**
     * test parseHeaders with plain ASCII subject
     */
    public function testParseHeadersWithPlainAsciiSubject()
    {
        $message = new Felamimail_Model_Message();
        $headers = [
            'subject' => 'Test Subject',
            'from'    => ['sender@example.com'],
            'to'      => ['recipient@example.com'],
            'date'    => 'Mon, 01 Jan 2024 12:00:00 +0000',
        ];

        $message->parseHeaders($headers);

        static::assertEquals('Test Subject', $message->subject);
        static::assertEquals('sender@example.com', $message->from_email);
        static::assertEquals('recipient@example.com', $message->to[0]['email']);
    }

    /**
     * test parseHeaders with RFC 2047 MIME-encoded UTF-8 subject (quoted-printable)
     *
     * @see https://tools.ietf.org/html/rfc2047
     */
    public function testParseHeadersWithMimeEncodedUtf8Subject()
    {
        $message = new Felamimail_Model_Message();

        // "Größer" encoded as =?UTF-8?Q?Gr=C3=B6=C3=9Fer?=
        $headers = [
            'subject' => '=?UTF-8?Q?Gr=C3=B6=C3=9Fer?=',
            'from'    => ['sender@example.com'],
            'to'      => ['recipient@example.com'],
            'date'    => 'Mon, 01 Jan 2024 12:00:00 +0000',
        ];

        $message->parseHeaders($headers);

        // After iconv_mime_decode, the subject should be decoded to "Größer"
        static::assertNotEquals('=?UTF-8?Q?Gr=C3=B6=C3=9Fer?=', $message->subject, 'Subject should not remain MIME-encoded');
        static::assertEquals('Größer', $message->subject, 'Subject should be properly decoded from RFC 2047 encoding');
    }

    /**
     * test parseHeaders with RFC 2047 MIME-encoded subject containing spaces (encoded-word with space)
     *
     * @see https://tools.ietf.org/html/rfc2047
     */
    public function testParseHeadersWithMimeEncodedSubjectWithSpaces()
    {
        $message = new Felamimail_Model_Message();

        // "Hello World" with special chars: "Héllo Wörld" encoded as =?UTF-8?Q?H=C3=A9llo_W=C3=B6rld?=\n =?UTF-8?Q?_Test?=
        // Note: RFC 2047 allows line folding with CRLF
        $headers = [
            'subject' => '=?UTF-8?Q?H=C3=A9llo_W=C3=B6rld?= =?UTF-8?Q?_Test?=',
            'from'    => ['sender@example.com'],
            'to'      => ['recipient@example.com'],
            'date'    => 'Mon, 01 Jan 2024 12:00:00 +0000',
        ];

        $message->parseHeaders($headers);

        static::assertNotEquals('=?UTF-8?Q?H=C3=A9llo_W=C3=B6rld?= =?UTF-8?Q?_Test?=', $message->subject, 'Subject should not remain MIME-encoded');
        static::assertEquals('Héllo Wörld Test', $message->subject, 'Subject should be properly decoded from multiple RFC 2047 encoded-words');
    }

    /**
     * test parseHeaders with non-ASCII plain subject (already decoded by IMAP layer)
     */
    public function testParseHeadersWithAlreadyDecodedNonAsciiSubject()
    {
        $message = new Felamimail_Model_Message();

        // Subject already decoded by IMAP layer (common case)
        $headers = [
            'subject' => 'Nachricht über Projekte',
            'from'    => ['sender@example.com'],
            'to'      => ['recipient@example.com'],
            'date'    => 'Mon, 01 Jan 2024 12:00:00 +0000',
        ];

        $message->parseHeaders($headers);

        static::assertEquals('Nachricht über Projekte', $message->subject, 'Already-decoded non-ASCII subject should be preserved');
    }

    /**
     * test parseHeaders with empty subject
     */
    public function testParseHeadersWithEmptySubject()
    {
        $message = new Felamimail_Model_Message();

        $headers = [
            'from' => ['sender@example.com'],
            'to'   => ['recipient@example.com'],
            'date' => 'Mon, 01 Jan 2024 12:00:00 +0000',
        ];

        $message->parseHeaders($headers);

        static::assertNull($message->subject, 'Subject should be null when not provided');
    }

    /**
     * test parseHeaders with subject containing special characters that need database filtering
     */
    public function testParseHeadersWithSpecialCharactersInSubject()
    {
        $message = new Felamimail_Model_Message();

        $headers = [
            'subject' => 'Test <script>alert("xss")</script>',
            'from'    => ['sender@example.com'],
            'to'      => ['recipient@example.com'],
            'date'    => 'Mon, 01 Jan 2024 12:00:00 +0000',
        ];

        $message->parseHeaders($headers);

        static::assertNotFalse(strpos($message->subject, '<script>'), 'Subject should contain the raw special characters after filtering');
    }

}
