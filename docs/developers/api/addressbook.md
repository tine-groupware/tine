# Addressbook API

## Relevante JSON-RPC-Methoden

- `Addressbook.searchContacts(filter, paging)`
- `Addressbook.getContact(id)`
- `Addressbook.saveContact(recordData)`
- `Addressbook.deleteContacts(ids)`
- `Addressbook.searchLists(filter, paging)`
- `Addressbook.saveList(recordData)`
- `Addressbook.searchEmailAddresss(filter, paging)`

## Mögliche Werte

Typische Filterfelder (Kontakt):

- `query` (Volltext)
- `n_family`, `n_given`, `n_fileas`
- `email_query`
- `container_id`
- `type` (`contact`, `user`, `email_account`)

Typische Operatoren:

- `contains`
- `equals`
- `startswith`
- `in`

Typische Paging-Werte:

- `sort`: z. B. `n_fileas`, `n_family`, `n_given`
- `dir`: `ASC` oder `DESC`
- `start`: Offset (z. B. `0`)
- `limit`: Anzahl (z. B. `50`)

Beispiel-Felder für `saveContact`:

- `n_given`, `n_family`, `n_fn`
- `email`, `email_home`
- `container_id`
- `org_name`, `title`, `tel_work`, `tel_cell`
- `preferred_email` (typisch `email` oder `email_home`)

## Kontakt im gemeinsamen Adressbuch speichern

Das Ziel-Adressbuch steuerst du immer über `recordData.container_id`.

Vorgehen:

1. Gemeinsame Addressbook-Container laden (read Grant reicht):
   - Methode: `Tinebase_Container.getContainer`
   - Parameter: `model = "Addressbook_Model_Contact"`, `containerType = "shared"`, `owner = null`
2. Gewünschte `id` aus der Response als `container_id` verwenden.
3. `Addressbook.saveContact` mit dieser `container_id` aufrufen.

Container-Abfrage (shared):

```json
{
  "jsonrpc": "2.0",
  "method": "Tinebase_Container.getContainer",
  "params": {
    "model": "Addressbook_Model_Contact",
    "containerType": "shared",
    "owner": null,
    "requiredGrants": "readGrant"
  },
  "id": 90
}
```

Kontakt in gemeinsames Adressbuch speichern:

```json
{
  "jsonrpc": "2.0",
  "method": "Addressbook.saveContact",
  "params": {
    "recordData": {
      "n_given": "Erika",
      "n_family": "Mustermann",
      "email": "erika.mustermann@example.org",
      "container_id": "<SHARED_ADDRESSBOOK_CONTAINER_ID>"
    }
  },
  "id": 91
}
```

Wichtig:

- Der aufrufende Account braucht Schreibrechte auf dem gemeinsamen Container.
- Ohne passende Grants folgt ein Access-Denied-Fehler.

## Beispiele

### Kontakte suchen

```json
{
  "jsonrpc": "2.0",
  "method": "Addressbook.searchContacts",
  "params": {
    "filter": [
      { "field": "query", "operator": "contains", "value": "Muster" }
    ],
    "paging": { "sort": "n_fileas", "dir": "ASC", "start": 0, "limit": 50 }
  },
  "id": 1
}
```

### Kontakt anlegen

```json
{
  "jsonrpc": "2.0",
  "method": "Addressbook.saveContact",
  "params": {
    "recordData": {
      "n_given": "Max",
      "n_family": "Muster",
      "email": "max.muster@example.org",
      "container_id": "<ADDRESSBOOK_CONTAINER_ID>",
      "org_name": "Muster GmbH",
      "tel_work": "+49 40 123456"
    }
  },
  "id": 2
}
```

### Kontakte löschen

```json
{
  "jsonrpc": "2.0",
  "method": "Addressbook.deleteContacts",
  "params": { "ids": ["<CONTACT_ID_1>", "<CONTACT_ID_2>"] },
  "id": 3
}
```
