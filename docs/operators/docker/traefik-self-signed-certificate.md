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
