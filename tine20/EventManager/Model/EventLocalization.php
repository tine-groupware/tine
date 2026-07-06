<?php declare(strict_types=1);

/**
 * tine Groupware
 *
 * @package     EventManager
 * @subpackage  Event
 * @license     https://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Tonia Wulff <t.wulff@metaways.de>
 * @copyright   Copyright (c) 2026 Metaways Infosystems GmbH (https://www.metaways.de)
 */

/**
 * class to hold localized Event data
 *
 * @package     EventManager
 * @subpackage  Event
 */
class EventManager_Model_EventLocalization extends Tinebase_Record_PropertyLocalization
{
    public const MODEL_NAME_PART = 'EventLocalization';

    /**
     * holds the configuration object (must be declared in the concrete class)
     *
     * @var Tinebase_ModelConfiguration
     */
    protected static $_configurationObject = NULL;
}
