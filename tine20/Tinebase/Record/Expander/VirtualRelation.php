<?php
/**
 * expands records based on provided definition
 *
 * @package     Tinebase
 * @subpackage  Record
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2020 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

use Tinebase_ModelConfiguration_Const as MCC;

class Tinebase_Record_Expander_VirtualRelation extends Tinebase_Record_Expander_Property
{
    public function __construct(protected $_cfg, $_model, $_property, $_expanderDefinition, Tinebase_Record_Expander $_rootExpander)
    {
        parent::__construct($_model, $_property, $_expanderDefinition, $_rootExpander);
    }

    protected function _lookForDataToFetch(Tinebase_Record_RecordSet $_records)
    {
        $this->_addRecordsToProcess($_records);
        $self = $this;
        $this->_rootExpander->_registerDataToFetch(new Tinebase_Record_Expander_DataRequest_VirtualRelation(
            // workaround: [$this, '_setRelationData'] doesn't work and in this case it shouldn't anyway
            function($_data) use($self, $_records) {$self->_setData($_records);}));
    }

    protected function _setData(Tinebase_Record_RecordSet $_data)
    {
        foreach ($_data as $record) {
            if (!isset($record['relations'])) {
                try {
                    $controller = Tinebase_Core::getApplicationInstance($_data->getRecordClassName());
                    if (method_exists($controller, 'get')) {
                        $record = $controller->get($record['id']);
                    } else {
                        continue;
                    }
                } catch (Exception $e) {
                    if (Tinebase_Core::isLogLevel(Zend_Log::ERR)) {
                        Tinebase_Core::getLogger()->err(
                            __METHOD__ . '::' . __LINE__ . " " . $e);
                    }
                    continue;
                }
            }

            if (!$record->relations instanceof Tinebase_Record_RecordSet) {
                if (Tinebase_Core::isLogLevel(Zend_Log::ERR)) {
                    Tinebase_Core::getLogger()->err(
                        __METHOD__ . '::' . __LINE__ . ' Could not fetch record relations of record id '
                        . $record->getId());
                }
                continue;
            }

            $record->{$this->_property} = new Tinebase_Record_RecordSet($this->_model, $record->relations
                ->filter('related_model', $this->_cfg[MCC::RECORD_CLASS_NAME])->filter('type', $this->_cfg[MCC::TYPE])
                ->related_record);
        }
    }
}
