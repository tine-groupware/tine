# Admin_Frontend_Json  

Tine 2.0

This class handles all Json requests for the admin application  

## Implements:
Tinebase_Frontend_Json_Interface, Tinebase_Frontend_Interface

## Extend:

Tinebase_Frontend_Json_Abstract

## Methods

| Name | Description |
|------|-------------|
|[__construct](#admin_frontend_json__construct)|constructs Admin_Frontend_Json|
|[deleteAccessLogs](#admin_frontend_jsondeleteaccesslogs)|delete access log entries|
|[deleteConfigs](#admin_frontend_jsondeleteconfigs)|deletes existing records|
|[deleteContainers](#admin_frontend_jsondeletecontainers)|deletes existing records|
|[deleteCustomfields](#admin_frontend_jsondeletecustomfields)|deletes existing records|
|[deleteEmailAccounts](#admin_frontend_jsondeleteemailaccounts)|deletes existing records|
|[deleteGroups](#admin_frontend_jsondeletegroups)|delete multiple groups|
|[deleteImportExportDefinitions](#admin_frontend_jsondeleteimportexportdefinitions)|deletes existing records|
|[deleteLogEntrys](#admin_frontend_jsondeletelogentrys)|deletes existing records|
|[deleteRoles](#admin_frontend_jsondeleteroles)|delete multiple roles|
|[deleteSambaMachines](#admin_frontend_jsondeletesambamachines)|deletes existing records|
|[deleteTags](#admin_frontend_jsondeletetags)|delete multiple tags|
|[deleteUsers](#admin_frontend_jsondeleteusers)|delete users|
|[getAllRoleRights](#admin_frontend_jsongetallrolerights)|get list of all role rights for all applications|
|[getApplication](#admin_frontend_jsongetapplication)|get application|
|[getApplications](#admin_frontend_jsongetapplications)|get list of applications|
|[getConfig](#admin_frontend_jsongetconfig)|Return a single record|
|[getContainer](#admin_frontend_jsongetcontainer)|Return a single record|
|[getCustomfield](#admin_frontend_jsongetcustomfield)|Return a single record|
|[getEmailAccount](#admin_frontend_jsongetemailaccount)|Return a single record|
|[getGroup](#admin_frontend_jsongetgroup)|gets a single group|
|[getGroupMembers](#admin_frontend_jsongetgroupmembers)|get list of group members|
|[getGroups](#admin_frontend_jsongetgroups)|get list of groups|
|[getImportExportDefinition](#admin_frontend_jsongetimportexportdefinition)|Return a single record|
|[getLogEntry](#admin_frontend_jsongetlogentry)|Return a single record|
|[getPossibleMFAs](#admin_frontend_jsongetpossiblemfas)|returns possible mfa adapter for given user|
|[getRole](#admin_frontend_jsongetrole)|get a single role with all related data|
|[getRoleMembers](#admin_frontend_jsongetrolemembers)|get list of role members|
|[getRoleRights](#admin_frontend_jsongetrolerights)|get list of role rights|
|[getRoles](#admin_frontend_jsongetroles)|get list of roles|
|[getSambaMachine](#admin_frontend_jsongetsambamachine)|Return a single record|
|[getServerInfo](#admin_frontend_jsongetserverinfo)|returns phpinfo() output|
|[getSieveRules](#admin_frontend_jsongetsieverules)|get sieve rules for account|
|[getSieveScript](#admin_frontend_jsongetsievescript)|get sieve script for account|
|[getSieveVacation](#admin_frontend_jsongetsievevacation)|get sieve vacation for account|
|[getTag](#admin_frontend_jsongettag)|gets a single tag|
|[getTags](#admin_frontend_jsongettags)|get list of tags|
|[getUser](#admin_frontend_jsongetuser)|returns a fullUser|
|[getUsers](#admin_frontend_jsongetusers)|get list of accounts|
|[resetPassword](#admin_frontend_jsonresetpassword)|reset password for given account|
|[resolveAccountName](#admin_frontend_jsonresolveaccountname)|adds the name of the account to each item in the name property|
|[revealEmailAccountPassword](#admin_frontend_jsonrevealemailaccountpassword)|reveal email account password|
|[saveConfig](#admin_frontend_jsonsaveconfig)|creates/updates a record|
|[saveContainer](#admin_frontend_jsonsavecontainer)|creates/updates a record|
|[saveCustomfield](#admin_frontend_jsonsavecustomfield)|creates/updates a record|
|[saveEmailAccount](#admin_frontend_jsonsaveemailaccount)|creates/updates a record|
|[saveGroup](#admin_frontend_jsonsavegroup)|save group data from edit form|
|[saveImportExportDefinition](#admin_frontend_jsonsaveimportexportdefinition)|creates/updates a record|
|[saveLogEntry](#admin_frontend_jsonsavelogentry)|creates/updates a record|
|[savePreferences](#admin_frontend_jsonsavepreferences)|save preferences for application|
|[saveQuota](#admin_frontend_jsonsavequota)|save quotas|
|[saveRole](#admin_frontend_jsonsaverole)|save role data from edit form|
|[saveRules](#admin_frontend_jsonsaverules)|set sieve rules for account|
|[saveSambaMachine](#admin_frontend_jsonsavesambamachine)|creates/updates a record|
|[saveSieveVacation](#admin_frontend_jsonsavesievevacation)|set sieve vacation for account|
|[saveTag](#admin_frontend_jsonsavetag)|save tag data from edit form|
|[saveUser](#admin_frontend_jsonsaveuser)|save user|
|[searchAccessLogs](#admin_frontend_jsonsearchaccesslogs)|Search for records matching given arguments|
|[searchConfigs](#admin_frontend_jsonsearchconfigs)|Search for records matching given arguments|
|[searchContainers](#admin_frontend_jsonsearchcontainers)|Search for records matching given arguments|
|[searchCustomfields](#admin_frontend_jsonsearchcustomfields)|Search for records matching given arguments|
|[searchEmailAccounts](#admin_frontend_jsonsearchemailaccounts)|Search for records matching given arguments|
|[searchGroups](#admin_frontend_jsonsearchgroups)|Search for groups matching given arguments|
|[searchImportExportDefinitions](#admin_frontend_jsonsearchimportexportdefinitions)|Search for records matching given arguments|
|[searchLogEntrys](#admin_frontend_jsonsearchlogentrys)|Search for records matching given arguments|
|[searchQuotaNodes](#admin_frontend_jsonsearchquotanodes)||
|[searchSambaMachines](#admin_frontend_jsonsearchsambamachines)|Search for records matching given arguments|
|[searchSharedAddressbooks](#admin_frontend_jsonsearchsharedaddressbooks)|search for shared addressbook containers|
|[searchUsers](#admin_frontend_jsonsearchusers)|search for users/accounts|
|[setAccountState](#admin_frontend_jsonsetaccountstate)|set account state|
|[setApplicationState](#admin_frontend_jsonsetapplicationstate)|set application state|

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



### Admin_Frontend_Json::__construct  

**Description**

```php
public __construct (void)
```

constructs Admin_Frontend_Json 

 

**Parameters**

`This function has no parameters.`

**Return Values**

`void`


<hr />


### Admin_Frontend_Json::deleteAccessLogs  

**Description**

```php
public deleteAccessLogs (array $ids)
```

delete access log entries 

 

**Parameters**

* `(array) $ids`
: list of logIds to delete  

**Return Values**

`array`

> with success flag


<hr />


### Admin_Frontend_Json::deleteConfigs  

**Description**

```php
public deleteConfigs (array $ids)
```

deletes existing records 

 

**Parameters**

* `(array) $ids`

**Return Values**

`array`




<hr />


### Admin_Frontend_Json::deleteContainers  

**Description**

```php
public deleteContainers (array $ids)
```

deletes existing records 

 

**Parameters**

* `(array) $ids`

**Return Values**

`array`




<hr />


### Admin_Frontend_Json::deleteCustomfields  

**Description**

```php
public deleteCustomfields (array $ids, array $context)
```

deletes existing records 

 

**Parameters**

* `(array) $ids`
* `(array) $context`

**Return Values**

`array`




<hr />


### Admin_Frontend_Json::deleteEmailAccounts  

**Description**

```php
public deleteEmailAccounts (array $ids)
```

deletes existing records 

 

**Parameters**

* `(array) $ids`

**Return Values**

`string`




<hr />


### Admin_Frontend_Json::deleteGroups  

**Description**

```php
public deleteGroups (array $groupIds)
```

delete multiple groups 

 

**Parameters**

* `(array) $groupIds`
: list of contactId's to delete  

**Return Values**

`array`

> with success flag


<hr />


### Admin_Frontend_Json::deleteImportExportDefinitions  

**Description**

```php
public deleteImportExportDefinitions (array $ids)
```

deletes existing records 

 

**Parameters**

* `(array) $ids`

**Return Values**

`string`




<hr />


### Admin_Frontend_Json::deleteLogEntrys  

**Description**

```php
public deleteLogEntrys (array $ids)
```

deletes existing records 

 

**Parameters**

* `(array) $ids`

**Return Values**

`string`




<hr />


### Admin_Frontend_Json::deleteRoles  

**Description**

```php
public deleteRoles (array $roleIds)
```

delete multiple roles 

 

**Parameters**

* `(array) $roleIds`
: list of roleId's to delete  

**Return Values**

`array`

> with success flag


<hr />


### Admin_Frontend_Json::deleteSambaMachines  

**Description**

```php
public deleteSambaMachines (array $ids)
```

deletes existing records 

 

**Parameters**

* `(array) $ids`

**Return Values**

`string`




<hr />


### Admin_Frontend_Json::deleteTags  

**Description**

```php
public deleteTags (array $tagIds)
```

delete multiple tags 

 

**Parameters**

* `(array) $tagIds`
: list of contactId's to delete  

**Return Values**

`array`

> with success flag


<hr />


### Admin_Frontend_Json::deleteUsers  

**Description**

```php
public deleteUsers (array $ids)
```

delete users 

 

**Parameters**

* `(array) $ids`
: array of account ids  

**Return Values**

`array`

> with success flag


<hr />


### Admin_Frontend_Json::getAllRoleRights  

**Description**

```php
public getAllRoleRights (void)
```

get list of all role rights for all applications 

 

**Parameters**

`This function has no parameters.`

**Return Values**

`array`

> with all rights for applications


<hr />


### Admin_Frontend_Json::getApplication  

**Description**

```php
public getApplication (int $applicationId)
```

get application 

 

**Parameters**

* `(int) $applicationId`
: application id to get  

**Return Values**

`array`

> with application data


<hr />


### Admin_Frontend_Json::getApplications  

**Description**

```php
public getApplications (string $filter, string $sort, string $dir, int $start, int $limit)
```

get list of applications 

 

**Parameters**

* `(string) $filter`
* `(string) $sort`
* `(string) $dir`
* `(int) $start`
* `(int) $limit`

**Return Values**

`array`

> with results array & totalcount (int)


<hr />


### Admin_Frontend_Json::getConfig  

**Description**

```php
public getConfig (string $id)
```

Return a single record 

 

**Parameters**

* `(string) $id`

**Return Values**

`array`

> record data


<hr />


### Admin_Frontend_Json::getContainer  

**Description**

```php
public getContainer (string $id)
```

Return a single record 

 

**Parameters**

* `(string) $id`

**Return Values**

`array`

> record data


<hr />


### Admin_Frontend_Json::getCustomfield  

**Description**

```php
public getCustomfield (string $id)
```

Return a single record 

 

**Parameters**

* `(string) $id`

**Return Values**

`array`

> record data


<hr />


### Admin_Frontend_Json::getEmailAccount  

**Description**

```php
public getEmailAccount (string $id)
```

Return a single record 

 

**Parameters**

* `(string) $id`

**Return Values**

`array`

> record data


<hr />


### Admin_Frontend_Json::getGroup  

**Description**

```php
public getGroup (string $id)
```

gets a single group 

 

**Parameters**

* `(string) $id`

**Return Values**

`array`




<hr />


### Admin_Frontend_Json::getGroupMembers  

**Description**

```php
public getGroupMembers (int $groupId)
```

get list of group members 

 

**Parameters**

* `(int) $groupId`

**Return Values**

`array`

> with results / total count


<hr />


### Admin_Frontend_Json::getGroups  

**Description**

```php
public getGroups (string $_filter, string $_sort, string $_dir, int $_start, int $_limit)
```

get list of groups 

 

**Parameters**

* `(string) $_filter`
* `(string) $_sort`
* `(string) $_dir`
* `(int) $_start`
* `(int) $_limit`

**Return Values**

`array`

> with results array & totalcount (int)


<hr />


### Admin_Frontend_Json::getImportExportDefinition  

**Description**

```php
public getImportExportDefinition (string $id)
```

Return a single record 

 

**Parameters**

* `(string) $id`

**Return Values**

`array`

> record data


<hr />


### Admin_Frontend_Json::getLogEntry  

**Description**

```php
public getLogEntry (string $id)
```

Return a single record 

 

**Parameters**

* `(string) $id`

**Return Values**

`array`

> record data


<hr />


### Admin_Frontend_Json::getPossibleMFAs  

**Description**

```php
public getPossibleMFAs (array|string $account)
```

returns possible mfa adapter for given user 

 

**Parameters**

* `(array|string) $account`
: Tinebase_Model_FullUser data or account id  

**Return Values**

`void`


<hr />


### Admin_Frontend_Json::getRole  

**Description**

```php
public getRole (int $roleId)
```

get a single role with all related data 

 

**Parameters**

* `(int) $roleId`

**Return Values**

`array`




<hr />


### Admin_Frontend_Json::getRoleMembers  

**Description**

```php
public getRoleMembers (int $roleId)
```

get list of role members 

 

**Parameters**

* `(int) $roleId`

**Return Values**

`array`

> with results / totalcount


<hr />


### Admin_Frontend_Json::getRoleRights  

**Description**

```php
public getRoleRights (int $roleId)
```

get list of role rights 

 

**Parameters**

* `(int) $roleId`

**Return Values**

`array`

> with results / totalcount


<hr />


### Admin_Frontend_Json::getRoles  

**Description**

```php
public getRoles (string $query, string $sort, string $dir, int $start, int $limit)
```

get list of roles 

 

**Parameters**

* `(string) $query`
* `(string) $sort`
* `(string) $dir`
* `(int) $start`
* `(int) $limit`

**Return Values**

`array`

> with results array & totalcount (int)


<hr />


### Admin_Frontend_Json::getSambaMachine  

**Description**

```php
public getSambaMachine (string $id)
```

Return a single record 

 

**Parameters**

* `(string) $id`

**Return Values**

`array`

> record data


<hr />


### Admin_Frontend_Json::getServerInfo  

**Description**

```php
public getServerInfo (void)
```

returns phpinfo() output 

 

**Parameters**

`This function has no parameters.`

**Return Values**

`array`




<hr />


### Admin_Frontend_Json::getSieveRules  

**Description**

```php
public getSieveRules (string $accountId)
```

get sieve rules for account 

 

**Parameters**

* `(string) $accountId`

**Return Values**

`array`




<hr />


### Admin_Frontend_Json::getSieveScript  

**Description**

```php
public getSieveScript (string $accountId)
```

get sieve script for account 

 

**Parameters**

* `(string) $accountId`

**Return Values**

`string`




<hr />


### Admin_Frontend_Json::getSieveVacation  

**Description**

```php
public getSieveVacation (string $id)
```

get sieve vacation for account 

 

**Parameters**

* `(string) $id`
: account id  

**Return Values**

`array`




<hr />


### Admin_Frontend_Json::getTag  

**Description**

```php
public getTag (int $tagId)
```

gets a single tag 

 

**Parameters**

* `(int) $tagId`

**Return Values**

`array`




<hr />


### Admin_Frontend_Json::getTags  

**Description**

```php
public getTags (string $_filter, string $_sort, string $_dir, int $_start, int $_limit)
```

get list of tags 

 

**Parameters**

* `(string) $_filter`
* `(string) $_sort`
* `(string) $_dir`
* `(int) $_start`
* `(int) $_limit`

**Return Values**

`array`

> with results array & totalcount (int)


<hr />


### Admin_Frontend_Json::getUser  

**Description**

```php
public getUser (string $id)
```

returns a fullUser 

 

**Parameters**

* `(string) $id`

**Return Values**

`array`




<hr />


### Admin_Frontend_Json::getUsers  

**Description**

```php
public getUsers (string $_filter, string $_sort, string $_dir, int $_start, int $_limit)
```

get list of accounts 

 

**Parameters**

* `(string) $_filter`
* `(string) $_sort`
* `(string) $_dir`
* `(int) $_start`
* `(int) $_limit`

**Return Values**

`array`

> with results array & totalcount (int)


<hr />


### Admin_Frontend_Json::resetPassword  

**Description**

```php
public resetPassword (array|string $account, string $password, bool $mustChange)
```

reset password for given account 

 

**Parameters**

* `(array|string) $account`
: Tinebase_Model_FullUser data or account id  
* `(string) $password`
: the new password  
* `(bool) $mustChange`

**Return Values**

`array`




<hr />


### Admin_Frontend_Json::resolveAccountName  

**Description**

```php
public static resolveAccountName (array $_items, bool $_hasAccountPrefix, bool $_removePrefix)
```

adds the name of the account to each item in the name property 

 

**Parameters**

* `(array) $_items`
: array of arrays which contain a type and id property  
* `(bool) $_hasAccountPrefix`
* `(bool) $_removePrefix`

**Return Values**

`array`

> items with appended name


**Throws Exceptions**


`\UnexpectedValueException`


<hr />


### Admin_Frontend_Json::revealEmailAccountPassword  

**Description**

```php
public revealEmailAccountPassword (void)
```

reveal email account password 

 

**Parameters**

`This function has no parameters.`

**Return Values**

`array`




<hr />


### Admin_Frontend_Json::saveConfig  

**Description**

```php
public saveConfig (array $recordData)
```

creates/updates a record 

 

**Parameters**

* `(array) $recordData`

**Return Values**

`array`

> created/updated record


<hr />


### Admin_Frontend_Json::saveContainer  

**Description**

```php
public saveContainer (array $recordData)
```

creates/updates a record 

 

**Parameters**

* `(array) $recordData`

**Return Values**

`array`

> created/updated record


<hr />


### Admin_Frontend_Json::saveCustomfield  

**Description**

```php
public saveCustomfield (array $recordData)
```

creates/updates a record 

 

**Parameters**

* `(array) $recordData`

**Return Values**

`array`

> created/updated record


<hr />


### Admin_Frontend_Json::saveEmailAccount  

**Description**

```php
public saveEmailAccount (array $recordData)
```

creates/updates a record 

 

**Parameters**

* `(array) $recordData`

**Return Values**

`array`

> created/updated record


<hr />


### Admin_Frontend_Json::saveGroup  

**Description**

```php
public saveGroup (array $recordData)
```

save group data from edit form 

 

**Parameters**

* `(array) $recordData`
: group data  

**Return Values**

`array`




<hr />


### Admin_Frontend_Json::saveImportExportDefinition  

**Description**

```php
public saveImportExportDefinition (array $recordData)
```

creates/updates a record 

 

**Parameters**

* `(array) $recordData`

**Return Values**

`array`

> created/updated record


<hr />


### Admin_Frontend_Json::saveLogEntry  

**Description**

```php
public saveLogEntry (array $recordData)
```

creates/updates a record 

 

**Parameters**

* `(array) $recordData`

**Return Values**

`array`

> created/updated record


<hr />


### Admin_Frontend_Json::savePreferences  

**Description**

```php
public savePreferences (array $data)
```

save preferences for application 

 

**Parameters**

* `(array) $data`
: json encoded preferences data  

**Return Values**

`array`

> with the changed prefs


**Throws Exceptions**


`\Tinebase_Exception_AccessDenied`


<hr />


### Admin_Frontend_Json::saveQuota  

**Description**

```php
public saveQuota (string $application, array $additionalData)
```

save quotas 

 

**Parameters**

* `(string) $application`
* `(array) $additionalData`

**Return Values**

`false[]|mixed|\Tinebase_Config_Struct|\Tinebase_Model_Tree_Node`




**Throws Exceptions**


`\Tinebase_Exception`


`\Tinebase_Exception_AccessDenied`


`\Tinebase_Exception_Backend_Database_LockTimeout`


`\Tinebase_Exception_NotFound`


<hr />


### Admin_Frontend_Json::saveRole  

**Description**

```php
public saveRole (array $roleData, array $roleMembers, array $roleRights)
```

save role data from edit form 

 

**Parameters**

* `(array) $roleData`
: role data  
* `(array) $roleMembers`
: role members  
* `(array) $roleRights`
: role rights  

**Return Values**

`array`




<hr />


### Admin_Frontend_Json::saveRules  

**Description**

```php
public saveRules (array $accountId, array $rulesData)
```

set sieve rules for account 

 

**Parameters**

* `(array) $accountId`
* `(array) $rulesData`

**Return Values**

`array`




<hr />


### Admin_Frontend_Json::saveSambaMachine  

**Description**

```php
public saveSambaMachine (array $recordData)
```

creates/updates a record 

 

**Parameters**

* `(array) $recordData`

**Return Values**

`array`

> created/updated record


<hr />


### Admin_Frontend_Json::saveSieveVacation  

**Description**

```php
public saveSieveVacation (array $recordData)
```

set sieve vacation for account 

 

**Parameters**

* `(array) $recordData`

**Return Values**

`array`




<hr />


### Admin_Frontend_Json::saveTag  

**Description**

```php
public saveTag (array $tagData)
```

save tag data from edit form 

 

**Parameters**

* `(array) $tagData`

**Return Values**

`array`

> with success, message, tag data and tag members


<hr />


### Admin_Frontend_Json::saveUser  

**Description**

```php
public saveUser (array $recordData)
```

save user 

 

**Parameters**

* `(array) $recordData`
: data of Tinebase_Model_FullUser  

**Return Values**

`array`




<hr />


### Admin_Frontend_Json::searchAccessLogs  

**Description**

```php
public searchAccessLogs (array $filter, array $paging)
```

Search for records matching given arguments 

 

**Parameters**

* `(array) $filter`
* `(array) $paging`

**Return Values**

`array`




<hr />


### Admin_Frontend_Json::searchConfigs  

**Description**

```php
public searchConfigs (array $filter, array $paging)
```

Search for records matching given arguments 

 

**Parameters**

* `(array) $filter`
* `(array) $paging`

**Return Values**

`array`




<hr />


### Admin_Frontend_Json::searchContainers  

**Description**

```php
public searchContainers (array $filter, array $paging)
```

Search for records matching given arguments 

 

**Parameters**

* `(array) $filter`
* `(array) $paging`

**Return Values**

`array`




<hr />


### Admin_Frontend_Json::searchCustomfields  

**Description**

```php
public searchCustomfields (array $filter, array $paging)
```

Search for records matching given arguments 

 

**Parameters**

* `(array) $filter`
* `(array) $paging`

**Return Values**

`array`




<hr />


### Admin_Frontend_Json::searchEmailAccounts  

**Description**

```php
public searchEmailAccounts (array $filter, array $paging)
```

Search for records matching given arguments 

 

**Parameters**

* `(array) $filter`
* `(array) $paging`

**Return Values**

`array`




<hr />


### Admin_Frontend_Json::searchGroups  

**Description**

```php
public searchGroups (array $_filter, array $_paging)
```

Search for groups matching given arguments 

 

**Parameters**

* `(array) $_filter`
* `(array) $_paging`

**Return Values**

`array`




<hr />


### Admin_Frontend_Json::searchImportExportDefinitions  

**Description**

```php
public searchImportExportDefinitions (array $filter, array $paging)
```

Search for records matching given arguments 

 

**Parameters**

* `(array) $filter`
* `(array) $paging`

**Return Values**

`array`




<hr />


### Admin_Frontend_Json::searchLogEntrys  

**Description**

```php
public searchLogEntrys (array $filter, array $paging)
```

Search for records matching given arguments 

 

**Parameters**

* `(array) $filter`
* `(array) $paging`

**Return Values**

`array`




<hr />


### Admin_Frontend_Json::searchQuotaNodes  

**Description**

```php
 searchQuotaNodes (void)
```

 

 

**Parameters**

`This function has no parameters.`

**Return Values**

`void`


<hr />


### Admin_Frontend_Json::searchSambaMachines  

**Description**

```php
public searchSambaMachines (array $filter, array $paging)
```

Search for records matching given arguments 

 

**Parameters**

* `(array) $filter`
* `(array) $paging`

**Return Values**

`array`




<hr />


### Admin_Frontend_Json::searchSharedAddressbooks  

**Description**

```php
public searchSharedAddressbooks (array $filter, array $paging)
```

search for shared addressbook containers 

 

**Parameters**

* `(array) $filter`
: unused atm  
* `(array) $paging`
: unused atm  

**Return Values**

`array`




<hr />


### Admin_Frontend_Json::searchUsers  

**Description**

```php
public searchUsers (array $filter, array $paging)
```

search for users/accounts 

 

**Parameters**

* `(array) $filter`
* `(array) $paging`

**Return Values**

`array`

> with results array & totalcount (int)


<hr />


### Admin_Frontend_Json::setAccountState  

**Description**

```php
public setAccountState (array $accountIds, string $state)
```

set account state 

 

**Parameters**

* `(array) $accountIds`
: array of account ids  
* `(string) $state`
: state to set  

**Return Values**

`array`

> with success flag


<hr />


### Admin_Frontend_Json::setApplicationState  

**Description**

```php
public setApplicationState (array $applicationIds, string $state)
```

set application state 

 

**Parameters**

* `(array) $applicationIds`
: array of application ids  
* `(string) $state`
: state to set  

**Return Values**

`array`

> with success flag


<hr />

