# Addressbook_Frontend_Json  

Addressbook_Frontend_Json

This class handles all Json requests for the addressbook application  

## Implements:
Tinebase_Frontend_Json_Interface, Tinebase_Frontend_Interface

## Extend:

Tinebase_Frontend_Json_Abstract

## Methods

| Name | Description |
|------|-------------|
|[autoCompleteContactProperty](#addressbook_frontend_jsonautocompletecontactproperty)|return autocomplete suggestions for a given property and value|
|[deleteContacts](#addressbook_frontend_jsondeletecontacts)|delete multiple contacts|
|[deleteIndustrys](#addressbook_frontend_jsondeleteindustrys)|delete multiple industries|
|[deleteListRoles](#addressbook_frontend_jsondeletelistroles)|delete multiple list roles|
|[deleteLists](#addressbook_frontend_jsondeletelists)|delete multiple lists|
|[getContact](#addressbook_frontend_jsongetcontact)|get one contact identified by $id|
|[getDefaultAddressbook](#addressbook_frontend_jsongetdefaultaddressbook)|get default addressbook|
|[getIndustry](#addressbook_frontend_jsongetindustry)|get one industry identified by $id|
|[getList](#addressbook_frontend_jsongetlist)|get one list identified by $id|
|[getListRole](#addressbook_frontend_jsongetlistrole)|get one list role identified by $id|
|[parseAddressData](#addressbook_frontend_jsonparseaddressdata)|get contact information from string by parsing it using predefined rules|
|[resolveImages](#addressbook_frontend_jsonresolveimages)|resolve images|
|[saveContact](#addressbook_frontend_jsonsavecontact)|save one contact|
|[saveIndustry](#addressbook_frontend_jsonsaveindustry)|save industry|
|[saveList](#addressbook_frontend_jsonsavelist)|save one list|
|[saveListRole](#addressbook_frontend_jsonsavelistrole)|save list role|
|[searchContacts](#addressbook_frontend_jsonsearchcontacts)|Search for contacts matching given arguments|
|[searchContactsByRecipientsToken](#addressbook_frontend_jsonsearchcontactsbyrecipientstoken)|Search list and contact by recipient token data|
|[searchEmailAddresss](#addressbook_frontend_jsonsearchemailaddresss)|Search for Email Addresses with the Email Model in Lists and Contacts|
|[searchIndustrys](#addressbook_frontend_jsonsearchindustrys)|Search for industries matching given arguments|
|[searchListMemberRoles](#addressbook_frontend_jsonsearchlistmemberroles)|Search for lists member roles matching given arguments|
|[searchListRoles](#addressbook_frontend_jsonsearchlistroles)|Search for lists roles matching given arguments|
|[searchLists](#addressbook_frontend_jsonsearchlists)|Search for lists matching given arguments|

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



### Addressbook_Frontend_Json::autoCompleteContactProperty  

**Description**

```php
public autoCompleteContactProperty (string $property, string $startswith)
```

return autocomplete suggestions for a given property and value 

 

**Parameters**

* `(string) $property`
* `(string) $startswith`

**Return Values**

`array`




**Throws Exceptions**


`\Tasks_Exception_UnexpectedValue`


<hr />


### Addressbook_Frontend_Json::deleteContacts  

**Description**

```php
public deleteContacts (array $ids)
```

delete multiple contacts 

 

**Parameters**

* `(array) $ids`
: list of contactId's to delete  

**Return Values**

`array`




<hr />


### Addressbook_Frontend_Json::deleteIndustrys  

**Description**

```php
public deleteIndustrys (array $ids)
```

delete multiple industries 

 

**Parameters**

* `(array) $ids`
: list of listId's to delete  

**Return Values**

`array`




<hr />


### Addressbook_Frontend_Json::deleteListRoles  

**Description**

```php
public deleteListRoles (array $ids)
```

delete multiple list roles 

 

**Parameters**

* `(array) $ids`
: list of listId's to delete  

**Return Values**

`array`




<hr />


### Addressbook_Frontend_Json::deleteLists  

**Description**

```php
public deleteLists (array $ids)
```

delete multiple lists 

 

**Parameters**

* `(array) $ids`
: list of listId's to delete  

**Return Values**

`array`




<hr />


### Addressbook_Frontend_Json::getContact  

**Description**

```php
public getContact (string $id)
```

get one contact identified by $id 

 

**Parameters**

* `(string) $id`

**Return Values**

`array`




<hr />


### Addressbook_Frontend_Json::getDefaultAddressbook  

**Description**

```php
public getDefaultAddressbook (void)
```

get default addressbook 

 

**Parameters**

`This function has no parameters.`

**Return Values**

`array`




<hr />


### Addressbook_Frontend_Json::getIndustry  

**Description**

```php
public getIndustry (string $id)
```

get one industry identified by $id 

 

**Parameters**

* `(string) $id`

**Return Values**

`array`




<hr />


### Addressbook_Frontend_Json::getList  

**Description**

```php
public getList (string $id)
```

get one list identified by $id 

 

**Parameters**

* `(string) $id`

**Return Values**

`array`




<hr />


### Addressbook_Frontend_Json::getListRole  

**Description**

```php
public getListRole (string $id)
```

get one list role identified by $id 

 

**Parameters**

* `(string) $id`

**Return Values**

`array`




<hr />


### Addressbook_Frontend_Json::parseAddressData  

**Description**

```php
public parseAddressData (string $address)
```

get contact information from string by parsing it using predefined rules 

 

**Parameters**

* `(string) $address`

**Return Values**

`array`




<hr />


### Addressbook_Frontend_Json::resolveImages  

**Description**

```php
public static resolveImages (\Tinebase_Record_RecordSet $_records)
```

resolve images 

 

**Parameters**

* `(\Tinebase_Record_RecordSet) $_records`

**Return Values**

`void`


<hr />


### Addressbook_Frontend_Json::saveContact  

**Description**

```php
public saveContact (array $recordData, bool $duplicateCheck)
```

save one contact 

if $recordData['id'] is empty the contact gets added, otherwise it gets updated 

**Parameters**

* `(array) $recordData`
: an array of contact properties  
* `(bool) $duplicateCheck`

**Return Values**

`array`




<hr />


### Addressbook_Frontend_Json::saveIndustry  

**Description**

```php
public saveIndustry (array $recordData)
```

save industry 

 

**Parameters**

* `(array) $recordData`

**Return Values**

`array`




<hr />


### Addressbook_Frontend_Json::saveList  

**Description**

```php
public saveList (array $recordData, bool $duplicateCheck)
```

save one list 

if $recordData['id'] is empty the list gets added, otherwise it gets updated 

**Parameters**

* `(array) $recordData`
: an array of list properties  
* `(bool) $duplicateCheck`

**Return Values**

`array`




<hr />


### Addressbook_Frontend_Json::saveListRole  

**Description**

```php
public saveListRole (array $recordData)
```

save list role 

 

**Parameters**

* `(array) $recordData`

**Return Values**

`array`




<hr />


### Addressbook_Frontend_Json::searchContacts  

**Description**

```php
public searchContacts (array $filter, array $paging)
```

Search for contacts matching given arguments 

 

**Parameters**

* `(array) $filter`
* `(array) $paging`

**Return Values**

`array`




<hr />


### Addressbook_Frontend_Json::searchContactsByRecipientsToken  

**Description**

```php
public searchContactsByRecipientsToken (array $addressData)
```

Search list and contact by recipient token data 

 

**Parameters**

* `(array) $addressData`

**Return Values**

`array`




**Throws Exceptions**


`\Tinebase_Exception_InvalidArgument`


<hr />


### Addressbook_Frontend_Json::searchEmailAddresss  

**Description**

```php
public searchEmailAddresss (array $filter, array $paging)
```

Search for Email Addresses with the Email Model in Lists and Contacts 

 

**Parameters**

* `(array) $filter`
* `(array) $paging`

**Return Values**

`array`




<hr />


### Addressbook_Frontend_Json::searchIndustrys  

**Description**

```php
public searchIndustrys (array $filter, array $paging)
```

Search for industries matching given arguments 

 

**Parameters**

* `(array) $filter`
* `(array) $paging`

**Return Values**

`array`




<hr />


### Addressbook_Frontend_Json::searchListMemberRoles  

**Description**

```php
public searchListMemberRoles (array $filter, array $paging)
```

Search for lists member roles matching given arguments 

 

**Parameters**

* `(array) $filter`
* `(array) $paging`

**Return Values**

`array`




<hr />


### Addressbook_Frontend_Json::searchListRoles  

**Description**

```php
public searchListRoles (array $filter, array $paging)
```

Search for lists roles matching given arguments 

 

**Parameters**

* `(array) $filter`
* `(array) $paging`

**Return Values**

`array`




<hr />


### Addressbook_Frontend_Json::searchLists  

**Description**

```php
public searchLists (array $filter, array $paging)
```

Search for lists matching given arguments 

 

**Parameters**

* `(array) $filter`
* `(array) $paging`

**Return Values**

`array`




<hr />

