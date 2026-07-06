# Setup CLI

This document describes the usage of the tine Groupware setup CLI (`setup.php`).

## Add Auth Token

The `--add_auth_token` command allows you to create a new authentication token for a user.

### Usage

```bash
php setup.php --add_auth_token -- user="<username>" auth_token="<token>" valid_until="<YYYY-MM-DD HH:MM:SS>" channels="<channel1,channel2>" [id="<optional-uid>"]
```

### Options

- `user`: The login name of the user. (Mandatory)
- `auth_token`: The authentication token string. (Mandatory)
- `valid_until`: The expiration date and time of the token (e.g., `2026-12-31 23:59:59`). (Mandatory)
- `channels`: A comma-separated list of channels (e.g., `web,sync,Addressbook.searchContacts`). (Mandatory)
- `id`: A unique identifier for the token. If omitted, a unique ID will be automatically generated. (Optional)

### Example

```bash
php setup.php --add_auth_token -- user="admin" auth_token="my-secret-token" valid_until="2026-12-31 23:59:59" channels="web,sync,Addressbook.searchContacts"
```

## create_admin

### description
Creates a new admin user or activates an existing admin user. Allows to reset the admin password, too.
The function makes sure that the user is part of the relevant admin role and group.

### usage example
php setup.php --create_admin


## setconfig

### description
Allows to change config values for all applications. If "app" param is omitted, config is set for Tinebase.

### usage example
php setup.php --setconfig -- configkey=sample3 configvalue=value3 app=Addressbook


## update

### description
Update the database for all activated tine20 components. Can be invoked after upgrading instead of opening the setup-module in the browser.

### usage example
setup.php --update


## sync accounts (LDAP/AD)

### description
Synchronize user accounts and groups from LDAP/AD.

### usage example
setup.php --sync_accounts_from_ldap


===backup===

### description
Backup data, config and files.
You may choose what to backup and to include or exclude a Timestamp to the output file names.

### usage example
setup.php --backup -- config=1 db=1 files=1 backupDir=/backup/tine20 noTimestamp=1


## restore

### description
Restore data, config and files from a backup.

### usage example
setup.php --restore -- config=1 db=1 files=1 backupDir=/backup/tine20


## updateAllImportExportDefinitions

### description
Update Import and Export definitions for all applications.

### usage example
setup.php --updateAllImportExportDefinitions
