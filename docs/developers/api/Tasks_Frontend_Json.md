# Tasks_Frontend_Json  

json interface for tasks

## Implements:
Tinebase_Frontend_Json_Interface, Tinebase_Frontend_Interface

## Extend:

Tinebase_Frontend_Json_Abstract

## Methods

| Name | Description |
|------|-------------|
|[__construct](#tasks_frontend_json__construct)|the constructor|
|[deleteTasks](#tasks_frontend_jsondeletetasks)|Deletes an existing Task|
|[getDefaultContainer](#tasks_frontend_jsongetdefaultcontainer)|temporaray function to get a default container|
|[getTask](#tasks_frontend_jsongettask)|Return a single Task|
|[saveTask](#tasks_frontend_jsonsavetask)|creates/updates a Task|
|[searchTasks](#tasks_frontend_jsonsearchtasks)|Search for tasks matching given arguments|

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



### Tasks_Frontend_Json::__construct  

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


### Tasks_Frontend_Json::deleteTasks  

**Description**

```php
public deleteTasks (array $ids)
```

Deletes an existing Task 

 

**Parameters**

* `(array) $ids`

**Return Values**

`string`




<hr />


### Tasks_Frontend_Json::getDefaultContainer  

**Description**

```php
public getDefaultContainer (void)
```

temporaray function to get a default container 

 

**Parameters**

`This function has no parameters.`

**Return Values**

`array`

> container


<hr />


### Tasks_Frontend_Json::getTask  

**Description**

```php
public getTask (string $id)
```

Return a single Task 

 

**Parameters**

* `(string) $id`

**Return Values**

`\Tasks_Model_Task`

> task


<hr />


### Tasks_Frontend_Json::saveTask  

**Description**

```php
public saveTask (array $recordData)
```

creates/updates a Task 

 

**Parameters**

* `(array) $recordData`

**Return Values**

`array`

> created/updated task


<hr />


### Tasks_Frontend_Json::searchTasks  

**Description**

```php
public searchTasks (array $filter, array $paging)
```

Search for tasks matching given arguments 

 

**Parameters**

* `(array) $filter`
* `(array) $paging`

**Return Values**

`array`




<hr />

