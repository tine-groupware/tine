<?php

/**
 * MatrixSynapseIntegrator Backend
 *
 * @package      MatrixSynapseIntegrator
 * @subpackage   Backend
 * @license      https://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright    Copyright (c) 2025 Metaways Infosystems GmbH (https://www.metaways.de)
 * @author       Philipp Schüle <p.schuele@metaways.de>
 */

/**
 * MatrixSynapseIntegrator Backend
 *
 * @package      MatrixSynapseIntegrator
 * @subpackage   Backend
 */
class MatrixSynapseIntegrator_Backend_DirectoryPostgresql
{
    public function updateDirectory(array $directory)
    {
        $conf = MatrixSynapseIntegrator_Config::getInstance();
        if (
            !isset($conf->{MatrixSynapseIntegrator_Config::MATRIX_DIRECTORY_DATABASE_URL})
                || !isset($conf->{MatrixSynapseIntegrator_Config::MATRIX_DIRECTORY_DATABASE_USERNAME})
                || !isset($conf->{MatrixSynapseIntegrator_Config::MATRIX_DIRECTORY_DATABASE_PASSWORD})
                || !isset($conf->{MatrixSynapseIntegrator_Config::MATRIX_SYNAPSE_TENANT_NAME})
        ) {
            throw new Tinebase_Exception(
                "Matrix Synapse config missing. 'matrixDirectoryDatabaseUrl', 'matrixDirectoryDatabaseUsername', "
                . "'matrixDirectoryDatabasePassword' and 'matrixSynapseTenantName' are required"
            );
        }

        $conn = new PDO(
            $conf->{MatrixSynapseIntegrator_Config::MATRIX_DIRECTORY_DATABASE_URL},
            $conf->{MatrixSynapseIntegrator_Config::MATRIX_DIRECTORY_DATABASE_USERNAME},
            $conf->{MatrixSynapseIntegrator_Config::MATRIX_DIRECTORY_DATABASE_PASSWORD}
        );

        $tenant = $conf->{MatrixSynapseIntegrator_Config::MATRIX_SYNAPSE_TENANT_NAME};

        $query = $conn->prepare("DELETE FROM data.directory WHERE tenant = :tenant");
        $query->execute(["tenant" => $tenant]);

        // CREATE TABLE data.directory (tenant text, idColumn text, displayNameColumn text, search text);
        // CREATE INDEX index_directory_search ON directory(search);
        $query = $conn->prepare(
            "INSERT INTO data.directory (tenant, idColumn, displayNameColumn, search) VALUES "
            . "(:tenant, :id, :displayname, :search)"
        );
        foreach ($directory as $row) {
            $query->execute([
                "tenant" => $tenant,
                "id" => $row["id"],
                "displayname" => $row["displayName"],
                "search" => $row["userInfo"],
            ]);
        }

        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) {
            Tinebase_Core::getLogger()->info('Wrote ' . sizeof($directory) . ' rows to synapse directory.');
        }
    }
}
