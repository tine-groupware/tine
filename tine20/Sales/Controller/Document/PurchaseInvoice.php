<?php declare(strict_types=1);

/**
 * PurchaseInvoice Document controller for Sales application
 *
 * @package     Sales
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2025 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * PurchaseInvoice Document controller class for Sales application
 *
 * @package     Sales
 * @subpackage  Controller
 *
 * @method Sales_Model_Document_PurchaseInvoice create(Sales_Model_Document_PurchaseInvoice $_record)
 */
class Sales_Controller_Document_PurchaseInvoice extends Sales_Controller_Document_Abstract
{
    use Tinebase_Controller_SingletonTrait;

    protected static $_adminGrant = Sales_Model_DivisionGrants::GRANT_ADMIN_DOCUMENT_PURCHASE_INVOICE;
    protected static $_readGrant = Sales_Model_DivisionGrants::GRANT_READ_DOCUMENT_PURCHASE_INVOICE;

    /**
     * the constructor
     *
     * don't use the constructor. use the singleton
     */
    protected function __construct()
    {
        $this->_applicationName = Sales_Config::APP_NAME;
        $this->_backend = new Tinebase_Backend_Sql([
            Tinebase_Backend_Sql_Abstract::MODEL_NAME => Sales_Model_Document_PurchaseInvoice::class,
            Tinebase_Backend_Sql_Abstract::TABLE_NAME => Sales_Model_Document_PurchaseInvoice::TABLE_NAME,
            Tinebase_Backend_Sql_Abstract::MODLOG_ACTIVE => true,
        ]);
        $this->_modelName = Sales_Model_Document_PurchaseInvoice::class;
        $this->_purgeRecords = false;
        $this->_doContainerACLChecks = false;

        $this->_documentStatusConfig = Sales_Config::DOCUMENT_PURCHASE_INVOICE_STATUS;
        $this->_documentStatusTransitionConfig = Sales_Config::DOCUMENT_PURCHASE_INVOICE_STATUS_TRANSITIONS;
        $this->_documentStatusField = Sales_Model_Document_PurchaseInvoice::FLD_PURCHASE_INVOICE_STATUS;
        $this->_oldRecordBookWriteableFields = [ // TODO FIXME!!!
            'tags', 'attachments', 'relations',
        ];
        $this->_bookRecordRequiredFields = [ // TODO FIXME !!!
            Sales_Model_Document_PurchaseInvoice::FLD_PURCHASE_INVOICE_STATUS,
        ];
        parent::__construct();
    }

    /**
     * inspect creation of one record (before create)
     *
     * @param   Sales_Model_Document_Abstract $_record
     * @return  void
     */
    protected function _inspectBeforeCreate(Tinebase_Record_Interface $_record)
    {
        if (empty($_record->{Sales_Model_Document_PurchaseInvoice::FLD_DIVISION_ID})) {
            $_record->{Sales_Model_Document_PurchaseInvoice::FLD_DIVISION_ID} = Sales_Config::getInstance()->{Sales_Config::DEFAULT_DIVISION};
        }
        parent::_inspectBeforeCreate($_record);

        // important! after _inspectDenormalization in parent::_inspectBeforeUpdate
        // the recipient address is not part of a customer, debitor_id needs to refer to the local denormalized instance
        //$this->_inspectAddressField($_record, Sales_Model_Document_Order::FLD_DELIVERY_RECIPIENT_ID);
    }

    /**
     * @param Sales_Model_Document_Abstract $_record
     * @param Sales_Model_Document_Abstract $_oldRecord
     */
    protected function _inspectBeforeUpdate($_record, $_oldRecord)
    {
        parent::_inspectBeforeUpdate($_record, $_oldRecord);

        // important! after _inspectDenormalization in parent::_inspectBeforeUpdate
        // the recipient address is not part of a customer, debitor_id needs to refer to the local denormalized instance
        //$this->_inspectAddressField($_record, Sales_Model_Document_Order::FLD_DELIVERY_RECIPIENT_ID);
    }

    protected function _inspectCategoryDebitor(Sales_Model_Document_Abstract $_record)
    {
        // we dont do that
    }

    protected function _inspectAutoCreateValues(Sales_Model_Document_Abstract $document): void
    {
        // we dont do that
    }

    protected function _inspectDefaultBoilerplates(Sales_Model_Document_Abstract $document): void
    {
        // we dont do that
    }

    protected function _inspectVAT(Sales_Model_Document_Abstract $_record): void
    {
        // we dont do that
    }

    protected function _inspectAddressField($_record, $field)
    {
        // we dont do that
    }

