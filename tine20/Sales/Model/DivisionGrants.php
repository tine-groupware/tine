<?php declare(strict_types=1);
/**
 * class to handle grants
 * 
 * @package     Sales
 * @subpackage  Record
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2023-2024 Metaways Infosystems GmbH (http://www.metaways.de)
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

    public const GRANT_ADMIN_DEBITOR = self::GRANT_ADMIN . '_' . Sales_Model_Debitor::MODEL_NAME_PART;

    public const GRANT_ADMIN_DOCUMENT_DELIVERY = self::GRANT_ADMIN . '_' . Sales_Model_Document_Delivery::MODEL_NAME_PART;
    public const GRANT_ADMIN_DOCUMENT_INVOICE = self::GRANT_ADMIN . '_' . Sales_Model_Document_Invoice::MODEL_NAME_PART;
    public const GRANT_ADMIN_DOCUMENT_OFFER = self::GRANT_ADMIN . '_' . Sales_Model_Document_Offer::MODEL_NAME_PART;
    public const GRANT_ADMIN_DOCUMENT_ORDER = self::GRANT_ADMIN . '_' . Sales_Model_Document_Order::MODEL_NAME_PART;

    public const GRANT_READ_DEBITOR = self::GRANT_READ . '_' . Sales_Model_Debitor::MODEL_NAME_PART;

    public const GRANT_READ_DOCUMENT_DELIVERY = self::GRANT_READ . '_' . Sales_Model_Document_Delivery::MODEL_NAME_PART;
    public const GRANT_READ_DOCUMENT_INVOICE = self::GRANT_READ . '_' . Sales_Model_Document_Invoice::MODEL_NAME_PART;
    public const GRANT_READ_DOCUMENT_OFFER = self::GRANT_READ . '_' . Sales_Model_Document_Offer::MODEL_NAME_PART;
    public const GRANT_READ_DOCUMENT_ORDER = self::GRANT_READ . '_' . Sales_Model_Document_Order::MODEL_NAME_PART;


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
            self::GRANT_ADMIN,
            self::GRANT_ADMIN_DEBITOR,
            self::GRANT_ADMIN_DOCUMENT_DELIVERY,
            self::GRANT_ADMIN_DOCUMENT_INVOICE,
            self::GRANT_ADMIN_DOCUMENT_OFFER,
            self::GRANT_ADMIN_DOCUMENT_ORDER,
            self::GRANT_READ_DEBITOR,
            self::GRANT_READ_DOCUMENT_DELIVERY,
            self::GRANT_READ_DOCUMENT_INVOICE,
            self::GRANT_READ_DOCUMENT_OFFER,
            self::GRANT_READ_DOCUMENT_ORDER,
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
            self::GRANT_ADMIN => [
                self::LABEL         => 'Admin', // _('Admin')
                self::DESCRIPTION   => 'The grant to administrate this division (implies all other grants and the grant to set grants as well).', // _('The grant to administrate this division (implies all other grants and the grant to set grants as well).')
            ],
            self::GRANT_ADMIN_DEBITOR => [
                self::LABEL         => 'Admin Debitors', // _('Admin Debitors')
                self::DESCRIPTION   => 'The grant to administrate the division\'s debitors.', // _('The grant to administrate the division\'s debitors.')
            ],
            self::GRANT_ADMIN_DOCUMENT_DELIVERY => [
                self::LABEL         => 'Admin Delivery Documents', // _('Admin Delivery Documents')
                self::DESCRIPTION   => 'The grant to administrate the division\'s delivery documents.', // _('The grant to administrate the division\'s delivery documents.')
            ],
            self::GRANT_ADMIN_DOCUMENT_INVOICE => [
                self::LABEL         => 'Admin Invoice Documents', // _('Admin Invoice Documents')
                self::DESCRIPTION   => 'The grant to administrate the division\'s invoice documents.', // _('The grant to administrate the division\'s delivery documents.')
            ],
            self::GRANT_ADMIN_DOCUMENT_OFFER => [
                self::LABEL         => 'Admin Offer Documents', // _('Admin Offer Documents')
                self::DESCRIPTION   => 'The grant to administrate the division\'s offer documents.', // _('The grant to administrate the division\'s delivery documents.')
            ],
            self::GRANT_ADMIN_DOCUMENT_ORDER => [
                self::LABEL         => 'Admin Order Documents', // _('Admin Order Documents')
                self::DESCRIPTION   => 'The grant to administrate the division\'s order documents.', // _('The grant to administrate the division\'s delivery documents.')
            ],
            self::GRANT_READ_DEBITOR => [
                self::LABEL         => 'Read Debitors', // _('Read Debitors')
                self::DESCRIPTION   => 'The grant to read the division\'s debitors.', // _('The grant to read the division\'s debitors.')
            ],
            self::GRANT_READ_DOCUMENT_DELIVERY => [
                self::LABEL         => 'Read Delivery Documents', // _('Read Delivery Documents')
                self::DESCRIPTION   => 'The grant to read the division\'s delivery documents.', // _('The grant to read the division\'s delivery documents.')
            ],
            self::GRANT_READ_DOCUMENT_INVOICE => [
                self::LABEL         => 'Read Invoice Documents', // _('Read Invoice Documents')
                self::DESCRIPTION   => 'The grant to read the division\'s invoice documents.', // _('The grant to read the division\'s delivery documents.')
            ],
            self::GRANT_READ_DOCUMENT_OFFER => [
                self::LABEL         => 'Read Offer Documents', // _('Read Offer Documents')
                self::DESCRIPTION   => 'The grant to read the division\'s offer documents.', // _('The grant to read the division\'s delivery documents.')
            ],
            self::GRANT_READ_DOCUMENT_ORDER => [
                self::LABEL         => 'Read Order Documents', // _('Read Order Documents')
                self::DESCRIPTION   => 'The grant to read the division\'s order documents.', // _('The grant to read the division\'s delivery documents.')
            ],
        ];
    }
}
