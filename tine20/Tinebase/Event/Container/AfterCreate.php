<?php declare(strict_types=1);
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Event
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2026 Metaways Infosystems GmbH (https://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */

/**
 * event fired after a container has been created
 *
 * @package     Tinebase
 * @subpackage  Container
 */
class Tinebase_Event_Container_AfterCreate extends Tinebase_Event_Abstract
{
    public Tinebase_Model_Container $container;
}
