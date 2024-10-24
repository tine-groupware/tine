<?php declare(strict_types=1);
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2024 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * class for Tinebase demodata
 *
 * @package     Tinebase
 */
class Tinebase_Setup_DemoData extends Tinebase_Setup_DemoData_Abstract
{
    use Tinebase_Controller_SingletonTrait;

    protected $_appName = Tinebase_Config::APP_NAME;
}