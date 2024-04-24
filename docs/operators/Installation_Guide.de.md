Installation Guide
===================

[www.tine-groupware.de](https://www.tine-groupware.de/) | [docker-compose.yml](https://tine-docu.s3web.rz1.metaways.net/de/operators/docker/docker-compose.yml) | [Dockerfile](https://github.com/tine-groupware/tine/blob/main/ci/dockerimage/built.Dockerfile)

## Schnellstart

Dies ist eine schnelle und leichte Möglichkeit, tine auszuprobieren. Hierfür benötigen Sie Docker und Docker Compose (https://docs.docker.com/compose/).

Erstellen Sie im ersten Schritt einen Ordner. Docker Compose verwendet die Ordnernamen zur Identifizierung.

```
mkdir tine
cd tine
```
Als zweiten Schritt laden Sie die aktuelle Datei docker-compose.yml herunter und speichern diese in dem soeben erstellten Ordner.

```
wget https://tine-docu.s3web.rz1.metaways.net/de/operators/docker/docker-compose.yml
```

Jetzt können Sie Docker-Compose starten.

---
**Anmerkung**

Je nach Docker-Installation erfolgt der Docker Compose Aufruf so: `docker compose` oder so `docker-compose`.

---

```
docker compose up
```

Warten Sie einen Moment, bis die Datenbank erreichbar ist. Im Webcontainer Log steht dann `web_1    | DB available`. Dann können Sie Tine installieren. Öffnen Sie dafür ein neues Terminal und führen Sie den Installer aus. Im Installer müssen Sie die Tine-Lizenz und Datenschutzerklärung bestätigen und können das Password für den initialen Admin festlegen.

```
docker compose exec web tine20_install
```

tine ist jetzt unter http://127.0.0.1:4000 erreichbar.

### setup.php UI

Die tine-Setup-UI ist dann unter http://127.0.0.1:4000/setup.php erreichbar. Bitte dran denken, dass diese im Container
 mit HTTP Basic Auth geschützt ist. Benutzername und Passworthash sollten über die ENV Variable TINE20_SETUP_HTPASSWD gesetzt werden (Beispiel: "setup:$apr1$JhCtViTh$k15DH.HvNR5hZ66Ew5aTH/" #setup:setuppw).
Der hash kann mit htpasswd generiert werden:
1. `htpasswd -c setup.htpasswd setup`.
2. Pasword eingeben wiederholen
3. Benutzername und Passworthash aus der Datei `setup.htpasswd` kopieren.

Hinweis: Bei der Verwendung von docker-compose muss `$` wie folgt escaped werden: `$$`.

### Aufräumen
Um alle von Docker Compose erstellten Container, Netzwerke und Volumes zu stoppen und löschen nutzen Sie:
```
docker compose down --volumes
```

## Image
Dieses Image enthält den tine-Code, PHP-FPM und Nginx. Zusätzlich benötigen Sie eine Datenbank, beispielsweise MariaDB. In der Produktion sollte dieses Image mit einem Reverse-Proxy verwendet werden, der die gesamte benutzerdefinierte Konfiguration und SSL-Terminierung übernimmt.

### Paths
| Path | Definition |
|---|---|
| `/etc/tine20/config.inc.php` | tine Hauptkonfigurationsdatei.
| `/etc/tine20/conf.d/*` | tine Konfigurationsdateien werden automatisch eingeschlossen.
| `/var/lib/tine20/files` | Speichern der User-Daten. Dateien wie die im tine-Dateimanager
| `/var/lib/tine20/tmp` | Temporäre Dateispeicherung
| `/var/lib/tine20/caching` | Wird zum Zwischenspeichern verwendet, wenn `TINE20_CACHING_BACKEND == 'File'`
| `/var/lib/tine20/sessions`  | Wird als Sitzungsspeicher verwendet, wenn `TINE20_SESSION_BACKEND == 'File'`

## Update

Zum Updaten einmal 'docker compose down && docker composer up' machen. Falls man eine andere Major-Version haben möchte, kann
vorher in der docker-compose.yml auch eine konkrete Version angegeben werden.

Zum Updaten von tine selbst verwendet man folgenden Befehl (ggf. muss der Name des Containers angepasst werden, herausfinden
kann man ihn z.B. mit 'docker ps'):

```
docker exec --user tine20 tine-docker_web_1 sh -c "php /usr/share/tine20/setup.php --config=/etc/tine20 --update"
```

Falls dieser Fehler erscheint:

    "Tinebase_Exception -> waited for Action Queue to become empty for more than 300 sec"

Sollte geprüft werden, ob noch Jobs in der Queue sind und/oder man startet das Update mit dem Schalter `skipQueueCheck=1`.

## SSL / Reverse Proxy

Um den tine-Container "von aussen" verfügbar zu machen, kann man einen NGINX, Traefik oder HAProxy davorschalten.

### NGINX

Beispiel einer NGINX VHOST conf:

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

Alternativ zu NGINX kann man auch traefik zur docker-composer.yml hinzufügen:

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

Um von einer alten Installation mit lokaler tine auf das Docker-Setup zu migrieren, müssen nur die Volumes entsprechend gemountet werden (das root-Passwort der Datenbank sollte bekannt sein und die DB sollte die gleiche Version haben):

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

Bitte achtet auf die korrekte Angabe des TINE20_DATABASE_TABLEPREFIX -> sonst werden ggf. die Tabellen nicht gefunden.

Falls das nicht klappt oder die alte tine DB auf einem anderen Server liegt, sollte man die tine CLI Funktionen `--backup` und `--restore` benutzen.
Dabei muss beachtet werden, dass auf die Backup-Dateien von innerhalb des tine-Containers aus zugriffen werden kann.


## Custom-Konfiguration

Wenn man möchte, kann man Custom-Configs (via conf.d) ebenfalls als eigenes Volume in den Container mounten:

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

## Ansible Rolle für Deployments

https://github.com/tine-groupware/tine/tree/main/scripts/ansible/roles/tinedockercompose
