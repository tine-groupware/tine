<?php
/**
 * convert functions for ImportExportDefinitions from/to json (array) format
 * 
 * @package     Tinebase
 * @subpackage  Convert
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2011 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * convert functions for ImportExportDefinitions from/to json (array) format
 *
 * @package     Tinebase
 * @subpackage  Convert
 */
class Tinebase_Convert_ImportExportDefinition_Json extends Tinebase_Convert_Json
{
    /**
     * converts Tinebase_Record_Interface to external format
     * 
     * @param  Tinebase_Record_Interface $_model
     * @return mixed
     * 
     * @todo rename model to record?
     */
    public function fromTine20Model(Tinebase_Record_Interface $_model)
    {
        /** @var Tinebase_Model_ImportExportDefinition $_model */
        $this->_convertOptions($_model);
        
        $result = parent::fromTine20Model($_model);
        
        return $result;
    }
    
    /**
     * convert plugin_options to array
     * 
     * @param Tinebase_Model_ImportExportDefinition $_definition
     */
    protected function _convertOptions(Tinebase_Model_ImportExportDefinition $_definition)
    {
        $options = array();

        if (is_array($_definition->plugin_options)) {
            $options = $_definition->plugin_options;
        } else if (! empty($_definition->plugin_options)) {
            $options = Tinebase_ImportExportDefinition::getOptionsAsZendConfigXml($_definition)->toArray();
        }

        if (isset($options['autotags'])) {
            $options['autotags'] = $this->_handleAutotags($options['autotags']);
        }

        if (isset($options['container_id'])) {
            $options['container_id'] = Tinebase_Container::getInstance()->getContainerById($options['container_id'])->toArray();
        }
        
        $_definition->plugin_options_json = $options;

        if (isset($options['plugin_options_definition']) && $options['plugin_options_definition']) {
            if (method_exists($_definition->plugin, 'getPluginOptionsDefinition')) {
                $validPluginOptions = [];
                foreach (call_user_func($_definition->plugin . '::getPluginOptionsDefinition') as $name => $pluginOptionsDefinition) {
                    if ((isset($pluginOptionsDefinition['definitionPluginOptionDefinitionRequired']) && !$pluginOptionsDefinition['definitionPluginOptionDefinitionRequired']) ||
                        (is_array($options['plugin_options_definition']) && array_key_exists($name, $options['plugin_options_definition']) && is_array($options['plugin_options_definition'][$name]))) {
                        $validPluginOptions[$name] = array_replace_recursive($pluginOptionsDefinition, $options['plugin_options_definition'][$name]);
                    } else {
                        $validPluginOptions[$name] = $pluginOptionsDefinition;
                    }
                }
                $_definition->plugin_options_definition = $validPluginOptions;
            }
        }
    }
    
    /**
     * resolve and sanitize tags
     * 
     * @param array $_autotagOptions
     * @return array
     */
    protected function _handleAutotags($_autotagOptions)
    {
        $result = $_autotagOptions['tag'] ?? $_autotagOptions;
        
        if (isset($result['name'])) {
            $result = array($result);
        }
        
        // resolve tags if they exist
        foreach ($result as $idx => $value) {
            if (isset($value['id'])) {
                try {
                    $tag = Tinebase_Tags::getInstance()->get($value['id']);
                    $result[$idx] = $tag->toArray();
                } catch (Tinebase_Exception_NotFound) {
                    // do nothing
                }
            }
        }
        
        return $result;
    }

    /**
     * converts Tinebase_Record_RecordSet to external format
     * 
     * @param Tinebase_Record_RecordSet  $_records
     * @param Tinebase_Model_Filter_FilterGroup $_filter
     * @param Tinebase_Model_Pagination $_pagination
     * 
     * @return mixed
     */
    public function fromTine20RecordSet(?\Tinebase_Record_RecordSet $_records = NULL, $_filter = NULL, $_pagination = NULL)
    {
        foreach ($_records as $record) {
            $this->_convertOptions($record);
        }
        
        $result = parent::fromTine20RecordSet($_records, $_filter, $_pagination);
        
        return $result;
    }
}
