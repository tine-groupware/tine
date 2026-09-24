tine Docker image CLI
---

Version: Liva 2025.11

Some common tine-docker CLI commands follow.

## UPDATE

~~~
    docker exec --user tine20 <tine-web-container> sh -c "php /usr/share/tine20/setup.php --update -v"
~~~

## INSTALL AN APP

~~~
    docker exec --user tine20 <tine-web-container> sh -c "php /usr/share/tine20/setup.php --install HumanResources"
~~~

## LIST INSTALLED APPS

Also shows versions and maintenance mode status.

~~~
    docker exec --user tine20 <tine-web-container> sh -c "php /usr/share/tine20/setup.php --list"
~~~

## MAINTENANCE MODE

~~~
    docker exec --user tine20 <tine-web-container> sh -c "php /usr/share/tine20/setup.php --maintenance_mode -- mode=[on|off]"
~~~

## BACKUP / RESTORE

1) Recommended: create a volume for the tine backups - for persisting on the host or moving to another host.
If you don't have a volume, you might want to user "docker cp" to copy the backup files to the host.

2) Run tine --backup via docker exec
~~~
    docker exec --user tine20 <tine-web-container> sh -c "php /usr/share/tine20/setup.php --backup -- db=1 files=1 backupDir=/var/lib/tine20/backup/ noTimestamp=1"
~~~

The database config table is always dumped separately to `tine20_mysql_config.sql.bz2`.
Use `configExcludeKeys` (a comma separated list of config keys) to omit sensitive keys
such as `imap` and `smtp`, which contain passwords, from that dump:
~~~
    docker exec --user tine20 <tine-web-container> sh -c "php /usr/share/tine20/setup.php --backup -- db=1 files=1 backupDir=/var/lib/tine20/backup/ noTimestamp=1 configExcludeKeys=imap,smtp"
~~~
The matching `tine20_mysql_config.sql.bz2` is restored automatically by `--restore` when present.

3) Restore backup files 
~~~
    docker exec --user tine20 <tine-web-container> sh -c "php /usr/share/tine20/setup.php --restore -- db=1 files=1 backupDir=/var/lib/tine20/backup/"
~~~

## CLI PASSWORDFILE

~~~
    docker exec --user tine20 <tine-web-container> sh -c "php /usr/share/tine20/tine20.php --passwordfile=/etc/tine20/pw"
~~~
