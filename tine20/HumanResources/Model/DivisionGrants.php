<?php
/**
 * class to handle grants
 * 
 * @package     HumanResources
 * @subpackage  Record
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2022 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */

/**
 * defines Division grants
 * 
 * @package     HumanResources
 * @subpackage  Record
 *  */
class HumanResources_Model_DivisionGrants extends Tinebase_Model_Grants
{
    public const MODEL_NAME_PART    = 'DivisionGrants';


    public const READ_OWN_DATA = 'readOwnDataGrant';
    public const READ_BASIC_EMPLOYEE_DATA = 'readBasicEmployeeDataGrant';
    public const READ_EMPLOYEE_DATA = 'readEmployeeDataGrant';
    public const UPDATE_EMPLOYEE_DATA = 'updateEmployeeDataGrant';
    public const READ_TIME_DATA = 'readTimeDataGrant';
    public const UPDATE_TIME_DATA = 'updateTimeDataGrant';
    public const CREATE_OWN_CHANGE_REQUEST = 'createOwnChangeRequestGrant';
    public const READ_CHANGE_REQUEST = 'readChangeRequestGrant';
    public const CREATE_CHANGE_REQUEST = 'createChangeRequestGrant';
    public const UPDATE_CHANGE_REQUEST = 'updateChangeRequestGrant';

    /**
     * application the record belongs to
     *
     * @var string
     */
    protected $_application = HumanResources_Config::APP_NAME;
    
    /**
     * get all possible grants
     *
     * @return  array   all container grants
     */
    public static function getAllGrants()
    {
        return [
            self::READ_OWN_DATA,
            self::READ_BASIC_EMPLOYEE_DATA,
            self::READ_EMPLOYEE_DATA,
            self::UPDATE_EMPLOYEE_DATA,
            self::READ_TIME_DATA,
            self::UPDATE_TIME_DATA,
            self::CREATE_OWN_CHANGE_REQUEST,
            self::READ_CHANGE_REQUEST,
            self::CREATE_CHANGE_REQUEST,
            self::UPDATE_CHANGE_REQUEST,
            self::GRANT_ADMIN,
        ];
    }

    protected static $_modelConfiguration = null;

    /**
     * holds the configuration object (must be declared in the concrete class)
     *
     * @var Tinebase_ModelConfiguration
     */
    protected static $_configurationObject = NULL;

    public static function getAllGrantsMC(): array
    {
        return [
            self::READ_OWN_DATA    => [
                self::LABEL         => 'Read own data', // _('Read own data')
                self::DESCRIPTION   => 'The permission to read own basic employee data, accounts, absences and working time reports.', // _('The permission to read own basic employee data, accounts, absences and working time reports.')
            ],
            self::READ_BASIC_EMPLOYEE_DATA => [
                self::LABEL         => 'Read basic employee',  // _('Read basic employee')
                self::DESCRIPTION   => 'The permission to read basic employee data for all employees in this division..',  // _('The permission to read basic employee data for all employees in this division.')
            ],
            self::READ_EMPLOYEE_DATA => [
                self::LABEL         => 'Read employee',  // _('Read employee')
                self::DESCRIPTION   => 'The permission to read complete employee data, accounts, contracts, and absence for all employees in this division.',  // _('The permission to read complete employee data, accounts, contracts, and absence for all employees in this division.')
            ],
            self::UPDATE_EMPLOYEE_DATA => [
                self::LABEL         => 'Update employee', // _('Update employee')
                self::DESCRIPTION   => 'The permission to update complete employee data, accounts, contracts, and absence for all employees in this division.', // _('The permission to update complete employee data, accounts, contracts, and absence for all employees in this division.')
            ],
            self::READ_TIME_DATA => [
                self::LABEL         => 'Read time data', // _('Read time data')
                self::DESCRIPTION   => 'The permission to read working time reports for all employees in this division.', // _('The permission to read working time reports for all employees in this division.')
            ],
            self::UPDATE_TIME_DATA => [
                self::LABEL         => 'Update time data', // _('Update time data')
                self::DESCRIPTION   => 'The permission to update working time reports for all employees in this division.', // _('The permission to update working time reports for all employees in this division.')
            ],
            self::CREATE_OWN_CHANGE_REQUEST => [
                self::LABEL         => 'Create own change requests', // _('Create own change requests')
                self::DESCRIPTION   => 'The permission to create change requests for own absences and working time reports.', // _('The permission to create change requests for own absences and working time reports.')
            ],
            self::READ_CHANGE_REQUEST => [
                self::LABEL         => 'Read change requests', // _('Read change requests')
                self::DESCRIPTION   => 'The permission to read absences and change requests for working time reports for all employees in this division.', // _('The permission to read absences and change requests for working time reports for all employees in this division.')
            ],
            self::CREATE_CHANGE_REQUEST => [
                self::LABEL         => 'Create change requests', // _('Create change requests')
                self::DESCRIPTION   => 'The permission to create absences and change requests for working time reports for all employees in this division (includes permission to read basic employee, contract and account data).', // _('The permission to create absences and change requests for working time reports for all employees in this division (includes permission to read basic employee, contract and account data).')
            ],
            self::UPDATE_CHANGE_REQUEST => [
                self::LABEL         => 'Update change requests', // _('Update change requests')
                self::DESCRIPTION   => 'The permission to update absences and change requests for working time reports for all employees in this division (includes permission to read basic employee, contract and account data).', // _('The permission to update absences and change requests for working time reports for all employees in this division (includes permission to read basic employee, contract and account data).')
            ],
            self::GRANT_ADMIN => [
                self::LABEL         => 'Admin', // _('Admin')
                self::DESCRIPTION   => 'The permission to administrate this division (implies all other grants and the grant to set grants as well).', // _('The permission to administrate this division (implies all other grants and the grant to set grants as well).')
            ],
        ];
    }

    public function setFromArray(array &$_data)
    {
        // this one first, for cascade
        if (isset($_data[self::UPDATE_EMPLOYEE_DATA]) && $_data[self::UPDATE_EMPLOYEE_DATA]) {
            $_data[self::READ_EMPLOYEE_DATA] = true;
            $_data[self::UPDATE_CHANGE_REQUEST] = true;
        }
        // first update then create so it cascades down for read basic employee data
        if (isset($_data[self::UPDATE_CHANGE_REQUEST]) && $_data[self::UPDATE_CHANGE_REQUEST]) {
            $_data[self::READ_CHANGE_REQUEST] = true;
            $_data[self::CREATE_CHANGE_REQUEST] = true;
        }
        if (isset($_data[self::CREATE_CHANGE_REQUEST]) && $_data[self::CREATE_CHANGE_REQUEST]) {
            $_data[self::READ_CHANGE_REQUEST] = true;
            $_data[self::READ_BASIC_EMPLOYEE_DATA] = true;
        }
        if (isset($_data[self::UPDATE_TIME_DATA]) && $_data[self::UPDATE_TIME_DATA]) {
            $_data[self::READ_TIME_DATA] = true;
        }
        if (isset($_data[self::READ_EMPLOYEE_DATA]) && $_data[self::READ_EMPLOYEE_DATA]) {
            $_data[self::READ_CHANGE_REQUEST] = true;
        }
        parent::setFromArray($_data);
    }
}
