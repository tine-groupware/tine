<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Filter
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2021 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 */

/**
 * Tinebase_Model_Filter_Tag
 * 
 * filters by given tag
 * 
 * @package     Tinebase
 * @subpackage  Filter
 */
class Tinebase_Model_Filter_Tag extends Tinebase_Model_Filter_Abstract
{
    /**
     * @var array list of allowed operators
     */
    protected $_operators = array(
        0 => 'equals',
        1 => 'not',
        2 => 'in',
        3 => 'notin',
        4 => 'contains',
    );
    
    /**
     * @var array maps abstract operators to sql operators
     */
    protected $_opSqlMap = array(
        'equals'     => array('sqlop' => ' IS NOT NULL'),
        'not'        => array('sqlop' => ' IS NULL'    ),
        'in'         => array('sqlop' => ' IS NOT NULL'),
        'notin'      => array('sqlop' => ' IS NULL'    ),
        'contains'   => array('sqlop' => ' IS NOT NULL'),
    );
    
    /**
     * set options 
     *
     * @param  array $_options
     * @throws Tinebase_Exception_InvalidArgument
     */
    protected function _setOptions(array $_options)
    {
        if (! isset($_options['applicationName'])) {
            throw new Tinebase_Exception_InvalidArgument('Tag filter needs the applicationName option');
        }
        
        $_options['idProperty'] ??= 'id';
        
        $this->_options = $_options;
    }
    
    /**
     * appends sql to given select statement
     *
     * @param  Zend_Db_Select                $_select
     * @param  Tinebase_Backend_Sql_Abstract $_backend
     * @throws Tinebase_Exception_NotFound
     */
    public function appendFilterSql($_select, $_backend)
    {
        // check the view right of the tag (throws Exception if not accessible)
        if ($this->_operator === 'contains') {
            $tagIds = Tinebase_Tags::getInstance()->searchTags(new Tinebase_Model_TagFilter([
                'name' => $this->_value
            ]))->getArrayOfIds();
        } else {
            $tagIds = $this->_value;
        }

        // don't take empty tag filter into account
        if (empty($tagIds)) {
            if ($this->_operator === 'in' || $this->_operator === 'contains') {
                $_select->where('1=0');
            }
            return;
        }

        $db = Tinebase_Core::getDb();
        $idProperty = $db->quoteIdentifier($this->_options['idProperty']);
        
        $app = Tinebase_Application::getInstance()->getApplicationByName($this->_options['applicationName']);

        $correlationName = Tinebase_Record_Abstract::generateUID(5) . ((is_array($tagIds) === true)
                ? implode(',', $tagIds) : $tagIds) . 'tag';
        // per left join we add a tag column named as the tag and filter this joined column
        // NOTE: we name the column we join like the tag, to be able to join multiple tag criteria (multiple invocations of this function)
        $_select->joinLeft(
            /* what */    array($correlationName => SQL_TABLE_PREFIX . 'tagging'), 
            /* on   */    $db->quoteIdentifier("{$correlationName}.record_id") . " = $idProperty " .
                " AND " . $db->quoteIdentifier("{$correlationName}.application_id") . " = " . $db->quote($app->getId()) .
                " AND " . $db->quoteInto($db->quoteIdentifier("{$correlationName}.tag_id") . " IN (?)", (array) $tagIds),
        /* select */  array());
        
        $_select->where($db->quoteIdentifier("{$correlationName}.tag_id") . $this->_opSqlMap[$this->_operator]['sqlop']);
        $_select->group($this->_options['idProperty']);
    }
    
    /**
     * returns array with the filter settings of this filter
     *
     * @param  bool $_valueToJson resolve value for json api?
     * @return array
     */
    public function toArray($_valueToJson = false)
    {
        $result = parent::toArray($_valueToJson);
        
        if ($this->_operator !== 'contains' && $_valueToJson == true) {
            $tags = Tinebase_Tags::getInstance()->getTagsById($this->_value)->toArray();
            if (count($tags) > 0) {
                $result['value'] = (is_array($this->_value)) ? $tags : $tags[0];
            } else {
                $result['value'] = '';
            }
        }
        return $result;
    }
}
