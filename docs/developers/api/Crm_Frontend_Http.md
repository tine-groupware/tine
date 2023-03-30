# Crm_Frontend_Http  

backend class for Tinebase_Http_Server

This class handles all Http requests for the Crm application  

## Implements:
Tinebase_Frontend_Interface

## Extend:

Tinebase_Frontend_Http_Abstract

## Methods

| Name | Description |
|------|-------------|
|[exportLeads](#crm_frontend_httpexportleads)|export lead|

## Inherited methods

| Name | Description |
|------|-------------|
|__call|magic method for http api|



### Crm_Frontend_Http::exportLeads  

**Description**

```php
public exportLeads (string $filter, string $options)
```

export lead 

 

**Parameters**

* `(string) $filter`
: JSON encoded string with lead ids for multi export  
* `(string) $options`
: format or export definition id  

**Return Values**

`void`


<hr />

