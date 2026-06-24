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
    /** @use Tinebase_Controller_SingletonTrait<Tinebase_Controller_Instance> */
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

    public function getTrustedMailDomains(): array
    {
        $trustedMailDomains = [];
        $records =  $this->getAll();
        $expander = new Tinebase_Record_Expander(Tinebase_Model_Instance::class, [
            Tinebase_Record_Expander::EXPANDER_PROPERTIES => [
                Tinebase_Model_Instance::FLD_MAIL_DOMAINS   => [],
            ],
        ]);
        $expander->expand($records);
        foreach ($records as $instance) {
            $domains = $instance[Tinebase_Model_Instance::FLD_MAIL_DOMAINS]->sort(Tinebase_Model_InstanceMailDomain::FLD_DOMAIN_NAME)->domain_name;

            if (count($domains) > 0) {
                $pattern = '(' . implode('|', array_map(fn($d) => preg_quote($d, '/'), $domains)) . ')';
                $image = $instance[Tinebase_Model_Instance::FLD_FLAG_ICON_FILE];

                if (!empty($image) && !str_contains($image, 'icon-set')) {
                    try {
                        $image = Tinebase_ImageHelper::getDataUrl($image);
                    } catch (Exception $e){
                        if (Tinebase_Core::isLogLevel(Zend_Log::WARN)) {
                            Tinebase_Core::getLogger()->warn(
                                __METHOD__ . '::' . __LINE__ . ' Failed to load the flag icon from : ' . $image);
                        }
                    }
                }

                $trustedMailDomains[$pattern] = [
                    'id'    => $instance[Tinebase_Model_Instance::FLD_URL],
                    'name' => $instance[Tinebase_Model_Instance::FLD_NAME],
                    'image' => $image,
                ];
            }
        }

        $trustedMailDomainConfig = Felamimail_Config::getInstance()->get(Felamimail_Config::TRUSTED_MAIL_DOMAINS);
        return array_merge($trustedMailDomains, $trustedMailDomainConfig);
    }
}
