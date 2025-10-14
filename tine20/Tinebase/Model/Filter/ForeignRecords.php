<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Filter
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2017-2025 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */

/**
 * foreign id filter
 * 
 * Expects:
 * - a record class in options->recordClassName
 * - a controller class in options->controllerClassName
 * 
 * Hands over all options to filtergroup
 * Hands over AclFilter functions to filtergroup
 *
 * @package     Tinebase
 * @subpackage  Filter
 */
class Tinebase_Model_Filter_ForeignRecords extends Tinebase_Model_Filter_ForeignId
{
    /**
     * set options
     *
     * @param array $_options
     * @throws Tinebase_Exception_InvalidArgument
     */
    protected function _setOptions(array $_options)
    {
        if (! isset($_options['refIdField'])) {
            throw new Tinebase_Exception_InvalidArgument('refIdField is required');
        }
        if (! isset($_options['filtergroup']) && isset($_options['recordClassName'])) {
            $_options['filtergroup'] = $_options['recordClassName'] . 'Filter';
        }
        if (! isset($_options['controller']) && isset($_options['controllerClassName'])) {
            $_options['controller'] = $_options['controllerClassName'];
        }
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
        if ($this->_doJoin && $this->_filterGroup) {
            $groupSelect = new Tinebase_Backend_Sql_Filter_GroupSelect($_select);
            $joinBackend = $this->_getController()->getBackend();
            Tinebase_Backend_Sql_Filter_FilterGroup::appendFilters($groupSelect, $this->_filterGroup, $joinBackend);

            $db = $_backend->getAdapter();
            $orgField = $this->_field;
            $this->_field = 'id';
            $not = str_starts_with($this->_operator, 'not');

            try {
                if ($this->_valueIsNull) {
                    $subNot = str_starts_with($this->_orgValue[0][self::OPERATOR] ?? '', 'not');
                    if (($not && !$subNot) || (!$not && $subNot)) {
                        $_select->joinLeft(
                            [$this->_options['subTablename'] => $joinBackend->getTablePrefix() . $joinBackend->getTableName()],
                            $this->_getQuotedFieldName($_backend) . ' = ' .
                            $db->quoteIdentifier($this->_options['subTablename'] . '.' . $this->_options['refIdField']),
                            []
                        );
                        $_select->where($db->quoteIdentifier($this->_options['subTablename']) . '.id IS NOT NULL');
                    } else {
                        $_select->joinLeft(
                            [$this->_options['subTablename'] => $joinBackend->getTablePrefix() . $joinBackend->getTableName()],
                            $this->_getQuotedFieldName($_backend) . ' = ' .
                            $db->quoteIdentifier($this->_options['subTablename'] . '.' . $this->_options['refIdField']),
                            []
                        );
                        $_select->where($db->quoteIdentifier($this->_options['subTablename']) . '.id IS NULL');
                    }
                } else {
                    if (!$not) {
                        $_select->joinLeft(
                            [$this->_options['subTablename'] => $joinBackend->getTablePrefix() . $joinBackend->getTableName()],
                            $this->_getQuotedFieldName($_backend) . ' = ' .
                            $db->quoteIdentifier($this->_options['subTablename'] . '.' . $this->_options['refIdField']),
                            []
                        );
                        $groupSelect->where($db->quoteIdentifier($this->_options['subTablename']) . '.id IS NOT NULL');
                        $groupSelect->appendWhere();
                    } else {
                        $groupSql = $groupSelect->getSQL();
                        $_select->joinLeft(
                            [$this->_options['subTablename'] => $joinBackend->getTablePrefix() . $joinBackend->getTableName()],
                            $this->_getQuotedFieldName($_backend) . ' = ' .
                            $db->quoteIdentifier($this->_options['subTablename'] . '.' . $this->_options['refIdField'])
                            . ($groupSql ? ' AND (' . $groupSql . ')' : ''),
                            []
                        );
                        $_select->where($db->quoteIdentifier($this->_options['subTablename']) . '.id IS NULL');
                    }
                }
            } finally {
                $this->_field = $orgField;
            }

            return;
        }

        if (! is_array($this->_foreignIds) && null !== $this->_filterGroup) {
            $this->_foreignIds = array_keys($this->_getController()
                ->search($this->_filterGroup, null, false, $this->_options['refIdField']));
        }

        // TODO allow to configure id property or get it from model config
        $orgField = $this->_field;
        $this->_field = $this->_options['id_field'] ?? 'id';

        try {
            $not = str_starts_with($this->_operator, 'not');
            if ($this->_valueIsNull) {
                $subNot = str_starts_with($this->_orgValue[0][self::OPERATOR] ?? '', 'not');
                if (($not && !$subNot) || (!$not && $subNot)) {
                    if (empty($this->_foreignIds)) {
                        $_select->where('1 = 0');
                    } else {
                        $_select->where($this->_getQuotedFieldName($_backend) . ' IN (?)', $this->_foreignIds);
                    }
                } else {
                    if (empty($this->_foreignIds)) {
                        $_select->where('1 = 1');
                    } else {
                        $_select->where($this->_getQuotedFieldName($_backend) . ' NOT IN (?)', $this->_foreignIds);
                    }
                }
            } else {
                if ($not) {
                    if (empty($this->_foreignIds)) {
                        $_select->where('1 = 1');
                    } else {
                        $_select->where($this->_getQuotedFieldName($_backend) . ' NOT IN (?)', $this->_foreignIds);
                    }
                } else {
                    if (empty($this->_foreignIds)) {
                        $_select->where('1 = 0');
                    } else {
                        $_select->where($this->_getQuotedFieldName($_backend) . ' IN (?)', $this->_foreignIds);
                    }
                }
            }
        } finally {
            $this->_field = $orgField;
        }
    }
}