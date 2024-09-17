Configure tine as Single Sign On provider
=
tine can act as [SSO](https://en.wikipedia.org/wiki/Single_sign-on) identity provider for [oidc](https://openid.net/connect/) and [SAML2](https://en.wikipedia.org/wiki/SAML_2.0)

## 1) Install SSO application
* Go to `setup.php` and make sure SSO is installed
* In the UI go to `Admin` > `Applications` > `SSO` and make sure SSO is activated


## 2) Generate keys

!!! note "Convert certificate to json web key"

    To convert the certificate into the json web key format we use the `pem-jwk` tool here.

    `npm install -g pem-jwk`

    You can convert the key alternatively e.g. with an online converter like <https://irrte.ch/jwt-js-decode/pem2jwk.html>

~~~ sh
cd /path/to/docker-composer.yml
openssl req -x509 -newkey rsa:4096 -keyout ./conf.d/sso_key.pem -out ./conf.d/sso_cert.pem -days 730 -nodes -subj '/CN=tine-sso'
openssl pkey -in ./conf.d/sso_key.pem -out ./conf.d/sso_cert.crt -pubout
pem-jwk ./conf.d/sso_cert.crt > ./conf.d/sso_cert.jwk
sudo chown $(docker-compose exec  web sh -c "id tine20 -u"):$(docker-compose exec  web sh -c "id tine20 -g") ./conf.d/sso_*
sudo chmod 660 ./conf.d/sso_cert.* ./conf.d/sso_key.*
~~~

## 3) Create config

``` php title="./conf.d/sso.inc.php"
--8<-- "etc/tine20/conf.d/sso.inc.php.dist"
```

## 4) Clear config cache

``` sh title=""
--8<-- "scripts/docker-compose/clearCache"
```

## 5) Configure relaying parties
SSO Relaying parties can be configured per UI in the admin module or via cli using curl

### - UI
Go to `Admin` > `Applications` > `SSO` and open the SSO configuration dialog.

### - CLI
``` sh title="Login"
--8<-- "scripts/curl/login"
```

#### SAML2
The metadata URL of the tine idp (needed in config of the rp) is <https://YOURTINEURL/sso/saml2/idpmetadata> .

If not given tine fetches the `AssertionConsumerService*` and `singleLogoutService*` properties from the optional `metaUrl`.
``` sh title="Add SAML2 RP"
--8<-- "scripts/curl/admin_SSOAddRP_SAML2"
```
!!! note "Custom attributes mappings"
    The mapping `tine user attributes` => `SAML2 attributes` can be customized the rp's `attributeMapping` property (JSON formatted)
    ``` json title="Example to map user email to SAML2 uid"
    {
        "uid": "accountEmailAddress"
    }
    ```

!!! note "Custom attributes in SAML response"
    You can add custom attributes which are sent to the rp see `customHooks` property (JSON formatted) in the rp config.
    ``` php title="Example post auth hook"
    --8<-- "etc/tine20/samlPostAuthHook.php.dist"
    ```

#### oidc
The oicd provider url of the tine idp (needed in the config of the rp) is <https://YOURTINEURL> (no trailing slash). 
``` sh title="Add oidc RP"
--8<-- "scripts/curl/admin_SSOAddRP_oidc"
```

## Tine as RP, foreign IDP

### - CLI
``` sh title="Login"
--8<-- "scripts/curl/login"
```
``` sh title="Add foreign mock IDP"
--8<-- "scripts/curl/admin_SSOAddExIdp_oidc"
```