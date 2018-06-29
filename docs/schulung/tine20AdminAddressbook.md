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
    
Weitere Infos:

* gibt es eine Möglichkeit, Customfields in das Mapping aufzunehmen, so dass sie in konfigurierte LDAP-Properties geschrieben werden?

-> nein, Customfields gehen nicht. Wäre aber ne Kleinigkeit das hinzuzufügen, etwa 2 Std. Zusatzaufwand 

* Wie konfiguriert man den Adressbuch-Container, so dass der Inhalt ins LDAP gesynct wird? Geht das über die Container XPROPS?
-> das muß alles zu Fuß konfiguriert werden: in der sync config:
 
 
    'filter' => [['field'=>'container_id', 'operator' => in, 'value' => ['ID_1', 'ID_2', etc.]]

* Sind mehrere Ordner/Container möglich?
-> ja, es wird mit einem "echtem" Addressbook_Model_ContactFilter gesteuert (s.o.)

* Kann ein initialer Sync der Daten durchgeführt werden?
-> ja: Addressbook_Frontend_Cli::syncbackends -> überträgt alle Kontakte die zum Filter passen und noch nicht übertragen wurden.

btw. mit dem Filter sollte man ein bischen aufpassen, am besten man ändert ihn nie! Wenn dann darf der Filter nur "größer" werden, aber er darf nie bereits übertragene Kontake ausschließen.

Änderungen rückgängig machen (UNDO-Funktion)
=================

Wenn man weiss, von wem und wann Änderungen gemacht wurden, können diese einfach wiederhergestellt
 werden (-d steht für Dry Run):
 
    $ tine20-cli --method=Tinebase.undo -d -- \
      record_type=Addressbook_Model_Contact \
      modification_time=2013-05-08 \
      modification_account=ACCOUNTID
