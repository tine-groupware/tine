Installation Guide
===================

Docker Installation Guide
---
[www.tine-groupware.de](https://www.tine-groupware.de/) | [docker-compose.yml](https://tine-docu.s3web.rz1.metaways.net/operators/docker/docker-compose.yml) | [Dockerfile](https://github.com/tine-groupware/tine/blob/main/ci/dockerimage/built.Dockerfile)

## Quickstart

This is an easy way to try out tine-groupware. You need Docker and Docker Compose (https://docs.docker.com/compose/).

First, create a folder. Docker Compose uses the folder names as an identifier.

```
mkdir tine
cd tine
```
Then you need to download the current docker-compose.yml (see below). And save it in the folder just created.
```
wget https://tine-docu.s3web.rz1.metaways.net/operators/docker/docker-compose.yml
```
Now you can start the docker-compose.

---
**Note**

Depending on your docker compose installation, you need to use this command: `docker compose` or this `docker-compose`.

---


```
docker compose up
```

Wait for the database to become available. If it is, the web container will log `web_1    | DB available`. Now open another terminal and start the tine installer. There you need to accept the tine-license and Privacy policy and you will be able to set the initial admin password.

```
docker compose exec web tine20_install
```

Your tine-groupware is now reachable at http://127.0.0.1:4000.

### setup.php UI

The setup.php browser interface can be accessed here: http://127.0.0.1:4000/setup.php. The site is protected by HTTP basic auth. 
Username and password hash can be configured with the ENV Variable TINE20_SETUP_HTPASSWD (example: "setup:$apr1$JhCtViTh$k15DH.HvNR5hZ66Ew5aTH/" #setup:setuppw).
The hash can be generated with htpasswd:
1. `htpasswd -c setup.htpasswd setup`.
2. Enter the password.
3. Copy username and password form the file  `setup.htpasswd`.

Note: When using docker-compose, `$` needs to be escaped as follows: `$$`.

### Cleanup
Use the following to stop and delete all containers, networks and volumes created by this compose.
```
docker compose down --volumes
``` 

### Image
This image contains the tine code, PHP-FPM, and Nginx. Additionally, a database e.g MariaDB is required. In production, this image should be utilized with a reverse proxy handling all the custom configuration and ssl termination.

### Paths
| Path | Description |
|---|---|
| `/etc/tine20/config.inc.php` | tine main config file.
| `/etc/tine20/conf.d/*` | tine auto include config files.
| `/var/lib/tine20/files` | Stores user data. Files like in tine Filemanager
| `/var/lib/tine20/tmp` | Temporary file storage
|`/var/lib/tine20/caching` | Used for caching if `TINE20_CACHING_BACKEND == 'File'`
|`/var/lib/tine20/sessions`  | Used as session store if `TINE20_SESSION_BACKEND == 'File'`

## Update

Use 'docker compose up' to fetch the latest docker image.

Use this command to update tine:

```
docker exec --user tine20 tine-docker_web_1 sh -c "php /usr/share/tine20/setup.php --config=/etc/tine20 --update"
```

If you see this error during the update:

    "Tinebase_Exception -> waited for Action Queue to become empty for more than 300 sec"

You should check why there are still jobs in the ActionQueue and/or run the update with `skipQueueCheck=1`. 

## SSL / Reverse Proxy

### NGINX

Example NGINX VHOST conf:

```apacheconf
server {
    listen 80;
    listen 443 ssl;
    
    ssl_certificate /etc/letsencrypt/live/MYDOMAIN.de/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/MYDOMAIN.de/privkey.pem;
    
    server_name tine.MYDOMAIN.de autodiscover.MYDOMAIN.de;
    
    if ($ssl_protocol = "" ) {
        rewrite        ^ https://$server_name$request_uri? permanent;
    }
    
    access_log /var/www/MYDOMAIN/logs/nginx-access.log;
    error_log /var/www/MYDOMAIN/logs/nginx-error.log;
    
    client_max_body_size 2G; # set maximum upload size
    
    location /.well-known { }
    
    location / {
        proxy_pass http://127.0.0.1:4000;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header Host $host;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }
}
```

### TRAEFIK

```yaml
  traefik:
    image: "traefik:v2.6"
    restart: always
    container_name: "traefik"
    command:
      - "--providers.docker=true"
      - "--providers.docker.exposedbydefault=false"
      - "--entrypoints.web.address=:80"
      - "--entrypoints.web.http.redirections.entryPoint.to=websecure"
      - "--entrypoints.web.http.redirections.entryPoint.scheme=https"
      - "--entrypoints.web.http.redirections.entrypoint.permanent=true"
      - "--entrypoints.websecure.address=:443"
      - "--certificatesresolvers.http01.acme.httpchallenge=true"
      - "--certificatesresolvers.http01.acme.httpchallenge.entrypoint=web"
      - "--certificatesresolvers.http01.acme.storage=/letsencrypt/acme.json"
    ports:
      - "80:80"
      - "443:443"
      - "8080:8080"
    volumes:
      - "./letsencrypt:/letsencrypt"
      - "/var/run/docker.sock:/var/run/docker.sock:ro"

  web:
    image: tinegroupware/tine:2021.11
    #[...]
    labels:
      - "traefik.enable=true"
      - "traefik.http.routers.tile-server.rule=Host(`MYDOMAIN.de`)"
      - "traefik.http.routers.tile-server.entrypoints=websecure"
      - "traefik.http.routers.tile-server.tls.certresolver=http01"
      - "traefik.http.services.tile-server.loadbalancer.server.port=80"
```

## Migration

To migrate from an old tine installation, you can try to just mount the database as a volume
(you have to know the root password of the existing database and you should ideally use the same db version):

```yaml
  db:
    image: mariadb:10.6
    volumes:
      - "/var/lib/mysql:/var/lib/mysql"
    #[...]
    
  web:
    image: tinegroupware/tine:2021.11
    volumes:
      - "/var/lib/tine20/files:/var/lib/tine20/files"
    #[...]
```

If this does not work or your existing tine database is on another server, it is recommended to use the tine CLI functions `--backup` and `--restore` for migration.
Please note that the tine container needs to access the backup/dump files, so it might be necessary to copy the files into the container.

## Custom Configuration

```yaml
  web:
    image: tinegroupware/tine:2021.11
    volumes:
      - "conf.d:/etc/tine20/conf.d"
    #[...]
```

## docker-compose.yml

``` yaml title="docker-compose.yml"
--8<-- "docs/operators/docker/docker-compose.yml"
```

## Ansible Role For Deployments

https://github.com/tine-groupware/tine/tree/main/scripts/ansible/roles/tinedockercompose
