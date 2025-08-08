<?php declare(strict_types=1);
/**
 * Tine 2.0
 *
 * @package     SSO
 * @subpackage  Frontend
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2024-2025 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * cli server for SSO
 *
 * This class handles cli requests for the SSO
 *
 * @package     SSO
 * @subpackage  Frontend
 */
class SSO_Frontend_Cli extends Tinebase_Frontend_Cli_Abstract
{
    /**
     * the internal name of the application
     *
     * @var string
     */
    protected $_applicationName = SSO_Config::APP_NAME;

    /**
     * help array with function names and param descriptions
     *
     * @return void
     */
    protected $_help = [
        'generateKey' => [
            'description'   => 'generate openSSL private public key',
            'params'        => [
                'path'          => 'path where key files should be stored [required]',
                'name'          => 'name of the key [required]',
            ],
        ],
    ];

    public function generateKey(Zend_Console_Getopt $_opts): int
    {
        $this->_checkAdminRight();

        $params = $this->_parseArgs($_opts, ['path', 'name']);

        try {
            SSO_Controller::getInstance()->generateKey($params['path'], $params['name']);
        } catch (Tinebase_Exception $e) {
            echo PHP_EOL . $e->getMessage() . PHP_EOL;
            return 1;
        }

        if (!SSO_Controller::getInstance()->addKeysToConfig($params['path'], $params['name'])) {
            echo PHP_EOL . 'failed to add keys to config' . PHP_EOL;
            return 1;
        }

        echo PHP_EOL . 'success' . PHP_EOL;
        return 0;
    }
}