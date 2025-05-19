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

use Sales_Model_Document_DispatchHistory as DispatchHistory;
use Tinebase_Model_Filter_Abstract as TMFA;

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
            Tinebase_Backend_Sql_Abstract::MODEL_NAME => DispatchHistory::class,
            Tinebase_Backend_Sql_Abstract::TABLE_NAME => DispatchHistory::TABLE_NAME,
            Tinebase_Backend_Sql_Abstract::MODLOG_ACTIVE => true,
        ]);
        $this->_modelName = DispatchHistory::class;
        $this->_purgeRecords = false;
        $this->_doContainerACLChecks = false;
    }

    protected function _inspectBeforeCreate(Tinebase_Record_Interface $_record)
    {
        parent::_inspectBeforeCreate($_record);

        $this->_registerOnCommitHook($_record);
    }

    protected function _registerOnCommitHook(DispatchHistory $history): void
    {
        Tinebase_TransactionManager::getInstance()->registerOnCommitCallback([static::class, 'onCommitCallback'], [$history]);
        Tinebase_TransactionManager::getInstance()->registerOnRollbackCallback([static::class, 'clearOnCommitCallbackCache']);
        Tinebase_TransactionManager::getInstance()->registerAfterCommitCallback([static::class, 'clearOnCommitCallbackCache']);
    }

    public function readEmailDispatchResponses(): bool
    {
        if (0 === ($dHistories = $this->search(Tinebase_Model_Filter_FilterGroup::getFilterForModel($this->_modelName, [
                    [TMFA::FIELD => DispatchHistory::FLD_TYPE, TMFA::OPERATOR => TMFA::OP_EQUALS, TMFA::VALUE => DispatchHistory::DH_TYPE_WAIT_FOR_FEEDBACK],
                    [TMFA::FIELD => DispatchHistory::FLD_FEEDBACK_RECEIVED, TMFA::OPERATOR => TMFA::OP_EQUALS, TMFA::VALUE => false],
                    [TMFA::FIELD => DispatchHistory::FLD_DISPATCH_TRANSPORT, TMFA::OPERATOR => TMFA::OP_EQUALS, TMFA::VALUE => Sales_Model_EDocument_Dispatch_Email::class],
                ])))->count()) {
            return true;
        }

        $msgs = [];
        /** @var DispatchHistory $dispatchHistory */
        foreach ($dHistories as $dispatchHistory) {
            if (null === ($fmAccountId = ($dispatchHistory->xprops()['fmAccountId'] ?? null)) ||
                    null === ($sentMsgId = ($dispatchHistory->xprops()['sentMsgId'] ?? null))) {
                continue;
            }
            $msgs[$fmAccountId][$sentMsgId] = $dispatchHistory->getId();
        }

        foreach ($msgs as $fmAccountId => $msgDispatchHistories) {
            Felamimail_Controller_Cache_Message::getInstance()->updateCache(
                $inbox = Felamimail_Controller_Folder::getInstance()->getByBackendAndGlobalName($fmAccountId, 'INBOX'),
                10, getrandmax()
            );

            $cache = Tinebase_Core::getCache();
            $cacheId = Tinebase_Helper::convertCacheId(
                'readEmailDispatchResponses' . $fmAccountId
            );
            if (false === ($skipMsgCache = $cache->load($cacheId))) {
                $skipMsgCache = [];
            }
            $newSkipMsgCache = [];

            Felamimail_Controller_Cache_Message::getInstance()->doContainerACLChecks(false);
            foreach (Felamimail_Controller_Cache_Message::getInstance()->search(Tinebase_Model_Filter_FilterGroup::getFilterForModel(Felamimail_Model_Message::class, [
                        [TMFA::FIELD => 'folder_id', TMFA::OPERATOR => TMFA::OP_EQUALS, TMFA::VALUE => $inbox->getId()],
                    ]), _onlyIds: ['id', 'messageuid']) as $msgId => $msgUid) {
                if ($skipMsgCache[$msgUid] ?? 0 > 2) {
                    $newSkipMsgCache[$msgUid] = 3;
                    continue;
                }

                try {
                    $msg = new Felamimail_Model_Message([
                        'id' => $msgId,
                        'messageuid' => $msgUid,
                        'folder_id' => $inbox->getId(),
                    ], true);
                    $headers = Felamimail_Controller_Message::getInstance()->getMessageHeaders($msg);

                    if ('accepted' !== strtolower($headers['x-zre-state'] ?? '') || !($msgDispatchHistories[$inReplyTo = ($headers['in-reply-to'] ?? null)] ?? false)) {
                        $newSkipMsgCache[$msgUid] = ($skipMsgCache[$msgUid] ?? 0) + 1;
                        continue;
                    }

                    $dispatchHistory = $dHistories->getById($msgDispatchHistories[$inReplyTo]);
                    $transaction = Tinebase_RAII::getTransactionManagerRAII();

                    $dispatchHistory = $this->get($dispatchHistory->getId());
                    $dispatchHistory->{DispatchHistory::FLD_FEEDBACK_RECEIVED} = true;
                    $this->update($dispatchHistory);
                    $dispatchHistory = clone $dispatchHistory;
                    $dispatchHistory->setId(null);
                    $dispatchHistory->{DispatchHistory::FLD_FEEDBACK_RECEIVED} = false;
                    $dispatchHistory->{DispatchHistory::FLD_TYPE} = DispatchHistory::DH_TYPE_SUCCESS;
                    $dispatchHistory->attachments = null;
                    $dispatchHistory->xprops = null;
                    $addedHistoryId = $this->create($dispatchHistory)->getId();

                    Sales_Controller_Document_DispatchHistory::getInstance()->fileMessageAttachment(
                        ['record_id' => $addedHistoryId],
                        Felamimail_Controller_Cache_Message::getInstance()->get($msg->getId()),
                        ['partId' => null, 'filename' => 'email.eml']
                    );

                    $transaction->release();
                    $newSkipMsgCache[$msgUid] = 3;
                } catch (Throwable $t) {
                    Tinebase_Exception::log($t);
                }
            }
            $cache->save($newSkipMsgCache, $cacheId);
        }

        return true;
    }

    public static function onCommitCallback(DispatchHistory $history): void
    {
        if (self::$onCommitCallbackCache[$docId = $history->getIdFromProperty(DispatchHistory::FLD_DOCUMENT_ID)] ?? false) {
            return;
        }
        self::$onCommitCallbackCache[$docId] = true;

        Tinebase_Record_Expander_DataRequest::clearCache();
        /** @var Tinebase_Record_Interface $model */
        $model = $history->{DispatchHistory::FLD_DOCUMENT_TYPE};
        /** @var Sales_Model_Document_Abstract $document */
        $document = $model::getConfiguration()->getControllerInstance()->get($docId);

        /** @var Tinebase_Record_RecordSet $historyList */
        $historyList = $document->{Sales_Model_Document_Abstract::FLD_DISPATCH_HISTORY}->filter(DispatchHistory::FLD_DISPATCH_ID, $history->{DispatchHistory::FLD_DISPATCH_ID});
        $historyList->mergeById($document->{Sales_Model_Document_Abstract::FLD_DISPATCH_HISTORY}->filter(DispatchHistory::FLD_PARENT_DISPATCH_ID, $history->{DispatchHistory::FLD_DISPATCH_ID}));
        $historyListSuccessCount = $historyList->filter(DispatchHistory::FLD_TYPE, DispatchHistory::DH_TYPE_SUCCESS)->count();
        $historyListStartCount = $historyList->filter(DispatchHistory::FLD_TYPE, DispatchHistory::DH_TYPE_START)->count();
        $historyListFailCount = $historyList->filter(DispatchHistory::FLD_TYPE, DispatchHistory::DH_TYPE_FAIL)->count();

        if (0 === $historyListFailCount && $historyListStartCount === $historyListSuccessCount) {
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
