<?php declare(strict_types=1);
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2023 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

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
    }

    protected function _checkRight($_action)
    {
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
        $newModels = $updatedRecord->xprops(Tinebase_Model_EvaluationDimension::FLD_MODELS);
        $oldModels = $currentRecord->xprops(Tinebase_Model_EvaluationDimension::FLD_MODELS);

        if (!empty($addModels = array_diff($newModels, $oldModels))) {
            $this->addDimensionToModel($updatedRecord, $addModels);
        }
        if (!empty($delModels = array_diff($oldModels, $newModels))) {
            $this->removeDimensionFromModel($updatedRecord, $delModels);
        }
    }

    protected function _inspectAfterDelete(Tinebase_Record_Interface $record)
    {
        /** @var Tinebase_Model_EvaluationDimension $record */
        parent::_inspectAfterDelete($record);
        if (!empty($models = $record->xprops(Tinebase_Model_EvaluationDimension::FLD_MODELS))) {
            $this->removeDimensionFromModel($record, $models);
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
            Tinebase_CustomField::getInstance()->addCustomField($dimension->getSystemCF($model));
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
                Tinebase_CustomField::getInstance()->deleteCustomField($cfc);
            }
        }
    }
}
