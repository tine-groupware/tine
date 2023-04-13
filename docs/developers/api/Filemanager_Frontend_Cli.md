# Filemanager_Frontend_Cli  

Cli frontend for Filemanager

This class handles cli requests for the Filemanager  



## Extend:

Tinebase_Frontend_Cli_Abstract

## Methods

| Name | Description |
|------|-------------|
|[csvExportFolder](#filemanager_frontend_clicsvexportfolder)||
|[csvExportFolderHelper](#filemanager_frontend_clicsvexportfolderhelper)|give all folder from the root directory(default /shared)|

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



### Filemanager_Frontend_Cli::csvExportFolder  

**Description**

```php
 csvExportFolder (void)
```

 

 

**Parameters**

`This function has no parameters.`

**Return Values**

`void`


<hr />


### Filemanager_Frontend_Cli::csvExportFolderHelper  

**Description**

```php
public csvExportFolderHelper (\Zend_Console_Getopt $opts, string $parentNodels, array $paths)
```

give all folder from the root directory(default /shared) 

 

**Parameters**

* `(\Zend_Console_Getopt) $opts`
* `(string) $parentNodels`
* `(array) $paths`

**Return Values**

`array`




**Throws Exceptions**


`\Tinebase_Exception_NotFound`


<hr />

