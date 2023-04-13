# Setup_Frontend_Json  

Setup json frontend

## Implements:
Tinebase_Frontend_Interface

## Extend:

Tinebase_Frontend_Abstract

## Methods

| Name | Description |
|------|-------------|
|[__construct](#setup_frontend_json__construct)|the constructor|
|[checkConfig](#setup_frontend_jsoncheckconfig)|check config and return status|
|[deleteLicense](#setup_frontend_jsondeletelicense)|removes the current license|
|[envCheck](#setup_frontend_jsonenvcheck)|do the environment check|
|[getAllRegistryData](#setup_frontend_jsongetallregistrydata)|Returns registry data of all applications current user has access to|
|[getEmailConfig](#setup_frontend_jsongetemailconfig)|load email config data|
|[getLicense](#setup_frontend_jsongetlicense)|Get current license if available|
|[getRegistryData](#setup_frontend_jsongetregistrydata)|Returns registry data of setup|
|[installApplications](#setup_frontend_jsoninstallapplications)|install new applications|
|[loadAuthenticationData](#setup_frontend_jsonloadauthenticationdata)|load auth config data|
|[loadConfig](#setup_frontend_jsonloadconfig)|load config data from config file / default data|
|[login](#setup_frontend_jsonlogin)|authenticate user by username and password|
|[logout](#setup_frontend_jsonlogout)|destroy session|
|[saveAuthentication](#setup_frontend_jsonsaveauthentication)|Update authentication data (needs Tinebase tables to store the data)|
|[saveConfig](#setup_frontend_jsonsaveconfig)|save config data in config file|
|[saveEmailConfig](#setup_frontend_jsonsaveemailconfig)|Update email config data|
|[saveLicense](#setup_frontend_jsonsavelicense)|Saves license configuration|
|[searchApplications](#setup_frontend_jsonsearchapplications)|search for installed and installable applications|
|[uninstallApplications](#setup_frontend_jsonuninstallapplications)|uninstall applications|
|[updateApplications](#setup_frontend_jsonupdateapplications)|update existing applications|
|[uploadLicense](#setup_frontend_jsonuploadlicense)||




### Setup_Frontend_Json::__construct  

**Description**

```php
public __construct (void)
```

the constructor 

 

**Parameters**

`This function has no parameters.`

**Return Values**

`void`


<hr />


### Setup_Frontend_Json::checkConfig  

**Description**

```php
public checkConfig (void)
```

check config and return status 

 

**Parameters**

`This function has no parameters.`

**Return Values**

`array`




<hr />


### Setup_Frontend_Json::deleteLicense  

**Description**

```php
public deleteLicense (void)
```

removes the current license 

 

**Parameters**

`This function has no parameters.`

**Return Values**

`void`


<hr />


### Setup_Frontend_Json::envCheck  

**Description**

```php
public envCheck (void)
```

do the environment check 

 

**Parameters**

`This function has no parameters.`

**Return Values**

`array`




<hr />


### Setup_Frontend_Json::getAllRegistryData  

**Description**

```php
public getAllRegistryData (void)
```

Returns registry data of all applications current user has access to 

 

**Parameters**

`This function has no parameters.`

**Return Values**

`mixed`

> array 'variable name' => 'data'  
  
TODO DRY: most of this already is part of Tinebase_Frontend_Json::_getAnonymousRegistryData


<hr />


### Setup_Frontend_Json::getEmailConfig  

**Description**

```php
public getEmailConfig (void)
```

load email config data 

 

**Parameters**

`This function has no parameters.`

**Return Values**

`array`




<hr />


### Setup_Frontend_Json::getLicense  

**Description**

```php
public getLicense (void)
```

Get current license if available 

 

**Parameters**

`This function has no parameters.`

**Return Values**

`mixed`




<hr />


### Setup_Frontend_Json::getRegistryData  

**Description**

```php
public getRegistryData (void)
```

Returns registry data of setup 

. 

**Parameters**

`This function has no parameters.`

**Return Values**

`mixed`

> array 'variable name' => 'data'


<hr />


### Setup_Frontend_Json::installApplications  

**Description**

```php
public installApplications (array $applicationNames, array $)
```

install new applications 

 

**Parameters**

* `(array) $applicationNames`
: application names to install  
* `(array) $`
: | optional $options  

**Return Values**

`void`


<hr />


### Setup_Frontend_Json::loadAuthenticationData  

**Description**

```php
public loadAuthenticationData (void)
```

load auth config data 

 

**Parameters**

`This function has no parameters.`

**Return Values**

`array`




<hr />


### Setup_Frontend_Json::loadConfig  

**Description**

```php
public loadConfig (void)
```

load config data from config file / default data 

 

**Parameters**

`This function has no parameters.`

**Return Values**

`array`




<hr />


### Setup_Frontend_Json::login  

**Description**

```php
public login (string $username, string $password)
```

authenticate user by username and password 

 

**Parameters**

* `(string) $username`
: the username  
* `(string) $password`
: the password  

**Return Values**

`array`




<hr />


### Setup_Frontend_Json::logout  

**Description**

```php
public logout (void)
```

destroy session 

 

**Parameters**

`This function has no parameters.`

**Return Values**

`array`




<hr />


### Setup_Frontend_Json::saveAuthentication  

**Description**

```php
public saveAuthentication (array $data)
```

Update authentication data (needs Tinebase tables to store the data) 

Installs Tinebase if not already installed 

**Parameters**

* `(array) $data`

**Return Values**

`array`

> [success status]


<hr />


### Setup_Frontend_Json::saveConfig  

**Description**

```php
public saveConfig (array $data)
```

save config data in config file 

 

**Parameters**

* `(array) $data`

**Return Values**

`array`

> with config data


<hr />


### Setup_Frontend_Json::saveEmailConfig  

**Description**

```php
public saveEmailConfig (array $data)
```

Update email config data 

 

**Parameters**

* `(array) $data`

**Return Values**

`array`

> [success status]


<hr />


### Setup_Frontend_Json::saveLicense  

**Description**

```php
public saveLicense (string $license)
```

Saves license configuration 

 

**Parameters**

* `(string) $license`

**Return Values**

`array`




<hr />


### Setup_Frontend_Json::searchApplications  

**Description**

```php
public searchApplications (void)
```

search for installed and installable applications 

 

**Parameters**

`This function has no parameters.`

**Return Values**

`array`




<hr />


### Setup_Frontend_Json::uninstallApplications  

**Description**

```php
public uninstallApplications (array $applicationNames)
```

uninstall applications 

 

**Parameters**

* `(array) $applicationNames`
: application names to uninstall  

**Return Values**

`array`




<hr />


### Setup_Frontend_Json::updateApplications  

**Description**

```php
public updateApplications (array $applicationNames)
```

update existing applications 

 

**Parameters**

* `(array) $applicationNames`
: application names to update  

**Return Values**

`array`

> TODO remove $applicationNames param and adopt js client


<hr />


### Setup_Frontend_Json::uploadLicense  

**Description**

```php
 uploadLicense (void)
```

 

 

**Parameters**

`This function has no parameters.`

**Return Values**

`void`


<hr />

