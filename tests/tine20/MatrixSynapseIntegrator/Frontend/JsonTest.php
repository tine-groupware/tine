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
    /**
     * unit in test
     *
     * @var MatrixSynapseIntegrator_Frontend_Json
     */
    protected $_uit = null;

    public function testMatrixAccountApi($delete = true): array
    {
        $user = Tinebase_Core::getUser();
        return $this->_testSimpleRecordApi(
            MatrixSynapseIntegrator_Model_MatrixAccount::MODEL_NAME_PART,
            null,
            null, // no description because _testSimpleRecordApi does not support TYPE_USER fields atm
            $delete,
            [
                MatrixSynapseIntegrator_Model_MatrixAccount::FLD_ACCOUNT_ID => $user->getId(),
                MatrixSynapseIntegrator_Model_MatrixAccount::FLD_MATRIX_ID => '@' . $user->getId() . ':matrix.domain',
                MatrixSynapseIntegrator_Model_MatrixAccount::FLD_MATRIX_RECOVERY_PASSWORD => 'somepw',
            ],
            false // no update (see above - descriptionField)
        );
    }

    public function testGetAccountData()
    {
        $account = $this->testMatrixAccountApi(false);
//        self::assertArrayHasKey(MatrixSynapseIntegrator_Model_MatrixAccount::FLD_MATRIX_RECOVERY_PASSWORD,
//            $account, print_r($account, true));
        $accountData = $this->_getUit()->getAccountData();
        self::assertIsArray($accountData);
        self::assertEquals('somepw',  $accountData['recovery_password']);
    }
}
