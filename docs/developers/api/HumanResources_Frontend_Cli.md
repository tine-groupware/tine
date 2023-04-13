# HumanResources_Frontend_Cli  

cli frontend for humanresources

This class handles cli requests for the humanresources  



## Extend:

Tinebase_Frontend_Cli_Abstract

## Methods

| Name | Description |
|------|-------------|
|[create_missing_accounts](#humanresources_frontend_clicreate_missing_accounts)|creates missing accounts|
|[importEmployee](#humanresources_frontend_cliimportemployee)|import employee data from csv file|
|[set_contracts_end_date](#humanresources_frontend_cliset_contracts_end_date)|sets the contracts end_date to the date of employment_begin of the corresponding employee, if employee has an employment end date|
|[transfer_user_accounts](#humanresources_frontend_clitransfer_user_accounts)|transfers the account data to employee data|

## Inherited methods

| Name | Description |
|------|-------------|
|createContainer|add container|
|createDemoData|create demo data|
|getHelp|echos usage information|
|importegw14|import from egroupware|
|setContainerGrants|set container grants|
|setContainerGrantsReadOnly|setContainerGrantsReadOnly|
|updateImportExportDefinition|update or create import/export definition|



### HumanResources_Frontend_Cli::create_missing_accounts  

**Description**

```php
public create_missing_accounts (\Zend_Console_Getopt $_opts)
```

creates missing accounts 

 

**Parameters**

* `(\Zend_Console_Getopt) $_opts`

**Return Values**

`int`




<hr />


### HumanResources_Frontend_Cli::importEmployee  

**Description**

```php
public importEmployee (\Zend_Console_Getopt $opts)
```

import employee data from csv file 

 

**Parameters**

* `(\Zend_Console_Getopt) $opts`

**Return Values**

`int`




<hr />


### HumanResources_Frontend_Cli::set_contracts_end_date  

**Description**

```php
public set_contracts_end_date (void)
```

sets the contracts end_date to the date of employment_begin of the corresponding employee, if employee has an employment end date 

 

**Parameters**

`This function has no parameters.`

**Return Values**

`void`


<hr />


### HumanResources_Frontend_Cli::transfer_user_accounts  

**Description**

```php
public transfer_user_accounts (\Zend_Console_Getopt $_opts)
```

transfers the account data to employee data 

 

**Parameters**

* `(\Zend_Console_Getopt) $_opts`

**Return Values**

`int`




<hr />

