<?php
/**
 * Tine 2.0
 * 
 * @package     Voipmanager Management
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Thomas Wadewitz <t.wadewitz@metaways.de>
 * @copyright   Copyright (c) 2007-2018 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * Template Filter Class
 * @package Voipmanager
 */
class Voipmanager_Model_Snom_TemplateFilter extends Tinebase_Model_Filter_FilterGroup
{
    /**
     * application the record belongs to
     *
     * @var string
     */
    protected $_applicationName = 'Voipmanager';
    
    /**
     * @var string name of model this filter group is designed for
     */
    protected $_modelName = Voipmanager_Model_Snom_Template::class;
    
    /**
     * @var array filter model fieldName => definition
     */
    protected $_filterModel = array(
        'query'                => array(
            'filter' => 'Tinebase_Model_Filter_Query', 
            'options' => array(
                'fields' => array('name', 'model', 'description'),
                'modelName' => Voipmanager_Model_Snom_Template::class,
            )
        ),
        'name'          => array('filter' => 'Tinebase_Model_Filter_Text'),
        'description'   => array('filter' => 'Tinebase_Model_Filter_Text'),
        'model'         => array('filter' => 'Tinebase_Model_Filter_Text'),
    );
}
