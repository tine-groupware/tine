<?php
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

    protected $pdo;

    protected function __construct()
    {
        $this->_applicationName = MatrixSynapseIntegrator_Config::APP_NAME;
    }

    protected function synapseListUsersWithOidc() {
        $users = [];

        $conf = MatrixSynapseIntegrator_Config::getInstance();
        if (!isset($conf->{MatrixSynapseIntegrator_Config::MATRIX_SYNAPSE_DATABASE_URL}) || !isset($conf->{MatrixSynapseIntegrator_Config::MATRIX_SYNAPSE_DATABASE_USERNAME}) || !isset($conf->{MatrixSynapseIntegrator_Config::MATRIX_SYNAPSE_DATABASE_PASSWORD})) {
            throw new Tinebase_Exception("Matrix Synapse config missing. 'matrixSynapseDatabaseUrl', 'matrixSynapseDatabaseUsername' and 'matrixSynapseDatabasePassword' are required");
        }

        $conn = new PDO($conf->{MatrixSynapseIntegrator_Config::MATRIX_SYNAPSE_DATABASE_URL}, $conf->{MatrixSynapseIntegrator_Config::MATRIX_SYNAPSE_DATABASE_USERNAME}, $conf->{MatrixSynapseIntegrator_Config::MATRIX_SYNAPSE_DATABASE_PASSWORD});

        $query = $conn->query("SELECT
                    name as id,
                    profiles.user_id as local_id,
                    external_id,
                    displayname
                FROM data.users
                LEFT JOIN data.user_external_ids ON users.name = user_external_ids.user_id
                LEFT JOIN data.profiles ON users.name = profiles.full_user_id
                WHERE auth_provider LIKE 'oidc-%';"
            );
    
        foreach ($query as $user) {
            $users[$user["external_id"]] = [
                "id" => $user["id"],
                "local_id" => $user["local_id"],
                "displayname" => $user["displayname"],
            ];
        }

        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info('Found ' . sizeof($users) . ' synapse users with oidc.');

        return $users;
    }

    protected function synapseOverwriteDirectory($directory) {
        $conf = MatrixSynapseIntegrator_Config::getInstance();
        if (!isset($conf->{MatrixSynapseIntegrator_Config::MATRIX_DIRECTORY_DATABASE_URL}) || !isset($conf->{MatrixSynapseIntegrator_Config::MATRIX_DIRECTORY_DATABASE_USERNAME}) || !isset($conf->{MatrixSynapseIntegrator_Config::MATRIX_DIRECTORY_DATABASE_PASSWORD}) || !isset($conf->{MatrixSynapseIntegrator_Config::MATRIX_SYNAPSE_TENANT_NAME})) {
            throw new Tinebase_Exception("Matrix Synapse config missing. 'matrixDirectoryDatabaseUrl', 'matrixDirectoryDatabaseUsername', 'matrixDirectoryDatabasePassword' and 'matrixSynapseTenantName' are required");
        }

        $conn = new PDO($conf->{MatrixSynapseIntegrator_Config::MATRIX_DIRECTORY_DATABASE_URL}, $conf->{MatrixSynapseIntegrator_Config::MATRIX_DIRECTORY_DATABASE_USERNAME}, $conf->{MatrixSynapseIntegrator_Config::MATRIX_DIRECTORY_DATABASE_PASSWORD});

        $tenant = $conf->{MatrixSynapseIntegrator_Config::MATRIX_SYNAPSE_TENANT_NAME};

        $query = $conn->prepare("DELETE FROM data.directory WHERE tenant = :tenant");
        $query->execute(["tenant" => $tenant]);
    
        // CREATE TABLE data.directory (tenant text, idColumn text, displayNameColumn text, search text);
        // CREATE INDEX index_directory_search ON directory(search);
        $query = $conn->prepare("INSERT INTO data.directory (tenant, idColumn, displayNameColumn, search) VALUES (:tenant, :id, :displayname, :search)");
        foreach ($directory as $row) {
            $query->execute([
                "tenant" => $tenant,
                "id" => $row["id"],
                "displayname" => $row["displayname"],
                "search" => $row["search"],
            ]);
        }

        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info('Wrote ' . sizeof($directory) . ' rows to synapse directory.');
    }

    protected function generateDirectoryFromAddressbook($synapseUsers) {
        $directory = [];
        
        $pdo = Tinebase_Core::getDb()->getConnection();

        // select from tine20_accounts and tine20_addressbook where tine20_accounts.id in synapse users
        $tineIds = array_keys($synapseUsers);
        // @phpstan-ignore-next-line
        $query = $pdo->prepare(
            "SELECT
                tine20_accounts.id,
                tine20_addressbook.matrix_id,
                tine20_accounts.login_name,
                tine20_accounts.email,
                tine20_accounts.first_name,
                tine20_accounts.last_name,
                tine20_accounts.full_name,
                tine20_accounts.display_name,
                tine20_addressbook.n_fileas,
                tine20_addressbook.n_fn,
                tine20_addressbook.n_given,
                tine20_addressbook.tel_car_normalized as tel_1,
                tine20_addressbook.tel_cell_normalized as tel_2,
                tine20_addressbook.tel_fax_normalized as tel_3,
                tine20_addressbook.tel_other_normalized as tel_4,
                tine20_addressbook.tel_pager_normalized as tel_5,
                tine20_addressbook.tel_prefer_normalized as tel_6,
                tine20_addressbook.tel_work_normalized as tel_7
            FROM " . SQL_TABLE_PREFIX . "accounts as tine20_accounts
            LEFT JOIN " . SQL_TABLE_PREFIX . "addressbook as tine20_addressbook ON tine20_accounts.contact_id = tine20_addressbook.id
            WHERE tine20_accounts.id IN (".str_repeat("?,", count($tineIds)-1) . "?".") AND tine20_addressbook.private != 1 AND tine20_accounts.visibility = 'displayed' AND tine20_accounts.status = 'enabled';"
        );
        $query->execute($tineIds);
    
        foreach ($query as $row) {
            // Join all search fields. This is ok as we do a ilike *?* match on that field.
            $search = $synapseUsers[$row["id"]]["id"] . " " . $synapseUsers[$row["id"]]["local_id"] . " " . $synapseUsers[$row["id"]]["displayname"];
            foreach (["login_name", "email", "first_name", "last_name", "full_name", "display_name", "n_fileas", "n_fn", "n_given", "tel_1", "tel_2", "tel_3", "tel_4", "tel_5", "tel_6", "tel_7"] as $key) {
                $search = $search . " " . $row[$key];
            }
    
            $directory[] = [
                "id" => $synapseUsers[$row["id"]]["id"],
                "displayname" => $synapseUsers[$row["id"]]["displayname"],
                "search" => $search
            ];
        }
    
        return $directory;
    }
    
    /**
     * Generates and updates a directory that maps users and their address information to matrix ids. The directory is used
     * by synapse* to enrich local search. Allowing users to be searched by email address, phone number or real name.
     * 
     * - Mappings are only generated for synapse users who have an oidc auth provider (whose name starts with "oidc-"). 
     * - The directory database can be fed by multiple Tine instances, Tine instances only manage their own entries.
     * - * The directory endpoint is provided by ma1ds. 
     */
    public function exportDirectory($force = false) {
        if (MatrixSynapseIntegrator_Config::getInstance()->{MatrixSynapseIntegrator_Config::MATRIX_DIRECTORY_ENABLED} || $force) {
            $synapseUsers = $this->synapseListUsersWithOidc();

            if (sizeof($synapseUsers) == 0) {
                return true;
            }

            $directory = $this->generateDirectoryFromAddressbook($synapseUsers);
            $this->synapseOverwriteDirectory($directory);
        }

        return true;
    }
}
