<?php
/**
 * FileMetadata controller
 *
 * @package     EFile
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2020 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * FileMetadata controller
 *
 * @package     ExampleApplication
 * @subpackage  Controller
 */
class EFile_Controller_FileMetadata extends Tinebase_Controller_Record_Abstract
{
    use Tinebase_Controller_SingletonTrait;

    /**
     * the constructor
     *
     * don't use the constructor. use the singleton
     */
    protected function __construct()
    {
        $this->_applicationName = EFile_Config::APP_NAME;
        $this->_modelName = EFile_Model_FileMetadata::class;
        $this->_backend = new Tinebase_Backend_Sql([
            Tinebase_Backend_Sql::MODEL_NAME    => EFile_Model_FileMetadata::class,
            Tinebase_Backend_Sql::TABLE_NAME    => EFile_Model_FileMetadata::TABLE_NAME,
            Tinebase_Backend_Sql::MODLOG_ACTIVE => true
        ]);

        $this->_purgeRecords = false;
        $this->_doContainerACLChecks = false;
    }

    protected function _inspectBeforeCreate(Tinebase_Record_Interface $_record)
    {
        parent::_inspectBeforeCreate($_record);

        if (EFile_Model_EFileTierType::TIER_TYPE_FILE !== Tinebase_FileSystem::getInstance()
                ->get($_record->{EFile_Model_FileMetadata::FLD_NODE_ID})->{EFile_Config::TREE_NODE_FLD_TIER_TYPE}) {
            return;
        }

        if (!$_record->{EFile_Model_FileMetadata::FLD_DURATION_START}) {
            $_record->{EFile_Model_FileMetadata::FLD_DURATION_START} = Tinebase_DateTime::today(Tinebase_Core::getUserTimezone());
        }
        if (!$_record->{EFile_Model_FileMetadata::FLD_COMMISSIONED_OFFICE}) {
            if (null === ($contact = Addressbook_Config::getInstallationRepresentative())) {
                $_record->{EFile_Model_FileMetadata::FLD_COMMISSIONED_OFFICE} =
                    Tinebase_Core::getUrl(Tinebase_Core::GET_URL_HOST) ?: 'tine20';
            } else {
                $_record->{EFile_Model_FileMetadata::FLD_COMMISSIONED_OFFICE} = $contact->n_fileas;
            }
        }
    }

    protected function _inspectBeforeUpdate($_record, $_oldRecord)
    {
        parent::_inspectBeforeUpdate($_record, $_oldRecord);

        if (EFile_Model_EFileTierType::TIER_TYPE_FILE !== Tinebase_FileSystem::getInstance()
                ->get($_record->{EFile_Model_FileMetadata::FLD_NODE_ID})->{EFile_Config::TREE_NODE_FLD_TIER_TYPE}) {
            return;
        }

        if (!$_record->{EFile_Model_FileMetadata::FLD_DURATION_START}) {
            $_record->{EFile_Model_FileMetadata::FLD_DURATION_START} =
                $_oldRecord->{EFile_Model_FileMetadata::FLD_DURATION_START};
        }
    }
}
