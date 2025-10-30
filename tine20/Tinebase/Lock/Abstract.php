<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Lock
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2018-2025 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */

/**
 * Abstract lock implementation
 *
 * @package     Tinebase
 * @subpackage  Lock
 */
abstract class Tinebase_Lock_Abstract implements Tinebase_Lock_Interface
{
    protected string $_lockId;

    protected bool $_isLocked = false;

    public function __construct(string $_lockId)
    {
        $this->_lockId = sha1($_lockId);
    }

    public function isLocked(): bool
    {
        return $this->_isLocked;
    }
}