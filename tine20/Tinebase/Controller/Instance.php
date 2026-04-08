<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Ching En Cheng <c.cheng@metaways.de>
 * @copyright   Copyright (c) 2026 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * controller for Instance
 *
 * @package     Tinebase
 * @subpackage  Controller
 */
class Tinebase_Controller_Instance extends Tinebase_Controller_Record_Abstract
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
        $this->_modelName = Tinebase_Model_Instance::class;
        $this->_backend = new Tinebase_Backend_Sql([
            Tinebase_Backend_Sql::TABLE_NAME        => Tinebase_Model_Instance::TABLE_NAME,
            Tinebase_Backend_Sql::MODEL_NAME        => Tinebase_Model_Instance::class,
            Tinebase_Backend_Sql::MODLOG_ACTIVE     => true,
        ]);
    }

    protected function _inspectAfterSetRelatedDataCreate($createdRecordWithRelated, $_record)
    {
        parent::_inspectAfterSetRelatedDataCreate($createdRecordWithRelated, $_record);

        $this->_updateTrustedMailDomains($createdRecordWithRelated, $_record);
    }

    protected function _inspectAfterSetRelatedDataUpdate($updatedRecord, $record, $currentRecord)
    {
        parent::_inspectAfterSetRelatedDataUpdate($updatedRecord, $record, $currentRecord);

        $this->_updateTrustedMailDomains($updatedRecord, $record);
    }

    /**
     *
     * @throws Tinebase_Exception_AccessDenied
     */
    protected function _inspectAfterDelete(Tinebase_Record_Interface $record)
    {
        parent::_inspectAfterDelete($record);

        $this->_updateTrustedMailDomains(null, $record);
    }

    protected function _updateTrustedMailDomains($updatedRecord, $record)
    {
        if ($this->_doContainerACLChecks && Tinebase_Core::isReplica()) {
            return;
        }

        $supportedMailServers = Felamimail_Config::getInstance()->get(Felamimail_Config::TRUSTED_MAIL_DOMAINS);

        if ($record) {
            $oldInstanceName = $record[Tinebase_Model_Instance::FLD_NAME];
            foreach ($supportedMailServers as $key => $data) {
                if ($data['id'] === $oldInstanceName) {
                    unset($supportedMailServers[$key]);
                }
            }
        }

        if ($updatedRecord) {
            $domains = $updatedRecord[Tinebase_Model_Instance::FLD_MAIL_DOMAINS]->domain_name;

            if (count($domains) > 0) {
                $pattern = '(' . implode('|', array_map(fn($d) => preg_quote($d, '/'), $domains)) . ')';
                $newInstanceName = $updatedRecord[Tinebase_Model_Instance::FLD_NAME];
                $url = $updatedRecord[Tinebase_Model_Instance::FLD_URL];

                $supportedMailServers[$pattern] = [
                    'id'    => $newInstanceName,
                    'value' => $newInstanceName,
                    'image' => "https://$url/favicon",
                ];
            }
            Felamimail_Config::getInstance()->set(Felamimail_Config::TRUSTED_MAIL_DOMAINS, $supportedMailServers);
        }
    }
}
