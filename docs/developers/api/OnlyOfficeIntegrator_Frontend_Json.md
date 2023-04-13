# OnlyOfficeIntegrator_Frontend_Json  

OnlyOfficeIntegrator json fronend

## Implements:
Tinebase_Frontend_Json_Interface, Tinebase_Frontend_Interface

## Extend:

Tinebase_Frontend_Json_Abstract

## Methods

| Name | Description |
|------|-------------|
|[createNew](#onlyofficeintegrator_frontend_jsoncreatenew)||
|[exportAs](#onlyofficeintegrator_frontend_jsonexportas)||
|[getEditorConfigForAttachment](#onlyofficeintegrator_frontend_jsongeteditorconfigforattachment)||
|[getEditorConfigForNodeId](#onlyofficeintegrator_frontend_jsongeteditorconfigfornodeid)|get signed editor config it is only possible to open one specific revision of a node at a time|
|[getEditorConfigForTempFileId](#onlyofficeintegrator_frontend_jsongeteditorconfigfortempfileid)||
|[getEmbedUrlForNodeId](#onlyofficeintegrator_frontend_jsongetembedurlfornodeid)||
|[getHistory](#onlyofficeintegrator_frontend_jsongethistory)||
|[getHistoryData](#onlyofficeintegrator_frontend_jsongethistorydata)||
|[saveAs](#onlyofficeintegrator_frontend_jsonsaveas)||
|[tokenKeepAlive](#onlyofficeintegrator_frontend_jsontokenkeepalive)||
|[tokenSignOut](#onlyofficeintegrator_frontend_jsontokensignout)||
|[waitForDocumentSave](#onlyofficeintegrator_frontend_jsonwaitfordocumentsave)||

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



### OnlyOfficeIntegrator_Frontend_Json::createNew  

**Description**

```php
 createNew (void)
```

 

 

**Parameters**

`This function has no parameters.`

**Return Values**

`void`


<hr />


### OnlyOfficeIntegrator_Frontend_Json::exportAs  

**Description**

```php
 exportAs (void)
```

 

 

**Parameters**

`This function has no parameters.`

**Return Values**

`void`


<hr />


### OnlyOfficeIntegrator_Frontend_Json::getEditorConfigForAttachment  

**Description**

```php
 getEditorConfigForAttachment (void)
```

 

 

**Parameters**

`This function has no parameters.`

**Return Values**

`void`


<hr />


### OnlyOfficeIntegrator_Frontend_Json::getEditorConfigForNodeId  

**Description**

```php
public getEditorConfigForNodeId (string $nodeId, string $revision)
```

get signed editor config it is only possible to open one specific revision of a node at a time 

 

**Parameters**

* `(string) $nodeId`
* `(string) $revision`

**Return Values**

`array`




**Throws Exceptions**


`\Tinebase_Exception_AccessDenied`


`\Tinebase_Exception_InvalidArgument`


`\Tinebase_Exception_NotFound`


`\Tinebase_Exception_Record_DefinitionFailure`


`\Tinebase_Exception_Record_Validation`


`\Tinebase_Exception_SystemGeneric`


<hr />


### OnlyOfficeIntegrator_Frontend_Json::getEditorConfigForTempFileId  

**Description**

```php
 getEditorConfigForTempFileId (void)
```

 

 

**Parameters**

`This function has no parameters.`

**Return Values**

`void`


<hr />


### OnlyOfficeIntegrator_Frontend_Json::getEmbedUrlForNodeId  

**Description**

```php
 getEmbedUrlForNodeId (void)
```

 

 

**Parameters**

`This function has no parameters.`

**Return Values**

`void`


<hr />


### OnlyOfficeIntegrator_Frontend_Json::getHistory  

**Description**

```php
 getHistory (void)
```

 

 

**Parameters**

`This function has no parameters.`

**Return Values**

`void`


<hr />


### OnlyOfficeIntegrator_Frontend_Json::getHistoryData  

**Description**

```php
 getHistoryData (void)
```

 

 

**Parameters**

`This function has no parameters.`

**Return Values**

`void`


<hr />


### OnlyOfficeIntegrator_Frontend_Json::saveAs  

**Description**

```php
 saveAs (void)
```

 

 

**Parameters**

`This function has no parameters.`

**Return Values**

`void`


<hr />


### OnlyOfficeIntegrator_Frontend_Json::tokenKeepAlive  

**Description**

```php
 tokenKeepAlive (void)
```

 

 

**Parameters**

`This function has no parameters.`

**Return Values**

`void`


<hr />


### OnlyOfficeIntegrator_Frontend_Json::tokenSignOut  

**Description**

```php
 tokenSignOut (void)
```

 

 

**Parameters**

`This function has no parameters.`

**Return Values**

`void`


<hr />


### OnlyOfficeIntegrator_Frontend_Json::waitForDocumentSave  

**Description**

```php
 waitForDocumentSave (void)
```

 

 

**Parameters**

`This function has no parameters.`

**Return Values**

`void`


<hr />

