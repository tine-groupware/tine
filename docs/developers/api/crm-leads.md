# CRM Leads API

## Relevante JSON-RPC-Methoden

- `Crm.searchLeads(filter, paging)`
- `Crm.getLead(id)`
- `Crm.saveLead(recordData)`
- `Crm.deleteLeads(ids)`
- `Crm.getDefaultContainer()`
- `Crm.getRegistryData()`

## Mögliche Werte

Pflichtfelder für `saveLead`:

- `lead_name`
- `leadstate_id`
- `leadtype_id`
- `leadsource_id`
- `start`

Optionale häufige Felder:

- `end`
- `description`
- `turnover`
- `probability` (typisch 0..100)
- `container_id`
- `relations` (z. B. Beziehung zu Kontakt als `CUSTOMER`)

Hinweis zu `leadstate_id`, `leadtype_id`, `leadsource_id`:

- Das sind numerische IDs aus der CRM-Konfiguration.
- Die gültigen Werte hängen von eurer Instanz ab (nicht global fest codiert).

Typische Paging-Werte:

- `sort`: z. B. `lead_name`, `start`, `end`
- `dir`: `ASC` oder `DESC`
- `start`, `limit`

## Lead im gemeinsamen CRM-Ordner speichern

Auch bei Leads gilt: Der Zielordner wird über `recordData.container_id` gesetzt.

Vorgehen:

1. Gemeinsame Lead-Container laden:
   - Methode: `Tinebase_Container.getContainer`
   - Parameter: `model = "Crm_Model_Lead"`, `containerType = "shared"`, `owner = null`
2. Gewünschte Container-`id` als `container_id` im Lead setzen.
3. `Crm.saveLead` ausführen.

Container-Abfrage (shared):

```json
{
  "jsonrpc": "2.0",
  "method": "Tinebase_Container.getContainer",
  "params": {
    "model": "Crm_Model_Lead",
    "containerType": "shared",
    "owner": null,
    "requiredGrants": "readGrant"
  },
  "id": 190
}
```

Lead in gemeinsamem Ordner speichern:

```json
{
  "jsonrpc": "2.0",
  "method": "Crm.saveLead",
  "params": {
    "recordData": {
      "lead_name": "Neukunde Shared",
      "leadstate_id": 1,
      "leadtype_id": 1,
      "leadsource_id": 1,
      "start": "2026-04-03 09:00:00",
      "container_id": "<SHARED_CRM_CONTAINER_ID>"
    }
  },
  "id": 191
}
```

Wichtig:

- Der API-User benötigt Schreibrechte auf dem gemeinsamen Lead-Container.
- Falls kein `container_id` gesetzt wird, landet der Lead typischerweise im Default-Container.

## Beispiele

### Leads suchen

```json
{
  "jsonrpc": "2.0",
  "method": "Crm.searchLeads",
  "params": {
    "filter": [
      { "field": "query", "operator": "contains", "value": "Angebot" }
    ],
    "paging": { "sort": "start", "dir": "DESC", "start": 0, "limit": 25 }
  },
  "id": 10
}
```

### Lead anlegen

```json
{
  "jsonrpc": "2.0",
  "method": "Crm.saveLead",
  "params": {
    "recordData": {
      "lead_name": "Neukunde Q2",
      "leadstate_id": 1,
      "leadtype_id": 1,
      "leadsource_id": 1,
      "start": "2026-04-03 09:00:00",
      "description": "Erstkontakt via Website",
      "probability": 40
    }
  },
  "id": 11
}
```

### Lead löschen

```json
{
  "jsonrpc": "2.0",
  "method": "Crm.deleteLeads",
  "params": { "ids": ["<LEAD_ID>"] },
  "id": 12
}
```
