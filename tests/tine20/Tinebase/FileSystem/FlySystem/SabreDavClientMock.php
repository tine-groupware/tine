<?php declare(strict_types=1);
/**
 * Tine 2.0 - http://www.tine20.org
 *
 * @package     Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2023 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */

use Sabre\HTTP\RequestInterface;
use Sabre\HTTP\ResponseInterface;

class Tinebase_FileSystem_FlySystem_SabreDavClientMock extends Tinebase_FileSystem_FlySystem_CachingSabreDavClient
{
    public $request;
    public $response;
    public static $callBack;

    public $url;
    public $curlSettings;

    /**
     * Just making this method public.
     *
     * @param string $url
     *
     * @return string
     */
    public function getAbsoluteUrl($url)
    {
        return parent::getAbsoluteUrl($url);
    }

    public function doRequest(RequestInterface $request): ResponseInterface
    {
        $this->request = $request;

        if (static::$callBack) {
            return (static::$callBack)();
        }
        return $this->response;
    }
}
