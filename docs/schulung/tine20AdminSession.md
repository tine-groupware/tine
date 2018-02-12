Tine 2.0 Admin Schulung: Session-Settings
=================

Version: Egon 2016.11
Version: Caroline 2017.11

Konfiguration der Session

Session-Lifetime/Timeout einstellen
=================

Das Session-Timeout wird über eine php.ini-Datei geregelt.

Tine 2.0 bringt eine eigene Datei tine20.ini mit, die sich (bei Centos) im Verzeichnis /etc/php.d/ befindet.

Dort sollte es die Einstellung

session.gc_maxlifetime = 86400

geben, die das Timeout (in Sekunden) steuert. 86400 Sekunden = 1 Tag.

Wenn Sie die Zeit ändern möchten, können Sie das auf einen anderen Wert setzen und dann den Webserver neu starten, damit die Einstellung aktiv wird. Den aktuellen Wert können Sie auch in Tine 2.0 im Adminbereich (Serverinfo) einsehen.

Mehr Informationen zu den PHP-Session-Konfigurationen finden Sie hier: http://php.net/manual/en/session.configuration.php

siehe z.b. https://service.metaways.net/Ticket/Display.html?id=160376
