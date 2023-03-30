# HumanResources_Frontend_Json  

This class handles all Json requests for the HumanResources application

## Implements:
Tinebase_Frontend_Json_Interface, Tinebase_Frontend_Interface

## Extend:

Tinebase_Frontend_Json_Abstract

## Methods

| Name | Description |
|------|-------------|
|[__construct](#humanresources_frontend_json__construct)|the constructor|
|[calculateAllDailyWTReports](#humanresources_frontend_jsoncalculatealldailywtreports)|calculate all daily working time reports|
|[calculateAllMonthlyWTReports](#humanresources_frontend_jsoncalculateallmonthlywtreports)|calculate all monthly working time reports|
|[clockIn](#humanresources_frontend_jsonclockin)||
|[clockOut](#humanresources_frontend_jsonclockout)||
|[clockPause](#humanresources_frontend_jsonclockpause)||
|[createMissingAccounts](#humanresources_frontend_jsoncreatemissingaccounts)||
|[deleteEmployees](#humanresources_frontend_jsondeleteemployees)|deletes existing records|
|[deleteFreeTimes](#humanresources_frontend_jsondeletefreetimes)|deletes existing records|
|[deleteWorkingTime](#humanresources_frontend_jsondeleteworkingtime)|deletes existing records|
|[generateStreamReport](#humanresources_frontend_jsongeneratestreamreport)||
|[getAccount](#humanresources_frontend_jsongetaccount)|Return a single record|
|[getAttendanceRecorderDeviceStates](#humanresources_frontend_jsongetattendancerecorderdevicestates)||
|[getEmployee](#humanresources_frontend_jsongetemployee)|Return a single record|
|[getFeastAndFreeDays](#humanresources_frontend_jsongetfeastandfreedays)|returns feast days and freedays of an employee for the freetime edit dialog|
|[getFreeTime](#humanresources_frontend_jsongetfreetime)|Return a single record|
|[getStream](#humanresources_frontend_jsongetstream)|Return a single stream|
|[getWorkingTime](#humanresources_frontend_jsongetworkingtime)|Return a single record|
|[recalculateEmployeesWTReports](#humanresources_frontend_jsonrecalculateemployeeswtreports)||
|[saveAccount](#humanresources_frontend_jsonsaveaccount)|creates/updates a record|
|[saveDailyWTReport](#humanresources_frontend_jsonsavedailywtreport)||
|[saveEmployee](#humanresources_frontend_jsonsaveemployee)|creates/updates a record|
|[saveFreeTime](#humanresources_frontend_jsonsavefreetime)|creates/updates a record|
|[saveMonthlyWTReport](#humanresources_frontend_jsonsavemonthlywtreport)||
|[saveStream](#humanresources_frontend_jsonsavestream)|creates/updates a stream|
|[saveWorkingTime](#humanresources_frontend_jsonsaveworkingtime)|creates/updates a record|
|[searchAccounts](#humanresources_frontend_jsonsearchaccounts)|Search for records matching given arguments|
|[searchEmployees](#humanresources_frontend_jsonsearchemployees)|Search for records matching given arguments|
|[searchFreeTimes](#humanresources_frontend_jsonsearchfreetimes)|Search for records matching given arguments|
|[searchStreams](#humanresources_frontend_jsonsearchstreams)||
|[searchWorkingTimes](#humanresources_frontend_jsonsearchworkingtimes)|Search for records matching given arguments|
|[setConfig](#humanresources_frontend_jsonsetconfig)|Sets the config for HR|
|[wtInfo](#humanresources_frontend_jsonwtinfo)||

## Inherited methods

| Name | Description |
|------|-------------|
|__call|magic method for json api|
|addFilterModelPlugin|Add a plugin for a former filter|
|getModelsConfiguration|returns model configurations for application starter|
|getRegistryData|Returns registry data of the application.|
|getRelatableModels|Returns all relatable models for this app|
|getTemplates|get available templates by containerId|
|resolveContainersAndTags|resolve containers and tags|



### HumanResources_Frontend_Json::__construct  

**Description**

```php
public __construct (void)
```

the constructor 

 

**Parameters**

`This function has no parameters.`

**Return Values**

`void`


<hr />


### HumanResources_Frontend_Json::calculateAllDailyWTReports  

**Description**

```php
public calculateAllDailyWTReports (void)
```

calculate all daily working time reports 

 

**Parameters**

`This function has no parameters.`

**Return Values**

`void`




<hr />


### HumanResources_Frontend_Json::calculateAllMonthlyWTReports  

**Description**

```php
public calculateAllMonthlyWTReports (void)
```

calculate all monthly working time reports 

 

**Parameters**

`This function has no parameters.`

**Return Values**

`void`




<hr />


### HumanResources_Frontend_Json::clockIn  

**Description**

```php
 clockIn (void)
```

 

 

**Parameters**

`This function has no parameters.`

**Return Values**

`void`


<hr />


### HumanResources_Frontend_Json::clockOut  

**Description**

```php
 clockOut (void)
```

 

 

**Parameters**

`This function has no parameters.`

**Return Values**

`void`


<hr />


### HumanResources_Frontend_Json::clockPause  

**Description**

```php
 clockPause (void)
```

 

 

**Parameters**

`This function has no parameters.`

**Return Values**

`void`


<hr />


### HumanResources_Frontend_Json::createMissingAccounts  

**Description**

```php
 createMissingAccounts (void)
```

 

 

**Parameters**

`This function has no parameters.`

**Return Values**

`void`


<hr />


### HumanResources_Frontend_Json::deleteEmployees  

**Description**

```php
public deleteEmployees (array $ids)
```

deletes existing records 

 

**Parameters**

* `(array) $ids`

**Return Values**

`string`




<hr />


### HumanResources_Frontend_Json::deleteFreeTimes  

**Description**

```php
public deleteFreeTimes (array $ids)
```

deletes existing records 

 

**Parameters**

* `(array) $ids`

**Return Values**

`string`




<hr />


### HumanResources_Frontend_Json::deleteWorkingTime  

**Description**

```php
public deleteWorkingTime (array $ids)
```

deletes existing records 

 

**Parameters**

* `(array) $ids`

**Return Values**

`string`




<hr />


### HumanResources_Frontend_Json::generateStreamReport  

**Description**

```php
 generateStreamReport (void)
```

 

 

**Parameters**

`This function has no parameters.`

**Return Values**

`void`


<hr />


### HumanResources_Frontend_Json::getAccount  

**Description**

```php
public getAccount (string $id)
```

Return a single record 

 

**Parameters**

* `(string) $id`

**Return Values**

`array`

> record data


<hr />


### HumanResources_Frontend_Json::getAttendanceRecorderDeviceStates  

**Description**

```php
 getAttendanceRecorderDeviceStates (void)
```

 

 

**Parameters**

`This function has no parameters.`

**Return Values**

`void`


<hr />


### HumanResources_Frontend_Json::getEmployee  

**Description**

```php
public getEmployee (string $id)
```

Return a single record 

 

**Parameters**

* `(string) $id`

**Return Values**

`array`

> record data


<hr />


### HumanResources_Frontend_Json::getFeastAndFreeDays  

**Description**

```php
public getFeastAndFreeDays (string $_employeeId, int $_yearMonth, string $_freeTimeId, string $_accountId)
```

returns feast days and freedays of an employee for the freetime edit dialog 

 

**Parameters**

* `(string) $_employeeId`
* `(int) $_yearMonth`
* `(string) $_freeTimeId`
: deprecated do not used anymore!  
* `(string) $_accountId`
: used for vacation calculations (account period might differ from $_year)  

**Return Values**

`void`


<hr />


### HumanResources_Frontend_Json::getFreeTime  

**Description**

```php
public getFreeTime (string $id)
```

Return a single record 

 

**Parameters**

* `(string) $id`

**Return Values**

`array`

> record data


<hr />


### HumanResources_Frontend_Json::getStream  

**Description**

```php
public getStream (string $id)
```

Return a single stream 

 

**Parameters**

* `(string) $id`

**Return Values**

`array`

> stream data


<hr />


### HumanResources_Frontend_Json::getWorkingTime  

**Description**

```php
public getWorkingTime (string $id)
```

Return a single record 

 

**Parameters**

* `(string) $id`

**Return Values**

`array`

> record data


<hr />


### HumanResources_Frontend_Json::recalculateEmployeesWTReports  

**Description**

```php
 recalculateEmployeesWTReports (void)
```

 

 

**Parameters**

`This function has no parameters.`

**Return Values**

`void`


<hr />


### HumanResources_Frontend_Json::saveAccount  

**Description**

```php
public saveAccount (array $recordData)
```

creates/updates a record 

 

**Parameters**

* `(array) $recordData`

**Return Values**

`array`

> created/updated record


<hr />


### HumanResources_Frontend_Json::saveDailyWTReport  

**Description**

```php
 saveDailyWTReport (void)
```

 

 

**Parameters**

`This function has no parameters.`

**Return Values**

`void`


<hr />


### HumanResources_Frontend_Json::saveEmployee  

**Description**

```php
public saveEmployee (array $recordData)
```

creates/updates a record 

 

**Parameters**

* `(array) $recordData`

**Return Values**

`array`

> created/updated record


<hr />


### HumanResources_Frontend_Json::saveFreeTime  

**Description**

```php
public saveFreeTime (array $recordData)
```

creates/updates a record 

 

**Parameters**

* `(array) $recordData`

**Return Values**

`array`

> created/updated record


<hr />


### HumanResources_Frontend_Json::saveMonthlyWTReport  

**Description**

```php
 saveMonthlyWTReport (void)
```

 

 

**Parameters**

`This function has no parameters.`

**Return Values**

`void`


<hr />


### HumanResources_Frontend_Json::saveStream  

**Description**

```php
public saveStream (array $recordData)
```

creates/updates a stream 

 

**Parameters**

* `(array) $recordData`

**Return Values**

`array`

> created/updated stream


<hr />


### HumanResources_Frontend_Json::saveWorkingTime  

**Description**

```php
public saveWorkingTime (array $recordData)
```

creates/updates a record 

 

**Parameters**

* `(array) $recordData`

**Return Values**

`array`

> created/updated record


<hr />


### HumanResources_Frontend_Json::searchAccounts  

**Description**

```php
public searchAccounts (array $filter, array $paging)
```

Search for records matching given arguments 

 

**Parameters**

* `(array) $filter`
* `(array) $paging`

**Return Values**

`array`




<hr />


### HumanResources_Frontend_Json::searchEmployees  

**Description**

```php
public searchEmployees (array $filter, array $paging)
```

Search for records matching given arguments 

 

**Parameters**

* `(array) $filter`
* `(array) $paging`

**Return Values**

`array`




<hr />


### HumanResources_Frontend_Json::searchFreeTimes  

**Description**

```php
public searchFreeTimes (array $filter, array $paging)
```

Search for records matching given arguments 

 

**Parameters**

* `(array) $filter`
* `(array) $paging`

**Return Values**

`array`




<hr />


### HumanResources_Frontend_Json::searchStreams  

**Description**

```php
 searchStreams (void)
```

 

 

**Parameters**

`This function has no parameters.`

**Return Values**

`void`


<hr />


### HumanResources_Frontend_Json::searchWorkingTimes  

**Description**

```php
public searchWorkingTimes (array $filter, array $paging)
```

Search for records matching given arguments 

 

**Parameters**

* `(array) $filter`
* `(array) $paging`

**Return Values**

`array`




<hr />


### HumanResources_Frontend_Json::setConfig  

**Description**

```php
public setConfig (array $config)
```

Sets the config for HR 

 

**Parameters**

* `(array) $config`

**Return Values**

`void`


<hr />


### HumanResources_Frontend_Json::wtInfo  

**Description**

```php
 wtInfo (void)
```

 

 

**Parameters**

`This function has no parameters.`

**Return Values**

`void`


<hr />

