<?php

use function PHPUnit\Framework\returnArgument;

/**
 * Directory controller for MatrixSynapseIntegrator application
 *
 * @package     MatrixSynapseIntegrator
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Milan Mertens <m.mertens@metaways>
 * @copyright   Copyright (c) 2025 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * Directory controller class for MatrixSynapseIntegrator application
 *
 * @package     Directory
 * @subpackage  Controller
 */
class MatrixSynapseIntegrator_Controller_Directory extends Tinebase_Controller_Record_Abstract
{
    use Tinebase_Controller_SingletonTrait;

    protected const INCLUDED_USER_FIELDS = [
        'accountLoginName',
        'accountEmailAddress',
        'accountFirstName',
        'accountLastName',
        'accountFullName',
    ];

    protected const INCLUDED_CONTACT_FIELDS = [
        'n_fileas',
        'n_fn',
        'n_given',
        'tel_car_normalized',
        'tel_cell_normalized',
        'tel_fax_normalized',
        'tel_other_normalized',
        'tel_pager_normalized',
        'tel_prefer_normalized',
        'tel_work_normalized',
    ];

    protected $pdo;
    protected ?MatrixSynapseIntegrator_Backend_DirectoryPostgresql $_directory = null;

    protected function __construct()
    {
        $this->_applicationName = MatrixSynapseIntegrator_Config::APP_NAME;
    }

    protected function generateDirectory(): array
    {
        $entries = [];

        $matrixAccounts = MatrixSynapseIntegrator_Controller_MatrixAccount::getInstance()->getAll();
        foreach ($matrixAccounts as $matrixAccount) {
            $user = Tinebase_User::getInstance()->getFullUserById(
                $matrixAccount->{MatrixSynapseIntegrator_Model_MatrixAccount::FLD_ACCOUNT_ID}
            );

            if ($user->visibility !== 'displayed' || $user->accountStatus !== 'enabled') {
                continue;
            }

            $entries[] = [
                'id' => $matrixAccount->{MatrixSynapseIntegrator_Model_MatrixAccount::FLD_MATRIX_ID},
                'displayName' => $user->accountDisplayName,
                'userInfo' => join('|', $this->getUserInfo($user)),
            ];
        }

        return $entries;
    }

    public function getUserInfo(Tinebase_Model_FullUser $user): array
    {
        $userInfo = [];

        foreach (self::INCLUDED_USER_FIELDS as $field) {
            $value = $user->{$field};
            if ($value === null) {
                continue;
            }

            $userInfo[] = $value;
        }

        $contact = Addressbook_Controller_Contact::getInstance()->getContactByUserId($user);

        foreach (self::INCLUDED_CONTACT_FIELDS as $field) {
            $value = $contact->{$field};
            if ($value === null) {
                continue;
            }

            $userInfo[] = $value;
        }

        return $userInfo;
    }

    public function setDirectoryBackend(
        ?MatrixSynapseIntegrator_Backend_DirectoryPostgresql $backend = null
    ): MatrixSynapseIntegrator_Backend_DirectoryPostgresql {
        return $this->_directory = $backend ?: new MatrixSynapseIntegrator_Backend_DirectoryPostgresql();
    }

    public function getDirectoryBackend(): MatrixSynapseIntegrator_Backend_DirectoryPostgresql
    {
        return $this->_directory ?: $this->setDirectoryBackend();
    }

    /**
     * Generates and updates a directory that maps users and their address information to matrix ids. The directory is
     * used by synapse* to enrich local search. Allowing users to be searched by email address, phone number or real
     * name.
     *
     * - Mappings are only generated for synapse users who have an oidc auth provider (whose name starts with "oidc-").
     * - The directory database can be fed by multiple Tine instances, Tine instances only manage their own entries.
     * - * The directory endpoint is provided by ma1ds.
     */
    public function exportDirectory($force = false)
    {
        if (
            MatrixSynapseIntegrator_Config::getInstance()->{MatrixSynapseIntegrator_Config::MATRIX_DIRECTORY_ENABLED}
                || $force
        ) {
            $directory = $this->generateDirectory();

            if (sizeof($directory) == 0) {
                return true;
            }

            $this->getDirectoryBackend()->updateDirectory($directory);
        }

        return true;
    }
}
