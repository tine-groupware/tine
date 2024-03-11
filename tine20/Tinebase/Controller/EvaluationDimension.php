<?php declare(strict_types=1);
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2023-2024 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

use Tinebase_Model_Filter_Abstract as TMFA;
use Tinebase_ModelConfiguration_Const as TMCC;

/**
 * controller for EvaluationDimension
 *
 * @package     Tinebase
 * @subpackage  Controller
 */
class Tinebase_Controller_EvaluationDimension extends Tinebase_Controller_Record_Abstract
{
    use Tinebase_Controller_SingletonTrait;

    /**
     * the constructor
     *
     * don't use the constructor. use the singleton
     */
    protected function __construct()
    {
        $this->_applicationName = Tinebase_Config::APP_NAME;
        $this->_modelName = Tinebase_Model_EvaluationDimension::class;
        $this->_backend = new Tinebase_Backend_Sql([
            Tinebase_Backend_Sql::TABLE_NAME        => Tinebase_Model_EvaluationDimension::TABLE_NAME,
            Tinebase_Backend_Sql::MODEL_NAME        => Tinebase_Model_EvaluationDimension::class,
            Tinebase_Backend_Sql::MODLOG_ACTIVE     => true,
        ]);
        $this->_purgeRecords = false;
        $this->_doContainerACLChecks = false;
        $this->_duplicateCheckFields = [Tinebase_Model_EvaluationDimension::FLD_NAME];
    }

    protected function _checkRight($_action)
    {
        if (! $this->_doRightChecks) {
            return;
        }

        parent::_checkRight($_action);

        if (self::ACTION_GET === $_action) {
            return;
        }

        if (!Tinebase_Core::getUser()
                ->hasRight(Tinebase_Config::APP_NAME, Tinebase_Acl_Rights::MANAGE_EVALUATION_DIMENSIONS)) {
            throw new Tinebase_Exception_AccessDenied('no right to ' . $_action . ' evaluation dimensions');
        }
    }

    protected function _inspectAfterCreate($_createdRecord, Tinebase_Record_Interface $_record)
    {
        /** @var Tinebase_Model_EvaluationDimension $_createdRecord */
        parent::_inspectAfterCreate($_createdRecord, $_record);

        if (!empty($models = $_createdRecord->xprops(Tinebase_Model_EvaluationDimension::FLD_MODELS))) {
            $this->addDimensionToModel($_createdRecord, $models);
        }
    }

    protected function _inspectBeforeUpdate($_record, $_oldRecord)
    {
        parent::_inspectBeforeUpdate($_record, $_oldRecord);

        if (!empty($_record->xprops(Tinebase_Model_EvaluationDimension::FLD_MODELS)) &&
                $_record->{Tinebase_Model_EvaluationDimension::FLD_NAME} !== $_oldRecord->{Tinebase_Model_EvaluationDimension::FLD_NAME}) {
            throw new Tinebase_Exception_SystemGeneric('renaming is currently only possible if no models are assigned');
        }
    }

    protected function _inspectAfterUpdate($updatedRecord, $record, $currentRecord)
    {
        /** @var Tinebase_Model_EvaluationDimension $updatedRecord */
        parent::_inspectAfterUpdate($updatedRecord, $record, $currentRecord);
        $newModels = array_unique($updatedRecord->xprops(Tinebase_Model_EvaluationDimension::FLD_MODELS));
        $oldModels = array_unique($currentRecord->xprops(Tinebase_Model_EvaluationDimension::FLD_MODELS));

        if (!empty($addModels = array_diff($newModels, $oldModels))) {
            $this->addDimensionToModel($updatedRecord, $addModels);
        }
        if (!empty($delModels = array_diff($oldModels, $newModels))) {
            $this->removeDimensionFromModel($updatedRecord, $delModels);
        }
        if (!empty($updateModels = array_intersect($newModels, $oldModels))) {
            $this->updateDimensionOfModel($updatedRecord, $updateModels);
        }
    }

    protected function _inspectAfterDelete(Tinebase_Record_Interface $record)
    {
        /** @var Tinebase_Model_EvaluationDimension $record */
        parent::_inspectAfterDelete($record);
        if (!empty($models = $record->xprops(Tinebase_Model_EvaluationDimension::FLD_MODELS))) {
            $this->removeDimensionFromModel($record, array_unique($models));
        }
    }

    protected function addDimensionToModel(Tinebase_Model_EvaluationDimension $dimension, array $models): void
    {
        /** @var Tinebase_Record_Interface $model */
        foreach ($models as $model) {
            if (!$model::getConfiguration()->{Tinebase_ModelConfiguration_Const::HAS_SYSTEM_CUSTOM_FIELDS}) {
                throw new Tinebase_Exception_SystemGeneric($model . ' does not support system cf');
            }

            /** @var string $model */
            $cfc = $dimension->getSystemCF($model);
            $fun = function() use ($cfc) {
                Tinebase_CustomField::getInstance()->addCustomField($cfc, [Tinebase_Model_EvaluationDimensionItem::class]);
            };
            if (Tinebase_TransactionManager::getInstance()->hasOpenTransactions()) {
                Tinebase_TransactionManager::getInstance()->registerAfterCommitCallback($fun);
            } else {
                $fun();
            }
        }
    }

    protected function updateDimensionOfModel(Tinebase_Model_EvaluationDimension $dimension, array $models): void
    {
        /** @var string $model */
        foreach ($models as $model) {
            $cfc = $dimension->getSystemCF($model);
            $cfc = Tinebase_CustomField::getInstance()->getCustomFieldByNameAndApplication(
                $cfc->application_id, $cfc->name, $cfc->model, true);
            if (null !== $cfc) {
                $cfc = $dimension->getSystemCF($model)->setId($cfc->getId());
                $fun = function() use ($cfc) {
                    Tinebase_CustomField::getInstance()->updateCustomField($cfc, [Tinebase_Model_EvaluationDimensionItem::class]);
                };
                if (Tinebase_TransactionManager::getInstance()->hasOpenTransactions()) {
                    Tinebase_TransactionManager::getInstance()->registerAfterCommitCallback($fun);
                } else {
                    $fun();
                }
            } else {
                $this->addDimensionToModel($dimension, [$model]);
            }
        }
    }

