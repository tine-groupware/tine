<?php declare(strict_types=1);

/**
 * DispatchHistory Document controller for Sales application
 *
 * @package     Sales
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2024 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * DispatchHistory Document controller class for Sales application
 *
 * @package     Sales
 * @subpackage  Controller
 */
class Sales_Controller_Document_DispatchHistory extends Tinebase_Controller_Record_Abstract
{
    use Tinebase_Controller_SingletonTrait;

    protected function __construct()
    {
        $this->_applicationName = Sales_Config::APP_NAME;
        $this->_backend = new Tinebase_Backend_Sql([
            Tinebase_Backend_Sql_Abstract::MODEL_NAME => Sales_Model_Document_DispatchHistory::class,
            Tinebase_Backend_Sql_Abstract::TABLE_NAME => Sales_Model_Document_DispatchHistory::TABLE_NAME,
            Tinebase_Backend_Sql_Abstract::MODLOG_ACTIVE => true,
        ]);
        $this->_modelName = Sales_Model_Document_DispatchHistory::class;
        $this->_purgeRecords = false;
        $this->_doContainerACLChecks = false;
    }

    protected function _inspectBeforeCreate(Tinebase_Record_Interface $_record)
    {
        parent::_inspectBeforeCreate($_record);

        $this->_registerOnCommitHook($_record);
    }

    protected function _inspectBeforeUpdate($_record, $_oldRecord)
    {
        throw new Tinebase_Exception_NotImplemented('dispatch history should not be updated');
    }

    protected function _registerOnCommitHook(Sales_Model_Document_DispatchHistory $history): void
    {
        Tinebase_TransactionManager::getInstance()->registerOnCommitCallback([static::class, 'onCommitCallback'], [$history]);
        Tinebase_TransactionManager::getInstance()->registerOnRollbackCallback([static::class, 'clearOnCommitCallbackCache']);
        Tinebase_TransactionManager::getInstance()->registerAfterCommitCallback([static::class, 'clearOnCommitCallbackCache']);
    }

    public static function onCommitCallback(Sales_Model_Document_DispatchHistory $history): void
    {
        if (self::$onCommitCallbackCache[$docId = $history->getIdFromProperty(Sales_Model_Document_DispatchHistory::FLD_DOCUMENT_ID)] ?? false) {
            return;
        }
        self::$onCommitCallbackCache[$docId] = true;

        Tinebase_Record_Expander_DataRequest::clearCache();
        /** @var Tinebase_Record_Interface $model */
        $model = $history->{Sales_Model_Document_DispatchHistory::FLD_DOCUMENT_TYPE};
        /** @var Sales_Model_Document_Abstract $document */
        $document = $model::getConfiguration()->getControllerInstance()->get($docId);

        /** @var Tinebase_Record_RecordSet $historyList */
        $historyList = $document->{Sales_Model_Document_Abstract::FLD_DISPATCH_HISTORY}->filter(Sales_Model_Document_DispatchHistory::FLD_DISPATCH_ID, $history->{Sales_Model_Document_DispatchHistory::FLD_DISPATCH_ID});
        $historyList->mergeById($document->{Sales_Model_Document_Abstract::FLD_DISPATCH_HISTORY}->filter(Sales_Model_Document_DispatchHistory::FLD_PARENT_DISPATCH_ID, $history->{Sales_Model_Document_DispatchHistory::FLD_DISPATCH_ID}));
        $historyListSuccessCount = $historyList->filter(Sales_Model_Document_DispatchHistory::FLD_TYPE, Sales_Model_Document_DispatchHistory::DH_TYPE_SUCCESS)->count();
        $historyListStartCount = $historyList->filter(Sales_Model_Document_DispatchHistory::FLD_TYPE, Sales_Model_Document_DispatchHistory::DH_TYPE_START)->count();

        if ($historyListStartCount === $historyListSuccessCount
                && $historyListStartCount + $historyListSuccessCount === $historyList->count()) {
            if ($document->{$document::getStatusField()} !== Sales_Model_Document_Abstract::STATUS_DISPATCHED) {
                $document->{$document::getStatusField()} = Sales_Model_Document_Abstract::STATUS_DISPATCHED;
            }
        } else {
            if ($document->{$document::getStatusField()} !== Sales_Model_Document_Abstract::STATUS_MANUAL_DISPATCH) {
                $document->{$document::getStatusField()} = Sales_Model_Document_Abstract::STATUS_MANUAL_DISPATCH;
            }
        }
        if ($document->isDirty()) {
            $model::getConfiguration()->getControllerInstance()->update($document);
        }
    }

    public static function clearOnCommitCallbackCache(): void
    {
        self::$onCommitCallbackCache = [];
    }

    protected static array $onCommitCallbackCache = [];
}
