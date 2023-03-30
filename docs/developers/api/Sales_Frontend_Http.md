# Sales_Frontend_Http  

This class handles all Http requests for the Sales application

## Implements:
Tinebase_Frontend_Interface

## Extend:

Tinebase_Frontend_Http_Abstract

## Methods

| Name | Description |
|------|-------------|
|[exportInvoicePositions](#sales_frontend_httpexportinvoicepositions)|export invoice positions by invoice id and accountable (php class name)|

## Inherited methods

| Name | Description |
|------|-------------|
|__call|magic method for http api|



### Sales_Frontend_Http::exportInvoicePositions  

**Description**

```php
public exportInvoicePositions (string $invoiceId, string $accountable)
```

export invoice positions by invoice id and accountable (php class name) 

 

**Parameters**

* `(string) $invoiceId`
* `(string) $accountable`

**Return Values**

`void`


**Throws Exceptions**


`\Tinebase_Exception_InvalidArgument`


<hr />

