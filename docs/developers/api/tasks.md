# Tasks API

## Relevante JSON-RPC-Methoden

- `Tasks.searchTasks(filter, paging)`
- `Tasks.getTask(id)`
- `Tasks.saveTask(recordData)`
- `Tasks.deleteTasks(ids)`
- `Tasks.getDefaultContainer()`
- `Tasks.getRegistryData()`

## Mögliche Werte

Typische Task-Felder:

- `summary` (Pflicht)
- `description`
- `due`
- `priority`
- `percent` (0..100)
- `status`
- `organizer`
- `container_id`

Mögliche `status`-Werte:

- `NEEDS-ACTION` (Default)
- `IN-PROCESS`
- `COMPLETED`
- `CANCELLED`

Mögliche `class`-Werte:

- `PUBLIC`
- `PRIVATE`

Typische Paging-Werte:

- `sort`: z. B. `due`, `summary`
- `dir`: `ASC` oder `DESC`
- `start`, `limit`

## Beispiele

### Aufgaben suchen

```json
{
  "jsonrpc": "2.0",
  "method": "Tasks.searchTasks",
  "params": {
    "filter": [
      { "field": "query", "operator": "contains", "value": "Angebot" }
    ],
    "paging": { "sort": "due", "dir": "ASC", "start": 0, "limit": 50 }
  },
  "id": 30
}
```

### Aufgabe anlegen

```json
{
  "jsonrpc": "2.0",
  "method": "Tasks.saveTask",
  "params": {
    "recordData": {
      "summary": "Angebot nachfassen",
      "description": "Kunde am Dienstag anrufen",
      "due": "2026-04-08 10:00:00",
      "status": "IN-PROCESS",
      "percent": 20,
      "container_id": "<TASK_CONTAINER_ID>"
    }
  },
  "id": 31
}
```

### Aufgaben löschen

```json
{
  "jsonrpc": "2.0",
  "method": "Tasks.deleteTasks",
  "params": { "ids": ["<TASK_ID>"] },
  "id": 32
}
```
