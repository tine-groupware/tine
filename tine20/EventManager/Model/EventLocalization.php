<?php declare(strict_types=1);

/**
 * Tine 2.0
 *
 * @package     EventManager
 * @subpackage  Event
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2022 Metaways Infosystems GmbH (http://www.metaways.de)
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
