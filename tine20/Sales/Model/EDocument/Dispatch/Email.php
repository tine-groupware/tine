<?php declare(strict_types=1);
/**
 * class to hold EDocument dispatch data
 *
 * @package     Sales
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2025-2025 Metaways Infosystems GmbH (http://www.metaways.de)
 */

class Sales_Model_EDocument_Dispatch_Email extends Sales_Model_EDocument_Dispatch_Abstract
{
    public const MODEL_NAME_PART = 'EDocument_Dispatch_Email';

    public const FLD_EMAIL = 'email';

    public static function inheritModelConfigHook(array &$_definition)
    {
        parent::inheritModelConfigHook($_definition);

        $_definition[self::MODEL_NAME] = self::MODEL_NAME_PART;
        $_definition[self::RECORD_NAME] = 'Email'; // gettext('GENDER_Email')
        $_definition[self::RECORDS_NAME] = 'Emails'; // ngettext('Email', 'Emails', n)
        $_definition[self::TITLE_PROPERTY] = 'Dispatch to Email: {{ record.email }}';

        $_definition[self::FIELDS][self::FLD_EMAIL] = [
            self::TYPE              => self::TYPE_STRING,
            self::SPECIAL_TYPE      => self::SPECIAL_TYPE_EMAIL,
            self::LABEL             => 'Email', // _('Email')
            self::UI_CONFIG         => [
                'emptyText'             => 'If empty, email address of document recipient will be taken.', // _('If empty, email address of document recipient will be taken.')
            ]
        ];
    }

