<?php
/**
 * Tine 2.0
 *
 * @package     HumanResources
 * @subpackage  Frontend
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * @copyright   Copyright (c) 2012 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * cli frontend for humanresources
 *
 * This class handles cli requests for the humanresources
 *
 * @package     HumanResources
 * @subpackage  Frontend
 */
class HumanResources_Frontend_Cli extends Tinebase_Frontend_Cli_Abstract
{
    /**
     * the internal name of the application
     * @var string
     */
    /**
     * import demodata default definitions
     *
     * @var array
     */
    protected $_defaultDemoDataDefinition = [
        'HumanResources_Model_Employee' => 'hr_demodata_employee_import_csv',
        'HumanResources_Model_Division' => 'hr_import_division_csv',
    ];
    protected $_applicationName = 'HumanResources';

    protected $_help = array(
        'transfer_user_accounts' => array(
            'description'   => 'Transfers all Tine 2.0. User-Accounts to Employee Records. If feast_calendar_id and working_time_model_id is given, a contract will be generated for each employee.',
                'params' => array(
                    'delete_private'        => "removes private information of the contact-record of the imported account",
                    'feast_calendar_id'     => 'the id of the contracts\' feast calendar',
                    'working_time_model_id' => 'use this working time schema for the contract',
                    'vacation_days'         => 'use this amount of vacation days for the contract'
            )
        ),
        'importEmployee' => array(
            'description'   => 'Import Employee csv file',
                'params' => array(
                    'feast_calendar_id'     => 'the id of the contracts\' feast calendar',
                    'working_time_model_id' => 'use this working time schema for the contract',
                    'vacation_days'         => 'use this amount of vacation days for the contract'
            )
        ),
        'create_missing_accounts' => array(
            'description'   => 'Creates missing accounts for this and next year, if a valid contract has been found.',
            'params' => array(
                'year'     => 'just create accounts for this year'
            )
        ),
        'set_contracts_end_date' => array(
            'description'   => 'sets the contracts end_date to the date of employment_begin of the corresponding employee, if employee has an employment end date',
        )
    );

    /**
     * creates missing accounts
     * 
     * @param Zend_Console_Getopt $_opts
     * @return integer
     */
    public function create_missing_accounts($_opts)
    {
        $args = $this->_parseArgs($_opts, array());
        $year = (isset($args['year']) || array_key_exists('year', $args)) ? intval($args['year']) : 0;
        
        if ($year !==0 && $year < 2010 || $year > 2100) {
            echo 'Please use a valid year with 4 digits!' . PHP_EOL;
            return;
        }
        
        HumanResources_Controller_Account::getInstance()->createMissingAccounts($year);
    }
    
    /**
     * transfers the account data to employee data
     * 
     * @param Zend_Console_Getopt $_opts
     * @return integer
     */
    public function transfer_user_accounts($_opts)
    {
        $args = $this->_parseArgs($_opts, array());
        $deletePrivateInfo = in_array('delete_private', $args['other']);
        $workingTimeModelId = (isset($args['working_time_model_id']) || array_key_exists('working_time_model_id', $args)) ? $args['working_time_model_id'] : NULL;
        $feastCalendarId = (isset($args['feast_calendar_id']) || array_key_exists('feast_calendar_id', $args)) ? $args['feast_calendar_id'] : NULL;
        $vacationDays = (isset($args['vacation_days']) || array_key_exists('vacation_days', $args)) ? $args['vacation_days'] : NULL;
        HumanResources_Controller_Employee::getInstance()->transferUserAccounts($deletePrivateInfo, $feastCalendarId, $workingTimeModelId, $vacationDays, TRUE);
        
        return 0;
    }
    
    /**
     * import employee data from csv file
     * 
     * @param Zend_Console_Getopt $opts
     * @return integer
     */
    public function importEmployee($opts)
    {
        // this is broken! not gona do a thing here
        return 1;

        $args = $opts->getRemainingArgs();
        array_push($args, 'definition=' . 'hr_employee_import_csv');
        if ($opts->d) {
            array_push($args, '--dry');
        }
        if ($opts->v) {
            array_push($args, '--verbose');
        }
        $opts->setArguments($args);
        
        $result = $this->_import($opts);
        
        if (empty($result)) {
            return 2;
        }
        
        foreach ($result as $filename => $importResult) {
            $importedEmployee = $this->_getImportedEmployees($importResult);
            
            foreach ($importedEmployee as $employee) {
                $this->_sanitizeEmployee($opts, $employee);
                $currentEmployee = $this->_getCurrentEmployee($employee);
                
                if ($currentEmployee) {
                    $employee = $this->_updateImportedEmployee($opts, $employee, $currentEmployee);
                } else {
                    $employee = $this->_createImportedEmployee($opts, $employee);
                }
                
                if ($opts->v) {
                    print_r($employee->toArray());
                }
                if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' '
                    . print_r($employee->toArray(), TRUE));
            }
        }
        
