# Tasks_Frontend_Http  

backend class for Tinebase_Http_Server
This class handles all Http requests for the calendar application

## Implements:
Tinebase_Frontend_Interface

## Extend:

Tinebase_Frontend_Http_Abstract

## Methods

| Name | Description |
|------|-------------|
|[exportTasks](#tasks_frontend_httpexporttasks)|export tasks|

## Inherited methods

| Name | Description |
|------|-------------|
|__call|magic method for http api|



### Tasks_Frontend_Http::exportTasks  

**Description**

```php
public exportTasks (string $filter, string $options)
```

export tasks 

 

**Parameters**

* `(string) $filter`
: JSON encoded string with items ids for multi export or item filter  
* `(string) $options`
: format or export definition id  

**Return Values**

`void`


<hr />

