# Calendar_Frontend_Cli  

Cli frontend for Calendar

This class handles cli requests for the Calendar  



## Extend:

Tinebase_Frontend_Cli_Abstract

## Methods

| Name | Description |
|------|-------------|
|[alarmAckReport](#calendar_frontend_clialarmackreport)|print alarm acknowledgement report (when, ip, client, user, ...)|
|[compareCalendars](#calendar_frontend_clicomparecalendars)|compare two calendars and create report of missing/different events|
|[deleteDuplicateEvents](#calendar_frontend_clideleteduplicateevents)|delete duplicate events - allowed params: organizer=ORGANIZER_CONTACTID (equals) created_by=USER_ID (equals) dtstart="2014-10-28" (after) summary=EVENT_SUMMARY (contains) -d (dry run)|
|[exportResources](#calendar_frontend_cliexportresources)|exports all resources as CSV examples: --method Calendar.exportResources --username=USER|
|[exportSharedCalendars](#calendar_frontend_cliexportsharedcalendars)|exports all shared calendars as CSV examples: --method Calendar.exportSharedCalendars --username=USER|
|[exportVCalendar](#calendar_frontend_cliexportvcalendar)|exports calendars as ICS (VCALENDAR) examples: --method Calendar.exportVCalendar --username=USER -- container_id=CALID filename=/my/export/file.ics --method Calendar.exportVCalendar --username=USER -- type=personal path=/my/export/path/|
|[fetchDataFromIcs](#calendar_frontend_clifetchdatafromics)|fetch data from ics file and put it into the matching tine20 events example: php tine20.php --method=Calendar.fetchDataFromIcs -v -d --username test --password test my.ics|
|[getAnonymousMethods](#calendar_frontend_cligetanonymousmethods)|return anonymous methods|
|[import](#calendar_frontend_cliimport)|import events|
|[importCalDavData](#calendar_frontend_cliimportcaldavdata)|import calendar events from a CalDav source|
|[importCalDavDataForUser](#calendar_frontend_cliimportcaldavdataforuser)|import calendar events from a CalDav source for one user|
|[importCalDavMultiProc](#calendar_frontend_cliimportcaldavmultiproc)|import calendars and calendar events from a CalDav source using multiple parallel processes|
|[repairAttendee](#calendar_frontend_clirepairattendee)||
|[repairDanglingDisplaycontainerEvents](#calendar_frontend_clirepairdanglingdisplaycontainerevents)|repair dangling attendee records (no displaycontainer_id)|
|[reportBigEventAttachments](#calendar_frontend_clireportbigeventattachments)||
|[restoreFallouts](#calendar_frontend_clirestorefallouts)|remove future fallout exdates for events in given calendars|
|[sharedCalendarReport](#calendar_frontend_clisharedcalendarreport)|report of shared calendars|
|[updateCalDavData](#calendar_frontend_cliupdatecaldavdata)|update calendar/events from a CalDav source using etags|
|[updateCalDavDataForUser](#calendar_frontend_cliupdatecaldavdataforuser)|update calendar/events from a CalDav source using etags for one user|
|[updateCalDavMultiProc](#calendar_frontend_cliupdatecaldavmultiproc)|update calendar events from a CalDav source using multiple parallel processes|
|[updateEventLocations](#calendar_frontend_cliupdateeventlocations)|update event locations (before 2018.11 there was no autoupdate on resource rename) allowed params: --updatePastEvents (otherwise from now on) -d (dry run)|
|[userCalendarReport](#calendar_frontend_cliusercalendarreport)||

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



### Calendar_Frontend_Cli::alarmAckReport  

**Description**

```php
public alarmAckReport (\Zend_Console_Getopt $_opts)
```

print alarm acknowledgement report (when, ip, client, user, ...) 

 

**Parameters**

* `(\Zend_Console_Getopt) $_opts`

**Return Values**

`void`


<hr />


### Calendar_Frontend_Cli::compareCalendars  

**Description**

```php
public compareCalendars (\Zend_Console_Getopt $_opts)
```

compare two calendars and create report of missing/different events 

 

**Parameters**

* `(\Zend_Console_Getopt) $_opts`

**Return Values**

`void`


<hr />


### Calendar_Frontend_Cli::deleteDuplicateEvents  

**Description**

```php
public deleteDuplicateEvents (\Zend_Console_Getopt $opts)
```

delete duplicate events - allowed params: organizer=ORGANIZER_CONTACTID (equals) created_by=USER_ID (equals) dtstart="2014-10-28" (after) summary=EVENT_SUMMARY (contains) -d (dry run) 

 

**Parameters**

* `(\Zend_Console_Getopt) $opts`

**Return Values**

`void`


<hr />


### Calendar_Frontend_Cli::exportResources  

**Description**

```php
public exportResources ( $_opts)
```

exports all resources as CSV examples: --method Calendar.exportResources --username=USER 

 

**Parameters**

* `() $_opts`

**Return Values**

`bool`




<hr />


### Calendar_Frontend_Cli::exportSharedCalendars  

**Description**

```php
public exportSharedCalendars ( $_opts)
```

exports all shared calendars as CSV examples: --method Calendar.exportSharedCalendars --username=USER 

 

**Parameters**

* `() $_opts`

**Return Values**

`bool`




<hr />


### Calendar_Frontend_Cli::exportVCalendar  

**Description**

```php
public exportVCalendar ( $_opts)
```

exports calendars as ICS (VCALENDAR) examples: --method Calendar.exportVCalendar --username=USER -- container_id=CALID filename=/my/export/file.ics --method Calendar.exportVCalendar --username=USER -- type=personal path=/my/export/path/ 

 

**Parameters**

* `() $_opts`

**Return Values**

`bool`




<hr />


### Calendar_Frontend_Cli::fetchDataFromIcs  

**Description**

```php
public fetchDataFromIcs ( $_opts)
```

fetch data from ics file and put it into the matching tine20 events example: php tine20.php --method=Calendar.fetchDataFromIcs -v -d --username test --password test my.ics 

 

**Parameters**

* `() $_opts`

**Return Values**

`int`

> NOTE: currently only alarms are supported  
  
TODO add more fields that can be processed / imported


<hr />


### Calendar_Frontend_Cli::getAnonymousMethods  

**Description**

```php
public static getAnonymousMethods (void)
```

return anonymous methods 

 

**Parameters**

`This function has no parameters.`

**Return Values**

`array`




<hr />


### Calendar_Frontend_Cli::import  

**Description**

```php
public import (\Zend_Console_Getopt $_opts)
```

import events 

 

**Parameters**

* `(\Zend_Console_Getopt) $_opts`

**Return Values**

`void`


<hr />


### Calendar_Frontend_Cli::importCalDavData  

**Description**

```php
public importCalDavData (void)
```

import calendar events from a CalDav source 

param Zend_Console_Getopt $_opts 

**Parameters**

`This function has no parameters.`

**Return Values**

`void`


<hr />


### Calendar_Frontend_Cli::importCalDavDataForUser  

**Description**

```php
public importCalDavDataForUser (void)
```

import calendar events from a CalDav source for one user 

param Zend_Console_Getopt $_opts 

**Parameters**

`This function has no parameters.`

**Return Values**

`void`


<hr />


### Calendar_Frontend_Cli::importCalDavMultiProc  

**Description**

```php
public importCalDavMultiProc (void)
```

import calendars and calendar events from a CalDav source using multiple parallel processes 

param Zend_Console_Getopt $_opts 

**Parameters**

`This function has no parameters.`

**Return Values**

`void`


<hr />


### Calendar_Frontend_Cli::repairAttendee  

**Description**

```php
 repairAttendee (void)
```

 

 

**Parameters**

`This function has no parameters.`

**Return Values**

`void`


<hr />


### Calendar_Frontend_Cli::repairDanglingDisplaycontainerEvents  

**Description**

```php
public repairDanglingDisplaycontainerEvents (void)
```

repair dangling attendee records (no displaycontainer_id) 

 

**Parameters**

`This function has no parameters.`

**Return Values**

`void`


<hr />


### Calendar_Frontend_Cli::reportBigEventAttachments  

**Description**

```php
 reportBigEventAttachments (void)
```

 

 

**Parameters**

`This function has no parameters.`

**Return Values**

`void`


<hr />


### Calendar_Frontend_Cli::restoreFallouts  

**Description**

```php
public restoreFallouts ( $_opts)
```

remove future fallout exdates for events in given calendars 

 

**Parameters**

* `() $_opts`

**Return Values**

`void`


<hr />


### Calendar_Frontend_Cli::sharedCalendarReport  

**Description**

```php
public sharedCalendarReport (\Zend_Console_Getopt $_opts)
```

report of shared calendars 

 

**Parameters**

* `(\Zend_Console_Getopt) $_opts`

**Return Values**

`int`




<hr />


### Calendar_Frontend_Cli::updateCalDavData  

**Description**

```php
public updateCalDavData (void)
```

update calendar/events from a CalDav source using etags 

param Zend_Console_Getopt $_opts 

**Parameters**

`This function has no parameters.`

**Return Values**

`void`


<hr />


### Calendar_Frontend_Cli::updateCalDavDataForUser  

**Description**

```php
public updateCalDavDataForUser (\Zend_Console_Getopt $_opts)
```

update calendar/events from a CalDav source using etags for one user 

 

**Parameters**

* `(\Zend_Console_Getopt) $_opts`

**Return Values**

`void`


<hr />


### Calendar_Frontend_Cli::updateCalDavMultiProc  

**Description**

```php
public updateCalDavMultiProc (void)
```

update calendar events from a CalDav source using multiple parallel processes 

param Zend_Console_Getopt $_opts 

**Parameters**

`This function has no parameters.`

**Return Values**

`void`


<hr />


### Calendar_Frontend_Cli::updateEventLocations  

**Description**

```php
public updateEventLocations (void)
```

update event locations (before 2018.11 there was no autoupdate on resource rename) allowed params: --updatePastEvents (otherwise from now on) -d (dry run) 

 

**Parameters**

`This function has no parameters.`

**Return Values**

`void`


<hr />


### Calendar_Frontend_Cli::userCalendarReport  

**Description**

```php
 userCalendarReport (void)
```

 

 

**Parameters**

`This function has no parameters.`

**Return Values**

`void`


<hr />

