# Voipmanager_Frontend_Json  

backend class for Zend_Json_Server

This class handles all Json requests for the Voipmanager Management application  

## Implements:
Tinebase_Frontend_Json_Interface, Tinebase_Frontend_Interface

## Extend:

Tinebase_Frontend_Json_Abstract

## Methods

| Name | Description |
|------|-------------|
|[deleteAsteriskContexts](#voipmanager_frontend_jsondeleteasteriskcontexts)|delete multiple contexts|
|[deleteAsteriskMeetmes](#voipmanager_frontend_jsondeleteasteriskmeetmes)|delete multiple meetmes|
|[deleteAsteriskSipPeers](#voipmanager_frontend_jsondeleteasterisksippeers)|delete multiple asterisk sip peers|
|[deleteAsteriskVoicemails](#voipmanager_frontend_jsondeleteasteriskvoicemails)|delete multiple voicemails|
|[deleteSnomLocations](#voipmanager_frontend_jsondeletesnomlocations)|delete multiple locations|
|[deleteSnomPhoneSettings](#voipmanager_frontend_jsondeletesnomphonesettings)|delete phoneSettings|
|[deleteSnomPhones](#voipmanager_frontend_jsondeletesnomphones)|delete multiple phones|
|[deleteSnomSettings](#voipmanager_frontend_jsondeletesnomsettings)|delete multiple settings|
|[deleteSnomSoftwares](#voipmanager_frontend_jsondeletesnomsoftwares)|delete multiple softwareversion entries|
|[deleteSnomTemplates](#voipmanager_frontend_jsondeletesnomtemplates)|delete multiple template entries|
|[getAsteriskContext](#voipmanager_frontend_jsongetasteriskcontext)|get one context identified by contextId|
|[getAsteriskMeetme](#voipmanager_frontend_jsongetasteriskmeetme)|get one meetme identified by meetmeId|
|[getAsteriskSipPeer](#voipmanager_frontend_jsongetasterisksippeer)|get one asterisk sip peer identified by sipPeerId|
|[getAsteriskVoicemail](#voipmanager_frontend_jsongetasteriskvoicemail)|get one voicemail identified by voicemailId|
|[getSnomLocation](#voipmanager_frontend_jsongetsnomlocation)|get one location identified by locationId|
|[getSnomPhone](#voipmanager_frontend_jsongetsnomphone)|get one phone identified by phoneId|
|[getSnomPhoneSettings](#voipmanager_frontend_jsongetsnomphonesettings)|get one phoneSettings identified by phoneSettingsId|
|[getSnomSetting](#voipmanager_frontend_jsongetsnomsetting)|get one setting identified by settingId|
|[getSnomSoftware](#voipmanager_frontend_jsongetsnomsoftware)|get one software identified by softwareId|
|[getSnomTemplate](#voipmanager_frontend_jsongetsnomtemplate)|get one template identified by templateId|
|[resetHttpClientInfo](#voipmanager_frontend_jsonresethttpclientinfo)|send HTTP Client Info to multiple phones|
|[saveAsteriskContext](#voipmanager_frontend_jsonsaveasteriskcontext)|save one context|
|[saveAsteriskMeetme](#voipmanager_frontend_jsonsaveasteriskmeetme)|save one meetme|
|[saveAsteriskSipPeer](#voipmanager_frontend_jsonsaveasterisksippeer)|add/update asterisk sip peer|
|[saveAsteriskVoicemail](#voipmanager_frontend_jsonsaveasteriskvoicemail)|save one voicemail|
|[saveSnomLocation](#voipmanager_frontend_jsonsavesnomlocation)|save one location|
|[saveSnomPhone](#voipmanager_frontend_jsonsavesnomphone)|save one phone - if $recordData['id'] is empty the phone gets added, otherwise it gets updated|
|[saveSnomPhoneSettings](#voipmanager_frontend_jsonsavesnomphonesettings)|save one phoneSettings|
|[saveSnomSetting](#voipmanager_frontend_jsonsavesnomsetting)|save one setting|
|[saveSnomSoftware](#voipmanager_frontend_jsonsavesnomsoftware)|add/update software|
|[saveSnomTemplate](#voipmanager_frontend_jsonsavesnomtemplate)|add/update template|
|[searchAsteriskContexts](#voipmanager_frontend_jsonsearchasteriskcontexts)|Search for records matching given arguments|
|[searchAsteriskMeetmes](#voipmanager_frontend_jsonsearchasteriskmeetmes)|Search for records matching given arguments|
|[searchAsteriskSipPeers](#voipmanager_frontend_jsonsearchasterisksippeers)|Search for records matching given arguments|
|[searchAsteriskVoicemails](#voipmanager_frontend_jsonsearchasteriskvoicemails)|Search for records matching given arguments|
|[searchSnomLocations](#voipmanager_frontend_jsonsearchsnomlocations)|Search for records matching given arguments|
|[searchSnomPhones](#voipmanager_frontend_jsonsearchsnomphones)|Search for records matching given arguments|
|[searchSnomSettings](#voipmanager_frontend_jsonsearchsnomsettings)|Search for records matching given arguments|
|[searchSnomSoftwares](#voipmanager_frontend_jsonsearchsnomsoftwares)|Search for records matching given arguments|
|[searchSnomTemplates](#voipmanager_frontend_jsonsearchsnomtemplates)|Search for records matching given arguments|
|[updatePropertiesAsteriskSipPeer](#voipmanager_frontend_jsonupdatepropertiesasterisksippeer)|update multiple records|

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



### Voipmanager_Frontend_Json::deleteAsteriskContexts  

**Description**

```php
public deleteAsteriskContexts (array $ids)
```

delete multiple contexts 

 

**Parameters**

* `(array) $ids`
: list of contextId's to delete  

**Return Values**

`array`




<hr />


### Voipmanager_Frontend_Json::deleteAsteriskMeetmes  

**Description**

```php
public deleteAsteriskMeetmes (array $ids)
```

delete multiple meetmes 

 

**Parameters**

* `(array) $ids`
: list of meetmeId's to delete  

**Return Values**

`array`




<hr />


### Voipmanager_Frontend_Json::deleteAsteriskSipPeers  

**Description**

```php
public deleteAsteriskSipPeers (array $ids)
```

delete multiple asterisk sip peers 

 

**Parameters**

* `(array) $ids`
: list of sipPeerId's to delete  

**Return Values**

`array`




<hr />


### Voipmanager_Frontend_Json::deleteAsteriskVoicemails  

**Description**

```php
public deleteAsteriskVoicemails (array $ids)
```

delete multiple voicemails 

 

**Parameters**

* `(array) $ids`
: list of voicemailId's to delete  

**Return Values**

`array`




<hr />


### Voipmanager_Frontend_Json::deleteSnomLocations  

**Description**

```php
public deleteSnomLocations (array $ids)
```

delete multiple locations 

 

**Parameters**

* `(array) $ids`
: list of locationId's to delete  

**Return Values**

`array`




<hr />


### Voipmanager_Frontend_Json::deleteSnomPhoneSettings  

**Description**

```php
public deleteSnomPhoneSettings (array $ids)
```

delete phoneSettings 

 

**Parameters**

* `(array) $ids`
: phoneSettingsId to delete  

**Return Values**

`array`




<hr />


### Voipmanager_Frontend_Json::deleteSnomPhones  

**Description**

```php
public deleteSnomPhones (array $ids)
```

delete multiple phones 

 

**Parameters**

* `(array) $ids`
: list of phoneId's to delete  

**Return Values**

`array`




<hr />


### Voipmanager_Frontend_Json::deleteSnomSettings  

**Description**

```php
public deleteSnomSettings (array $ids)
```

delete multiple settings 

 

**Parameters**

* `(array) $ids`
: list of settingId's to delete  

**Return Values**

`array`




<hr />


### Voipmanager_Frontend_Json::deleteSnomSoftwares  

**Description**

```php
public deleteSnomSoftwares (array $ids)
```

delete multiple softwareversion entries 

 

**Parameters**

* `(array) $ids`
: list of softwareId's to delete  

**Return Values**

`array`




<hr />


### Voipmanager_Frontend_Json::deleteSnomTemplates  

**Description**

```php
public deleteSnomTemplates (array $ids)
```

delete multiple template entries 

 

**Parameters**

* `(array) $ids`
: list of templateId's to delete  

**Return Values**

`array`




<hr />


### Voipmanager_Frontend_Json::getAsteriskContext  

**Description**

```php
public getAsteriskContext (int $id)
```

get one context identified by contextId 

 

**Parameters**

* `(int) $id`

**Return Values**

`array`




<hr />


### Voipmanager_Frontend_Json::getAsteriskMeetme  

**Description**

```php
public getAsteriskMeetme (int $id)
```

get one meetme identified by meetmeId 

 

**Parameters**

* `(int) $id`

**Return Values**

`array`




<hr />


### Voipmanager_Frontend_Json::getAsteriskSipPeer  

**Description**

```php
public getAsteriskSipPeer (int $id)
```

get one asterisk sip peer identified by sipPeerId 

 

**Parameters**

* `(int) $id`

**Return Values**

`array`




<hr />


### Voipmanager_Frontend_Json::getAsteriskVoicemail  

**Description**

```php
public getAsteriskVoicemail (int $id)
```

get one voicemail identified by voicemailId 

 

**Parameters**

* `(int) $id`

**Return Values**

`array`




<hr />


### Voipmanager_Frontend_Json::getSnomLocation  

**Description**

```php
public getSnomLocation (int $id)
```

get one location identified by locationId 

 

**Parameters**

* `(int) $id`

**Return Values**

`array`




<hr />


### Voipmanager_Frontend_Json::getSnomPhone  

**Description**

```php
public getSnomPhone (int $id)
```

get one phone identified by phoneId 

 

**Parameters**

* `(int) $id`

**Return Values**

`array`




<hr />


### Voipmanager_Frontend_Json::getSnomPhoneSettings  

**Description**

```php
public getSnomPhoneSettings (int $id)
```

get one phoneSettings identified by phoneSettingsId 

 

**Parameters**

* `(int) $id`

**Return Values**

`array`




<hr />


### Voipmanager_Frontend_Json::getSnomSetting  

**Description**

```php
public getSnomSetting (int $id)
```

get one setting identified by settingId 

 

**Parameters**

* `(int) $id`

**Return Values**

`array`




<hr />


### Voipmanager_Frontend_Json::getSnomSoftware  

**Description**

```php
public getSnomSoftware (int $id)
```

get one software identified by softwareId 

 

**Parameters**

* `(int) $id`

**Return Values**

`array`




<hr />


### Voipmanager_Frontend_Json::getSnomTemplate  

**Description**

```php
public getSnomTemplate (int $id)
```

get one template identified by templateId 

 

**Parameters**

* `(int) $id`

**Return Values**

`array`




<hr />


### Voipmanager_Frontend_Json::resetHttpClientInfo  

**Description**

```php
public resetHttpClientInfo (array $phoneIds)
```

send HTTP Client Info to multiple phones 

 

**Parameters**

* `(array) $phoneIds`
: list of phoneId's to send http client info to  

**Return Values**

`array`




<hr />


### Voipmanager_Frontend_Json::saveAsteriskContext  

**Description**

```php
public saveAsteriskContext (array $recordData)
```

save one context 

if $contextData['id'] is empty the context gets added, otherwise it gets updated 

**Parameters**

* `(array) $recordData`
: an array of context properties  

**Return Values**

`array`




<hr />


### Voipmanager_Frontend_Json::saveAsteriskMeetme  

**Description**

```php
public saveAsteriskMeetme (array $recordData)
```

save one meetme 

if $meetmeData['id'] is empty the meetme gets added, otherwise it gets updated 

**Parameters**

* `(array) $recordData`
: an array of meetme properties  

**Return Values**

`array`




<hr />


### Voipmanager_Frontend_Json::saveAsteriskSipPeer  

**Description**

```php
public saveAsteriskSipPeer (array $recordData)
```

add/update asterisk sip peer 

if $sipPeerData['id'] is empty the sip peer gets added, otherwise it gets updated 

**Parameters**

* `(array) $recordData`
: an array of sipPeer properties  

**Return Values**

`array`




<hr />


### Voipmanager_Frontend_Json::saveAsteriskVoicemail  

**Description**

```php
public saveAsteriskVoicemail (array $recordData)
```

save one voicemail 

if $voicemailData['id'] is empty the voicemail gets added, otherwise it gets updated 

**Parameters**

* `(array) $recordData`
: an array of voicemail properties  

**Return Values**

`array`




<hr />


### Voipmanager_Frontend_Json::saveSnomLocation  

**Description**

```php
public saveSnomLocation (array $recordData)
```

save one location 

if $locationData['id'] is empty the location gets added, otherwise it gets updated 

**Parameters**

* `(array) $recordData`
: an array of location properties  

**Return Values**

`array`




<hr />


### Voipmanager_Frontend_Json::saveSnomPhone  

**Description**

```php
public saveSnomPhone (array $recordData)
```

save one phone - if $recordData['id'] is empty the phone gets added, otherwise it gets updated 

 

**Parameters**

* `(array) $recordData`
: an array of phone properties  

**Return Values**

`array`




<hr />


### Voipmanager_Frontend_Json::saveSnomPhoneSettings  

**Description**

```php
public saveSnomPhoneSettings (array $recordData)
```

save one phoneSettings 

if $phoneSettingsData['id'] is empty the phoneSettings gets added, otherwise it gets updated 

**Parameters**

* `(array) $recordData`
: an array of phoneSettings properties  

**Return Values**

`array`




<hr />


### Voipmanager_Frontend_Json::saveSnomSetting  

**Description**

```php
public saveSnomSetting (array $recordData)
```

save one setting 

if $settingData['id'] is empty the setting gets added, otherwise it gets updated 

**Parameters**

* `(array) $recordData`
: an array of setting properties  

**Return Values**

`array`




<hr />


### Voipmanager_Frontend_Json::saveSnomSoftware  

**Description**

```php
public saveSnomSoftware (array $recordData)
```

add/update software 

if $softwareData['id'] is empty the software gets added, otherwise it gets updated 

**Parameters**

* `(array) $recordData`
: an array of software properties  

**Return Values**

`array`




<hr />


### Voipmanager_Frontend_Json::saveSnomTemplate  

**Description**

```php
public saveSnomTemplate (array $recordData)
```

add/update template 

if $templateData['id'] is empty the template gets added, otherwise it gets updated 

**Parameters**

* `(array) $recordData`
: an array of template properties  

**Return Values**

`array`




<hr />


### Voipmanager_Frontend_Json::searchAsteriskContexts  

**Description**

```php
public searchAsteriskContexts (array $filter, array $paging)
```

Search for records matching given arguments 

 

**Parameters**

* `(array) $filter`
* `(array) $paging`

**Return Values**

`array`




<hr />


### Voipmanager_Frontend_Json::searchAsteriskMeetmes  

**Description**

```php
public searchAsteriskMeetmes (array $filter, array $paging)
```

Search for records matching given arguments 

 

**Parameters**

* `(array) $filter`
* `(array) $paging`

**Return Values**

`array`




<hr />


### Voipmanager_Frontend_Json::searchAsteriskSipPeers  

**Description**

```php
public searchAsteriskSipPeers (array $filter, array $paging)
```

Search for records matching given arguments 

 

**Parameters**

* `(array) $filter`
* `(array) $paging`

**Return Values**

`array`




<hr />


### Voipmanager_Frontend_Json::searchAsteriskVoicemails  

**Description**

```php
public searchAsteriskVoicemails (array $filter, array $paging)
```

Search for records matching given arguments 

 

**Parameters**

* `(array) $filter`
* `(array) $paging`

**Return Values**

`array`




<hr />


### Voipmanager_Frontend_Json::searchSnomLocations  

**Description**

```php
public searchSnomLocations (array $filter, array $paging)
```

Search for records matching given arguments 

 

**Parameters**

* `(array) $filter`
* `(array) $paging`

**Return Values**

`array`




<hr />


### Voipmanager_Frontend_Json::searchSnomPhones  

**Description**

```php
public searchSnomPhones (array $filter, array $paging)
```

Search for records matching given arguments 

 

**Parameters**

* `(array) $filter`
* `(array) $paging`

**Return Values**

`array`




<hr />


### Voipmanager_Frontend_Json::searchSnomSettings  

**Description**

```php
public searchSnomSettings (array $filter, array $paging)
```

Search for records matching given arguments 

 

**Parameters**

* `(array) $filter`
* `(array) $paging`

**Return Values**

`array`




<hr />


### Voipmanager_Frontend_Json::searchSnomSoftwares  

**Description**

```php
public searchSnomSoftwares (array $filter, array $paging)
```

Search for records matching given arguments 

 

**Parameters**

* `(array) $filter`
* `(array) $paging`

**Return Values**

`array`




<hr />


### Voipmanager_Frontend_Json::searchSnomTemplates  

**Description**

```php
public searchSnomTemplates (array $filter, array $paging)
```

Search for records matching given arguments 

 

**Parameters**

* `(array) $filter`
* `(array) $paging`

**Return Values**

`array`




<hr />


### Voipmanager_Frontend_Json::updatePropertiesAsteriskSipPeer  

**Description**

```php
public updatePropertiesAsteriskSipPeer (string $id, array $data)
```

update multiple records 

 

**Parameters**

* `(string) $id`
: record id  
* `(array) $data`
: key/value pairs to update  

**Return Values**

`\updated`

> record


<hr />

