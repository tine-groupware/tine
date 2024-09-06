<?php declare(strict_types=1);
/**
 * Tine 2.0 - http://www.tine20.org
 *
 * @package     Sales
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2021 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */

/**
 * AbstractTest class for Sales_Document_*
 */
class Sales_Document_Abstract extends TestCase
{
    protected function _createProduct(array $data = []): Sales_Model_Product
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return Sales_Controller_Product::getInstance()->create(new Sales_Model_Product(array_merge([
            Sales_Model_Product::FLD_NAME => [[
                Sales_Model_ProductLocalization::FLD_LANGUAGE => 'en',
                Sales_Model_ProductLocalization::FLD_TEXT => Tinebase_Record_Abstract::generateUID(),
            ]],
        ], $data)));
    }

    protected function _createCustomer(): Sales_Model_Customer
    {
        $division = Sales_Controller_Division::getInstance()->get(Sales_Config::getInstance()->{Sales_Config::DEFAULT_DIVISION});
        if ($division->{Sales_Model_Division::FLD_BANK_ACCOUNTS}->count() === 0) {
            $bankAccounts = Tinebase_Controller_BankAccount::getInstance()->getAll();
            if ($bankAccounts->count() === 0) {
                $bankAccounts->addRecord(Tinebase_Controller_BankAccount::getInstance()->create(new Tinebase_Model_BankAccount([
                    Tinebase_Model_BankAccount::FLD_NAME => 'unittest',
                    Tinebase_Model_BankAccount::FLD_BIC => 'unittest',
                    Tinebase_Model_BankAccount::FLD_IBAN => 'unittest',
                ])));
            }
            $division->{Sales_Model_Division::FLD_BANK_ACCOUNTS}->addRecord(new Sales_Model_DivisionBankAccount([
                Sales_Model_DivisionBankAccount::FLD_BANK_ACCOUNT => $bankAccounts->getFirstRecord(),
            ], true));
            Sales_Controller_Division::getInstance()->update($division);
        }

        $name = Tinebase_Record_Abstract::generateUID();
        /** @var Sales_Model_Customer $customer */
        $customer = Sales_Controller_Customer::getInstance()->create(new Sales_Model_Customer([
            'name' => $name,
            'cpextern_id' => $this->_personas['sclever']->contact_id,
            'bic' => 'SOMEBIC',
            'postal' => new Sales_Model_Address([
                'name' => 'some postal address for ' . $name,
                'street' => 'teststreet for ' . $name,
                'type' => 'postal'
            ]),
            Sales_Model_Customer::FLD_DEBITORS => [[
                Sales_Model_Debitor::FLD_NAME => '-',
                Sales_Model_Debitor::FLD_DIVISION_ID => $division->getId(),
                'delivery' => new Tinebase_Record_RecordSet(Sales_Model_Address::class,[[
                    'name' => 'some delivery address for ' . $name,
                    'type' => 'delivery'
                ]]),
                'billing' => new Tinebase_Record_RecordSet(Sales_Model_Address::class,[[
                    'name' => 'some billing address for ' . $name,
                    'type' => 'billing'
                ]]),
            ]],
        ]));

        Tinebase_Record_Expander::expandRecord($customer);
        return $customer;
    }
}
