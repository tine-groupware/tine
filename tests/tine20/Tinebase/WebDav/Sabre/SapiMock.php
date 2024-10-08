<?php declare(strict_types=1);

/**
 * HTTP Response Mock object.
 *
 * This class exists to make the transition to sabre/http easier.
 *
 * @copyright Copyright (C) fruux GmbH (https://fruux.com/)
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
class Tinebase_WebDav_Sabre_SapiMock extends \Sabre\HTTP\Sapi
{
    public static $sent = 0;

    /**
     * Overriding this so nothing is ever echo'd.
     */
    public static function sendResponse(\Sabre\HTTP\ResponseInterface $response)
    {
        ++self::$sent;
    }
}
