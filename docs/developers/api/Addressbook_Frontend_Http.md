# Addressbook_Frontend_Http  

Addressbook http frontend class

This class handles all Http requests for the addressbook application  

## Implements:
Tinebase_Frontend_Interface

## Extend:

Tinebase_Frontend_Http_Abstract

## Methods

| Name | Description |
|------|-------------|
|[exportContacts](#addressbook_frontend_httpexportcontacts)|export contact|
|[exportLists](#addressbook_frontend_httpexportlists)|export list|

## Inherited methods

| Name | Description |
|------|-------------|
|__call|magic method for http api|



### Addressbook_Frontend_Http::exportContacts  

**Description**

```php
public exportContacts (string $filter, string $options)
```

export contact 

 

**Parameters**

* `(string) $filter`
: JSON encoded string with contact ids for multi export or contact filter  
* `(string) $options`
: format or export definition id  
  
TODO replace with generic export (via __call)  

**Return Values**

`void`


<hr />


### Addressbook_Frontend_Http::exportLists  

**Description**

```php
public exportLists (string $filter, string $options)
```

export list 

 

**Parameters**

* `(string) $filter`
: JSON encoded string with contact ids for multi export or contact filter  
* `(string) $options`
: format or export definition id  
  
TODO replace with generic export (via __call)  

**Return Values**

`void`


<hr />

