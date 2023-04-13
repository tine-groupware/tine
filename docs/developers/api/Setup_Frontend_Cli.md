# Setup_Frontend_Cli  

cli server

This class handles all requests from cli scripts  





## Methods

| Name | Description |
|------|-------------|
|[__construct](#setup_frontend_cli__construct)||
|[_migrateUtf8mb4](#setup_frontend_cli_migrateutf8mb4)||
|[authenticate](#setup_frontend_cliauthenticate)|authentication|
|[handle](#setup_frontend_clihandle)|handle request (call -ApplicationName-_Cli.-MethodName- or -ApplicationName-_Cli.getHelp)|
|[parseConfigValue](#setup_frontend_cliparseconfigvalue)|parse options|




### Setup_Frontend_Cli::__construct  

**Description**

```php
 __construct (void)
```

 

 

**Parameters**

`This function has no parameters.`

**Return Values**

`void`


<hr />


### Setup_Frontend_Cli::_migrateUtf8mb4  

**Description**

```php
 _migrateUtf8mb4 (void)
```

 

 

**Parameters**

`This function has no parameters.`

**Return Values**

`void`


<hr />


### Setup_Frontend_Cli::authenticate  

**Description**

```php
public authenticate (string $_username, string $_password)
```

authentication 

 

**Parameters**

* `(string) $_username`
* `(string) $_password`

**Return Values**

`bool`




<hr />


### Setup_Frontend_Cli::handle  

**Description**

```php
public handle (\Zend_Console_Getopt $_opts, bool $exitAfterHandle)
```

handle request (call -ApplicationName-_Cli.-MethodName- or -ApplicationName-_Cli.getHelp) 

 

**Parameters**

* `(\Zend_Console_Getopt) $_opts`
* `(bool) $exitAfterHandle`

**Return Values**

`int`




<hr />


### Setup_Frontend_Cli::parseConfigValue  

**Description**

```php
public static parseConfigValue (string $_value)
```

parse options 

 

**Parameters**

* `(string) $_value`

**Return Values**

`array|string`




<hr />

