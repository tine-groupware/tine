# Felamimail_Frontend_Http  

This class handles all Http requests for the Felamimail application

## Implements:
Tinebase_Frontend_Interface

## Extend:

Tinebase_Frontend_Http_Abstract

## Methods

| Name | Description |
|------|-------------|
|[downloadAttachments](#felamimail_frontend_httpdownloadattachments)|download email attachment(s)|
|[downloadMessage](#felamimail_frontend_httpdownloadmessage)|download message|
|[getResource](#felamimail_frontend_httpgetresource)|get resource, delivers the image (audio, video) data|

## Inherited methods

| Name | Description |
|------|-------------|
|__call|magic method for http api|



### Felamimail_Frontend_Http::downloadAttachments  

**Description**

```php
public downloadAttachments ( $id, string $partIds, string $model)
```

download email attachment(s) 

if multiple partIds are given, a zip file is created for download 

**Parameters**

* `() $id`
* `(string) $partIds`
: (comma separated part ids)  
* `(string) $model`

**Return Values**

`void`


**Throws Exceptions**


`\Tinebase_Exception_InvalidArgument`


<hr />


### Felamimail_Frontend_Http::downloadMessage  

**Description**

```php
public downloadMessage (string $messageId)
```

download message 

 

**Parameters**

* `(string) $messageId`

**Return Values**

`void`


<hr />


### Felamimail_Frontend_Http::getResource  

**Description**

```php
public getResource (string $cid, string $messageId)
```

get resource, delivers the image (audio, video) data 

 

**Parameters**

* `(string) $cid`
* `(string) $messageId`

**Return Values**

`void`


<hr />

