<?php
/**
 * @package     MatrixSynapseIntegrator
 * @subpackage  Test
 * @license     https://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2025 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */

/**
 * Test class for Json Frontend
 */
class MatrixSynapseIntegrator_Frontend_JsonTest extends TestCase
{
    public function testMatrixAccountApi()
    {
        $user = Tinebase_Core::getUser();
        $this->_testSimpleRecordApi(
            MatrixSynapseIntegrator_Model_MatrixAccount::MODEL_NAME_PART,
            null,
            null, // no description because _testSimpleRecordApi does not support TYPE_USER fields atm
            true,
            [
                MatrixSynapseIntegrator_Model_MatrixAccount::FLD_ACCOUNT_ID => $user->getId(),
                MatrixSynapseIntegrator_Model_MatrixAccount::FLD_MATRIX_ID => '@' . $user->getId() . ':matrix.domain',
            ],
            false // no update (see above - descriptionField)
        );
    }
}
