# Configuring {{ branding.title }} for Single Sign On (SSO)
## {{ branding.title }} as Single Sign On Identity Provider (SSO IdP)

{{ branding.title }} can act as [SSO](https://en.wikipedia.org/wiki/Single_sign-on) identity provider for [oidc](https://openid.net/connect/) and [SAML2](https://en.wikipedia.org/wiki/SAML_2.0)

### 1) Install SSO application
* Go to `setup.php` and make sure SSO is installed
* In the UI go to `Admin` > `Applications` > `SSO` and make sure SSO is activated
* Installing SSO will automatically generate the necessary certificates
* SSO will automatically rotate the certificates every 6 months


### 2) Generate keys manually

call the {{ branding.title }} cli:
tine20.php --method=SSO.generateKey

To check if you have valid certificates, you can call these URLs:
* https://my.tine.url/sso/saml2/idpmetadata
* https://my.tine.url/sso/oauth2/certs

### 3) Create config

``` php title="./conf.d/sso.inc.php"
--8<-- "etc/tine20/conf.d/sso.inc.php.dist"
```

### 4) Clear config cache

``` sh title="clearCache"
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
The metadata URL of the {{ branding.title }} idp (needed in config of the rp) is <https://YOURTINEURL/sso/saml2/idpmetadata> .

If not given {{ branding.title }} fetches the `AssertionConsumerService*` and `singleLogoutService*` properties from the optional `metaUrl`.
``` sh title="Add SAML2 RP"
--8<-- "scripts/curl/admin_SSOAddRP_SAML2"
```
!!! note "Custom attributes mappings"
    The mapping `{{ branding.title }} user attributes` => `SAML2 attributes` can be customized the rp's `attributeMapping` property (JSON formatted)
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

EXAMPLE: add tine as iDP in sentry (tested with version 26.6.0 + tine 2026.11)

(see https://docs.sentry.io/organization/authentication/sso/#saml2-identity-providers)

- add relying party in tine via admin
  - name: same as METADATA_URL (example: https://sentry.bla.blubb/saml/metadata/sentry/)
  - type: SAML2 Relying Party Config 
     - name: same as METADATA_URL
     - entity id: same as METADATA_URL
     - metadata_url: METADATA_URL (example: https://sentry.bla.blubb/saml/metadata/sentry/)
     - Assertion Consumer Service Binding: :urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST
     - Assertion Consumer Service Location: example: https://sentry.bla.blubb/saml/acs/sentry/
     - Logout Service Binding: urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST
     - attribute mapping:
~~~json
{
    "uid": "accountId",
    "email": "accountEmailAddress",
    "first_name": "accountFirstName",
    "last_name": "accountLastName"
}
~~~

- connect to tine via sentry
  - add tine metadata url -> https://my.tine.url/sso/saml2/idpmetadata
  - add mapping (uid, email, first_name, last_name) -> see above
  - done...


##### - OIDC
The OIDC provider url of the {{ branding.title }} idp (needed in the config of the rp) is <https://YOURTINEURL> (no trailing slash). 
``` sh title="Add OIDC RP"
--8<-- "scripts/curl/admin_SSOAddRP_oidc"
```

## {{ branding.title }} as Single Sign On Relaying Party (SSO RP)

{{ branding.title }} can act as [SSO](https://en.wikipedia.org/wiki/Single_sign-on) Relaying Party for [OIDC](https://openid.net/connect/)

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