<?php

/**
 * Tine 2.0
 *
 * @package     OnlyOfficeIntegrator
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2022 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 *
 * this is 2023.11 (ONLY!)
 */

use Tinebase_ModelConfiguration_Const as TMCC;

class OnlyOfficeIntegrator_Setup_Update_16 extends Setup_Update_Abstract
{
    const RELEASE016_UPDATE000 = __CLASS__ . '::update000';
    const RELEASE016_UPDATE001 = __CLASS__ . '::update001';

    static protected $_allUpdates = [
        self::PRIO_NORMAL_APP_UPDATE        => [
            self::RELEASE016_UPDATE000          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update000',
            ],
            self::RELEASE016_UPDATE001          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update001',
            ],
        ],
    ];

    public function update000()
    {
        $this->addApplicationUpdate('OnlyOfficeIntegrator', '16.0', self::RELEASE016_UPDATE000);
    }

    public function update001()
    {
        Tinebase_TransactionManager::getInstance()->rollBack();

        $cf = Tinebase_CustomField::getInstance()->getCustomFieldByNameAndApplication(
            Tinebase_Config::APP_NAME, OnlyOfficeIntegrator_Config::FM_NODE_EDITING_CFNAME,
            Tinebase_Model_Tree_Node::class, true);
        $cf->xprops('definition')[Tinebase_Model_CustomField_Config::DEF_FIELD][TMCC::OMIT_MOD_LOG] = true;
        Tinebase_CustomField::getInstance()->updateCustomField($cf);

        $cf = Tinebase_CustomField::getInstance()->getCustomFieldByNameAndApplication(
            Tinebase_Config::APP_NAME, OnlyOfficeIntegrator_Config::FM_NODE_EDITORS_CFNAME,
            Tinebase_Model_Tree_Node::class, true);
        $cf->xprops('definition')[Tinebase_Model_CustomField_Config::DEF_FIELD][TMCC::OMIT_MOD_LOG] = true;
        Tinebase_CustomField::getInstance()->updateCustomField($cf);

        $this->addApplicationUpdate('OnlyOfficeIntegrator', '16.1', self::RELEASE016_UPDATE001);
    }
}
