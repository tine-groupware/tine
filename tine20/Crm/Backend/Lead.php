<?php
/**
 * tine Groupware
 *
 * @package     Crm
 * @subpackage  Backend
 * @license     https://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2025 Metaways Infosystems GmbH (https://www.metaways.de)
 * 
 */

/**
 * backend for leads
 *
 * @package     Crm
 * @subpackage  Backend
 */
class Crm_Backend_Lead extends Tinebase_Backend_Sql_Abstract
{
    /**
     * Table name without prefix
     *
     * @var string
     */
    protected $_tableName = 'metacrm_lead';
    
    /**
     * Model name
     *
     * @var string
     */
    protected $_modelName = 'Crm_Model_Lead';

    /**
     * if modlog is active, we add 'is_deleted = 0' to select object in _getSelect()
     *
     * @var boolean
     */
    protected $_modlogActive = TRUE;

    /**
     * default column(s) for count
     *
     * @var string
     */
    protected $_defaultCountCol = 'id';

    public function __construct($_dbAdapter = NULL, $_options = array())
    {
        parent::__construct($_dbAdapter, $_options);

        $this->_additionalSearchCountCols = [
            Crm_Model_Lead::FLD_TURNOVER => Crm_Model_Lead::FLD_TURNOVER,
            Crm_Model_Lead::FLD_PROBABLE_TURNOVER => new Zend_Db_Expr('SUM('
                . $this->_db->quoteIdentifier('turnover')
                . '*' . $this->_db->quoteIdentifier('probability')
                . '*0.01' . ')'
            ),
            // only needed by \Tinebase_Backend_Sql_Abstract::searchCount, for the sub-select query
            'probability' => 'probability',
        ];
    }

    /**
     * get the basic select object to fetch records from the database
     *  
     * @param array|string|Zend_Db_Expr $_cols columns to get, * per default
     * @param boolean $_getDeleted get deleted records (if modlog is active)
     * @return Zend_Db_Select
     */
    protected function _getSelect($_cols = '*', $_getDeleted = false)
    {
        $select = parent::_getSelect($_cols, $_getDeleted);
        
        // return probableTurnover (turnover * probability)
        if ($_cols === '*'
            // TODO find out why we could need this -> we get duplicate columns if we keep this ...
            // || array_key_exists('probableTurnover', (array)$_cols)
        ) {
            $select->columns(
                array('probableTurnover' => '(' . $this->_db->quoteIdentifier($this->_tableName . '.turnover') 
                    . '*' . $this->_db->quoteIdentifier($this->_tableName . '.probability') . '*0.01)'
                )
            );
        }
        return $select;
    }
}
