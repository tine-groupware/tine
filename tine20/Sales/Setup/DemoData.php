<?php
/**
 * Tine 2.0
 *
 * @package     Sales
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * @copyright   Copyright (c) 2013-2025 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

use Tinebase_Model_Filter_Abstract as TMFA;

/**
 * class for Sales initialization
 *
 * @package     Setup
 */
class Sales_Setup_DemoData extends Tinebase_Setup_DemoData_Abstract
{
    /**
     * holds the instance of the singleton
     *
     * @var Sales_Setup_DemoData
     */
    private static $_instance = NULL;

    /**
     * The contract controller
     *
     * @var Sales_Controller_Contract
     */
    protected $_contractController = NULL;

    /**
     * required apps
     *
     * @var array
     */
    protected static array $_requiredApplications = array('Admin', 'Addressbook', Tinebase_Config::APP_NAME);

    /**
     * The product controller
     *
     * @var Sales_Controller_Product
     */
    protected $_productController  = NULL;

    /**
     * the application name to work on
     *
     * @var string
     */
    protected $_appName = 'Sales';
    /**
     * models to work on
     * @var array
     */
    protected $_models = array('product', 'customer', 'contract', 'invoice', 'orderconfirmation', 'offer');

    protected ?Sales_Model_Division $_division;

