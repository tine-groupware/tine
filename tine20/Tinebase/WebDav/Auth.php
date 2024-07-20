<?php

use Sabre\DAV;

/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  WebDav
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2010-2024 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 */

/**
 * class to handle webdav authentication
 * 
 * @package     Tinebase
 * @subpackage  WebDav
 */
class Tinebase_WebDav_Auth implements DAV\Auth\Backend\BackendInterface
{
    public function check(\Sabre\HTTP\RequestInterface $request, \Sabre\HTTP\ResponseInterface $response)
    {
        if (Tinebase_Core::getUser() instanceof Tinebase_Model_FullUser) {
            return [true, 'principals/users/' . Tinebase_Core::getUser()->contact_id];
        }
        return [false, 'not authenticated'];
    }

    public function challenge(\Sabre\HTTP\RequestInterface $request, \Sabre\HTTP\ResponseInterface $response)
    {
    }
}
