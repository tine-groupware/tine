<?php declare(strict_types=1);
/**
 * expands records based on provided definition
 *
 * @package     Tinebase
 * @subpackage  Record
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2018-2025 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

use Tinebase_ModelConfiguration_Const as TMCC;

abstract class Tinebase_Record_Expander_Abstract
{
    const EXPANDER_USE_JSON_EXPANDER = 'useJsonExpander';
    const EXPANDER_PROPERTIES = 'properties';
    const EXPANDER_PROPERTY_CLASSES = 'propertyClasses';
    const EXPANDER_FULL = 'full';
    const EXPANDER_REPLACE_GET_TITLE = 'replaceGetTitle';
    const GET_DELETED = 'getDeleted';

    const PROPERTY_CLASS_USER = 'user';
    const PROPERTY_CLASS_GRANTS = 'grants';
    const PROPERTY_CLASS_ACCOUNT_GRANTS = 'account_grants';

    const DATA_FETCH_PRIO_DEPENDENTRECORD = 100;
    const DATA_FETCH_PRIO_CONTAINER = 950;
    const DATA_FETCH_PRIO_ACCOUNT_GRANTS = 951; // after container
    const DATA_FETCH_PRIO_USER = 1000;
    const DATA_FETCH_PRIO_RELATION = 800;
    const DATA_FETCH_PRIO_AFTER_RELATION = 801;
    const DATA_FETCH_PRIO_NOTES = 900;

    protected $_model;
    protected $_subExpanders = [];

    /**
     * @var Tinebase_Record_Expander
     */
    protected $_rootExpander;

    public function __construct($_model, $_expanderDefinition, Tinebase_Record_Expander $_rootExpander)
    {
        /** @var Tinebase_Record_Abstract $_model */
        $this->_model = $_model;
        $this->_rootExpander = $_rootExpander;
        if ($_expanderDefinition[self::EXPANDER_USE_JSON_EXPANDER] ?? false) {
            $_expanderDefinition = array_merge($_model::getConfiguration()->jsonExpander, $_expanderDefinition);
        }
        if (isset($_expanderDefinition[self::EXPANDER_PROPERTIES])) {
            foreach ($_expanderDefinition[self::EXPANDER_PROPERTIES] as $prop => $definition) {
                try {
                    $this->_subExpanders[] = Tinebase_Record_Expander_Factory::create($_model, $definition, $prop,
                        $this->_rootExpander);
                } catch (Tinebase_Exception_InvalidArgument $teia) {
                    if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) {
                        Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__ . ' '
                            . $teia->getMessage());
                    }
                }
            }
        }
        if (isset($_expanderDefinition[self::EXPANDER_PROPERTY_CLASSES])) {
            foreach ($_expanderDefinition[self::EXPANDER_PROPERTY_CLASSES] as $propClass => $definition) {
                $this->_subExpanders[] = Tinebase_Record_Expander_Factory::createPropClass($_model, $definition,
                    $propClass, $this->_rootExpander);
            }
        }
        if (($_expanderDefinition[self::EXPANDER_FULL] ?? false) && ($mc = $_model::getConfiguration())) {
            foreach (array_merge($mc->recordFields ?? [], $mc->recordsFields ?? []) as $prop => $conf) {
                if (! ($conf[TMCC::CONFIG][TMCC::DEPENDENT_RECORDS] ?? false)) {
                    continue;
                }
                $this->_subExpanders[] = Tinebase_Record_Expander_Factory::create($_model, [self::EXPANDER_FULL => true], $prop,
                    $this->_rootExpander);
            }
            if ($mc->{TMCC::HAS_RELATIONS}) {
                $this->_subExpanders[] = Tinebase_Record_Expander_Factory::create($_model, [], TMCC::FLD_RELATIONS, $this->_rootExpander);
            }
            if ($mc->{TMCC::HAS_TAGS}) {
                $this->_subExpanders[] = Tinebase_Record_Expander_Factory::create($_model, [], TMCC::FLD_TAGS, $this->_rootExpander);
            }
            if ($mc->{TMCC::HAS_ALARMS}) {
                $this->_subExpanders[] = Tinebase_Record_Expander_Factory::create($_model, [], TMCC::FLD_ALARMS, $this->_rootExpander);
            }
            if ($mc->{TMCC::HAS_ATTACHMENTS}) {
                $this->_subExpanders[] = Tinebase_Record_Expander_Factory::create($_model, [], TMCC::FLD_ATTACHMENTS, $this->_rootExpander);
            }
            if ($mc->{TMCC::HAS_NOTES}) {
                $this->_subExpanders[] = Tinebase_Record_Expander_Factory::create($_model, [], TMCC::FLD_NOTES, $this->_rootExpander);
            }
        }
    }

    public function expand(Tinebase_Record_RecordSet $_records)
    {
        /** @var Tinebase_Record_Expander_Sub $expander */
        foreach ($this->_subExpanders as $expander) {
            /** @noinspection Annotator */
            $expander->_lookForDataToFetch($_records);
        }
    }

    protected abstract function _lookForDataToFetch(Tinebase_Record_RecordSet $_records);
    protected abstract function _setData(Tinebase_Record_RecordSet $_data);
    protected abstract function _registerDataToFetch(Tinebase_Record_Expander_DataRequest $_dataRequest);
}
