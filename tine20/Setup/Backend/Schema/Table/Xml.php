<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Setup
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2008-2023 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Matthias Greiling <m.greiling@metaways.de>
 */

class Setup_Backend_Schema_Table_Xml extends Setup_Backend_Schema_Table_Abstract
{
    public array $requirements;

    public function __construct($_tableDefinition = NULL)
    {
        if($_tableDefinition !== NULL) {
            if(!$_tableDefinition instanceof SimpleXMLElement) {
                $_tableDefinition = new SimpleXMLElement($_tableDefinition);
            }

            if ($this->getBackend()->getDb()->getConfig()['charset'] === 'utf8') {
                $this->charset = 'utf8';
                $this->collation = 'utf8_unicode_ci';
            } else {
                $this->charset = 'utf8mb4';
                $this->collation = 'utf8mb4_unicode_ci';
            }
            
            $this->setName($_tableDefinition->name);
            $this->comment = (string) $_tableDefinition->comment;
            $this->version = (string) $_tableDefinition->version;

            $this->requirements = array();
            if ($_tableDefinition->requirements) {
                $requirements = $_tableDefinition->requirements->xpath('./required');
                if (is_array($requirements)) {
                    foreach($requirements as $requirement) {
                        $this->requirements[] = (string)$requirement;
                    }
                }
            }
            
            foreach ($_tableDefinition->declaration->field as $field) {
                $this->addField(Setup_Backend_Schema_Field_Factory::factory('Xml', $field));
            }
    
            foreach ($_tableDefinition->declaration->index as $index) {
                $this->addIndex(Setup_Backend_Schema_Index_Factory::factory('Xml', $index));
            }
        }
    }    
}
