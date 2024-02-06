Server Requirements
===================

General
-------

Generally tine supports all officially supported PHP versions. For Database server [MySQL] or [MariaDB] is recommended.
Support for others has been discontinued.

### Overview

-   Docker-Runtime (see [Docker Engine])
-   Webserver (e. g. [Apache Webserver] or [NGINX Webserver])
    - NOTE: nginx is recommended - apache configuration is no longer updated
    - TODO add link to nginx configs
-   Database server
    -   [MySQL] 5.7.5 or later
    -   MariaDB 10.0.2
-   [PHP] 8.0 - 8.1 suggested

### Docker-Setup is recommended

see [DOCKER-QUICKSTART]

[DOCKER-QUICKSTART]: ../docker/DOCKER-QUICKSTART/

### Support for some Databases has been dropped

* Support for PGSQL has been dropped
* Support for Oracle has been dropped
* **MySQL** Version needs to be **greater or equal to 5.7.5**
* **MariaDB** Version needs to be **greater or equal to 10.0.2**

### PHP 8.0 required, older PHP versions not supported anymore

Beginning with release 2023.11 **PHP** needs to be **greater or equal to 8.0**.
Support for PHP versions up to 7.4 is not continued.

### PHP Extensions

**Required**

-   json
-   gd
-   date
-   SPL
-   SimpleXML
-   ctype
-   dom
-   openssl
-   iconv
-   zip
-   xml
-   hash
-   mbstring
-   bcmath
-   intl

**Optional**

-   Redis
-   LDAP
-   Memcache

Client requirements
-------------------

### Supported browsers

Current versions of the following browsers on the date of the tine release

-   Mozilla Firefox
-   Browser with Chrome engine (Microsoft Edge, Opera, Chromium, Brave, ...)
-   Apple Safari

For security reasons, we recommend always to use the latest version!

### ActiveSync Clients
- iOS Version 7 to 16
- Android Version 6 to 13

### WebDAV Clients
- OwnCloud Client Version 2.0 to 2.11
- Windows Explorer Windows 7 to 11
- macOS X Finder macOS X 10.7 to 12.x
- KDE Plasma Version 4.11 to 5.26
- Gnome/Nautilus Version 3.0 to 43
- CardBook (Thunderbird-Addon)

### CalDAV Clients

- Mozilla Thunderbird Version 38.0 to 102.4
- eM Client Version 7.0 to 8.0
- iCal macOS X 10.7 to 12.x
- Reminders/Erinnerungen macOS X 10.7 to 12.x
- iOS Version 7 to 16
- DAVx5 (former DavDroid) Version 1.5 to 4.2

Latest Release Notes
----------------------

Latest Release Notes:

``` title="RELEASENOTES"
--8<-- "tine20/RELEASENOTES"
```

[Apache Webserver]: https://httpd.apache.org/
[NGINX Webserver]: https://www.nginx.com/
[MySQL]: https://www.mysql.com/
[MariaDB]: https://mariadb.com/
[PHP]: https://www.php.net/
[Docker Engine]: https://docs.docker.com/engine/install/
