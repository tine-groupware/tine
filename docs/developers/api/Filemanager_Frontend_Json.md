# Filemanager_Frontend_Json  

backend class for Zend_Json_Server

This class handles all Json requests for the Filemanager application  

## Implements:
Tinebase_Frontend_Json_Interface, Tinebase_Frontend_Interface

## Extend:

Tinebase_Frontend_Json_Abstract

## Methods

| Name | Description |
|------|-------------|
|[copyNodes](#filemanager_frontend_jsoncopynodes)|copy node(s)|
|[createNode](#filemanager_frontend_jsoncreatenode)|create node|
|[createNodes](#filemanager_frontend_jsoncreatenodes)|create nodes|
|[deleteDownloadLinks](#filemanager_frontend_jsondeletedownloadlinks)|deletes existing records|
|[deleteNodes](#filemanager_frontend_jsondeletenodes)|delete node(s)|
|[getDownloadLink](#filemanager_frontend_jsongetdownloadlink)|Return a single record|
|[getFolderUsage](#filemanager_frontend_jsongetfolderusage)|Return usage array of a folder|
|[getNode](#filemanager_frontend_jsongetnode)|returns the node record|
|[getParentNodeByFilter](#filemanager_frontend_jsongetparentnodebyfilter)||
|[moveNodes](#filemanager_frontend_jsonmovenodes)|move node(s)|
|[saveDownloadLink](#filemanager_frontend_jsonsavedownloadlink)|creates/updates a record|
|[saveNode](#filemanager_frontend_jsonsavenode)|save node save node here in json fe just updates meta info (name, description, relations, customfields, tags, notes), if record already exists (after it had been uploaded)|
|[searchDownloadLinks](#filemanager_frontend_jsonsearchdownloadlinks)|Search for records matching given arguments|
|[searchNodes](#filemanager_frontend_jsonsearchnodes)|search file/directory nodes|

## Inherited methods

| Name | Description |
|------|-------------|
|__call|magic method for json api|
|addFilterModelPlugin|Add a plugin for a former filter|
|getModelsConfiguration|returns model configurations for application starter|
|getRegistryData|Returns registry data of the application.|
|getRelatableModels|Returns all relatable models for this app|
|getTemplates|get available templates by containerId|
|resolveContainersAndTags|resolve containers and tags|



### Filemanager_Frontend_Json::copyNodes  

**Description**

```php
public copyNodes (string|array $sourceFilenames, string|array $destinationFilenames, bool $forceOverwrite)
```

copy node(s) 

 

**Parameters**

* `(string|array) $sourceFilenames`
: string->single file, array->multiple  
* `(string|array) $destinationFilenames`
: string->singlefile OR directory, array->multiple files  
* `(bool) $forceOverwrite`

**Return Values**

`array`




<hr />


### Filemanager_Frontend_Json::createNode  

**Description**

```php
public createNode (array $filename, string $type, string $tempFileId, bool $forceOverwrite)
```

create node 

 

**Parameters**

* `(array) $filename`
* `(string) $type`
: mimetype  
* `(string) $tempFileId`
* `(bool) $forceOverwrite`

**Return Values**

`array`




<hr />


### Filemanager_Frontend_Json::createNodes  

**Description**

```php
public createNodes (string|array $filenames, string|array $type, string|array $tempFileIds, bool $forceOverwrite)
```

create nodes 

 

**Parameters**

* `(string|array) $filenames`
* `(string|array) $type`
: directory or mime type in case of a file  
* `(string|array) $tempFileIds`
* `(bool) $forceOverwrite`

**Return Values**

`array`




<hr />


### Filemanager_Frontend_Json::deleteDownloadLinks  

**Description**

```php
public deleteDownloadLinks (array $ids)
```

deletes existing records 

 

**Parameters**

* `(array) $ids`

**Return Values**

`array`




<hr />


### Filemanager_Frontend_Json::deleteNodes  

**Description**

```php
public deleteNodes (string|array $filenames)
```

delete node(s) 

 

**Parameters**

* `(string|array) $filenames`
: string->single file, array->multiple  

**Return Values**

`array`




<hr />


### Filemanager_Frontend_Json::getDownloadLink  

**Description**

```php
public getDownloadLink (string $id)
```

Return a single record 

 

**Parameters**

* `(string) $id`

**Return Values**

`array`

> record data


<hr />


### Filemanager_Frontend_Json::getFolderUsage  

**Description**

```php
public getFolderUsage ( $_id)
```

Return usage array of a folder 

 

**Parameters**

* `() $_id`

**Return Values**

`array`

> of folder usage


<hr />


### Filemanager_Frontend_Json::getNode  

**Description**

```php
public getNode (string $id)
```

returns the node record 

 

**Parameters**

* `(string) $id`

**Return Values**

`array`




**Throws Exceptions**


`\Tinebase_Exception_NotFound`


<hr />


### Filemanager_Frontend_Json::getParentNodeByFilter  

**Description**

```php
 getParentNodeByFilter (void)
```

 

 

**Parameters**

`This function has no parameters.`

**Return Values**

`void`


<hr />


### Filemanager_Frontend_Json::moveNodes  

**Description**

```php
public moveNodes (string|array $sourceFilenames, string|array $destinationFilenames, bool $forceOverwrite)
```

move node(s) 

 

**Parameters**

* `(string|array) $sourceFilenames`
: string->single file, array->multiple  
* `(string|array) $destinationFilenames`
: string->singlefile OR directory, array->multiple files  
* `(bool) $forceOverwrite`

**Return Values**

`array`




<hr />


### Filemanager_Frontend_Json::saveDownloadLink  

**Description**

```php
public saveDownloadLink (array $recordData)
```

creates/updates a record 

 

**Parameters**

* `(array) $recordData`

**Return Values**

`array`

> created/updated record


<hr />


### Filemanager_Frontend_Json::saveNode  

**Description**

```php
public saveNode (array $)
```

save node save node here in json fe just updates meta info (name, description, relations, customfields, tags, notes), if record already exists (after it had been uploaded) 

 

**Parameters**

* `(array) $`
: with record data  

**Return Values**

`array`




<hr />


### Filemanager_Frontend_Json::searchDownloadLinks  

**Description**

```php
public searchDownloadLinks (array $filter, array $paging)
```

Search for records matching given arguments 

 

**Parameters**

* `(array) $filter`
* `(array) $paging`

**Return Values**

`array`




<hr />


### Filemanager_Frontend_Json::searchNodes  

**Description**

```php
public searchNodes (array $filter, array $paging)
```

search file/directory nodes 

 

**Parameters**

* `(array) $filter`
* `(array) $paging`

**Return Values**

`array`




<hr />

