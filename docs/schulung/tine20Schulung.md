Tine 2.0 Admin Schulung
=================

Version: Elena 2015.11

Ressourcen
------------

- Github: https://github.com/tine20/Tine-2.0-Open-Source-Groupware-and-CRM
- Wiki: https://wiki.tine20.org
- Slack: https://tine20.slack.com/
- Forum: http://www.tine20.org/forum/
- Mantis: https://forge.tine20.org/
- Handbuch: https://www.amazon.de/Tine-2-0-Benutzerhandbuch-Kai-Uwe-Kroll/dp/3737579385/

Installation / Update
------------

- Standard: via DEB/RPM/Appcenter Package
- sources.list Eintrag
- verschiedene Versionen 
- verschiedene (Meta-)Packages
- Installation via CLI inkl. Übergabe von Konfigurationsparametern

        php --config=/etc/tine20/config.inc.php setup.php --install -- \ 
            adminLoginName="admin" adminPassword="pw" adminEmailAddress="admin@example.org" \
            acceptedTermsVersion=1000 \
            imap="backend:standard,host:mail.tine20.org,port:143,useSystemAccount:1,ssl:tls,domain:tine20.org" 

- Update einspielen

        php --config=/etc/tine20/config.inc.php setup.php --update
         
- maintenance mode (de)aktivieren

        # activate (before update)
        php --config=/etc/tine20/config.inc.php setup.php --setconfig -- \
            configkey=maintenanceMode configvalue=1

        # deactivate (after successful update)
        php --config=/etc/tine20/config.inc.php setup.php --setconfig -- \
            configkey=maintenanceMode configvalue=0

Konfiguration
------------

- via setup.php
- via config.inc.php
- via DB
- via CLI
- via APP/config.inc.php
- via Admin/Apps/Settings
- Caching der Config
- Feature Switches
- Scheduler / Async Job

Sync
------------

- welche Clients werden unterstützt?
    - siehe Releasenotes
- Verwaltung von ActiveSync-Geräten
    - reset Device
    - Remote Wipe + Security Policies
- CardDAV, CalDAV und WebDAV
- Owncloud / Nextcloud

Absicherung
------------

- https
- config.inc.php + Bewegdaten (Logs, Tmp, Files) ausserhalb vom Docroot
- Captcha
- Filepermissions

Performance
------------

- PHP 7
- Webserver
- Caching
- Redis (auch für Sessions)
- DB
- Skalierung
- Queue-Worker

Backup/Restore
------------

- via CLI

        # backup
        php setup.php --backup -- db=1 files=1 backupDir=/backup
        
        # restore
        php setup.php --restore -- db=1 files=1 backupDir=/vagrant/backup/2016-08-03-13-20-47
        
- DB
- Files

LDAP-Integration
------------

- Konfiguration
- Sync-Konfiguration
- Sync-Hooks
- Email-Integration
- sync accounts via CLI (und die Options)
- UCS

siehe tine20AdminLDAP.md

Logging
------------

- Aufbau einer Log-Zeile
- Loglevel
- Filterung
- Tools/Techniken zum Extrahieren der benötigten Infos

Bugreports
------------

- welche Infos werden wohin versendet?
- auto-bugreporting

Benutzer und Gruppen / Rollen und Rechte
------------

- Rechtekonzept
- welche Rechte gibt es?

Ordner und Berechtigungen
------------

- Konzept
- aus Admin-Sicht

mögliche weitere Themen
------------

- CLI-API (siehe https://wiki.tine20.org/CLI_Functions)
- JSON-RPC API
- "virtuelles" Filesystem
- Lizenzhandling
- Volltext-Suche
- Benutzerdefinierte Felder und Tags
- E-Mail: Konten / Benutzer / Filter
- Ansible
- Development
    - CI / Gerrit
    - Branching-Modell
