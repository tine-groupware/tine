# eAkte

Mit fortschreitender Digitalisierung inklusive deren gesetzlichen Rahmenbedingungen ist die Einführung einer elektronischen Aktenführung zunehmend erforderlich.
Dies hat uns veranlasst, in enger Zusammenarbeit mit Kunden und in Anlehnung an die rechtlichen, fachlichen sowie funktionalen Anforderungen zum „Baustein E-Akte“ des „Organisationskonzepts elektronische Verwaltungsarbeit“ vom Bundesministerium des Innern (BMI), in tine die Anwendung eAkte zu entwickeln.
Die elektronische Abbildung entspricht dabei den Grundsätzen ordnungsgemäßer Aktenführung (gesetzlich nicht normiert) und der verwaltungs- bzw. behördlichen Schriftgutsammlung.

Als E-Akte wird allgemein eine digitale Datensammlung bezeichnet, die nach dem Vorbild herkömmlicher Akten (auf Papier) aufgebaut ist.

## Aufbau und Struktur eAkte

Die Anwendung eAkte ist integriert in der tine Anwendung [Dateimanager](ga_Dateimanager.md) und über den Button eAkte rechts im oberen Bearbeitungsmenue über der Tabelle aktiviert. In diesem Bereich des Handbuches konzentrieren wir uns auf die Spezifika der eAkte. Bitte entnehmen Sie Grundlegendes im Umgang mit dem Dateimanager der tine Anwendung [Dateimanager](ga_Dateimanager.md).

Voraussetzung für die Führung einer eAkte, ist die Anlage eines Hauptordners (z.B. "Aktenplan") im Dateimanager unter Gemeinsame Ordner.
Dieser muss in den Konfigurationseinstellungen in der tine Anwendung admin unter eAkte hinterlegt werden. Erst dann erkennt das System diesen als eAkte.