        return 0;
    }
    
    /**
     * returns imported employee (incl. duplicates)
     * 
     * @param array $importResult
     * @return Tinebase_Record_RecordSet
     */
    protected function _getImportedEmployees($importResult)
    {
        $result = $importResult['results'];
        $result->id = NULL;
        
        foreach ($importResult['exceptions'] as $exceptionData) {
            $exception = $exceptionData['exception'];
            if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' '
                . print_r($exception, TRUE));
            if (isset($exception['clientRecord'])) {
                $recordData = $exception['clientRecord'];
                $duplicate = (isset($exception['duplicates']) && isset($exception['duplicates'][0])) ? $exception['duplicates'][0] : NULL;
                if ($duplicate) {
                    $recordData['id'] = $duplicate['id'];
                }
                $recordData['created_by'] = $recordData['created_by']['accountId'];
                $result->addRecord(new HumanResources_Model_Employee($recordData));
            }
        }
        
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' '
            . print_r($result->toArray(), TRUE));
        
        return $result;
    }
    
    /**
     * sanitize employee data
     * - fix n_fn + street(2) + contract / cost center
     * 
     * @param Zend_Console_Getopt $opts
     * @param HumanResources_Model_Employee $employee
     */
    protected function _sanitizeEmployee($opts, $employee)
    {
        if (preg_match('/([\w\s]+), ([\w\s]+)/u', $employee->n_fn, $matches)) {
            $employee->n_fn = $matches[2] . ' ' . $matches[1];
        }
        if (! empty($employee->street2)) {
            $employee->street = $employee->street . ' ' . $employee->street2;
            $employee->street2 = '';
        }

        if (! empty($employee->countryname)) {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' '
                . 'Trying to add contract with cost center number ' . $employee->countryname);
            
            $args = $this->_parseArgs($opts);
            
            // expecting cost center number here, creating contract from data
            $costCenter = Tinebase_Controller_CostCenter::getInstance()->search(
                Tinebase_Model_Filter_FilterGroup::getFilterForModel(Tinebase_Model_CostCenter::class,
                array(array(
                    'field'    => 'number',
                    'operator' => 'equals',
                    'value'    => $employee->countryname,
                ))))->getFirstRecord();
            
            $contract = HumanResources_Controller_Employee::getInstance()->createContractDataForEmployee(array(
                'feastCalendarId'     => isset($args['feast_calendar_id']) ? $args['feast_calendar_id'] : NULL,
                'workingTimeModelId'  => isset($args['working_time_model_id']) ? $args['working_time_model_id'] : NULL,
                'vacationDays'        => isset($args['vacation_days']) ? $args['vacation_days'] : NULL,
                'startDate'           => $employee->employment_begin ? $employee->employment_begin : NULL
            ), TRUE);
            
            $employee->contracts = new Tinebase_Record_RecordSet('HumanResources_Model_Contract');
            $employee->contracts->addRecord(new HumanResources_Model_Contract($contract));
            
            if ($costCenter) {
                $employee->costcenters  = new Tinebase_Record_RecordSet('HumanResources_Model_CostCenter');
                $cc = new HumanResources_Model_CostCenter(array(
                    'cost_center_id' => $costCenter->getId(),
                    'start_date'     => $employee->employment_begin
                ));
                $employee->costcenters->addRecord($cc);
            }
            
            unset($employee->countryname);
        }
    }
    
    /**
     * get existing employee
     * 
     * @param HumanResources_Model_Employee $employee
     * @return HumanResources_Model_Employee
     */
    protected function _getCurrentEmployee($employee)
    {
        $result = NULL;
        $currentEmployee = NULL;
        
        if ($employee->getId()) {
            try {
                $currentEmployee = HumanResources_Controller_Employee::getInstance()->get($employee->getId());
                $currentEmployee->contracts = HumanResources_Controller_Contract::getInstance()->getContractsByEmployeeId($employee->getId())->toArray();
                if ($currentEmployee->n_fn === $employee->n_fn) {
                    return $currentEmployee;
                }
            } catch (Tinebase_Exception_NotFound $tenf) {
                $currentEmployee = NULL;
            }
        } 
        
        $result = HumanResources_Controller_Employee::getInstance()->search(new HumanResources_Model_EmployeeFilter(array(array(
            'field'     => 'query',
            'operator'  => 'contains',
            'value'     => $employee->n_fn
        ))))->getFirstRecord();
        
        if ($result === NULL && $currentEmployee !== NULL) {
            // use the employee with matching number if no one with the same name could be found
            $result = $currentEmployee;
        }
        
        return $result;
    }
    
    /**
     * update employee
     * 
     * @param Zend_Console_Getopt $opts
     * @param HumanResources_Model_Employee $employee
     * @param HumanResources_Model_Employee $currentEmployee
     * @return HumanResources_Model_Employee
     */
    protected function _updateImportedEmployee($opts, $employee, $currentEmployee)
    {
        if ($opts->v) {
            echo "Updating employee " . $employee->n_fn . ".\n";
        }
        // update only some fields
        $fieldsToUpdate = array(
            'postalcode',
            'locality',
            'street',
            'bday',
            'employment_begin',
            'employment_end',
            'bank_account_number',
            'bank_name',
            'bank_code_number',
            'health_insurance',
            'number',
            'contracts',
        );
        $changed = FALSE;
        foreach ($fieldsToUpdate as $field) {
            if (! empty($employee->{$field}) && $currentEmployee->{$field} !== $employee->{$field}) {
                if ($field === 'contracts' && ! empty($currentEmployee->{$field})) {
                    if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' '
                        . print_r($currentEmployee->toArray(), TRUE));
                } else {
                    $currentEmployee->{$field} = $employee->{$field};
                }
                $changed = TRUE;
            }
        }
        
        if ($opts->d || ! $changed) {
            $result = $currentEmployee;
        } else {
            $json = new HumanResources_Frontend_Json();
            $json->saveEmployee($currentEmployee->toArray());
            if (is_array($currentEmployee->contracts)) {
                $rs = new Tinebase_Record_RecordSet('HumanResources_Model_Contract');
                foreach ($currentEmployee->contracts as $contract) {
                    $rs->addRecord(new HumanResources_Model_Contract($contract));
                }
                $currentEmployee->contracts = $rs;
            }
            if (is_array($currentEmployee->costcenters)) {
                $ccrs = new Tinebase_Record_RecordSet('HumanResources_Model_CostCenter');
                foreach ($currentEmployee->costcenters as $costcenter) {
                    $ccrs->addRecord(new HumanResources_Model_CostCenter($costcenter));
                }
                $currentEmployee->costcenters = $ccrs;
            }
            $result = HumanResources_Controller_Employee::getInstance()->update($currentEmployee);
        }
        
        return $result;
    }
    
    /**
     * insert new (+check if user account exists) or update existing employee
     * 
     * @param Zend_Console_Getopt $opts
     * @param HumanResources_Model_Employee $employee
     * @return HumanResources_Model_Employee
     */
    protected function _createImportedEmployee($opts, $employee)
    {
        try {
            $user = Tinebase_User::getInstance()->getUserByProperty('accountFullName', $employee->n_fn);
            $employee->account_id = $user->getId();
            if ($opts->v) {
                echo "User " . $employee->n_fn . " found and added account_id.\n";
            }
        } catch (Tinebase_Exception_NotFound $tenf) {
            if ($opts->v) {
                echo "User " . $employee->n_fn . " no found\n";
            }
        }
        if (! $opts->d) {
            $employee = HumanResources_Controller_Employee::getInstance()->create($employee);
        }
        if ($opts->v) {
            echo "Created new employee " . $employee->n_fn . ".\n";
        }
        
        return $employee;
    }
    
    /**
     * sets the contracts end_date to the date of employment_begin of the corresponding 
     * employee, if employee has an employment end date
     */
    public function set_contracts_end_date()
    {
        $eController = HumanResources_Controller_Employee::getInstance();
        $cController = HumanResources_Controller_Contract::getInstance();
        
        $filter = new HumanResources_Model_EmployeeFilter(array(array('field' => 'is_employed', 'operator' => 'equals', 'value' => FALSE)));
        $oldEmployees = $eController->search($filter);
        
        foreach($oldEmployees as $employee) {
            $contract = $cController->getContractsByEmployeeId($employee->getId())->sort('start_date', 'DESC')->getFirstRecord();
            if ($contract) {
                $contract->end_date = $employee->employment_end;
                $cController->update($contract);
            }
        }
    }
}
