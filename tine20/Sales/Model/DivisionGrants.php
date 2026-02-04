<?php declare(strict_types=1);
/**
 * class to handle grants
 * 
 * @package     Sales
 * @subpackage  Record
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2023-2025 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */

/**
 * defines Division grants
 * 
 * @package     Sales
 * @subpackage  Record
 *  */
class Sales_Model_DivisionGrants extends Tinebase_Model_Grants
{
    public const MODEL_NAME_PART    = 'DivisionGrants';

    public const GRANT_ADMIN_DEBITOR = Sales_Model_Debitor::MODEL_NAME_PART . '_' . self::GRANT_ADMIN;

    public const GRANT_ADMIN_DOCUMENT_DELIVERY = Sales_Model_Document_Delivery::MODEL_NAME_PART . '_' . self::GRANT_ADMIN;
    public const GRANT_ADMIN_DOCUMENT_INVOICE = Sales_Model_Document_Invoice::MODEL_NAME_PART . '_' . self::GRANT_ADMIN;
    public const GRANT_ADMIN_DOCUMENT_OFFER = Sales_Model_Document_Offer::MODEL_NAME_PART . '_' . self::GRANT_ADMIN;
    public const GRANT_ADMIN_DOCUMENT_ORDER = Sales_Model_Document_Order::MODEL_NAME_PART . '_' . self::GRANT_ADMIN;
    public const GRANT_ADMIN_DOCUMENT_PURCHASE_INVOICE = Sales_Model_Document_PurchaseInvoice::MODEL_NAME_PART . '_' . self::GRANT_ADMIN;

    public const GRANT_READ_DEBITOR = Sales_Model_Debitor::MODEL_NAME_PART . '_' . self::GRANT_READ;

    public const GRANT_READ_DOCUMENT_DELIVERY = Sales_Model_Document_Delivery::MODEL_NAME_PART . '_' . self::GRANT_READ;
    public const GRANT_READ_DOCUMENT_INVOICE = Sales_Model_Document_Invoice::MODEL_NAME_PART . '_' . self::GRANT_READ;
    public const GRANT_READ_DOCUMENT_OFFER = Sales_Model_Document_Offer::MODEL_NAME_PART . '_' . self::GRANT_READ;
    public const GRANT_READ_DOCUMENT_ORDER = Sales_Model_Document_Order::MODEL_NAME_PART . '_' . self::GRANT_READ;
    public const GRANT_READ_DOCUMENT_PURCHASE_INVOICE = Sales_Model_Document_PurchaseInvoice::MODEL_NAME_PART . '_' . self::GRANT_READ;
    public const GRANT_EDIT_DOCUMENT_PURCHASE_INVOICE = Sales_Model_Document_PurchaseInvoice::MODEL_NAME_PART . '_' . self::GRANT_EDIT;
    public const GRANT_APPROVE_DOCUMENT_PURCHASE_INVOICE = Sales_Model_Document_PurchaseInvoice::MODEL_NAME_PART . '_approveGrant';


    /**
     * application the record belongs to
     *
     * @var string
     */
    protected $_application = Sales_Config::APP_NAME;
    
    /**
     * get all possible grants
     *
     * @return  array   all container grants
     */
    public static function getAllGrants()
    {
        return [
            self::GRANT_READ_DEBITOR,
            self::GRANT_ADMIN_DEBITOR,
            self::GRANT_READ_DOCUMENT_OFFER,
            self::GRANT_ADMIN_DOCUMENT_OFFER,
            self::GRANT_READ_DOCUMENT_ORDER,
            self::GRANT_ADMIN_DOCUMENT_ORDER,
            self::GRANT_READ_DOCUMENT_DELIVERY,
            self::GRANT_ADMIN_DOCUMENT_DELIVERY,
            self::GRANT_READ_DOCUMENT_INVOICE,
            self::GRANT_ADMIN_DOCUMENT_INVOICE,
            self::GRANT_READ_DOCUMENT_PURCHASE_INVOICE,
            self::GRANT_EDIT_DOCUMENT_PURCHASE_INVOICE,
            self::GRANT_APPROVE_DOCUMENT_PURCHASE_INVOICE,
            self::GRANT_ADMIN,
        ];
    }

    protected static $_modelConfiguration = null;

    /**
     * holds the configuration object (must be declared in the concrete class)
     *
     * @var Tinebase_ModelConfiguration
     */
    protected static $_configurationObject = NULL;

