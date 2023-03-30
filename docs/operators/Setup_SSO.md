configure tine as Single Sign On provider
=

1) make sure the sso application is installed (setup) and activated (admin)

2) generate keys

!!! note "convert certificate to json web key"

    To convert the certificate into the json web key format mwe use the `pem-jwk` tool here.

    `npm install -g pem-jwk`

    Alternatively you can convert the key with an online converter like <https://irrte.ch/jwt-js-decode/pem2jwk.html>

~~~ shell
cd /path/to/docker-composer.yml
openssl req -x509 -newkey rsa:4096 -keyout ./conf.d/sso_key.pem -out ./conf.d/sso_cert.pem -days 730 -nodes -subj '/CN=tine-sso'
openssl pkey -in ./conf.d/sso_key.pem -out ./conf.d/sso_cert.crt -pubout
pem-jwk ./conf.d/sso_cert.crt > ./conf.d/sso_cert.jwk
sudo chown $(docker exec  81d0a03e3bde sh -c "id tine20 -u"):$(docker exec  81d0a03e3bde sh -c "id tine20 -g") ./conf.d/sso_*
~~~

2) create config
``` title="./conf.d/sso.inc.php"
--8<-- "etc/tine20/conf.d/sso.inc.php.dist"
```

3) clear config cache
``` shell
docker compose exec --user tine20 web sh -c "cd /usr/share/tine20/ && php setup.php --config=$TINE20_CONFIG_PATH --clear_cache -v"
```

4) go to `Admin` > `Applications` > `SSO` to configure a relying party via UI
