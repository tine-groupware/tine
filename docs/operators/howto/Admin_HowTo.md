Tine Admin HowTo
=================

Version: Ellie 2023.11

# Ressourcen

- Github: https://github.com/tine-groupware/tine
- Forum: https://github.com/tine-groupware/tine/discussions
- Handbuch: https://www.amazon.de/Tine-2-0-Benutzerhandbuch-Kai-Uwe-Kroll/dp/3737579385/ bzw. UserManual App
- Community Chat: https://matrix.to/#/!gGPNgDOyMWwSPjFFXa:matrix.org

# Serveraustattung

## Beispielsetup mit OO + Docservice 2020.11

tine VM webserver
- 16GB RAM
- 4 vcpus
- storage
  - system 100G
  - tine filesystem in eigenem volume: 2TB
- ubuntu 20.04 (LTS)

tine VM DB
- ubuntu 20.04 (LTS)
- 24GB RAM
- 2-4 vcpus
- storage
  - system 100G
- db 0,5-1TB
- mariadb
- redis

onlyoffice
  - ubuntu 20.04 (LTS)
  - docker host
  - 2-4G RAM
  - 2 vCPUs
- storage 100G
  - system 50G
  - /data 50G

docservice
  - ubuntu 20.04 (LTS)
  - 2-4G RAM
  - 2 vCPUs
  - storage: 50G

### Ressourcenbedarf pro Anzahl User

- vCPUS
  - DB
    ~1 pro 100 User 
  - Webserver
    ~1 pro 100 User 
  - Webserver mit viel Sync (WebDAV/ActiveSync)
    ~2 pro 100 User
- RAM
  - DB
    ~1G pr 50 User
  - Webserver
    ~1G pro 100 User

### Docservice (50-100 Users)

- 4G RAM
- 2 vCPUs

### OnlyOffice (50-100 Users)

- 4G RAM
- 2 vCPUs

# Installation / Update

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
         
- Maintenance mode (de)aktivieren

        # activate (before update - all users)
        php --config=/etc/tine20/config.inc.php setup.php --maintenance_mode -- state=on

        # deactivate (after successful update)
        php --config=/etc/tine20/config.inc.php setup.php --maintenance_mode -- state=off

# Konfiguration

- via setup.php
- via config.inc.php
- via DB
- via CLI
- via APP/config.inc.php
- via Admin/Apps/Settings
- Caching der Config
- Feature Switches
- Scheduler / Async Job

# Sync

- welche Clients werden unterstützt?
    - siehe Releasenotes
- Verwaltung von ActiveSync-Geräten
    - reset Device
    - Remote Wipe + Security Policies
- CardDAV, CalDAV und WebDAV
- Owncloud / Nextcloud

# Absicherung

- https
- config.inc.php + Bewegdaten (Logs, Tmp, Files) ausserhalb vom Docroot
- Captcha
- Filepermissions

# Performance

- PHP 7.4 oder besser: 8
- Webserver
- Caching
- Redis (auch für Sessions)
- DB
- Skalierung
- Queue-Worker

# Backup/Restore

- via CLI

        # backup
        php setup.php --backup -- db=1 files=1 backupDir=/backup
        
        # restore
        php setup.php --restore -- db=1 files=1 backupDir=/vagrant/backup/2016-08-03-13-20-47
        
- DB
- Files

# LDAP-Integration

- Konfiguration
- Sync-Konfiguration
- Sync-Hooks
- Email-Integration
- sync accounts via CLI (und die Options)
- UCS

siehe tine20AdminLDAP.md

# Logging

- Aufbau einer Log-Zeile
- Loglevel
- Filterung
- Tools/Techniken zum Extrahieren der benötigten Infos
- siehe auch tine20AdminLogging.md

# Bugreports

- welche Infos werden wohin versendet?
- auto-bugreporting

# Benutzer und Gruppen / Rollen und Rechte

- Rechtekonzept
- welche Rechte gibt es?

# Ordner und Berechtigungen

- Konzept
- aus Admin-Sicht

# mögliche weitere Themen

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
