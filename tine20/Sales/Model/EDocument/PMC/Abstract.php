<?php declare(strict_types=1);
/**
 * Tine 2.0
 *
 * @package     Sales
 * @subpackage  Model
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2024 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */

use UBL21\Common\CommonAggregateComponents\PaymentMeans;
use UBL21\Common\CommonBasicComponents\PaymentID;
use UBL21\Common\CommonBasicComponents\PaymentMeansCode;
use UBL21\Invoice\Invoice;

abstract class Sales_Model_EDocument_PMC_Abstract extends Tinebase_Record_NewAbstract
{
    /**
     * Holds the model configuration (must be assigned in the concrete class)
     *
     * @var array
     */
    protected static $_modelConfiguration = [
        self::APP_NAME                      => Sales_Config::APP_NAME,
    ];

    public function toUblInvoice(Invoice $ublInvoice, Sales_Model_Document_Invoice $invoice, Sales_Model_EDocument_PaymentMeansCode $pmc): void
    {
        if (!($paymentMeans = $ublInvoice->getPaymentMeans())) {
            $ublInvoice->setPaymentMeans($paymentMeans = [$this->createPaymentMeans($invoice)]);
        }
        /** @var PaymentMeans $payMeans */
        foreach($paymentMeans as $payMeans) {
            $this->toUblPaymentMeans($payMeans, $pmc);
        }
    }

    public function toUblPaymentMeans(PaymentMeans $paymentMeans, Sales_Model_EDocument_PaymentMeansCode $pmc): void
    {
        $paymentMeans
            ->setPaymentMeansCode((new PaymentMeansCode($pmc->{Sales_Model_EDocument_PaymentMeansCode::FLD_CODE}))
                ->setName($pmc->{Sales_Model_EDocument_PaymentMeansCode::FLD_NAME}));
    }

    protected function createPaymentMeans(Sales_Model_Document_Invoice $invoice): PaymentMeans
    {
        return (new PaymentMeans())
            ->setPaymentID([new PaymentID($invoice->{Sales_Model_Document_Invoice::FLD_DOCUMENT_NUMBER} . ' ' . $invoice->{Sales_Model_Document_Invoice::FLD_DEBITOR_ID}->{Sales_Model_Debitor::FLD_NUMBER})]); // BT-83 Verwendungszweck
    }
}