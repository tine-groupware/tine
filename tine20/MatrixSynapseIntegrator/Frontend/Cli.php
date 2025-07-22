<?php
/**
 * Tine 2.0
 * @package     MatrixSynapseIntegrator
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Milan Mertens <m.mertens@metaways>
 * @copyright   Copyright (c) 2025 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * Cli frontend for MatrixSynapseIntegrator
 *
 * This class handles cli requests for the MatrixSynapseIntegrator
 *
 * @package     MatrixSynapseIntegrator
 */
class MatrixSynapseIntegrator_Frontend_Cli extends Tinebase_Frontend_Cli_Abstract
{
    /**
     * the internal name of the application
     * 
     * @var string
     */
    protected $_applicationName = MatrixSynapseIntegrator_Config::APP_NAME;

    public function exportDirectory($opt)
    {
        MatrixSynapseIntegrator_Controller_Directory::getInstance()->exportDirectory(true);
        return 0;
    }

    public function testMatrixLogin()
    {
        $response = MatrixSynapseIntegrator_Controller_MatrixAccount::getInstance()->synapseLogin();
        print_r($response);
        return 0;
    }
}
