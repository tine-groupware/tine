# Traefik Reverse proxy

Configuration of traefik with tine & docker.

## Add traefik config

docker-compose.yml:
~~~yml
  web:
    #[...]
    ### for traefik support (see https://doc.traefik.io/traefik/providers/docker/)
    labels:
    - "traefik.enable=true"
    - "traefik.http.routers.web.rule=Host(`tine.domain.de`)"
    - "traefik.http.routers.web.entrypoints=websecure"
    - "traefik.http.routers.web.tls=true"
    - "traefik.http.services.web.loadbalancer.server.port=80"

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
      - "--providers.file.directory=/etc/traefik/dynamic_conf"
      - "--providers.file.watch=true"
      # - "--log.level=DEBUG"
    ports:
      - "80:80"
      - "443:443"
    networks:
      - internal_network
      - external_network
    volumes:
      - "/var/run/docker.sock:/var/run/docker.sock:ro"
      # needed for custom certificates
      - "/srv/tine/traefik/certs/:/certs/:ro"
      - "/srv/tine/traefik/traefik.yml:/etc/traefik/dynamic_conf/conf.yml:ro"
~~~

~~~
root@tinedocker:/srv/tine# cat traefik/
certs/       traefik.yml
~~~

traefik/traefik.yml:
~~~yml
tls:
  stores:
    default:
      defaultCertificate:
        certFile: /certs/Server.ID-320917-x509chain.pem
        keyFile: /certs/key.pem
~~~

## Redirect config/middleware (i.e. from /tine20)

docker-compose.yml:
~~~yml
  web:
    #[...]
    labels:
    - "traefik.enable=true"
    - "traefik.http.routers.web.rule=Host(`tine.domain.de`)"
    # pathreplace config -> needed to remove the /tine20 that users still have in the bookmarks / redirects
    # see https://doc.traefik.io/traefik/middlewares/http/replacepathregex/
    - "traefik.http.routers.web.middlewares=tine20pathreplace"
    - "traefik.http.middlewares.tine20pathreplace.replacepathregex.regex=^/tine20(.*)"
    - "traefik.http.middlewares.tine20pathreplace.replacepathregex.replacement=$$1"
    #[...]
~~~

## Using Let's Encrypt with traefik + tine

docker-compose.yml (only the relavant lines):
~~~yml
  web:
    labels:
      - "traefik.enable=true"
      - "traefik.http.routers.web.rule=Host(`tine.domain.de`)"
      - "traefik.http.routers.web.entrypoints=websecure"
      - "traefik.http.routers.web.tls=true"
      - "traefik.http.routers.web.tls.certresolver=http01"
      - "traefik.http.services.web.loadbalancer.server.port=80"

traefik:
    image: "traefik:v3.6"
    command:
      - "--providers.docker=true"
      - "--providers.docker.exposedbydefault=false"
      - "--entrypoints.web.address=:80"
      - "--entrypoints.web.http.redirections.entryPoint.to=web"
      - "--entrypoints.web.http.redirections.entryPoint.scheme=https"
      - "--entrypoints.web.http.redirections.entrypoint.permanent=true"
      - "--entrypoints.websecure.address=:443"
      - "--entrypoints.websecure.http.tls=true"
      - "--certificatesresolvers.http01.acme.httpchallenge=true"
      - "--certificatesresolvers.http01.acme.httpchallenge.entrypoint=websecure"
      - "--certificatesresolvers.http01.acme.storage=/letsencrypt/acme.json"
    volumes:
      - "./letsencrypt:/letsencrypt"
~~~
