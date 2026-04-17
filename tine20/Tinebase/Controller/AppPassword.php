<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2023-2026 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

use Firebase\JWT\JWT;
use Firebase\JWT\SignatureInvalidException;
use Tinebase_Model_Filter_Abstract as TMFA;

/**
 * controller for AppPassword
 *
 * @package     Tinebase
 * @subpackage  Controller
 *
 * @extends Tinebase_Controller_Record_Abstract<Tinebase_Model_AppPassword>
 */
class Tinebase_Controller_AppPassword extends Tinebase_Controller_Record_Abstract
{
    /** @use Tinebase_Controller_SingletonTrait<Tinebase_Controller_AppPassword> */
    use Tinebase_Controller_SingletonTrait;

    public const PWD_LENGTH = 26;
    public const PWD_MIN_LENGTH = 10;

    /**
     * the constructor
     *
     * don't use the constructor. use the singleton
     */
    protected function __construct()
    {
        $this->_applicationName = Tinebase_Config::APP_NAME;
        $this->_modelName = Tinebase_Model_AppPassword::class;
        $this->_backend = new Tinebase_Backend_Sql([
            Tinebase_Backend_Sql::TABLE_NAME        => Tinebase_Model_AppPassword::TABLE_NAME,
            Tinebase_Backend_Sql::MODEL_NAME        => Tinebase_Model_AppPassword::class,
            Tinebase_Backend_Sql::MODLOG_ACTIVE     => false,
        ]);
        $this->_purgeRecords = true;
        $this->_omitModLog = true;
    }

    protected function _checkGrant($_record, $_action, $_throw = TRUE, $_errorMessage = 'No Permission.',
        /** @noinspection PhpUnusedParameterInspection */ $_oldRecord = NULL)
    {
        if (!$this->_doContainerACLChecks) {
            return true;
        }
        if (Tinebase_Core::getUser()->hasRight(Tinebase_Config::APP_NAME, Tinebase_Acl_Rights::ADMIN)) {
            return true;
        }

        if ($_record->getIdFromProperty(Tinebase_Model_AppPassword::FLD_ACCOUNT_ID) !== Tinebase_Core::getUser()->getId()) {
            if ($_throw) {
                new Tinebase_Exception_AccessDenied($_errorMessage);
            }
            return false;
        }

        return true;
    }

    /**
     * Removes containers where current user has no access to
     *
     * @param Tinebase_Model_Filter_FilterGroup $_filter
     * @param string $_action get|update
     */
    public function checkFilterACL(Tinebase_Model_Filter_FilterGroup $_filter, $_action = self::ACTION_GET)
    {
        if (!$this->_doContainerACLChecks || Tinebase_Core::getUser()->hasRight(Tinebase_Config::APP_NAME, Tinebase_Acl_Rights::ADMIN)) {
            if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__
                . ' Container ACL disabled for ' . $_filter->getModelName() . '.');
            return;
        }

        if ($_filter->getCondition() !== Tinebase_Model_Filter_FilterGroup::CONDITION_AND) {
            $_filter->andWrapItself();
        }

