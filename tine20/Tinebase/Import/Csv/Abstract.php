<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2018 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * abstract csv import class
 * 
 * some documentation for the xml import definition:
 * 
 * <delimiter>TAB</delimiter>:           use tab as delimiter
 * <config> main tags
 * <container_id>34</container_id>:     container id for imported records (required)
 * <encoding>UTF-8</encoding>:          encoding of input file
 * <duplicates>1<duplicates>:           check for duplicates
 * <use_headline>0</use_headline>:      just remove the headline/first line but do not use it for mapping
 *
 * <mapping><field> special tags:
 * <append>glue</append>:               value is appended to destination field with 'glue' as glue
 * <replace>\n</replace>:               replace \r\n with \n
 * <fixed>fixed</fixed>:                the field has a fixed value ('fixed' in this example)
 * 
 *
 * @todo        add tests for notes
 * @todo        add more documentation
 * @package     Tinebase
 * @subpackage  Import
 */
abstract class Tinebase_Import_Csv_Abstract extends Tinebase_Import_Abstract
{
    /**
     * csv headline
     * 
     * @var array
     */
    protected $_headline = array();
    
    /**
     * special delimiters
     * 
     * @var array
     */
    protected $_specialDelimiter = array(
        'TAB'   => "\t"
    );
    
    /**
     * constructs a new importer from given config
     * 
     * @param array $_options
     * @throws Tinebase_Exception_InvalidArgument
     */
    public function __construct(array $_options = array())
    {
        $this->_options = array_merge($this->_options, array(
            'maxLineLength'               => 8000,
            'delimiter'                   => ',',
            'enclosure'                   => '"',
            'escape'                      => '\\',
            'encodingTo'                  => 'UTF-8',
            'mapping'                     => '',
            'headline'                    => 0,
            'use_headline'                => 1,
            'mapUndefinedFieldsEnable'    => 0,
            'mapUndefinedFieldsIgnoreEmpty' => 0,
            'mapUndefinedFieldsTo'        => 'description',
            'demoData'                    => false
        ));

        parent::__construct($_options);

        if (empty($this->_options['model'])) {
            throw new Tinebase_Exception_InvalidArgument(static::class . ' needs model in config.');
        }
        
        $this->_setController();
    }

