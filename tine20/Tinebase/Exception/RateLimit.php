<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Exception
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2023 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schuele <p.schuele@metaways.de>
 *
 */

/**
 * RateLimit exception
 * 
 * @package     Tinebase
 * @subpackage  Exception
 *
 * see https://www.rfc-editor.org/rfc/rfc6585#section-4
 */
class Tinebase_Exception_RateLimit extends Tinebase_Exception_ProgramFlow
{
    public function __construct($_message = 'Too Many Requests', $_code = 429)
    {
        parent::__construct($_message, $_code);
    }

    // TODO also return number of allowed requests by timeframe
}
