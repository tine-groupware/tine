<?php declare(strict_types=1);
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Event
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2026 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */

/**
 * event for before record updates
 *
 * @package     Tinebase
 * @subpackage  Event
 */
class Tinebase_Event_Record_BeforeUpdate extends Tinebase_Event_Observer_Abstract
{
    public ?Tinebase_Record_Interface $oldRecord = null;
}