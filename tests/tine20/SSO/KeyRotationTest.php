<?php declare(strict_types=1);
/**
 * Tine 2.0 - http://www.tine20.org
 *
 * @package     SSO
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2025 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */

/**
 * SSO key rotation tests
 *
 * @package     SSO
 */
class SSO_KeyRotationTest extends TestCase
{
    protected $_oldOauth2KeyCfg = null;
    protected $_oldSaml2KeyCfg = null;
    protected $_oldKeyRotationEnabled = null;
    protected $_tempKeyDir = null;

    protected function _createTestKey(string $dir, string $name): void
    {
        $keyFile = $dir . '/' . $name . '_key.pem';
        $certFile = $dir . '/' . $name . '_cert.pem';
        $pubFile = $dir . '/' . $name . '_cert.crt';
        $jwkFile = $dir . '/' . $name . '_cert.jwk';

        $cmd = 'openssl req -x509 -newkey rsa:4096 -keyout ' . escapeshellarg($keyFile) . ' -out ' . escapeshellarg($certFile) . ' -days 730 -nodes -subj \'/CN=test\' 2>&1';
        exec($cmd, $output, $returnCode);
        $this->assertSame(0, $returnCode, 'openssl key generation failed: ' . implode("\n", $output));

        $cmd = 'openssl pkey -in ' . escapeshellarg($keyFile) . ' -out ' . escapeshellarg($pubFile) . ' -pubout 2>&1';
        exec($cmd, $output, $returnCode);
        $this->assertSame(0, $returnCode, 'openssl pubkey extraction failed: ' . implode("\n", $output));

        $options = [
            'use' => 'sig',
            'alg' => 'RS256',
            'kid' => Tinebase_Record_Abstract::generateUID(),
        ];
        $keyFactory = new \Strobotti\JWK\KeyFactory();
        file_put_contents($jwkFile, json_encode($keyFactory->createFromPem(file_get_contents($pubFile), $options)));
        chmod($keyFile, 0640);
        chmod($certFile, 0640);
        chmod($pubFile, 0640);
        chmod($jwkFile, 0640);
    }

    public function setUp(): void
    {
        parent::setUp();

        $config = SSO_Config::getInstance();
        $config->{SSO_Config::OAUTH2}->{SSO_Config::ENABLED} = true;
        $config->{SSO_Config::SAML2}->{SSO_Config::ENABLED} = true;

        // Save old configs
        $keys = $config->{SSO_Config::OAUTH2}->{SSO_Config::OAUTH2_KEYS};
        $this->_oldOauth2KeyCfg = is_object($keys) ? $keys->toArray() : $keys;
        $keys = $config->{SSO_Config::SAML2}->{SSO_Config::SAML2_KEYS};
        $this->_oldSaml2KeyCfg = is_object($keys) ? $keys->toArray() : $keys;
        $this->_oldKeyRotationEnabled = $config->{SSO_Config::KEY_ROTATION_ENABLED} ?? false;

        // Enable key rotation
        $config->{SSO_Config::KEY_ROTATION_ENABLED} = true;

        // Clear keys from config (keyRotate checks for keys in file config and aborts if present)
        $config->{SSO_Config::OAUTH2}->{SSO_Config::OAUTH2_KEYS} = [];
        $config->{SSO_Config::SAML2}->{SSO_Config::SAML2_KEYS} = [];

        // Create temp key directory
        $this->_tempKeyDir = Tinebase_Core::getTempDir() . '/.ssokeys_test';
        if (is_dir($this->_tempKeyDir)) {
            exec('rm -rf ' . escapeshellarg($this->_tempKeyDir));
        }
        mkdir($this->_tempKeyDir, 0750, true);
    }

    protected function tearDown(): void
    {
        $config = SSO_Config::getInstance();
        $config->{SSO_Config::OAUTH2}->{SSO_Config::OAUTH2_KEYS} = $this->_oldOauth2KeyCfg;
        $config->{SSO_Config::SAML2}->{SSO_Config::SAML2_KEYS} = $this->_oldSaml2KeyCfg;
        $config->{SSO_Config::KEY_ROTATION_ENABLED} = $this->_oldKeyRotationEnabled;

        // Cleanup temp key directory
        if ($this->_tempKeyDir && is_dir($this->_tempKeyDir)) {
            exec('rm -rf ' . escapeshellarg($this->_tempKeyDir));
        }

        parent::tearDown();
    }

    public function testKeyRotateCreatesNewKeyAndOldKeyIsActiveUntilNotBefore(): void
    {
        // Step 1: Create an old key
        $oldDate = Tinebase_DateTime::now()->subMonth(7);
        $keyBaseDir = $this->_tempKeyDir . '/.ssokeys';
        mkdir($keyBaseDir, 0750, true);
        $oldDateDir = $keyBaseDir . '/' . $oldDate->format('Y-m-d H:i:s');
        mkdir($oldDateDir);
        $this->_createTestKey($oldDateDir, 'tine-sso-' . basename($oldDateDir));

        // Add the old key to config
        $oldKeyConfig = [
            'use' => 'sig',
            'alg' => 'RS256',
            'kid' => 'tine-sso-' . basename($oldDateDir),
            'privatekey' => $oldDateDir . '/tine-sso-' . basename($oldDateDir) . '_key.pem',
            'publickey' => $oldDateDir . '/tine-sso-' . basename($oldDateDir) . '_cert.crt',
        ];
        SSO_Config::getInstance()->{SSO_Config::OAUTH2}->{SSO_Config::OAUTH2_KEYS} = [$oldKeyConfig];

        // Step 2: Call keyRotate - should create a new key and keep the old one
        $result = SSO_Controller::getInstance()->keyRotate();
        $this->assertTrue($result, 'keyRotate should return true');

        // Step 3: Verify both keys exist in config
        $oauth2Keys = SSO_Config::getInstance()->{SSO_Config::OAUTH2}->{SSO_Config::OAUTH2_KEYS};
        $this->assertIsArray($oauth2Keys, 'OAuth2 keys should be an array');
        $this->assertCount(2, $oauth2Keys, 'Should have both old and new keys after rotation');

        // Step 4: Find the new key (the one with notBefore set)
        $newKey = null;
        $foundOldKey = false;
        foreach ($oauth2Keys as $k) {
            if (isset($k['notBefore'])) {
                $newKey = $k;
            } elseif ($k['kid'] === $oldKeyConfig['kid']) {
                $foundOldKey = true;
            }
        }
        $this->assertNotNull($newKey, 'New key should have notBefore set');
        $this->assertTrue($foundOldKey, 'Old key should still be in config');

        // Step 5: Verify the new key's notBefore is in the future
        $notBefore = new Tinebase_DateTime($newKey['notBefore']);
        $this->assertTrue($notBefore->isLater(Tinebase_DateTime::now()), 'notBefore should be in the future');

        // Step 6: getActiveOAuth2Key should return the old key because the new key's notBefore hasn't elapsed
        $activeKey = SSO_Controller::getActiveOAuth2Key();
        $this->assertSame($oldKeyConfig['kid'], $activeKey['kid'],
            'getActiveOAuth2Key should return the old key (new key notBefore not yet elapsed)');
    }
}
