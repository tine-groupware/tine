<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Auth
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2011-2024 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */

use Psr\Http\Message\ResponseInterface;

/**
 * credential cache adapter (cookie)
 *  
 * @package     Tinebase
 * @subpackage  Auth
 */
class Tinebase_Auth_CredentialCache_Adapter_Cookie implements Tinebase_Auth_CredentialCache_Adapter_Interface
{
    /**
     * cookie key const
     * 
     * @var string
     */
    public const COOKIE_KEY = 'usercredentialcache';
    
    /**
     * setCache() - persists cache
     *
     * @param  Tinebase_Model_CredentialCache $_cache
     */
    public function setCache(Tinebase_Model_CredentialCache $_cache, ?ResponseInterface $response = null): ?ResponseInterface
    {
        $cacheId = $_cache->getCacheId();
        $cookieOptions = Tinebase_Helper::getDefaultCookieSettings();
        $cookieValue = base64_encode(Zend_Json::encode($cacheId));
        if (null === $response) {
            setcookie(self::COOKIE_KEY, $cookieValue, $cookieOptions);
        } else {
            $cookie = new HansOtt\PSR7Cookies\SetCookie(
                name: self::COOKIE_KEY,
                value: $cookieValue,
                path: '/',
                secure: $cookieOptions['secure'] ?? false,
                httpOnly: true,
                sameSite: $cookieOptions['samesite'] ?? ''
            );
            $response = $cookie->addToResponse($response);
        }
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
            . ' Set credential cache cookie.');

        return $response;
    }
    
    /**
     * getCache() - get the credential cache
     *
     * @return ?Tinebase_Model_CredentialCache
     */
    public function getCache(): ?Tinebase_Model_CredentialCache
    {
        $result = null;
        if (isset($_COOKIE[self::COOKIE_KEY]) && ! empty($_COOKIE[self::COOKIE_KEY])) {
            $decodedCookie = base64_decode((string) $_COOKIE[self::COOKIE_KEY]);
            try {
                $cacheId = Zend_Json::decode($decodedCookie);
            } catch (Zend_Json_Exception $zje) {
                Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__
                    . ' Cookie decoding failed:' . $zje->getMessage()
                    . ' (cookie: ' . $decodedCookie . ')');
                return null;
            }
            if (is_array($cacheId)) {
                $result = new Tinebase_Model_CredentialCache($cacheId);
            } else {
                Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__
                    . ' Could not get CC from cookie (cache is not an array)');
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(
                    __METHOD__ . '::' . __LINE__ . ' cache: ' . print_r($cacheId, true));
            }
        } else {
            Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__
                . ' Could not get CC from cookie (could not find CC key in $_COOKIE)');
        }
        
        return $result;
    }

    /**
     * resetCache() - resets the cache
     */
    public function resetCache()
    {
        if (headers_sent()) {
            if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) Tinebase_Core::getLogger()->notice(
                __METHOD__ . '::' . __LINE__ . ' Could not set cookie - headers already sent');
        } else {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(
                __METHOD__ . '::' . __LINE__ . ' Reset credential cache cookie.');
            setcookie(self::COOKIE_KEY, '', ['expires' => time() - 3600, 'path' => '', 'domain' => '', 'secure' => Tinebase_Core::isHttpsRequest()]);
        }
    }
    
    /**
     * getDefaultKey() - get default cache key
     * 
     * @return string
     */
    public function getDefaultKey()
    {
        return Tinebase_Record_Abstract::generateUID();
    }

    /**
     * getDefaultId() - get default cache id
     * 
     * @return string
     */
    public function getDefaultId()
    {
        return Tinebase_Record_Abstract::generateUID();
    }
}
