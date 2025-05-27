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

    protected bool $ignoreInspect = false;

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

        $user = Tinebase_Core::getUser();
        if (is_object($user) && ! $user->hasRight(
            Tinebase_Config::APP_NAME, Tinebase_Acl_Rights::MANAGE_EVALUATION_DIMENSIONS)
        ) {
            throw new Tinebase_Exception_AccessDenied('no right to ' . $_action . ' evaluation dimensions');
        }
    }

    protected function _inspectAfterCreate($_createdRecord, Tinebase_Record_Interface $_record)
    {
        /** @var Tinebase_Model_EvaluationDimension $_createdRecord */
        parent::_inspectAfterCreate($_createdRecord, $_record);

        if ($this->ignoreInspect) {
            return;
        }

        if (!empty($models = $_createdRecord->xprops(Tinebase_Model_EvaluationDimension::FLD_MODELS))) {
            $this->updateDimensionOfModel($_createdRecord, $models);
        }
    }

    protected function _inspectBeforeUpdate($_record, $_oldRecord)
    {
        parent::_inspectBeforeUpdate($_record, $_oldRecord);

        if (!empty($_record->xprops(Tinebase_Model_EvaluationDimension::FLD_MODELS)) &&
                $_record->{Tinebase_Model_EvaluationDimension::FLD_NAME} !== $_oldRecord->{Tinebase_Model_EvaluationDimension::FLD_NAME}) {
            $t = Tinebase_Translation::getTranslation();
            throw new Tinebase_Exception_SystemGeneric($t->_('renaming is currently only possible if no models are assigned'));
        }
    }

    protected function _inspectAfterUpdate($updatedRecord, $record, $currentRecord)
    {
        /** @var Tinebase_Model_EvaluationDimension $updatedRecord */
        parent::_inspectAfterUpdate($updatedRecord, $record, $currentRecord);

        if ($this->ignoreInspect) {
            return;
        }

        $newModels = array_unique($updatedRecord->xprops(Tinebase_Model_EvaluationDimension::FLD_MODELS));
        $oldModels = array_unique($currentRecord->xprops(Tinebase_Model_EvaluationDimension::FLD_MODELS));

        if (!empty($addModels = array_diff($newModels, $oldModels))) {
            $this->updateDimensionOfModel($updatedRecord, $addModels);
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

        if ($this->ignoreInspect) {
            return;
        }
        
        if (!empty($models = $record->xprops(Tinebase_Model_EvaluationDimension::FLD_MODELS))) {
            $this->removeDimensionFromModel($record, array_unique($models));
        }
    }

    protected function updateDimensionOfModel(Tinebase_Model_EvaluationDimension $dimension, array $models): void
    {
        /** @var string $model */
        foreach ($models as $model) {
            $cfc = $dimension->getSystemCF($model);
            $existing = Tinebase_CustomField::getInstance()->getCustomFieldByNameAndApplication(
                $cfc->application_id, $cfc->name, $cfc->model, true);
            if (null !== $existing) {
                $cfc->setId($existing->getId());
                $fun = function () use ($cfc) {
                    Tinebase_CustomField::getInstance()->updateCustomField($cfc, [Tinebase_Model_EvaluationDimensionItem::class]);
                };
            } else {
                $fun = function() use ($cfc) {
                    Tinebase_CustomField::getInstance()->addCustomField($cfc, [Tinebase_Model_EvaluationDimensionItem::class]);
                };
            }
            if (Tinebase_TransactionManager::getInstance()->hasOpenTransactions()) {
                Tinebase_TransactionManager::getInstance()->registerAfterCommitCallback($fun);
            } else {
                $fun();
            }
        }

        $fun = function() {
            Setup_Controller::getInstance()->clearCache(false);
        };
        if (Tinebase_TransactionManager::getInstance()->hasOpenTransactions()) {
            Tinebase_TransactionManager::getInstance()->registerAfterCommitCallback($fun);
        } else {
            $fun();
        }
    }

    protected function removeDimensionFromModel(Tinebase_Model_EvaluationDimension $dimension, array $models): void
    {
        /** @var string $model */
        foreach ($models as $model) {
            try {
                $cfc = $dimension->getSystemCF($model);
            } catch (Tinebase_Exception_NotFound) {
                // in case the application is already deinstalled, we can ignore that, the system cf gets removed by cleanup
                continue;
            }
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

        $fun = function() {
            Setup_Controller::getInstance()->clearCache(false);
        };
        if (Tinebase_TransactionManager::getInstance()->hasOpenTransactions()) {
            Tinebase_TransactionManager::getInstance()->registerAfterCommitCallback($fun);
        } else {
            $fun();
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

    /**
     * @param Tinebase_Model_ModificationLog $modification
     */
    public function applyReplicationModificationLog(Tinebase_Model_ModificationLog $modification)
    {
        $this->ignoreInspect = true;
        try {
            Tinebase_Timemachine_ModificationLog::defaultApply($modification, $this);
        } finally {
            $this->ignoreInspect = false;
        }
    }
}
