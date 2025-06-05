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
    public const EXPANDER_PROPERTIES = 'properties';
    public const EXPANDER_PROPERTY_CLASSES = 'propertyClasses';
    public const EXPANDER_FULL = 'full';
    public const EXPANDER_REPLACE_GET_TITLE = 'replaceGetTitle';
    public const EXPANDER_USE_FILTER = 'userFilter';
    public const EXPANDER_USE_JSON_EXPANDER = 'useJsonExpander';
    public const GET_DELETED = 'getDeleted';

    public const PROPERTY_CLASS_USER = 'user';
    public const PROPERTY_CLASS_GRANTS = 'grants';
    public const PROPERTY_CLASS_ACCOUNT_GRANTS = 'account_grants';

    public const DATA_FETCH_PRIO_DEPENDENTRECORD = 100;
    public const DATA_FETCH_PRIO_CONTAINER = 950;
    public const DATA_FETCH_PRIO_ACCOUNT_GRANTS = 951; // after container
    public const DATA_FETCH_PRIO_USER = 1000;
    public const DATA_FETCH_PRIO_RELATION = 800;
    public const DATA_FETCH_PRIO_AFTER_RELATION = 801;
    public const DATA_FETCH_PRIO_NOTES = 900;
    protected $_subExpanders = [];

    /**
     * @var Tinebase_Record_Expander
     */
    protected $_rootExpander;

    protected ?array $_definitionFilter = null;

    public function __construct(protected $_model, $_expanderDefinition, Tinebase_Record_Expander $_rootExpander)
    {
        $this->_rootExpander = $_rootExpander;
        if ($_expanderDefinition[self::EXPANDER_USE_JSON_EXPANDER] ?? false) {
            $_expanderDefinition = array_merge($this->_model::getConfiguration()->jsonExpander, $_expanderDefinition);
        }
        if (isset($_expanderDefinition[self::EXPANDER_PROPERTIES])) {
            foreach ($_expanderDefinition[self::EXPANDER_PROPERTIES] as $prop => $definition) {
                try {
                    $this->_subExpanders[] = Tinebase_Record_Expander_Factory::create($this->_model, $definition, $prop,
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
                $this->_subExpanders[] = Tinebase_Record_Expander_Factory::createPropClass($this->_model, $definition,
                    $propClass, $this->_rootExpander);
            }
        }
        if (($_expanderDefinition[self::EXPANDER_FULL] ?? false) && ($mc = $this->_model::getConfiguration())) {
            foreach (array_merge($mc->recordFields ?? [], $mc->recordsFields ?? []) as $prop => $conf) {
                if (! ($conf[TMCC::CONFIG][TMCC::DEPENDENT_RECORDS] ?? false)) {
                    continue;
                }
                $this->_subExpanders[] = Tinebase_Record_Expander_Factory::create($this->_model, [self::EXPANDER_FULL => true], $prop,
                    $this->_rootExpander);
            }
            if ($mc->{TMCC::HAS_RELATIONS}) {
                $this->_subExpanders[] = Tinebase_Record_Expander_Factory::create($this->_model, [], TMCC::FLD_RELATIONS, $this->_rootExpander);
            }
            if ($mc->{TMCC::HAS_TAGS}) {
                $this->_subExpanders[] = Tinebase_Record_Expander_Factory::create($this->_model, [], TMCC::FLD_TAGS, $this->_rootExpander);
            }
            if ($mc->{TMCC::HAS_ALARMS}) {
                $this->_subExpanders[] = Tinebase_Record_Expander_Factory::create($this->_model, [], TMCC::FLD_ALARMS, $this->_rootExpander);
            }
            if ($mc->{TMCC::HAS_ATTACHMENTS}) {
                $this->_subExpanders[] = Tinebase_Record_Expander_Factory::create($this->_model, [], TMCC::FLD_ATTACHMENTS, $this->_rootExpander);
            }
            if ($mc->{TMCC::HAS_NOTES}) {
                $this->_subExpanders[] = Tinebase_Record_Expander_Factory::create($this->_model, [], TMCC::FLD_NOTES, $this->_rootExpander);
            }
        }
        if (is_array($_expanderDefinition[self::EXPANDER_USE_FILTER] ?? null)) {
            $this->_definitionFilter = $_expanderDefinition[self::EXPANDER_USE_FILTER];
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
