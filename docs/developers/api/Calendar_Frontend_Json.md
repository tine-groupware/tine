# Calendar_Frontend_Json  

json interface for calendar

## Implements:
Tinebase_Frontend_Json_Interface, Tinebase_Frontend_Interface

## Extend:

Tinebase_Frontend_Json_Abstract

## Methods

| Name | Description |
|------|-------------|
|[createRecurException](#calendar_frontend_jsoncreaterecurexception)|creates an exception instance of a recurring event|
|[deleteEvents](#calendar_frontend_jsondeleteevents)|deletes existing events|
|[deleteRecurSeries](#calendar_frontend_jsondeleterecurseries)|deletes a recur series|
|[deleteResources](#calendar_frontend_jsondeleteresources)|deletes existing resources|
|[getDefaultCalendar](#calendar_frontend_jsongetdefaultcalendar)|get default addressbook|
|[getEvent](#calendar_frontend_jsongetevent)|Return a single event|
|[getFreeBusyInfo](#calendar_frontend_jsongetfreebusyinfo)||
|[getPollEvents](#calendar_frontend_jsongetpollevents)|get alternative events for poll identified by its id|
|[getResource](#calendar_frontend_jsongetresource)|Return a single resouece|
|[iMIPPrepare](#calendar_frontend_jsonimipprepare)|prepares an iMIP (RFC 6047) Message|
|[iMIPProcess](#calendar_frontend_jsonimipprocess)|process an iMIP (RFC 6047) Message|
|[importEvents](#calendar_frontend_jsonimportevents)|import contacts|
|[importRemoteEvents](#calendar_frontend_jsonimportremoteevents)|creates a scheduled import|
|[resolveGroupMembers](#calendar_frontend_jsonresolvegroupmembers)||
|[saveEvent](#calendar_frontend_jsonsaveevent)|creates/updates an event / recur|
|[saveResource](#calendar_frontend_jsonsaveresource)|creates/updates a Resource|
|[searchAttenders](#calendar_frontend_jsonsearchattenders)||
|[searchEvents](#calendar_frontend_jsonsearchevents)|Search for events matching given arguments|
|[searchFreeTime](#calendar_frontend_jsonsearchfreetime)||
|[searchResources](#calendar_frontend_jsonsearchresources)|Search for resources matching given arguments|
|[setAttenderStatus](#calendar_frontend_jsonsetattenderstatus)|sets attendee status for an attender on the given event|
|[setDefinitePollEvent](#calendar_frontend_jsonsetdefinitepollevent)|set the definite event by deleting all other alternative events and close poll|
|[updateRecurSeries](#calendar_frontend_jsonupdaterecurseries)|updated a recur series|

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



### Calendar_Frontend_Json::createRecurException  

**Description**

```php
public createRecurException (array $recordData, bool $deleteInstance, bool $deleteAllFollowing, bool $checkBusyConflicts)
```

creates an exception instance of a recurring event 

NOTE: deleting persistent exceptions is done via a normal delete action  
and handled in the controller 

**Parameters**

* `(array) $recordData`
* `(bool) $deleteInstance`
* `(bool) $deleteAllFollowing`
* `(bool) $checkBusyConflicts`

**Return Values**

`array`

> exception Event | updated baseEvent


<hr />


### Calendar_Frontend_Json::deleteEvents  

**Description**

```php
public deleteEvents (array $ids, string $range)
```

deletes existing events 

 

**Parameters**

* `(array) $ids`
* `(string) $range`

**Return Values**

`string`




<hr />


### Calendar_Frontend_Json::deleteRecurSeries  

**Description**

```php
public deleteRecurSeries (array $recordData)
```

deletes a recur series 

 

**Parameters**

* `(array) $recordData`

**Return Values**

`array`




<hr />


### Calendar_Frontend_Json::deleteResources  

**Description**

```php
public deleteResources (array $ids)
```

deletes existing resources 

 

**Parameters**

* `(array) $ids`

**Return Values**

`string`




<hr />


### Calendar_Frontend_Json::getDefaultCalendar  

**Description**

```php
public getDefaultCalendar (void)
```

get default addressbook 

 

**Parameters**

`This function has no parameters.`

**Return Values**

`array`




<hr />


### Calendar_Frontend_Json::getEvent  

**Description**

```php
public getEvent (string $id)
```

Return a single event 

 

**Parameters**

* `(string) $id`

**Return Values**

`array`

> record data


<hr />


### Calendar_Frontend_Json::getFreeBusyInfo  

**Description**

```php
 getFreeBusyInfo (void)
```

 

 

**Parameters**

`This function has no parameters.`

**Return Values**

`void`


<hr />


### Calendar_Frontend_Json::getPollEvents  

**Description**

```php
public getPollEvents (string $pollId)
```

get alternative events for poll identified by its id 

NOTE: the event itself is a alternative events as well. 

**Parameters**

* `(string) $pollId`

**Return Values**

`array`

> array results -> array of events


<hr />


### Calendar_Frontend_Json::getResource  

**Description**

```php
public getResource (string $id)
```

Return a single resouece 

 

**Parameters**

* `(string) $id`

**Return Values**

`array`

> record data


<hr />


### Calendar_Frontend_Json::iMIPPrepare  

**Description**

```php
public iMIPPrepare (array|\Calendar_Model_iMIP $iMIP)
```

prepares an iMIP (RFC 6047) Message 

 

**Parameters**

* `(array|\Calendar_Model_iMIP) $iMIP`

**Return Values**

`array`

> prepared iMIP part


<hr />


### Calendar_Frontend_Json::iMIPProcess  

**Description**

```php
public iMIPProcess (array $iMIP, string $status)
```

process an iMIP (RFC 6047) Message 

 

**Parameters**

* `(array) $iMIP`
* `(string) $status`

**Return Values**

`array`

> prepared iMIP part


<hr />


### Calendar_Frontend_Json::importEvents  

**Description**

```php
public importEvents (string $tempFileId, string $definitionId, array $importOptions, array $clientRecordData)
```

import contacts 

 

**Parameters**

* `(string) $tempFileId`
: to import  
* `(string) $definitionId`
* `(array) $importOptions`
* `(array) $clientRecordData`

**Return Values**

`array`




**Throws Exceptions**


`\Tinebase_Exception_SystemGeneric`


<hr />


### Calendar_Frontend_Json::importRemoteEvents  

**Description**

```php
public importRemoteEvents (string $remoteUrl, string $interval, array $importOptions)
```

creates a scheduled import 

 

**Parameters**

* `(string) $remoteUrl`
* `(string) $interval`
* `(array) $importOptions`

**Return Values**

`array`




<hr />


### Calendar_Frontend_Json::resolveGroupMembers  

**Description**

```php
 resolveGroupMembers (void)
```

 

 

**Parameters**

`This function has no parameters.`

**Return Values**

`void`


<hr />


### Calendar_Frontend_Json::saveEvent  

**Description**

```php
public saveEvent (array $recordData, bool $checkBusyConflicts)
```

creates/updates an event / recur 

WARNING: the Calendar_Controller_Event::create method is not conform to the regular interface!  
The parent's _save method doesn't work here! 

**Parameters**

* `(array) $recordData`
* `(bool) $checkBusyConflicts`

**Return Values**

`array`

> created/updated event


<hr />


### Calendar_Frontend_Json::saveResource  

**Description**

```php
public saveResource (array $recordData)
```

creates/updates a Resource 

 

**Parameters**

* `(array) $recordData`

**Return Values**

`array`

> created/updated Resource


**Throws Exceptions**


`\Calendar_Exception_ResourceAdminGrant`


<hr />


### Calendar_Frontend_Json::searchAttenders  

**Description**

```php
 searchAttenders (void)
```

 

 

**Parameters**

`This function has no parameters.`

**Return Values**

`void`


<hr />


### Calendar_Frontend_Json::searchEvents  

**Description**

```php
public searchEvents (array $filter, array $paging, bool $addFixedCalendars)
```

Search for events matching given arguments 

 

**Parameters**

* `(array) $filter`
* `(array) $paging`
* `(bool) $addFixedCalendars`

**Return Values**

`array`




<hr />


### Calendar_Frontend_Json::searchFreeTime  

**Description**

```php
 searchFreeTime (void)
```

 

 

**Parameters**

`This function has no parameters.`

**Return Values**

`void`


<hr />


### Calendar_Frontend_Json::searchResources  

**Description**

```php
public searchResources (array $filter, array $paging)
```

Search for resources matching given arguments 

 

**Parameters**

* `(array) $filter`
* `(array) $paging`

**Return Values**

`array`




<hr />


### Calendar_Frontend_Json::setAttenderStatus  

**Description**

```php
public setAttenderStatus (array $eventData, array $attenderData, string $authKey)
```

sets attendee status for an attender on the given event 

NOTE: for recur events we implicitly create an exceptions on demand 

**Parameters**

* `(array) $eventData`
* `(array) $attenderData`
* `(string) $authKey`

**Return Values**

`array`

> complete event


<hr />


### Calendar_Frontend_Json::setDefinitePollEvent  

**Description**

```php
public setDefinitePollEvent (array $event)
```

set the definite event by deleting all other alternative events and close poll 

 

**Parameters**

* `(array) $event`

**Return Values**

`array`

> updated event


<hr />


### Calendar_Frontend_Json::updateRecurSeries  

**Description**

```php
public updateRecurSeries (array $recordData, bool $checkBusyConflicts)
```

updated a recur series 

 

**Parameters**

* `(array) $recordData`
* `(bool) $checkBusyConflicts`

**Return Values**

`array`




<hr />

