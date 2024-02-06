<?php declare(strict_types=1);
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Exception
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2023 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 *
 */

/**
 * Auth Password Required exception
 *
 * @package     Tinebase
 * @subpackage  Exception
 */
class Tinebase_Exception_Auth_PwdRequired extends Tinebase_Exception_SystemGeneric
{
    protected $_title = 'Password required';

    public function __construct($_message = null, $_code = 651)
    {
        parent::__construct($_message, $_code);
    }
}