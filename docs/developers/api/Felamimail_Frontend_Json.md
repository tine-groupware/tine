# Felamimail_Frontend_Json  

json frontend for Felamimail

This class handles all Json requests for the Felamimail application  

## Implements:
Tinebase_Frontend_Json_Interface, Tinebase_Frontend_Interface

## Extend:

Tinebase_Frontend_Json_Abstract

## Methods

| Name | Description |
|------|-------------|
|[addFlags](#felamimail_frontend_jsonaddflags)|add given flags to given messages|
|[addFolder](#felamimail_frontend_jsonaddfolder)|add new folder|
|[approveAccountMigration](#felamimail_frontend_jsonapproveaccountmigration)||
|[changeCredentials](#felamimail_frontend_jsonchangecredentials)|change account pwd / username|
|[clearFlags](#felamimail_frontend_jsonclearflags)|clear given flags from given messages|
|[deleteAccounts](#felamimail_frontend_jsondeleteaccounts)|deletes existing accounts|
|[deleteDraft](#felamimail_frontend_jsondeletedraft)||
|[deleteFolder](#felamimail_frontend_jsondeletefolder)|delete folder|
|[doMailsBelongToAccount](#felamimail_frontend_jsondomailsbelongtoaccount)||
|[emptyFolder](#felamimail_frontend_jsonemptyfolder)|remove all messages from folder and delete subfolders|
|[fileAttachments](#felamimail_frontend_jsonfileattachments)||
|[fileMessages](#felamimail_frontend_jsonfilemessages)|file messages into Filemanager|
|[fillAttachmentCache](#felamimail_frontend_jsonfillattachmentcache)||
|[getAccount](#felamimail_frontend_jsongetaccount)|get account data|
|[getAttachmentCache](#felamimail_frontend_jsongetattachmentcache)||
|[getFileSuggestions](#felamimail_frontend_jsongetfilesuggestions)|fetch suggestions for filing places for given message / recipients / .|
|[getFolderStatus](#felamimail_frontend_jsongetfolderstatus)|get folder status|
|[getMessage](#felamimail_frontend_jsongetmessage)|get message data|
|[getMessageFromNode](#felamimail_frontend_jsongetmessagefromnode)|returns eml node converted to Felamimail message|
|[getRules](#felamimail_frontend_jsongetrules)|get sieve rules for account|
|[getVacation](#felamimail_frontend_jsongetvacation)|get sieve vacation for account|
|[getVacationMessage](#felamimail_frontend_jsongetvacationmessage)|get vacation message defined by template / do substitutions for dates and representative|
|[getVacationMessageTemplates](#felamimail_frontend_jsongetvacationmessagetemplates)|get available vacation message templates|
|[importMessage](#felamimail_frontend_jsonimportmessage)|import message into target folder|
|[moveFolder](#felamimail_frontend_jsonmovefolder)|move folder|
|[moveMessages](#felamimail_frontend_jsonmovemessages)|move messages to folder|
|[processSpam](#felamimail_frontend_jsonprocessspam)||
|[refreshFolder](#felamimail_frontend_jsonrefreshfolder)|refresh folder|
|[renameFolder](#felamimail_frontend_jsonrenamefolder)|rename folder|
|[saveAccount](#felamimail_frontend_jsonsaveaccount)|creates/updates a record|
|[saveDraft](#felamimail_frontend_jsonsavedraft)||
|[saveMessage](#felamimail_frontend_jsonsavemessage)|save + send message|
|[saveMessageInFolder](#felamimail_frontend_jsonsavemessageinfolder)|save message in folder|
|[saveRules](#felamimail_frontend_jsonsaverules)|set sieve rules for account|
|[saveVacation](#felamimail_frontend_jsonsavevacation)|set sieve vacation for account|
|[searchAccounts](#felamimail_frontend_jsonsearchaccounts)||
|[searchFolders](#felamimail_frontend_jsonsearchfolders)|search folders and update/initialize cache of subfolders|
|[searchMessages](#felamimail_frontend_jsonsearchmessages)|search messages in message cache|
|[sendReadingConfirmation](#felamimail_frontend_jsonsendreadingconfirmation)|send reading confirmation|
|[testIMapSettings](#felamimail_frontend_jsontestimapsettings)|test imap settings|
|[testSmtpSettings](#felamimail_frontend_jsontestsmtpsettings)|test smtp settings|
|[updateFlags](#felamimail_frontend_jsonupdateflags)|update flags - use session/writeClose to allow following requests|
|[updateFolderCache](#felamimail_frontend_jsonupdatefoldercache)|update folder cache|
|[updateMessageCache](#felamimail_frontend_jsonupdatemessagecache)|update message cache - use session/writeClose to update incomplete cache and allow following requests|

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



### Felamimail_Frontend_Json::addFlags  

**Description**

```php
public addFlags (array $filterData, string|array $flags)
```

add given flags to given messages 

 

**Parameters**

* `(array) $filterData`
* `(string|array) $flags`

**Return Values**

`array`




<hr />


### Felamimail_Frontend_Json::addFolder  

**Description**

```php
public addFolder (string $name, string $parent, string $accountId)
```

add new folder 

 

**Parameters**

* `(string) $name`
* `(string) $parent`
* `(string) $accountId`

**Return Values**

`array`




<hr />


### Felamimail_Frontend_Json::approveAccountMigration  

**Description**

```php
 approveAccountMigration (void)
```

 

 

**Parameters**

`This function has no parameters.`

**Return Values**

`void`


<hr />


### Felamimail_Frontend_Json::changeCredentials  

**Description**

```php
public changeCredentials (string $id, string $username, string $password)
```

change account pwd / username 

 

**Parameters**

* `(string) $id`
* `(string) $username`
* `(string) $password`

**Return Values**

`array`




<hr />


### Felamimail_Frontend_Json::clearFlags  

**Description**

```php
public clearFlags (array $filterData, string|array $flags)
```

clear given flags from given messages 

 

**Parameters**

* `(array) $filterData`
* `(string|array) $flags`

**Return Values**

`array`




<hr />


### Felamimail_Frontend_Json::deleteAccounts  

**Description**

```php
public deleteAccounts (array $ids)
```

deletes existing accounts 

 

**Parameters**

* `(array) $ids`

**Return Values**

`array`




<hr />


### Felamimail_Frontend_Json::deleteDraft  

**Description**

```php
 deleteDraft (void)
```

 

 

**Parameters**

`This function has no parameters.`

**Return Values**

`void`


<hr />


### Felamimail_Frontend_Json::deleteFolder  

**Description**

```php
public deleteFolder (string $folder, string $accountId)
```

delete folder 

 

**Parameters**

* `(string) $folder`
: the folder global name to delete  
* `(string) $accountId`

**Return Values**

`array`




<hr />


### Felamimail_Frontend_Json::doMailsBelongToAccount  

**Description**

```php
 doMailsBelongToAccount (void)
```

 

 

**Parameters**

`This function has no parameters.`

**Return Values**

`void`


<hr />


### Felamimail_Frontend_Json::emptyFolder  

**Description**

```php
public emptyFolder (string $folderId)
```

remove all messages from folder and delete subfolders 

 

**Parameters**

* `(string) $folderId`
: the folder id to delete  

**Return Values**

`array`

> with folder status


<hr />


### Felamimail_Frontend_Json::fileAttachments  

**Description**

```php
 fileAttachments (void)
```

 

 

**Parameters**

`This function has no parameters.`

**Return Values**

`void`


<hr />


### Felamimail_Frontend_Json::fileMessages  

**Description**

```php
public fileMessages (array $filterData, array $locations)
```

file messages into Filemanager 

 

**Parameters**

* `(array) $filterData`
* `(array) $locations`

**Return Values**

`array`




<hr />


### Felamimail_Frontend_Json::fillAttachmentCache  

**Description**

```php
 fillAttachmentCache (void)
```

 

 

**Parameters**

`This function has no parameters.`

**Return Values**

`void`


<hr />


### Felamimail_Frontend_Json::getAccount  

**Description**

```php
public getAccount (string $id)
```

get account data 

 

**Parameters**

* `(string) $id`

**Return Values**

`array`




<hr />


### Felamimail_Frontend_Json::getAttachmentCache  

**Description**

```php
 getAttachmentCache (void)
```

 

 

**Parameters**

`This function has no parameters.`

**Return Values**

`void`


<hr />


### Felamimail_Frontend_Json::getFileSuggestions  

**Description**

```php
public getFileSuggestions (array $messages)
```

fetch suggestions for filing places for given message / recipients / . 

.. 

**Parameters**

* `(array) $messages`

**Return Values**

`array`




<hr />


### Felamimail_Frontend_Json::getFolderStatus  

**Description**

```php
public getFolderStatus (array $filterData)
```

get folder status 

 

**Parameters**

* `(array) $filterData`

**Return Values**

`array`

> of folder status


**Throws Exceptions**


`\Tinebase_Exception_SystemGeneric`


<hr />


### Felamimail_Frontend_Json::getMessage  

**Description**

```php
public getMessage (string $id, string $mimeType)
```

get message data 

 

**Parameters**

* `(string) $id`
* `(string) $mimeType`

**Return Values**

`array`




<hr />


### Felamimail_Frontend_Json::getMessageFromNode  

**Description**

```php
public getMessageFromNode ( $nodeId)
```

returns eml node converted to Felamimail message 

 

**Parameters**

* `() $nodeId`

**Return Values**

`array`




<hr />


### Felamimail_Frontend_Json::getRules  

**Description**

```php
public getRules (string $accountId)
```

get sieve rules for account 

 

**Parameters**

* `(string) $accountId`

**Return Values**

`array`




<hr />


### Felamimail_Frontend_Json::getVacation  

**Description**

```php
public getVacation (string $id)
```

get sieve vacation for account 

 

**Parameters**

* `(string) $id`
: account id  

**Return Values**

`array`




<hr />


### Felamimail_Frontend_Json::getVacationMessage  

**Description**

```php
public getVacationMessage (array $vacationData)
```

get vacation message defined by template / do substitutions for dates and representative 

 

**Parameters**

* `(array) $vacationData`

**Return Values**

`array`




<hr />


### Felamimail_Frontend_Json::getVacationMessageTemplates  

**Description**

```php
public getVacationMessageTemplates (void)
```

get available vacation message templates 

 

**Parameters**

`This function has no parameters.`

**Return Values**

`array`




<hr />


### Felamimail_Frontend_Json::importMessage  

**Description**

```php
public importMessage ( $targetFolderId,  $tempFile)
```

import message into target folder 

 

**Parameters**

* `() $targetFolderId`
* `() $tempFile`

**Return Values**

`void`


<hr />


### Felamimail_Frontend_Json::moveFolder  

**Description**

```php
public moveFolder (string $newGlobalName, string $oldGlobalName, string $accountId)
```

move folder 

 

**Parameters**

* `(string) $newGlobalName`
* `(string) $oldGlobalName`
* `(string) $accountId`

**Return Values**

`array`




<hr />


### Felamimail_Frontend_Json::moveMessages  

**Description**

```php
public moveMessages (array $filterData, string $targetFolderId, bool $keepOriginalMessages)
```

move messages to folder 

 

**Parameters**

* `(array) $filterData`
: filter data  
* `(string) $targetFolderId`
* `(bool) $keepOriginalMessages`

**Return Values**

`array`

> source folder status


<hr />


### Felamimail_Frontend_Json::processSpam  

**Description**

```php
 processSpam (void)
```

 

 

**Parameters**

`This function has no parameters.`

**Return Values**

`void`


<hr />


### Felamimail_Frontend_Json::refreshFolder  

**Description**

```php
public refreshFolder (string $folderId)
```

refresh folder 

 

**Parameters**

* `(string) $folderId`
: the folder id to delete  

**Return Values**

`array`




<hr />


### Felamimail_Frontend_Json::renameFolder  

**Description**

```php
public renameFolder (string $newName, string $oldGlobalName, string $accountId)
```

rename folder 

 

**Parameters**

* `(string) $newName`
* `(string) $oldGlobalName`
* `(string) $accountId`

**Return Values**

`array`




<hr />


### Felamimail_Frontend_Json::saveAccount  

**Description**

```php
public saveAccount (array $recordData)
```

creates/updates a record 

 

**Parameters**

* `(array) $recordData`

**Return Values**

`array`

> created/updated record


<hr />


### Felamimail_Frontend_Json::saveDraft  

**Description**

```php
 saveDraft (void)
```

 

 

**Parameters**

`This function has no parameters.`

**Return Values**

`void`


<hr />


### Felamimail_Frontend_Json::saveMessage  

**Description**

```php
public saveMessage (array $recordData)
```

save + send message 

- this function has to be named 'saveMessage' because of the generic edit dialog function names 

**Parameters**

* `(array) $recordData`

**Return Values**

`array`




<hr />


### Felamimail_Frontend_Json::saveMessageInFolder  

**Description**

```php
public saveMessageInFolder (string $folderName, array $recordData)
```

save message in folder 

 

**Parameters**

* `(string) $folderName`
* `(array) $recordData`

**Return Values**

`array`




<hr />


### Felamimail_Frontend_Json::saveRules  

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


### Felamimail_Frontend_Json::saveVacation  

**Description**

```php
public saveVacation (array $recordData)
```

set sieve vacation for account 

 

**Parameters**

* `(array) $recordData`

**Return Values**

`array`




<hr />


### Felamimail_Frontend_Json::searchAccounts  

**Description**

```php
 searchAccounts (void)
```

 

 

**Parameters**

`This function has no parameters.`

**Return Values**

`void`


<hr />


### Felamimail_Frontend_Json::searchFolders  

**Description**

```php
public searchFolders (array $filter)
```

search folders and update/initialize cache of subfolders 

 

**Parameters**

* `(array) $filter`

**Return Values**

`array`




<hr />


### Felamimail_Frontend_Json::searchMessages  

**Description**

```php
public searchMessages (array $filter, array $paging)
```

search messages in message cache 

 

**Parameters**

* `(array) $filter`
* `(array) $paging`

**Return Values**

`array`




<hr />


### Felamimail_Frontend_Json::sendReadingConfirmation  

**Description**

```php
public sendReadingConfirmation (string $messageId)
```

send reading confirmation 

 

**Parameters**

* `(string) $messageId`

**Return Values**

`array`




<hr />


### Felamimail_Frontend_Json::testIMapSettings  

**Description**

```php
public testIMapSettings ( $accountId,  $fields, bool $forceConnect)
```

test imap settings 

 

**Parameters**

* `() $accountId`
* `() $fields`
* `(bool) $forceConnect`

**Return Values**

`array`




**Throws Exceptions**


`\Tinebase_Exception_SystemGeneric`


<hr />


### Felamimail_Frontend_Json::testSmtpSettings  

**Description**

```php
public testSmtpSettings ( $accountId,  $fields, bool $forceConnect)
```

test smtp settings 

 

**Parameters**

* `() $accountId`
* `() $fields`
* `(bool) $forceConnect`

**Return Values**

`array`




**Throws Exceptions**


`\Tinebase_Exception_SystemGeneric`


`\Zend_Exception`


<hr />


### Felamimail_Frontend_Json::updateFlags  

**Description**

```php
public updateFlags (string $folderId, int $time)
```

update flags - use session/writeClose to allow following requests 

 

**Parameters**

* `(string) $folderId`
: id of active folder  
* `(int) $time`
: update time in seconds  

**Return Values**

`array`




<hr />


### Felamimail_Frontend_Json::updateFolderCache  

**Description**

```php
public updateFolderCache (string $accountId, string $folderName)
```

update folder cache 

 

**Parameters**

* `(string) $accountId`
* `(string) $folderName`
: of parent folder  

**Return Values**

`array`

> of (sub)folders in cache


<hr />


### Felamimail_Frontend_Json::updateMessageCache  

**Description**

```php
public updateMessageCache (string $folderId, int $time)
```

update message cache - use session/writeClose to update incomplete cache and allow following requests 

 

**Parameters**

* `(string) $folderId`
: id of active folder  
* `(int) $time`
: update time in seconds  

**Return Values**

`array`




<hr />

