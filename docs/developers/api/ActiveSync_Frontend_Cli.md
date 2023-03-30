# ActiveSync_Frontend_Cli  

Cli frontend for ActiveSync

This class handles cli requests for the ActiveSync  



## Extend:

Tinebase_Frontend_Cli_Abstract

## Methods

| Name | Description |
|------|-------------|
|[remoteDeviceReset](#activesync_frontend_cliremotedevicereset)|remoteDeviceReset set remoteWipe flag for device (id) php tine20.php --method=ActiveSync.remoteDeviceReset id=12345|
|[resetSync](#activesync_frontend_cliresetsync)|reset sync|

## Inherited methods

| Name | Description |
|------|-------------|
|createContainer|add container|
|createDemoData|create demo data|
|getHelp|echos usage information|
|importegw14|import from egroupware|
|setContainerGrants|set container grants|
|setContainerGrantsReadOnly|setContainerGrantsReadOnly|
|updateImportExportDefinition|update or create import/export definition|



### ActiveSync_Frontend_Cli::remoteDeviceReset  

**Description**

```php
public remoteDeviceReset (\Zend_Console_Getopt $opts)
```

remoteDeviceReset set remoteWipe flag for device (id) php tine20.php --method=ActiveSync.remoteDeviceReset id=12345 

 

**Parameters**

* `(\Zend_Console_Getopt) $opts`

**Return Values**

`int`




**Throws Exceptions**


`\Tinebase_Exception_AccessDenied`


`\Tinebase_Exception_InvalidArgument`


`\Tinebase_Exception_NotFound`


<hr />


### ActiveSync_Frontend_Cli::resetSync  

**Description**

```php
public resetSync (\Zend_Console_Getopt $opts)
```

reset sync 

 

**Parameters**

* `(\Zend_Console_Getopt) $opts`

**Return Values**

`void`


<hr />

