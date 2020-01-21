Tine 2.0 Admin Schulung: Phone/Voipmanager/Telefonie
=================

Version: Nele 2018.11

Tine 2.0 bietet in Hinblick auf CTI folgende Möglichkeiten:

### Click2Dial, d.h. Anruf eines Kontaktes aus dem Adressbuch

Diese Funktion wird vom Tine 2.0 Server aus angestossen und kann auf dem Telefonie-Server z.B. eine Webschnittstelle bedienen, die dann den Anruf auslöst und das Telefon des Benutzers mit dem entsprechenden Kontakt verbindet. Möglich wäre auch, dass der Telefonie-Server über eine Linux-Binary auf dem Tine 2.0-System getriggert wird.

Eventuell ist hier (je nach Telefonie-Server) eine Adaption der Ansteuerung der Schnittstelle nötig, der Aufwand sollte sich aber in Grenzen halten.

### Call Monitor, d.h. Anrufhistorie

Hier werden alle Anrufe (eingehend und ausgehend) eines Telefons aufgezeichnet und können in Tine 2.0 angesehen und durchsucht werden. Es wird auch der entsprechende Kontakt aus dem Adressbuch zu dem Anruf angezeigt, wenn die Nummer zugeordnet werden kann.
Der Eintrag wird über eine Webschnittstelle (mit Authentifizierung) von Tine 2.0 angelegt und geht im Normalfall von den Telefonen aus.

### Konfiguration der Telefone, Telefonbenutzer und Rufnummern

Diese Funktion ist in Tine 2.0 für den Asterisk-Telefonie-Server in Zusammenspiel mit SNOM-Telefonen implementiert.

### Anbindung an Sipgate

Es gibt ein Tine 2.0 Community-Modul für die Anbindung an Sipgate (https://www.sipgate.de/). Damit sind die Funktionen 1. und 2. sowie SMS-Versand mit Sipgate möglich.

Allerdings ist das schon seit einiger Zeit nicht mehr gepflegt worden, so dass für die Inbetriebnahme hier noch Zusatzaufwand nötig wäre.