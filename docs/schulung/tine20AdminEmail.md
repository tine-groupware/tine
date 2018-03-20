Tine 2.0 Admin Schulung: E-Mail / Felamimail
=================

Version: Caroline 2017.11

Konfiguration und Problemlösungen des E-Mail-Moduls von Tine 2.0

Problem: IMAP-Server hat selbstsigniertes Zertifikat
=================

Wenn die Verbindung zum IMAP-Server fehlschlägt, kann es an einem
 selbstsignierten Zertifikat liegen. Dann muss man die "verifyPeer"-Option
 auf "false" (0) setzen. Das kann man am einfachsten direkt in der
 DB tun:
 
    mysql> select * from tine20_config where name = 'imap'\G
    *************************** 1. row ***************************
                id: a0ccbbce865f37c2c3113506d606d2a1293176cc
    application_id: 64d1f06623b4539810b50a249858c212ca13d533
              name: imap
             value: {"active":true,"backend":"dovecot_imap","host":"localhost","port":993,"ssl":"ssl","useSystemAccount":"1","domain":"","useEmailAsUsername":false,"dbmail":{"port":3306},"cyrus":{"useProxyAuth":0},"dovecot":{"host":"localhost","dbname":"dovecot","username":"dovecot","password":"dovecot","port":"3306","uid":"998","gid":"998","home":"\/var\/spool\/mail\/%d\/%n","scheme":"SSHA256"},"dovecotcombined":{"adapter":"pdo_mysql","port":3306},"instanceName":"tine20.mytine20.de"}
    1 row in set (0.00 sec)

    mysql> update tine20_config set value = '{"active":true,"backend":"dovecot_imap","host":"localhost","port":993,"ssl":"ssl","useSystemAccount":"1","domain":"","useEmailAsUsername":false,"dbmail":{"port":3306},"cyrus":{"useProxyAuth":0},"dovecot":{"host":"localhost","dbname":"dovecot","username":"dovecot","password":"dovecot","port":"3306","uid":"998","gid":"998","home":"\/var\/spool\/mail\/%d\/%n","scheme":"SSHA256"},"dovecotcombined":{"adapter":"pdo_mysql","port":3306},"verifyPeer":"0","instanceName":"tine20.mytine20.de"}' where name = 'imap'\G

