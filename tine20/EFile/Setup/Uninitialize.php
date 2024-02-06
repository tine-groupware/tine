<?php
/**
 * Tine 2.0
 *
 * @package     EFile
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2020 Metaways Infosystems GmbH (http://www.metaways.de)
 */

use Tinebase_ModelConfiguration_Const as TMCC;

/**
 * class for EFile uninitialization
 *
 * @package     Setup
 */
class EFile_Setup_Uninitialize extends Setup_Uninitialize
{
    /**
     * uninit system customfields
     */
    protected function _uninitializeCORSystemCustomField()
    {
        if (Tinebase_Core::isReplica()) {
            return;
        }

        $appId = Tinebase_Core::getTinebaseId();
        $fmAppId = Tinebase_Application::getInstance()->getApplicationByName(Filemanager_Config::APP_NAME)->getId();

        try {
            $customfield = Tinebase_CustomField::getInstance()->getCustomFieldByNameAndApplication($appId,
                EFile_Config::TREE_NODE_FLD_TIER_TYPE, Tinebase_Model_Tree_Node::class, true);
            if ($customfield) {
                Tinebase_CustomField::getInstance()->deleteCustomField($customfield);
            }
        } catch (Tinebase_Exception_NotFound $tenf) {
        } catch (Throwable $t) {
            // problem!
            Tinebase_Exception::log($t);
        }
        try {
            $customfield = Tinebase_CustomField::getInstance()->getCustomFieldByNameAndApplication($fmAppId,
                EFile_Config::TREE_NODE_FLD_TIER_TYPE, Filemanager_Model_Node::class, true);
            if ($customfield) {
                Tinebase_CustomField::getInstance()->deleteCustomField($customfield);
            }
        } catch (Tinebase_Exception_NotFound $tenf) {
        } catch (Throwable $t) {
            // problem!
            Tinebase_Exception::log($t);
        }

        try {
            $customfield = Tinebase_CustomField::getInstance()->getCustomFieldByNameAndApplication($appId,
                EFile_Config::TREE_NODE_FLD_TIER_TOKEN, Tinebase_Model_Tree_Node::class, true);
            if ($customfield) {
                Tinebase_CustomField::getInstance()->deleteCustomField($customfield);
            }
        } catch (Tinebase_Exception_NotFound $tenf) {
        } catch (Throwable $t) {
            // problem!
            Tinebase_Exception::log($t);
        }
        try {
            $customfield = Tinebase_CustomField::getInstance()->getCustomFieldByNameAndApplication($fmAppId,
                EFile_Config::TREE_NODE_FLD_TIER_TOKEN, Filemanager_Model_Node::class, true);
            if ($customfield) {
                Tinebase_CustomField::getInstance()->deleteCustomField($customfield);
            }
        } catch (Tinebase_Exception_NotFound $tenf) {
        } catch (Throwable $t) {
            // problem!
            Tinebase_Exception::log($t);
        }

        try {
            $customfield = Tinebase_CustomField::getInstance()->getCustomFieldByNameAndApplication($appId,
                EFile_Config::TREE_NODE_FLD_TIER_REF_NUMBER, Tinebase_Model_Tree_Node::class, true);
            if ($customfield) {
                Tinebase_CustomField::getInstance()->deleteCustomField($customfield);
            }
        } catch (Tinebase_Exception_NotFound $tenf) {
        } catch (Throwable $t) {
            // problem!
            Tinebase_Exception::log($t);
        }
        try {
            $customfield = Tinebase_CustomField::getInstance()->getCustomFieldByNameAndApplication($fmAppId,
                EFile_Config::TREE_NODE_FLD_TIER_REF_NUMBER, Filemanager_Model_Node::class, true);
            if ($customfield) {
                Tinebase_CustomField::getInstance()->deleteCustomField($customfield);
            }
        } catch (Tinebase_Exception_NotFound $tenf) {
        } catch (Throwable $t) {
            // problem!
            Tinebase_Exception::log($t);
        }

        try {
            $customfield = Tinebase_CustomField::getInstance()->getCustomFieldByNameAndApplication($appId,
                EFile_Config::TREE_NODE_FLD_TIER_COUNTER, Tinebase_Model_Tree_Node::class, true);
            if ($customfield) {
                Tinebase_CustomField::getInstance()->deleteCustomField($customfield);
            }
        } catch (Tinebase_Exception_NotFound $tenf) {
        } catch (Throwable $t) {
            // problem!
            Tinebase_Exception::log($t);
        }
        try {
            $customfield = Tinebase_CustomField::getInstance()->getCustomFieldByNameAndApplication($fmAppId,
                EFile_Config::TREE_NODE_FLD_TIER_COUNTER, Filemanager_Model_Node::class, true);
            if ($customfield) {
                Tinebase_CustomField::getInstance()->deleteCustomField($customfield);
            }
        } catch (Tinebase_Exception_NotFound $tenf) {
        } catch (Throwable $t) {
            // problem!
            Tinebase_Exception::log($t);
        }

        try {
            $customfield = Tinebase_CustomField::getInstance()->getCustomFieldByNameAndApplication($appId,
                EFile_Config::TREE_NODE_FLD_FILE_METADATA, Tinebase_Model_Tree_Node::class, true);
            if ($customfield) {
                Tinebase_CustomField::getInstance()->deleteCustomField($customfield);
            }
        } catch (Tinebase_Exception_NotFound $tenf) {
        } catch (Throwable $t) {
            // problem!
            Tinebase_Exception::log($t);
        }
        try {
            $customfield = Tinebase_CustomField::getInstance()->getCustomFieldByNameAndApplication($fmAppId,
                EFile_Config::TREE_NODE_FLD_FILE_METADATA, Filemanager_Model_Node::class, true);
            if ($customfield) {
                Tinebase_CustomField::getInstance()->deleteCustomField($customfield);
            }
        } catch (Tinebase_Exception_NotFound $tenf) {
        } catch (Throwable $t) {
            // problem!
            Tinebase_Exception::log($t);
        }
    }
}