    protected function removeDimensionFromModel(Tinebase_Model_EvaluationDimension $dimension, array $models): void
    {
        /** @var string $model */
        foreach ($models as $model) {
            $cfc = $dimension->getSystemCF($model);
            $cfc = Tinebase_CustomField::getInstance()->getCustomFieldByNameAndApplication(
                $cfc->application_id, $cfc->name, $cfc->model, true);
            if (null !== $cfc) {
                $fun = function() use ($cfc) {
                    Tinebase_CustomField::getInstance()->deleteCustomField($cfc);
                };
                if (Tinebase_TransactionManager::getInstance()->hasOpenTransactions()) {
                    Tinebase_TransactionManager::getInstance()->registerAfterCommitCallback($fun);
                } else {
                    $fun();
                }
            }
        }
    }

    public static function removeModelsFromDimension(string $dimensionName, array $models): bool
    {
        if (null === ($cc = self::getInstance()->search(Tinebase_Model_Filter_FilterGroup::getFilterForModel(Tinebase_Model_EvaluationDimension::class, [
                [TMFA::FIELD => Tinebase_Model_EvaluationDimension::FLD_NAME, TMFA::OPERATOR => TMFA::OP_EQUALS, TMFA::VALUE => $dimensionName],
            ]))->getFirstRecord())) {
            return false;
        }

        $cc->{Tinebase_Model_EvaluationDimension::FLD_MODELS} =
            array_diff($cc->xprops(Tinebase_Model_EvaluationDimension::FLD_MODELS), $models);
        self::getInstance()->update($cc);
        return true;
    }

    public static function addModelsToDimension(string $dimensionName, array $models): bool
    {
        if (null === ($cc = self::getInstance()->search(Tinebase_Model_Filter_FilterGroup::getFilterForModel(Tinebase_Model_EvaluationDimension::class, [
                    [TMFA::FIELD => Tinebase_Model_EvaluationDimension::FLD_NAME, TMFA::OPERATOR => TMFA::OP_EQUALS, TMFA::VALUE => $dimensionName],
                ]))->getFirstRecord())) {
            return false;
        }

        $cc->{Tinebase_Model_EvaluationDimension::FLD_MODELS} = array_unique(
            array_merge($cc->xprops(Tinebase_Model_EvaluationDimension::FLD_MODELS), $models));
        self::getInstance()->update($cc);
        return true;
    }

    public static function modelConfigHook(array &$_fields, Tinebase_ModelConfiguration $mc): void
    {
        $table = $mc->getTable();
        if (!isset($table[TMCC::INDEXES])) {
            $table[TMCC::INDEXES] = [];
        }
        $assoc = $mc->getAssociations();
        if (!isset($assoc[\Doctrine\ORM\Mapping\ClassMetadataInfo::MANY_TO_ONE])) {
            $assoc[\Doctrine\ORM\Mapping\ClassMetadataInfo::MANY_TO_ONE] = [];
        }
        $jsonExpander = $mc->jsonExpander;
        if (!isset($jsonExpander[Tinebase_Record_Expander::EXPANDER_PROPERTIES])) {
            $jsonExpander[Tinebase_Record_Expander::EXPANDER_PROPERTIES] = [];
        }

        foreach (array_keys($_fields) as $property) {
            if (strpos($property, 'eval_dim_') === 0) {
                if (!array_key_exists($property, $table[TMCC::INDEXES])) {
                    $table[TMCC::INDEXES][$property] = [
                        TMCC::COLUMNS => [$property]
                    ];
                }
                if (!array_key_exists($property, $assoc[\Doctrine\ORM\Mapping\ClassMetadataInfo::MANY_TO_ONE])) {
                    $assoc[\Doctrine\ORM\Mapping\ClassMetadataInfo::MANY_TO_ONE][$property] = [
                        TMCC::TARGET_ENTITY         => Tinebase_Model_EvaluationDimensionItem::class,
                        TMCC::FIELD_NAME            => $property,
                        TMCC::JOIN_COLUMNS          => [[
                            TMCC::NAME                  => $property,
                            TMCC::REFERENCED_COLUMN_NAME=> 'id',
                        ]],
                    ];
                }
                if (!array_key_exists($property, $jsonExpander[Tinebase_Record_Expander::EXPANDER_PROPERTIES])) {
                    $jsonExpander[Tinebase_Record_Expander::EXPANDER_PROPERTIES][$property] = [];
                }
            }
        }

        $mc->setTable($table);
        $mc->setAssociations($assoc);
        $mc->setJsonExpander($jsonExpander);
    }

    public function getByName(string $name): ?Tinebase_Model_EvaluationDimension
    {
        /** @var ?Tinebase_Model_EvaluationDimension $result */
        $result = $this->search(Tinebase_Model_Filter_FilterGroup::getFilterForModel(Tinebase_Model_EvaluationDimension::class, [
            [TMFA::FIELD => Tinebase_Model_EvaluationDimension::FLD_NAME, TMFA::OPERATOR => TMFA::OP_EQUALS, TMFA::VALUE => $name],
        ]), null, new Tinebase_Record_Expander(Tinebase_Model_EvaluationDimension::class, Tinebase_Model_EvaluationDimension::getConfiguration()->jsonExpander))->getFirstRecord();
        return $result;
    }
}
