# Crm_Frontend_Json  

This class handles all Json requests for the Crm application

## Implements:
Tinebase_Frontend_Json_Interface, Tinebase_Frontend_Interface

## Extend:

Tinebase_Frontend_Json_Abstract

## Methods

| Name | Description |
|------|-------------|
|[__construct](#crm_frontend_json__construct)|the constructor|
|[deleteLeads](#crm_frontend_jsondeleteleads)|deletes existing records|
|[getDefaultContainer](#crm_frontend_jsongetdefaultcontainer)|get default container for leads|
|[getLead](#crm_frontend_jsongetlead)|Return a single record|
|[saveLead](#crm_frontend_jsonsavelead)|creates/updates a record|
|[saveSettings](#crm_frontend_jsonsavesettings)|creates/updates settings|
|[searchLeads](#crm_frontend_jsonsearchleads)|Search for records matching given arguments|

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



### Crm_Frontend_Json::__construct  

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


### Crm_Frontend_Json::deleteLeads  

**Description**

```php
public deleteLeads (array $ids)
```

deletes existing records 

 

**Parameters**

* `(array) $ids`

**Return Values**

`string`




<hr />


### Crm_Frontend_Json::getDefaultContainer  

**Description**

```php
public getDefaultContainer (void)
```

get default container for leads 

 

**Parameters**

`This function has no parameters.`

**Return Values**

`array`




<hr />


### Crm_Frontend_Json::getLead  

**Description**

```php
public getLead (string $id)
```

Return a single record 

 

**Parameters**

* `(string) $id`

**Return Values**

`array`

> record data


<hr />


### Crm_Frontend_Json::saveLead  

**Description**

```php
public saveLead (array $recordData, bool $duplicateCheck)
```

creates/updates a record 

 

**Parameters**

* `(array) $recordData`
* `(bool) $duplicateCheck`

**Return Values**

`array`

> created/updated record


<hr />


### Crm_Frontend_Json::saveSettings  

**Description**

```php
public saveSettings (void)
```

creates/updates settings 

 

**Parameters**

`This function has no parameters.`

**Return Values**

`array`

> created/updated settings


<hr />


### Crm_Frontend_Json::searchLeads  

**Description**

```php
public searchLeads (array $filter, array $paging)
```

Search for records matching given arguments 

 

**Parameters**

* `(array) $filter`
* `(array) $paging`

**Return Values**

`array`




<hr />

