# Setup_Frontend_Http  

http server

This class handles all requests from cli scripts  

## Implements:
Tinebase_Frontend_Interface

## Extend:

Tinebase_Frontend_Http_Abstract

## Methods

| Name | Description |
|------|-------------|
|[downloadConfig](#setup_frontend_httpdownloadconfig)|download config as config file|
|[getServiceMap](#setup_frontend_httpgetservicemap)|get json-api service map|
|[mainScreen](#setup_frontend_httpmainscreen)|display Tine 2.0 main screen|
|[uploadTempFile](#setup_frontend_httpuploadtempfile)|receives file uploads and stores it in the file_uploads db|

## Inherited methods

| Name | Description |
|------|-------------|
|__call|magic method for http api|



### Setup_Frontend_Http::downloadConfig  

**Description**

```php
public downloadConfig (array $data)
```

download config as config file 

 

**Parameters**

* `(array) $data`

**Return Values**

`void`


<hr />


### Setup_Frontend_Http::getServiceMap  

**Description**

```php
public static getServiceMap (void)
```

get json-api service map 

 

**Parameters**

`This function has no parameters.`

**Return Values**

`string`




<hr />


### Setup_Frontend_Http::mainScreen  

**Description**

```php
public mainScreen (void)
```

display Tine 2.0 main screen 

 

**Parameters**

`This function has no parameters.`

**Return Values**

`void`


<hr />


### Setup_Frontend_Http::uploadTempFile  

**Description**

```php
public uploadTempFile (void)
```

receives file uploads and stores it in the file_uploads db 

 

**Parameters**

`This function has no parameters.`

**Return Values**

`void`


**Throws Exceptions**


`\Tinebase_Exception_UnexpectedValue`


`\Tinebase_Exception_NotFound`


<hr />

