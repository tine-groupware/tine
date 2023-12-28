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
 * controller for EvaluationDimensionItem
 *
 * @package     Tinebase
 * @subpackage  Controller
 */
class Tinebase_Controller_EvaluationDimensionItem extends Tinebase_Controller_Record_Abstract
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
        $this->_modelName = Tinebase_Model_EvaluationDimensionItem::class;
        $this->_backend = new Tinebase_Backend_Sql([
            Tinebase_Backend_Sql::TABLE_NAME        => Tinebase_Model_EvaluationDimensionItem::TABLE_NAME,
            Tinebase_Backend_Sql::MODEL_NAME        => Tinebase_Model_EvaluationDimensionItem::class,
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
            throw new Tinebase_Exception_AccessDenied('no right to ' . $_action . ' evaluation dimension items');
        }
    }
}
