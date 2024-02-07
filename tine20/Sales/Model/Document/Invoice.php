<?php declare(strict_types=1);
/**
 * Tine 2.0
 *
 * @package     Sales
 * @subpackage  Model
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2021-2023 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */

/**
 * Invoice Document Model
 *
 * @package     Sales
 * @subpackage  Model
 */
class Sales_Model_Document_Invoice extends Sales_Model_Document_Abstract
{
    public const MODEL_NAME_PART = 'Document_Invoice';
    public const TABLE_NAME = 'sales_document_invoice';

    public const FLD_INVOICE_STATUS = 'invoice_status';
    public const FLD_DOCUMENT_PROFORMA_NUMBER = 'document_proforma_number';

    public const FLD_IS_SHARED = 'is_shared';

    public const FLD_LAST_DATEV_SEND_DATE = 'last_datev_send_date';

    /**
     * invoice status
     */
    public const STATUS_PROFORMA = 'PROFORMA';
    public const STATUS_BOOKED = 'BOOKED';
    public const STATUS_SHIPPED = 'SHIPPED';
    public const STATUS_PAID = 'PAID';


    /**
     * @param array $_definition
     */
    public static function inheritModelConfigHook(array &$_definition)
    {
        parent::inheritModelConfigHook($_definition);

        $_definition[self::CREATE_MODULE] = true;
        $_definition[self::RECORD_NAME] = 'Invoice'; // gettext('GENDER_Invoice')
        $_definition[self::RECORDS_NAME] = 'Invoices'; // ngettext('Invoice', 'Invoices', n)

        $_definition[self::VERSION] = 2;
        $_definition[self::MODEL_NAME] = self::MODEL_NAME_PART;
        $_definition[self::TABLE] = [
            self::NAME                      => self::TABLE_NAME,
            /*self::INDEXES                   => [
                self::FLD_PRODUCT_ID            => [
                    self::COLUMNS                   => [self::FLD_PRODUCT_ID],
                ],
            ]*/
        ];

        // invoice recipient type
        $_definition[self::FIELDS][self::FLD_RECIPIENT_ID][self::CONFIG][self::TYPE] = Sales_Model_Document_Address::TYPE_BILLING;

        // invoice positions
        $_definition[self::FIELDS][self::FLD_POSITIONS][self::CONFIG][self::MODEL_NAME] =
            Sales_Model_DocumentPosition_Invoice::MODEL_NAME_PART;

        // invoice status
        Tinebase_Helper::arrayInsertAfterKey($_definition[self::FIELDS], self::FLD_DOCUMENT_NUMBER, [
            self::FLD_INVOICE_STATUS => [
                self::LABEL => 'Status', // _('Status')
                self::TYPE => self::TYPE_KEY_FIELD,
                self::NAME => Sales_Config::DOCUMENT_INVOICE_STATUS,
                self::LENGTH => 255,
                self::NULLABLE => true,
            ]
        ]);

        $_definition[self::FIELDS][self::FLD_DOCUMENT_NUMBER][self::NULLABLE] = true;
        $_definition[self::FIELDS][self::FLD_DOCUMENT_NUMBER][self::CONFIG][Tinebase_Numberable::CONFIG_OVERRIDE] =
            Sales_Controller_Document_Invoice::class . '::documentNumberConfigOverride';

        Tinebase_Helper::arrayInsertAfterKey($_definition[self::FIELDS], self::FLD_DOCUMENT_NUMBER, [
            self::FLD_DOCUMENT_PROFORMA_NUMBER => [
                self::TYPE                      => self::TYPE_NUMBERABLE_STRING,
                self::LABEL                     => 'Proforma Number', //_('Proforma Number')
                self::QUERY_FILTER              => true,
                self::SHY                       => true,
                self::CONFIG                    => [
                    Tinebase_Numberable::STEPSIZE          => 1,
                    Tinebase_Numberable::BUCKETKEY         => self::class . '#' . self::FLD_DOCUMENT_PROFORMA_NUMBER,
                    Tinebase_Numberable_String::PREFIX     => 'PI-', // _('PI-')
                    Tinebase_Numberable_String::ZEROFILL   => 7,
                    Tinebase_Numberable::CONFIG_OVERRIDE   =>
                        Sales_Controller_Document_Invoice::class . '::documentProformaNumberConfigOverride',
                ],
            ],
            self::FLD_LAST_DATEV_SEND_DATE       => [
                self::LABEL                 => 'Last Datev send date', // _('Last Datev send date')
                self::TYPE                  => self::TYPE_DATETIME,
                self::VALIDATORS            => array(Zend_Filter_Input::ALLOW_EMPTY => true),
                self::NULLABLE              => true,
                self::SHY                   => true,
            ],
        ]);

        $_definition[self::FIELDS][self::FLD_IS_SHARED] = [
            self::TYPE                  => self::TYPE_BOOLEAN,
            self::LABEL                 => 'Shared Document', //_('Shared Document')
            self::DEFAULT_VAL           => false,
            self::SHY                   => true,
        ];
    }

    /**
     * holds the configuration object (must be declared in the concrete class)
     *
     * @var Tinebase_ModelConfiguration
     */
    protected static $_configurationObject = NULL;

    protected static string $_statusField = self::FLD_INVOICE_STATUS;
    protected static string $_statusConfigKey = Sales_Config::DOCUMENT_INVOICE_STATUS;
    protected static string $_documentNumberPrefix = 'IN-'; // _('IN-')

    public function transitionFrom(Sales_Model_Document_Transition $transition)
    {
        parent::transitionFrom($transition);

        $sourceDoc = $transition->{Sales_Model_Document_Transition::FLD_SOURCE_DOCUMENTS}->getFirstRecord();
        switch ($sourceDoc->{Sales_Model_Document_TransitionSource::FLD_SOURCE_DOCUMENT_MODEL}) {
            case Sales_Model_Document_Order::class:
                $this->{self::FLD_RECIPIENT_ID} = $sourceDoc->{Sales_Model_Document_TransitionSource::FLD_SOURCE_DOCUMENT}
                    ->{Sales_Model_Document_Order::FLD_INVOICE_RECIPIENT_ID};
            case Sales_Model_Document_Invoice::class:
                break;
            default:
                throw new Tinebase_Exception_SystemGeneric('transition from ' . $sourceDoc->{Sales_Model_Document_TransitionSource::FLD_SOURCE_DOCUMENT_MODEL} . ' to ' . static::class . ' not allowed');
        }

        if (Sales_Config::INVOICE_DISCOUNT_SUM === $this->{self::FLD_INVOICE_DISCOUNT_TYPE}) {
            $this->_checkProductPrecursorPositionsComplete();
        }

        $this->{self::FLD_IS_SHARED} = $transition->{Sales_Model_Document_Transition::FLD_SOURCE_DOCUMENTS}->count() > 1;
    }
}
