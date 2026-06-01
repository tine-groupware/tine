<?php declare(strict_types=1);
/**
 * Tine 2.0
 *
 * @package     Sales
 * @subpackage  Model
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2026 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */

/**
 * Credit Document Model
 *
 * @package     Sales
 * @subpackage  Model
 */
class Sales_Model_Document_Credit extends Sales_Model_Document_Abstract
{
    public const MODEL_NAME_PART = 'Document_Credit';
    public const TABLE_NAME = 'sales_document_credit';

    public const FLD_CREDIT_STATUS = 'credit_status';
    public const FLD_DOCUMENT_PROFORMA_NUMBER = 'document_proforma_number';
    public const FLD_PAY_AT = 'pay_at';
    public const FLD_PAID_AT = 'paid_at';


    /**
     * @param array $_definition
     */
    public static function inheritModelConfigHook(array &$_definition)
    {
        parent::inheritModelConfigHook($_definition);

        $_definition[self::CREATE_MODULE] = true;
        $_definition[self::RECORD_NAME] = 'Credit'; // gettext('GENDER_Credit')
        $_definition[self::RECORDS_NAME] = 'Credits'; // ngettext('Credit', 'Credits', n)

        $_definition[self::VERSION] = 1;
        $_definition[self::MODEL_NAME] = self::MODEL_NAME_PART;
        $_definition[self::TABLE][self::NAME] = self::TABLE_NAME;

        $_definition[self::FIELDS][self::FLD_POSITIONS][self::CONFIG][self::MODEL_NAME] =
            Sales_Model_DocumentPosition_Credit::MODEL_NAME_PART;

        $_definition[self::FIELDS][self::FLD_DOCUMENT_NUMBER][self::NULLABLE] = true;
        unset($_definition[self::FIELDS][self::FLD_DOCUMENT_NUMBER][self::CONFIG][Tinebase_Model_NumberableConfig::NO_AUTOCREATE]);
        $_definition[self::FIELDS][self::FLD_DOCUMENT_NUMBER][self::CONFIG][Tinebase_Numberable::CONFIG_OVERRIDE] =
            Sales_Controller_Document_Credit::class . '::documentNumberConfigOverride';

        unset($_definition[self::FIELDS][self::FLD_PAYMENT_TERMS]);
//        $_definition[self::FIELDS][self::FLD_PAYMENT_MEANS][self::TYPE] = self::TYPE_RECORD;

        $translate = Tinebase_Translation::getDefaultTranslation(Sales_Config::APP_NAME);
        Tinebase_Helper::arrayInsertAfterKey($_definition[self::FIELDS], self::FLD_DOCUMENT_NUMBER, [
            self::FLD_DOCUMENT_PROFORMA_NUMBER => [
                self::TYPE                      => self::TYPE_NUMBERABLE_STRING,
                self::LABEL                     => 'Proforma Number', //_('Proforma Number')
                self::QUERY_FILTER              => true,
                self::SHY                       => true,
                self::CONFIG                    => [
                    Tinebase_Numberable::STEPSIZE          => 1,
                    Tinebase_Numberable_String::PREFIX     => $translate->_('PC-'), // _('PC-')
                    Tinebase_Numberable_String::ZEROFILL   => 7,
                    Tinebase_Numberable::CONFIG_OVERRIDE   =>
                        Sales_Controller_Document_Credit::class . '::documentProformaNumberConfigOverride',
                ],
            ],
            self::FLD_CREDIT_STATUS => [
                self::LABEL => 'Status', // _('Status')
                self::TYPE => self::TYPE_KEY_FIELD,
                self::NAME => Sales_Config::DOCUMENT_CREDIT_STATUS,
                self::LENGTH => 255,
                self::NULLABLE => true,
            ],
        ]);

        Tinebase_Helper::arrayInsertAfterKey($_definition[self::FIELDS], self::FLD_GROSS_SUM, [
            self::FLD_PAY_AT => [
                self::LABEL             => 'Pay at', // _('Pay at')
                self::TYPE              => self::TYPE_DATE,
                self::NULLABLE          => true,
            ],
            self::FLD_PAID_AT => [
                self::LABEL             => 'Paid at', // _('Paid at')
                self::TYPE              => self::TYPE_DATE,
                self::NULLABLE          => true,
            ],
        ]);
/*
        $_definition[self::FIELDS] = array_merge($_definition[self::FIELDS], [
            self::FLD_ORDER_ID => [
                self::TYPE => self::TYPE_RECORD,
                self::DISABLED => true,
                self::CONFIG => [
                    self::APP_NAME => Sales_Config::APP_NAME,
                    self::MODEL_NAME => Sales_Model_Document_Order::MODEL_NAME_PART,
                ],
                self::NULLABLE => true,
            ],
        ]);*/
    }

    /**
     * holds the configuration object (must be declared in the concrete class)
     *
     * @var Tinebase_ModelConfiguration
     */
    protected static $_configurationObject = NULL;

    protected static string $_statusField = self::FLD_CREDIT_STATUS;
    protected static string $_statusConfigKey = Sales_Config::DOCUMENT_CREDIT_STATUS;
    protected static string $_documentNumberPrefix = 'CR-'; // _('CR-')
}
