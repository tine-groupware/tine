<?php
/**
 * Tine 2.0
 * @package     GDPR
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Ching En Cheng <c.cheng@metaways.de>
 * @copyright   Copyright (c) 2023 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * Cli frontend for GDPR
 *
 * This class handles cli requests for the GDPR
 *
 * @package     GDPR
 */
class GDPR_Frontend_Cli extends Tinebase_Frontend_Cli_Abstract
{
    /**
     * the internal name of the application
     * 
     * @var string
     */
    protected $_applicationName = GDPR_Config::APP_NAME;
}