    public static function getAllGrantsMC(): array
    {
        return [
            self::GRANT_READ_DEBITOR => [
                self::LABEL         => 'Read Debitors', // _('Read Debitors')
                self::DESCRIPTION   => 'The permission to read the division\'s debitors.', // _('The permission to read the division\'s debitors.')
            ],
            self::GRANT_ADMIN_DEBITOR => [
                self::LABEL         => 'Admin Debitors', // _('Admin Debitors')
                self::DESCRIPTION   => 'The permission to administrate the division\'s debitors.', // _('The permission to administrate the division\'s debitors.')
            ],
            self::GRANT_READ_DOCUMENT_OFFER => [
                self::LABEL         => 'Read Offers', // _('Read Offers')
                self::DESCRIPTION   => 'The permission to read the division\'s offer documents.', // _('The permission to read the division\'s offer documents.')
            ],
            self::GRANT_ADMIN_DOCUMENT_OFFER => [
                self::LABEL         => 'Admin Offers', // _('Admin Offers')
                self::DESCRIPTION   => 'The permission to administrate the division\'s offer documents.', // _('The permission to administrate the division\'s offer documents.')
            ],
            self::GRANT_READ_DOCUMENT_ORDER => [
                self::LABEL         => 'Read Orders', // _('Read Orders')
                self::DESCRIPTION   => 'The permission to read the division\'s order documents.', // _('The permission to read the division\'s order documents.')
            ],
            self::GRANT_ADMIN_DOCUMENT_ORDER => [
                self::LABEL         => 'Admin Orders', // _('Admin Orders')
                self::DESCRIPTION   => 'The permission to administrate the division\'s order documents.', // _('The permission to administrate the division\'s order documents.')
            ],
            self::GRANT_READ_DOCUMENT_PURCHASE_INVOICE => [
                self::LABEL         => 'Read Purchase Invoices', // _('Read Purchase Invoices')
                self::DESCRIPTION   => 'The grant to read the division\'s purchase invoice documents.', // _('The grant to read the division\'s purchase invoice documents.')
            ],
            self::GRANT_EDIT_DOCUMENT_PURCHASE_INVOICE => [
                self::LABEL         => 'Edit Purchase Invoices', // _('Edit Purchase Invoices')
                self::DESCRIPTION   => 'The grant to create/edit the division\'s purchase invoice documents.', // _('The grant to create/edit the division\'s purchase invoice documents.')
            ],
            self::GRANT_APPROVE_DOCUMENT_PURCHASE_INVOICE => [
                self::LABEL         => 'Approve Purchase Invoices', // _('Approve Purchase Invoices')
                self::DESCRIPTION   => 'The grant to approve the division\'s purchase invoice documents.', // _('The grant to approve the division\'s purchase invoice documents.')
            ],
            self::GRANT_READ_DOCUMENT_DELIVERY => [
                self::LABEL         => 'Read Deliveries', // _('Read Deliveries')
                self::DESCRIPTION   => 'The permission to read the division\'s delivery documents.', // _('The permission to read the division\'s delivery documents.')
            ],
            self::GRANT_ADMIN_DOCUMENT_DELIVERY => [
                self::LABEL         => 'Admin Deliveries', // _('Admin Deliveries')
                self::DESCRIPTION   => 'The permission to administrate the division\'s delivery documents.', // _('The permission to administrate the division\'s delivery documents.')
            ],
            self::GRANT_READ_DOCUMENT_INVOICE => [
                self::LABEL         => 'Read Invoices', // _('Read Invoices')
                self::DESCRIPTION   => 'The permission to read the division\'s invoice documents.', // _('The permission to read the division\'s invoice documents.')
            ],
            self::GRANT_ADMIN_DOCUMENT_INVOICE => [
                self::LABEL         => 'Admin Invoices', // _('Admin Invoices')
                self::DESCRIPTION   => 'The permission to administrate the division\'s invoice documents.', // _('The permission to administrate the division\'s invoice documents.')
            ],
            self::GRANT_ADMIN => [
                self::LABEL         => 'Admin', // _('Admin')
                self::DESCRIPTION   => 'The permission to administrate this division (implies all other grants and the grant to set grants as well).', // _('The permission to administrate this division (implies all other grants and the grant to set grants as well).')
            ],
        ];
    }
}
