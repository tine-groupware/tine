<?php declare(strict_types=1);

/**
 * Tine 2.0
 *
 * @package     EventManager
 * @subpackage  Option
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2022 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * class to hold localized Option data
 *
 * @package     EventManager
 * @subpackage  Option
 */
class EventManager_Model_OptionLocalization extends Tinebase_Record_PropertyLocalization
{
    public const MODEL_NAME_PART = 'OptionLocalization';

    /**
     * holds the configuration object (must be declared in the concrete class)
     *
     * @var Tinebase_ModelConfiguration
     */
    protected static $_configurationObject = NULL;
}
