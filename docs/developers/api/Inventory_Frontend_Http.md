# Inventory_Frontend_Http  

Inventory http frontend class

This class handles all Http requests for the Inventory application  

## Implements:
Tinebase_Frontend_Interface

## Extend:

Tinebase_Frontend_Http_Abstract

## Methods

| Name | Description |
|------|-------------|
|[exportInventoryItems](#inventory_frontend_httpexportinventoryitems)|export inventoryItems|

## Inherited methods

| Name | Description |
|------|-------------|
|__call|magic method for http api|



### Inventory_Frontend_Http::exportInventoryItems  

**Description**

```php
public exportInventoryItems (string $filter, string $options)
```

export inventoryItems 

 

**Parameters**

* `(string) $filter`
: JSON encoded string with items ids for multi export or item filter  
* `(string) $options`
: format or export definition id  

**Return Values**

`void`


<hr />

