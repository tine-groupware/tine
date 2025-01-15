<?php declare(strict_types=1);
/**
 * Tine 2.0 - http://www.tine20.org
 *
 * @package     Sales
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2021 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */

use Tinebase_Model_Filter_Abstract as TMFA;

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
        $division = self::makeDefaultDivisonUblReady();

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
            'vatid' => 'DE0987654321',
            Sales_Model_Customer::FLD_DEBITORS => [[
                Sales_Model_Debitor::FLD_NAME => '-',
                Sales_Model_Debitor::FLD_DIVISION_ID => $division->getId(),
                'delivery' => new Tinebase_Record_RecordSet(Sales_Model_Address::class,[[
                    'name' => 'some delivery address for ' . $name,
                    'type' => 'delivery'
                ]]),
                'billing' => new Tinebase_Record_RecordSet(Sales_Model_Address::class,[[
                    Sales_Model_Address::FLD_POSTALCODE => '12345',
                    Sales_Model_Address::FLD_LOCALITY => 'Neu Altdorf',
                    'name' => 'some billing address for ' . $name,
                    'type' => 'billing'
                ]]),
                Sales_Model_Debitor::FLD_EAS_ID => Sales_Controller_EDocument_EAS::getInstance()->search(Tinebase_Model_Filter_FilterGroup::getFilterForModel(Sales_Model_EDocument_EAS::class, [
                    [TMFA::FIELD => Sales_Model_EDocument_EAS::FLD_CODE, TMFA::OPERATOR => TMFA::OP_EQUALS, TMFA::VALUE => '9930'],
                ]))->getFirstRecord(),
                Sales_Model_Debitor::FLD_ELECTRONIC_ADDRESS => 'DE0987654321',
                Sales_Model_Debitor::FLD_BUYER_REFERENCE => 'buy ref',
            ]],
        ]));

        Tinebase_Record_Expander::expandRecord($customer);
        return $customer;
    }

    public function makeDefaultDivisonUblReady()
    {
        $division = Sales_Controller_Division::getInstance()->get(Sales_Config::getInstance()->{Sales_Config::DEFAULT_DIVISION});

        if ($division->{Sales_Model_Division::FLD_BANK_ACCOUNTS}->count() === 0) {
            $bankAccounts = Tinebase_Controller_BankAccount::getInstance()->getAll();
            if ($bankAccounts->count() === 0) {
                $bankAccounts->addRecord(Tinebase_Controller_BankAccount::getInstance()->create(new Tinebase_Model_BankAccount([
                    Tinebase_Model_BankAccount::FLD_NAME => 'unittest',
                    Tinebase_Model_BankAccount::FLD_BIC => 'BYLADEM1001',
                    Tinebase_Model_BankAccount::FLD_IBAN => 'DE02120300000000202051',
                ])));
            }
            $division->{Sales_Model_Division::FLD_BANK_ACCOUNTS}->addRecord(new Sales_Model_DivisionBankAccount([
                Sales_Model_DivisionBankAccount::FLD_BANK_ACCOUNT => $bankAccounts->getFirstRecord(),
            ], true));
        }

        if (empty($division->{Sales_Model_Division::FLD_ELECTRONIC_ADDRESS})) {
            $division->{Sales_Model_Division::FLD_EAS_ID} = Sales_Controller_EDocument_EAS::getInstance()->search(Tinebase_Model_Filter_FilterGroup::getFilterForModel(Sales_Model_EDocument_EAS::class, [
                [TMFA::FIELD => Sales_Model_EDocument_EAS::FLD_CODE, TMFA::OPERATOR => TMFA::OP_EQUALS, TMFA::VALUE => '9930'],
            ]))->getFirstRecord();
            $division->{Sales_Model_Division::FLD_ELECTRONIC_ADDRESS} = 'DE1234567890';
        }
        $division->{Sales_Model_Division::FLD_VAT_NUMBER} = 'DE1234567890';
        $division->{Sales_Model_Division::FLD_TAX_REGISTRATION_ID} = '1234567890';
        $division->{Sales_Model_Division::FLD_ADDR_COUNTRY} = 'de';
        $division->{Sales_Model_Division::FLD_CONTACT_PHONE} = '123';
        $division->{Sales_Model_Division::FLD_CONTACT_EMAIL} = 'test@foo.de';

        return Sales_Controller_Division::getInstance()->update($division);
    }
}
