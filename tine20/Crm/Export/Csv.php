<?php
/**
 * Crm csv generation class
 *
 * @package     Crm
 * @subpackage  Export
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 * 
 * @todo        add products
 */

/**
 * Crm csv generation class
 * 
 * @package     Crm
 * @subpackage  Export
 * 
 */
class Crm_Export_Csv extends Tinebase_Export_Csv
{
    /**
     * @var string application name of this export class
     */
    protected $_applicationName = 'Crm';
    
    /**
     * the record model
     *
     * @var string
     */
    protected $_modelName = 'Crm_Model_Lead';
    
    /**
     * lead relation types
     * 
     * @var array
     */
    protected $_relationsTypes = array('CUSTOMER', 'PARTNER', 'RESPONSIBLE', 'TASK', 'PRODUCT');

    /**
     * lead relation subfields
     * 
     * @var array
     */
    protected $_relationFields = array(
        'CUSTOMER' => array(
            'org_name',
            'n_family',
            'n_given',
            'adr_one_street',
            'adr_one_postalcode',
            'adr_one_locality',
            'adr_one_countryname',
            'tel_work',
            'tel_cell',
            'email',
        ),
        'PARTNER' => array(
            'org_name',
            'n_family',
            'n_given',
            'adr_one_street',
            'adr_one_postalcode',
            'adr_one_locality',
            'adr_one_countryname',
            'tel_work',
            'tel_cell',
            'email',
        ),
        'RESPONSIBLE' => array(
            'org_name',
            'n_family',
            'n_given',
            'adr_one_street',
            'adr_one_postalcode',
            'adr_one_locality',
            'adr_one_countryname',
            'tel_work',
            'tel_cell',
            'email',
        ),
        'PRODUCT' => [
            'name'
        ],
        'TASK' => [
            'summary'
        ]
    );
    
    /**
     * special fields
     * 
     * @var array
     */
    protected $_specialFields = array('leadstate_id' => 'Leadstate', 'leadtype_id' => 'Leadtype', 'leadsource_id' => 'Leadsource');
    
    /**
     * get record relations
     * 
     * @var boolean
     */
    protected $_getRelations = TRUE;
    
    /**
     * fields to skip
     * 
     * @var array
     */
    protected $_skipFields = array(
        'id'                    ,
        'created_by'            ,
        'creation_time'         ,
        'last_modified_by'      ,
        'last_modified_time'    ,
        'is_deleted'            ,
        'deleted_time'          ,
        'deleted_by'            ,
        'relations'             ,
        'tasks'                 ,
    );
        
    /**
     * special field value function
     * 
     * @param Tinebase_Record_Interface $_record
     * @param string $_fieldName
     * @return string
     */
    protected function _addSpecialValue(Tinebase_Record_Interface $_record, $_fieldName)
    {
        $keyFieldName = preg_replace('/_id/', 's', $_fieldName);
        return Crm_Config::getInstance()->get($keyFieldName)->getTranslatedValue($_record->$_fieldName);
    }

    /**
     * add relation values from related records
     *
     * @param Tinebase_Record_Interface $record
     * @param string $relationType
     * @param string $recordField
     * @param boolean $onlyFirstRelation
     * @param string $keyfield
     * @param string $application
     * @return string
     */
    protected function _addRelations(Tinebase_Record_Interface $record, $relationType, $recordField = NULL, $onlyFirstRelation = FALSE, $keyfield = NULL, $application = NULL)
    {
        if ('TASK' === $relationType) {
            return $record->tasks instanceof Tinebase_Record_RecordSet ? join(';', $record->tasks->summary): '';
        }
        return parent::_addRelations($record, $relationType, $recordField, $onlyFirstRelation, $keyfield, $application);
    }

    /**
     * resolve records and prepare for export (set user timezone, ...)
     *
     * @param Tinebase_Record_RecordSet $_records
     */
    protected function _resolveRecords(Tinebase_Record_RecordSet $_records)
    {
        Tinebase_Record_Expander::expandRecords($_records);
        parent::_resolveRecords($_records);
    }
}