    /**
     * the constructor
     *
     */
    private function __construct()
    {
        $this->_productController     = Sales_Controller_Product::getInstance();
        $this->_contractController    = Sales_Controller_Contract::getInstance();
        $this->_division = Sales_Controller_Division::getInstance()->get(Sales_Config::getInstance()->{Sales_Config::DEFAULT_DIVISION});

        $updateDivision = false;

        if ($this->_division->{Sales_Model_Division::FLD_BANK_ACCOUNTS}->count() === 0) {
            $this->_division->{Sales_Model_Division::FLD_BANK_ACCOUNTS}->addRecord(new Sales_Model_DivisionBankAccount([
                Sales_Model_DivisionBankAccount::FLD_BANK_ACCOUNT => Tinebase_Controller_BankAccount::getInstance()->getAll()->getFirstRecord()->getId(),
            ], true));
            $updateDivision = true;
        }
        if (Tinebase_EmailUser::manages(Tinebase_Config::IMAP)
            && null === $this->_division->{Sales_Model_Division::FLD_DISPATCH_FM_ACCOUNT_ID}
        ) {
            $smtpConfig = Tinebase_Config::getInstance()->get(Tinebase_Config::SMTP, new Tinebase_Config_Struct())->toArray();
            $dispatchEmailAddress = 'dispatch@failedToFindDomain.tld';
            if (isset($smtpConfig['primarydomain'])) {
                $dispatchEmailAddress = 'dispatch@' . $smtpConfig['primarydomain'];
            }
            if (!empty(Tinebase_Core::getUser()->accountEmailAddress)) {
                list(,$domain) = explode('@', Tinebase_Core::getUser()->accountEmailAddress, 2);
                $dispatchEmailAddress = 'dispatch@' . $domain;
            }
            $filter = Tinebase_Model_Filter_FilterGroup::getFilterForModel(Felamimail_Model_Account::class, [
                [TMFA::FIELD => 'email', TMFA::OPERATOR => TMFA::OP_EQUALS, TMFA::VALUE => $dispatchEmailAddress],
                [TMFA::FIELD => 'type', TMFA::OPERATOR => TMFA::OP_EQUALS, TMFA::VALUE => Tinebase_EmailUser_Model_Account::TYPE_SHARED_INTERNAL],
            ]);
            if (null === ($dispatchFMAccount = Admin_Controller_EmailAccount::getInstance()->search($filter)->getFirstRecord())) {
                (new Admin_Frontend_Json())->saveEmailAccount([
                    'name' => 'sales dispatch mail account',
                    'email' => $dispatchEmailAddress,
                    'type' => Felamimail_Model_Account::TYPE_SHARED_INTERNAL,
                    'password' => '123',
                    'grants' => [
                        [
                            'readGrant' => true,
                            'editGrant' => true,
                            'addGrant' => true,
                            'account_type' => 'user',
                            'account_id' => Tinebase_Core::getUser()->getId(),
                        ]
                    ]
                ]);
                $dispatchFMAccount = Admin_Controller_EmailAccount::getInstance()->search($filter)->getFirstRecord();
            }

            $this->_division->{Sales_Model_Division::FLD_DISPATCH_FM_ACCOUNT_ID} = $dispatchFMAccount;
            $updateDivision = true;
        } else if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) {
            Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__
                . ' IMAP not managed - skip creating dispatch mail account.');
        }

        if ($updateDivision) {
            $this->_division = Sales_Controller_Division::getInstance()->update($this->_division);
        }

        $this->_loadCostCentersAndDivisions();
    }

    /**
     * the singleton pattern
     *
     * @return Sales_Setup_DemoData
     */
    public static function getInstance()
    {
        if (self::$_instance === NULL) {
            self::$_instance = new self();
        }

        return self::$_instance;
    }

    /**
     * unsets the instance to save memory, be aware that hasBeenRun still needs to work after unsetting!
     *
     */
    public function unsetInstance()
    {
        if (self::$_instance !== NULL) {
            self::$_instance = null;
        }
    }

    /**
     * this is required for other applications needing demo data of this application
     * if this returns true, this demodata has been run already
     *
     * @return boolean
     */
    public static function hasBeenRun()
    {
        $c = Sales_Controller_Contract::getInstance();

        $f = new Sales_Model_ContractFilter(array(
            array('field' => 'description', 'operator' => 'equals', 'value' => 'Created by Tine 2.0 DemoData'),
        ), 'AND');

        return ($c->search($f)->count() > 1) ? true : false;
    }

    /**
     * creates the products - no containers, just "shared"
     */
    protected function _createSharedProducts()
    {
        $l = Sales_Model_ProductLocalization::FLD_LANGUAGE;
        $t = Sales_Model_ProductLocalization::FLD_TEXT;

        $products = array(
            array(
                'name' => [[
                    Sales_Model_ProductLocalization::FLD_LANGUAGE => 'de',
                    Sales_Model_ProductLocalization::FLD_TEXT => '10 Port 100 MBit Ethernet Switch',
                ], [
                    Sales_Model_ProductLocalization::FLD_LANGUAGE => 'en',
                    Sales_Model_ProductLocalization::FLD_TEXT => '10 port 100 mbit ethernet switch',
                ]],
                'description' => [[
                    Sales_Model_ProductLocalization::FLD_LANGUAGE => 'de',
                    Sales_Model_ProductLocalization::FLD_TEXT => '10 Port 100 MBit Ethernet Switch RJ45 Stecker',
                ], [
                    Sales_Model_ProductLocalization::FLD_LANGUAGE => 'en',
                    Sales_Model_ProductLocalization::FLD_TEXT => '10 port 100 mbit ethernet switch, standard plug',
                ]],
                'salesprice' => 28.13,
            ),
            array(
                'name' => [[
                    Sales_Model_ProductLocalization::FLD_LANGUAGE => 'en',
                    Sales_Model_ProductLocalization::FLD_TEXT => '28 port 100 mbit ethernet switch PoE',
                ], [
                    Sales_Model_ProductLocalization::FLD_LANGUAGE => 'de',
                    Sales_Model_ProductLocalization::FLD_TEXT => '28 Port 100 MBit Ethernet Switch PoE',
                ]],
                'description' => [[
                    Sales_Model_ProductLocalization::FLD_LANGUAGE => 'en',
                    Sales_Model_ProductLocalization::FLD_TEXT => '28 port 100 mbit ethernet switch, PoE, standard plug',
                ], [
                    Sales_Model_ProductLocalization::FLD_LANGUAGE => 'de',
                    Sales_Model_ProductLocalization::FLD_TEXT => '28 Port Fast Ethernet Switch, PoE, RJ45',
                ]],
                'salesprice' => 1029.99,
            ),
            array(
                'name' => [[
                    Sales_Model_ProductLocalization::FLD_LANGUAGE => 'en',
                    Sales_Model_ProductLocalization::FLD_TEXT => '10 Port gigabit ethernet switch',
                ], [
                    Sales_Model_ProductLocalization::FLD_LANGUAGE => 'de',
                    Sales_Model_ProductLocalization::FLD_TEXT => '10 port 1 Gigabit ethernet switch',
                ]],
                'description' => [[
                    Sales_Model_ProductLocalization::FLD_LANGUAGE => 'de',
                    Sales_Model_ProductLocalization::FLD_TEXT => '10 Port 1 Gigabit Ethernet Switch RJ45 Stecker',
                ], [
                    Sales_Model_ProductLocalization::FLD_LANGUAGE => 'en',
                    Sales_Model_ProductLocalization::FLD_TEXT => '10 port gigabit ethernet switch, standard plug',
                ]],
                'salesprice' => 78.87,
                Sales_Model_Product::FLD_SALESTAXRATE => 0.0,
            ),
            array(
                'name' => [[
                    Sales_Model_ProductLocalization::FLD_LANGUAGE => 'en',
                    Sales_Model_ProductLocalization::FLD_TEXT => '28 Port gigabit ethernet switch PoE',
                ], [
                    Sales_Model_ProductLocalization::FLD_LANGUAGE => 'de',
                    Sales_Model_ProductLocalization::FLD_TEXT => '28 port 1 Gigabit ethernet switch PoE',
                ]],
                'description' => [[
                    Sales_Model_ProductLocalization::FLD_LANGUAGE => 'de',
                    Sales_Model_ProductLocalization::FLD_TEXT => '28 Port 1 Gigabit Ethernet Switch PoE RJ45 Stecker',
                ], [
                    Sales_Model_ProductLocalization::FLD_LANGUAGE => 'en',
                    Sales_Model_ProductLocalization::FLD_TEXT => '28 port gigabit ethernet switch, PoE standard plug',
                ]],
                'salesprice' => 3496.45,
                Sales_Model_Product::FLD_SALESTAXRATE => 7.0,
            ),
        );

        $default = array(
            'manufacturer' => 'SwitchCo',
            'category' => self::$_en ? 'LAN Equipment' : 'Netzwerkausrüstung',
            Sales_Model_Product::FLD_SALESTAXRATE => Tinebase_Config::getInstance()->{Tinebase_Config::SALES_TAX},
        );

        foreach($products as $key => $product) {
            $switches[$key] = $this->_productController->create(new Sales_Model_Product(array_merge($default, $product)));
        }

        $products = array(
            array(
                'name' => [[ $l => 'en', $t => '10m Cat. 5a red'], [$l => 'de', $t => '10m Kat. 5a rot']],
                'description' => [[ $l => 'en', $t => '10m Cat. 5a red cable up to 100MBit.'], [$l => 'de', $t => '10m Kat. 5a rotes Kabel. Erlaubt Übertragungsraten von bis zu 100MBit.']],
                'salesprice' => 5.99,
            ),
            array(
                'name' => [[ $l => 'en', $t => '10m Cat. 5a blue'], [$l => 'de', $t => '10m Kat. 5a blau']],
                'description' => [[ $l => 'en', $t => '10m Cat. 5a blue cable up to 100MBit.'], [$l => 'de', $t => '10m Kat. 5a blaues Kabel. Erlaubt Übertragungsraten von bis zu 100MBit.']],
                'salesprice' => 5.99,
            ),
            array(
                'name' => [[ $l => 'en', $t => '10m Cat. 6 red'], [$l => 'de', $t => '10m Kat. 6 rot']],
                'description' => [[ $l => 'en', $t => '10m Cat. 6 red cable up to 1000MBit.'], [$l => 'de', $t => '10m Kat. 5a rotes Kabel. Erlaubt Übertragungsraten von bis zu 1000MBit.']],
                'salesprice' => 9.99,
            ),
            array(
                'name' => [[ $l => 'en', $t => '10m Cat. 6 blue'], [$l => 'de', $t => '10m Kat. 6 blau']],
                'description' => [[ $l => 'en', $t => '10m Cat. 6 blue cable up to 1000MBit.'], [$l => 'de', $t => '10m Kat. 5a blaues Kabel. Erlaubt Übertragungsraten von bis zu 1000MBit.']],
                'salesprice' => 9.99,
            ),
        );

        $default = array(
            'manufacturer' => self::$_en ? 'Salad Cabels' : 'Salat Kabel & Co.',
            'category' => self::$_en ? 'LAN Equipment' : 'Netzwerkausrüstung',
            Sales_Model_Product::FLD_SALESTAXRATE => Tinebase_Config::getInstance()->{Tinebase_Config::SALES_TAX},
        );

        $subProductIds = [];

        foreach($products as $key => $product) {
            $subProductIds[$key] = $this->_productController->create(new Sales_Model_Product(array_merge($product, $default)));
        }

        $products = [
            [
                Sales_Model_Product::FLD_SHORTCUT => 'cable-set',
                Sales_Model_Product::FLD_UNIT => Sales_Model_Product::UNIT_PIECE,
                'name' => [[ $l => 'en', $t => 'Cable-set'], [$l => 'de', $t => 'Kabelsatz']],
                'description' => [[ $l => 'en', $t => 'colorful networkcable set'], [$l => 'de', $t => 'Farbiges Netzwerkkabelset']],
                'salesprice' => 38.99,
                Sales_Model_Product::FLD_UNFOLD_TYPE => Sales_Model_Product::UNFOLD_TYPE_SET,
                Sales_Model_Product::FLD_SUBPRODUCTS => [
                    [
                        Sales_Model_SubProductMapping::FLD_PRODUCT_ID => $subProductIds[0],
                        Sales_Model_SubProductMapping::FLD_SHORTCUT => '5ared',
                        Sales_Model_SubProductMapping::FLD_QUANTITY => 5,
                    ], [
                        Sales_Model_SubProductMapping::FLD_PRODUCT_ID => $subProductIds[1],
                        Sales_Model_SubProductMapping::FLD_SHORTCUT => '5ablue',
                        Sales_Model_SubProductMapping::FLD_QUANTITY => 5,
                    ], [
                        Sales_Model_SubProductMapping::FLD_PRODUCT_ID => $subProductIds[2],
                        Sales_Model_SubProductMapping::FLD_SHORTCUT => '6red',
                        Sales_Model_SubProductMapping::FLD_QUANTITY => 5,
                    ], [
                        Sales_Model_SubProductMapping::FLD_PRODUCT_ID => $subProductIds[3],
                        Sales_Model_SubProductMapping::FLD_SHORTCUT => '6blue',
                        Sales_Model_SubProductMapping::FLD_QUANTITY => 5,
                    ]
                ]
            ], [
                Sales_Model_Product::FLD_SHORTCUT => 'net-starter',
                Sales_Model_Product::FLD_UNIT => Sales_Model_Product::UNIT_PIECE,
                'name' => [[ $l => 'en', $t => 'Net-Starter'], [$l => 'de', $t => 'Netzstarter']],
                'description' => [[ $l => 'en', $t => 'network starter bundle'], [$l => 'de', $t => 'Netzwerk Starter']],
                'salesprice' => 99.99,
                Sales_Model_Product::FLD_UNFOLD_TYPE => Sales_Model_Product::UNFOLD_TYPE_BUNDLE,
                Sales_Model_Product::FLD_SUBPRODUCTS => [
                    [
                        Sales_Model_SubProductMapping::FLD_PRODUCT_ID => $switches[$key],
                        Sales_Model_SubProductMapping::FLD_SHORTCUT => '10sw',
                        Sales_Model_SubProductMapping::FLD_QUANTITY => 1,
                    ], [
                        Sales_Model_SubProductMapping::FLD_PRODUCT_ID => $subProductIds[0],
                        Sales_Model_SubProductMapping::FLD_SHORTCUT => '5ared',
                        Sales_Model_SubProductMapping::FLD_QUANTITY => 5,
                    ], [
                        Sales_Model_SubProductMapping::FLD_PRODUCT_ID => $subProductIds[1],
                        Sales_Model_SubProductMapping::FLD_SHORTCUT => '5ablue',
                        Sales_Model_SubProductMapping::FLD_QUANTITY => 5,
                    ]
                ]
            ]
        ];

        foreach($products as $product) {
            $this->_productController->create(new Sales_Model_Product(array_merge($default, $product)));
        }

        $products = [
            [
                'name' => 'vCPU',
                Sales_Model_Product::FLD_DEFAULT_GROUPING => 'Dynamic costs',
                Sales_Model_Product::FLD_SHORTCUT => 'vcpu',
                Sales_Model_Product::FLD_IS_SALESPRODUCT => false,
                'description' => 'vCPU',
                'salesprice' => 9.99,
            ], [
                'name' => 'RAM in GB',
                Sales_Model_Product::FLD_DEFAULT_GROUPING => 'Dynamic costs',
                Sales_Model_Product::FLD_SHORTCUT => 'vram',
                Sales_Model_Product::FLD_IS_SALESPRODUCT => false,
                'description' => 'RAM in GB',
                'salesprice' => 9.99,
            ]
        ];

        $default = array(
            'manufacturer' => 'Hosting Giant Ltd',
            'category' => 'Hosting',
            Sales_Model_Product::FLD_SALESTAXRATE => Tinebase_Config::getInstance()->{Tinebase_Config::SALES_TAX},
        );

        $subProductIds = [];

        foreach($products as $key => $product) {
            $product['name'] = [[
                Sales_Model_ProductLocalization::FLD_LANGUAGE => self::$_locale,
                Sales_Model_ProductLocalization::FLD_TEXT => $product['name'],
            ]];
            $product['description'] = [[
                Sales_Model_ProductLocalization::FLD_LANGUAGE => self::$_locale,
                Sales_Model_ProductLocalization::FLD_TEXT => $product['description'],
            ]];
            $subProductIds[$key] = $this->_productController->create(new Sales_Model_Product(array_merge($default, $product)))->getId();
        }

        $products = [
            [
                Sales_Model_Product::FLD_SHORTCUT => 'vm-small',
                Sales_Model_Product::FLD_DEFAULT_GROUPING => 'Periocical costs',
                Sales_Model_Product::FLD_DEFAULT_SORTING => 50000,
                Sales_Model_Product::FLD_UNIT => Sales_Model_Product::UNIT_PIECE,
                'name' => self::$_en ? 'VM small' : 'VM klein',
                'description' => self::$_en ? 'small, general purpose VM with {vcpu.quantity} vCPUs and {vram.quantity}GB RAM' : 'kleine, general purpose VM mit {vcpu.quantity} vCPUs und {vram.quantity}GB RAM',
                'salesprice' => 9.99,
                Sales_Model_Product::FLD_UNFOLD_TYPE => Sales_Model_Product::UNFOLD_TYPE_BUNDLE,
                Sales_Model_Product::FLD_SUBPRODUCTS => [
                    [
                        Sales_Model_SubProductMapping::FLD_PRODUCT_ID => $subProductIds[0],
                        Sales_Model_SubProductMapping::FLD_QUANTITY => 2,
                        Sales_Model_SubProductMapping::FLD_SHORTCUT => 'vcpu',
                        Sales_Model_SubProductMapping::FLD_VARIABLE_POSITION_FLAG => 'SHARED',

                    ], [
                        Sales_Model_SubProductMapping::FLD_PRODUCT_ID => $subProductIds[1],
                        Sales_Model_SubProductMapping::FLD_QUANTITY => 4,
                        Sales_Model_SubProductMapping::FLD_SHORTCUT => 'vram',
                        Sales_Model_SubProductMapping::FLD_VARIABLE_POSITION_FLAG => 'SHARED',
                    ]
                ]
            ], [
                Sales_Model_Product::FLD_SHORTCUT => 'vm-middle',
                Sales_Model_Product::FLD_DEFAULT_GROUPING => 'Periocical costs',
                Sales_Model_Product::FLD_UNIT => Sales_Model_Product::UNIT_PIECE,
                'name' => self::$_en ? 'VM middle' : 'VM mittel',
                'description' => self::$_en ? 'middle, general purpose VM with {vcpu.quantity} vCPUs and {vram.quantity}GB RAM' : 'mittlere, general purpose VM mit {vcpu.quantity} vCPUs und {vram.quantity}GB RAM',
                'salesprice' => 19.99,
                Sales_Model_Product::FLD_UNFOLD_TYPE => Sales_Model_Product::UNFOLD_TYPE_BUNDLE,
                Sales_Model_Product::FLD_SUBPRODUCTS => [
                    [
                        Sales_Model_SubProductMapping::FLD_PRODUCT_ID => $subProductIds[0],
                        Sales_Model_SubProductMapping::FLD_QUANTITY => 4,
                        Sales_Model_SubProductMapping::FLD_SHORTCUT => 'vcpu',
                        Sales_Model_SubProductMapping::FLD_VARIABLE_POSITION_FLAG => 'SHARED',

                    ], [
                        Sales_Model_SubProductMapping::FLD_PRODUCT_ID => $subProductIds[1],
                        Sales_Model_SubProductMapping::FLD_QUANTITY => 8,
                        Sales_Model_SubProductMapping::FLD_SHORTCUT => 'vram',
                        Sales_Model_SubProductMapping::FLD_VARIABLE_POSITION_FLAG => 'SHARED',
                    ]
                ]
            ]
        ];

        foreach($products as $product) {
            $product['name'] = [[
                Sales_Model_ProductLocalization::FLD_LANGUAGE => self::$_locale,
                Sales_Model_ProductLocalization::FLD_TEXT => $product['name'],
            ]];
            $product['description'] = [[
                Sales_Model_ProductLocalization::FLD_LANGUAGE => self::$_locale,
                Sales_Model_ProductLocalization::FLD_TEXT => $product['description'],
            ]];
            $this->_productController->create(new Sales_Model_Product(array_merge($default, $product)));
        }
    }

    /**
     * creates the customers with some addresses getting from the addressbook
     */
    protected function _createSharedCustomers()
    {
        $pagination = new Tinebase_Model_Pagination(array('limit' => 6, 'sort' => 'id', 'dir' => 'ASC'));
        // @todo: use shared addresses only
        $filter = new Addressbook_Model_ContactFilter(array(array('field' => 'type', 'operator' => 'equals', 'value' => Addressbook_Model_Contact::CONTACTTYPE_CONTACT)));
        $addresses = Addressbook_Controller_Contact::getInstance()->search($filter, $pagination);

        $customers = array(
            array(
                'name' => 'ELKO Elektronik und Söhne',
                'url' => 'https://www.elko-elektronik.de',
                'discount' => 0,
                'name_shorthand' => 'ELKO',
                'vat_procedure' => Sales_Config::VAT_PROCEDURE_STANDARD,
                'vatid' => 'DE456789123',
                'debitor' => [
                    Sales_Model_Debitor::FLD_NAME => 'ELKO Elektronik und Söhne',
                    Sales_Model_Debitor::FLD_EDOCUMENT_DISPATCH_TYPE => Sales_Model_EDocument_Dispatch_Email::class,
                    Sales_Model_Debitor::FLD_EDOCUMENT_DISPATCH_CONFIG => new Sales_Model_EDocument_Dispatch_Email([
                        Sales_Model_EDocument_Dispatch_Email::FLD_EMAIL => 'rechnung@elko-elektronik.de',
                        Sales_Model_EDocument_Dispatch_Abstract::FLD_DOCUMENT_TYPES => new Tinebase_Record_RecordSet(Sales_Model_EDocument_Dispatch_DocumentType::class, [[
                            Sales_Model_EDocument_Dispatch_DocumentType::FLD_DOCUMENT_TYPE => Sales_Config::ATTACHED_DOCUMENT_TYPES_EDOCUMENT,
                        ], [
                            Sales_Model_EDocument_Dispatch_DocumentType::FLD_DOCUMENT_TYPE => Sales_Config::ATTACHED_DOCUMENT_TYPES_PAPERSLIP,
                        ]]),
                    ]),
                ],

            ),
            array(
                'name' => 'Reifenlieferant Gebrüder Platt',
                'url' => 'https://www.platt-reifen.de',
                'discount' => 0,
                'name_shorthand' => 'PLATT',
                'vat_procedure' => Sales_Config::VAT_PROCEDURE_STANDARD,
                'vatid' => 'DE567891234',
                'debitor' => [
                    Sales_Model_Debitor::FLD_NAME => 'Reifenlieferant Gebrüder Platt',
                    Sales_Model_Debitor::FLD_EDOCUMENT_DISPATCH_TYPE => Sales_Model_EDocument_Dispatch_Upload::class,
                    Sales_Model_Debitor::FLD_EDOCUMENT_DISPATCH_CONFIG => new Sales_Model_EDocument_Dispatch_Upload([
                        Sales_Model_EDocument_Dispatch_Upload::FLD_URL => 'https://www.platt-reifen.de/lieferantenprotal/erechnungsupload/',
                        Sales_Model_EDocument_Dispatch_Abstract::FLD_DOCUMENT_TYPES => new Tinebase_Record_RecordSet(Sales_Model_EDocument_Dispatch_DocumentType::class, [[
                            Sales_Model_EDocument_Dispatch_DocumentType::FLD_DOCUMENT_TYPE => Sales_Config::ATTACHED_DOCUMENT_TYPES_EDOCUMENT,
                        ]]),
                    ]),
                ],
            ),
            array(
                'name' => 'Frische Fische Gmbh & Co. KG',
                'url' => 'https://www.frische-fische-hamburg.de',
                'discount' => 15.2,
                'name_shorthand' => 'FrischeFische',
                'vat_procedure' => Sales_Config::VAT_PROCEDURE_STANDARD,
                'vatid' => 'DE678912345',
                'debitor' => [
                    Sales_Model_Debitor::FLD_NAME => 'Frische Fische Gmbh & Co. KG',
                    Sales_Model_Debitor::FLD_EDOCUMENT_DISPATCH_TYPE => Sales_Model_EDocument_Dispatch_Manual::class,
                    Sales_Model_Debitor::FLD_EDOCUMENT_DISPATCH_CONFIG => new Sales_Model_EDocument_Dispatch_Manual([
                        Sales_Model_EDocument_Dispatch_Manual::FLD_INSTRUCTIONS => 'transfer via matrix to @rechnung:frische-fische-hamburg.de',
                    ]),
                ],
            ),
        );

        $i=0;

        $customerController = Sales_Controller_Customer::getInstance();
        $addressController = Sales_Controller_Address::getInstance();

        $customerRecords = new Tinebase_Record_RecordSet('Sales_Model_Customer');

        foreach ($customers as $customer) {
            $contactExtern = $addresses->getByIndex($i);
            if ($contactExtern) {
                $customer['cpextern_id'] = $contactExtern->getId();
            }
            $i++;
            $contactIntern = $addresses->getByIndex($i);
            if ($contactIntern) {
                $customer['cpintern_id'] = $contactIntern->getId();
            }
            $i++;
            $customer['iban'] = Tinebase_Record_Abstract::generateUID(20);
            $customer['bic'] = Tinebase_Record_Abstract::generateUID(12);
            $customer['credit_term'] = 30;
            $customer['currency'] = 'EUR';
            $customer['currency_trans_rate'] = 1;
            $customer[Sales_Model_Customer::FLD_DEBITORS] =
                new Tinebase_Record_RecordSet(Sales_Model_Debitor::class, [array_merge([
                    Sales_Model_Debitor::FLD_DIVISION_ID => $this->_division->getId(),
                    Sales_Model_Debitor::FLD_EAS_ID      => Sales_Controller_EDocument_EAS::getInstance()->getByCode('9930')->getId(),
                    Sales_Model_Debitor::FLD_ELECTRONIC_ADDRESS => $customer['vatid'],
                ], $customer['debitor'])], true);

            try {
                $customerRecords->addRecord($customerController->create(new Sales_Model_Customer($customer)));
            } catch (Tinebase_Exception_Duplicate $e) {
                echo 'Skipping creating customer ' . $customer['name'] . ' - exists already.' . PHP_EOL;
            }
        }

        $pagination = new Tinebase_Model_Pagination(array('limit' => 16, 'sort' => 'id', 'dir' => 'DESC'));
        $addresses = Addressbook_Controller_Contact::getInstance()->search($filter, $pagination);

        $i=0;
        foreach($customerRecords as $customer) {
            foreach(array('postal', 'billing', 'delivery', 'billing', 'delivery') as $type) {
                $caddress = $addresses->getByIndex($i);
                $address = new Sales_Model_Address(array(
                    'customer_id' => 'postal' === $type ? $customer->getId() : null,
                    Sales_Model_Address::FLD_DEBITOR_ID => 'postal' !== $type ? $customer->{Sales_Model_Customer::FLD_DEBITORS}->getFirstRecord()->getId() : null,
                    'type'        => $type,
                    'name'        => $customer['name'],
                    'prefix1'     => $caddress->org_name,
                    'prefix2'     => $caddress->org_unit,
                    'prefix3'     => $caddress->n_fn,
                    'street'      => $caddress->adr_two_street,
                    'postalcode'  => $caddress->adr_two_postalcode,
                    'locality'    => $caddress->adr_two_locality,
                    'region'      => $caddress->adr_two_region,
                    'countryname' => $caddress->adr_two_countryname,
                ));

                $addressController->create($address);

                $i++;
            }
            // the last customer gets plus one delivery address
            $caddress = $addresses->getByIndex($i);
            $address = new Sales_Model_Address(array(
                'customer_id' => 'postal' === $type ? $customer->getId() : null,
                Sales_Model_Address::FLD_DEBITOR_ID => 'postal' !== $type ? $customer->{Sales_Model_Customer::FLD_DEBITORS}->getFirstRecord()->getId() : null,
                'type'        => $type,
                'name'        => $customer['name'],
                'prefix1'     => $caddress->org_name,
                'prefix2'     => $caddress->org_unit,
                'prefix3'     => $caddress->n_fn,
                'street'      => $caddress->adr_two_street,
                'postalcode'  => $caddress->adr_two_postalcode,
                'locality'    => $caddress->adr_two_locality,
                'region'      => $caddress->adr_two_region,
                'countryname' => $caddress->adr_two_countryname,
            ));

            $addressController->create($address);
        }

        if (static::$_createFullData) {
            $i=0;
            while ($i < 200) {
                $customerController->create(new Sales_Model_Customer(array(
                    'name' => Tinebase_Record_Abstract::generateUID(),
                    Sales_Model_Customer::FLD_DEBITORS =>
                        new Tinebase_Record_RecordSet(Sales_Model_Debitor::class, [[
                            Sales_Model_Debitor::FLD_DIVISION_ID => $this->_division->getId(),
                            Sales_Model_Debitor::FLD_NAME        => 'Demo Debitor '. $i,
                        ]], true),
                )));
                $i++;
            }
        }
    }

    /**
     * creates the invoices - no containers, just "shared"
     */
    protected function _createSharedInvoices()
    {
        $sic = Sales_Controller_Invoice::getInstance();

        $now = new Tinebase_DateTime();
        $now->setTimezone(Tinebase_Core::getUserTimezone());
        $now->setDate($now->format('Y'), $now->format('m'), 1);
        $now->setTime(3,0,0);

        if (! $this->_referenceDate) {
            $this->_setReferenceDate();
        }

        $date = clone $this->_referenceDate;

        while ($date < $now) {
            $sic->createAutoInvoices($date);
            $date->addMonth(1);
        }
    }

    /**
     * creates the contracts - no containers, just "shared"
     */
    protected function _createSharedContracts()
    {
        if (!Tinebase_Application::getInstance()->isInstalled('Timetracker')) {
            return;
        }

        $cNumber = 1;

        $container = $this->_contractController->getSharedContractsContainer();
        $cid = $container->getId();
        $ccs = array($this->_developmentCostCenter, $this->_marketingCostCenter);

        $i = 0;

        $this->_setReferenceDate();

        $customers = Sales_Controller_Customer::getInstance()->getAll();
        $addresses = Sales_Controller_Address::getInstance()->getAll();

        $customersCount = $customers->count();
        $ccIndex = 0;


        $timeaccoountProduct = Sales_Controller_Product::getInstance()->create(new Sales_Model_Product([
            'name' => [[
                Sales_Model_ProductLocalization::FLD_LANGUAGE => self::$_locale,
                Sales_Model_ProductLocalization::FLD_TEXT => 'Timetracker Product',
            ]],
            'description' => [[
                Sales_Model_ProductLocalization::FLD_LANGUAGE => self::$_locale,
                Sales_Model_ProductLocalization::FLD_TEXT => 'this is a generic timetracker used in demo data',
            ]],
            'salesprice' => 100,
            'accountable' => 'TimetrackerTimeaccount'
        ]));

        while ($i < $customersCount) {
            $costcenter = $ccs[$i%2];
            $i++;

            $customer = $customers->getByIndex($ccIndex);

            $address = $addresses->filter('customer_id', $customer->getId())->filter('type', 'billing')->getFirstRecord();
            $addressId = $address ? $address->getId() : NULL;

            $title = self::$_de ? ('Vertrag für KST ' . $costcenter->number . ' - ' . $costcenter->name) : ('Contract for costcenter ' . $costcenter->number . ' - ' . $costcenter->name) . ' ' . Tinebase_Record_Abstract::generateUID(3);
            $ccid = $costcenter->getId();

            $contract = new Sales_Model_Contract(array(
                'number'       => $cNumber,
                'title'        => $title,
                'description'  => 'Created by Tine 2.0 DemoData',
                'container_id' => $cid,
                'status'       => 'OPEN',
                'cleared'      => 'NOT_YET_CLEARED',
                'start_date'   => clone $this->_referenceDate,
                'billing_address_id' => $addressId
            ));

            $timeaccount = Timetracker_Controller_Timeaccount::getInstance()->search(new Timetracker_Model_TimeaccountFilter(array(
                array('field' => 'title', 'operator' => 'equals', 'value' => 'Test Timeaccount ' . $i))));
            $timeaccount = $timeaccount->getFirstRecord();
            if (!$timeaccount) {
                $timeaccount = new Timetracker_Model_Timeaccount();
                $timeaccount->title = 'Test Timeaccount ' . $i;
                $timeaccount->number = $i;
                $timeaccount->is_billable = true;
                $timeaccount->status = 'to bill';
                $timeaccount->price = 120;
                $timeaccount = Timetracker_Controller_Timeaccount::getInstance()->create($timeaccount);

                $timeaccountRelation = array(
                    'own_model'              => Sales_Model_Contract::class,
                    'own_backend'            => Tinebase_Model_Relation::DEFAULT_RECORD_BACKEND,
                    'own_id'                 => NULL,
                    'related_degree'         => Tinebase_Model_Relation::DEGREE_SIBLING,
                    'related_model'          => Timetracker_Model_Timeaccount::class,
                    'related_backend'        => Tinebase_Model_Relation::DEFAULT_RECORD_BACKEND,
                    'related_id'             => $timeaccount->getId(),
                    'type'                   => 'TIME_ACCOUNT'
                );
            }


            for($ts = 0; $ts < 6; $ts++) {
                $timesheet = new Timetracker_Model_Timesheet();
                $timesheet->timeaccount_id = $timeaccount->getId();
                $timesheet->is_billable = true;
                $timesheet->description = $ts . ' - ' . $i . ' Test Task';
                $timesheet->account_id = Tinebase_Core::getUser()->getId();
                $timesheet->start_date = (clone $this->_referenceDate)->addDay($i);
                $timesheet->duration = 30;
                $timesheet->accounting_time = 30;
                Timetracker_Controller_Timesheet::getInstance()->create($timesheet);
            }

            $contract->eval_dim_cost_center = $costcenter->getId();
            $relations = array(
                array(
                    'own_model'              => 'Sales_Model_Contract',
                    'own_backend'            => Tinebase_Model_Relation::DEFAULT_RECORD_BACKEND,
                    'own_id'                 => NULL,
                    'related_degree'         => Tinebase_Model_Relation::DEGREE_SIBLING,
                    'related_model'          => 'Sales_Model_Customer',
                    'related_backend'        => Tinebase_Model_Relation::DEFAULT_RECORD_BACKEND,
                    'related_id'             => $customer->getId(),
                    'type'                   => 'CUSTOMER'
                ),

            );

            if ($timeaccountRelation) {
                $relations[] = $timeaccountRelation;
            }

            $genericProduct = Sales_Controller_Product::getInstance()->create(new Sales_Model_Product([
                'name' => [[
                    Sales_Model_ProductLocalization::FLD_LANGUAGE => 'en',
                    Sales_Model_ProductLocalization::FLD_TEXT => 'Generic Product',
                ], [
                    Sales_Model_ProductLocalization::FLD_LANGUAGE => 'de',
                    Sales_Model_ProductLocalization::FLD_TEXT => 'Generisches Produkt',
                ]],
                'description' => [[
                    Sales_Model_ProductLocalization::FLD_LANGUAGE => 'en',
                    Sales_Model_ProductLocalization::FLD_TEXT => 'ein generisches produkt aus den demo daten',
                ], [
                    Sales_Model_ProductLocalization::FLD_LANGUAGE => 'de',
                    Sales_Model_ProductLocalization::FLD_TEXT => 'ein generisches produkt aus den demo daten',
                ]], 'salesprice' => 100,
            ]));

            $contract->products = [
                [
                    'product_id' => $genericProduct->getId(),
                    'quantity' => 1
                ],
                [
                    'product_id' => $timeaccoountProduct->getId(),
                    'quantity' => 1
                ]
            ];

            $contract->relations = $relations;

            $this->_contractController->create($contract);
            $cNumber++;
            $ccIndex++;
            if ($ccIndex == $customersCount) {
                $ccIndex = 0;
            }
        }
    }

    /**
     * creates some order confirmations
     */
    protected function _createSharedOrderconfirmations()
    {
        $i = 1;

        $this->_setReferenceDate();

        $contracts = Sales_Controller_Contract::getInstance()->getAll('number');

        // create for each contract a order confirmation
        foreach($contracts as $contract) {
            $relations = array(array(
                'own_model'              => 'Sales_Model_OrderConfirmation',
                'own_backend'            => Tinebase_Model_Relation::DEFAULT_RECORD_BACKEND,
                'own_id'                 => NULL,
                'related_degree'         => Tinebase_Model_Relation::DEGREE_SIBLING,
                'related_model'          => 'Sales_Model_Contract',
                'related_backend'        => Tinebase_Model_Relation::DEFAULT_RECORD_BACKEND,
                'related_id'             => $contract->getId(),
                'type'                   => 'CONTRACT'
            ));

            $oc = Sales_Controller_OrderConfirmation::getInstance()->create(new Sales_Model_OrderConfirmation(array(
                'number' => $i,
                'title'  => self::$_de ? ('Auftragsbestätigung für Vertrag ' . $contract->title) : ('Order Confirmation for Contract' . $contract->title),
                'description' => 'Created by Tine 2.0 DemoData',
                'relations' => $relations
            )));

            $i++;
        }
    }

    /**
     * creates some offers
     */
    protected function _createSharedOffers()
    {
        $i = 0;

        $this->_setReferenceDate();

        $customers          = Sales_Controller_Customer::getInstance()->getAll('number');
        $orderconfirmations = Sales_Controller_OrderConfirmation::getInstance()->getAll('number');

        foreach ($customers as $customer) {
            $oc = $orderconfirmations->getByIndex($i);
            if (!$oc) {
                // order confirmation not found
                continue;
            }
            $i++;
            $relations = array(array(
                'own_model'              => 'Sales_Model_Offer',
                'own_backend'            => Tinebase_Model_Relation::DEFAULT_RECORD_BACKEND,
                'own_id'                 => NULL,
                'related_degree'         => Tinebase_Model_Relation::DEGREE_SIBLING,
                'related_model'          => 'Sales_Model_Customer',
                'related_backend'        => Tinebase_Model_Relation::DEFAULT_RECORD_BACKEND,
                'related_id'             => $customer->getId(),
                'type'                   => 'OFFER'
            ), array(
                'own_model'              => 'Sales_Model_Offer',
                'own_backend'            => Tinebase_Model_Relation::DEFAULT_RECORD_BACKEND,
                'own_id'                 => NULL,
                'related_degree'         => Tinebase_Model_Relation::DEGREE_SIBLING,
                'related_model'          => 'Sales_Model_OrderConfirmation',
                'related_backend'        => Tinebase_Model_Relation::DEFAULT_RECORD_BACKEND,
                'related_id'             => $oc->getId(),
                'type'                   => 'OFFER'
            ));
            Sales_Controller_Offer::getInstance()->create(new Sales_Model_Offer(array(
                'number' => $i,
                'title'  => self::$_de ? ('Angebot für Kunde ' . $customer->name) : ('Offer for Customer' . $customer->name),
                'description' => 'Created by Tine 2.0 DemoData',
                'relations' => $relations
            )));
        }
    }

    /**
     * returns a new product
     * return Sales_Model_Product
     */
    protected function _createProduct($data)
    {

    }

    /**
     * create some costcenters
     *
     * @see Tinebase_Setup_DemoData_Abstract
     */
    protected function _onCreate()
    {
        $cc = Tinebase_Controller_EvaluationDimension::getInstance()->search(Tinebase_Model_Filter_FilterGroup::getFilterForModel(Tinebase_Model_EvaluationDimension::class, [
            ['field' => Tinebase_Model_EvaluationDimension::FLD_NAME, 'operator' => 'equals', 'value' => Tinebase_Model_EvaluationDimension::COST_CENTER],
        ]), null, new Tinebase_Record_Expander(Tinebase_Model_EvaluationDimension::class, Tinebase_Model_EvaluationDimension::getConfiguration()->jsonExpander))->getFirstRecord();

        $ccs = (static::$_de)
        ? array('Management', 'Marketing', 'Entwicklung', 'Produktion', 'Verwaltung',     'Controlling')
        : array('Management', 'Marketing', 'Development', 'Production', 'Administration', 'Controlling')
        ;

        $id = 1;
        foreach($ccs as $title) {
            if (Tinebase_Controller_EvaluationDimensionItem::getInstance()->search(Tinebase_Model_Filter_FilterGroup::getFilterForModel(Tinebase_Model_EvaluationDimensionItem::class,
                    [['field' => 'name', 'operator' => 'equals', 'value' => $title],
                        ['field' => Tinebase_Model_EvaluationDimensionItem::FLD_EVALUATION_DIMENSION_ID, 'operator' => 'equals', 'value' => $cc->getId()]]))->count() > 0) {
                continue;
            }

            $cc->{Tinebase_Model_EvaluationDimension::FLD_ITEMS}->addRecord(new Tinebase_Model_EvaluationDimensionItem(
                array('name' => $title, 'number' => $id)
                , true));

            $id++;
        }
        $cc = Tinebase_Controller_EvaluationDimension::getInstance()->update($cc);
        $this->_costCenters = clone $cc->{Tinebase_Model_EvaluationDimension::FLD_ITEMS};

        $divisionsArray = (static::$_de)
        ? array('Management', 'EDV', 'Marketing', 'Public Relations', 'Produktion', 'Verwaltung')
        : array('Management', 'IT', 'Marketing', 'Public Relations', 'Production', 'Administration')
        ;

        if (Tinebase_Application::getInstance()->isInstalled('HumanResources')) {
            foreach ($divisionsArray as $divisionName) {
                try {
                    HumanResources_Controller_Division::getInstance()->create(new HumanResources_Model_Division(
                        ['title' => $divisionName]
                    ));
                } catch (Zend_Db_Statement_Exception $e) {
                } catch (Tinebase_Exception_Duplicate $e) {
                } catch (Tinebase_Exception_SystemGeneric $e) {
                }
            }
        }

        $this->_loadCostCentersAndDivisions();

        $defaultDivision = Sales_Controller_Division::getInstance()->search()->getFirstRecord();
        $defaultDivision->{Sales_Model_Division::FLD_NAME} = 'Tine Publications, Ltd';
        $defaultDivision->{Sales_Model_Division::FLD_ADDR_PREFIX1} = 'Montgomery Street 589';
        $defaultDivision->{Sales_Model_Division::FLD_ADDR_POSTAL} = 'BN1';
        $defaultDivision->{Sales_Model_Division::FLD_ADDR_LOCALITY} = 'East Sussex';
        $defaultDivision->{Sales_Model_Division::FLD_ADDR_REGION} = 'Brighton';
        $defaultDivision->{Sales_Model_Division::FLD_ADDR_COUNTRY} = 'GB';
        $defaultDivision->{Sales_Model_Division::FLD_CONTACT_NAME} = 'Mr. Sales';
        $defaultDivision->{Sales_Model_Division::FLD_CONTACT_EMAIL} = 'sales@mail.test';
        $defaultDivision->{Sales_Model_Division::FLD_CONTACT_PHONE} = '+441273-3766-373';
        $defaultDivision->{Sales_Model_Division::FLD_TAX_REGISTRATION_ID} = '12345 67890';
        $defaultDivision->{Sales_Model_Division::FLD_VAT_NUMBER} = 'GB123456789';
        $defaultDivision->{Sales_Model_Division::FLD_EAS_ID} = Sales_Controller_EDocument_EAS::getInstance()->getByCode('9932')->getId();
        $defaultDivision->{Sales_Model_Division::FLD_ELECTRONIC_ADDRESS} = 'GB123456789';
        $defaultDivision->{Sales_Model_Division::FLD_SEPA_CREDITOR_ID} = 'GB98ZZZ09999999999';
        Sales_Controller_Division::getInstance()->update($defaultDivision);
    }
}
