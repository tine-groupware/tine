# UserManual_Frontend_Http  

backend class for Tinebase_Http_Server

This class handles all Http requests for the UserManual application  

## Implements:
Tinebase_Frontend_Interface

## Extend:

Tinebase_Frontend_Http_Abstract

## Methods

| Name | Description |
|------|-------------|
|[get](#usermanual_frontend_httpget)|show manual page|
|[getContext](#usermanual_frontend_httpgetcontext)|show manual page by context|

## Inherited methods

| Name | Description |
|------|-------------|
|__call|magic method for http api|



### UserManual_Frontend_Http::get  

**Description**

```php
public get (string $file)
```

show manual page 

 

**Parameters**

* `(string) $file`

**Return Values**

`void`


<hr />


### UserManual_Frontend_Http::getContext  

**Description**

```php
public getContext (string $context)
```

show manual page by context 

 

**Parameters**

* `(string) $context`

**Return Values**

`void`


<hr />

