<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Setup
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2008-2024 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Matthias Greiling <m.greiling@metaways.de>
 */

abstract class Setup_Backend_Schema_Index_Abstract extends Setup_Backend_Schema_Abstract
{
    
    /**
     * the name of the field(s)/column(s) in its own table 
     *
     * @var array
     */
    public $field = array();
    
    /**
     * length for fields
     * 
     * @var array
     */
    public $fieldLength = array();

    /**
     * index defines primary key
     *
     * @var boolean
     */
    public $primary;

    /**
     * index defines unique key
     *
     * @var boolean
     */
    public $unique;

    /**
     * index defines any key, except (foreign, unique or primary)
     *
     * @var boolean
     */
    public $mul;
    
    /**
     * index defines foreign key
     *
     * @var boolean
     */
    public $foreign;
    
    /**
     * name of referenced table of foreign key
     *
     * @var string
     */
    public $referenceTable;
    
    /**
     * name of referenced table field/column of foreign key
     *
     * @var string
     */
    public $referenceField;
    
    /**
     * defines behaviour of foreign key
     *
     * @var boolean
     */
    public $referenceOnDelete;
    
    /**
     * defines behaviour of foreign key
     *
     * @var boolean
     */
    public $referenceOnUpdate;

    /**
     * lenght of index 
     * 
     * @var integer
     */
    public $length = NULL;

    public $fulltext;
    public $ondelete;

    abstract protected function _setIndex($_declaration);

    public function setForeignKey($_foreign)
    {
        $this->foreign = 'true';
    }
}
