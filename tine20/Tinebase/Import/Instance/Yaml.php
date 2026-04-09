<?php
/**
 * tine Groupware
 *
 * @package     Tinebase
 * @subpackage  Import
 * @license     https://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Ching En Cheng <c.cheng@metaways.de>
 * @copyright   Copyright (c) 2026 Metaways Infosystems GmbH (https://www.metaways.de)
 */

/**
 * instance yaml import class
 *
 * @package     Tinebase
 * @subpackage  Import
 */
class Tinebase_Import_Instance_Yaml extends Tinebase_Import_Abstract
{
    protected function _getRawData(&$_resource)
    {
        return [];
    }


    /**
     * import the data
     *
     * @param $_filename
     * @param array $_clientRecordData
     * @return array with import data (imported records, failures, duplicates and totalcount)
     * @throws Throwable
     * @throws Tinebase_Exception
     * @throws Tinebase_Exception_InvalidArgument
     */
    public function importFile($_filename, $_clientRecordData = array()): array
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
            . ' Starting import of ' . ((! empty($this->_options['model'])) ? $this->_options['model'] . 's' : ' records'));

        if (! extension_loaded('yaml')) {
            throw new Tinebase_Exception('yaml extension required');
        }

        $this->_initImportResult();

        $data = yaml_parse_file($_filename);
        $existingInstances = Tinebase_Controller_Instance::getInstance()->getAll();

        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . ' ' . __LINE__ . ' '
            . ' Found ' . count($existingInstances) . ' existing instances');

        foreach ($data['customers'] as $instanceName => $config) {
            $existingInstance = $existingInstances->find('name', $instanceName);
            try {
                $domains = array_filter(
                    array_merge(
                        [$config['smtp']['primarydomain']],
                        explode(',', $config['smtp']['secondarydomains'])
                    )
                );
                $domainRecords = array_map(fn($domain) => [
                    Tinebase_Model_InstanceMailDomain::FLD_DOMAIN_NAME => $domain,
                ], $domains);

                $instance = new Tinebase_Model_Instance([
                    Tinebase_Model_Instance::FLD_NAME  => $instanceName,
                    Tinebase_Model_Instance::FLD_URL  => $config['fqdn'],
                    Tinebase_Model_Instance::FLD_MAIL_DOMAINS  => $domainRecords,
                ]);
                if (!$existingInstance) {
                    $instance = Tinebase_Controller_Instance::getInstance()->create($instance, TRUE);
                    $this->_importResult['totalcount'] += 1;
                    $this->_importResult['results']->addRecord($instance);
                } else {
                    $instance->id = $existingInstance->getId();

                    /** @var Tinebase_Model_Diff $diff */
                    $diff = $existingInstance->diff($instance);

                    if (!$diff->isEmpty()) {
                        $instance = Tinebase_Controller_Instance::getInstance()->update($instance, FALSE);
                        $this->_importResult['updatecount'] += 1;
                    }
                    $this->_importResult['results']->addRecord($instance);
                }
            } catch (Exception $e) {
                if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) Tinebase_Core::getLogger()->notice(__METHOD__ . ' ' . __LINE__
                    . ' Import failed for Instance ' . $instanceName);
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . ' ' . __LINE__
                    . ' ' . print_r($config, TRUE));
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . ' ' . __LINE__
                    . ' ' . $e);
                $this->_importResult['failcount'] += 1;
            }
        }

        return $this->_importResult;
    }
}