    public function dispatch(Sales_Model_Document_Abstract $document, ?string $parentDispatchId = null): bool
    {
        $dispatchId = Tinebase_Record_Abstract::generateUID();
        if (!$this->{self::FLD_EMAIL}) {
            if (!$document->{Sales_Model_Document_Abstract::FLD_RECIPIENT_ID}->{Sales_Model_Address::FLD_EMAIL}) {
                return false;
            }
            $email = $document->{Sales_Model_Document_Abstract::FLD_RECIPIENT_ID}->{Sales_Model_Address::FLD_EMAIL};
        } else {
            $email = $this->{self::FLD_EMAIL};
        }

        $t = Tinebase_Translation::getDefaultTranslation(Sales_Config::APP_NAME);
        Sales_Controller_Document_DispatchHistory::getInstance()->create(new Sales_Model_Document_DispatchHistory([
            Sales_Model_Document_DispatchHistory::FLD_DOCUMENT_TYPE => $document::class,
            Sales_Model_Document_DispatchHistory::FLD_DOCUMENT_ID => $document->getId(),
            Sales_Model_Document_DispatchHistory::FLD_DISPATCH_TRANSPORT => static::class,
            Sales_Model_Document_DispatchHistory::FLD_DISPATCH_DATE => Tinebase_DateTime::now(),
            Sales_Model_Document_DispatchHistory::FLD_TYPE => Sales_Model_Document_DispatchHistory::DH_TYPE_START,
            Sales_Model_Document_DispatchHistory::FLD_DISPATCH_ID => $dispatchId,
            Sales_Model_Document_DispatchHistory::FLD_PARENT_DISPATCH_ID => $parentDispatchId,
            Sales_Model_Document_DispatchHistory::FLD_DISPATCH_REPORT => $t->_('email to: ') . $email,
            Sales_Model_Document_DispatchHistory::FLD_DISPATCH_CONFIG => clone $this,
        ]));

        try {
            $division = $document->{Sales_Model_Document_Abstract::FLD_DOCUMENT_CATEGORY}
                ->{Sales_Model_Document_Category::FLD_DIVISION_ID};
            $fmAccountId = $division->getIdFromProperty(Sales_Model_Division::FLD_DISPATCH_FM_ACCOUNT_ID);

            $attachments = [];
            $attachedDocSend = new Tinebase_Record_RecordSet(Sales_Model_Document_AttachedDocument::class);

            /** @var Sales_Model_EDocument_Dispatch_DocumentType $docType */
            foreach ($this->{self::FLD_DOCUMENT_TYPES} as $docType) {
                /** @var Sales_Model_Document_AttachedDocument $attachedDoc */
                foreach ($document->{Sales_Model_Document_Abstract::FLD_ATTACHED_DOCUMENTS}->filter(Sales_Model_Document_AttachedDocument::FLD_TYPE,
                    $docType->{Sales_Model_EDocument_Dispatch_DocumentType::FLD_DOCUMENT_TYPE}) as $attachedDoc) {
                    $attachedDocSend->addRecord($attachedDoc);
                    $node = Tinebase_FileSystem::getInstance()->get($attachedDoc->getIdFromProperty(Sales_Model_Document_AttachedDocument::FLD_NODE_ID));
                    $attachments[] = [
                        'attachment_type' => 'attachment',
                        'id' => $node->getId(),
                        'name' => $node->name,
                        'size' => $node->size,
                    ];
                }
            }

            if (empty($attachments)) {
                throw new Tinebase_Exception_UnexpectedValue('no attached documents found for dispatching by email');
            }

            $locale = new Zend_Locale($document->{Sales_Model_Document_Abstract::FLD_DOCUMENT_LANGUAGE});
            $t = Tinebase_Translation::getTranslation(Sales_Config::APP_NAME, $locale);
            if ($boilerPlate = $document->{Sales_Model_Document_Abstract::FLD_BOILERPLATES}
                ?->find(Sales_Model_Boilerplate::FLD_NAME, 'Email')) {
                $twig = new Tinebase_Twig($locale, $t, [
                    Tinebase_Twig::TWIG_LOADER =>
                        new Tinebase_Twig_CallBackLoader($boilerPlate->getId(), ($boilerPlate->last_modified_time ?: $boilerPlate->creation_time)->getTimestamp(), fn() => $boilerPlate->{Sales_Model_Boilerplate::FLD_BOILERPLATE}),
                    Tinebase_Twig::TWIG_AUTOESCAPE => false,
                ]);
                $body = $twig->load($boilerPlate->getId())->render(['record' => $document]);
            } else {
                $body = 'see document attached';
            }


            $msg = new Felamimail_Model_Message([
                'account_id' => $fmAccountId,
                'subject' => $t->_($document::getConfiguration()->recordName) . ' ' . $document->{Sales_Model_Document_Abstract::FLD_DOCUMENT_NUMBER}
                    . (($title = $document->getTitle()) ? ': ' . $title : '') . ' ' . Tinebase_DateTime::now()->setTimezone(Tinebase_Core::getUserTimezone())->toString(),
                'to' => $email,
                'body' => $body,
                'attachments' => $attachments,
            ], true);
            $msg = Felamimail_Controller_Message_Send::getInstance()->sendMessage($msg);
            // TODO FIXME this is not concurrency safe at all!!! need to fix this code in felamimail
            if (null === ($sentMessage = Felamimail_Controller_Message::getInstance()->fetchRecentMessageFromFolder(
                    Felamimail_Controller_Account::getInstance()->getSystemFolder($fmAccountId, Felamimail_Model_Folder::FOLDER_SENT),
                    $msg))) {
                throw new Tinebase_Exception_Backend('could not find sent message in sent folder');
            }

        } catch (Throwable $t) {
            Tinebase_Exception::log($t);

            Sales_Controller_Document_DispatchHistory::getInstance()->create(new Sales_Model_Document_DispatchHistory([
                Sales_Model_Document_DispatchHistory::FLD_DOCUMENT_TYPE => $document::class,
                Sales_Model_Document_DispatchHistory::FLD_DOCUMENT_ID => $document->getId(),
                Sales_Model_Document_DispatchHistory::FLD_DISPATCH_TRANSPORT => static::class,
                Sales_Model_Document_DispatchHistory::FLD_DISPATCH_DATE => Tinebase_DateTime::now(),
                Sales_Model_Document_DispatchHistory::FLD_TYPE => Sales_Model_Document_DispatchHistory::DH_TYPE_FAIL,
                Sales_Model_Document_DispatchHistory::FLD_DISPATCH_REPORT => get_class($t) . ': ' . $t->getMessage(),
                Sales_Model_Document_DispatchHistory::FLD_DISPATCH_ID => $dispatchId,
                Sales_Model_Document_DispatchHistory::FLD_PARENT_DISPATCH_ID => $parentDispatchId,
            ]));

            return false;
        }

        /** @var Felamimail_Model_Message $sentMessage */
        $dispatchHistory = new Sales_Model_Document_DispatchHistory([
            Sales_Model_Document_DispatchHistory::FLD_DOCUMENT_TYPE => $document::class,
            Sales_Model_Document_DispatchHistory::FLD_DOCUMENT_ID => $document->getId(),
            Sales_Model_Document_DispatchHistory::FLD_DISPATCH_TRANSPORT => static::class,
            Sales_Model_Document_DispatchHistory::FLD_DISPATCH_DATE => Tinebase_DateTime::now(),
            Sales_Model_Document_DispatchHistory::FLD_TYPE => $this->{self::FLD_EXPECTS_FEEDBACK} ?
                Sales_Model_Document_DispatchHistory::DH_TYPE_WAIT_FOR_FEEDBACK : Sales_Model_Document_DispatchHistory::DH_TYPE_SUCCESS,
            Sales_Model_Document_DispatchHistory::FLD_DISPATCH_ID => $dispatchId,
            Sales_Model_Document_DispatchHistory::FLD_PARENT_DISPATCH_ID => $parentDispatchId,
            Sales_Model_Document_DispatchHistory::FLD_XPROPS => [
                'fmAccountId' => $fmAccountId,
                'sentMsgId' => $sentMessage->message_id,
            ],
        ]);

        $transaction = Tinebase_RAII::getTransactionManagerRAII();
        $addedHistoryId = Sales_Controller_Document_DispatchHistory::getInstance()->create($dispatchHistory)->getId();
        Sales_Controller_Document_DispatchHistory::getInstance()->fileMessageAttachment(
            ['record_id' => $addedHistoryId],
            $sentMessage,
            ['partId' => null, 'filename' => 'email.eml']
        );
        $transaction->release();

        return true;
    }

    protected static $_configurationObject = null;
}