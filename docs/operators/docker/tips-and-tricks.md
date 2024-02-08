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
(use differernt user/pw if you changed them):

~~~shell
docker compose exec db sh -c "mysql -u root -proot tine"
~~~
