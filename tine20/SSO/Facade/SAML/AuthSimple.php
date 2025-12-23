<?php declare(strict_types=1);

use SimpleSAML\Utils;

/**
 * Facade for simpleSAMLphp Auth/Simple class
 *
 * @package     SSO
 * @subpackage  Facade
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2021 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */
class SSO_Facade_SAML_AuthSimple extends \SimpleSAML\Auth\Simple
{
    /**
     * Start an authentication process.
     *
     * This function accepts an array $params, which controls some parts of the authentication. The accepted parameters
     * depends on the authentication source being used. Some parameters are generic:
     *  - 'ErrorURL': A URL that should receive errors from the authentication.
     *  - 'KeepPost': If the current request is a POST request, keep the POST data until after the authentication.
     *  - 'ReturnTo': The URL the user should be returned to after authentication.
     *  - 'ReturnCallback': The function we should call after the user has finished authentication.
     *
     * Please note: this function never returns.
     *
     * @param array $params Various options to the authentication request.
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function login(array &$params): \Symfony\Component\HttpFoundation\Response
    {
        if (array_key_exists('KeepPost', $params)) {
            $keepPost = (bool) $params['KeepPost'];
        } else {
            $keepPost = true;
        }

        $httpUtils = new Utils\HTTP();
        if (array_key_exists('ReturnTo', $params)) {
            $returnTo = (string) $params['ReturnTo'];
        } else {
            if (array_key_exists('ReturnCallback', $params)) {
                $returnTo = (array) $params['ReturnCallback'];
            } else {
                $returnTo = $httpUtils->getSelfURL();
            }
        }

        if (is_string($returnTo) && $keepPost && $_SERVER['REQUEST_METHOD'] === 'POST') {
            $returnTo = $httpUtils->getPOSTRedirectURL($returnTo, $_POST);
        }

        if (array_key_exists('ErrorURL', $params)) {
            $errorURL = (string) $params['ErrorURL'];
        } else {
            $errorURL = null;
        }

        $as = $this->getAuthSource();

        try {
            $as->initLogin($returnTo, $errorURL, $params);
        } catch (SSO_Facade_SAML_LoginMaskException $e) {
            // we need to make sure the state has not set any exception handlers!
            // we want the exceptions we throw, to be thrown all the way back to tine20 code to catch it!
            unset($params[\SimpleSAML\Auth\State::EXCEPTION_HANDLER_URL]);
            unset($params[\SimpleSAML\Auth\State::EXCEPTION_HANDLER_FUNC]);
            throw $e;
        } catch (SSO_Facade_SAML_MFAMaskException $e) {
            // we need to make sure the state has not set any exception handlers!
            // we want the exceptions we throw, to be thrown all the way back to tine20 code to catch it!
            unset($params[\SimpleSAML\Auth\State::EXCEPTION_HANDLER_URL]);
            unset($params[\SimpleSAML\Auth\State::EXCEPTION_HANDLER_FUNC]);
            throw $e;
        } catch (SSO_Facade_SAML_RedirectException $e) {
            // we need to make sure the state has not set any exception handlers!
            // we want the exceptions we throw, to be thrown all the way back to tine20 code to catch it!
            unset($params[\SimpleSAML\Auth\State::EXCEPTION_HANDLER_URL]);
            unset($params[\SimpleSAML\Auth\State::EXCEPTION_HANDLER_FUNC]);
            throw $e;
        }
        throw new Exception('unreachable');
    }
}
