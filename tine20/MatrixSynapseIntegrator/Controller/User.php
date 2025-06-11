<?php

/**
 * MatrixSynapseIntegrator Controller
 *
 * @package      MatrixSynapseIntegrator
 * @subpackage   Controller
 * @license      http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2025 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */

/**
 * MatrixSynapseIntegrator Controller
 *
 * @package      MatrixSynapseIntegrator
 * @subpackage   Controller
 *
 * @todo        remove this - obsoleted by
 */
class MatrixSynapseIntegrator_Controller_User extends Tinebase_Controller_Abstract
{
    use Tinebase_Controller_SingletonTrait;

    protected ?MatrixSynapseIntegrator_Backend_Corporal $_backend = null;

    public function setBackend(?MatrixSynapseIntegrator_Backend_Corporal $backend = null): MatrixSynapseIntegrator_Backend_Corporal
    {
        return $this->_backend = $backend ?: new MatrixSynapseIntegrator_Backend_Corporal();
    }

    public function getBackend(): MatrixSynapseIntegrator_Backend_Corporal
    {
        return $this->_backend ?: $this->setBackend();
    }

    public function create(Tinebase_Model_FullUser $user): bool
    {
        if ($this->_inactiveMatrixUser($user)) {
            return true;
        }

        $this->_replaceUserIdXprop($user);
        $this->getBackend()->push($user);

        return true;
    }

    protected function _inactiveMatrixUser(Tinebase_Model_FullUser $user): bool
    {
        $matrixId = $user->xprops()[MatrixSynapseIntegrator_Config::USER_XPROP_MATRIX_ID] ?? null;
        $active = $user->xprops()[MatrixSynapseIntegrator_Config::USER_XPROP_MATRIX_ACTIVE] ?? false;
        return (! $active || empty($matrixId));
    }

    public function update(Tinebase_Model_FullUser $user, Tinebase_Model_FullUser $olduser): bool
    {
        $matrixId = $user->xprops()[MatrixSynapseIntegrator_Config::USER_XPROP_MATRIX_ID] ?? null;
        $active = $user->xprops()[MatrixSynapseIntegrator_Config::USER_XPROP_MATRIX_ACTIVE] ?? false;
        $oldMatrixId = $olduser->xprops()[MatrixSynapseIntegrator_Config::USER_XPROP_MATRIX_ID] ?? null;
        $oldActive = $olduser->xprops()[MatrixSynapseIntegrator_Config::USER_XPROP_MATRIX_ACTIVE] ?? false;

        if ($active === $oldActive && $matrixId == $oldMatrixId) {
            return true;
        }
        $this->getBackend()->push($user);
        return true;
    }

    public function delete(Tinebase_Model_FullUser $user): bool
    {
        if ($this->_inactiveMatrixUser($user)) {
            return true;
        }

        $user->xprops()[MatrixSynapseIntegrator_Config::USER_XPROP_MATRIX_ACTIVE] = false;
        $this->getBackend()->push($user);
        return true;
    }

    /**
     * update xprop - replace user id
     *
     * @param Tinebase_Model_FullUser $user
     * @return void
     * @throws Tinebase_Exception_InvalidArgument
     * @throws Tinebase_Exception_Record_Validation
     * @throws Tinebase_Exception_SystemGeneric
     */
    protected function _replaceUserIdXprop(Tinebase_Model_FullUser $user): void
    {
        $matrixId = $user->xprops()[MatrixSynapseIntegrator_Config::USER_XPROP_MATRIX_ID];
        if (str_contains($matrixId, '{user.id}')) {
            $user->xprops()[MatrixSynapseIntegrator_Config::USER_XPROP_MATRIX_ID] = str_replace('{user.id}',
                $user->getId(), $matrixId);
            Tinebase_User::getInstance()->updateUserInSqlBackend($user);
        }
    }
}
