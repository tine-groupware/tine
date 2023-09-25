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
