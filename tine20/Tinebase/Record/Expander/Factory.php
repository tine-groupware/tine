<?php
/**
 * factory to create expanders
 *
 * @package     Tinebase
 * @subpackage  Record
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2018-2021 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

use Tinebase_ModelConfiguration_Const as MCC;

class Tinebase_Record_Expander_Factory
{
    /**
     * @param string $_model
     * @param array $_definition
     * @param string $_property
     * @param Tinebase_Record_Expander $_rootExpander
     * @return Tinebase_Record_Expander_Property
     * @throws Tinebase_Exception_InvalidArgument
     * @throws Tinebase_Exception_NotImplemented
     */
    public static function create($_model, array $_definition, $_property, Tinebase_Record_Expander $_rootExpander)
    {
        /** @var Tinebase_Record_Abstract $_model */
        if (null === ($mc = $_model::getConfiguration())) {
            throw new Tinebase_Exception_InvalidArgument($_model . ' doesn\'t have a modelconfig');
        }
        if (!$mc->hasField($_property)) {
            throw new Tinebase_Exception_InvalidArgument($_model . ' doesn\'t have property ' . $_property);
        }
        $fieldDef = $mc->getFields()[$_property];
        if (!isset($fieldDef[MCC::TYPE])) {
            throw new Tinebase_Exception_InvalidArgument($_model . '::' . $_property . ' has not type');
        }
        // hrmpf legacy
        if (isset($fieldDef[MCC::CONFIG][MCC::FUNCTION])) {
            return new Tinebase_Record_Expander_VirtualFunction($fieldDef[MCC::CONFIG],
                null, $_property, $_definition, $_rootExpander);
        }
        if (null === ($propModel = $mc->getFieldModel($_property)) ) {
            throw new Tinebase_Exception_NotImplemented($_model . '::' . $_property . ' has a unknown model');
        }

        $prio = null;
        switch ($fieldDef[MCC::TYPE]) {
            case MCC::TYPE_DYNAMIC_RECORD:
                $_definition['fieldDefConfig'] = $fieldDef[MCC::CONFIG];
                return new Tinebase_Record_Expander_DynamicRecordProperty($propModel, $_property, $_definition,
                    $_rootExpander, $prio ?: Tinebase_Record_Expander_Abstract::DATA_FETCH_PRIO_DEPENDENTRECORD);
            /** @noinspection PhpMissingBreakStatementInspection */
            case MCC::TYPE_USER:
                $prio = Tinebase_Record_Expander_Abstract::DATA_FETCH_PRIO_USER;
            /** @noinspection PhpMissingBreakStatementInspection */
            case MCC::TYPE_CONTAINER:
                if (null === $prio) {
                    $prio = Tinebase_Record_Expander_Abstract::DATA_FETCH_PRIO_CONTAINER;
                }
            case MCC::TYPE_RECORD:
                if (isset($fieldDef[MCC::CONFIG][MCC::REF_ID_FIELD])) {
                    $_definition['fieldDefConfig'] = $fieldDef[MCC::CONFIG];
                    return new Tinebase_Record_Expander_RefIdProperty($propModel, $_property, $_definition,
                        $_rootExpander, $prio ?: Tinebase_Record_Expander_Abstract::DATA_FETCH_PRIO_DEPENDENTRECORD, true);
                }
                return new Tinebase_Record_Expander_RecordProperty($propModel, $_property, $_definition, $_rootExpander,
                     $prio ?: Tinebase_Record_Expander_Abstract::DATA_FETCH_PRIO_DEPENDENTRECORD);
            case MCC::TYPE_RECORDS:
                if (isset($fieldDef[MCC::CONFIG][MCC::STORAGE]) && MCC::TYPE_JSON ===
                        $fieldDef[MCC::CONFIG][MCC::STORAGE]) {
                    return new Tinebase_Record_Expander_JsonStorageProperty($propModel, $_property, $_definition,
                        $_rootExpander, $prio ?: Tinebase_Record_Expander_Abstract::DATA_FETCH_PRIO_DEPENDENTRECORD);
                } elseif (isset($fieldDef[MCC::CONFIG][MCC::STORAGE]) && MCC::TYPE_JSON_REFID ===
                        $fieldDef[MCC::CONFIG][MCC::STORAGE]) {
                    return new Tinebase_Record_Expander_JsonRefIdStorageProperty($fieldDef, $propModel, $_property, $_definition,
                        $_rootExpander, $prio ?: Tinebase_Record_Expander_Abstract::DATA_FETCH_PRIO_DEPENDENTRECORD);
                } else {
                    if (isset($fieldDef[MCC::CONFIG][MCC::REF_ID_FIELD])) {
                        $_definition['fieldDefConfig'] = $fieldDef[MCC::CONFIG];
                        return new Tinebase_Record_Expander_RefIdProperty($propModel, $_property, $_definition,
                            $_rootExpander, $prio ?: Tinebase_Record_Expander_Abstract::DATA_FETCH_PRIO_DEPENDENTRECORD,
                            false);
                    }
                    return new Tinebase_Record_Expander_RecordsProperty($propModel, $_property, $_definition,
                        $_rootExpander, $prio ?: Tinebase_Record_Expander_Abstract::DATA_FETCH_PRIO_DEPENDENTRECORD);
                }
            case MCC::TYPE_RELATION:
                return new Tinebase_Record_Expander_Relations($_model, $propModel, $_property, $_definition,
                    $_rootExpander);
            case MCC::TYPE_TAG:
                return new Tinebase_Record_Expander_Tags($propModel, $_property, $_definition, $_rootExpander);
            case MCC::TYPE_NOTE:
                return new Tinebase_Record_Expander_Note($propModel, $_property, $_definition, $_rootExpander);
            case MCC::TYPE_ATTACHMENTS:
                return new Tinebase_Record_Expander_Attachments($propModel, $_property, $_definition, $_rootExpander);
            case MCC::TYPE_VIRTUAL:
                switch ($fieldDef[MCC::CONFIG][MCC::TYPE]) {
                    case MCC::TYPE_RELATIONS:
                    case MCC::TYPE_RELATION:
                        return new Tinebase_Record_Expander_VirtualRelation($fieldDef[MCC::CONFIG][MCC::CONFIG],
                            $propModel, $_property, $_definition, $_rootExpander);
                    case MCC::TYPE_PRE_EXPANDED:
                        return new Tinebase_Record_Expander_VirtualPreExpanded($propModel, $_property, $_definition, $_rootExpander);
                }
        }

        throw new Tinebase_Exception_InvalidArgument($_model . '::' . $_property . ' of type ' . $fieldDef['type'] .
            ' is not supported');
    }

    /**
     * @param string $_model
     * @param array $_definition
     * @param string $_class
     * @param Tinebase_Record_Expander $_rootExpander
     * @return Tinebase_Record_Expander_Sub
     * @throws Tinebase_Exception_InvalidArgument
     */
    public static function createPropClass($_model, array $_definition, $_class,
            Tinebase_Record_Expander $_rootExpander)
    {
        /** @var Tinebase_Record_Abstract $_model */
        if (null === ($mc = $_model::getConfiguration())) {
            throw new Tinebase_Exception_InvalidArgument($_model . ' doesn\'t have a modelconfig');
        }

        switch ($_class) {
            case Tinebase_Record_Expander_Abstract::PROPERTY_CLASS_USER:
                // we pass here exceptionally the model of the parent class, the constructor will resolve that
                // and pass along the model of the properties (tinebase_model_[full]user)
                return new Tinebase_Record_Expander_PropertyClass_User($_model, $_definition, $_rootExpander);

            case Tinebase_Record_Expander_Abstract::PROPERTY_CLASS_GRANTS:
                // we pass here exceptionally the model of the parent class, the constructor will resolve that
                // and pass along the model of the properties (tinebase_model_[full]user)
                return new Tinebase_Record_Expander_PropertyClass_Grants($_model, $_definition, $_rootExpander);

            case Tinebase_Record_Expander_Abstract::PROPERTY_CLASS_ACCOUNT_GRANTS:
                // we pass here exceptionally the model of the parent class, the constructor will resolve that
                // and pass along the model of the properties (tinebase_model_[full]user)
                return new Tinebase_Record_Expander_PropertyClass_AccountGrants($_model, $_definition, $_rootExpander);
        }

        throw new Tinebase_Exception_InvalidArgument($_class . ' is not supported');
    }
}
