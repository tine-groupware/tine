<?php declare(strict_types=1);
/**
 * Tine 2.0
 *
 * @package     Sales
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2024 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

use \Einvoicing\Invoice;

/**
 * class for Sales UBL Einvoicing Preset for XRechnung
 *
 * @package     Setup
 */
class Sales_EDocument_Einvoicing_Preset_XRechnung extends \Einvoicing\Presets\AbstractPreset
{
    public function getSpecification(): string
    {
        return "urn:cen.eu:en16931:2017#compliant#urn:xeinkauf.de:kosit:xrechnung_3.0";
    }

    public function setupInvoice(Invoice $invoice): void
    {
        parent::setupInvoice($invoice);
        $invoice->setBusinessProcess('urn:fdc:peppol.eu:2017:poacc:billing:01:1.0');
    }
}

