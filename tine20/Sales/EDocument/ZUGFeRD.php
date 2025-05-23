<?php declare(strict_types=1);
/**
 * ZUGFeRD file handling class
 *
 * @package     Sales
 * @subpackage  EDocument
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2025 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

class Sales_EDocument_ZUGFeRD
{
    protected ?string $xml = null;

    /**
     * @throws Tinebase_Exception_UnexpectedValue
     */
    protected function __construct(
        protected \Smalot\PdfParser\Document $pdf
    ) {
        $warnMsg = '';
        $prefix = null;
        foreach ($pdf->getDetails()['pdfextension:schemas'] ?? [] as $schema) {
            if (('urn:factur-x:pdfa:CrossIndustryDocument:invoice:1p0#' === ($schema['pdfaschema:namespaceuri'] ?? null)
                    || 'Factur-X PDFA Extension Schema' === ($schema['pdfaschema:schema'] ?? null)) && ($schema['pdfaschema:prefix'] ?? false)) {
                $prefix = $schema['pdfaschema:prefix'];
                break;
            }
        }

        if (null === $prefix) {
            if ($pdf->getDetails()['fx:documentfilename'] ?? false) {
                $warnMsg .= 'no pdf extension schema / prefix found:' . PHP_EOL;
                $warnMsg .= print_r($pdf->getDetails()['pdfextension:schemas'] ?? [], true) . PHP_EOL;
                $prefix = 'fx';
            } else {
                throw new Tinebase_Exception_UnexpectedValue('no Factur-X PDFA Extension Schema found, not a ZUGFeRD pdf');
            }
        }

        if ('INVOICE' !== ($pdf->getDetails()[$prefix . ':documenttype'] ?? null)) {
            $warnMsg .= 'document type not "INVOICE": ' . ($pdf->getDetails()[$prefix . ':documenttype'] ?? '') . PHP_EOL;
        }
        if (1 === version_compare('3.0', $pdf->getDetails()[$prefix . ':version'] ?? '2.9')) {
            $warnMsg .= 'version not at least 3.0: ' . ($pdf->getDetails()[$prefix . ':version'] ?? '') . PHP_EOL;
        }
        if ('XRECHNUNG' !== ($pdf->getDetails()[$prefix . ':conformancelevel'] ?? null)) {
            $warnMsg .= 'conformance level not "XRECHNUNG": ' . ($pdf->getDetails()[$prefix . ':conformancelevel'] ?? '') . PHP_EOL;
        }

        if ($filename = $pdf->getDetails()[$prefix . ':documentfilename'] ?? false) {
            /** @var Smalot\PdfParser\PDFObject $object */
            foreach ($pdf->getObjectsByType('Filespec') as $object) {
                foreach (['F', 'UF'] as $elementName) {
                    if ($object->getHeader()?->get($elementName)->getContent() === $filename) {
                        if (!($efHeader = $object->getHeader()->get('EF')) instanceof \Smalot\PdfParser\Header) {
                            $warnMsg .= 'Filespec is missing EF header' . PHP_EOL;
                            continue 2;
                        }
                        if (!($xRef = $efHeader->get($elementName)) instanceof \Smalot\PdfParser\PDFObject) {
                            $warnMsg .= 'Filespecs EF header is excpected to have a ' . $elementName . ' xref header' . PHP_EOL;
                            continue;
                        }
                        $this->xml = $xRef->getContent();
                        if (str_starts_with($this->xml, '<?xml ')) {
                            break 2;
                        } else {
                            $warnMsg .= 'Filespecs EF header is excpected to have a ' . $elementName . ' xref header containing valid xml: ' . $this->xml . PHP_EOL;
                            $this->xml = null;
                        }
                    }
                }
            }
            if (null === $this->xml) {
                $warnMsg .= 'no matching Filespec found' . PHP_EOL;
            }
        } else {
            $warnMsg .= $prefix . ':documentfilename not found' . PHP_EOL;
        }

        if ('' !== $warnMsg) {
            $e = new Tinebase_Exception($warnMsg);
            $e->setLogToSentry(true);
            $e->setLogLevelMethod('warn');
            Tinebase_Exception::log($e);
        }

        if (null === $this->xml) {
            throw new Tinebase_Exception_UnexpectedValue('not a ZUGFeRD pdf');
        }
    }

    public function getXml(): string
    {
        return $this->xml;
    }

    /**
     * @throws Tinebase_Exception_UnexpectedValue
     */
    public static function createFromString(string $data): self
    {
        try {
            $pdf = (new \Smalot\PdfParser\Parser())->parseContent($data);
        } catch (Exception $e) {
            $te = new Tinebase_Exception($e->getMessage(), previous: $e);
            $te->setLogToSentry(true);
            $te->setLogLevelMethod('warn');
            Tinebase_Exception::log($te);
            throw new Tinebase_Exception_UnexpectedValue('not a ZUGFeRD pdf');
        }
        return new self($pdf);
    }
}