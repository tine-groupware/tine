<?php declare(strict_types=1);

use UBL21\Common\CommonAggregateComponents\FinancialInstitutionBranch;
use UBL21\Common\CommonAggregateComponents\PayeeFinancialAccount;
use UBL21\Common\CommonBasicComponents\ID as UBL_ID;
use UBL21\Common\CommonBasicComponents\Name as UBL_Name;
use UBL21\Invoice\Invoice;

/**
 * Tine 2.0
 *
 * @package     Sales
 * @subpackage  Model
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2024 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */

class Sales_Model_EDocument_PMC_PayeeFinancialAccount extends Sales_Model_EDocument_PMC_Abstract
{
    public const MODEL_NAME_PART = 'EDocument_PMC_PayeeFinancialAccount';

    public static function inheritModelConfigHook(array &$_definition)
    {
        parent::inheritModelConfigHook($_definition);
        $_definition[self::MODEL_NAME] = self::MODEL_NAME_PART;
        $_definition[self::TITLE_PROPERTY] = 'All bank accounts'; // _('All bank accounts')
        $_definition[self::RECORD_NAME] = 'Payee Financial Account'; // gettext('GENDER_Payee Financial Account')
        $_definition[self::RECORDS_NAME] = 'Payee Financial Accounts'; // ngettext('Payee Financial Account', 'Payee Financial Accounts', n)
    }

    /**
     * holds the configuration object (must be declared in the concrete class)
     *
     * @var Tinebase_ModelConfiguration
     */
    protected static $_configurationObject = null;

    public function toUblInvoice(Invoice $ublInvoice, Sales_Model_Document_Invoice $invoice, Sales_Model_EDocument_PaymentMeansCode $pmc): void
    {
        $division = $invoice->{Sales_Model_Document_Invoice::FLD_DOCUMENT_CATEGORY}->{Sales_Model_Document_Category::FLD_DIVISION_ID};

        $ublInvoice->setPaymentMeans([$paymentMeans = $this->createPaymentMeans($invoice)]);
        $paymentMeansFirst = true;

        /** @var Tinebase_Model_BankAccount $bankAccount */
        foreach ($division->{Sales_Model_Division::FLD_BANK_ACCOUNTS} as $bankAccount) {
            $bankAccount = $bankAccount->{Sales_Model_DivisionBankAccount::FLD_BANK_ACCOUNT};
            if (!$paymentMeansFirst) {
                $paymentMeans = clone $paymentMeans;
                $paymentMeans->setPaymentID([clone $paymentMeans->getPaymentID()[0]]);
                $ublInvoice->addToPaymentMeans($paymentMeans);
            }
            $paymentMeansFirst = false;
            $paymentMeans->setPayeeFinancialAccount((new PayeeFinancialAccount)
                ->setID(new UBL_ID($bankAccount->{Tinebase_Model_BankAccount::FLD_IBAN}))
                ->setName(new UBL_Name($division->{Sales_Model_Division::FLD_NAME}))
                ->setFinancialInstitutionBranch((new FinancialInstitutionBranch)
                    ->setID(new UBL_ID($bankAccount->{Tinebase_Model_BankAccount::FLD_BIC}))
                )
            );
        }

        parent::toUblInvoice($ublInvoice, $invoice, $pmc);
    }
}