<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Filter
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2016-2017 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */

/**
 * Tinebase_Model_Filter_Path
 * 
 * filters own ids match result of path search
 * 
 * <code>
 *      'contact'        => array('filter' => 'Tinebase_Model_Filter_Path', 'options' => array(
 *      )
 * </code>     
 * 
 * @package     Tinebase
 * @subpackage  Filter
 */
class Tinebase_Model_Filter_Path extends Tinebase_Model_Filter_Text
{
    protected $_controller = null;

    /**
     * @var array
     */
    protected $_pathRecordIds = null;

    /**
     * get path controller
     * 
     * @return Tinebase_Record_Path
     */
    protected function _getController()
    {
        if ($this->_controller === null) {
            $this->_controller = Tinebase_Record_Path::getInstance();
        }
        
        return $this->_controller;
    }
    
    /**
     * appends sql to given select statement
     *
     * @param Zend_Db_Select                $_select
     * @param Tinebase_Backend_Sql_Abstract $_backend
     */
    public function appendFilterSql($_select, $_backend)
    {
        if (true !== Tinebase_Config::getInstance()->featureEnabled(Tinebase_Config::FEATURE_SEARCH_PATH) ||
                empty($this->_value)) {
            return;
        }

        $modelName = $_backend->getModelName();
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' 
            . 'Adding Path filter for: ' . $modelName);
        
        $this->_resolvePathIds($modelName);

        if (empty($this->_pathRecordIds)) {
            if (!str_contains($this->_operator, 'not')) {
                $_select->where('1 = 0');
            } else {
                $_select->where('1 = 1');
            }
        } else {
            $idField = (isset($this->_options['idProperty']) || array_key_exists('idProperty', $this->_options)) ? $this->_options['idProperty'] : 'id';
            $db = $_backend->getAdapter();
            $qField = $db->quoteIdentifier($_backend->getTableName() . '.' . $idField);
            $_select->where($db->quoteInto("$qField IN (?)", $this->_pathRecordIds));
        }
    }
    
    /**
     * resolve foreign ids
     */
    protected function _resolvePathIds($_model)
    {
        if (! is_array($this->_pathRecordIds)) {
            $paths = $this->_getController()->search(new Tinebase_Model_PathFilter(array(
                array('field' => 'path', 'operator' => 'contains', 'value' => $this->_value)
            )));

            $this->_pathRecordIds = array();
            if ($paths->count() > 0) {

                $searchTerms = Tinebase_Model_Filter_FullText::sanitizeValue($this->_value,
                    Setup_Backend_Factory::factory()->supports('mysql >= 5.6.4 | mariadb >= 10.0.5'));

                if (count($searchTerms) < 1) {
                    if (Tinebase_Core::isLogLevel(Zend_Log::WARN)) {
                        Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ .
                            ' found paths, but sanitized search terms array is empty. value: ' .
                            print_r($this->_value, true));
                    }
                    return;
                }

                array_walk($searchTerms, function (&$val) {
                    $val = mb_strtolower($val);
                });
                $hitIds = array();

                /** @var Tinebase_Model_Path $path */
                foreach ($paths as $path) {
                    $sT = $searchTerms;
                    $pathParts = explode('/', trim($path->path, '/'));
                    $shadowPathParts = explode('/', trim($path->shadow_path, '/'));
                    $offset = 0;
                    foreach ($pathParts as $pathPart) {
                        $pathPart = mb_strtolower($pathPart);
                        $found = array();
                        foreach ($sT as $key => $term) {
                            if (str_contains($pathPart, (string) $term)) {
                                $found[] = $key;
                            }
                        }
                        if (empty($found)) {
                            ++$offset;
                            continue;
                        }
                        foreach($found as $key) {
                            unset($sT[$key]);
                        }
                        if (count($sT) === 0) {
                            break;
                        }
                        ++$offset;
                    }
                    if (count($sT) === 0) {
                        $hits = array_slice($shadowPathParts, $offset);
                        foreach ($hits as $shadowPathPart) {
                            $model = substr($shadowPathPart, 1, strpos($shadowPathPart, '}') - 1);
                            if ($_model !== $model) {
                                continue;
                            }
                            $id = substr($shadowPathPart, strpos($shadowPathPart, '}') + 1);
                            if (false !== ($pos = strpos($id, '{'))) {
                                $id = substr($id, 0, $pos - 1);
                            }
                            $hitIds[$id] = true;
                        }
                    } else {
                        Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ .
                            ' found path, but not all search terms were found in it: ' . print_r($searchTerms, true) . ' path: ' . $path->path);
                    }
                }

                $this->_pathRecordIds = array_keys($hitIds);
            }
        }

        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' foreign ids: ' 
            . print_r($this->_pathRecordIds, TRUE));
    }
}