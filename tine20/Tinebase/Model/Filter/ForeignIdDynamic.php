<?php declare(strict_types=1);
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Filter
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2026-2026 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */

use Tinebase_ModelConfiguration_Const as TMCC;

class Tinebase_Model_Filter_ForeignIdDynamic extends Tinebase_Model_Filter_ForeignId
{
    // we keep it simple, appendFilterSql would need to be adjusted significantly for other operators!
    protected $_operators = [
        'equals', //expects ID as value
        'in', //expects IDs as value
        'definedBy',
    ];

    public const REF_MODEL_VALUE = 'ref_model_value';

    protected function _setOptions(array $_options)
    {
        if (! isset($_options[TMCC::REF_MODEL_FIELD]) || ! isset($_options[self::REF_MODEL_VALUE])) {
            throw new Tinebase_Exception_InvalidArgument('ref model field and value need to be set!');
        }
        $_options['controller'] = str_replace('_Model_', '_Controller_', $_options[self::REF_MODEL_VALUE]);
        $_options['filtergroup'] = $_options[self::REF_MODEL_VALUE];
        parent::_setOptions($_options);
    }
    
    /**
     * appends sql to given select statement
     *
     * @param Zend_Db_Select                $_select
     * @param Tinebase_Backend_Sql_Abstract $_backend
     */
    public function appendFilterSql($_select, $_backend)
    {
        if ('definedBy' === $this->_operator) {
            $this->_foreignIds = $this->_getController()->search($this->_filterGroup, null, false, true);
        }

        if (empty($this->_foreignIds)) {
            $_select->where('1 = 0');
        } else {
            $_select->where($this->_getQuotedFieldName($_backend) . ' IN (?)', $this->_foreignIds);
            $oldValue = $this->_options['field'] ?? null;
            $this->_options['field'] = $this->_options[TMCC::REF_MODEL_FIELD];
            $_select->where($this->_getQuotedFieldName($_backend) . ' = ?', $this->_options[self::REF_MODEL_VALUE]);
            if (null === ($this->_options['field'] = $oldValue)) {
                unset($this->_options['field']);
            }
        }
    }
}
