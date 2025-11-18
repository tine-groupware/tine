<?php declare(strict_types=1);
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Auth
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2021-2025 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */

use \IPLib\Factory;

/**
 * SecondFactor Auth Facade
 *
 * @package     Tinebase
 * @subpackage  Auth
 */
final class Tinebase_Auth_MFA
{
    /**
     * the singleton pattern
     *
     * @param string $mfaId
     * @return static self
     * @throws Tinebase_Exception_Backend
     */
    public static function getInstance(string $mfaId): self
    {
        if (!isset(self::$_instances[$mfaId])) {
            $mfas = Tinebase_Config::getInstance()->{Tinebase_Config::MFA};
            if (!$mfas->records || ! ($config = $mfas->records->getById($mfaId))) {
                throw new Tinebase_Exception_Backend(self::class . ' with id ' . $mfaId . ' not found');
            }
            if (is_array($config->{Tinebase_Model_MFA_Config::FLD_PROVIDER_CONFIG})) {
                $mfas->records->runConvertToRecord();
            }
            self::$_instances[$mfaId] = new self($config);
        }

        return self::$_instances[$mfaId];
    }

    public static function destroyInstances(): void
    {
        self::$_instances = [];
    }

    public function sendOut(Tinebase_Model_MFA_UserConfig $_userCfg, Tinebase_Model_FullUser $user): bool
    {
        try {
            return $this->_adapter->sendOut($_userCfg, $user);
        } catch (Tinebase_Exception $e) {
            $e->setLogToSentry(false);
            $e->setLogLevelMethod('notice');
            Tinebase_Exception::log($e);
            throw new Tinebase_Exception_SystemGeneric(Tinebase_Translation::getTranslation()
                ->_('MFA send out failed, please try again or check with your system administrator'));
        }
    }

    public function validate($_data, Tinebase_Model_MFA_UserConfig $_userCfg): bool
    {
        try {
            $result = $this->_adapter->validate($_data, $_userCfg);
            if (!$result && Tinebase_Core::isLogLevel(Zend_Log::INFO)) {
                Tinebase_Core::getLogger()
                    ->info(__METHOD__ . '::' . __LINE__ . ' MFA validation failure for MFA ID '
                        . $_userCfg->getId());
            }
            return $result;
        } catch (Tinebase_Exception $e) {
            $e->setLogToSentry(false);
            $e->setLogLevelMethod('notice');
            Tinebase_Exception::log($e);
            throw new Tinebase_Exception_SystemGeneric(Tinebase_Translation::getTranslation()
                ->_('MFA validation failed, please try again or check with your system administrator'));
        }
    }

    public function getConfig(): Tinebase_Model_MFA_Config
    {
        return $this->_config;
    }

    public function getAdapter(): Tinebase_Auth_MFA_AdapterInterface
    {
        return $this->_adapter;
    }

    public static function hasPwdLessProvider(): bool
    {
        $mfas = Tinebase_Config::getInstance()->{Tinebase_Config::MFA} ?: null;
        /** @var Tinebase_Model_MFA_Config $mfa */
        foreach ($mfas?->records ?: [] as $mfa) {
            if ($mfa->{Tinebase_Model_MFA_Config::FLD_ALLOW_PWD_LESS_LOGIN}) {
                return true;
            }
        }
        return false;
    }

    public static function getAccountsMFAUserConfig(string $_userMfaId, Tinebase_Model_FullUser $_account): ?Tinebase_Model_MFA_UserConfig
    {
        if (!$_account->mfa_configs) {
            return null;
        }
        return $_account->mfa_configs->find(Tinebase_Model_MFA_UserConfig::FLD_ID, $_userMfaId);
    }

    public function persistUserConfig(?string $_accountId, Closure $cb): bool
    {
        if ($this->_persistUserConfigDelegator) {
            return ($this->_persistUserConfigDelegator)($cb);
        } else {
            $user = Tinebase_User::getInstance()->getUserById($_accountId, Tinebase_Model_FullUser::class);
            if (!$cb($user)) {
                return false;
            }
            Tinebase_User::getInstance()->updateUserInSqlBackend($user);
        }

        return true;
    }

    public function setPersistUserConfigDelegator(?Closure $fun)
    {
        $this->_persistUserConfigDelegator = $fun;
    }

    /**
     * the constructor
     *
     * don't use the constructor. use the singleton
     */
    private function __construct(Tinebase_Model_MFA_Config $config)
    {
        $this->_adapter = new $config->{Tinebase_Model_MFA_Config::FLD_PROVIDER_CLASS}(
            $config->{Tinebase_Model_MFA_Config::FLD_PROVIDER_CONFIG},
            $config->getId()
        );
        $this->_config = $config;
    }

    public static function checkMFABypass(): bool
    {
        // mfa free netmasks:
        if (($_SERVER['HTTP_X_REAL_IP'] ?? false) &&
            !empty($byPassMasks = Tinebase_Config::getInstance()->{Tinebase_Config::MFA_BYPASS_NETMASKS}) &&
            ($ip = Factory::parseAddressString($_SERVER['HTTP_X_REAL_IP']))
        ) {
            foreach ($byPassMasks as $netmask) {
                if (Factory::parseRangeString($netmask)?->contains($ip)) {
                    // bypassing
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * don't clone. Use the singleton.
     */
    private function __clone() {}

    private Tinebase_Auth_MFA_AdapterInterface $_adapter;

    private Tinebase_Model_MFA_Config $_config;

    /**
     * holds the instances of the singleton
     *
     * @var array<self>
     */
    private static $_instances = [];

    protected $_persistUserConfigDelegator = null;
}
