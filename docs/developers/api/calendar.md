# Calendar API

## Relevante JSON-RPC-Methoden

- `Calendar.searchEvents(filter, paging)`
- `Calendar.getEvent(id)`
- `Calendar.saveEvent(recordData)`
- `Calendar.deleteEvents(ids, range)`
- `Calendar.searchResources(filter, paging)`
- `Calendar.getDefaultCalendar()`

## Mögliche Werte

Wichtige Event-Felder:

- `summary`
- `dtstart`, `dtend` (UTC bzw. JSON-Format wie im Client)
- `container_id`
- `description`
- `location`
- `status`: `CONFIRMED`, `TENTATIVE`, `CANCELLED`
- `class`: `PUBLIC`, `PRIVATE`
- `transp`: `OPAQUE`, `TRANSPARENT`

Mögliche `range`-Werte bei `deleteEvents`:

- `THIS`
- `THISANDFUTURE`
- `ALL`

Typische Paging-Werte:

- `sort`: z. B. `dtstart`, `summary`
- `dir`: `ASC` oder `DESC`
- `start`, `limit`

## Beispiele

### Termine suchen

```json
{
  "jsonrpc": "2.0",
  "method": "Calendar.searchEvents",
  "params": {
    "filter": [
      { "field": "period", "operator": "within", "value": { "from": "2026-04-01 00:00:00", "until": "2026-04-30 23:59:59" } }
    ],
    "paging": { "sort": "dtstart", "dir": "ASC", "start": 0, "limit": 100 }
  },
  "id": 20
}
```

### Termin speichern

```json
{
  "jsonrpc": "2.0",
  "method": "Calendar.saveEvent",
  "params": {
    "recordData": {
      "summary": "Projektmeeting",
      "dtstart": "2026-04-10 08:00:00",
      "dtend": "2026-04-10 09:00:00",
      "container_id": "<CALENDAR_CONTAINER_ID>",
      "status": "CONFIRMED",
      "class": "PUBLIC"
    }
  },
  "id": 21
}
```

### Serienereignis ab diesem Termin löschen

```json
{
  "jsonrpc": "2.0",
  "method": "Calendar.deleteEvents",
  "params": {
    "ids": ["<EVENT_ID>"],
    "range": "THISANDFUTURE"
  },
  "id": 22
}
```
