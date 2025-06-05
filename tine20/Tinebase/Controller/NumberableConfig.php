<?php
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
 * controller for NumberableConfig
 *
 * @package     Tinebase
 * @subpackage  Controller
 */
class Tinebase_Controller_NumberableConfig extends Tinebase_Controller_Record_Abstract
{
    use Tinebase_Controller_SingletonTrait;

    /**
     * the constructor
     *
     * don't use the constructor. use the singleton
     */
    protected function __construct()
    {
        $this->_doContainerACLChecks = false;
        $this->_applicationName = Tinebase_Config::APP_NAME;
        $this->_modelName = Tinebase_Model_NumberableConfig::class;
        $this->_backend = new Tinebase_Backend_Sql([
            Tinebase_Backend_Sql::TABLE_NAME        => Tinebase_Model_NumberableConfig::TABLE_NAME,
            Tinebase_Backend_Sql::MODEL_NAME        => Tinebase_Model_NumberableConfig::class,
            Tinebase_Backend_Sql::MODLOG_ACTIVE     => true,
        ]);
        $this->_purgeRecords = false;
    }

    protected function _checkRight($_action)
    {
        if (! $this->_doRightChecks || self::ACTION_GET === $_action) {
            return;
        }

        parent::_checkRight($_action);

        if (! Tinebase_Core::getUser()->hasRight(
                Tinebase_Application::getInstance()->getApplicationByName(Tinebase_Config::APP_NAME),
                Tinebase_Acl_Rights::MANAGE_NUMBERABLES)) {
            throw new Tinebase_Exception_AccessDenied('no right to ' . $_action . ' ' . Tinebase_Model_NumberableConfig::class);
        }
    }

    /**
     * @param Tinebase_Model_NumberableConfig $_record
     */
    protected function _inspectBeforeCreate(Tinebase_Record_Interface $_record)
    {
        parent::_inspectBeforeCreate($_record);
        $this->_inspectBucketKey($_record);
    }

    /**
     * @param Tinebase_Model_NumberableConfig $_record
     * @param Tinebase_Model_NumberableConfig $_oldRecord
     */
    protected function _inspectBeforeUpdate($_record, $_oldRecord)
    {
        parent::_inspectBeforeUpdate($_record, $_oldRecord);
        $this->_inspectBucketKey($_record, $_oldRecord);
    }

    protected function _inspectBucketKey(Tinebase_Model_NumberableConfig $numCfg, ?Tinebase_Model_NumberableConfig $oldNumCfg = null)
    {
        if (str_ends_with((string) $numCfg->{Tinebase_Model_NumberableConfig::FLD_BUCKET_KEY}, '#' . ($oldNumCfg ?: $numCfg)->{Tinebase_Model_NumberableConfig::FLD_PREFIX})) {
            $numCfg->{Tinebase_Model_NumberableConfig::FLD_BUCKET_KEY} = substr((string) $numCfg->{Tinebase_Model_NumberableConfig::FLD_BUCKET_KEY}, 0,
                strrpos((string) $numCfg->{Tinebase_Model_NumberableConfig::FLD_BUCKET_KEY}, '#' . ($oldNumCfg ?: $numCfg)->{Tinebase_Model_NumberableConfig::FLD_PREFIX}));
        }
        $numCfg->{Tinebase_Model_NumberableConfig::FLD_BUCKET_KEY} =
            $numCfg->{Tinebase_Model_NumberableConfig::FLD_BUCKET_KEY} . '#' . $numCfg->{Tinebase_Model_NumberableConfig::FLD_PREFIX};
    }
}
