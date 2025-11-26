<?php declare(strict_types=1);
/**
 * Addressbook Yealink XML generation class
 *
 * @package     Addressbook
 * @subpackage  Export
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2023 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * Addressbook Yealink XML generation class
 *
 * @package     Addressbook
 * @subpackage  Export
 */
class Addressbook_Export_Yealinkxml extends Tinebase_Export_Abstract
{
    protected $_format = 'xml';
    protected $_applicationName = Addressbook_Config::APP_NAME;
    protected string|XMLWriter $_xml = '';
    protected $_defaultExportname = 'adb_yealinkxml';

    public static function getDefaultFormat()
    {
        return 'xml';
    }

    public function getDownloadContentType()
    {
        return 'application/xml';
    }

    public function generate()
    {
        $this->_sortInfo = ['sort' => 'container_id'];
        $this->_groupByProperty = 'container_id';
        $this->_groupByProcessor = fn(&$val) => $val instanceof Tinebase_Record_Interface ? $val = $val->getId() : null;

        $this->_xml = new XMLWriter();
        $this->_xml->openMemory();
        $this->_xml->startDocument();
        $this->_xml->startElement('YealinkIPPhoneBook');
        $this->_xml->startElement('Title');
        $this->_xml->text(Tinebase_Core::getUrl(Tinebase_Core::GET_URL_HOST));
        $this->_xml->endElement(); // Title

        $this->_exportRecords();

        $this->_xml->endElement(); // YealinkIPPhoneBook;
    }

    protected function _startGroup()
    {
        parent::_startGroup();
        $container = Tinebase_Container::getInstance()->getContainerById($this->_lastGroupValue);

        $this->_xml->startElement('Menu');
        $this->_xml->startAttribute('Name');
        $this->_xml->text($container->name);
        $this->_xml->endAttribute();
    }

    protected function _endGroup()
    {
        parent::_endGroup();
        $this->_xml->endElement(); // Menu
    }

    public function write($target = 'php://output')
    {
        $this->save($target);
    }

    public function save($target = null)
    {
        if (!$target) {
            $target = 'php://output';
        }
        if ($this->_xml instanceof XMLWriter) {
            $this->_xml = $this->_xml->outputMemory();
        }
        file_put_contents($target, $this->_xml);
    }

    /**
     * @param Addressbook_Model_Contact $_record
     *
     * @todo @refactor split this up in multiple FNs
     */
    protected function _processRecord(Tinebase_Record_Interface $_record)
    {
        $unitStarted = false;
        $startUnitFn = function() use($_record) {
            $this->_xml->startElement('Unit');
            $this->_xml->startAttribute('Name');
            $this->_xml->text($_record->getTitle());
            $this->_xml->endAttribute(); // Name
        };

        $i = 1;
        foreach (Addressbook_Model_Contact::getTelefoneFields() as $field) {
            if ($val = $_record->{$field . '_normalized'}) {
                if (!$unitStarted) {
                    $startUnitFn();
                    $unitStarted = true;
                }
                $this->_xml->startAttribute('Phone' . ($i++));
                $this->_xml->text($val);
                $this->_xml->endAttribute();
            }
        }

        if ($unitStarted) {
            $this->_xml->endElement(); //Unit
        }
    }
}