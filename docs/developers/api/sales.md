# Sales API

## Relevante JSON-RPC-Methoden

Vertrags- und Kundenbereich:

- `Sales.searchContracts(filter, paging)`
- `Sales.getContract(id)`
- `Sales.saveContract(recordData)`
- `Sales.deleteContracts(ids)`
- `Sales.searchCustomers(filter, paging)`
- `Sales.getCustomer(id)`
- `Sales.saveCustomer(recordData)`
- `Sales.deleteCustomers(ids)`

Weitere häufige Bereiche:

- `Sales.searchProductAggregates(filter, paging)`
- `Sales.billContract(id, date)`
- `Sales.getConfig()`
- `Sales.setConfig(config)`

Je nach aktivierten Sales-Features zusätzlich z. B.:

- Offers
- Invoices
- Order Confirmations
- Suppliers
- Purchase Invoices

## Mögliche Werte

Häufige Felder bei `Contract`:

- `title` / `number`
- `customer_id`
- `container_id`
- `start_date`, `end_date`
- `billing_address_id`
- `products`

Häufige Felder bei `Customer`:

- `name`
- `number`
- `description`
- `postal_id` / Adressbezug

Typische Paging-Werte:

- `sort`: z. B. `number`, `title`, `name`
- `dir`: `ASC` oder `DESC`
- `start`, `limit`

## Beispiele

### Kunden suchen

```json
{
  "jsonrpc": "2.0",
  "method": "Sales.searchCustomers",
  "params": {
    "filter": [
      { "field": "query", "operator": "contains", "value": "GmbH" }
    ],
    "paging": { "sort": "name", "dir": "ASC", "start": 0, "limit": 50 }
  },
  "id": 50
}
```

### Vertrag speichern

```json
{
  "jsonrpc": "2.0",
  "method": "Sales.saveContract",
  "params": {
    "recordData": {
      "title": "Servicevertrag Premium",
      "customer_id": "<CUSTOMER_ID>",
      "container_id": "<CONTRACT_CONTAINER_ID>",
      "start_date": "2026-04-01"
    }
  },
  "id": 51
}
```

### Vertrag abrechnen

```json
{
  "jsonrpc": "2.0",
  "method": "Sales.billContract",
  "params": {
    "id": "<CONTRACT_ID>",
    "date": "2026-04-30"
  },
  "id": 52
}
```
