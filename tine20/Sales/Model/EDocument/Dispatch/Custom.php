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
        self::TITLE_PROPERTY            => '{% if dispatch_configs and dispatch_configs is iterable %}{{ dispatch_configs|map(c => renderModel(c.dispatch_type))|join(", ") }}{% else %}...{% endif %}',

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
                    [Tinebase_Record_Validator_SubValidate::class],
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

        Sales_Controller_Document_DispatchHistory::getInstance()->create(new Sales_Model_Document_DispatchHistory([
                Sales_Model_Document_DispatchHistory::FLD_DOCUMENT_ID => $document->getId(),
                Sales_Model_Document_DispatchHistory::FLD_DOCUMENT_TYPE => $document::class,
                Sales_Model_Document_DispatchHistory::FLD_DISPATCH_TRANSPORT => static::class,
                Sales_Model_Document_DispatchHistory::FLD_DISPATCH_DATE => Tinebase_DateTime::now(),
                Sales_Model_Document_DispatchHistory::FLD_DISPATCH_REPORT => $report,
                Sales_Model_Document_DispatchHistory::FLD_TYPE => Sales_Model_Document_DispatchHistory::DH_TYPE_START,
                Sales_Model_Document_DispatchHistory::FLD_DISPATCH_ID => $dispatchId,
                Sales_Model_Document_DispatchHistory::FLD_DISPATCH_CONFIG => clone $this,
            ])
        );

        $result = true;
        $report = $t->_('successfully dispatched the following dispatches:');
        $failed = $t->_('failed to dispatch the following dispatches:');
        /** @var Sales_Model_EDocument_Dispatch_DynamicConfig $dispatchConfig */
        foreach ($this->{self::FLD_DISPATCH_CONFIGS} as $dispatchConfig) {
            try {
                if ($dispatchConfig->{Sales_Model_EDocument_Dispatch_DynamicConfig::FLD_DISPATCH_CONFIG}->dispatch($document, $dispatchId)) {
                    $report .= PHP_EOL . $dispatchConfig->{Sales_Model_EDocument_Dispatch_DynamicConfig::FLD_DISPATCH_CONFIG}->getTitle();
                } else {
                    $result = false;
                    $failed .= PHP_EOL . $dispatchConfig->{Sales_Model_EDocument_Dispatch_DynamicConfig::FLD_DISPATCH_CONFIG}->getTitle();
                }
            } catch (Throwable $t) {
                Tinebase_Exception::log($t);
                $failed .= PHP_EOL . get_class($t) . ': ' . $t->getMessage();
                $result = false;
            }
        }

        Sales_Controller_Document_DispatchHistory::getInstance()->create(
            new Sales_Model_Document_DispatchHistory([
                Sales_Model_Document_DispatchHistory::FLD_DOCUMENT_ID => $document->getId(),
                Sales_Model_Document_DispatchHistory::FLD_DOCUMENT_TYPE => $document::class,
                Sales_Model_Document_DispatchHistory::FLD_DISPATCH_TRANSPORT => static::class,
                Sales_Model_Document_DispatchHistory::FLD_DISPATCH_DATE => Tinebase_DateTime::now(),
                Sales_Model_Document_DispatchHistory::FLD_DISPATCH_REPORT => $report . ($result ? '' : PHP_EOL . $failed),
                Sales_Model_Document_DispatchHistory::FLD_TYPE => $result ? Sales_Model_Document_DispatchHistory::DH_TYPE_SUCCESS : Sales_Model_Document_DispatchHistory::DH_TYPE_FAIL,
                Sales_Model_Document_DispatchHistory::FLD_DISPATCH_ID => $dispatchId,
            ])
        );

        return $result;
    }

    public function getRequiredDocumentTypes(): array
    {
        $requiredTypes = [];
        /** @var Sales_Model_EDocument_Dispatch_DynamicConfig $dispatchConfig */
        foreach ($this->{self::FLD_DISPATCH_CONFIGS} as $dispatchConfig) {
            $requiredTypes = array_merge($requiredTypes, $dispatchConfig->{Sales_Model_EDocument_Dispatch_DynamicConfig::FLD_DISPATCH_CONFIG}->getRequiredDocumentTypes());
        }
        return array_unique($requiredTypes);
    }

    public function getMissingDocumentTypes(Sales_Model_Document_Abstract $document): array
    {
        $missingDoyTypes = [];
        /** @var Sales_Model_EDocument_Dispatch_DynamicConfig $dispatchConfig */
        foreach ($this->{self::FLD_DISPATCH_CONFIGS} as $dispatchConfig) {
            $missingDoyTypes = array_merge($missingDoyTypes, $dispatchConfig->{Sales_Model_EDocument_Dispatch_DynamicConfig::FLD_DISPATCH_CONFIG}->getMissingDocumentTypes($document));
        }
        return array_unique($missingDoyTypes);
    }
}