<?php declare(strict_types=1);
/**
 * class to hold EDocument dispatch data
 *
 * @package     Sales
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2025-2025 Metaways Infosystems GmbH (http://www.metaways.de)
 */

class Sales_Model_EDocument_Dispatch_Custom extends Tinebase_Record_NewAbstract implements Sales_Model_EDocument_Dispatch_Interface
{
    public const MODEL_NAME_PART = 'EDocument_Dispatch_Custom';

    public const FLD_DISPATCH_CONFIGS = 'dispatch_configs';

    protected static $_modelConfiguration = [
        self::APP_NAME                  => Sales_Config::APP_NAME,
        self::MODEL_NAME                => self::MODEL_NAME_PART,
        self::RECORD_NAME               => 'Custom Config', // gettext('GENDER_Custom Config')
        self::RECORDS_NAME              => 'Custom Configs', // ngettext('Custom Config', 'Custom Configs', n)
//        self::TITLE_PROPERTY            => '{% for config in dispatch_configs %}{{ renderModel(config.dispatch_type) }}{% endfor %}',
        self::TITLE_PROPERTY            => '{{ dispatch_configs|map(c => renderModel(c.dispatch_type))|join(", ") }}',

        self::FIELDS                    => [
            self::FLD_DISPATCH_CONFIGS        => [
                self::TYPE                      => self::TYPE_RECORDS,
                self::LABEL                     => 'Custom Configs', // _('Custom Configs')
                self::CONFIG                    => [
                    self::APP_NAME                  => Sales_Config::APP_NAME,
                    self::MODEL_NAME                => Sales_Model_EDocument_Dispatch_DynamicConfig::MODEL_NAME_PART,
                    self::STORAGE                   => self::TYPE_JSON,
                ],
                self::VALIDATORS                => [
                    Zend_Filter_Input::ALLOW_EMPTY  => false,
                    Zend_Filter_Input::PRESENCE     => Zend_Filter_Input::PRESENCE_REQUIRED,
                ],
                self::UI_CONFIG                 => [
                    'allowDuplicatePicks'           => true,
                    'columns'                       => [
                        Sales_Model_EDocument_Dispatch_DynamicConfig::FLD_DISPATCH_TYPE,
                        Sales_Model_EDocument_Dispatch_DynamicConfig::FLD_DISPATCH_CONFIG,
                    ]
                ]
            ],
        ],
    ];

    protected static $_configurationObject = null;

    public function dispatch(Sales_Model_Document_Abstract $document, ?string $parentDispatchId = null): bool
    {
        if (null !== $parentDispatchId) {
            throw new Tinebase_Exception_UnexpectedValue('parentDispatchId needs to be null');
        }
        $dispatchId = Tinebase_Record_Abstract::generateUID();

        $t = Tinebase_Translation::getDefaultTranslation(Sales_Config::APP_NAME);
        $report = $t->_('starting to dispatch the following dispatches:');
        /** @var Sales_Model_EDocument_Dispatch_DynamicConfig $dispatchConfig */
        foreach ($this->{self::FLD_DISPATCH_CONFIGS} as $dispatchConfig) {
            $report .= PHP_EOL . $dispatchConfig->{Sales_Model_EDocument_Dispatch_DynamicConfig::FLD_DISPATCH_CONFIG}->getTitle();
        }

        /** @var Sales_Controller_Document_Abstract $docCtrl */
        $docCtrl = $document::getConfiguration()->getControllerInstance();
        $transaction = Tinebase_RAII::getTransactionManagerRAII();
        /** @var Sales_Model_Document_Abstract $document */
        $document = $docCtrl->get($document->getId());

        $document->{$document::getStatusField()} = Sales_Model_Document_Abstract::STATUS_MANUAL_DISPATCH;
        $document->{Sales_Model_Document_Abstract::FLD_DISPATCH_HISTORY}->addRecord(
            new Sales_Model_Document_DispatchHistory([
                Sales_Model_Document_DispatchHistory::FLD_DISPATCH_TRANSPORT => static::class,
                Sales_Model_Document_DispatchHistory::FLD_DISPATCH_DATE => Tinebase_DateTime::now(),
                Sales_Model_Document_DispatchHistory::FLD_DISPATCH_REPORT => $report,
                Sales_Model_Document_DispatchHistory::FLD_TYPE => Sales_Model_Document_DispatchHistory::DH_TYPE_START,
                Sales_Model_Document_DispatchHistory::FLD_DISPATCH_ID => $dispatchId,
            ])
        );

        /** @var Sales_Model_Document_Abstract $document */
        $document = $docCtrl->update($document);
        $transaction->release();

        // we need to change the dispatch state in order for dispatch state to be set correctly by the child dispatchers
        $document->{$document::getStatusField()} = Sales_Config::getInstance()->{$document::getStatusConfigKey()}->records->filter(
                fn ($rec) => $rec->{Sales_Model_Document_Status::FLD_BOOKED} && !$rec->{Sales_Model_Document_Status::FLD_CLOSED}
                    && $rec->getId() !== Sales_Model_Document_Abstract::STATUS_MANUAL_DISPATCH && $rec->getId() !== Sales_Model_Document_Abstract::STATUS_DISPATCHED
            )->getFirstRecord()->getId();

        $result = true;
        $report = $t->_('successfully dispatched the following dispatches:');
        $failed = $t->_('failed to dispatch the following dispatches:');
        /** @var Sales_Model_EDocument_Dispatch_DynamicConfig $dispatchConfig */
        foreach ($this->{self::FLD_DISPATCH_CONFIGS} as $dispatchConfig) {
            if ($dispatchConfig->{Sales_Model_EDocument_Dispatch_DynamicConfig::FLD_DISPATCH_CONFIG}->dispatch($document, $dispatchId)) {
                $report .= PHP_EOL . $dispatchConfig->{Sales_Model_EDocument_Dispatch_DynamicConfig::FLD_DISPATCH_CONFIG}->getTitle();
            } else {
                $result = false;
                $failed .= PHP_EOL . $dispatchConfig->{Sales_Model_EDocument_Dispatch_DynamicConfig::FLD_DISPATCH_CONFIG}->getTitle();
            }
        }

        $transaction = Tinebase_RAII::getTransactionManagerRAII();
        /** @var Sales_Model_Document_Abstract $document */
        $document = $docCtrl->get($document->getId());
        $document->{$document::getStatusField()} = $result && Sales_Model_Document_Abstract::STATUS_MANUAL_DISPATCH !== $document->{$document::getStatusField()} ? Sales_Model_Document_Abstract::STATUS_DISPATCHED : Sales_Model_Document_Abstract::STATUS_MANUAL_DISPATCH;
        $document->{Sales_Model_Document_Abstract::FLD_DISPATCH_HISTORY}->addRecord(
            new Sales_Model_Document_DispatchHistory([
                Sales_Model_Document_DispatchHistory::FLD_DISPATCH_TRANSPORT => static::class,
                Sales_Model_Document_DispatchHistory::FLD_DISPATCH_DATE => Tinebase_DateTime::now(),
                Sales_Model_Document_DispatchHistory::FLD_DISPATCH_REPORT => $report . ($result ? '' : PHP_EOL . $failed),
                Sales_Model_Document_DispatchHistory::FLD_TYPE => $result ? Sales_Model_Document_DispatchHistory::DH_TYPE_SUCCESS : Sales_Model_Document_DispatchHistory::DH_TYPE_FAIL,
                Sales_Model_Document_DispatchHistory::FLD_DISPATCH_ID => $dispatchId,
            ])
        );
        $docCtrl->update($document);
        $transaction->release();

        return $result;
    }
}