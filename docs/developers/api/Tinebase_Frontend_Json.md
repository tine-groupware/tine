# Tinebase_Frontend_Json  

Json interface to Tinebase

## Implements:
Tinebase_Frontend_Json_Interface, Tinebase_Frontend_Interface

## Extend:

Tinebase_Frontend_Json_Abstract

## Methods

| Name | Description |
|------|-------------|
|[__construct](#tinebase_frontend_json__construct)||
|[_getLoginFailedResponse](#tinebase_frontend_json_getloginfailedresponse)||
|[aggregatePopulation](#tinebase_frontend_jsonaggregatepopulation)|seriously?.... please get rid of this|
|[attachMultipleTagsToMultipleRecords](#tinebase_frontend_jsonattachmultipletagstomultiplerecords)|attach multiple tags to multiple records identified by a filter|
|[attachTagToMultipleRecords](#tinebase_frontend_jsonattachtagtomultiplerecords)|attach tag to multiple records identified by a filter|
|[authenticate](#tinebase_frontend_jsonauthenticate)|authenticate user by username and password|
|[autoComplete](#tinebase_frontend_jsonautocomplete)|return autocomplete suggestions for a given recordclass, the property and value|
|[changePassword](#tinebase_frontend_jsonchangepassword)|change password of user|
|[changePin](#tinebase_frontend_jsonchangepin)|change pin of user|
|[changeUserAccount](#tinebase_frontend_jsonchangeuseraccount)|switch to another user's account|
|[checkAuthToken](#tinebase_frontend_jsoncheckauthtoken)||
|[clearState](#tinebase_frontend_jsonclearstate)|clears state|
|[copyNodes](#tinebase_frontend_jsoncopynodes)||
|[createTempFile](#tinebase_frontend_jsoncreatetempfile)||
|[deleteTags](#tinebase_frontend_jsondeletetags)|deletes tags identified by an array of identifiers|
|[detachTagsFromMultipleRecords](#tinebase_frontend_jsondetachtagsfrommultiplerecords)|detach tags to multiple records identified by a filter|
|[getAllRegistryData](#tinebase_frontend_jsongetallregistrydata)|Returns registry data of all applications current user has access to|
|[getAuthToken](#tinebase_frontend_jsongetauthtoken)||
|[getAvailableTranslations](#tinebase_frontend_jsongetavailabletranslations)|returns list of all available translations|
|[getConfig](#tinebase_frontend_jsongetconfig)|get config settings for application|
|[getCountryList](#tinebase_frontend_jsongetcountrylist)|get list of translated country names|
|[getRelations](#tinebase_frontend_jsongetrelations)|get all relations of a given record|
|[getReplicationModificationLogs](#tinebase_frontend_jsongetreplicationmodificationlogs)|returns the replication modification logs|
|[getTerminationDeadline](#tinebase_frontend_jsongetterminationdeadline)||
|[getUserProfile](#tinebase_frontend_jsongetuserprofile)|get profile of current user|
|[getUserProfileConfig](#tinebase_frontend_jsongetuserprofileconfig)|gets the userProfile config|
|[getUsers](#tinebase_frontend_jsongetusers)|get users|
|[getWebAuthnAuthenticateOptionsForMFA](#tinebase_frontend_jsongetwebauthnauthenticateoptionsformfa)||
|[getWebAuthnRegisterPublicKeyOptionsForMFA](#tinebase_frontend_jsongetwebauthnregisterpublickeyoptionsformfa)||
|[joinTempFiles](#tinebase_frontend_jsonjointempfiles)|joins all given tempfiles in given order to a single new tempFile|
|[loadState](#tinebase_frontend_jsonloadstate)|retuns all states|
|[login](#tinebase_frontend_jsonlogin)|login user with given username and password|
|[logout](#tinebase_frontend_jsonlogout)|destroy session|
|[openIDCLogin](#tinebase_frontend_jsonopenidclogin)||
|[ping](#tinebase_frontend_jsonping)|ping|
|[reportPresence](#tinebase_frontend_jsonreportpresence)||
|[restoreRevision](#tinebase_frontend_jsonrestorerevision)||
|[saveConfig](#tinebase_frontend_jsonsaveconfig)|save application config|
|[savePreferences](#tinebase_frontend_jsonsavepreferences)|save preferences for application|
|[saveTag](#tinebase_frontend_jsonsavetag)|adds a new personal tag|
|[searchCustomFieldValues](#tinebase_frontend_jsonsearchcustomfieldvalues)|search / get custom field values|
|[searchDepartments](#tinebase_frontend_jsonsearchdepartments)|search / get departments|
|[searchNotes](#tinebase_frontend_jsonsearchnotes)|search / get notes - used by activities grid|
|[searchPaths](#tinebase_frontend_jsonsearchpaths)||
|[searchPreferencesForApplication](#tinebase_frontend_jsonsearchpreferencesforapplication)|search preferences|
|[searchRoles](#tinebase_frontend_jsonsearchroles)|Search for roles|
|[searchTags](#tinebase_frontend_jsonsearchtags)|search tags|
|[searchTagsByForeignFilter](#tinebase_frontend_jsonsearchtagsbyforeignfilter)|search tags by foreign filter|
|[setLocale](#tinebase_frontend_jsonsetlocale)|sets locale|
|[setState](#tinebase_frontend_jsonsetstate)|set state|
|[setTimezone](#tinebase_frontend_jsonsettimezone)|sets timezone|
|[setUserProfileConfig](#tinebase_frontend_jsonsetuserprofileconfig)|saves userProfile config|
|[toogleAdvancedSearch](#tinebase_frontend_jsontoogleadvancedsearch)|Toggles advanced search preference|
|[updateCredentialCache](#tinebase_frontend_jsonupdatecredentialcache)|update user credential cache|
|[updateMultipleRecords](#tinebase_frontend_jsonupdatemultiplerecords)|Used for updating multiple records|
|[updateUserProfile](#tinebase_frontend_jsonupdateuserprofile)|update user profile|
|[void](#tinebase_frontend_jsonvoid)|dummy function to measure speed of framework initialization|

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



### Tinebase_Frontend_Json::__construct  

**Description**

```php
 __construct (void)
```

 

 

**Parameters**

`This function has no parameters.`

**Return Values**

`void`


<hr />


### Tinebase_Frontend_Json::_getLoginFailedResponse  

**Description**

```php
 _getLoginFailedResponse (void)
```

 

 

**Parameters**

`This function has no parameters.`

**Return Values**

`void`


<hr />


### Tinebase_Frontend_Json::aggregatePopulation  

**Description**

```php
public aggregatePopulation (array $_communityNumber)
```

seriously?.... please get rid of this 

 

**Parameters**

* `(array) $_communityNumber`

**Return Values**

`mixed`




<hr />


### Tinebase_Frontend_Json::attachMultipleTagsToMultipleRecords  

**Description**

```php
public attachMultipleTagsToMultipleRecords (array $filterData, string $filterName, mixed $tags)
```

attach multiple tags to multiple records identified by a filter 

 

**Parameters**

* `(array) $filterData`
* `(string) $filterName`
* `(mixed) $tags`
: array of existing and non-existing tags  

**Return Values**

`void`




<hr />


### Tinebase_Frontend_Json::attachTagToMultipleRecords  

**Description**

```php
public attachTagToMultipleRecords (array $filterData, string $filterName, mixed $tag)
```

attach tag to multiple records identified by a filter 

 

**Parameters**

* `(array) $filterData`
* `(string) $filterName`
* `(mixed) $tag`
: string|array existing and non-existing tag  

**Return Values**

`array`




<hr />


### Tinebase_Frontend_Json::authenticate  

**Description**

```php
public authenticate (string $username, string $password)
```

authenticate user by username and password 

 

**Parameters**

* `(string) $username`
: the username  
* `(string) $password`
: the password  

**Return Values**

`array`




<hr />


### Tinebase_Frontend_Json::autoComplete  

**Description**

```php
public autoComplete (string $appName, string $modelName, string $property, string $startswith)
```

return autocomplete suggestions for a given recordclass, the property and value 

 

**Parameters**

* `(string) $appName`
* `(string) $modelName`
* `(string) $property`
* `(string) $startswith`

**Return Values**

`array`




**Throws Exceptions**


`\Tinebase_Exception_InvalidArgument`


<hr />


### Tinebase_Frontend_Json::changePassword  

**Description**

```php
public changePassword (string $oldPassword, string $newPassword)
```

change password of user 

 

**Parameters**

* `(string) $oldPassword`
: the old password  
* `(string) $newPassword`
: the new password  

**Return Values**

`array`




<hr />


### Tinebase_Frontend_Json::changePin  

**Description**

```php
public changePin (string $oldPassword, string $newPassword)
```

change pin of user 

 

**Parameters**

* `(string) $oldPassword`
: the old password  
* `(string) $newPassword`
: the new password  

**Return Values**

`array`




<hr />


### Tinebase_Frontend_Json::changeUserAccount  

**Description**

```php
public changeUserAccount (string $loginName)
```

switch to another user's account 

 

**Parameters**

* `(string) $loginName`

**Return Values**

`array`




<hr />


### Tinebase_Frontend_Json::checkAuthToken  

**Description**

```php
 checkAuthToken (void)
```

 

 

**Parameters**

`This function has no parameters.`

**Return Values**

`void`


<hr />


### Tinebase_Frontend_Json::clearState  

**Description**

```php
public clearState (string $name)
```

clears state 

 

**Parameters**

* `(string) $name`

**Return Values**

`array`




<hr />


### Tinebase_Frontend_Json::copyNodes  

**Description**

```php
 copyNodes (void)
```

 

 

**Parameters**

`This function has no parameters.`

**Return Values**

`void`


<hr />


### Tinebase_Frontend_Json::createTempFile  

**Description**

```php
 createTempFile (void)
```

 

 

**Parameters**

`This function has no parameters.`

**Return Values**

`void`


<hr />


### Tinebase_Frontend_Json::deleteTags  

**Description**

```php
public deleteTags (array $ids)
```

deletes tags identified by an array of identifiers 

 

**Parameters**

* `(array) $ids`

**Return Values**

`array`




<hr />


### Tinebase_Frontend_Json::detachTagsFromMultipleRecords  

**Description**

```php
public detachTagsFromMultipleRecords (array $filterData, string $filterName, mixed $tag)
```

detach tags to multiple records identified by a filter 

 

**Parameters**

* `(array) $filterData`
* `(string) $filterName`
* `(mixed) $tag`
: string|array existing and non-existing tag  

**Return Values**

`array`




<hr />


### Tinebase_Frontend_Json::getAllRegistryData  

**Description**

```php
public getAllRegistryData (void)
```

Returns registry data of all applications current user has access to 

 

**Parameters**

`This function has no parameters.`

**Return Values**

`mixed`

> array 'variable name' => 'data'


**Throws Exceptions**


`\Tinebase_Exception_AccessDenied`


<hr />


### Tinebase_Frontend_Json::getAuthToken  

**Description**

```php
 getAuthToken (void)
```

 

 

**Parameters**

`This function has no parameters.`

**Return Values**

`void`


<hr />


### Tinebase_Frontend_Json::getAvailableTranslations  

**Description**

```php
public getAvailableTranslations (void)
```

returns list of all available translations 

 

**Parameters**

`This function has no parameters.`

**Return Values**

`array`

> list of all available translations


<hr />


### Tinebase_Frontend_Json::getConfig  

**Description**

```php
public getConfig (string $id)
```

get config settings for application 

 

**Parameters**

* `(string) $id`
: application name  

**Return Values**

`array`




<hr />


### Tinebase_Frontend_Json::getCountryList  

**Description**

```php
public getCountryList (void)
```

get list of translated country names 

Wrapper for {@see \Tinebase_Core::getCountrylist} 

**Parameters**

`This function has no parameters.`

**Return Values**

`array`

> list of countrys


<hr />


### Tinebase_Frontend_Json::getRelations  

**Description**

```php
public getRelations (string $model, string $id, string $degree, array $type, string $relatedModel)
```

get all relations of a given record 

 

**Parameters**

* `(string) $model`
: own model to get relations for  
* `(string) $id`
: own id to get relations for  
* `(string) $degree`
: only return relations of given degree  
* `(array) $type`
: only return relations of given type  
* `(string) $relatedModel`
: only return relations having this related model  

**Return Values**

`array`




<hr />


### Tinebase_Frontend_Json::getReplicationModificationLogs  

**Description**

```php
public getReplicationModificationLogs (int $sequence, int $limit)
```

returns the replication modification logs 

 

**Parameters**

* `(int) $sequence`
* `(int) $limit`

**Return Values**

`array`




**Throws Exceptions**


`\Tinebase_Exception_AccessDenied`


<hr />


### Tinebase_Frontend_Json::getTerminationDeadline  

**Description**

```php
 getTerminationDeadline (void)
```

 

 

**Parameters**

`This function has no parameters.`

**Return Values**

`void`


<hr />


### Tinebase_Frontend_Json::getUserProfile  

**Description**

```php
public getUserProfile (string $userId)
```

get profile of current user 

 

**Parameters**

* `(string) $userId`

**Return Values**

`array`




<hr />


### Tinebase_Frontend_Json::getUserProfileConfig  

**Description**

```php
public getUserProfileConfig (void)
```

gets the userProfile config 

 

**Parameters**

`This function has no parameters.`

**Return Values**

`array`




<hr />


### Tinebase_Frontend_Json::getUsers  

**Description**

```php
public getUsers (string $filter, string $sort, string $dir, int $start, int $limit)
```

get users 

 

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


### Tinebase_Frontend_Json::getWebAuthnAuthenticateOptionsForMFA  

**Description**

```php
 getWebAuthnAuthenticateOptionsForMFA (void)
```

 

 

**Parameters**

`This function has no parameters.`

**Return Values**

`void`


<hr />


### Tinebase_Frontend_Json::getWebAuthnRegisterPublicKeyOptionsForMFA  

**Description**

```php
 getWebAuthnRegisterPublicKeyOptionsForMFA (void)
```

 

 

**Parameters**

`This function has no parameters.`

**Return Values**

`void`


<hr />


### Tinebase_Frontend_Json::joinTempFiles  

**Description**

```php
public joinTempFiles (array $tempFilesData)
```

joins all given tempfiles in given order to a single new tempFile 

 

**Parameters**

* `(array) $tempFilesData`
: of tempfiles arrays $tempFiles  

**Return Values**

`array`

> new tempFile


<hr />


### Tinebase_Frontend_Json::loadState  

**Description**

```php
public loadState (void)
```

retuns all states 

 

**Parameters**

`This function has no parameters.`

**Return Values**

`array`

> of name => value


<hr />


### Tinebase_Frontend_Json::login  

**Description**

```php
public login (?string $username, ?string $password, ?string $MFAUserConfigId, ?string $MFAPassword)
```

login user with given username and password 

 

**Parameters**

* `(?string) $username`
: the username  
* `(?string) $password`
: the password  
* `(?string) $MFAUserConfigId`
: config for mfa device to use  
* `(?string) $MFAPassword`
: otp from mfa device  

**Return Values**

`array`




<hr />


### Tinebase_Frontend_Json::logout  

**Description**

```php
public logout (void)
```

destroy session 

 

**Parameters**

`This function has no parameters.`

**Return Values**

`array`




<hr />


### Tinebase_Frontend_Json::openIDCLogin  

**Description**

```php
 openIDCLogin (void)
```

 

 

**Parameters**

`This function has no parameters.`

**Return Values**

`void`


<hr />


### Tinebase_Frontend_Json::ping  

**Description**

```php
public ping (void)
```

ping 

NOTE: auth & outdated client gets checked in server 

**Parameters**

`This function has no parameters.`

**Return Values**

`void`


<hr />


### Tinebase_Frontend_Json::reportPresence  

**Description**

```php
 reportPresence (void)
```

 

 

**Parameters**

`This function has no parameters.`

**Return Values**

`void`


<hr />


### Tinebase_Frontend_Json::restoreRevision  

**Description**

```php
 restoreRevision (void)
```

 

 

**Parameters**

`This function has no parameters.`

**Return Values**

`void`


<hr />


### Tinebase_Frontend_Json::saveConfig  

**Description**

```php
public saveConfig (array $recordData)
```

save application config 

 

**Parameters**

* `(array) $recordData`

**Return Values**

`array`




<hr />


### Tinebase_Frontend_Json::savePreferences  

**Description**

```php
public savePreferences (void)
```

save preferences for application 

 

**Parameters**

`This function has no parameters.`

**Return Values**

`void`


<hr />


### Tinebase_Frontend_Json::saveTag  

**Description**

```php
public saveTag (array $tag)
```

adds a new personal tag 

 

**Parameters**

* `(array) $tag`

**Return Values**

`array`




<hr />


### Tinebase_Frontend_Json::searchCustomFieldValues  

**Description**

```php
public searchCustomFieldValues (array $filter, array $paging)
```

search / get custom field values 

 

**Parameters**

* `(array) $filter`
: filter array  
* `(array) $paging`
: pagination info  

**Return Values**

`array`




<hr />


### Tinebase_Frontend_Json::searchDepartments  

**Description**

```php
public searchDepartments (array $filter, array $paging)
```

search / get departments 

 

**Parameters**

* `(array) $filter`
: filter array  
* `(array) $paging`
: pagination info  

**Return Values**

`array`




<hr />


### Tinebase_Frontend_Json::searchNotes  

**Description**

```php
public searchNotes (array $filter, array $paging)
```

search / get notes - used by activities grid 

 

**Parameters**

* `(array) $filter`
: filter array  
* `(array) $paging`
: pagination info  

**Return Values**

`array`




<hr />


### Tinebase_Frontend_Json::searchPaths  

**Description**

```php
 searchPaths (void)
```

 

 

**Parameters**

`This function has no parameters.`

**Return Values**

`void`


<hr />


### Tinebase_Frontend_Json::searchPreferencesForApplication  

**Description**

```php
public searchPreferencesForApplication (string $applicationName, array $filter)
```

search preferences 

 

**Parameters**

* `(string) $applicationName`
* `(array) $filter`
: json encoded  

**Return Values**

`array`




<hr />


### Tinebase_Frontend_Json::searchRoles  

**Description**

```php
public searchRoles (array $filter, array $paging)
```

Search for roles 

 

**Parameters**

* `(array) $filter`
* `(array) $paging`

**Return Values**

`array`




<hr />


### Tinebase_Frontend_Json::searchTags  

**Description**

```php
public searchTags (array $filter, array $paging)
```

search tags 

 

**Parameters**

* `(array) $filter`
: filter array  
* `(array) $paging`
: pagination info  

**Return Values**

`array`




<hr />


### Tinebase_Frontend_Json::searchTagsByForeignFilter  

**Description**

```php
public searchTagsByForeignFilter (array $filterData, string $filterName)
```

search tags by foreign filter 

 

**Parameters**

* `(array) $filterData`
* `(string) $filterName`

**Return Values**

`array`




<hr />


### Tinebase_Frontend_Json::setLocale  

**Description**

```php
public setLocale (string $localeString, bool $saveaspreference, bool $setcookie)
```

sets locale 

 

**Parameters**

* `(string) $localeString`
* `(bool) $saveaspreference`
* `(bool) $setcookie`

**Return Values**

`array`




<hr />


### Tinebase_Frontend_Json::setState  

**Description**

```php
public setState (string $name, string $value)
```

set state 

 

**Parameters**

* `(string) $name`
* `(string) $value`

**Return Values**

`array`




<hr />


### Tinebase_Frontend_Json::setTimezone  

**Description**

```php
public setTimezone (string $timezoneString, bool $saveaspreference)
```

sets timezone 

 

**Parameters**

* `(string) $timezoneString`
* `(bool) $saveaspreference`

**Return Values**

`string`




<hr />


### Tinebase_Frontend_Json::setUserProfileConfig  

**Description**

```php
public setUserProfileConfig (array $configData)
```

saves userProfile config 

 

**Parameters**

* `(array) $configData`

**Return Values**

`void`


<hr />


### Tinebase_Frontend_Json::toogleAdvancedSearch  

**Description**

```php
public toogleAdvancedSearch (string|int $state)
```

Toggles advanced search preference 

 

**Parameters**

* `(string|int) $state`

**Return Values**

`true`




<hr />


### Tinebase_Frontend_Json::updateCredentialCache  

**Description**

```php
public updateCredentialCache (string $password)
```

update user credential cache 

- fires Tinebase_Event_User_ChangeCredentialCache 

**Parameters**

* `(string) $password`

**Return Values**

`array`




<hr />


### Tinebase_Frontend_Json::updateMultipleRecords  

**Description**

```php
public updateMultipleRecords (string $appName, string $modelName, array $changes, array $filter)
```

Used for updating multiple records 

 

**Parameters**

* `(string) $appName`
* `(string) $modelName`
* `(array) $changes`
* `(array) $filter`

**Return Values**

`void`


<hr />


### Tinebase_Frontend_Json::updateUserProfile  

**Description**

```php
public updateUserProfile (array $profileData)
```

update user profile 

 

**Parameters**

* `(array) $profileData`

**Return Values**

`array`




<hr />


### Tinebase_Frontend_Json::void  

**Description**

```php
public void (void)
```

dummy function to measure speed of framework initialization 

 

**Parameters**

`This function has no parameters.`

**Return Values**

`void`


<hr />

