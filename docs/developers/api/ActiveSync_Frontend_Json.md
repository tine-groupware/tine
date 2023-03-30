# ActiveSync_Frontend_Json  

backend class for Zend_Json_Server

This class handles all Json requests for the ActiveSync application  

## Implements:
Tinebase_Frontend_Json_Interface, Tinebase_Frontend_Interface

## Extend:

Tinebase_Frontend_Json_Abstract

## Methods

| Name | Description |
|------|-------------|
|[deleteSyncDevices](#activesync_frontend_jsondeletesyncdevices)|deletes existing records|
|[getSyncDevice](#activesync_frontend_jsongetsyncdevice)|Return a single record|
|[remoteResetDevices](#activesync_frontend_jsonremoteresetdevices)||
|[saveSyncDevice](#activesync_frontend_jsonsavesyncdevice)|creates/updates a record|
|[searchSyncDevices](#activesync_frontend_jsonsearchsyncdevices)|Search for records matching given arguments|
|[setDeviceContentFilter](#activesync_frontend_jsonsetdevicecontentfilter)|Set sync filter|

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



### ActiveSync_Frontend_Json::deleteSyncDevices  

**Description**

```php
public deleteSyncDevices (array $ids)
```

deletes existing records 

 

**Parameters**

* `(array) $ids`

**Return Values**

`string`




<hr />


### ActiveSync_Frontend_Json::getSyncDevice  

**Description**

```php
public getSyncDevice (string $id)
```

Return a single record 

 

**Parameters**

* `(string) $id`

**Return Values**

`array`

> record data


<hr />


### ActiveSync_Frontend_Json::remoteResetDevices  

**Description**

```php
 remoteResetDevices (void)
```

 

 

**Parameters**

`This function has no parameters.`

**Return Values**

`void`


<hr />


### ActiveSync_Frontend_Json::saveSyncDevice  

**Description**

```php
public saveSyncDevice (array $recordData)
```

creates/updates a record 

 

**Parameters**

* `(array) $recordData`

**Return Values**

`array`

> created/updated record


<hr />


### ActiveSync_Frontend_Json::searchSyncDevices  

**Description**

```php
public searchSyncDevices (array $filter, array $paging)
```

Search for records matching given arguments 

 

**Parameters**

* `(array) $filter`
* `(array) $paging`

**Return Values**

`array`




<hr />


### ActiveSync_Frontend_Json::setDeviceContentFilter  

**Description**

```php
public setDeviceContentFilter (string $deviceId, string $class, string $filterId)
```

Set sync filter 

 

**Parameters**

* `(string) $deviceId`
* `(string) $class`
: one of {Calendar, Contacts, Email, Tasks}  
* `(string) $filterId`

**Return Values**

`array`

> device data


<hr />

