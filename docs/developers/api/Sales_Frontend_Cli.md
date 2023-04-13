# Sales_Frontend_Cli  

Cli frontend for Sales

This class handles cli requests for the Sales  



## Extend:

Tinebase_Frontend_Cli_Abstract

## Methods

| Name | Description |
|------|-------------|
|[addEmailToSalesAddress](#sales_frontend_cliaddemailtosalesaddress)|addEmailToSalesAddress|
|[create_auto_invoices](#sales_frontend_clicreate_auto_invoices)|creates missing accounts|
|[mergeContracts](#sales_frontend_climergecontracts)|merge contracts into one contract and removes the old ones|
|[migrateOffersToDocuments](#sales_frontend_climigrateofferstodocuments)|supports -d (dry-run)|
|[removeUnbilledAutoInvoices](#sales_frontend_cliremoveunbilledautoinvoices)|removes unbilled auto invoices|
|[setLastAutobill](#sales_frontend_clisetlastautobill)||
|[transferBillingInformation](#sales_frontend_clitransferbillinginformation)||
|[transferContractsToOrderConfirmation](#sales_frontend_clitransfercontractstoorderconfirmation)|transfers all contracts starting with AB- to orderconfirmation|
|[updateBillingInformation](#sales_frontend_cliupdatebillinginformation)||
|[updateLastAutobillOfProductAggregates](#sales_frontend_cliupdatelastautobillofproductaggregates)|sets start date and last_auobill by existing invoice positions / normalizes last_autobill|

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



### Sales_Frontend_Cli::addEmailToSalesAddress  

**Description**

```php
public addEmailToSalesAddress (void)
```

addEmailToSalesAddress 

 

**Parameters**

`This function has no parameters.`

**Return Values**

`void`


<hr />


### Sales_Frontend_Cli::create_auto_invoices  

**Description**

```php
public create_auto_invoices (\Zend_Console_Getopt $_opts)
```

creates missing accounts 

* optional params:  
- day=YYYY-MM-DD  
- remove_unbilled=1  
- contract=CONTRACT_ID or contract=NUMBER 

**Parameters**

* `(\Zend_Console_Getopt) $_opts`

**Return Values**

`bool`




<hr />


### Sales_Frontend_Cli::mergeContracts  

**Description**

```php
public mergeContracts (\Zend_Console_Getopt $_opts)
```

merge contracts into one contract and removes the old ones 

 

**Parameters**

* `(\Zend_Console_Getopt) $_opts`

**Return Values**

`void`


<hr />


### Sales_Frontend_Cli::migrateOffersToDocuments  

**Description**

```php
public migrateOffersToDocuments (\Zend_Console_Getopt $_opts)
```

supports -d (dry-run) 

 

**Parameters**

* `(\Zend_Console_Getopt) $_opts`

**Return Values**

`void`




**Throws Exceptions**


`\Tinebase_Exception_AccessDenied`


`\Tinebase_Exception_NotFound`


<hr />


### Sales_Frontend_Cli::removeUnbilledAutoInvoices  

**Description**

```php
public removeUnbilledAutoInvoices (\Sales_Model_Contract $contract)
```

removes unbilled auto invoices 

 

**Parameters**

* `(\Sales_Model_Contract) $contract`

**Return Values**

`void`


<hr />


### Sales_Frontend_Cli::setLastAutobill  

**Description**

```php
 setLastAutobill (void)
```

 

 

**Parameters**

`This function has no parameters.`

**Return Values**

`void`


<hr />


### Sales_Frontend_Cli::transferBillingInformation  

**Description**

```php
 transferBillingInformation (void)
```

 

 

**Parameters**

`This function has no parameters.`

**Return Values**

`void`


<hr />


### Sales_Frontend_Cli::transferContractsToOrderConfirmation  

**Description**

```php
public transferContractsToOrderConfirmation (void)
```

transfers all contracts starting with AB- to orderconfirmation 

 

**Parameters**

`This function has no parameters.`

**Return Values**

`void`


<hr />


### Sales_Frontend_Cli::updateBillingInformation  

**Description**

```php
 updateBillingInformation (void)
```

 

 

**Parameters**

`This function has no parameters.`

**Return Values**

`void`


<hr />


### Sales_Frontend_Cli::updateLastAutobillOfProductAggregates  

**Description**

```php
public updateLastAutobillOfProductAggregates (void)
```

sets start date and last_auobill by existing invoice positions / normalizes last_autobill 

 

**Parameters**

`This function has no parameters.`

**Return Values**

`void`


<hr />

