# Tasks_Frontend_Cli  

Cli frontend for Tasks

This class handles cli requests for the Tasks  



## Extend:

Tinebase_Frontend_Cli_Abstract

## Methods

| Name | Description |
|------|-------------|
|[importCalDavCalendars](#tasks_frontend_cliimportcaldavcalendars)|import calendars from a CalDav source|
|[importCalDavDataForUser](#tasks_frontend_cliimportcaldavdataforuser)|import calendar events from a CalDav source for one user|
|[importCalDavMultiProc](#tasks_frontend_cliimportcaldavmultiproc)|import calendars and calendar events from a CalDav source using multiple parallel processes|
|[updateCalDavDataForUser](#tasks_frontend_cliupdatecaldavdataforuser)|update calendar/events from a CalDav source using etags for one user|
|[updateCalDavMultiProc](#tasks_frontend_cliupdatecaldavmultiproc)|update calendar events from a CalDav source using multiple parallel processes|

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



### Tasks_Frontend_Cli::importCalDavCalendars  

**Description**

```php
public importCalDavCalendars (void)
```

import calendars from a CalDav source 

param Zend_Console_Getopt $_opts 

**Parameters**

`This function has no parameters.`

**Return Values**

`void`


<hr />


### Tasks_Frontend_Cli::importCalDavDataForUser  

**Description**

```php
public importCalDavDataForUser (void)
```

import calendar events from a CalDav source for one user 

param Zend_Console_Getopt $_opts 

**Parameters**

`This function has no parameters.`

**Return Values**

`void`


<hr />


### Tasks_Frontend_Cli::importCalDavMultiProc  

**Description**

```php
public importCalDavMultiProc (void)
```

import calendars and calendar events from a CalDav source using multiple parallel processes 

param Zend_Console_Getopt $_opts 

**Parameters**

`This function has no parameters.`

**Return Values**

`void`


<hr />


### Tasks_Frontend_Cli::updateCalDavDataForUser  

**Description**

```php
public updateCalDavDataForUser (\Zend_Console_Getopt $_opts)
```

update calendar/events from a CalDav source using etags for one user 

 

**Parameters**

* `(\Zend_Console_Getopt) $_opts`

**Return Values**

`void`


<hr />


### Tasks_Frontend_Cli::updateCalDavMultiProc  

**Description**

```php
public updateCalDavMultiProc (void)
```

update calendar events from a CalDav source using multiple parallel processes 

param Zend_Console_Getopt $_opts 

**Parameters**

`This function has no parameters.`

**Return Values**

`void`


<hr />

