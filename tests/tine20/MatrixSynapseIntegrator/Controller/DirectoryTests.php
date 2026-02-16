<?php

use function PHPUnit\Framework\assertGreaterThan;
use function PHPUnit\Framework\returnArgument;

/**
 * Tine 2.0 - http://www.tine20.org
 *
 * @package     MatrixSynapseIntegrator
 * @subpackage  Test
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2025 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */

class MatrixSynapseIntegrator_Controller_DirectoryTests extends TestCase
{
    private $directoryMock;

    public function setUp(): void
    {
        parent::setUp();

        $this->directoryMock = new MatrixSynapseIntegrator_Backend_DirectoryMock();

        MatrixSynapseIntegrator_Controller_Directory::getInstance()->setDirectoryBackend(
            $this->directoryMock
        );
        MatrixSynapseIntegrator_Controller_MatrixAccount::getInstance()->setCorporalBackend(
            new MatrixSynapseIntegrator_Backend_CorporalMock()
        );
    }

    public function tearDown(): void
    {
        parent::tearDown();

        MatrixSynapseIntegrator_Controller_Directory::destroyInstance();
        MatrixSynapseIntegrator_Controller_MatrixAccount::destroyInstance();
    }

    public function testGetUserInfo()
    {
        $user = $this->createUser();
        Addressbook_Model_Contact::resetConfiguration();
        $contact = Addressbook_Controller_Contact::getInstance()->getContactByUserId($user);
        $contact->tel_work = '0123456789';
        $contact = Addressbook_Controller_Contact::getInstance()->update($contact);
        $this->assertSame('+49123456789', $contact->tel_work_normalized, print_r($contact->toArray(), true) . PHP_EOL . print_r(Addressbook_Model_Contact::getTelefoneFields(), true));

        $userInfo = MatrixSynapseIntegrator_Controller_Directory::getInstance()->getUserInfo($user);

        self::assertContains($user->accountFirstName, $userInfo);
        self::assertContains($user->accountLastName, $userInfo);
        self::assertContains($user->accountLoginName, $userInfo);
        self::assertContains($user->accountLoginName . '@mail.test', $userInfo);
        self::assertContains('+49123456789', $userInfo, print_r($userInfo, true));
    }

    public function testExportDirectory()
    {
        $this->_skipIfLDAPBackend('Zend_Ldap_Exception: 0x44 (Already exists; Entry CN=PHPUnit User Tine 2.0...');

        $user1 = $this->createUser();
        $user2 = $this->createUser();

        $userDisabled = $this->createUser();
        $userDisabled->accountStatus = Tinebase_Model_User::ACCOUNT_STATUS_DISABLED;
        Admin_Controller_User::getInstance()->update($userDisabled);

        $userInvisible = $this->createUser();
        $userInvisible->visibility = Tinebase_Model_User::VISIBILITY_HIDDEN;
        Admin_Controller_User::getInstance()->update($userInvisible);

        MatrixSynapseIntegrator_Controller_Directory::getInstance()->exportDirectory(true);

        self::assertNotNull($this->getUserEntry($user1, $this->directoryMock->directory));
        self::assertNotNull($this->getUserEntry($user2, $this->directoryMock->directory));
        self::assertNull($this->getUserEntry($userDisabled, $this->directoryMock->directory));
        self::assertNull($this->getUserEntry($userInvisible, $this->directoryMock->directory));
    }

    private function createUser()
    {
        $user = $this->_createTestUser();
        MatrixSynapseIntegrator_Controller_MatrixAccount::getInstance()->create(
            new MatrixSynapseIntegrator_Model_MatrixAccount(
                MatrixSynapseIntegrator_ControllerTests::getMatrixAccountData($user)
            )
        );

        return $user;
    }

    private function getUserEntry(Tinebase_Model_User $user, array $directory): array|null
    {
        foreach ($directory as $entry) {
            $matrixAccount = MatrixSynapseIntegrator_Controller_MatrixAccount::getInstance()->getMatrixAccountForUser(
                $user
            );

            if ($entry['id'] === $matrixAccount->{MatrixSynapseIntegrator_Model_MatrixAccount::FLD_MATRIX_ID}) {
                return $entry;
            }
        }

        return null;
    }
}
