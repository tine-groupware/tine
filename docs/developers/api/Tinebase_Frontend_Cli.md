# Tinebase_Frontend_Cli  

cli server

This class handles all requests from cli scripts  



## Extend:

Tinebase_Frontend_Cli_Abstract

## Methods

| Name | Description |
|------|-------------|
|[addCustomfield](#tinebase_frontend_cliaddcustomfield)|add new customfield config|
|[authenticate](#tinebase_frontend_cliauthenticate)|authentication|
|[cleanAclTables](#tinebase_frontend_clicleanacltables)||
|[cleanCustomfields](#tinebase_frontend_clicleancustomfields)|cleanCustomfields|
|[cleanFileObjects](#tinebase_frontend_clicleanfileobjects)||
|[cleanModlog](#tinebase_frontend_clicleanmodlog)|clean timemachine_modlog for records that have been pruned (not deleted!)|
|[cleanNotes](#tinebase_frontend_clicleannotes)|cleanNotes: removes notes of records that have been deleted|
|[cleanRelations](#tinebase_frontend_clicleanrelations)|clean relations, set relation to deleted if at least one of the ends has been set to deleted or pruned|
|[clearDeletedFiles](#tinebase_frontend_clicleardeletedfiles)|clears deleted files from filesystem|
|[clearDeletedFilesFromDatabase](#tinebase_frontend_clicleardeletedfilesfromdatabase)|clears deleted files from the database, use -- d=false or -- d=0 to turn off dryRun. Default is -- d=true|
|[clearTable](#tinebase_frontend_clicleartable)|clear table as defined in arguments can clear the following tables: - credential_cache - access_log - async_job - temp_files - timemachine_modlog|
|[createAllDemoData](#tinebase_frontend_clicreatealldemodata)|creates demo data for all applications accepts same arguments as Tinebase_Frontend_Cli_Abstract::createDemoData and the additional argument "skipAdmin" to force no user/group/role creation|
|[duplicatePersonalContainerCheck](#tinebase_frontend_cliduplicatepersonalcontainercheck)|Delete duplicate personal container without content.|
|[executeQueueJob](#tinebase_frontend_cliexecutequeuejob)|process given queue job --jobId the queue job id to execute|
|[export](#tinebase_frontend_cliexport)|export records|
|[fileSystemCheckIndexing](#tinebase_frontend_clifilesystemcheckindexing)|checks if there are not yet indexed file objects and adds them to the index synchronously that means this can be very time consuming|
|[fileSystemCheckPreviews](#tinebase_frontend_clifilesystemcheckpreviews)|checks if there are files missing previews and creates them synchronously that means this can be very time consuming also deletes previews of files that no longer exist|
|[fileSystemRecreateAllPreviews](#tinebase_frontend_clifilesystemrecreateallpreviews)|recreates all previews|
|[fileSystemSizeRecalculation](#tinebase_frontend_clifilesystemsizerecalculation)|recalculates the revision sizes and then the folder sizes|
|[forceResync](#tinebase_frontend_cliforceresync)||
|[forceSyncTokenResync](#tinebase_frontend_cliforcesynctokenresync)|forces containers that support sync token to resync via WebDAV sync tokens|
|[handle](#tinebase_frontend_clihandle)|handle request (call -ApplicationName-_Cli.-MethodName- or -ApplicationName-_Cli.getHelp)|
|[import](#tinebase_frontend_cliimport)|import records|
|[increaseReplicationMasterId](#tinebase_frontend_cliincreasereplicationmasterid)||
|[monitoringActiveUsers](#tinebase_frontend_climonitoringactiveusers)|nagios monitoring for tine 2.0 active users|
|[monitoringCheckCache](#tinebase_frontend_climonitoringcheckcache)|nagios monitoring for tine 2.0 cache|
|[monitoringCheckConfig](#tinebase_frontend_climonitoringcheckconfig)|nagios monitoring for tine 2.0 config file|
|[monitoringCheckCron](#tinebase_frontend_climonitoringcheckcron)|nagios monitoring for tine 2.0 async cronjob run|
|[monitoringCheckDB](#tinebase_frontend_climonitoringcheckdb)|nagios monitoring for tine 2.0 database connection|
|[monitoringCheckLicense](#tinebase_frontend_climonitoringchecklicense)|nagios monitoring for tine 2.0 license|
|[monitoringCheckPreviewService](#tinebase_frontend_climonitoringcheckpreviewservice)|nagios monitoring for tine preview service integration|
|[monitoringCheckQueue](#tinebase_frontend_climonitoringcheckqueue)|nagios monitoring for tine 2.0 action queue|
|[monitoringCheckSentry](#tinebase_frontend_climonitoringchecksentry)|nagios monitoring for tine 2.0 sentry integration|
|[monitoringLoginNumber](#tinebase_frontend_climonitoringloginnumber)|nagios monitoring for successful tine 2.0 logins during the last 5 mins|
|[monitoringMailServers](#tinebase_frontend_climonitoringmailservers)|nagios monitoring for mail servers imap/smtp/sieve|
|[monitoringMaintenanceMode](#tinebase_frontend_climonitoringmaintenancemode)|nagios monitoring for tine 2.0 maintenance mode|
|[purgeDeletedRecords](#tinebase_frontend_clipurgedeletedrecords)|purge deleted records|
|[reReplicateContainer](#tinebase_frontend_clirereplicatecontainer)||
|[readModifictionLogFromMaster](#tinebase_frontend_clireadmodifictionlogfrommaster)||
|[rebuildPaths](#tinebase_frontend_clirebuildpaths)|rebuildPaths|
|[repairContainerOwner](#tinebase_frontend_clirepaircontainerowner)||
|[repairFileSystemAclNodes](#tinebase_frontend_clirepairfilesystemaclnodes)|repair acl of nodes (supports -d for dry run)|
|[repairTable](#tinebase_frontend_clirepairtable)|repair a table|
|[repairTreeIsDeletedState](#tinebase_frontend_clirepairtreeisdeletedstate)||
|[reportPreviewStatus](#tinebase_frontend_clireportpreviewstatus)||
|[resetSchedulerTasks](#tinebase_frontend_cliresetschedulertasks)|re-adds all scheduler tasks (if they are missing)|
|[restoreOrDiffEtcFileTreeFromBackupDB](#tinebase_frontend_clirestoreordiffetcfiletreefrombackupdb)|utility function to be adjusted for the needs at hand at the time of usage|
|[sanitizeFSMimeTypes](#tinebase_frontend_clisanitizefsmimetypes)||
|[sanitizeGroupListSync](#tinebase_frontend_clisanitizegrouplistsync)|default is dryRun, to make changes use "-- dryRun=[0|false]|
|[setCustomfieldAcl](#tinebase_frontend_clisetcustomfieldacl)|set customfield acl|
|[setDefaultGrantsOfPersistentFilters](#tinebase_frontend_clisetdefaultgrantsofpersistentfilters)|repair function for persistent filters (favorites) without grants: this adds default grants for those filters.|
|[setMaintenanceMode](#tinebase_frontend_clisetmaintenancemode)|set Maintenance Mode|
|[setNodeAcl](#tinebase_frontend_clisetnodeacl)|set node acl|
|[syncFileTreeFromBackupDB](#tinebase_frontend_clisyncfiletreefrombackupdb)||
|[testNotification](#tinebase_frontend_clitestnotification)||
|[transferRelations](#tinebase_frontend_clitransferrelations)|transfer relations|
|[triggerAsyncEvents](#tinebase_frontend_clitriggerasyncevents)|trigger async events (for example via cronjob)|
|[undeleteFileNodes](#tinebase_frontend_cliundeletefilenodes)|recursive undelete of file nodes - needs parent id param (only works if file objects still exist)|
|[undo](#tinebase_frontend_cliundo)||
|[undoDeprecated](#tinebase_frontend_cliundodeprecated)|undo changes to records defined by certain criteria (user, date, fields, ...)|
|[userReport](#tinebase_frontend_cliuserreport)|show user report (number of enabled, disabled, ... users)|
|[waitForActionQueueToEmpty](#tinebase_frontend_cliwaitforactionqueuetoempty)||

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



### Tinebase_Frontend_Cli::addCustomfield  

**Description**

```php
public addCustomfield ( $_opts)
```

add new customfield config 

example:  
$ php tine20.php --method=Tinebase.addCustomfield -- \  
application="Addressbook" model="Addressbook_Model_Contact" name="datefield" \  
definition='{"label":"Date","type":"datetime", "uiconfig": {"group":"Dates", "order": 30}}' 

**Parameters**

* `() $_opts`

**Return Values**

`bool`

> success


<hr />


### Tinebase_Frontend_Cli::authenticate  

**Description**

```php
public authenticate (string $_username, string $_password)
```

authentication 

 

**Parameters**

* `(string) $_username`
* `(string) $_password`

**Return Values**

`void`


<hr />


### Tinebase_Frontend_Cli::cleanAclTables  

**Description**

```php
 cleanAclTables (void)
```

 

 

**Parameters**

`This function has no parameters.`

**Return Values**

`void`


<hr />


### Tinebase_Frontend_Cli::cleanCustomfields  

**Description**

```php
public cleanCustomfields (void)
```

cleanCustomfields 

 

**Parameters**

`This function has no parameters.`

**Return Values**

`void`


<hr />


### Tinebase_Frontend_Cli::cleanFileObjects  

**Description**

```php
 cleanFileObjects (void)
```

 

 

**Parameters**

`This function has no parameters.`

**Return Values**

`void`


<hr />


### Tinebase_Frontend_Cli::cleanModlog  

**Description**

```php
public cleanModlog (void)
```

clean timemachine_modlog for records that have been pruned (not deleted!) 

 

**Parameters**

`This function has no parameters.`

**Return Values**

`void`


<hr />


### Tinebase_Frontend_Cli::cleanNotes  

**Description**

```php
public cleanNotes (void)
```

cleanNotes: removes notes of records that have been deleted 

-- purge=1 param also removes redundant notes (empty updates + create notes)  
supports dry run (-d) 

**Parameters**

`This function has no parameters.`

**Return Values**

`void`


<hr />


### Tinebase_Frontend_Cli::cleanRelations  

**Description**

```php
public cleanRelations (void)
```

clean relations, set relation to deleted if at least one of the ends has been set to deleted or pruned 

 

**Parameters**

`This function has no parameters.`

**Return Values**

`void`


<hr />


### Tinebase_Frontend_Cli::clearDeletedFiles  

**Description**

```php
public clearDeletedFiles (void)
```

clears deleted files from filesystem 

 

**Parameters**

`This function has no parameters.`

**Return Values**

`int`




<hr />


### Tinebase_Frontend_Cli::clearDeletedFilesFromDatabase  

**Description**

```php
public clearDeletedFilesFromDatabase (\Zend_Console_Getopt $opts)
```

clears deleted files from the database, use -- d=false or -- d=0 to turn off dryRun. Default is -- d=true 

 

**Parameters**

* `(\Zend_Console_Getopt) $opts`

**Return Values**

`int`




<hr />


### Tinebase_Frontend_Cli::clearTable  

**Description**

```php
public clearTable ( $_opts)
```

clear table as defined in arguments can clear the following tables: - credential_cache - access_log - async_job - temp_files - timemachine_modlog 

if param date is given (date=2010-09-17), all records before this date are deleted (if the table has a date field) 

**Parameters**

* `() $_opts`

**Return Values**

`bool`

> success


<hr />


### Tinebase_Frontend_Cli::createAllDemoData  

**Description**

```php
public createAllDemoData (\Zend_Console_Getopt $_opts)
```

creates demo data for all applications accepts same arguments as Tinebase_Frontend_Cli_Abstract::createDemoData and the additional argument "skipAdmin" to force no user/group/role creation 

 

**Parameters**

* `(\Zend_Console_Getopt) $_opts`

**Return Values**

`void`


<hr />


### Tinebase_Frontend_Cli::duplicatePersonalContainerCheck  

**Description**

```php
public duplicatePersonalContainerCheck (\Zend_Console_Getopt $opts)
```

Delete duplicate personal container without content. 

e.g. php tine20.php --method=Tinebase.duplicatePersonalContainerCheck app=Addressbook [-d] 

**Parameters**

* `(\Zend_Console_Getopt) $opts`

**Return Values**

`void`


**Throws Exceptions**


`\Tinebase_Exception_AccessDenied`


`\Tinebase_Exception_InvalidArgument`


`\Tinebase_Exception_NotFound`


`\Tinebase_Exception_Record_SystemContainer`


<hr />


### Tinebase_Frontend_Cli::executeQueueJob  

**Description**

```php
public executeQueueJob (\Zend_Console_Getopt $_opts)
```

process given queue job --jobId the queue job id to execute 

 

**Parameters**

* `(\Zend_Console_Getopt) $_opts`

**Return Values**

`bool`

> success


**Throws Exceptions**


`\Tinebase_Exception_InvalidArgument`


<hr />


### Tinebase_Frontend_Cli::export  

**Description**

```php
public export (\Zend_Console_Getopt $_opts)
```

export records 

usage: method=Tinebase.export -- definition=DEFINITION_NAME 

**Parameters**

* `(\Zend_Console_Getopt) $_opts`

**Return Values**

`int`




<hr />


### Tinebase_Frontend_Cli::fileSystemCheckIndexing  

**Description**

```php
public fileSystemCheckIndexing (void)
```

checks if there are not yet indexed file objects and adds them to the index synchronously that means this can be very time consuming 

 

**Parameters**

`This function has no parameters.`

**Return Values**

`int`




<hr />


### Tinebase_Frontend_Cli::fileSystemCheckPreviews  

**Description**

```php
public fileSystemCheckPreviews (void)
```

checks if there are files missing previews and creates them synchronously that means this can be very time consuming also deletes previews of files that no longer exist 

 

**Parameters**

`This function has no parameters.`

**Return Values**

`int`




<hr />


### Tinebase_Frontend_Cli::fileSystemRecreateAllPreviews  

**Description**

```php
public fileSystemRecreateAllPreviews (void)
```

recreates all previews 

 

**Parameters**

`This function has no parameters.`

**Return Values**

`int`




<hr />


### Tinebase_Frontend_Cli::fileSystemSizeRecalculation  

**Description**

```php
public fileSystemSizeRecalculation (void)
```

recalculates the revision sizes and then the folder sizes 

 

**Parameters**

`This function has no parameters.`

**Return Values**

`int`




<hr />


### Tinebase_Frontend_Cli::forceResync  

**Description**

```php
 forceResync (void)
```

 

 

**Parameters**

`This function has no parameters.`

**Return Values**

`void`


<hr />


### Tinebase_Frontend_Cli::forceSyncTokenResync  

**Description**

```php
public forceSyncTokenResync (\Zend_Console_Getopt $_opts)
```

forces containers that support sync token to resync via WebDAV sync tokens 

this will DELETE the complete content history for the affected containers  
this will increate the sequence for all records in all affected containers  
this will increate the sequence of all affected containers  
  
this will cause 2 BadRequest responses to sync token requests  
the first one as soon as the client notices that something changed and sends a sync token request  
eventually the client receives a false sync token (as we increased content sequence, but we dont have a content history entry)  
eventually not (if something really changed in the calendar in the meantime)  
  
in case the client got a fake sync token, the clients next sync token request (once something really changed) will fail again  
after something really changed valid sync tokens will be handed out again 

**Parameters**

* `(\Zend_Console_Getopt) $_opts`

**Return Values**

`void`


<hr />


### Tinebase_Frontend_Cli::handle  

**Description**

```php
public handle (\Zend_Console_Getopt $_opts)
```

handle request (call -ApplicationName-_Cli.-MethodName- or -ApplicationName-_Cli.getHelp) 

 

**Parameters**

* `(\Zend_Console_Getopt) $_opts`

**Return Values**

`bool|int`

> success


<hr />


### Tinebase_Frontend_Cli::import  

**Description**

```php
public import (\Zend_Console_Getopt $_opts)
```

import records 

 

**Parameters**

* `(\Zend_Console_Getopt) $_opts`

**Return Values**

`int`




<hr />


### Tinebase_Frontend_Cli::increaseReplicationMasterId  

**Description**

```php
 increaseReplicationMasterId (void)
```

 

 

**Parameters**

`This function has no parameters.`

**Return Values**

`void`


<hr />


### Tinebase_Frontend_Cli::monitoringActiveUsers  

**Description**

```php
public monitoringActiveUsers (void)
```

nagios monitoring for tine 2.0 active users 

 

**Parameters**

`This function has no parameters.`

**Return Values**

`\number`




<hr />


### Tinebase_Frontend_Cli::monitoringCheckCache  

**Description**

```php
public monitoringCheckCache (void)
```

nagios monitoring for tine 2.0 cache 

 

**Parameters**

`This function has no parameters.`

**Return Values**

`int`




<hr />


### Tinebase_Frontend_Cli::monitoringCheckConfig  

**Description**

```php
public monitoringCheckConfig (void)
```

nagios monitoring for tine 2.0 config file 

 

**Parameters**

`This function has no parameters.`

**Return Values**

`int`




<hr />


### Tinebase_Frontend_Cli::monitoringCheckCron  

**Description**

```php
public monitoringCheckCron (void)
```

nagios monitoring for tine 2.0 async cronjob run 

 

**Parameters**

`This function has no parameters.`

**Return Values**

`int`




<hr />


### Tinebase_Frontend_Cli::monitoringCheckDB  

**Description**

```php
public monitoringCheckDB (void)
```

nagios monitoring for tine 2.0 database connection 

 

**Parameters**

`This function has no parameters.`

**Return Values**

`int`




<hr />


### Tinebase_Frontend_Cli::monitoringCheckLicense  

**Description**

```php
public monitoringCheckLicense (void)
```

nagios monitoring for tine 2.0 license 

 

**Parameters**

`This function has no parameters.`

**Return Values**

`int`




<hr />


### Tinebase_Frontend_Cli::monitoringCheckPreviewService  

**Description**

```php
public monitoringCheckPreviewService (void)
```

nagios monitoring for tine preview service integration 

 

**Parameters**

`This function has no parameters.`

**Return Values**

`int`

> TODO also display on status page  
TODO use tine logic to test docservice?  
TODO catch output


<hr />


### Tinebase_Frontend_Cli::monitoringCheckQueue  

**Description**

```php
public monitoringCheckQueue (void)
```

nagios monitoring for tine 2.0 action queue 

 

**Parameters**

`This function has no parameters.`

**Return Values**

`int`




<hr />


### Tinebase_Frontend_Cli::monitoringCheckSentry  

**Description**

```php
public monitoringCheckSentry (void)
```

nagios monitoring for tine 2.0 sentry integration 

 

**Parameters**

`This function has no parameters.`

**Return Values**

`int`




<hr />


### Tinebase_Frontend_Cli::monitoringLoginNumber  

**Description**

```php
public monitoringLoginNumber (void)
```

nagios monitoring for successful tine 2.0 logins during the last 5 mins 

 

**Parameters**

`This function has no parameters.`

**Return Values**

`\number`




<hr />


### Tinebase_Frontend_Cli::monitoringMailServers  

**Description**

```php
public monitoringMailServers (void)
```

nagios monitoring for mail servers imap/smtp/sieve 

 

**Parameters**

`This function has no parameters.`

**Return Values**

`int`




<hr />


### Tinebase_Frontend_Cli::monitoringMaintenanceMode  

**Description**

```php
public monitoringMaintenanceMode (void)
```

nagios monitoring for tine 2.0 maintenance mode 

 

**Parameters**

`This function has no parameters.`

**Return Values**

`int`




<hr />


### Tinebase_Frontend_Cli::purgeDeletedRecords  

**Description**

```php
public purgeDeletedRecords ( $_opts)
```

purge deleted records 

if param date is given (for example: date=2010-09-17), all records before this date are deleted (if the table has a date field)  
if table names are given, purge only records from this tables 

**Parameters**

* `() $_opts`

**Return Values**

`bool`

> success  
  
TODO move purge logic to applications, purge Tinebase tables at the end


<hr />


### Tinebase_Frontend_Cli::reReplicateContainer  

**Description**

```php
 reReplicateContainer (void)
```

 

 

**Parameters**

`This function has no parameters.`

**Return Values**

`void`


<hr />


### Tinebase_Frontend_Cli::readModifictionLogFromMaster  

**Description**

```php
 readModifictionLogFromMaster (void)
```

 

 

**Parameters**

`This function has no parameters.`

**Return Values**

`void`


<hr />


### Tinebase_Frontend_Cli::rebuildPaths  

**Description**

```php
public rebuildPaths (\Zend_Console_Getopt $opts)
```

rebuildPaths 

 

**Parameters**

* `(\Zend_Console_Getopt) $opts`

**Return Values**

`int`

> success


<hr />


### Tinebase_Frontend_Cli::repairContainerOwner  

**Description**

```php
 repairContainerOwner (void)
```

 

 

**Parameters**

`This function has no parameters.`

**Return Values**

`void`


<hr />


### Tinebase_Frontend_Cli::repairFileSystemAclNodes  

**Description**

```php
public repairFileSystemAclNodes (\Zend_Console_Getopt $opts)
```

repair acl of nodes (supports -d for dry run) 

 

**Parameters**

* `(\Zend_Console_Getopt) $opts`

**Return Values**

`int`




**Throws Exceptions**


`\Tinebase_Exception_InvalidArgument`


`\Tinebase_Exception_NotFound`


`\Tinebase_Exception_Record_Validation`


`\Zend_Db_Statement_Exception`


<hr />


### Tinebase_Frontend_Cli::repairTable  

**Description**

```php
public repairTable (\Zend_Console_Getopt $opts)
```

repair a table 

 

**Parameters**

* `(\Zend_Console_Getopt) $opts`

**Return Values**

`void`


<hr />


### Tinebase_Frontend_Cli::repairTreeIsDeletedState  

**Description**

```php
 repairTreeIsDeletedState (void)
```

 

 

**Parameters**

`This function has no parameters.`

**Return Values**

`void`


<hr />


### Tinebase_Frontend_Cli::reportPreviewStatus  

**Description**

```php
 reportPreviewStatus (void)
```

 

 

**Parameters**

`This function has no parameters.`

**Return Values**

`void`


<hr />


### Tinebase_Frontend_Cli::resetSchedulerTasks  

**Description**

```php
public resetSchedulerTasks (\Zend_Console_Getopt $opts)
```

re-adds all scheduler tasks (if they are missing) 

 

**Parameters**

* `(\Zend_Console_Getopt) $opts`

**Return Values**

`int`




<hr />


### Tinebase_Frontend_Cli::restoreOrDiffEtcFileTreeFromBackupDB  

**Description**

```php
public restoreOrDiffEtcFileTreeFromBackupDB (void)
```

utility function to be adjusted for the needs at hand at the time of usage 

 

**Parameters**

`This function has no parameters.`

**Return Values**

`void`


<hr />


### Tinebase_Frontend_Cli::sanitizeFSMimeTypes  

**Description**

```php
 sanitizeFSMimeTypes (void)
```

 

 

**Parameters**

`This function has no parameters.`

**Return Values**

`void`


<hr />


### Tinebase_Frontend_Cli::sanitizeGroupListSync  

**Description**

```php
public sanitizeGroupListSync (\Zend_Console_Getopt $opts)
```

default is dryRun, to make changes use "-- dryRun=[0|false] 

 

**Parameters**

* `(\Zend_Console_Getopt) $opts`

**Return Values**

`int`




<hr />


### Tinebase_Frontend_Cli::setCustomfieldAcl  

**Description**

```php
public setCustomfieldAcl ( $_opts)
```

set customfield acl 

example:  
$ php tine20.php --method Tinebase.setCustomfieldAcl -- application=Addressbook \  
  model=Addressbook_Model_Contact name=$CFNAME \  
  grants='[{"account":"$USERNAME","account_type":"user","readGrant":1,"writeGrant":1},{"account_type":"anyone","readGrant":1}]' 

**Parameters**

* `() $_opts`

**Return Values**

`int`




**Throws Exceptions**


`\Tinebase_Exception_InvalidArgument`


<hr />


### Tinebase_Frontend_Cli::setDefaultGrantsOfPersistentFilters  

**Description**

```php
public setDefaultGrantsOfPersistentFilters (void)
```

repair function for persistent filters (favorites) without grants: this adds default grants for those filters. 

 

**Parameters**

`This function has no parameters.`

**Return Values**

`int`




<hr />


### Tinebase_Frontend_Cli::setMaintenanceMode  

**Description**

```php
public setMaintenanceMode (\Zend_Console_Getopt $_opts)
```

set Maintenance Mode 

 

**Parameters**

* `(\Zend_Console_Getopt) $_opts`

**Return Values**

`void`


**Throws Exceptions**


`\Tinebase_Exception_AccessDenied`


`\Tinebase_Exception_InvalidArgument`


`\Tinebase_Exception_NotFound`


<hr />


### Tinebase_Frontend_Cli::setNodeAcl  

**Description**

```php
public setNodeAcl ( $_opts)
```

set node acl 

example:  
$ php tine20.php --method Tinebase.setNodeAcl [-d] -- id=NODEID \  
  grants='[{"account":"$USERNAME","account_type":"user","readGrant":1,"writeGrant":1},{"account":"$GROUPNAME","account_type":"group","readGrant":1}]' 

**Parameters**

* `() $_opts`

**Return Values**

`int`




<hr />


### Tinebase_Frontend_Cli::syncFileTreeFromBackupDB  

**Description**

```php
 syncFileTreeFromBackupDB (void)
```

 

 

**Parameters**

`This function has no parameters.`

**Return Values**

`void`


<hr />


### Tinebase_Frontend_Cli::testNotification  

**Description**

```php
 testNotification (void)
```

 

 

**Parameters**

`This function has no parameters.`

**Return Values**

`void`


<hr />


### Tinebase_Frontend_Cli::transferRelations  

**Description**

```php
public transferRelations (\Zend_Console_Getopt $opts)
```

transfer relations 

 

**Parameters**

* `(\Zend_Console_Getopt) $opts`

**Return Values**

`void`


<hr />


### Tinebase_Frontend_Cli::triggerAsyncEvents  

**Description**

```php
public triggerAsyncEvents (\Zend_Console_Getopt $_opts)
```

trigger async events (for example via cronjob) 

 

**Parameters**

* `(\Zend_Console_Getopt) $_opts`

**Return Values**

`int`




<hr />


### Tinebase_Frontend_Cli::undeleteFileNodes  

**Description**

```php
public undeleteFileNodes (\Zend_Console_Getopt $_opts)
```

recursive undelete of file nodes - needs parent id param (only works if file objects still exist) 

 

**Parameters**

* `(\Zend_Console_Getopt) $_opts`

**Return Values**

`int`




<hr />


### Tinebase_Frontend_Cli::undo  

**Description**

```php
 undo (void)
```

 

 

**Parameters**

`This function has no parameters.`

**Return Values**

`void`


<hr />


### Tinebase_Frontend_Cli::undoDeprecated  

**Description**

```php
public undoDeprecated (\Zend_Console_Getopt $opts)
```

undo changes to records defined by certain criteria (user, date, fields, ...) 

example: $ php tine20.php --username pschuele --method Tinebase.undoDeprecated -d  
-- record_type=Addressbook_Model_Contact modification_time=2013-05-08 modification_account=3263 

**Parameters**

* `(\Zend_Console_Getopt) $opts`

**Return Values**

`int`




<hr />


### Tinebase_Frontend_Cli::userReport  

**Description**

```php
public userReport (void)
```

show user report (number of enabled, disabled, ... users) 

TODO add system user count  
TODO use twig? 

**Parameters**

`This function has no parameters.`

**Return Values**

`void`


<hr />


### Tinebase_Frontend_Cli::waitForActionQueueToEmpty  

**Description**

```php
 waitForActionQueueToEmpty (void)
```

 

 

**Parameters**

`This function has no parameters.`

**Return Values**

`void`


<hr />

