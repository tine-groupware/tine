<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Filter
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2017 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 */

/**
 * Tinebase_Model_Filter_Query
 * 
 * filters for all of the given filterstrings if it is contained in at least 
 * one of the defined fields
 * 
 * -> allow search for all Müllers who live in Munich but not all Müllers and all people who live in Munich
 * 
 * The fields to query in _must_ be defined in the options key 'fields'
 * The value string is space-exploded into multiple filterstrings
 * 
 * @package     Tinebase
 * @subpackage  Filter
 */
class Tinebase_Model_Filter_Query extends Tinebase_Model_Filter_FilterGroup
{
    use Tinebase_Model_Filter_AdvancedSearchTrait;

    protected $_field;
    protected $_value;
    protected $_operator;
    protected $_clientOptions;

    protected static $_recursionProtection = [];

    /**
     * constructs a new filter group
     *
     * @param  array $_data
     * @param  string $_condition {AND|OR}
     * @param  array $_options
     * @throws Tinebase_Exception_InvalidArgument
     *
     * TODO $_options param is not used - discard or merge with $_data['options']?
     */
    public function __construct(array $_data = array(), $_condition = '', $_options = array())
    {
        $recursionProtection = static::$_recursionProtection;

        if (count($_options) > 0) {
            Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__ .
                ' Given options are not used ... put options in $_data[\'options\']');
        }

        $this->_operator = empty($_data['operator']) ? 'contains' : $_data['operator'] ;

        $condition = (str_starts_with((string) $this->_operator, 'not'))
            ? Tinebase_Model_Filter_FilterGroup::CONDITION_AND
            : Tinebase_Model_Filter_FilterGroup::CONDITION_OR;

        parent::__construct(array(),
            $condition,
            $_data['options']);

        if (isset(static::$_recursionProtection[$this->_options['modelName']])) {
            throw new Tinebase_Exception_QueryFilterRecursion();
        }
        static::$_recursionProtection[$this->_options['modelName']] = true;

        if (isset($_data['id'])) {
            $this->setId($_data['id']);
        }
        if (isset($_data['label'])) {
            $this->setLabel($_data['label']);
        }
        if (isset($_data['clientOptions'])) {
            $this->_clientOptions = $_data['clientOptions'];
        }

        $this->_field = $_data['field'];
        $this->_value = $_data['value'];

        if (!empty($this->_value)) {
            $queries = is_array($this->_value) ? $this->_value : explode(' ', (string) $this->_value);

            /** @var Tinebase_Model_Filter_FilterGroup $parentFilterGroup */
            $parentFilterGroup = $this->_options['parentFilter'];
            /** @var Tinebase_Model_Filter_FilterGroup $innerGroup */
            $innerGroup = new Tinebase_Model_Filter_FilterGroup(array(),
                Tinebase_Model_Filter_FilterGroup::CONDITION_AND);

            switch ($this->_operator) {
                case 'contains':
                case 'notcontains':
                case 'equals':
                case 'not':
                case 'startswith':
                case 'wordstartswith':
                case 'endswith':
                    foreach ($queries as $query) {
                        $subGroup = $this->_getSubfilterGroup($parentFilterGroup, $query, $condition);
                        $innerGroup->addFilterGroup($subGroup);
                    }
                    break;
                case 'notin':
                case 'in':
                    $this->_addFilterToInnerGroup($parentFilterGroup, $queries, $innerGroup);
                    break;
                default:
                    throw new Tinebase_Exception_InvalidArgument('Operator not defined: ' . $this->_operator);
            }

            $this->addFilterGroup($innerGroup);

            if (isset($this->_options['relatedModels']) && isset($this->_options['modelName'])) {
                $relationFilter = $this->_getAdvancedSearchFilter($this->_options['modelName'],
                    $this->_options['relatedModels']);
                if (null !== $relationFilter) {
                    $this->addFilter($relationFilter);
                }
            }
        }

        static::$_recursionProtection = $recursionProtection;
    }

    /**
     * @param Tinebase_Model_Filter_FilterGroup $parentFilterGroup
     * @param string $query
     * @param $condition
     * @return Tinebase_Model_Filter_FilterGroup
     */
    protected function _getSubfilterGroup(Tinebase_Model_Filter_FilterGroup $parentFilterGroup, $query, $condition)
    {
        $subGroup = new Tinebase_Model_Filter_FilterGroup(array(), $condition);
        foreach ($this->_options['fields'] as $field) {
            try {
                $filter = $parentFilterGroup->createFilter(['field' => $field, 'operator' => $this->_operator, 'value' => $query, 'clientOptions' => $this->_clientOptions]);
            } catch (Tinebase_Exception_QueryFilterRecursion) {
                continue;
            }
            $this->_addFilterToGroup($subGroup, $filter);
        }


        return $subGroup;
    }

    /**
     * @param Tinebase_Model_Filter_FilterGroup $parentFilterGroup
     * @param array $queries
     * @param Tinebase_Model_Filter_FilterGroup $innerGroup
     */
    protected function _addFilterToInnerGroup(
        Tinebase_Model_Filter_FilterGroup $parentFilterGroup,
        $queries,
        Tinebase_Model_Filter_FilterGroup $innerGroup)
    {
        foreach ($this->_options['fields'] as $field) {
            try {
                $filter = $parentFilterGroup->createFilter(['field' => $field, 'operator' => $this->_operator, 'value' => $queries, 'clientOptions' => $this->_clientOptions]);
            } catch (Tinebase_Exception_QueryFilterRecursion) {
                continue;
            }
            $this->_addFilterToGroup($innerGroup, $filter);
        }
    }

    /**
     * @param Tinebase_Model_Filter_FilterGroup $group
     * @param Tinebase_Model_Filter_Abstract $filter
     */
    protected function _addFilterToGroup(Tinebase_Model_Filter_FilterGroup $group, Tinebase_Model_Filter_Abstract $filter)
    {
        if ($filter instanceof Tinebase_Model_Filter_FullText) {
            if (! $filter->isQueryFilterEnabled() && !($this->_options['ignoreFullTextConfig'] ?? false)) {
                return;
            }
        }

        $group->addFilter($filter);
    }

    /**
     * returns fieldname of this filter
     *
     * @return string
     */
    public function getField()
    {
        return $this->_field;
    }

    /**
     * gets value
     *
     * @return  mixed
     */
    public function getValue()
    {
        return $this->_value;
    }

    /**
     * gets operator
     *
     * @return string
     */
    public function getOperator()
    {
        return $this->_operator;
    }

    /**
     * set options 
     *
     * @param  array $_options
     * @throws Tinebase_Exception_Record_NotDefined
     * @throws Tinebase_Exception_UnexpectedValue
     */
    protected function _setOptions(array $_options)
    {
        if (empty($_options['fields'])) {
            throw new Tinebase_Exception_Record_NotDefined('Fields must be defined in the options of a query filter');
        }
        if (!isset($_options['parentFilter']) || !is_object($_options['parentFilter'])) {
            throw new Tinebase_Exception_UnexpectedValue('parentFilter needs to be set in options (should be done by parent filter group)');
        }
        
        parent::_setOptions($_options);
    }

    /**
     * returns array with the filter settings of this filter
     *
     * @param  bool $_valueToJson resolve value for json api?
     * @return array
     */
    public function toArray($_valueToJson = false)
    {
        $result = array(
            'field'     => $this->_field,
            'operator'  => $this->_operator,
            'value'     => $this->_value
        );

        if ($this->_id) {
            $result['id'] = $this->_id;
        }
        if ($this->_label) {
            $result['label'] = $this->_label;
        }

        return $result;
    }
}
