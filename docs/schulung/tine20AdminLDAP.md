Tine 2.0 Admin Schulung: LDAP Integration
=================

Version: Caroline 2017.11

Konfiguration und Problemlösungen im Zusammenhang mit der LDAP Anbindung (Authentifizierung und Benutzerkonten)

# Konfiguration Auth

Die Konfiguration kann z.B. über die setup.php angepasst werden ("Authentifizierung/Benutzerkonten").

Alternativ kann die CLI-Funktion --setconfig verwendet werden.

Die aktuelle Konfiguration kann man sich (JSON-kodiert) auch über die Kommandozeile anzeigen lassen:

    $ php /usr/share/tine20/setup.php --config=/etc/tine20 --getconfig -- configkey=Tinebase_Authentication_BackendConfiguration

## Benutzer-Filter

Gibt an, welche Benutzer sich einloggen können, kann z.B. auch auf bestimmte Gruppen eingeschränkt werden (siehe auch Benutzer/Gruppen-Filter).

Standard-Filter:

    &(objectClass=user)

_TODO add more info_

# Konfiguration User/Groups

Die Konfiguration kann z.B. über die setup.php angepasst werden ("Authentifizierung/Benutzerkonten").
Alternativ kann die CLI-Funktion --setconfig verwendet werden.

Die aktuelle Konfiguration kann man sich (JSON-kodiert) auch über die Kommandozeile anzeigen lassen:

    $ php /usr/share/tine20/setup.php --config=/etc/tine20 --getconfig -- configkey=Tinebase_User_BackendConfiguration
    
## readonly

Das bedeutet, dass Tine 2.0 keine Änderungen in den LDAP/AD schreibt. Der LDAP ist das führende System.

## Benutzer/Gruppen-Filter

Dieser Filter gibt an, welche Benutzer/Gruppen synchronisiert werden. Der Filter folgt der LDAP-Filter Syntax.
Hier ein paar Beispiele:

    &(objectClass=user)
    
-> Standardfilter, es werden nur Objekte der Klasse "user" synchronisiert 

    &(objectClass=user)(memberOf=CN=mygroup,CN=Users,DC=example,DC=org)
    
-> Erweiterung des Standardfilters, es werden nur Benutzer aus der Gruppe "mygroup" synchronisiert

# Sync-Konfiguration

_TODO add more info_

# Sync-Hooks

_TODO add more info_

# Email-Integration

_TODO add more info_

# sync accounts via CLI (und die Options)

Basis-Kommando (Users + Groups):

    php setup.php --sync_accounts_from_ldap

Nur Benutzer:

    php setup.php --sync_accounts_from_ldap --onlyusers

Benutzer, die nicht mehr im LDAP sind, löschen:

    php setup.php --sync_accounts_from_ldap --syncdeletedusers

Benutzer-Accountstatus synchronisieren:

    php setup.php --sync_accounts_from_ldap --syncaccountstatus

Benutzer-Kontaktphoto synchronisieren:

    php setup.php --sync_accounts_from_ldap --syncontactphoto

## scheduler

Der Scheduler führt den Sync-Users/Groups-Job 1x pro Stunde aus (table tine20_scheduler_task):

             name: Tinebase_User/Group::syncUsers/Groups
           config: {"cron":"0 * * * *","callables":[{"class":"Tinebase_User","method":"syncUsers","args":{"options":{"sync_with_config_options":true}}},{"class":"Tinebase_Group","method":"syncGroups"}]}
         last_run: 2019-08-28 14:00:01
    last_duration: 18
          lock_id: NULL
         next_run: 2019-08-28 15:00:00
     last_failure: 2019-03-31 12:00:01
    failure_count: 0
