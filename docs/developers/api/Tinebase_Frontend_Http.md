# Tinebase_Frontend_Http  

HTTP interface to Tine

ATTENTION all public methods in this class are reachable without tine authentification
use $this->checkAuth(); if method requires authentification  

## Implements:
Tinebase_Frontend_Interface

## Extend:

Tinebase_Frontend_Http_Abstract

## Methods

| Name | Description |
|------|-------------|
|[cropImage](#tinebase_frontend_httpcropimage)|crops a image identified by an imgageURL and returns a new tempFileImage|
|[downloadPreview](#tinebase_frontend_httpdownloadpreview)|download file|
|[downloadRecordAttachment](#tinebase_frontend_httpdownloadrecordattachment)|download file attachment|
|[downloadTempfile](#tinebase_frontend_httpdownloadtempfile)|Download temp file to review|
|[getBlob](#tinebase_frontend_httpgetblob)||
|[getCustomJsFiles](#tinebase_frontend_httpgetcustomjsfiles)|dev mode custom js delivery|
|[getImage](#tinebase_frontend_httpgetimage)|downloads an image/thumbnail at a given size|
|[getJsTranslations](#tinebase_frontend_httpgetjstranslations)|returns javascript of translations for the currently configured locale|
|[getPostalXWindow](#tinebase_frontend_httpgetpostalxwindow)||
|[getServiceMap](#tinebase_frontend_httpgetservicemap)|get json-api service map|
|[getXRDS](#tinebase_frontend_httpgetxrds)|return xrds file used to autodiscover openId servers|
|[login](#tinebase_frontend_httplogin)|renders the login dialog|
|[loginFromPost](#tinebase_frontend_httploginfrompost)|login from HTTP post|
|[mainScreen](#tinebase_frontend_httpmainscreen)|display Tine 2.0 main screen|
|[openIDCLogin](#tinebase_frontend_httpopenidclogin)|openIDCLogin|
|[openId](#tinebase_frontend_httpopenid)|handle all kinds of openId requests|
|[setupRequired](#tinebase_frontend_httpsetuprequired)||
|[uploadTempFile](#tinebase_frontend_httpuploadtempfile)|receives file uploads and stores it in the file_uploads db|
|[userInfoPage](#tinebase_frontend_httpuserinfopage)|display user info page|

## Inherited methods

| Name | Description |
|------|-------------|
|__call|magic method for http api|



### Tinebase_Frontend_Http::cropImage  

**Description**

```php
public cropImage (string $imageurl, int $left, int $top, int $widht, int $height)
```

crops a image identified by an imgageURL and returns a new tempFileImage 

 

**Parameters**

* `(string) $imageurl`
: imageURL of the image to be croped  
* `(int) $left`
: left position of crop window  
* `(int) $top`
: top  position of crop window  
* `(int) $widht`
: widht  of crop window  
* `(int) $height`
: heidht of crop window  

**Return Values**

`string`

> imageURL of new temp image


<hr />


### Tinebase_Frontend_Http::downloadPreview  

**Description**

```php
public downloadPreview (string $_path, string $_appId, string $_type, int $_num, string $_revision)
```

download file 

 

**Parameters**

* `(string) $_path`
* `(string) $_appId`
* `(string) $_type`
* `(int) $_num`
* `(string) $_revision`

**Return Values**

`void`


<hr />


### Tinebase_Frontend_Http::downloadRecordAttachment  

**Description**

```php
public downloadRecordAttachment (string $nodeId, string $recordId, string $modelName)
```

download file attachment 

 

**Parameters**

* `(string) $nodeId`
* `(string) $recordId`
* `(string) $modelName`

**Return Values**

`void`


<hr />


### Tinebase_Frontend_Http::downloadTempfile  

**Description**

```php
public downloadTempfile ( $tmpfileId)
```

Download temp file to review 

 

**Parameters**

* `() $tmpfileId`

**Return Values**

`void`


<hr />


### Tinebase_Frontend_Http::getBlob  

**Description**

```php
 getBlob (void)
```

 

 

**Parameters**

`This function has no parameters.`

**Return Values**

`void`


<hr />


### Tinebase_Frontend_Http::getCustomJsFiles  

**Description**

```php
public getCustomJsFiles (void)
```

dev mode custom js delivery 

 

**Parameters**

`This function has no parameters.`

**Return Values**

`void`


<hr />


### Tinebase_Frontend_Http::getImage  

**Description**

```php
public getImage (\unknown_type $application, string $id, string $location, int $width, int $height, int $ratiomode)
```

downloads an image/thumbnail at a given size 

 

**Parameters**

* `(\unknown_type) $application`
* `(string) $id`
* `(string) $location`
* `(int) $width`
* `(int) $height`
* `(int) $ratiomode`

**Return Values**

`void`


<hr />


### Tinebase_Frontend_Http::getJsTranslations  

**Description**

```php
public getJsTranslations (string $locale, string $app)
```

returns javascript of translations for the currently configured locale 

 

**Parameters**

* `(string) $locale`
* `(string) $app`

**Return Values**

`string`

> (javascript)


<hr />


### Tinebase_Frontend_Http::getPostalXWindow  

**Description**

```php
 getPostalXWindow (void)
```

 

 

**Parameters**

`This function has no parameters.`

**Return Values**

`void`


<hr />


### Tinebase_Frontend_Http::getServiceMap  

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


### Tinebase_Frontend_Http::getXRDS  

**Description**

```php
public getXRDS (void)
```

return xrds file used to autodiscover openId servers 

 

**Parameters**

`This function has no parameters.`

**Return Values**

`void`




<hr />


### Tinebase_Frontend_Http::login  

**Description**

```php
public login (void)
```

renders the login dialog 

 

**Parameters**

`This function has no parameters.`

**Return Values**

`void`


<hr />


### Tinebase_Frontend_Http::loginFromPost  

**Description**

```php
public loginFromPost (void)
```

login from HTTP post 

redirects the tine main screen if authentication is successful  
otherwise redirects back to login url 

**Parameters**

`This function has no parameters.`

**Return Values**

`void`


<hr />


### Tinebase_Frontend_Http::mainScreen  

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


### Tinebase_Frontend_Http::openIDCLogin  

**Description**

```php
public openIDCLogin (void)
```

openIDCLogin 

 

**Parameters**

`This function has no parameters.`

**Return Values**

`bool`




<hr />


### Tinebase_Frontend_Http::openId  

**Description**

```php
public openId (void)
```

handle all kinds of openId requests 

 

**Parameters**

`This function has no parameters.`

**Return Values**

`void`




<hr />


### Tinebase_Frontend_Http::setupRequired  

**Description**

```php
 setupRequired (void)
```

 

 

**Parameters**

`This function has no parameters.`

**Return Values**

`void`


<hr />


### Tinebase_Frontend_Http::uploadTempFile  

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


### Tinebase_Frontend_Http::userInfoPage  

**Description**

```php
public userInfoPage (string $username)
```

display user info page 

in the future we can display public informations about the user here too  
currently it is only used as entry point for openId 

**Parameters**

* `(string) $username`
: the username  

**Return Values**

`void`




<hr />