        $_filter->addFilter($_filter->createFilter(
            Tinebase_Model_AppPassword::FLD_ACCOUNT_ID,
            Tinebase_Model_Filter_Abstract::OP_EQUALS,
            Tinebase_Core::getUser()->getId()
        ));
    }

    protected function _inspectBeforeCreate(Tinebase_Record_Interface $_record)
    {
        parent::_inspectBeforeCreate($_record);
        $this->_inspectPwd($_record);
    }

    protected function _inspectBeforeUpdate($_record, $_oldRecord)
    {
        parent::_inspectBeforeUpdate($_record, $_oldRecord);
        $this->_inspectPwd($_record);
    }

    protected function _inspectPwd(Tinebase_Model_AppPassword $_record): void
    {
        if (null === ($appPwd = $_record->{Tinebase_Model_AppPassword::FLD_AUTH_TOKEN}) || preg_match('/^sha(1|3-512)_/', $appPwd)) {
            return;
        }
        $appPwd = (string)$appPwd;
        if (strlen($appPwd) < self::PWD_MIN_LENGTH) {
            throw new Tinebase_Exception_UnexpectedValue('Password too short.');
        }
        $_record->{Tinebase_Model_AppPassword::FLD_AUTH_TOKEN} = 'sha3-512_' . hash('sha3-512', $appPwd);
    }

    public function getByToken(string $userId, string $token): ?Tinebase_Model_AppPassword
    {
        $oldValue = $this->doContainerACLChecks(false);
        try {
            return $this->search(
                Tinebase_Model_Filter_FilterGroup::getFilterForModel(Tinebase_Model_AppPassword::class, [
                    [TMFA::FIELD => Tinebase_Model_AppPassword::FLD_ACCOUNT_ID, TMFA::OPERATOR => TMFA::OP_EQUALS, TMFA::VALUE => $userId],
                    [TMFA::FIELD => Tinebase_Model_AppPassword::FLD_AUTH_TOKEN, TMFA::OPERATOR => 'in', TMFA::VALUE => [
                        'sha1_' . sha1($token),
                        'sha3-512_' . hash('sha3-512', $token),
                    ]],
                    [TMFA::FIELD => Tinebase_Model_AppPassword::FLD_VALID_UNTIL, TMFA::OPERATOR => 'after', TMFA::VALUE => Tinebase_DateTime::now()],
                ])
            )->getFirstRecord();
        } finally {
            $this->doContainerACLChecks($oldValue);
        }
    }

    public function getByJwt(string $token): ?Tinebase_Model_AppPassword
    {
        $tks = explode('.', $token);
        if (count($tks) != 3) {
            return null;
        }
        [$headb64] = $tks;
        try {
            if (null === ($header = JWT::jsonDecode(JWT::urlsafeB64Decode($headb64)))) {
                return null;
            }
        } catch (Exception) {
            return null;
        }
        if (!isset($header->kid) || !isset($header->alg)) {
            return null;
        }

        $oldValue = $this->doContainerACLChecks(false);
        try {
            foreach ($this->search(
                    Tinebase_Model_Filter_FilterGroup::getFilterForModel(Tinebase_Model_AppPassword::class, [
                        [TMFA::FIELD => Tinebase_Model_AppPassword::FLD_JWT_KEY_ID, TMFA::OPERATOR => TMFA::OP_EQUALS, TMFA::VALUE => $header->kid],
                        [TMFA::FIELD => Tinebase_Model_AppPassword::FLD_VALID_UNTIL, TMFA::OPERATOR => 'after', TMFA::VALUE => Tinebase_DateTime::now()],
                    ])) as $appPwd) {
                try {
                    $jwt = JWT::decode($token, new \Firebase\JWT\Key($appPwd->getPasswordFromProperty(Tinebase_Model_AppPassword::FLD_JWT_PRIVAT_KEY), $header->alg));
                } catch (Exception) {
                    continue;
                }
                if ($jwt->account_id !== $appPwd->{Tinebase_Model_AppPassword::FLD_ACCOUNT_ID}
                    /* legacy, remove: */ && (!isset($jwt->iss) ||  40 !== strlen($jwt->iss))) {
                    continue;
                }
                return $appPwd;
            }
        } finally {
            $this->doContainerACLChecks($oldValue);
        }

        return null;
    }

    public function generateJwt(array $data): Tinebase_Model_AppPassword
    {
        return $this->create(new Tinebase_Model_AppPassword(array_merge([
            Tinebase_Model_AppPassword::FLD_ACCOUNT_ID => Tinebase_Core::getUser()->getId(),
            Tinebase_Model_AppPassword::FLD_VALID_UNTIL => Tinebase_DateTime::now()->addYear(10),
            Tinebase_Model_AppPassword::FLD_JWT_KEY_ID => Tinebase_Record_Abstract::generateUID(),
        ], $data)));
    }

    public function getNewJwtToken(array $data): string
    {
        $appPwd = $this->generateJwt($data);

        return JWT::encode([
            'account_id' => $appPwd->{Tinebase_Model_AppPassword::FLD_ACCOUNT_ID},
        ], $data[Tinebase_Model_AppPassword::FLD_JWT_PRIVAT_KEY], 'HS512', $appPwd->{Tinebase_Model_AppPassword::FLD_JWT_KEY_ID});
    }
}
