<?php declare(strict_types=1);
/**
 * sasl protocol trait
 *
 * @package     Felamimail
 * @subpackage  Protocol
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2025 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 * @method array escapeString(string $s1, string $s2 = '')
 * @method array requestAndResponse(string $s, array $a, boolean $b = false)
 */
trait Felamimail_Protocol_SaslTrait
{
    /**
     *
     * @param array $_params Parameters for authentication
     * @param string $_method Sasl method
     * @return array Response from server
     * @throws Exception
     */
    public function saslAuthenticate(array $_params, string $_method, bool $dontParse = true): array|bool
    {
        switch ($_method)
        {
            case 'XOAUTH2':
                $token = base64_encode('user=' . ($_params['email'] ?? '') . chr(1) . chr(1)  . 'auth=Bearer ' . ($_params['token'] ?? '') . chr(1) . chr(1));
                $result = $this->requestAndResponse('AUTHENTICATE', $this->escapeString('XOAUTH2', $token), $dontParse);
                return is_array($result) ? $result : false;

            default :
                throw new Exception("Sasl method $_method not implemented!");
        }
    }
}