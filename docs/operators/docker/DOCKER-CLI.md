tine Docker image CLI
---

Some common tine-docker CLI commands

## UPDATE

~~~
    docker exec --user tine20 -it <tine-web-container> sh -c "php /usr/share/tine20/setup.php --config /etc/tine20 --update -v"
~~~

## BACKUP / RESTORE

1) Recommended: create a volume for the tine backups - for persisting on the host or moving to another host.
If you don't have a volume, you might want to user "docker cp" to copy the backup files to the host.

2) Run tine --backup via docker exec
~~~
    docker exec --user tine20 -it <tine-web-container> sh -c "php /usr/share/tine20/setup.php --config /etc/tine20 --backup -- db=1 files=1 backupDir=/var/lib/tine20/backup/ noTimestamp=1"
~~~

3) Restore backup files 
~~~
    docker exec --user tine20 -it <tine-web-container> sh -c "php /usr/share/tine20/setup.php --config /etc/tine20 --restore -- db=1 files=1 backupDir=/var/lib/tine20/backup/"
~~~

## CLI PASSWORDFILE

~~~
    docker exec --user tine20 -it <tine-web-container> sh -c "php /usr/share/tine20/tine20.php --config /etc/tine20 --passwordfile=/etc/tine20/pw"
~~~
