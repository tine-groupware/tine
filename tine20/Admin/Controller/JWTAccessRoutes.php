<?php declare(strict_types=1);
/**
 * JWTAccessRoutes controller
 *
 * @package     Admin
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2021-2024 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

use Firebase\JWT\JWT;
use Firebase\JWT\SignatureInvalidException;
use Tinebase_Model_Filter_Abstract as TMFA;

/**
 * JWTAccessRoutes controller
 *
 * @package     Admin
 * @subpackage  Controller
 */
class Admin_Controller_JWTAccessRoutes extends Tinebase_Controller_Record_Abstract
{
    use Tinebase_Controller_SingletonTrait;

    /**
     * the constructor
     *
     * don't use the constructor. use the singleton
     */
    protected function __construct()
    {
        $this->_applicationName = Admin_Config::APP_NAME;
        $this->_modelName = Admin_Model_JWTAccessRoutes::class;
        $this->_backend = new Tinebase_Backend_Sql([
            Tinebase_Backend_Sql::MODEL_NAME    => Admin_Model_JWTAccessRoutes::class,
            Tinebase_Backend_Sql::TABLE_NAME    => Admin_Model_JWTAccessRoutes::TABLE_NAME,
            Tinebase_Backend_Sql::MODLOG_ACTIVE => true
        ]);

        $this->_purgeRecords = false;
        $this->_doContainerACLChecks = false;
    }

    protected function _addDefaultFilter(Tinebase_Model_Filter_FilterGroup $_filter = null)
    {
        parent::_addDefaultFilter($_filter);
        if (null === $_filter->getFilter(Admin_Model_JWTAccessRoutes::FLD_TTL, _recursive: true)) {
            if (Tinebase_Model_Filter_FilterGroup::CONDITION_AND !== $_filter->getCondition()) {
                $_filter->andWrapItself();
            }
            $_filter->addFilterGroup(Tinebase_Model_Filter_FilterGroup::getFilterForModel($this->_modelName, [
                [TMFA::FIELD => Admin_Model_JWTAccessRoutes::FLD_TTL, TMFA::OPERATOR => TMFA::OP_EQUALS, TMFA::VALUE => null],
                [TMFA::FIELD => Admin_Model_JWTAccessRoutes::FLD_TTL, TMFA::OPERATOR => 'after', TMFA::VALUE => Tinebase_DateTime::now()],
            ], Tinebase_Model_Filter_FilterGroup::CONDITION_OR));
        }
    }

    public function cleanTTL(): bool
    {
        $this->deleteByFilter(Tinebase_Model_Filter_FilterGroup::getFilterForModel($this->_modelName, [
            [TMFA::FIELD => Admin_Model_JWTAccessRoutes::FLD_TTL, TMFA::OPERATOR => 'before', TMFA::VALUE => Tinebase_DateTime::now()],
        ]));

        return true;
    }

    public function getNewJWT(array $jwtAccessRoutesData, int $keyBits = 2048): string
    {
        if (! ($jwtAccessRoutesData[Admin_Model_JWTAccessRoutes::FLD_KEY] ?? false)) {
            $new_key_pair = openssl_pkey_new(array(
                "private_key_bits" => $keyBits,
                "private_key_type" => OPENSSL_KEYTYPE_RSA,
            ));
            openssl_pkey_export($new_key_pair, $private_key_pem);

            $jwtAccessRoutesData[Admin_Model_JWTAccessRoutes::FLD_KEY] = $private_key_pem;
        }


        $jwtAccessRoute = new Admin_Model_JWTAccessRoutes(array_merge([
            Admin_Model_JWTAccessRoutes::FLD_KEYID => Tinebase_Record_Abstract::generateUID(),
            Admin_Model_JWTAccessRoutes::FLD_ISSUER => Tinebase_Record_Abstract::generateUID(),
        ], $jwtAccessRoutesData));

        $token = JWT::encode(
            payload: ['iss' => $jwtAccessRoute->{Admin_Model_JWTAccessRoutes::FLD_ISSUER}],
            key: $jwtAccessRoute->{Admin_Model_JWTAccessRoutes::FLD_KEY},
            alg: 'RS512',
            keyId: $jwtAccessRoute->{Admin_Model_JWTAccessRoutes::FLD_KEYID}
        );

        Admin_Controller_JWTAccessRoutes::getInstance()->create($jwtAccessRoute);

        return $token;
    }

    public static function doRouteAuth($route, $token)
    {
        $tks = \explode('.', $token);
        if (\count($tks) != 3) {
            throw new UnexpectedValueException('Wrong number of segments');
        }
        list($headb64, $bodyb64) = $tks;
        if (null === ($header = JWT::jsonDecode(JWT::urlsafeB64Decode($headb64)))) {
            throw new UnexpectedValueException('Invalid header encoding');
        }
        if (null === $payload = JWT::jsonDecode(JWT::urlsafeB64Decode($bodyb64))) {
            throw new UnexpectedValueException('Invalid claims encoding');
        }
        if (!isset($payload->iss)) {
            throw new UnexpectedValueException('Payload is missing iss');
        }

        $filter = [
            ['field' => Admin_Model_JWTAccessRoutes::FLD_ISSUER, 'operator' => 'equals', 'value' => $payload->iss]
        ];
        if (isset($header->kid)) {
            $filter[] = [
                'field' => Admin_Model_JWTAccessRoutes::FLD_KEYID, 'operator' => 'equals', 'value' => $header->kid
            ];
        }
        $filter = Tinebase_Model_Filter_FilterGroup::getFilterForModel(Admin_Model_JWTAccessRoutes::class, $filter);

        /** @var Admin_Model_JWTAccessRoutes $jwtRoutes */
        foreach (static::getInstance()->search($filter) as $jwtRoutes) {
            if (in_array($route, $jwtRoutes->{Admin_Model_JWTAccessRoutes::FLD_ROUTES})) {

                $tks = \explode('.', $token);
                if (\count($tks) !== 3) {
                    throw new UnexpectedValueException('Wrong number of segments');
                }
                $headerRaw = JWT::urlsafeB64Decode($tks[0]);
                if (null === ($header = JWT::jsonDecode($headerRaw))) {
                    throw new UnexpectedValueException('Invalid header encoding');
                }
                if (empty($header->alg)) {
                    throw new UnexpectedValueException('Empty algorithm');
                }
                // just check the JWT is valid, then set user and be done
                try {
                    JWT::decode($token, new \Firebase\JWT\Key($jwtRoutes->{Admin_Model_JWTAccessRoutes::FLD_KEY}, $header->alg));
                } catch (SignatureInvalidException $e) {
                    // only if we could not apply this key to check the signature it makes sense to try the next one
                    // all other exceptions are not recoverable
                    continue;
                }
                Tinebase_Core::setUser(Tinebase_User::getInstance()->getFullUserById(
                    $jwtRoutes->{Admin_Model_JWTAccessRoutes::FLD_ACCOUNTID}));
                return;
            }
        }

        // no configured jwt secret found for this route
        throw new Tinebase_Exception_AccessDenied('jwt access denied to route ' . $route);
    }
}
