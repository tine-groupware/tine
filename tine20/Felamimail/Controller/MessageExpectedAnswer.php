<?php declare(strict_types=1);

/**
 * MessageExpectedAnswer controller for Felamimail application
 *
 * @package     Felamimail
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Tonia Leuschel <t.leuschel@metaways.de>
 * @copyright   Copyright (c) 2024 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */


/**
 * MessageExpectedAnswer controller class for Felamimail application
 *
 * @package     Felamimail
 * @subpackage  Controller
 */
class Felamimail_Controller_MessageExpectedAnswer extends Tinebase_Controller_Record_Abstract
{
    /** @use Tinebase_Controller_SingletonTrait<Felamimail_Controller_MessageExpectedAnswer> */
    use Tinebase_Controller_SingletonTrait;

    /**
     * the constructor
     *
     * don't use the constructor. use the singleton
     */
    protected function __construct()
    {
        $this->_applicationName = Felamimail_Config::APP_NAME;
        $this->_backend = new Tinebase_Backend_Sql([
            Tinebase_Backend_Sql_Abstract::MODEL_NAME => Felamimail_Model_MessageExpectedAnswer::class,
            Tinebase_Backend_Sql_Abstract::TABLE_NAME => Felamimail_Model_MessageExpectedAnswer::TABLE_NAME,
            Tinebase_Backend_Sql_Abstract::MODLOG_ACTIVE => true,
        ]);
        $this->_modelName = Felamimail_Model_MessageExpectedAnswer::class;
        $this->_purgeRecords = true;
        $this->_doContainerACLChecks = false;
    }
    /**
     * Check the expected answer for each entry and send an answer mail if needed.
     *
     * @return bool
     */
    public function checkExpectedAnswer(): bool
    {
        $entries = $this->search();
        foreach ($entries as $entry) {
            $now = Tinebase_DateTime::now();
            if ($entry->expected_answer <= $now) {
                $filter = Tinebase_Model_Filter_FilterGroup::getFilterForModel(Felamimail_Model_MessageExpectedAnswer::class, [
                    ['field' => Felamimail_Model_MessageExpectedAnswer::FLD_MESSAGE_ID, 'operator' => 'equals', 'value' => $entry->message_id]
                ]);
                try {
                    $this->sendUnAnswerMail($entry);
                    $this->deleteByFilter($filter);
                } catch (Exception $e) {
                    Tinebase_Exception::log($e);
                    return false;
                }
            }
        }

        return true;
    }
    /**
     * Generate and send a reminder mail if answer did not arrive once the expected answer date is past.
     *
     * @param Felamimail_Model_MessageExpectedAnswer $entry
     */
    public function sendUnAnswerMail(Felamimail_Model_MessageExpectedAnswer $entry): void
    {
        $locale = Tinebase_Translation::getLocale(Tinebase_Core::getPreference()->getValueForUser(Tinebase_Preference::LOCALE, $entry->user_id));
        if (Tinebase_Core::getPreference()->getValueForUser(Tinebase_Preference::LOCALE, $entry->user_id) === 'auto') {
            $locale->setLocale('de');
        }
        $translate = Tinebase_Translation::getTranslation('Felamimail', $locale);
        $subject = $translate->_('still unanswered: ') . $entry->subject;
        $timezone = Tinebase_Core::getPreference()->getValueForUser(Tinebase_Preference::TIMEZONE, $entry->user_id);
        $preferenceDatetimeUser = Tinebase_Translation::dateToStringInTzAndLocaleFormat(
            $entry->expected_answer,
            $timezone,
            $locale,
            'date',
            true
        );
        $recipient = Addressbook_Controller_Contact::getInstance()->getContactByUserId($entry->user_id);
        $user = Tinebase_User::getInstance()->getFullUserById($entry->user_id);
        $attachments = $this->getAttachments($entry);
        $templateFileName = 'SendUnansweredEmail';
        $this->_sendMessageWithTemplate(
            $templateFileName,
            [
                'user' => $user,
                'locale' => $locale,
                'recipients' => array($recipient),
                'subject' => $subject,
                'preferenceDatetimeUser' => $preferenceDatetimeUser,
                'attachments' => $attachments
            ]
        );
    }

    public function getAttachments(Felamimail_Model_MessageExpectedAnswer $entry): Tinebase_Record_RecordSet
    {
        $entry = Felamimail_Controller_MessageExpectedAnswer::getInstance()->get($entry->id);
        return $entry->attachments;
    }

    /**
     * send message with template
     *
     */
    protected function _sendMessageWithTemplate($templateFileName, $context = [])
    {
        $locale = $context['locale'];

        $twig = new Tinebase_Twig($locale, Tinebase_Translation::getTranslation(Felamimail_Config::APP_NAME));
        $htmlTemplate = $twig->load(
            Felamimail_Config::APP_NAME . '/views/emails/' . $templateFileName . '.html.twig',
            $locale
        );
        $textTemplate = $twig->load(
            Felamimail_Config::APP_NAME . '/views/emails/' . $templateFileName . '.text.twig',
            $locale
        );

        $html = $htmlTemplate->render($context);
        $text = $textTemplate->render($context);

        Tinebase_Notification::getInstance()->send(
            $context['user'],
            $context['recipients'],
            $context['subject'],
            $text,
            $html,
            $context['attachments']
        );
    }
}