<!-- SCREENSHOT -->
![Abbildung: Einbindung eAkte in Dateimanager]({{ img_url_desktop }}EAkte/1_eakte_dateimanager_light_1920x1020.png#only-light){.desktop-img}
![Abbildung: Einbindung eAkte in Dateimanager]({{ img_url_desktop }}EAkte/1_eakte_dateimanager_dark_1920x1020.png#only-dark){.desktop-img}
![Abbildung: Einbindung eAkte in Dateimanager]({{ img_url_mobile }}EAkte/1_eakte_dateimanager_light_1280x720.png#only-light){.mobile-img}
![Abbildung: Einbindung eAkte in Dateimanager]({{ img_url_mobile }}EAkte/1_eakte_dateimanager_dark_1280x720.png#only-dark){.mobile-img}

Der Aufbau und die Struktur einer eAkte besteht aus verschieden Ebenen. Diese werden über zur Verfügung stehende eAkte Ebenen-Typen gesteuert. Die eAkte Ebenen-Typen stehen dabei untereinander in festgelegten Abhängigkeiten und verfügen über bestimmte Merkmale und Konfigurationen. Diese Einstellungen werden dabei einmalig vor Anwendung der eAkte zentral auf Systemebene konfiguriert (entsprechend der jeweiligen Kundenbedürfnisse) und steuern u.a. die automatische Nummerierung mit den erforderlichen Trennzeichen zum Aufbau der Aktenzeichen.

Im Umgang mit der eAkte unterstützt Sie tine, neben der automatischen Nummerierung, bei der Anlage neuer Ebenen in Form von Ordnern über den Button eAkte und zeigt nur die jeweils zulässigen eAkte Ebenen-Typen aktiv an. Dies immer in Abhängigkeit der Ebene, unterhalb welcher, weitere Ordner und Daten angelegt werden sollen.
Jeder eAkte Ebenen-Typen ist mit einem eigenen Icon versehen und somit optisch hervorgehoben und leicht erkennbar.

Für einen einfachen Überblick werden nachfolgend die wichtigsten Fakten und Bedingungen bei der Anlage von Ebenen in Form von Ordnern und deren Daten innerhalb der eAkte pro eAkte Ebenen-Typen beschrieben.

### Rahmenaktenplan

Befinden Sie sich im linken Bearbeitungsmenü des Dateimanager auf der Ebene eines Ordners vom eAkte Ebenen-Typ Rahmenaktenplan öffnet sich, durch Klick auf den Button eAkte im Bearbeitungsmenü rechts oben über der Tabelle, ein Pulldown Menü mit den aktiven Optionen Rahmenaktenplan hinzufügen und Aktengruppe hinzufügen zur Anlage eines Unterordners.
Befinden Sie sich im linken Bearbeitungsmenü des Dateimanager direkt auf der eAkte (Hauptordner) erhalten Sie nur die Option Rahmenaktenplan hinzufügen als aktive Option.

<!-- SCREENSHOT -->
![Abbildung: Ebene Rahmenaktenplan]({{ img_url_desktop }}EAkte/2_eakte_rahmenaktenplan_light_1920x1020.png#only-light){.desktop-img}
![Abbildung: Ebene Rahmenaktenplan]({{ img_url_desktop }}EAkte/2_eakte_rahmenaktenplan_dark_1920x1020.png#only-dark){.desktop-img}
![Abbildung: Ebene Rahmenaktenplan]({{ img_url_mobile }}EAkte/2_eakte_rahmenaktenplan_light_1280x720.png#only-light){.mobile-img}
![Abbildung: Ebene Rahmenaktenplan]({{ img_url_mobile }}EAkte/2_eakte_rahmenaktenplan_dark_1280x720.png#only-dark){.mobile-img}

Bei Klick auf die gewünschte Option, erhalten Sie ein Eingabefenster, werden nach dem zu generierenden Ordnernamen gefragt und erhalten einen Hinweis zur automatischen Vergabe einer eAkten Ebenen-Nummer.

Zusammenfassend die Merkmale eines Ordners vom eAkte Ebenen-Typ Rahmenaktenplan:

* mögliche Ebenen oberhalb: eAkte (Hauptordner) oder ein anderer Ordner vom eAkte Ebenen-Typ Rahmenaktenplan
* mögliche Ebenen unterhalb: Ordner vom eAkte Ebenen-Typ Rahmenaktenplan oder Aktengruppe
* Ablage von Dateien und Dokumente unterhalb nicht möglich
* Vergabe einer vorangestellten eAkten Ebenen-Nummer erfolgt automatisch

### Aktengruppe

Befinden Sie sich im linken Bearbeitungsmenü des Dateimanager auf der Ebene eines Ordners vom eAkte Ebenen-Typ Aktengruppe öffnet sich, durch Klick auf den Button eAkte im Bearbeitungsmenü rechts oben über der Tabelle, ein Pulldown Menü mit der aktiven Option Akte hinzufügen zur Anlage eines Unterordners.

<!-- SCREENSHOT -->
![Abbildung: Ebene Aktengruppe]({{ img_url_desktop }}EAkte/3_eakte_aktengruppe_light_1920x1020.png#only-light){.desktop-img}
![Abbildung: Ebene Aktengruppe]({{ img_url_desktop }}EAkte/3_eakte_aktengruppe_dark_1920x1020.png#only-dark){.desktop-img}
![Abbildung: Ebene Aktengruppe]({{ img_url_mobile }}EAkte/3_eakte_aktengruppe_light_1280x720.png#only-light){.mobile-img}
![Abbildung: Ebene Aktengruppe]({{ img_url_mobile }}EAkte/3_eakte_aktengruppe_dark_1280x720.png#only-dark){.mobile-img}

Bei Klick auf diese Option, erhalten Sie ein Eingabefenster, werden nach dem zu generierenden Ordnernamen gefragt und erhalten einen Hinweis zur automatischen Vergabe einer eAkten Ebenen-Nummer.

Zusammenfassend die Merkmale eines Ordners vom eAkte Ebenen-Typ Aktengruppe:

* mögliche Ebenen oberhalb: Ordner vom eAkte Ebenen-Typ Rahmenaktenplan
* mögliche Ebenen unterhalb: Ordner vom eAkte Ebenen-Typ Akte
* Ablage von Dateien und Dokumente unterhalb nicht möglich
* Vergabe einer vorangestellten eAkten Ebenen-Nummer erfolgt automatisch

### Akte

Befinden Sie sich im linken Bearbeitungsmenü des Dateimanager auf der Ebene eines Ordners vom eAkte Ebenen-Typ Akte öffnet sich, durch Klick auf den Button eAkte im Bearbeitungsmenü rechts oben über der Tabelle, ein Pulldown Menü mit den aktiven Optionen Teilkte hinzufügen, Vorgang hinzufügen und Dokumentenordner hinzufügen zur Anlage eines Unterordners.

<!-- SCREENSHOT -->
![Abbildung: Ebene Akte]({{ img_url_desktop }}EAkte/4_eakte_akte_light_1920x1020.png#only-light){.desktop-img}
![Abbildung: Ebene Akte]({{ img_url_desktop }}EAkte/4_eakte_akte_dark_1920x1020.png#only-dark){.desktop-img}
![Abbildung: Ebene Akte]({{ img_url_mobile }}EAkte/4_eakte_akte_light_1280x720.png#only-light){.mobile-img}
![Abbildung: Ebene Akte]({{ img_url_mobile }}EAkte/4_eakte_akte_dark_1280x720.png#only-dark){.mobile-img}

Bei Klick auf die gewünschte Option, erhalten Sie ein Eingabefenster, werden nach dem zu generierenden Ordnernamen gefragt und erhalten einen Hinweis zur automatischen Vergabe einer eAkten Ebenen-Nummer.

Zusammenfassend die Merkmale eines Ordners vom eAkte Ebenen-Typ Akte:

* mögliche Ebenen oberhalb: Ordner vom eAkte Ebenen-Typ Aktengruppe
* mögliche Ebenen unterhalb: Ordner vom eAkte Ebenen-Typ Teilakte, Vorgang oder Dokumentenordner
* unterhalb Ablage von Dateien und Dokumenten möglich
* Vergabe einer vorangestellten eAkte Ebenen-Nummer erfolgt automatisch

### Teilakte

Befinden Sie sich im linken Bearbeitungsmenü des Dateimanager auf der Ebene eines Ordners vom eAkte Ebenen-Typ Teilakte öffnet sich, durch Klick auf den Button eAkte im Bearbeitungsmenü rechts oben über der Tabelle, ein Pulldown Menü mit den aktiven Optionen Teilkte hinzufügen, Vorgang hinzufügen und Dokumentenordner hinzufügen zur Anlage eines Unterordners.

<!-- SCREENSHOT -->
![Abbildung: Ebene Teilakte]({{ img_url_desktop }}EAkte/5_eakte_teilakte_light_1920x1020.png#only-light){.desktop-img}
![Abbildung: Ebene Teilakte]({{ img_url_desktop }}EAkte/5_eakte_teilakte_dark_1920x1020.png#only-dark){.desktop-img}
![Abbildung: Ebene Teilakte]({{ img_url_mobile }}EAkte/5_eakte_teilakte_light_1280x720.png#only-light){.mobile-img}
![Abbildung: Ebene Teilakte]({{ img_url_mobile }}EAkte/5_eakte_teilakte_dark_1280x720.png#only-dark){.mobile-img}

Bei Klick auf die gewünschte Option, erhalten Sie ein Eingabefenster, werden nach dem zu generierenden Ordnernamen gefragt und erhalten einen Hinweis zur automatischen Vergabe einer eAkten Ebenen-Nummer.

Zusammenfassend die Merkmale eines Ordners vom eAkte Ebenen-Typ Teilakte:

* mögliche Ebenen oberhalb: Ordner vom eAkte Ebenen-Typ Akte oder Teilakte
* mögliche Ebenen unterhalb: Ordner vom eAkte Ebenen-Typ Teilakte, Vorgang oder Dokumentenordner
* unterhalb Ablage von Dateien und Dokumenten möglich
* Vergabe einer vorangestellten eAkte Ebenen-Nummer erfolgt automatisch

### Vorgang

Befinden Sie sich im linken Bearbeitungsmenü des Dateimanager auf der Ebene eines Ordners vom eAkte Ebenen-Typ Vorgang öffnet sich, durch Klick auf den Button eAkte im Bearbeitungsmenü rechts oben über der Tabelle, ein Pulldown Menü mit der aktiven Option Dokumentenordner hinzufügen zur Anlage eines Unterordners.

<!-- SCREENSHOT -->
![Abbildung: Ebene Vorgang]({{ img_url_desktop }}EAkte/6_eakte_vorgang_light_1920x1020.png#only-light){.desktop-img}
![Abbildung: Ebene Vorgang]({{ img_url_desktop }}EAkte/6_eakte_vorgang_dark_1920x1020.png#only-dark){.desktop-img}
![Abbildung: Ebene Vorgang]({{ img_url_mobile }}EAkte/6_eakte_vorgang_light_1280x720.png#only-light){.mobile-img}
![Abbildung: Ebene Vorgang]({{ img_url_mobile }}EAkte/6_eakte_vorgang_dark_1280x720.png#only-dark){.mobile-img}

Bei Klick auf diese Option, erhalten Sie ein Eingabefenster, werden nach dem zu generierenden Ordnernamen gefragt und erhalten einen Hinweis zur automatischen Vergabe einer eAkten Ebenen-Nummer.

Zusammenfassend die Merkmale eines Ordners vom eAkte Ebenen-Typ Vorgang:

* mögliche Ebenen oberhalb: Ordner vom eAkte Ebenen-Typ Akte oder Teilakte
* mögliche Ebenen unterhalb: Ordner vom eAkte Ebenen-Typ Dokumentenordner
* unterhalb Ablage von Dateien und Dokumenten möglich
* Vergabe einer vorangestellten eAkte Ebenen-Nummer erfolgt automatisch

!!! note "Anmerkung"
    Die Nummerierung der Ordner vom Ebenen-Typ Vorgang erfolgt global im gesamten System / der Installation.
    Dies bedeutet, dass jeder Vorgang eine eigene und eindeutige Nummer erhält, unabhängig in welcher Ebene und unter welchem Ordner der jeweilige Vorgang zugehörig ist. Diese eAkten Ebenen-Nummer (Vorgangsnummer) ist ein Teil des jeweiligen Aktenzeichens.

### Dokumentenordner

Befinden Sie sich im linken Bearbeitungsmenü des Dateimanager auf der Ebene eines Ordners vom eAkte Ebenen-Typ Dokumentenordner öffnet sich, durch Klick auf den Button eAkte im Bearbeitungsmenü rechts oben über der Tabelle, ein Pulldown Menü mit der aktiven Option Dokumentenordner hinzufügen zur Anlage eines Unterordners.

<!-- SCREENSHOT -->
![Abbildung: Ebene Dokumentenordner]({{ img_url_desktop }}EAkte/7_eakte_dokumentenordner_light_1920x1020.png#only-light){.desktop-img}
![Abbildung: Ebene Dokumentenordner]({{ img_url_desktop }}EAkte/7_eakte_dokumentenordner_dark_1920x1020.png#only-dark){.desktop-img}
![Abbildung: Ebene Dokumentenordner]({{ img_url_mobile }}EAkte/7_eakte_dokumentenordner_light_1280x720.png#only-light){.mobile-img}
![Abbildung: Ebene Dokumentenordner]({{ img_url_mobile }}EAkte/7_eakte_dokumentenordner_dark_1280x720.png#only-dark){.mobile-img}

Bei Klick auf diese Option, erhalten Sie ein Eingabefenster, werden nach dem zu generierenden Ordnernamen gefragt und erhalten einen Hinweis zur automatischen Vergabe einer eAkten Ebenen-Nummer.

Zusammenfassend die Merkmale eines Ordners vom eAkte Ebenen-Typ Dokumentenordner:

* mögliche Ebenen oberhalb: Ordner vom eAkte Ebenen-Typ Teilakte, Vorgang, Dokumentenordner
* mögliche Ebenen unterhalb: Ordner vom eAkte Ebenen-Typ Dokumentenordner
* unterhalb Ablage von Dateien und Dokumenten möglich
* Vergabe einer vorangestellten eAkten Ebenen-Nummer erfolgt automatisch

## Metadaten und Eigenschaften

Die speziellen eAkte Eigenschaften sind in die bereits bestehenden Eigenschaften der Daten in der Anwendung [Dateimanager](ga_Dateimanager.md) integriert. Jeder Ordner und Datei innerhalb der eAkte verfügt über verschiedene Eigenschaften und enthält Informationen aus den übergeordneten Daten sowie Metadaten der Akte.
Öffnen Sie die Eigenschaften über den Button Eigenschaften bearbeiten oder mit der rechten Maustaste über das Kontextmenü auf dem jeweiligen Ordner bzw. der ausgewählten Datei.

Folgendes Beispiel zeigt den Bearbeitendialog Datei bearbeiten mit den Basis Eigenschaften im Reiter Datei.

<!-- SCREENSHOT -->
![Abbildung: Eigenschaften Reiter Datei]({{ img_url_desktop }}EAkte/8_eakte_eigenschaften_datei_light_1920x1020.png#only-light){.desktop-img}
![Abbildung: Eigenschaften Reiter Datei]({{ img_url_desktop }}EAkte/8_eakte_eigenschaften_datei_dark_1920x1020.png#only-dark){.desktop-img}
![Abbildung: Eigenschaften Reiter Datei]({{ img_url_mobile }}EAkte/8_eakte_eigenschaften_datei_light_1280x720.png#only-light){.mobile-img}
![Abbildung: Eigenschaften Reiter Datei]({{ img_url_mobile }}EAkte/8_eakte_eigenschaften_datei_dark_1280x720.png#only-dark){.mobile-img}

Die eAkte betreffenden grundlegenden Felder Aktenzeichen und eAkte Ebenen-Typ finden Sie im mittleren Bereich eAkte innerhalb des Reiters Datei. Der Bereich eAkte selbst ist für alle Datensätze unterhalb des Hauptordner aktiv und sichtbar.
Beide Felder werden innerhalb der eAkte automatisch bei Anlage der Daten vom System gesetzt.
Das Aktenzeichen wird entsprechend der Konfigurationen zur jeweiligen Ebene mit den vorgegebenen Trennzeichen gefüllt und setzt sich darüber hinaus aus den Aktenzeichen übergeordneter Daten zusammen.

Beispiel Aktenzeichen: //08.08.01.04//000002/003/001-000001

* (Trennzeichen + Rahmenaktenplan)      //08
* (Trennzeichen + Rahmenaktenplan)      .08
* (Trennzeichen + Rahmenaktenplan)      .01
* (Trennzeichen + Aktengruppe)          .04
* (Trennzeichen + Akte)                 //000002
* (Trennzeichen + Teilakte)             /003
* (Trennzeichen + Teilakte)             /001
* (Trennzeichen + Dokumentennummer)     -000001

Der eAkte Ebenen-Typ wurde unter [Aufbau und Struktur eAkte](sa_eAkte.md/#aufbau-und-struktur-eakte) für die einzelnen Ebenen erklärt und wird vom System automatisch gesetzt.

Eine für Sie interessante Information in den Eigenschaften ist der Pfad, welcher unabhängig der eAkte in der Anwendung Dateimanger, bei allen Datensätzen den exakten Namen der gesamten Ordnerstruktur bis zum aktuellen ausgewählten Datensatz enthält. Dies entspricht auch der Navigation in der Baumstruktur links im Dateimanager und wird oberhalb der Tabelle im Dateimanger als Filter gesetzt.

In einem separaten Reiter eAkte im Bearbeitendialog können die Akten-spezifischen
Metadaten erfasst und bearbeitet werden. Dieser Reiter eAkte ist erst ab einem eAkte Ebenen-Typ Akte aktiv. Auf untergeordneten Ebenen der Akte sind die Metadaten einsehbar, können jedoch nicht bearbeitet werden.

Ein Klick auf den Button Zur Akte springen im oberen Bereich des Bearbeitungsdialogs, springt (wie der Name vermuten lässt) im Dateimanager auf den zugehörigen und in der Baumstruktur höher liegenden Akten-Datensatz mit eAkte Ebenen-Typ Akte. Entsprechend ist der Button Zur Akte springen nur auf Daten unterhalb der Akte sichtbar.
Nutzen Sie den Button, um schnell auf die richtige Ebene zur Bearbeitung der Akten-Metadaten zu springen.

<!-- SCREENSHOT -->
![Abbildung: Eigenschaften Reiter eAkte]({{ img_url_desktop }}EAkte/9_eakte_eigenschaften_eakte_light_1920x1020.png#only-light){.desktop-img}
![Abbildung: Eigenschaften Reiter eAkte]({{ img_url_desktop }}EAkte/9_eakte_eigenschaften_eakte_dark_1920x1020.png#only-dark){.desktop-img}
![Abbildung: Eigenschaften Reiter eAkte]({{ img_url_mobile }}EAkte/9_eakte_eigenschaften_eakte_light_1280x720.png#only-light){.mobile-img}
![Abbildung: Eigenschaften Reiter eAkte]({{ img_url_mobile }}EAkte/9_eakte_eigenschaften_eakte_dark_1280x720.png#only-dark){.mobile-img}

Schauen wir uns nun die Metadaten einmal genauer an (die Daten sind informativ).

Sind noch keine Metadaten zur Akte gespeichert wird in Laufzeit von das aktuelle Tagesdatum vorgeschlagen, ändern Sie dieses bei Bedarf. Erforderlich ist bei Speicherung der Metadaten die Angabe der Aktenführende Stelle. Das Datum für die Laufzeit bis können Sie leer lassen und jederzeit nachtragen.

Handelt es sich um eine Kombination aus elektronischer Akte und Papierakte, setzen Sie bitte ein Häkchen in Hybridakte (Es gibt eine korrespondierende Papierakte), so dass Sie nun den Standort der Papierakte eintragen können.

Für geschlossene Akten setzen Sie bitte das Häkchen in Die Akte ist geschlossen. Dadurch werden weitere Felder aktiv und Sie tragen das Datum Schlussverfügung ein, wählen den Kontakt in Schlussverfügung durch sowie die passende Option zur Aufbewahrungsfrist (6 Jahre, 10 Jahre, ewig) über das Dropdown Menü aus und ergänzen dies ggf. um das Datum zum Ende der Aufbewahrungsfrist.

Akten deren Aufbewahrungsfrist abgelaufen ist und die ausgesondert sind, kennzeichnen Sie mit einem Häkchen in Die Akte ist ausgesondert. Es werden weitere Felder aktiv. Wählen Sie eine Option zur Aussonderungsart (Kassiert (vernichtet), Archiviert) über das Dropdown Menü aus, setzen das Aussonderungsdatum und erfassen Sie den Archivnamen.

