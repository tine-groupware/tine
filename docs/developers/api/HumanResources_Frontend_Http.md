# HumanResources_Frontend_Http  

This class handles all Http requests for the HumanResources application

## Implements:
Tinebase_Frontend_Interface

## Extend:

Tinebase_Frontend_Http_Abstract

## Methods

| Name | Description |
|------|-------------|
|[exportEmployees](#humanresources_frontend_httpexportemployees)|export employee|

## Inherited methods

| Name | Description |
|------|-------------|
|__call|magic method for http api|



### HumanResources_Frontend_Http::exportEmployees  

**Description**

```php
public exportEmployees (string $filter, string $options)
```

export employee 

 

**Parameters**

* `(string) $filter`
: JSON encoded string with employee ids for multi export or employee filter  
* `(string) $options`
: format or export definition id  

**Return Values**

`void`


<hr />

