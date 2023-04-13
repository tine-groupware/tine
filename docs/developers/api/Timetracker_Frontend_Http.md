# Timetracker_Frontend_Http  

This class handles all Http requests for the Timetracker application

## Implements:
Tinebase_Frontend_Interface

## Extend:

Tinebase_Frontend_Http_Abstract

## Methods

| Name | Description |
|------|-------------|
|[exportTimeaccounts](#timetracker_frontend_httpexporttimeaccounts)|export records matching given arguments|
|[exportTimesheets](#timetracker_frontend_httpexporttimesheets)|export records matching given arguments|

## Inherited methods

| Name | Description |
|------|-------------|
|__call|magic method for http api|



### Timetracker_Frontend_Http::exportTimeaccounts  

**Description**

```php
public exportTimeaccounts (string $filter, string $options)
```

export records matching given arguments 

 

**Parameters**

* `(string) $filter`
: json encoded  
* `(string) $options`
: format or export definition id  

**Return Values**

`void`


<hr />


### Timetracker_Frontend_Http::exportTimesheets  

**Description**

```php
public exportTimesheets (string $filter, string $options)
```

export records matching given arguments 

 

**Parameters**

* `(string) $filter`
: json encoded  
* `(string) $options`
: format or export definition id  

**Return Values**

`void`


<hr />

