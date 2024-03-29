tine Admin Schulung: E-Mail / Felamimail
=================

Version: Lu 2021.11

Konfiguration und Problemlösungen des E-Mail-Moduls von tine

Frage: Wie kann ich den E-Mail-Cache leeren?
=================

> Da wir mittels Tine die Postfächer allerdings nur per IMAP
> "integrieren", ist das cachen der E-Mails an sich und der
> Informationen der E-Mail nicht notwendig.
> Allein die Tabelle tine20_felamimail_cache_message besitzt 936954
> Einträge, was die Datenbank nur unnötig füllt.

Der tine Email-Client benötigt diese Tabellen, u.a. zur Suche und Darstellung der Ordner und E-Mails.
 Der Cache wird neu aufgebaut, wenn er gelöscht wird. Bei der eingebauten Backup-Funktionalität
 wird der E-Mail-Cache nicht mit gesichert.

> Kann man die Tabellen ähnlich zu Tinebase.clearTable in gewissen
> Abständen leeren?

Das kann man tun. Allerdings wird sich dann die Last auf dem Server nach dem Leeren erhöhen,
 da dieser anschliessend versucht, den Cache wieder zu füllen. Das passiert im Regelfall
 kurz nachdem sich der entsprechende Nutzer angemeldet hat.
 
Am einfachsten geht das durch den Aufruf einer CLI-Methode:

    $ tine20-cli --method=Felamimail.truncatecache
    
Damit wird der Cache aller Benutzer gelöscht. Die Benutzer-Abfrage dient zur Prüfung des ADMIN-Rechts.

Es gab auch schon Überlegungen, den Zugriff direkt auf den IMAP-Server zu machen, ohne den Cache dazwischen.
 Das ist aber aktuell noch Zukunftsmusik.

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

Frage: Wie kann ich den Notifikations-Service einrichten?
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

Frage: Wie kann ich Felamimail aus einer externen Webapplikation/Webseite aufrufen, um eine E-Mail zu versenden?
=================

* Es gibt im tine Menü den Punkt "tine als Standard-Mailprogramm
Verwenden" erweitert. Klickt der Nutzer diesen Punkt muss er, je nach Browser, diese Entscheidung noch
einmal in einem Nachfragedialog vom Browser bestätigen.

* Hat der Nutzer tine als Standard-Email-Programm festgelegt, so öffnet sich beim klicken auf
einen Mailto-Link im Browser ein neues tine Fenster mit einem Email-Verfassen-Fenster. Größe und
Position des Fensters werden dabei vom Browser automatisch gewählt und können nicht von tine
beeinflusst werden.

* Um die Mail zu versenden, muss der Nutzer bereits vor dem klicken eines Mailto an tine
angemeldet sein. Andernfalls ist es nicht möglich, die Mail zu versenden.
Der Mailto-Link muss muss standardgemäß formatiert werden. Als Referenz beziehen wir uns auf:
de.selfhtml.org/html/verweise/email.htm

* Zusätzlich zu den im Standard definierten Parametern (wie to, cc, subject, body, ...) können im mailto
auch Anhänge per http(s)-URL übergeben werden. Hierzu wird der Parametername "attachments"
verwendet. Der Wert kann entweder eine einzelne URL sein oder eine Komma separierte Liste von URLs.
Der Wert mus URL-codiert übergeben werden. (siehe de.wikipedia.org/wiki/URL-Encoding)

BSP:

    <p>Mail mit Anhang und Betreff:<br>
        <a href="mailto:c.weiss@metaways.de?
        attachments=http%3A%2F%2Flocalhost%2FexternalFilesTest%2Fdevop2.jpg&subject=Hallo%20Fritz,
        %20hallo%20Heidi">mit Anhang</a>
    </p>

* Der übergebene Anhang muss vom Browser des Benutzers ohne Authentifikation erreichbar sein. Der
COSR header: Access-Control-Allow-Origin: "*" bzw. Access-Control-Allow-Origin: "tine20.poolwelt.de"
muss bei der Übertragung mitgesendet werden.

* Da der maximale Umfang der übergebaren Parameter in Mailto-Links durch die Browser beschränkt
wird, können Empfänger, Texte oder Anhänge nicht in beliebiger Länge verwendet werden. Derzeit liegt
dieses Limit bei 65000 Zeichen.

Frage: wie kann ich einstellen, dass in Felamimails in den E-Mails auch verlinkte Bilder angezeigt werden?
===================

Vorsicht: das deaktivieren des Filters kann Security-Probleme nach sich ziehen, vor allem wenn viele E-Mails
 aus nicht-vertrauenswürdigen Quellen angezeigt werden. 

Konfiguration (z.b. in config.inc.php):

    'Felamimail' => [
        Felamimail_Config::FILTER_EMAIL_URIS => true,
    ]

Frage: Wie kann ich mit Felamimail winmail.dat Anhänge automatisch entpacken 
=================

Dazu muss auf dem Server ein (y)tnef Binary installiert sein. Bei Debian/Ubuntu wird das über das Paket "tnef" verteilt:

    apt install tnef
    
Unter Alpine Linux heisst das Paket ytnef:

    apk add ytnef

 
Frage: welche Anforderung muss ein EMail-Server erfüllen um in tine vollständig integrierbar zu sein?
===================

Vollständige Unterstützung in tine bieten die Dovecot (IMAP + Sieve) und Postfix (SMTP) Server. Es gibt auch andere Mailserver/Systeme, die über Plugins angesteuert werden können, diese werden aber nicht im vollen Funktionsumfang unterstützt.

Bei Dovecot und Postfix werden die Bewegdaten (Benutzer, Domains, Destinations, Aliases, Forwards, ...) über MySQL-Tabellen verwaltet, die von tine und den Mailsystemen geschrieben und gelesen werden.

TODO: detaillierte Anleitung erstellen.

Anleitung Github: https://github.com/tine20/tine20/wiki/EN%3AMailserver

Frage: wie kann ich weitere Templates für Abwesenheitsnotizen hinterlegen?
===================

Dazu muss nur eine geeignete .tpl Datei in dieses Verzeichnis gelegt werden:

https://my.tine20.tld/webdav/Felamimail/shared/Vacation%20Templates
