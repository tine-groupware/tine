<?php declare(strict_types=1);
/**
 * Tine 2.0 - http://www.tine20.org
 *
 * @package     SSO
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2023-2024 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */

use Tinebase_Model_Filter_Abstract as TMFA;

/**
 * SSO Admin JsonApi tests
 *
 * @package     SSO
 * @method Tinebase_Frontend_Json_Generic _getUit()
 */
class SSO_JsonTest extends TestCase
{
    public function __construct($name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
        $this->_uit = new Tinebase_Frontend_Json_Generic(SSO_Config::APP_NAME);
    }

    public function testExternalIdp()
    {
        $savedExtIdp = $this->_getUit()->saveExternalIdp($data = [
            SSO_Model_ExternalIdp::FLD_NAME => 'unittest',
            SSO_Model_ExternalIdp::FLD_CONFIG_CLASS => SSO_Model_ExIdp_OIdConfig::class,
            SSO_Model_ExternalIdp::FLD_CONFIG => [
                SSO_Model_ExIdp_OIdConfig::FLD_PROVIDER_URL => 'provider_url',
                SSO_Model_ExIdp_OIdConfig::FLD_ISSUER => 'issuer',
                SSO_Model_ExIdp_OIdConfig::FLD_CLIENT_ID => 'client_id',
                SSO_Model_ExIdp_OIdConfig::FLD_CLIENT_SECRET => 'secret',
            ],
            SSO_Model_ExternalIdp::FLD_DOMAINS => [
                [SSO_Model_ExIdpDomain::FLD_DOMAIN => 'unittest1'],
                [SSO_Model_ExIdpDomain::FLD_DOMAIN => 'unittest2'],
            ]
        ]);

        $this->assertNull($savedExtIdp[SSO_Model_ExternalIdp::FLD_CONFIG][SSO_Model_ExIdp_OIdConfig::FLD_CLIENT_SECRET]);
        $this->assertNotNull($savedExtIdp[SSO_Model_ExternalIdp::FLD_CONFIG][SSO_Model_ExIdp_OIdConfig::FLD_CLIENT_SECRET_CCID]);
        $this->assertCount(2, $savedExtIdp[SSO_Model_ExternalIdp::FLD_DOMAINS]);

        /** @var SSO_Model_ExternalIdp $extIdp */
        $extIdp = SSO_Controller_ExternalIdp::getInstance()->get($savedExtIdp['id']);
        $this->assertSame($data[SSO_Model_ExternalIdp::FLD_CONFIG][SSO_Model_ExIdp_OIdConfig::FLD_CLIENT_SECRET],
            $extIdp->{SSO_Model_ExternalIdp::FLD_CONFIG}->getClientSecret());

        $this->assertSame($extIdp->getId(), SSO_Controller_ExternalIdp::getInstance()->search(
            Tinebase_Model_Filter_FilterGroup::getFilterForModel(SSO_Model_ExternalIdp::class, [
                [TMFA::FIELD => SSO_Model_ExternalIdp::FLD_DOMAINS, TMFA::OPERATOR => 'definedBy', TMFA::VALUE => [
                    [TMFA::FIELD => SSO_Model_ExIdpDomain::FLD_DOMAIN, TMFA::OPERATOR => TMFA::OP_EQUALS, TMFA::VALUE => 'unittest1'],
                ]],
            ]))->getFirstRecord()->getId());
    }
}