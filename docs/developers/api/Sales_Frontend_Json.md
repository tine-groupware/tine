# Sales_Frontend_Json  

This class handles all Json requests for the Sales application

## Implements:
Tinebase_Frontend_Json_Interface, Tinebase_Frontend_Interface

## Extend:

Tinebase_Frontend_Json_Abstract

## Methods

| Name | Description |
|------|-------------|
|[__construct](#sales_frontend_json__construct)|the constructor|
|[billContract](#sales_frontend_jsonbillcontract)|rebills an invoice|
|[createFollowupDocument](#sales_frontend_jsoncreatefollowupdocument)||
|[createPaperSlip](#sales_frontend_jsoncreatepaperslip)||
|[createTimesheetForInvoice](#sales_frontend_jsoncreatetimesheetforinvoice)||
|[deleteContracts](#sales_frontend_jsondeletecontracts)|deletes existing records|
|[deleteCustomers](#sales_frontend_jsondeletecustomers)|deletes existing records|
|[deleteInvoices](#sales_frontend_jsondeleteinvoices)|deletes existing records|
|[deleteOffers](#sales_frontend_jsondeleteoffers)|deletes existing records|
|[deleteOrderConfirmations](#sales_frontend_jsondeleteorderconfirmations)|deletes existing records|
|[deletePurchaseInvoices](#sales_frontend_jsondeletepurchaseinvoices)|deletes existing records|
|[deleteSuppliers](#sales_frontend_jsondeletesuppliers)|deletes existing records|
|[getApplicableBoilerplates](#sales_frontend_jsongetapplicableboilerplates)||
|[getConfig](#sales_frontend_jsongetconfig)|Get Config for Sales|
|[getContract](#sales_frontend_jsongetcontract)|Return a single record|
|[getCustomer](#sales_frontend_jsongetcustomer)|Return a single record|
|[getInvoice](#sales_frontend_jsongetinvoice)|Return a single record|
|[getOffer](#sales_frontend_jsongetoffer)|Return a single record|
|[getOrderConfirmation](#sales_frontend_jsongetorderconfirmation)|Return a single record|
|[getPurchaseInvoice](#sales_frontend_jsongetpurchaseinvoice)|Return a single record|
|[getSharedOrderDocumentTransition](#sales_frontend_jsongetsharedorderdocumenttransition)||
|[getSupplier](#sales_frontend_jsongetsupplier)|Return a single record|
|[mergeInvoice](#sales_frontend_jsonmergeinvoice)|merge an invoice|
|[rebillInvoice](#sales_frontend_jsonrebillinvoice)|rebills an invoice|
|[saveContract](#sales_frontend_jsonsavecontract)|creates/updates a record|
|[saveCustomer](#sales_frontend_jsonsavecustomer)|creates/updates a record|
|[saveInvoice](#sales_frontend_jsonsaveinvoice)|creates/updates a record|
|[saveOffer](#sales_frontend_jsonsaveoffer)|creates/updates a record|
|[saveOrderConfirmation](#sales_frontend_jsonsaveorderconfirmation)|creates/updates a record|
|[savePurchaseInvoice](#sales_frontend_jsonsavepurchaseinvoice)|creates/updates a record|
|[saveSupplier](#sales_frontend_jsonsavesupplier)|creates/updates a record|
|[searchContracts](#sales_frontend_jsonsearchcontracts)|Search for records matching given arguments|
|[searchCustomers](#sales_frontend_jsonsearchcustomers)|Search for records matching given arguments|
|[searchInvoices](#sales_frontend_jsonsearchinvoices)|Search for records matching given arguments|
|[searchOffers](#sales_frontend_jsonsearchoffers)|Search for records matching given arguments|
|[searchOrderConfirmations](#sales_frontend_jsonsearchorderconfirmations)|Search for records matching given arguments|
|[searchProductAggregates](#sales_frontend_jsonsearchproductaggregates)||
|[searchPurchaseInvoices](#sales_frontend_jsonsearchpurchaseinvoices)|Search for records matching given arguments|
|[searchSuppliers](#sales_frontend_jsonsearchsuppliers)|Search for records matching given arguments|
|[setConfig](#sales_frontend_jsonsetconfig)|Sets the config for Sales|
|[trackDocument](#sales_frontend_jsontrackdocument)||

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



### Sales_Frontend_Json::__construct  

**Description**

```php
public __construct (void)
```

the constructor 

 

**Parameters**

`This function has no parameters.`

**Return Values**

`void`


<hr />


### Sales_Frontend_Json::billContract  

**Description**

```php
public billContract (string $id, string $date)
```

rebills an invoice 

 

**Parameters**

* `(string) $id`
* `(string) $date`

**Return Values**

`void`


<hr />


### Sales_Frontend_Json::createFollowupDocument  

**Description**

```php
 createFollowupDocument (void)
```

 

 

**Parameters**

`This function has no parameters.`

**Return Values**

`void`


<hr />


### Sales_Frontend_Json::createPaperSlip  

**Description**

```php
 createPaperSlip (void)
```

 

 

**Parameters**

`This function has no parameters.`

**Return Values**

`void`


<hr />


### Sales_Frontend_Json::createTimesheetForInvoice  

**Description**

```php
 createTimesheetForInvoice (void)
```

 

 

**Parameters**

`This function has no parameters.`

**Return Values**

`void`


<hr />


### Sales_Frontend_Json::deleteContracts  

**Description**

```php
public deleteContracts (array $ids)
```

deletes existing records 

 

**Parameters**

* `(array) $ids`

**Return Values**

`string`




<hr />


### Sales_Frontend_Json::deleteCustomers  

**Description**

```php
public deleteCustomers (array $ids)
```

deletes existing records 

 

**Parameters**

* `(array) $ids`

**Return Values**

`string`




<hr />


### Sales_Frontend_Json::deleteInvoices  

**Description**

```php
public deleteInvoices (array $ids)
```

deletes existing records 

 

**Parameters**

* `(array) $ids`

**Return Values**

`string`




<hr />


### Sales_Frontend_Json::deleteOffers  

**Description**

```php
public deleteOffers (array $ids)
```

deletes existing records 

 

**Parameters**

* `(array) $ids`

**Return Values**

`string`




<hr />


### Sales_Frontend_Json::deleteOrderConfirmations  

**Description**

```php
public deleteOrderConfirmations (array $ids)
```

deletes existing records 

 

**Parameters**

* `(array) $ids`

**Return Values**

`string`




<hr />


### Sales_Frontend_Json::deletePurchaseInvoices  

**Description**

```php
public deletePurchaseInvoices (array $ids)
```

deletes existing records 

 

**Parameters**

* `(array) $ids`

**Return Values**

`string`




<hr />


### Sales_Frontend_Json::deleteSuppliers  

**Description**

```php
public deleteSuppliers (array $ids)
```

deletes existing records 

 

**Parameters**

* `(array) $ids`

**Return Values**

`string`




<hr />


### Sales_Frontend_Json::getApplicableBoilerplates  

**Description**

```php
 getApplicableBoilerplates (void)
```

 

 

**Parameters**

`This function has no parameters.`

**Return Values**

`void`


<hr />


### Sales_Frontend_Json::getConfig  

**Description**

```php
public getConfig (void)
```

Get Config for Sales 

 

**Parameters**

`This function has no parameters.`

**Return Values**

`array`




<hr />


### Sales_Frontend_Json::getContract  

**Description**

```php
public getContract (string $id)
```

Return a single record 

 

**Parameters**

* `(string) $id`

**Return Values**

`array`

> record data


<hr />


### Sales_Frontend_Json::getCustomer  

**Description**

```php
public getCustomer (string $id)
```

Return a single record 

 

**Parameters**

* `(string) $id`

**Return Values**

`array`

> record data


<hr />


### Sales_Frontend_Json::getInvoice  

**Description**

```php
public getInvoice (string $id)
```

Return a single record 

 

**Parameters**

* `(string) $id`

**Return Values**

`array`

> record data


<hr />


### Sales_Frontend_Json::getOffer  

**Description**

```php
public getOffer (string $id)
```

Return a single record 

 

**Parameters**

* `(string) $id`

**Return Values**

`array`

> record data


<hr />


### Sales_Frontend_Json::getOrderConfirmation  

**Description**

```php
public getOrderConfirmation (string $id)
```

Return a single record 

 

**Parameters**

* `(string) $id`

**Return Values**

`array`

> record data


<hr />


### Sales_Frontend_Json::getPurchaseInvoice  

**Description**

```php
public getPurchaseInvoice (string $id)
```

Return a single record 

 

**Parameters**

* `(string) $id`

**Return Values**

`array`

> record data


<hr />


### Sales_Frontend_Json::getSharedOrderDocumentTransition  

**Description**

```php
 getSharedOrderDocumentTransition (void)
```

 

 

**Parameters**

`This function has no parameters.`

**Return Values**

`void`


<hr />


### Sales_Frontend_Json::getSupplier  

**Description**

```php
public getSupplier (string $id)
```

Return a single record 

 

**Parameters**

* `(string) $id`

**Return Values**

`array`

> record data


<hr />


### Sales_Frontend_Json::mergeInvoice  

**Description**

```php
public mergeInvoice (string $id)
```

merge an invoice 

 

**Parameters**

* `(string) $id`

**Return Values**

`void`


<hr />


### Sales_Frontend_Json::rebillInvoice  

**Description**

```php
public rebillInvoice (string $id)
```

rebills an invoice 

 

**Parameters**

* `(string) $id`

**Return Values**

`void`


<hr />


### Sales_Frontend_Json::saveContract  

**Description**

```php
public saveContract (array $recordData)
```

creates/updates a record 

 

**Parameters**

* `(array) $recordData`

**Return Values**

`array`

> created/updated record


<hr />


### Sales_Frontend_Json::saveCustomer  

**Description**

```php
public saveCustomer (array $recordData, bool $duplicateCheck)
```

creates/updates a record 

 

**Parameters**

* `(array) $recordData`
* `(bool) $duplicateCheck`

**Return Values**

`array`

> created/updated record


<hr />


### Sales_Frontend_Json::saveInvoice  

**Description**

```php
public saveInvoice (array $recordData, bool $duplicateCheck)
```

creates/updates a record 

 

**Parameters**

* `(array) $recordData`
* `(bool) $duplicateCheck`

**Return Values**

`array`

> created/updated record


**Throws Exceptions**


`\Tinebase_Exception_SystemGeneric`


<hr />


### Sales_Frontend_Json::saveOffer  

**Description**

```php
public saveOffer (array $recordData, bool $duplicateCheck)
```

creates/updates a record 

 

**Parameters**

* `(array) $recordData`
* `(bool) $duplicateCheck`

**Return Values**

`array`

> created/updated record


<hr />


### Sales_Frontend_Json::saveOrderConfirmation  

**Description**

```php
public saveOrderConfirmation (array $recordData, bool $duplicateCheck)
```

creates/updates a record 

 

**Parameters**

* `(array) $recordData`
* `(bool) $duplicateCheck`

**Return Values**

`array`

> created/updated record


<hr />


### Sales_Frontend_Json::savePurchaseInvoice  

**Description**

```php
public savePurchaseInvoice (array $recordData, bool $duplicateCheck)
```

creates/updates a record 

 

**Parameters**

* `(array) $recordData`
* `(bool) $duplicateCheck`

**Return Values**

`array`

> created/updated record


<hr />


### Sales_Frontend_Json::saveSupplier  

**Description**

```php
public saveSupplier (array $recordData, bool $duplicateCheck)
```

creates/updates a record 

 

**Parameters**

* `(array) $recordData`
* `(bool) $duplicateCheck`

**Return Values**

`array`

> created/updated record


<hr />


### Sales_Frontend_Json::searchContracts  

**Description**

```php
public searchContracts (array $filter, array $paging)
```

Search for records matching given arguments 

 

**Parameters**

* `(array) $filter`
* `(array) $paging`

**Return Values**

`array`




<hr />


### Sales_Frontend_Json::searchCustomers  

**Description**

```php
public searchCustomers (array $filter, array $paging)
```

Search for records matching given arguments 

 

**Parameters**

* `(array) $filter`
* `(array) $paging`

**Return Values**

`array`




<hr />


### Sales_Frontend_Json::searchInvoices  

**Description**

```php
public searchInvoices (array $filter, array $paging)
```

Search for records matching given arguments 

 

**Parameters**

* `(array) $filter`
* `(array) $paging`

**Return Values**

`array`




<hr />


### Sales_Frontend_Json::searchOffers  

**Description**

```php
public searchOffers (array $filter, array $paging)
```

Search for records matching given arguments 

 

**Parameters**

* `(array) $filter`
* `(array) $paging`

**Return Values**

`array`




<hr />


### Sales_Frontend_Json::searchOrderConfirmations  

**Description**

```php
public searchOrderConfirmations (array $filter, array $paging)
```

Search for records matching given arguments 

 

**Parameters**

* `(array) $filter`
* `(array) $paging`

**Return Values**

`array`




<hr />


### Sales_Frontend_Json::searchProductAggregates  

**Description**

```php
 searchProductAggregates (void)
```

 

 

**Parameters**

`This function has no parameters.`

**Return Values**

`void`


<hr />


### Sales_Frontend_Json::searchPurchaseInvoices  

**Description**

```php
public searchPurchaseInvoices (array $filter, array $paging)
```

Search for records matching given arguments 

 

**Parameters**

* `(array) $filter`
* `(array) $paging`

**Return Values**

`array`




<hr />


### Sales_Frontend_Json::searchSuppliers  

**Description**

```php
public searchSuppliers (array $filter, array $paging)
```

Search for records matching given arguments 

 

**Parameters**

* `(array) $filter`
* `(array) $paging`

**Return Values**

`array`




<hr />


### Sales_Frontend_Json::setConfig  

**Description**

```php
public setConfig (array $config)
```

Sets the config for Sales 

 

**Parameters**

* `(array) $config`

**Return Values**

`void`


<hr />


### Sales_Frontend_Json::trackDocument  

**Description**

```php
 trackDocument (void)
```

 

 

**Parameters**

`This function has no parameters.`

**Return Values**

`void`


<hr />

