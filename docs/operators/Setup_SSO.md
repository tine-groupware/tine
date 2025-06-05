# Configuring tine for Single Sign On (SSO)
## tine as Single Sign On Identity Provider (SSO IdP)

tine can act as [SSO](https://en.wikipedia.org/wiki/Single_sign-on) identity provider for [oidc](https://openid.net/connect/) and [SAML2](https://en.wikipedia.org/wiki/SAML_2.0)

### 1) Install SSO application
* Go to `setup.php` and make sure SSO is installed
* In the UI go to `Admin` > `Applications` > `SSO` and make sure SSO is activated


### 2) Generate keys

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

!!! note sso_cert.crt needs to contain both CERTIFICATE and PUBLIC KEY strings!

To check if you have a valid config, you can call this URL: https://my.tine.url/sso/saml2/idpmetadata

### 3) Create config

``` php title="./conf.d/sso.inc.php"
--8<-- "etc/tine20/conf.d/sso.inc.php.dist"
```

### 4) Clear config cache

``` sh title=""
--8<-- "scripts/docker-compose/clearCache"
```

### 5) Configure relaying parties
SSO Relaying parties can be configured per UI in the admin module or via cli using curl

#### - UI
Go to `Admin` > `Applications` > `SSO` and open the tab `RELYING PARTIES` in the SSO configuration dialog.

#### - CLI
``` sh title="Login"
--8<-- "scripts/curl/login"
```

##### - SAML2
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

##### - OIDC
The OIDC provider url of the tine idp (needed in the config of the rp) is <https://YOURTINEURL> (no trailing slash). 
``` sh title="Add OIDC RP"
--8<-- "scripts/curl/admin_SSOAddRP_oidc"
```

## tine as Single Sign On Relaying Party (SSO RP)

tine can act as [SSO](https://en.wikipedia.org/wiki/Single_sign-on) Relaying Party for [OIDC](https://openid.net/connect/)

### 1) Install SSO application
* Go to `setup.php` and make sure SSO is installed
* In the UI go to `Admin` > `Applications` > `SSO` and make sure SSO is activated

### 2) Configure identity providers (IdP)
SSO identity providers can be configured per UI in the admin module or via cli using curl

#### - UI
Go to `Admin` > `Applications` > `SSO` and open the tab `EXTERNAL IDENTITY PROVIDERS` in the SSO configuration dialog.

#### - CLI
``` sh title="Login"
--8<-- "scripts/curl/login"
```
> **NOTE:**
> For following setup to work correctly, a `oidc-server-mock` to `127.0.0.1` entry is required in `/etc/hosts`


``` sh title="Add foreign mock IDP"
--8<-- "scripts/curl/admin_SSOAddExIdp_oidc"
```