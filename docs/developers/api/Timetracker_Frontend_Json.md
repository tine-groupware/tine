# Timetracker_Frontend_Json  

This class handles all Json requests for the Timetracker application

## Implements:
Tinebase_Frontend_Json_Interface, Tinebase_Frontend_Interface

## Extend:

Tinebase_Frontend_Json_Abstract

## Methods

| Name | Description |
|------|-------------|
|[__construct](#timetracker_frontend_json__construct)|the constructor|
|[addTimeAccountFavorite](#timetracker_frontend_jsonaddtimeaccountfavorite)|Add given timeaccount id as a users favorite|
|[deleteTimeAccountFavorite](#timetracker_frontend_jsondeletetimeaccountfavorite)|Delete given timeaccount favorite|
|[deleteTimeaccounts](#timetracker_frontend_jsondeletetimeaccounts)|deletes existing records|
|[deleteTimesheets](#timetracker_frontend_jsondeletetimesheets)|deletes existing records|
|[getTimeAccountFavoriteRegistry](#timetracker_frontend_jsongettimeaccountfavoriteregistry)|Return registry data for timeaccount favorites|
|[getTimeaccount](#timetracker_frontend_jsongettimeaccount)|Return a single record|
|[getTimesheet](#timetracker_frontend_jsongettimesheet)|Return a single record|
|[saveTimeaccount](#timetracker_frontend_jsonsavetimeaccount)|creates/updates a record|
|[saveTimesheet](#timetracker_frontend_jsonsavetimesheet)|creates/updates a record|
|[searchTimeaccounts](#timetracker_frontend_jsonsearchtimeaccounts)|Search for records matching given arguments|
|[searchTimesheets](#timetracker_frontend_jsonsearchtimesheets)|Search for records matching given arguments|

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



### Timetracker_Frontend_Json::__construct  

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


### Timetracker_Frontend_Json::addTimeAccountFavorite  

**Description**

```php
public addTimeAccountFavorite ( $timeaccountId)
```

Add given timeaccount id as a users favorite 

 

**Parameters**

* `() $timeaccountId`

**Return Values**

`\Timetracker_Model_Timeaccount`




<hr />


### Timetracker_Frontend_Json::deleteTimeAccountFavorite  

**Description**

```php
public deleteTimeAccountFavorite ( $favId)
```

Delete given timeaccount favorite 

 

**Parameters**

* `() $favId`

**Return Values**

`\Tinebase_Record_RecordSet`




**Throws Exceptions**


`\Tinebase_Exception`


<hr />


### Timetracker_Frontend_Json::deleteTimeaccounts  

**Description**

```php
public deleteTimeaccounts (array $ids)
```

deletes existing records 

 

**Parameters**

* `(array) $ids`

**Return Values**

`string`




<hr />


### Timetracker_Frontend_Json::deleteTimesheets  

**Description**

```php
public deleteTimesheets (array $ids)
```

deletes existing records 

 

**Parameters**

* `(array) $ids`

**Return Values**

`string`




<hr />


### Timetracker_Frontend_Json::getTimeAccountFavoriteRegistry  

**Description**

```php
public getTimeAccountFavoriteRegistry (void)
```

Return registry data for timeaccount favorites 

 

**Parameters**

`This function has no parameters.`

**Return Values**

`array`




**Throws Exceptions**


`\Tinebase_Exception_InvalidArgument`


<hr />


### Timetracker_Frontend_Json::getTimeaccount  

**Description**

```php
public getTimeaccount (string $id)
```

Return a single record 

 

**Parameters**

* `(string) $id`

**Return Values**

`array`

> record data


<hr />


### Timetracker_Frontend_Json::getTimesheet  

**Description**

```php
public getTimesheet (string $id)
```

Return a single record 

 

**Parameters**

* `(string) $id`

**Return Values**

`array`

> record data


<hr />


### Timetracker_Frontend_Json::saveTimeaccount  

**Description**

```php
public saveTimeaccount (array $recordData)
```

creates/updates a record 

 

**Parameters**

* `(array) $recordData`

**Return Values**

`array`

> created/updated record


<hr />


### Timetracker_Frontend_Json::saveTimesheet  

**Description**

```php
public saveTimesheet (array $recordData, array $context)
```

creates/updates a record 

 

**Parameters**

* `(array) $recordData`
* `(array) $context`

**Return Values**

`array`

> created/updated record


<hr />


### Timetracker_Frontend_Json::searchTimeaccounts  

**Description**

```php
public searchTimeaccounts (array $filter, array $paging)
```

Search for records matching given arguments 

 

**Parameters**

* `(array) $filter`
* `(array) $paging`

**Return Values**

`array`




<hr />


### Timetracker_Frontend_Json::searchTimesheets  

**Description**

```php
public searchTimesheets (array $filter, array $paging)
```

Search for records matching given arguments 

 

**Parameters**

* `(array) $filter`
* `(array) $paging`

**Return Values**

`array`




<hr />

