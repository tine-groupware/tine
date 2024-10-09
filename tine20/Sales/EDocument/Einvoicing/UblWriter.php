<?php declare(strict_types=1);

use Einvoicing\Invoice;

/**
 * facade for AuthCodeGrant
 *
 * @package     Sales
 * @subpackage  EDocument
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2024 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

class Sales_EDocument_Einvoicing_UblWriter extends \Einvoicing\Writers\UblWriter
{
    /**
     * @param Sales_EDocument_Einvoicing_Invoice $invoice
     * @return string
     */
    public function export(Invoice $invoice): string
    {
        $result = parent::export($invoice);

        if ($invoice->getInvoicePeriodStart() && $invoice->getInvoicePeriodStart()) {
            $uxml = \UXML\UXML::fromString($result);
            $period = $uxml->add('cac:InvoicePeriod');
            $period->add('cbc:StartDate', $invoice->getInvoicePeriodStart()->format('Y-m-d'));
            $period->add('cbc:EndDate', $invoice->getInvoicePeriodEnd()->format('Y-m-d'));

            $result = $uxml->asXML();
        }

        return $result;
    }
}