    public function documentNumberConfigOverride(Sales_Model_Document_Abstract $document, string $property = Sales_Model_Document_Abstract::FLD_DOCUMENT_NUMBER): array
    {
        $result = [];
        if (!$document->isBooked()) {
            $result['skip'] = true;
        }
        return $result;
    }

    protected const TYPE_UBL = 1;
    protected const TYPE_CII = 2;
    protected function _getEDocumentXml(Tinebase_Model_FileLocation $fileLocation, ?string &$content, ?int &$type): ?string
    {
        if (!$fileLocation->isFile()) {
            throw new Tinebase_Exception('fileLocation is not a file');
        }
        if (!$fileLocation->canReadData()) {
            throw new Tinebase_Exception('fileLocation\'s data can not be read');
        }
        $name = $fileLocation->getName();
        $locationExt = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        $xmlContent = $content = $fileLocation->getContent();
        if ('xml' !== $locationExt) {
            try {
                $zugPferd = Sales_EDocument_ZUGFeRD::createFromString($xmlContent);
                $xmlContent = $zugPferd->getXml();
            } catch (Tinebase_Exception_UnexpectedValue $e) {}
        }

        $previousLibXmlError = libxml_use_internal_errors(true);
        try {
            libxml_clear_errors();
            if (! simplexml_load_string($xmlContent, namespace_or_prefix: 'rsm') instanceof \SimpleXMLElement) {
                return null;
            }
        } finally {
            libxml_use_internal_errors($previousLibXmlError);
            libxml_clear_errors();
        }

        if (false !== strpos($xmlContent, 'urn:oasis:names:specification:ubl:')) {
            $type = self::TYPE_UBL;
        } elseif (false !== strpos($xmlContent, 'urn:un:unece:uncefact:data:standard:CrossIndustry')) {
            $type = self::TYPE_CII;
        } else {
            return null;
        }

        return $xmlContent;
    }

    public function isEDocumentFile(Tinebase_Model_FileLocation $fileLocation): bool
    {
        return null !== $this->_getEDocumentXml($fileLocation, $content, $type);
    }

    public function importPurchaseInvoice(Tinebase_Model_FileLocation $fileLocation, bool $importNonEDocument = false): Sales_Model_Document_PurchaseInvoice
    {
        $xmlContent = $this->_getEDocumentXml($fileLocation, $content, $type);
        if (null !== $xmlContent) {
            $validationResult = (new Sales_EDocument_Service_Validate)->validateXRechnungContent($xmlContent);

            if (self::TYPE_UBL === $type) {
                $xmlContent = (new Sales_EDocument_Service_ConvertToXr())->convertUbl($xmlContent);
            } else {
                $xmlContent = (new Sales_EDocument_Service_ConvertToXr())->convertCii($xmlContent);
            }

            $purchaseInvoice = Sales_Model_Document_PurchaseInvoice::fromXR($xmlContent);
            $view = (new Sales_EDocument_Service_View)->viewXr($xmlContent);

            $validationResultFh = fopen('php://memory', 'w+');
            fwrite($validationResultFh, $validationResult['html']);
            $viewFh = fopen('php://memory', 'w+');
            fwrite($viewFh, $view);

            if (!$purchaseInvoice->attachments instanceof Tinebase_Record_RecordSet) {
                $purchaseInvoice->attachments = new Tinebase_Record_RecordSet(Tinebase_Model_Tree_Node::class);
            }
            $purchaseInvoice->attachments->addRecord(new Tinebase_Model_Tree_Node([
                'name' => 'validationReport.html',
                'tempFile' => $validationResultFh,
            ], true));
            $purchaseInvoice->attachments->addRecord(new Tinebase_Model_Tree_Node([
                'name' => 'xrechnung.html',
                'tempFile' => $viewFh,
            ], true));
        } elseif ($importNonEDocument) {
            $purchaseInvoice = new Sales_Model_Document_PurchaseInvoice([], true);
        } else {
            $t = Tinebase_Translation::getTranslation(Sales_Config::APP_NAME);
            throw new Tinebase_Exception_SystemGeneric($t->_('File is not a valid EDocument, do you want to create a purchase invoice anyway?'));
        }
        $contentFh = fopen('php://memory', 'w+');
        fwrite($contentFh, $content);

        if (!$purchaseInvoice->attachments instanceof Tinebase_Record_RecordSet) {
            $purchaseInvoice->attachments = new Tinebase_Record_RecordSet(Tinebase_Model_Tree_Node::class);
        }
        $purchaseInvoice->attachments->addRecord(new Tinebase_Model_Tree_Node([
            'name' => $fileLocation->getName(),
            'tempFile' => $contentFh,
        ], true));

        return $this->create($purchaseInvoice);
    }
}
