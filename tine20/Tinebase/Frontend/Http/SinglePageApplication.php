<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2018-2025 Metaways Infosystems GmbH (http://www.metaways.de)
 */
class Tinebase_Frontend_Http_SinglePageApplication {

    /**
     * generates initial client html
     *
     * @param string|array  $entryPoint
     * @param string        $template
     * @param array         $context
     * @return \Laminas\Diactoros\Response\HtmlResponse
     */
    public static function getClientHTML($entryPoint,
                                         $appName = 'Tinebase',
                                         ?string $template = null,
                                         array $context = [],
                                         ?string $fallbackHtml = null): \Laminas\Diactoros\Response\HtmlResponse
    {
        $entryPoints = is_array($entryPoint) ? $entryPoint : [$entryPoint];
        $template = $template ? "$appName/views/$template" : "Tinebase/views/singlePageApplication.html.twig";

        try {
            $html = self::_getTwigRenderedClientHTML($entryPoints, $appName, $template, $context);
        } catch (Throwable $e) {
            if (Tinebase_Core::isLogLevel(Zend_Log::ERR)) {
                Tinebase_Core::getLogger()->err(
                    __METHOD__ . '::' . __LINE__ . ' ' . $e);
            }
            $html = $fallbackHtml ?? 'ERROR: Could not load client tine HTML template.';
        }

        return new \Laminas\Diactoros\Response\HtmlResponse($html, 200, self::getHeaders());
    }

    protected static function _getTwigRenderedClientHTML($entryPoints, $appName, $template, $context): string
    {
        $locale = $context['lang'] ?? Tinebase_Core::getLocale();
        $twig = new Tinebase_Twig($locale, Tinebase_Translation::getTranslation($appName));
        $textTemplate = $twig->load($template, $locale);

        if (! array_key_exists('base', $context) && $base = self::_getBase()) {
            $context['base'] = $base;
        }

        $headerTemplate = $twig->load('Tinebase/views/header.html.twig');
        $footerTemplate = $twig->load('Tinebase/views/footer.html.twig');

        if (!isset($context['initialData'])) {
            $context['initialData'] = [];
        }

        if (isset($context['requiredPublicPagesConfig'])) {
            $translation = Tinebase_Translation::getTranslation();
            $enablePublicPages = Tinebase_Application::getInstance()->isInstalled(GDPR_Config::APP_NAME, true) &&
                GDPR_Config::getInstance()->get(GDPR_Config::ENABLE_PUBLIC_PAGES);

            //we have some modules use singlePageApplication vue template, some use base twig template, initialData is checked in singlePageApplication vue template only
            if (!$enablePublicPages) {
                $context['initialData']['errorMessage'] = $translation->_('Feature is not available. Please ask admin to enable public page config');
            }
        }

        $context['initialData'] = array_merge($context['initialData'], [
            'header'    => $headerTemplate->render($context),
            'footer'    => $footerTemplate->render($context),
        ]);
        $context['initialData'] = json_encode($context['initialData']);

        $context += [
            'assetHash' => Tinebase_Frontend_Http_SinglePageApplication::getAssetHash(),
            'jsFiles' => $entryPoints,
        ];

        /** @var \Psr\Http\Message\ServerRequestInterface $request */
        $request = Tinebase_Core::getContainer()->get(\Psr\Http\Message\RequestInterface::class);
        $headers = $request->getHeaders();

        if (isset($headers['x-tine20-render-content-only'])) {
            return $textTemplate->renderBlock('content', $context);
        }

        return $textTemplate->render($context);
    }

    protected static function _getBase(): ?string
    {
        $result = null;

        /** @var \Psr\Http\Message\ServerRequestInterface $request */
        $request = Tinebase_Core::getContainer()->get(\Psr\Http\Message\RequestInterface::class);
        $requestPath = $request->getUri()->getPath();
        $requestPath = rtrim($requestPath, '/');
        $depth = substr_count(preg_replace('/^\//', '', $requestPath), '/');
        if ($depth > 0) {
            $result = str_repeat('../', $depth);
        }

        // add subdir path if present
        $tineurlPath = Tinebase_Core::getUrl(Tinebase_Core::GET_URL_PATH);
        if (!empty($tineurlPath) && $tineurlPath !== DIRECTORY_SEPARATOR) {
            $result = $tineurlPath . DIRECTORY_SEPARATOR . $result;
        }

        if ($result) {
            // finally remove duplicate slashes
            $result = str_replace('//', '/', $result);
        }

        return $result;
    }

    /**
     * gets headers for initial client html pages
     *
     * @return array
     */
    public static function getHeaders()
    {
        $header = [];

        $frameAncestors = implode(' ' ,array_merge(
            (array) Tinebase_Core::getConfig()->get(Tinebase_Config::ALLOWEDJSONORIGINS, array()),
            array("'self'")
        ));

        // set Content-Security-Policy header against clickjacking and XSS
        // @see https://developer.mozilla.org/en/Security/CSP/CSP_policy_directives
        $scriptSrcs = array("'self'", "'unsafe-eval'", 'https://versioncheck.tine20.net');
        if (TINE20_BUILDTYPE == 'DEVELOPMENT') {
            $scriptSrcs[] = Tinebase_Core::getUrl(Tinebase_Core::GET_URL_PROTOCOL) . '://' .
                Tinebase_Core::getUrl(Tinebase_Core::GET_URL_HOST) . ":10443";
        }
        $scriptSrc = implode(' ', $scriptSrcs);
        $header += [
            "Content-Security-Policy" => "default-src 'self'",
            "Content-Security-Policy" => "script-src $scriptSrc",
            "Content-Security-Policy" => "frame-ancestors $frameAncestors",

            // headers for IE 10+11
            "X-Content-Security-Policy" => "default-src 'self'",
            "X-Content-Security-Policy" => "script-src $scriptSrc",
            "X-Content-Security-Policy" => "frame-ancestors $frameAncestors",
        ];

        // set Strict-Transport-Security; used only when served over HTTPS
        $headers['Strict-Transport-Security'] = 'max-age=16070400';

        // cache mainscreen for one day in production
        $maxAge = ! defined('TINE20_BUILDTYPE') || TINE20_BUILDTYPE != 'DEVELOPMENT' ? 86400 : -10000;
        $header += [
            'Cache-Control' => 'private, max-age=' . $maxAge,
            'Expires' => gmdate('D, d M Y H:i:s', Tinebase_DateTime::now()->addSecond($maxAge)->getTimestamp()) . " GMT",
        ];

        return $header;
    }

    /**
     * get map of asset files
     *
     * @param boolean $asJson
     * @throws Exception
     * @return string|array
     */
    public static function getAssetsMap($asJson = false)
    {
        $jsonFile = self::getAssetsJsonFilename();

        if (TINE20_BUILDTYPE =='DEVELOPMENT') {
            $devServerURL = Tinebase_Config::getInstance()->get('webpackDevServerURL', 'http://localhost:10443');
            $jsonFileUri = $devServerURL . '/' . $jsonFile;
            $json = Tinebase_Helper::getFileOrUriContents($jsonFileUri);
            if (! $json) {
                Tinebase_Core::getLogger()->err(self::class . '::' . __METHOD__
                    . ' (' . __LINE__ .') Could not get json file: ' . $jsonFile);
                throw new Exception('You need to run webpack-dev-server in dev mode! See https://wiki.tine20.org/Developers/Getting_Started/Working_with_GIT#Install_webpack');
            }
        } else if ($absoluteJsonFilePath = self::getAbsoluteAssetsJsonFilename()) {
            $json = file_get_contents($absoluteJsonFilePath);
        } else {
            throw new Tinebase_Exception_NotFound(('assets json not found'));
        }

        return $asJson ? $json : json_decode($json, true);
    }

    /**
     * @return string
     */
    public static function getAssetsJsonFilename()
    {
        return 'Tinebase/js/webpack-assets-FAT.json';
    }

    /**
     * @return string|null
     */
    public static function getAbsoluteAssetsJsonFilename()
    {
        $path = __DIR__ . '/../../../' . self::getAssetsJsonFilename();
        if (! file_exists($path)) {
            return null;
        }
        return $path;
    }

    /**
     *
     * @param  bool     $userEnabledOnly    this is needed when server concats js
     * @return string
     * @throws Exception
     * @throws Tinebase_Exception_InvalidArgument
     */
    public static function getAssetHash($userEnabledOnly = false)
    {
        $map = self::getAssetsMap();

        try {
            $apps = Tinebase_Application::getInstance()->getApplications(null, /* sort = */ 'order')->name;
            if ($userEnabledOnly) {
                $apps = array_intersect($apps, Tinebase_Application::getInstance()->getApplicationsByState(Tinebase_Application::ENABLED)->name);
            }

            foreach ($map as $asset => $ressources) {
                $appName = basename($asset);
                if (!in_array($appName, $apps)) {
                    unset($map[$asset]);
                }
            }
        } catch (Exception $e) {
            Tinebase_Core::getLogger()->notice(self::class . '::' . __METHOD__ . ' (' . __LINE__ .') cannot filter assetMap by installed apps');
            Tinebase_Core::getLogger()->notice(self::class . '::' . __METHOD__ . ' (' . __LINE__ .') ' . $e);
        }

        return sha1(json_encode($map) . TINE20_BUILDTYPE);
    }
}
