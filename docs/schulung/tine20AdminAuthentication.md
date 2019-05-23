Tine 2.0 Admin Schulung: Authentication
=================

Version: Nele 2018.11

Konfiguration und Problemlösungen im Zusammenhang mit der Authentication.

LDAP-Auth: siehe tine20AdminLDAP.md

TODO: add SSO-Auth (coming in 2019.11)

# Konfiguration Auth

Folgende Möglichkeiten gibt es für die primäre Authentifizierung:

* SQL
* LDAP/AD
* IMAP

TODO: add auth config options

# Konfiguration Second Factor

Tine 2.0 kann im Login-Dialog um einen zweiten Faktor für die Authentifizierung erweitert werden.

Dazu wird folgende Konfiguration (Beispiel config.inc.php) benötigt:

    'areaLocks' => [
        'records' => [
            // example config for PrivacyIdea
            [
                'area' => 'Tinebase.login', Tinebase_Model_AreaLockConfig::AREA_LOGIN,
                'provider' => 'token',  Tinebase_Auth::PIN
                'provider_config' => [
                    'adapter' => 'PrivacyIdea',  Tinebase_Auth_PrivacyIdea
                    'url' => 'https://10.10.10.11/pi/validate/check',
                            'allow_self_signed' => true, // for testing
                            'ignorePeerName'    => true, // for testing
                ],
                'validity' => 'once',  Tinebase_Model_AreaLockConfig::VALIDITY_ONCE
            ],
        ]
    ]

Damit das funktioniert, muss natürlich PrivacyIdea (siehe https://www.privacyidea.org/)
 entsprechend unter der angegebenen URL vorhanden und konfiguriert sein.

Folgende Auth-Provider werden z.z. unterstützt:

    const PROVIDER_PIN = 'pin'; // Benutzer bekommt PIN als zweites Passwort. Benötigt userPin config ('userPin' => true)
    const PROVIDER_TOKEN = 'token'; // z.z. nur PrivacyIdea

(see Tinebase_Model_AreaLockConfig)
