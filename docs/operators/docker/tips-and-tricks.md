tine Docker tips and tricks
---

A collection of (maybe) useful tips and tricks ...

## Use a HTTP proxy with docker

If you have the situation of having to use a HTTP proxy to access the internet / docker registries, this might be helpful:

File: /etc/systemd/system/multi-user.target.wants/docker.service

~~~ ini
[Service]
ExecStart=/usr/bin/dockerd --http-proxy="http://myproxy" --https-proxy="http://myproxy" -H fd:// --containerd=/run/containerd/containerd.sock
~~~

## Connect to the database via MySQL/MariaDB client

Go to your docker host and into the directory with the docker-compose.yml and run the following command
(use different user/pw if you changed them):

~~~shell
$ docker compose exec db sh -c "mysql -u root -proot tine"
~~~

## Debug MariaDB-Errors / activate error.log

Log into the Maria/Mysql Container:

~~~shell
$ docker compose exec db bash
~~~

Activate error.log by adding this line in the file /etc/mysql/mariadb.conf.d/50-server.cnf (for MariaDB):

~~~ini
log_error = /var/log/mysql/error.log
~~~

You might need to edit, copy and paste the file via `cat`.

Afterwards, you can restart MariaDB and check the file with `tail -f` to find any problems.

~~~shell
$ /etc/init.d/mariadb restart
$ tail -f /var/log/mysql/error.log
~~~

On this stackoverflow question, you can find additional ways to activate error/slow.logs:

(https://stackoverflow.com/questions/39708213/enable-logging-in-docker-mysql-container)
