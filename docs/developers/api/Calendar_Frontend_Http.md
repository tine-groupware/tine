# Calendar_Frontend_Http  

backend class for Tinebase_Http_Server

This class handles all Http requests for the calendar application  

## Implements:
Tinebase_Frontend_Interface

## Extend:

Tinebase_Frontend_Http_Abstract

## Methods

| Name | Description |
|------|-------------|
|[exportEvents](#calendar_frontend_httpexportevents)|export events|
|[exportResources](#calendar_frontend_httpexportresources)|export resources|

## Inherited methods

| Name | Description |
|------|-------------|
|__call|magic method for http api|



### Calendar_Frontend_Http::exportEvents  

**Description**

```php
public exportEvents (string $filter, string $options)
```

export events 

 

**Parameters**

* `(string) $filter`
: JSON encoded string with items ids for multi export or item filter  
* `(string) $options`
: format or export definition id  

**Return Values**

`void`


<hr />


### Calendar_Frontend_Http::exportResources  

**Description**

```php
public exportResources (string $filter, string $options)
```

export resources 

 

**Parameters**

* `(string) $filter`
: JSON encoded string with items ids for multi export or item filter  
* `(string) $options`
: format or export definition id  

**Return Values**

`void`


<hr />

