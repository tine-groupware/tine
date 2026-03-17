tine Groupware Setup CLI
==================

This document describes the usage of the tine Groupware setup CLI (`setup.php`).

# Add Auth Token

The `--add_auth_token` command allows you to create a new authentication token for a user.

## Usage

```bash
php setup.php --add_auth_token -- user="<username>" auth_token="<token>" valid_until="<YYYY-MM-DD HH:MM:SS>" channels="<channel1,channel2>" [id="<optional-uid>"]
```

## Options

- `user`: The login name of the user. (Mandatory)
- `auth_token`: The authentication token string. (Mandatory)
- `valid_until`: The expiration date and time of the token (e.g., `2026-12-31 23:59:59`). (Mandatory)
- `channels`: A comma-separated list of channels (e.g., `web,sync,Addressbook.searchContacts`). (Mandatory)
- `id`: A unique identifier for the token. If omitted, a unique ID will be automatically generated. (Optional)

## Example

```bash
php setup.php --add_auth_token -- user="admin" auth_token="my-secret-token" valid_until="2026-12-31 23:59:59" channels="web,sync,Addressbook.searchContacts"
```
