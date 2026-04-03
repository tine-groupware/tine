# API Documentation

This section documents the API surface of tine based on the current source code.

## Scope

The API in tine is split into multiple server plugins/protocols:

- JSON-RPC API (`Tinebase_Server_Plugin_Json`)
- HTTP frontend API (`Tinebase_Server_Plugin_Http`)
- Expressive API (`Tinebase_Server_Plugin_Expressive`)
- WebDAV API (`Tinebase_Server_Plugin_WebDAV` and `Tinebase_Server_Plugin_WebDAVCatchAll`)
- ActiveSync API (`ActiveSync_Server_Plugin`)

Server plugin registration is configured in:

- `tine20/Tinebase/Config.php` (`$_serverPlugins`)
- `tine20/ActiveSync/Config.php` (`$_serverPlugins`)

## JSON-RPC API

Transport and dispatch details:

- Main entrypoint: `tine20/index.php`
- Dispatcher: `Tinebase_Core::dispatchRequest()`
- JSON plugin detection: `Tinebase_Server_Plugin_Json::getServer()`
- JSON handler: `Tinebase_Server_Json`

Important request headers and behavior:

- `X-TINE20-REQUEST-TYPE: JSON` selects JSON server dispatch
- `X-TINE20-JSONKEY` is required for authenticated/privileged JSON methods
- Empty method in JSON request returns JSON-SMD service map
- JSON-RPC envelope: 2.0

See:

- [JSON-RPC Reference](json-rpc-reference.md)
- Existing background documentation: `docs/developers/server/jsonApi.md`

## API aufrufen (JSON-RPC)

Endpoint:

- `https://<tine-host>/index.php`

Empfohlene Header:

- `Content-Type: application/json`
- `X-TINE20-REQUEST-TYPE: JSON`

Minimaler Request-Aufbau:

```json
{
	"jsonrpc": "2.0",
	"method": "<App>.<Methode>",
	"params": {},
	"id": 1
}
```

### Authentifizierung

1. Login per `Tinebase.login` aufrufen.
2. Aus der Login-Response `jsonKey` lesen.
3. Bei allen privilegierten Folgerequests den Header `X-TINE20-JSONKEY: <jsonKey>` mitsenden.

Beispiel Login:

```bash
curl -sS 'https://<tine-host>/index.php' \
	-H 'Content-Type: application/json' \
	-H 'X-TINE20-REQUEST-TYPE: JSON' \
	--data '{
		"jsonrpc":"2.0",
		"method":"Tinebase.login",
		"params":{"username":"<user>","password":"<password>"},
		"id":1
	}'
```

Beispiel API-Call nach Login:

```bash
curl -sS 'https://<tine-host>/index.php' \
	-H 'Content-Type: application/json' \
	-H 'X-TINE20-REQUEST-TYPE: JSON' \
	-H 'X-TINE20-JSONKEY: <jsonKey>' \
	--data '{
		"jsonrpc":"2.0",
		"method":"Addressbook.searchContacts",
		"params":{
			"filter":[{"field":"query","operator":"contains","value":"Muster"}],
			"paging":{"sort":"n_fileas","dir":"ASC","start":0,"limit":50}
		},
		"id":2
	}'
```

Hinweis:

- Ein Request mit leerer Methode liefert die JSON-SMD Service Map.
- `Tinebase.logout` beendet die Sitzung.

## PHP-Client

Es gibt einen offiziellen PHP-Client für tine JSON-RPC:

- `https://github.com/tine-groupware/tine-client-php`

Beispiel aus dem Client-Repo (Lead speichern):

```php
$tineConnector->login();
$method = 'Crm.saveLead';
$result = $tineConnector->{$method}(recordData: [
		'lead_name' => 'My special lead',
		'leadstate_id' => 1,
		'leadtype_id' => 1,
		'leadsource_id' => 1,
]);
$tineConnector->logout();
```

## Modul-spezifische Beispiele

- [Addressbook API](addressbook.md)
- [CRM Leads API](crm-leads.md)
- [Calendar API](calendar.md)
- [Tasks API](tasks.md)
- [Projects API](projects.md)
- [Sales API](sales.md)

## Public HTTP API

Public API methods are exposed in code via methods with name pattern:

- `publicApi*`

These methods are primarily found in app controllers and represent public endpoints handled by HTTP/Expressive routing.

See:

- [Public HTTP API Reference](public-http-reference.md)

## Regenerating This Documentation

The two reference pages are generated from source code with:

- all `public function publicApi*` methods in `tine20/**/*.php`
- all `public function ...` methods in `tine20/**/Frontend/Json.php` and `tine20/**/Frontend/JsonPublic.php` (excluding internal methods prefixed with `_`)

If API code changes, regenerate the pages to keep this section in sync.
