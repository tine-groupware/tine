<?php declare(strict_types=1);
/**
 * tine Groupware
 *
 * @package     MatrixSynapseIntegrator
 * @subpackage  Setup
 * @license     https://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2025 Metaways Infosystems GmbH (https://www.metaways.de)
 */

/**
 * Class to handle application uninitialization
 *
 * @package     MatrixSynapseIntegrator
 * @subpackage  Setup
 */
class MatrixSynapseIntegrator_Setup_Uninitialize extends Setup_Uninitialize
{
    /**
     * uninitialize custom fields
     *
     * @param Tinebase_Model_Application $_application
     * @return void
     * @throws Tinebase_Exception_InvalidArgument
     * @throws Tinebase_Exception_NotFound
     */
    protected function _uninitializeCustomFields(Tinebase_Model_Application $_application)
    {
        self::removeCustomFields(MatrixSynapseIntegrator_Setup_Initialize::$customfields, $_application);
    }
}
