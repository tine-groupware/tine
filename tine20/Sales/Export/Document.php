<?php declare(strict_types=1);
/**
 * Tine 2.0
 *
 * @license      http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author       Paul Mehrer <p.mehrer@metaways.de>
 * @copyright    Copyright (c) 2021-2024 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * Class Sales_Export_Document
 */
class Sales_Export_Document extends Tinebase_Export_DocV2
{
    // we need to set locale etc before loading twig, so we overwrite _loadTwig
    protected function _loadTwig()
    {
        $this->_records = $this->_controller->search($this->_filter);
        if ($this->_records->count() !== 1) {
            throw new Tinebase_Exception_Record_Validation('can only export exactly one document at a time');
        }

        (new Tinebase_Record_Expander($this->_modelName, [
            Tinebase_Record_Expander::EXPANDER_PROPERTIES => [
                Sales_Model_Document_Abstract::FLD_CUSTOMER_ID => [
                    Tinebase_Record_Expander::EXPANDER_PROPERTIES => [
                        'cpextern_id'   => [],
                        'cpintern_id'   => [],
                    ],
                ],
                Sales_Model_Document_Abstract::FLD_RECIPIENT_ID => [],
                Sales_Model_Document_Abstract::FLD_DEBITOR_ID => [],
                Sales_Model_Document_Abstract::FLD_POSITIONS => [],
                Sales_Model_Document_Abstract::FLD_BOILERPLATES => [],
                Sales_Model_Document_Abstract::FLD_DOCUMENT_CATEGORY => [
                    Tinebase_Record_Expander::EXPANDER_PROPERTIES => [
                        Sales_Model_Document_Category::FLD_DIVISION_ID => [
                            Tinebase_Record_Expander::EXPANDER_PROPERTIES => [
                                Sales_Model_Division::FLD_BANK_ACCOUNTS => [
                                    Tinebase_Record_Expander::EXPANDER_PROPERTIES => [
                                        Sales_Model_DivisionBankAccount::FLD_BANK_ACCOUNT => [],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ]
        ]))->expand($this->_records);

        /** @var Sales_Model_Document_Abstract $record */
        $record = $this->_records->getFirstRecord();
        $cats = explode('/', $record->{Sales_Model_Document_Abstract::FLD_DOCUMENT_CATEGORY}->{Sales_Model_Document_Category::FLD_NAME});
        $division = str_replace('/', '', $record->{Sales_Model_Document_Abstract::FLD_DOCUMENT_CATEGORY}->{Sales_Model_Document_Category::FLD_DIVISION_ID}->{Sales_Model_Division::FLD_TITLE});
        $lang = str_replace('/', '', $record->{Sales_Model_Document_Abstract::FLD_DOCUMENT_LANGUAGE});
        $this->_locale = new Zend_Locale($record->{Sales_Model_Document_Abstract::FLD_DOCUMENT_LANGUAGE});
        Sales_Model_DocumentPosition_Abstract::setExportContextLocale($this->_locale);
        $this->_translate = Tinebase_Translation::getTranslation(Sales_Config::APP_NAME, $this->_locale);
        $config = Sales_Config::getInstance();

        $matchData = [
            'DIVISION-' . $division . '--',
            'LANG-' . $lang . '--',
        ];
        foreach ($cats as $cat) {
            $matchData[] = 'CATEGORY-' . $cat . '--';
        }

        if (null !== ($overwriteTemplate = $this->_findOverwriteTemplate($this->_templateFileName, $matchData))) {
            $this->_templateFileName = $overwriteTemplate;
            $this->_createDocument();
        }

        // do this after any _createDocument calls! otherwise we lose the watermark
        if (!$record->isBooked()) {
            $this->_docTemplate->addWaterMark('PROFORMA', null);
        }

        $vats = new Tinebase_Record_RecordSet(Tinebase_Config_KeyFieldRecord::class, []);
        foreach ($record->{Sales_Model_Document_Abstract::FLD_SALES_TAX_BY_RATE} ?? [] as $vat) {
            $vats->addRecord(new Tinebase_Config_KeyFieldRecord([
                'id' => $vat->{Sales_Model_Document_SalesTax::FLD_TAX_RATE},
                'value' => $vat->{Sales_Model_Document_SalesTax::FLD_TAX_AMOUNT},
            ], true));
        }
        $this->_records = [
            'PREPOSITIONS' => $this->_records,
            'POSITIONS' => $record->{Sales_Model_Document_Abstract::FLD_POSITIONS},
            'POSTPOSITIONS' => $this->_records,
            'VATS' => $vats,
            'POSTVATS' => $this->_records,
        ];


        if ($record->has(Sales_Model_Document_Abstract::FLD_VAT_PROCEDURE) &&
                $record->{Sales_Model_Document_Abstract::FLD_VAT_PROCEDURE} === Sales_Config::VAT_PROCEDURE_REVERSE_CHARGE) {
            $templates = $config->{Sales_Config::REVERSE_CHANGE_TEMPLATE};
            $record->{Sales_Model_Document_Abstract::FLD_VAT_PROCEDURE} = $templates[$lang] ?? $templates[$config->{Sales_Config::LANGUAGES_AVAILABLE}->default];
        }

        parent::_loadTwig();
    }

    /**
     * @param Tinebase_Record_Interface|null $_record
     */
    protected function _renderTwigTemplate($_record = null)
    {
        if (null === $_record) {
            $_record = $this->_records['PREPOSITIONS']->getFirstRecord();
        }
        parent::_renderTwigTemplate($_record);
    }

    protected function _getTwigContext(array $context)
    {
        $context['bankaccounts'] = $this->_records['PREPOSITIONS']->getFirstRecord()->{Sales_Model_Document_Abstract::FLD_DOCUMENT_CATEGORY}->{Sales_Model_Document_Category::FLD_DIVISION_ID}->{Sales_Model_Division::FLD_BANK_ACCOUNTS}->{Sales_Model_DivisionBankAccount::FLD_BANK_ACCOUNT};
        return parent::_getTwigContext($context);
    }

    protected function _startDataSource($_name)
    {
        parent::_startDataSource($_name);

        if ('POSITIONS' === $_name) {
            $this->_groupByProperty = Sales_Model_DocumentPosition_Abstract::FLD_GROUPING;
            $this->_groupByProcessor = function(?string &$grouping) {
                static $lastGrouping = null;
                static $groupCount = 0;

                if (null === $grouping) {
                    $grouping = '';
                }
                if (null === $lastGrouping) {
                    $lastGrouping = $grouping;
                } elseif ($lastGrouping !== $grouping) {
                    $lastGrouping = $grouping;
                    ++$groupCount;
                }

                if (preg_match('/^\[_[a-zA-Z0-9]+\]/', $grouping, $m)) {
                    $prefix = '';
                    if (preg_match('/^\[_[a-z]/', $grouping)) {
                        $i = $groupCount;
                        do {
                            $prefix .= chr(ord('a') + $i % 26);
                            $i -= 26;
                        } while($i >= 0);
                    } elseif (preg_match('/^\[_[A-Z]/', $grouping)) {
                        $i = $groupCount;
                        do {
                            $prefix .= chr(ord('A') + $i % 26);
                            $i -= 26;
                        } while($i >= 0);
                    } else {
                        if (($digits = strlen($m[0]) - 3) > 1) {
                            $prefix = sprintf('%0' . $digits . 'd', $groupCount);
                        } else {
                            $prefix = $groupCount;
                        }
                    }

                    $grouping = $prefix . substr($grouping, strlen($m[0]));
                }
            };
            $this->_groupByRecordProcessor = function (Sales_Model_DocumentPosition_Abstract $position, array &$context): void {
                $context['sum_net_price'] = ($context['sum_net_price'] ?? 0) + $position->{Sales_Model_DocumentPosition_Abstract::FLD_NET_PRICE};
                $context['sum_gross_price'] = ($context['sum_gross_price'] ?? 0) + $position->{Sales_Model_DocumentPosition_Abstract::FLD_GROSS_PRICE};
                $context['sum_sales_tax'] = ($context['sum_sales_tax'] ?? 0) + $position->{Sales_Model_DocumentPosition_Abstract::FLD_SALES_TAX};
                if (preg_match('/^\[_[a-zA-Z0-9]+\]/', $position->{Sales_Model_DocumentPosition_Abstract::FLD_GROUPING} ?? '', $m)) {
                    $context['sum_text'] = substr($position->{Sales_Model_DocumentPosition_Abstract::FLD_GROUPING}, strlen($m[0]));
                } else {
                    $context['sum_text'] = $position->{Sales_Model_DocumentPosition_Abstract::FLD_GROUPING};
                }
            };
        }
    }

    protected function _startGroup()
    {
        $this->_groupByContext = [];
        parent::_startGroup();
    }


    protected function _endDataSource($_name)
    {
        parent::_endDataSource($_name);

        if ('POSITIONS' === $_name) {
            $this->_groupByProperty = null;
            $this->_groupByRecordProcessor = null;
            $this->_groupByProcessor = null;
        }
    }
}
