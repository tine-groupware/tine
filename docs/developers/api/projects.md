# Projects API

## Relevante JSON-RPC-Methoden

Explizit in `Projects/Frontend/Json.php` vorhanden:

- `Projects.getRegistryData()`

Zusätzlich über das generische Model-API (Projekt-Modell ist als JSON-API exponiert):

- `Projects.searchProjects(filter, paging)`
- `Projects.getProject(id)`
- `Projects.saveProject(recordData)`
- `Projects.deleteProjects(ids)`

## Mögliche Werte

Typische Projektfelder (instanzabhängig durch Modellkonfiguration):

- `title`
- `description`
- `container_id`
- `status` / `number` / projektspezifische Custom Fields

Typische Paging-Werte:

- `sort`: z. B. `title`
- `dir`: `ASC` oder `DESC`
- `start`, `limit`

## Beispiele

### Projekte suchen

```json
{
  "jsonrpc": "2.0",
  "method": "Projects.searchProjects",
  "params": {
    "filter": [
      { "field": "query", "operator": "contains", "value": "Rollout" }
    ],
    "paging": { "sort": "title", "dir": "ASC", "start": 0, "limit": 50 }
  },
  "id": 40
}
```

### Projekt speichern

```json
{
  "jsonrpc": "2.0",
  "method": "Projects.saveProject",
  "params": {
    "recordData": {
      "title": "CRM Rollout 2026",
      "description": "Einführung in Region DACH",
      "container_id": "<PROJECT_CONTAINER_ID>"
    }
  },
  "id": 41
}
```

### Projekt löschen

```json
{
  "jsonrpc": "2.0",
  "method": "Projects.deleteProjects",
  "params": { "ids": ["<PROJECT_ID>"] },
  "id": 42
}
```
