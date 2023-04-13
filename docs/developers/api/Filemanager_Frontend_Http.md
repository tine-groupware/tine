# Filemanager_Frontend_Http  

Filemanager Http frontend

This class handles all Http requests for the Filemanager application  

## Implements:
Tinebase_Frontend_Interface

## Extend:

Tinebase_Frontend_Http_Abstract

## Methods

| Name | Description |
|------|-------------|
|[downloadFile](#filemanager_frontend_httpdownloadfile)|download file|

## Inherited methods

| Name | Description |
|------|-------------|
|__call|magic method for http api|



### Filemanager_Frontend_Http::downloadFile  

**Description**

```php
public downloadFile (string $path, string $id, string $revision)
```

download file 

 

**Parameters**

* `(string) $path`
* `(string) $id`
* `(string) $revision`

**Return Values**

`void`


**Throws Exceptions**


`\Tinebase_Exception_InvalidArgument`


<hr />

