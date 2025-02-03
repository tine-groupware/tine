tine Docker troubleshotting
---

A collection of troubleshooting tips

## container is "unhealthy" in "docker ps"

-> access log shows 499 status codes (connection reset by peer)
-> docker container/service (or at least nginx) might need a restart

~~~
systemctl restart docker