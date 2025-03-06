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
        $_definition[self::TITLE_PROPERTY] = self::FLD_EMAIL;

        $_definition[self::FIELDS][self::FLD_EMAIL] = [
            self::TYPE              => self::TYPE_STRING,
            self::LABEL             => 'Email', // _('Email')
        ];
    }

    public function dispatch(Sales_Model_Document_Abstract $document, ?string $parentDispatchId = null): bool
    {
        $dispatchId = Tinebase_Record_Abstract::generateUID();
        $dispatchHistory = new Sales_Model_Document_DispatchHistory([
            Sales_Model_Document_DispatchHistory::FLD_DOCUMENT_TYPE => $document::class,
            Sales_Model_Document_DispatchHistory::FLD_DOCUMENT_ID => $document->getId(),
            Sales_Model_Document_DispatchHistory::FLD_DISPATCH_TRANSPORT => static::class,
            Sales_Model_Document_DispatchHistory::FLD_DISPATCH_DATE => Tinebase_DateTime::now(),
            Sales_Model_Document_DispatchHistory::FLD_TYPE => Sales_Model_Document_DispatchHistory::DH_TYPE_START,
            Sales_Model_Document_DispatchHistory::FLD_DISPATCH_ID => $dispatchId,
            Sales_Model_Document_DispatchHistory::FLD_PARENT_DISPATCH_ID => $parentDispatchId,
        ]);

        if (!$this->{self::FLD_EMAIL}) {
            if (!$document->{Sales_Model_Document_Abstract::FLD_RECIPIENT_ID}->{Sales_Model_Address::FLD_EMAIL}) {
                return false;
            }
            $email = $document->{Sales_Model_Document_Abstract::FLD_RECIPIENT_ID}->{Sales_Model_Address::FLD_EMAIL};
        } else {
            $email = $this->{self::FLD_EMAIL};
        }

        if (null === $parentDispatchId) {
            /** @var Sales_Controller_Document_Abstract $docCtrl */
            $docCtrl = $document::getConfiguration()->getControllerInstance();
            $transaction = Tinebase_RAII::getTransactionManagerRAII();
            /** @var Sales_Model_Document_Abstract $document */
            $document = $docCtrl->get($document->getId());

            $document->{$document::getStatusField()} = Sales_Model_Document_Abstract::STATUS_MANUAL_DISPATCH;
            $document->{Sales_Model_Document_Abstract::FLD_DISPATCH_HISTORY}->addRecord($dispatchHistory);

            $document = $docCtrl->update($document);
            $transaction->release();

        } else {
            Sales_Controller_Document_DispatchHistory::getInstance()->create($dispatchHistory);
        }

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

        $msg = new Felamimail_Model_Message([
            'account_id' => $fmAccountId,
            'subject' => 'dispatch',
            'to' => $email,
            'body' => 'see document attached',
            'attachments' => $attachments,
        ], true);
        $msg = Felamimail_Controller_Message_Send::getInstance()->sendMessage($msg);
        $sentMessage = Felamimail_Controller_Message::getInstance()->fetchRecentMessageFromFolder(
            Felamimail_Controller_Account::getInstance()->getSystemFolder($fmAccountId, Felamimail_Model_Folder::FOLDER_SENT),
            $msg
        );

        $dispatchHistory = new Sales_Model_Document_DispatchHistory([
            Sales_Model_Document_DispatchHistory::FLD_DOCUMENT_TYPE => $document::class,
            Sales_Model_Document_DispatchHistory::FLD_DOCUMENT_ID => $document->getId(),
            Sales_Model_Document_DispatchHistory::FLD_DISPATCH_TRANSPORT => static::class,
            Sales_Model_Document_DispatchHistory::FLD_DISPATCH_DATE => Tinebase_DateTime::now(),
            Sales_Model_Document_DispatchHistory::FLD_TYPE => Sales_Model_Document_DispatchHistory::DH_TYPE_SUCCESS,
            Sales_Model_Document_DispatchHistory::FLD_DISPATCH_ID => $dispatchId,
            Sales_Model_Document_DispatchHistory::FLD_PARENT_DISPATCH_ID => $parentDispatchId,
        ]);

        $transaction = Tinebase_RAII::getTransactionManagerRAII();
        if (null === $parentDispatchId) {
            /** @var Sales_Controller_Document_Abstract $docCtrl */
            $docCtrl = $document::getConfiguration()->getControllerInstance();
            /** @var Sales_Model_Document_Abstract $document */
            $document = $docCtrl->get($document->getId());

            $document->{$document::getStatusField()} = Sales_Model_Document_Abstract::STATUS_DISPATCHED;
            $oldHistoryIds = $document->{Sales_Model_Document_Abstract::FLD_DISPATCH_HISTORY}->getArrayOfIds();
            $document->{Sales_Model_Document_Abstract::FLD_DISPATCH_HISTORY}->addRecord($dispatchHistory);

            $document = $docCtrl->update($document);

            $addedHistoryId = array_values(array_diff($document->{Sales_Model_Document_Abstract::FLD_DISPATCH_HISTORY}->getArrayOfIds(), $oldHistoryIds));
        } else {
            $addedHistoryId = [Sales_Controller_Document_DispatchHistory::getInstance()->create($dispatchHistory)->getId()];
        }

        Sales_Controller_Document_DispatchHistory::getInstance()->fileMessageAttachment(
            ['record_id' => $addedHistoryId[0]],
            $sentMessage,
            ['partId' => null, 'name' => 'email']
        );

        $transaction->release();

        //TODO FIXME make add history abstract?

        return true;
    }

    protected static $_configurationObject = null;
}