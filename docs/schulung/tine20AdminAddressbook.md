Tine 2.0 Admin Schulung: Adressbuch
=================

Version: Caroline 2017.11

Konfiguration und Problemlösungen im Adressbuch-Modul von Tine 2.0

Feature: Anbindung eines LDAP-Adressbuchs
=================

Um ein LDAP-Adressbuch mit Einträgen der objectClass "mozillaAbPersonAlpha" (siehe
 https://wiki.mozilla.org/MailNews:Mozilla_LDAP_Address_Book_Schema) anzubinden, muss folgendes in die
 config.inc.php geschrieben werden:
 
    'Addressbook' => array(
        'syncBackends' => array(
            '0' => array(
                'class'     => 'Addressbook_Backend_Sync_Ldap',
                'options'   => array(
                    'baseDN' => 'ou=my,o=base,c=dn',
                    'ldapConnection' => array(
                        'host' => 'my.ldap.host',
                        'port' => 389,
                        'username' => 'uid=admin,ou=People,o=base,c=dn',
                        'password' => '****',
                        'bindRequiresDn' => true,
                        'baseDn' => 'o=base,c=dn'
                    ),
                ),
            ),
	    ),
    ),

Änderungen rückgängig machen (UNDO-Funktion)
=================

Wenn man weiss, von wem und wann Änderungen gemacht wurden, können diese einfach wiederhergestellt
 werden (-d steht für Dry Run):
 
    $ tine20-cli --method=Tinebase.undo -d -- \
      record_type=Addressbook_Model_Contact \
      modification_time=2013-05-08 \
      modification_account=ACCOUNTID
