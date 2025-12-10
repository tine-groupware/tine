<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Filter
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2009-2025 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 */

/**
 * foreign id filter -> varchar(40), no ref_id set!
 * 
 * Expects:
 * - a filtergroup in options->filtergroup
 * - a controller  in options->controller
 * 
 * Hands over all options to filtergroup
 * Hands over AclFilter functions to filtergroup
 *
 * @package     Tinebase
 * @subpackage  Filter
 */
class Tinebase_Model_Filter_ForeignId extends Tinebase_Model_Filter_ForeignRecord
{
    /**
     * get foreign controller
     * 
     * @return Tinebase_Controller_Record_Abstract
     */
    protected function _getController()
    {
        if ($this->_controller === NULL) {
            $this->_controller = call_user_func($this->_options['controller'] . '::getInstance');
        }
        
        return $this->_controller;
    }
    
    /**
     * set options 
     *
     * @param array $_options
     * @throws Tinebase_Exception_InvalidArgument
     */
    protected function _setOptions(array $_options)
    {
        if (! isset($_options['controller']) || ! isset($_options['filtergroup'])) {
            throw new Tinebase_Exception_InvalidArgument('a controller and a filtergroup must be specified in the options');
        }

        parent::_setOptions($_options);
    }

    /**
     * get foreign filter group
     *
     * @return void
     */
    protected function _setFilterGroup()
    {
        if ($this->_doJoin) {
            $this->_options['subTablename'] = uniqid('a'); // prefix is very important, uniqid might return only digits, we'd be in trouble in mysql then
        }

        parent::_setFilterGroup();
    }
    
    /**
     * appends sql to given select statement
     *
     * @param Zend_Db_Select                $_select
     * @param Tinebase_Backend_Sql_Abstract $_backend
     */
    public function appendFilterSql($_select, $_backend)
    {
        $not = str_starts_with($this->_operator, 'not');
        if ($this->_valueIsNull) {
            $subNot = str_starts_with($this->_orgValue[0][self::OPERATOR] ?? '', 'not');
            // not nulls
            if (($not && !$subNot) || (!$not && $subNot)) {
                $_select->where($this->_getQuotedFieldName($_backend) . ' IS NOT NULL');
                // nulls
            } else {
                $_select->where($this->_getQuotedFieldName($_backend) . ' IS NULL');
            }
            return;
        }

        if ($this->_doJoin && $this->_filterGroup) {
            $groupSelect = new Tinebase_Backend_Sql_Filter_GroupSelect($_select);
            $joinBackend = $this->_getController()->getBackend();
            $mc = $this->_getController()->getModel()::getConfiguration();
            $db = $_backend->getAdapter();
            Tinebase_Backend_Sql_Filter_FilterGroup::appendFilters($groupSelect, $this->_filterGroup, $joinBackend);

            if (!$not) {
                $_select->joinLeft(
                    [$this->_options['subTablename'] => $joinBackend->getTablePrefix() . $joinBackend->getTableName()],
                    $this->_getQuotedFieldName($_backend) . ' = ' .
                    $db->quoteIdentifier($this->_options['subTablename'] . '.' . ($mc ? $mc->getIdProperty() : 'id')),
                    []
                );
                $groupSelect->where($db->quoteIdentifier($this->_options['subTablename']) . '.id IS NOT NULL');
                $groupSelect->appendWhere();

            } else {
                $groupSql = $groupSelect->getSQL();
                $_select->joinLeft(
                    [$this->_options['subTablename'] => $joinBackend->getTablePrefix() . $joinBackend->getTableName()],
                    $this->_getQuotedFieldName($_backend) . ' = ' .
                    $db->quoteIdentifier($this->_options['subTablename'] . '.' . ($mc ? $mc->getIdProperty() : 'id'))
                    . ($groupSql ? ' AND (' . $groupSql . ')' : ''),
                    []
                );
                $_select->where($db->quoteIdentifier($this->_options['subTablename']) . '.id IS NULL');
            }

            return;
        }

        if ($this->_filterGroup) {
            $this->_foreignIds = $this->_getController()->search($this->_filterGroup, null, false, true);
        }

        if ($not) {
            $groupSelect = new Tinebase_Backend_Sql_Filter_GroupSelect($_select);
            $valueIdentifier = $this->_getQuotedFieldName($_backend);
            $groupSelect->orWhere($valueIdentifier . ' IS NULL');
            if (!empty($this->_foreignIds)) {
                $groupSelect->orWhere($valueIdentifier . ' NOT IN (?)', $this->_foreignIds);
            }
            $groupSelect->appendWhere(Zend_Db_Select::SQL_OR);
        } else {
            if (empty($this->_foreignIds)) {
                $_select->where('1 = 0');
            } else {
                $_select->where($this->_getQuotedFieldName($_backend) . ' IN (?)', $this->_foreignIds);
            }
        }
    }
    
    /**
     * set required grants
     * 
     * @param array $_grants
     */
    public function setRequiredGrants(array $_grants)
    {
        $this->_filterGroup->setRequiredGrants($_grants);
    }
    
    /**
     * get filter information for toArray()
     * 
     * @return array
     */
    protected function _getGenericFilterInformation()
    {
        [$appName, , $filterName] = explode('_', static::class);
        
        $result = array(
            'linkType'      => 'foreignId',
            'appName'       => $appName,
            'filterName'    => $filterName,
        );
        
        if (isset($this->_options['modelName'])) {
            [, , $modelName] = explode('_', (string) $this->_options['modelName']);
            $result['modelName'] = $modelName;
        }
        
        return $result;
    }
}
