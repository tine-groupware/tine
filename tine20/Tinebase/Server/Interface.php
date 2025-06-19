<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Server
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * 
 */

/**
 * Server Interface with handle function
 * 
 * @package     Tinebase
 * @subpackage  Server
 */
interface Tinebase_Server_Interface
{
    /**
     * handler for tine requests
     * 
     * @param  \Laminas\Http\Request|null $request
     * @param  resource|string|null $body
     */
    public function handle(?\Laminas\Http\Request $request = null, $body = null);
    
    /**
     * returns request method
     * 
     * @return string|NULL
     */
    public function getRequestMethod();
}
