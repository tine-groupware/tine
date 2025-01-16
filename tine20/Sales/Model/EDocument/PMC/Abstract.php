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

        $first = true;
        /** @var PaymentMeans $payMeans */
        foreach($paymentMeans as $payMeans) {
            $this->toUblPaymentMeans($payMeans, $pmc);
            if (!$first) {
                $payMeans->getPaymentMeansCode()->setName(null);
            }
            $first = false;
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
        $id = (new Tinebase_Twig(($local = new Zend_Locale($invoice->{Sales_Model_Document_Invoice::FLD_DOCUMENT_LANGUAGE})), Tinebase_Translation::getTranslation(Sales_Config::APP_NAME, $local)))
            ->getEnvironment()
            ->createTemplate(Sales_Config::getInstance()->{Sales_Config::PAYMENT_MEANS_ID_TMPL})
            ->render(['invoice' => $invoice]);
        return (new PaymentMeans())
            ->setPaymentID([new PaymentID($id)]); // BT-83 Verwendungszweck
    }
}