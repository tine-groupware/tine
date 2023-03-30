# Phone_Frontend_Json  

backend class for Zend_Json_Server

This class handles all Json requests for the phone application  

## Implements:
Tinebase_Frontend_Json_Interface, Tinebase_Frontend_Interface

## Extend:

Tinebase_Frontend_Json_Abstract

## Methods

| Name | Description |
|------|-------------|
|[dialNumber](#phone_frontend_jsondialnumber)|dial number|
|[getMyPhone](#phone_frontend_jsongetmyphone)|get one phone identified by phoneId|
|[saveMyPhone](#phone_frontend_jsonsavemyphone)|save user phone|
|[searchCalls](#phone_frontend_jsonsearchcalls)|Search for calls matching given arguments|
|[searchMyPhones](#phone_frontend_jsonsearchmyphones)|Search for calls matching given arguments|

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



### Phone_Frontend_Json::dialNumber  

**Description**

```php
public dialNumber (int $number, string $phoneId, string $lineId)
```

dial number 

 

**Parameters**

* `(int) $number`
: phone number  
* `(string) $phoneId`
: phone id  
* `(string) $lineId`
: phone line id  

**Return Values**

`array`




<hr />


### Phone_Frontend_Json::getMyPhone  

**Description**

```php
public getMyPhone (int $id)
```

get one phone identified by phoneId 

 

**Parameters**

* `(int) $id`

**Return Values**

`array`




<hr />


### Phone_Frontend_Json::saveMyPhone  

**Description**

```php
public saveMyPhone (array $recordData)
```

save user phone 

 

**Parameters**

* `(array) $recordData`
: an array of phone properties  

**Return Values**

`array`




<hr />


### Phone_Frontend_Json::searchCalls  

**Description**

```php
public searchCalls (array $filter, array $paging)
```

Search for calls matching given arguments 

 

**Parameters**

* `(array) $filter`
* `(array) $paging`

**Return Values**

`array`




<hr />


### Phone_Frontend_Json::searchMyPhones  

**Description**

```php
public searchMyPhones (array $filter, array $paging)
```

Search for calls matching given arguments 

 

**Parameters**

* `(array) $filter`
* `(array) $paging`

**Return Values**

`array`




<hr />

