# Timetracker_Frontend_Cli  

cli server for timetracker

This class handles cli requests for the timetracker  



## Extend:

Tinebase_Frontend_Cli_Abstract

## Methods

| Name | Description |
|------|-------------|
|[allBillable](#timetracker_frontend_cliallbillable)|add manage billable to all users of all timeaccounts|
|[searchDuplicateTimeaccounts](#timetracker_frontend_clisearchduplicatetimeaccounts)|search and show duplicate timeaccounts|
|[transferTimesheetsToDifferentTimeaccounts](#timetracker_frontend_clitransfertimesheetstodifferenttimeaccounts)|transfers timesheets from one timeaccount (need id in params) to another - params: timeaccountId=xxx, dryrun=0|1|

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



### Timetracker_Frontend_Cli::allBillable  

**Description**

```php
public allBillable (void)
```

add manage billable to all users of all timeaccounts 

 

**Parameters**

`This function has no parameters.`

**Return Values**

`void`




<hr />


### Timetracker_Frontend_Cli::searchDuplicateTimeaccounts  

**Description**

```php
public searchDuplicateTimeaccounts (void)
```

search and show duplicate timeaccounts 

 

**Parameters**

`This function has no parameters.`

**Return Values**

`void`




<hr />


### Timetracker_Frontend_Cli::transferTimesheetsToDifferentTimeaccounts  

**Description**

```php
public transferTimesheetsToDifferentTimeaccounts (\Zend_Console_Getopt $_opts)
```

transfers timesheets from one timeaccount (need id in params) to another - params: timeaccountId=xxx, dryrun=0|1 

 

**Parameters**

* `(\Zend_Console_Getopt) $_opts`

**Return Values**

`bool`




<hr />

