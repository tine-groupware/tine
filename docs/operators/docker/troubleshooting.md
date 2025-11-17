tine Docker troubleshooting
---

A collection of troubleshooting tips

## container is "unhealthy" in "docker ps"

-> access log shows 499 status codes (connection reset by peer)
-> docker container/service (or at least nginx) might need a restart

~~~
systemctl restart docker
~~~

## Traefik stops working, it uses old API version 1.24

Error message:

~~~
traefik | 2025-11-12T12:55:48Z ERR Failed to retrieve information of the docker client and server host error=
  "Error response from daemon: client version 1.24 is too old. Minimum supported API version is 1.44,
   please upgrade your client to a newer version" providerName=docker

docker --version
Docker version 29.0.0, build 3d4129b
~~~

see https://community.traefik.io/t/traefik-stops-working-it-uses-old-api-version-1-24/29019/10
