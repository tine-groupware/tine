<?php declare(strict_types=1);
/**
 * class to hold EDocument dispatch data
 *
 * @package     Sales
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2025-2025 Metaways Infosystems GmbH (http://www.metaways.de)
 */

class Sales_Model_EDocument_Dispatch_Manual extends Tinebase_Record_NewAbstract implements Sales_Model_EDocument_Dispatch_Interface
{
    public const MODEL_NAME_PART = 'EDocument_Dispatch_Manual';
    public const FLD_INSTRUCTIONS = 'instructions';

    protected static $_modelConfiguration = [
        self::APP_NAME                  => Sales_Config::APP_NAME,
        self::MODEL_NAME                => self::MODEL_NAME_PART,
        self::RECORD_NAME               => 'Manual Dispatching', // gettext('GENDER_Manual Dispatching')
        self::RECORDS_NAME              => 'Manual Dispatchings', // ngettext('Manual Dispatching', 'Manual Dispatchings', n)
        self::TITLE_PROPERTY            => self::FLD_INSTRUCTIONS,

        self::FIELDS                    => [
            self::FLD_INSTRUCTIONS           => [
                self::LABEL                     => 'Instructions', // _('Instructions')
                self::TYPE                      => self::TYPE_FULLTEXT,
            ],
        ],
    ];
    protected static $_configurationObject = null;

    public function dispatch(Sales_Model_Document_Abstract $document, ?string $dispatchId = null): bool
    {
        $dispatchHistory = new Sales_Model_Document_DispatchHistory([
            Sales_Model_Document_DispatchHistory::FLD_DOCUMENT_TYPE => $document::class,
            Sales_Model_Document_DispatchHistory::FLD_DOCUMENT_ID => $document->getId(),
            Sales_Model_Document_DispatchHistory::FLD_DISPATCH_TRANSPORT => static::class,
            Sales_Model_Document_DispatchHistory::FLD_DISPATCH_DATE => Tinebase_DateTime::now(),
            Sales_Model_Document_DispatchHistory::FLD_DISPATCH_REPORT => $this->{self::FLD_INSTRUCTIONS},
            Sales_Model_Document_DispatchHistory::FLD_TYPE => Sales_Model_Document_DispatchHistory::DH_TYPE_START,
            Sales_Model_Document_DispatchHistory::FLD_DISPATCH_ID => $dispatchId ?? Tinebase_Record_Abstract::generateUID(),
        ]);

        if (null === $dispatchId) {
            /** @var Sales_Controller_Document_Abstract $docCtrl */
            $docCtrl = $document::getConfiguration()->getControllerInstance();
            $transaction = Tinebase_RAII::getTransactionManagerRAII();
            /** @var Sales_Model_Document_Abstract $document */
            $document = $docCtrl->get($document->getId());

            $document->{$document::getStatusField()} = Sales_Model_Document_Abstract::STATUS_MANUAL_DISPATCH;
            $document->{Sales_Model_Document_Abstract::FLD_DISPATCH_HISTORY}->addRecord($dispatchHistory);

            $docCtrl->update($document);
            $transaction->release();

        } else {
            $document->{$document::getStatusField()} = Sales_Model_Document_Abstract::STATUS_MANUAL_DISPATCH;
            Sales_Controller_Document_DispatchHistory::getInstance()->create($dispatchHistory);
        }

        return true;
    }
}