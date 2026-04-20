<?php

/**
 * MatrixSynapseIntegrator Backend
 *
 * @package      MatrixSynapseIntegrator
 * @subpackage   Backend
 * @license      https://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2025 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */

/**
 * MatrixSynapseIntegrator Backend Mock
 *
 * @package      MatrixSynapseIntegrator
 * @subpackage   Backend
 */
class MatrixSynapseIntegrator_Backend_SynapseMock extends MatrixSynapseIntegrator_Backend_Synapse
{
    public const ROOM_ID = '!hwkMyiXxaBUxTkGhDl:matrix.local.tine-dev.de';

    public function login(MatrixSynapseIntegrator_Model_MatrixAccount $account): array
    {
        return [
             'user_id' => '@monkey83:matrix.local.tine-dev.de',
             'home_server' => 'matrix.local.tine-dev.de',
             'access_token' => 'syt_bW9ua2V5ODM_lZeStfEXvhyzIREMPfjW_0oJGpP',
             'device_id' => 'ZMRHTQVBVI',
         ];
    }

    public function createRoom(MatrixSynapseIntegrator_Model_Room $room): string
    {
        return self::ROOM_ID;
    }
}
