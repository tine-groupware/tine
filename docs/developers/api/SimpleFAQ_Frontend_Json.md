# SimpleFAQ_Frontend_Json  

This class handles all Json requests for the SimpleFAQ application

## Implements:
Tinebase_Frontend_Json_Interface, Tinebase_Frontend_Interface

## Extend:

Tinebase_Frontend_Json_Abstract

## Methods

| Name | Description |
|------|-------------|
|[__construct](#simplefaq_frontend_json__construct)|the constructor|
|[deleteFaqs](#simplefaq_frontend_jsondeletefaqs)|deletes existing records|
|[getFaq](#simplefaq_frontend_jsongetfaq)|Return a single record|
|[getSettings](#simplefaq_frontend_jsongetsettings)|Returns settings for SimpleFAQ app|
|[saveFaq](#simplefaq_frontend_jsonsavefaq)|creates/updates a record|
|[saveSettings](#simplefaq_frontend_jsonsavesettings)|creates/updates settings|
|[searchFaqs](#simplefaq_frontend_jsonsearchfaqs)|Search for records matching given arguments|

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



### SimpleFAQ_Frontend_Json::__construct  

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


### SimpleFAQ_Frontend_Json::deleteFaqs  

**Description**

```php
public deleteFaqs (array $ids)
```

deletes existing records 

 

**Parameters**

* `(array) $ids`

**Return Values**

`string`




<hr />


### SimpleFAQ_Frontend_Json::getFaq  

**Description**

```php
public getFaq (string $id)
```

Return a single record 

 

**Parameters**

* `(string) $id`

**Return Values**

`array`

> record data


<hr />


### SimpleFAQ_Frontend_Json::getSettings  

**Description**

```php
public getSettings (void)
```

Returns settings for SimpleFAQ app 

 

**Parameters**

`This function has no parameters.`

**Return Values**

`array`

> record data


<hr />


### SimpleFAQ_Frontend_Json::saveFaq  

**Description**

```php
public saveFaq (array $recordData)
```

creates/updates a record 

 

**Parameters**

* `(array) $recordData`

**Return Values**

`array`

> created/updated record


<hr />


### SimpleFAQ_Frontend_Json::saveSettings  

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


### SimpleFAQ_Frontend_Json::searchFaqs  

**Description**

```php
public searchFaqs (array $filter, array $paging)
```

Search for records matching given arguments 

 

**Parameters**

* `(array) $filter`
* `(array) $paging`

**Return Values**

`array`




<hr />

