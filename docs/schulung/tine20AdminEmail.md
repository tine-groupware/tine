Tine 2.0 Admin Schulung: E-Mail / Felamimail
=================

Version: Caroline 2017.11

Konfiguration und Problemlösungen des E-Mail-Moduls von Tine 2.0

Frage: Wie kann ich den E-Mail-Cache leeren?
=================

Am einfachsten durch den Aufruf einer CLI-Methode:

    $ tine20-cli --method=Felamimail.truncatecache
    
Damit wird der Cache aller Benutzer gelöscht. Die Benutzer-Abfrage dient zur Prüfung des ADMIN-Rechts.

Problem: IMAP-Server hat selbstsigniertes Zertifikat
=================

Wenn die Verbindung zum IMAP/SMTP-Server fehlschlägt, kann es an einem
 selbstsignierten Zertifikat liegen. Dann muss man die "verifyPeer"-Option
 auf "false" (0) setzen. Das kann man am einfachsten direkt in der
 setup.php in den E-Mail-Einstellungen tun (seit 2017.11.8 - siehe https://forge.tine20.org/view.php?id=13832).
 
Oder man setzt es direkt in der Datenbank
 
    mysql> select * from tine20_config where name = 'imap'\G
    *************************** 1. row ***************************
                id: a0ccbbce865f37c2c3113506d606d2a1293176cc
    application_id: 64d1f06623b4539810b50a249858c212ca13d533
              name: imap
             value: {"active":true,"backend":"dovecot_imap","host":"localhost","port":993,"ssl":"ssl","useSystemAccount":"1","domain":"","useEmailAsUsername":false,"dbmail":{"port":3306},"cyrus":{"useProxyAuth":0},"dovecot":{"host":"localhost","dbname":"dovecot","username":"dovecot","password":"dovecot","port":"3306","uid":"998","gid":"998","home":"\/var\/spool\/mail\/%d\/%n","scheme":"SSHA256"},"dovecotcombined":{"adapter":"pdo_mysql","port":3306},"instanceName":"tine20.mytine20.de"}
    1 row in set (0.00 sec)

Jetzt die verifyPeer-Option in den JSON-String hinzufügen und ein UPDATE ausführen:

    mysql> update tine20_config set value = '{"active":true,"backend":"dovecot_imap","host":"localhost","port":993,"ssl":"ssl","useSystemAccount":"1","domain":"","useEmailAsUsername":false,"dbmail":{"port":3306},"cyrus":{"useProxyAuth":0},"dovecot":{"host":"localhost","dbname":"dovecot","username":"dovecot","password":"dovecot","port":"3306","uid":"998","gid":"998","home":"\/var\/spool\/mail\/%d\/%n","scheme":"SSHA256"},"dovecotcombined":{"adapter":"pdo_mysql","port":3306},"verifyPeer":"0","instanceName":"tine20.mytine20.de"}' where name = 'imap';

Der gleiche Schritt muss dann ggf. nochmal für SMTP gemacht werden.

Alternativ können die Configs auch via CLI setup.php --setconfig (vorher mit --getconfig auslesen) gesetzt werden.

Falls das nicht hilft bzw. nicht erwünscht ist, könnte auch das hier das Problem beheben:

> > Hier steht, wie man ein CA Zertifikat im PHP bekannt macht:
> > https://stackoverflow.com/questions/41772340/how-do-i-add-a-certificate-authority-to-php-so-the-file-function-trusts-certif
> > 
> > "Edit php.ini and add the line openssl.cafile=/etc/ssl/certs/cacert.pem to the top (or bottom)."
> > 
> > Wenn die Einstellung greift, sollte das in Tine auch unter Admin/Server Informationen stehen.
> > 
> > Ansonsten könnte man auch im PHP-Code nochmal schauen, welche Cert-Locations aktiv sind: http://php.net/manual/en/function.openssl-get-cert-locations.php
> > Das könnte man z.B. an zentraler Stelle (oder eben beim IMAP/Sieve-Zugriff) einbauen und ins Logfile schreiben.

> ok, jetzt habe ich es herausgefunden. Der Trick liegt darin, dass man wissen
muss, was PHP unter einem "correctly hashed certificate directory" mit
Zertifikaten versteht. Das Problem war, dass das Debian-Paket "ca-certificates"
das Zwischen-Zertifikat von RapidSSL, mit dem das Zertifikat für
mail.jobelmannschule.de signiert ist, nicht mitbringt. Das und das Wissen um das
"correctly hashed certificate directory", was ich mir zwischenzeitlich
zusammengesucht hatte, haben mich dann nämlich dazu gebracht, das
RapidSSL-Zwischen-Zertifikat auf dem Tine-Server nach
/usr/local/share/ca-certificates zu kopieren und dann "update-ca-certificates"
aufzurufen, woraufhin nämlich das "correctly hashed certificate directory" *mit*
dem RapidSSL-Zertifikat erstellt wird. Seitdem geht's. Jetzt kann ich auch das
"verifyPeer":"0" bei der IMAP-Konfig weglassen.

siehe auch https://service.metaways.net/Ticket/Display.html?id=159518
rt159518: [Jobelmannschule] Felamimail IMAP-Zugriff und Mailserver Zertifikate

Frage: Wir kann ich den Notifikations-Service einrichten?
=================

Am einfachsten kann dieser unter setup.php / Email eingerichtet werden.
Entscheidend sind folgende Felder (Werte sind beispielhaft):

Notifikationsdienst Emailadresse:
tine20notification.example.org

Benachrichtigungs-Benutzername:
tine20notification

Benachrichtigungs-Passwort:
••••

Lokaler Hostname (oder IP-Adresse) für den Notifikationsdienst:
localhost

Falls nicht direkt über "localhost" versendet werden soll, müssen noch folgende Felder ausgefüllt werden:

Hostname:
mailserver

Port:
25

Sichere Verbindung:
TLS

Authentifizierung:
Login

Damit sollten dann Termineinladungen und andere Notifications verschickt werden.

Falls die Einstellungen nicht korrekt sein sollten, findet man im tine20.log Informationen zur Fehlerursache.

Zum Testen kann eine Notification über

    php tine20.php --config=/etc/tine20/config.inc.php --method Tinebase.testNotification

an den eigenen Benutzer ausgelöst werden.