    /**
     * get raw data of a single record
     * 
     * @param  resource $_resource
     * @return array|null
     */
    protected function _getRawData(&$_resource)
    {
        $delimiter = ((isset($this->_specialDelimiter[$this->_options['delimiter']])
            || array_key_exists($this->_options['delimiter'], $this->_specialDelimiter))
        )
            ? $this->_specialDelimiter[$this->_options['delimiter']]
            : $this->_options['delimiter'];
        $lineData = fgetcsv(
            $_resource,
            $this->_options['maxLineLength'],
            $delimiter,
            $this->_options['enclosure'],
            (string) $this->_options['escape']
        );
        
        if (is_array($lineData) && count($lineData) == 1) {
            if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) Tinebase_Core::getLogger()->notice(
                __METHOD__ . '::' . __LINE__ . ' Only got 1 field in line. Wrong delimiter?');
            return null;
        }
        
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__
            . ' Raw data: ' . print_r($lineData, true));
        
        return $lineData;
    }
    
    /**
     * do something before the import
     * 
     * @param resource $_resource
     */
    protected function _beforeImport($_resource = NULL)
    {
        // get headline
        if (isset($this->_options['headline']) && $this->_options['headline']) {
            $firstLine = $this->_getRawData($_resource);
            $this->_headline = $firstLine ?: array();
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                . ' Got headline: ' . implode(', ', $this->_headline));
            if (! $this->_options['use_headline']) {
                // just read headline but do not use it
                $this->_headline = array();
            } else {
                array_walk($this->_headline, function(&$value) {
                    $value = trim(self::removeBomUtf8($value));
                });
            }
        }
    }

    /**
     * remove byte order mark
     *
     * @param string $s
     * @return string
     */
    public static function removeBomUtf8(string $s): string
    {
        if(substr($s,0,3)==chr(hexdec('EF')).chr(hexdec('BB')).chr(hexdec('BF'))){
            return substr($s,3);
        }else{
            return $s;
        }
    }

    /**
     * do the mapping
     *
     * @param array $_data
     * @return array
     * @throws Tinebase_Exception_UnexpectedValue
     */
    protected function _doMapping($_data)
    {
        $data = array();
        $_data_indexed = array();

        if (! $_data) {
            if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) Tinebase_Core::getLogger()->notice(
                __METHOD__ . '::' . __LINE__ . ' Got empty raw data - skipping.');
            return $data;
        }

        if (!empty($this->_headline)) {
            $headlineSize = sizeof($this->_headline);
            $dataSize = sizeof($_data);
            if ($headlineSize > $dataSize) {
                $arrayWithEmptyValues = array_fill($dataSize, $headlineSize - $dataSize, '');
                if (is_array($arrayWithEmptyValues)) {
                    $_data = array_merge($_data, $arrayWithEmptyValues);
                }
            } elseif ($dataSize > $headlineSize) {
                // TODO throw an exception if this happens?
                $arrayWithUnknownValues = array_fill($headlineSize, $dataSize - $headlineSize, 'unknown');
                if (is_array($arrayWithUnknownValues)) {
                    $this->_headline = array_merge($this->_headline, $arrayWithUnknownValues);
                }
            }
            $_data_indexed = array_combine($this->_headline, $_data);
        }

        if (! isset($this->_options['mapping']['field']) || ! is_array($this->_options['mapping']['field'])) {
            throw new Tinebase_Exception_UnexpectedValue('No field mapping defined');
        }

        $this->_mapValuesToDestination($_data_indexed, $_data, $data);

        if ($this->_options['mapUndefinedFieldsEnable'] == 1) {
            $undefinedFieldsText = $this->_createInfoTextForUnmappedFields($_data_indexed);
            if (! $undefinedFieldsText === false) {
                if ((isset($data[$this->_options['mapUndefinedFieldsTo']]) || array_key_exists($this->_options['mapUndefinedFieldsTo'], $data))) {
                    $data[$this->_options['mapUndefinedFieldsTo']] .= $this->_createInfoTextForUnmappedFields($_data_indexed);
                } else {
                    $data[$this->_options['mapUndefinedFieldsTo']] = $this->_createInfoTextForUnmappedFields($_data_indexed);
                }
            }
        }
        
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__
            . ' Mapped data: ' . print_r($data, true));
        
        return $data;
    }

    /**
     * map values to destination fields
     *
     * @param $_data_indexed
     * @param $_data
     * @param $data
     */
    protected function _mapValuesToDestination($_data_indexed, $_data, &$data)
    {
        foreach ($this->_options['mapping']['field'] as $index => $field) {
            if (empty($_data_indexed) && isset($_data[$index])) {
                $value = $_data[$index];
            } else if (isset($field['source']) && isset($_data_indexed[$field['source']])) {
                if (isset($field['append']) && isset($data[$field['destination']])) {
                    $value = $data[$field['destination']] . $field['append'] . $_data_indexed[$field['source']];
                } else {
                    $value = $_data_indexed[$field['source']];
                }
            } else {
                if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__
                    . ' No value found for field ' . ($field['source'] ?? print_r($field, true)));
                continue;
            }

            if ((! isset($field['destination']) || empty($field['destination'])) && ! isset($field['destinations'])) {
                if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__
                    . ' No destination in definition for field ' . $field['source']);
                continue;
            }

            if (isset($field['destinations']) && isset($field['destinations']['destination'])) {
                $destinations = $field['destinations']['destination'];
                $delimiter = isset($field['$separator']) && ! empty($field['$separator']) ? $field['$separator'] : ' ';
                $values = array_map('trim', explode($delimiter, (string) $value, count($destinations)));
                if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__
                    . ' values: ' . print_r($values, true));
                $i = 0;
                foreach ($destinations as $destination) {
                    if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__
                        . ' destination ' . $destination);
                    if (isset($values[$i])) {
                        $data[$destination] = trim($values[$i]);
                    }
                    $i++;
                }
            } else {
                $data[$field['destination']] = $value;
            }
        }
    }
    
    /**
     * Generates a text with every undefined data from import 
     * 
     * @param array $_data_indexed
     * @return string
     */
    protected function _createInfoTextForUnmappedFields ($_data_indexed)
    {
        $translation = Tinebase_Translation::getTranslation();
        $ignoreEmpty = (bool) $this->_options['mapUndefinedFieldsIgnoreEmpty'];
        
        $validKeys = array();
        foreach ($this->_options['mapping']['field'] as $keys) {
            $validKeys[$keys['source']] = null;
        }
        // This is an array containing every not mapped field as key with his value.
        $notImportedFields = array_diff_key($_data_indexed, $validKeys);
        
        if (count($notImportedFields) >= 1) {
            $description = ! $ignoreEmpty
                ? sprintf($translation->_("The following fields weren't imported: %s"), "\n") : '';
            $valueIfEmpty = $translation->_("N/A");
            
            foreach ($notImportedFields as $nKey => $nVal) {
                if (trim((string) $nVal) == "") {
                    if ($ignoreEmpty) {
                        continue;
                    }
                    $nVal = $valueIfEmpty;
                }
                if (trim($nKey) == "") {
                    $nKey = $valueIfEmpty;
                }

                $description .= $nKey . " : " . $nVal . " \n";
            }
            $return = $description;
        } else {
            $return = false;
        }
        return $return;
    }
}
