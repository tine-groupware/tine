<?php declare(strict_types=1);
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

class Sales_EDocument_Einvoicing_Invoice extends \Einvoicing\Invoice
{
    protected ?DateTime $invoicePeriodStart;
    protected ?DateTime $invoicePeriodEnd;

    public function getInvoicePeriodStart(): ?DateTime
    {
        return $this->invoicePeriodStart;
    }

    public function setInvoicePeriodStart(?DateTime $date): static
    {
        $this->invoicePeriodStart = $date;
        return $this;
    }

    public function getInvoicePeriodEnd(): ?DateTime
    {
        return $this->invoicePeriodEnd;
    }

    public function setInvoicePeriodEnd(?DateTime $date): static
    {
        $this->invoicePeriodEnd = $date;
        return $this;
    }
}