# Inventarisierung

<!--ERP-->
Die Anwendung Inventarisierung gehört – wie Sales, HumanResources und Zeiterfassung – nicht zu den Kernfunktionalitäten einer Groupware. Diese Anwendungen sind als "ERP-Bausteine" im Laufe der Zeit auf Kundenwunsch entstanden und haben dann Eingang in tine gefunden. Es werden sicher nicht die letzten Erweiterungen sein, sodass dieses Buch möglicherweise in der nächsten Auflage bereits weitere Zusatzanwendungen beschreiben wird.

<!--Inventar-,Listen-->
## Favoriten und Inventarlisten

Wie gewohnt finden Sie auf der linken Seite unter Favoriten nützliche Standardansichten. Hier ist es nur eine: Alle Inventargegenstände.

<!-- SCREENSHOT -->
![Abbildung: Inventarisierung in tine]({{ img_url_desktop }}Inventarisierung/1_inventarisierung_uebersicht_light_1920x1020.png#only-light){.desktop-img}
![Abbildung: Inventarisierung in tine]({{ img_url_desktop }}Inventarisierung/1_inventarisierung_uebersicht_dark_1920x1020.png#only-dark){.desktop-img}
![Abbildung: Inventarisierung in tine]({{ img_url_mobile }}Inventarisierung/1_inventarisierung_uebersicht_light_1280x720.png#only-light){.mobile-img}
![Abbildung: Inventarisierung in tine]({{ img_url_mobile }}Inventarisierung/1_inventarisierung_uebersicht_dark_1280x720.png#only-dark){.mobile-img}

<span id="inventoryitem-containertree"></span>
Die unter den Favoriten zu findenden Inventarlisten, enthalten standardmäßig die drei üblichen Ordner Meine Inventarlisten, Gemeinsame Inventarlisten und Inventarlisten anderer Benutzer, wobei der Inhalt der letztgenannten beiden Ordner von dem Vorhandensein solcher Listen in Ihrem speziellen tine-System sowie von Ihren Zugriffsrechten abhängt. Im Ordner Meine Inventarlisten finden Sie auf jeden Fall Ihre persönliche Inventarliste vor, denn diese erzeugt tine mit der Anlage des Benutzers automatisch.

<!--Inventar-,Gegenstände-->
## Inventar Gegenstand hinzufügen
Klicken Sie im Bearbeitungsmenü ganz links den Button Inventar Gegenstand hinzufügen.

<!-- SCREENSHOT -->
![Abbildung: Neuen Inventargegenstand hinzufügen]({{ img_url_desktop }}Inventarisierung/2_inventarisierung_gegenstand_neu_light_1920x1020.png#only-light){.desktop-img}
![Abbildung: Neuen Inventargegenstand hinzufügen]({{ img_url_desktop }}Inventarisierung/2_inventarisierung_gegenstand_neu_dark_1920x1020.png#only-dark){.desktop-img}
![Abbildung: Neuen Inventargegenstand hinzufügen]({{ img_url_mobile }}Inventarisierung/2_inventarisierung_gegenstand_neu_light_1280x720.png#only-light){.mobile-img}
![Abbildung: Neuen Inventargegenstand hinzufügen]({{ img_url_mobile }}Inventarisierung/2_inventarisierung_gegenstand_neu_dark_1280x720.png#only-dark){.mobile-img}

Die Bearbeitungsmaske enthält unter dem Reiter Allgemein zwei Pflichteingabefelder: ID und Name. Die Identifikationsnummer können Sie händisch vergeben oder vom System erzeugen lassen; im letzteren Fall klicken Sie den kleinen Zauberstab-Button an der rechten Seite des Feldes an, und es wird eine eindeutige ID generiert.

Darunter folgt das Textfeld Beschreibung. Bevor Sie hier etwas eingeben, sehen Sie sich die anderen Felder an: Es gibt einen Standort und drei Datumsfelder: Hinzugefügt, Garantie und Entfernt. Dazu finden Sie in der untersten Spalte zwei numerische Felder, Totale Anzahl und Verfügbare Anzahl, die beide mit 1 vorbelegt sind, sowie rechts unten ein Pulldown Status. Dieses Statusfeld dient der Beschreibung einiger definierter Zustände, die ein Inventargegenstand annehmen kann.

<!-- SCREENSHOT -->
![Abbildung: Status zuweisen]({{ img_url_desktop }}Inventarisierung/3_inventarisierung_gegenstand_status_light_1920x1020.png#only-light){.desktop-img}
![Abbildung: Status zuweisen]({{ img_url_desktop }}Inventarisierung/3_inventarisierung_gegenstand_status_dark_1920x1020.png#only-dark){.desktop-img}
![Abbildung: Status zuweisen]({{ img_url_mobile }}Inventarisierung/3_inventarisierung_gegenstand_status_light_1280x720.png#only-light){.mobile-img}
![Abbildung: Status zuweisen]({{ img_url_mobile }}Inventarisierung/3_inventarisierung_gegenstand_status_dark_1280x720.png#only-dark){.mobile-img}

Tragen Sie also in Beschreibung nur solche Informationen ein, die nicht bereits durch andere Felder vorgesehen sind.

Im grauen Randbereich des unteren Teils der Eingabemaske finden Sie links das Pulldown Gespeichert in. Hier wählen Sie die Inventarliste, in der der Gegenstand landen soll. Je nach Berechtigungen finden Sie hier Inventarlisten anderer Benutzer sowie gemeinsame Inventarlisten. Sollten Sie hier nicht die Inventarlisten finden, die Sie suchen, schauen Sie sich die vorhandenen Inventarlisten und die zugehörigen Rechtevergaben in der Anwendung Admin an ([Administration - Container](oa_Administration.md/#container)).

Neben den bereits aus anderen Anwendungen bekannten Reitern  Historie, Anhänge und Verknüpfungen ([Allgemeine Hinweise zur Bedienung](ca_StandardBedienhinweise.md)) finden Sie hier noch einen Reiter Buchhaltung.

<!-- SCREENSHOT -->
![Abbildung: Buchhaltungsinformationen]({{ img_url_desktop }}Inventarisierung/4_inventarisierung_gegenstand_buchhaltung_light_1920x1020.png#only-light){.desktop-img}
![Abbildung: Buchhaltungsinformationen]({{ img_url_desktop }}Inventarisierung/4_inventarisierung_gegenstand_buchhaltung_dark_1920x1020.png#only-dark){.desktop-img}
![Abbildung: Buchhaltungsinformationen]({{ img_url_mobile }}Inventarisierung/4_inventarisierung_gegenstand_buchhaltung_light_1280x720.png#only-light){.mobile-img}
![Abbildung: Buchhaltungsinformationen]({{ img_url_mobile }}Inventarisierung/4_inventarisierung_gegenstand_buchhaltung_dark_1280x720.png#only-dark){.mobile-img}

Neben dem Preis hinterlegen Sie über ein Pulldown die Kostenstelle des Gegenstands. Bedingung dafür ist natürlich, dass in Ihrem tine-System Kostenstellen angelegt wurden ([Sales](ha_Sales.md)).

In der Reihe darunter besteht die Möglichkeit, eine freie Bezeichnung der Anschaffungsrechnung für den betreffenden Gegenstand sowie das Rechnungsdatum zu hinterlegen. Alle Eingaben in der Buchhaltungsmaske sind fakultativ.

## Weitere Funktionen des Bearbeitungsmenüs
Inventargegenstand bearbeiten: Der Punkt des Bearbeitungsmenüs ist nur aktiv, wenn Sie in der Tabelle einen Gegenstand markiert haben. Sie erreichen das Menü auch über einen Doppelklick auf den Tabelleneintrag oder über einen Rechtsklick und das Kontextmenü.

Inventar Gegenstand löschen: löscht einen in der Tabelle angewählten Inventargegenstand, immer mit Sicherheitsabfrage.

Drucke Seite: öffnet Ihren betriebssystemeigenen Druckdialog und erzeugt standardmäßig eine Liste aller in der Tabellenansicht dargestellten Inventargegenstände im DIN-A4-Hochformat.

Inventar exportieren: Im rechten Teil des Bearbeitungsmenüs finden Sie übereinander zwei Buttons zum Ex- und Import von Inventargegenständen. Inventar exportieren ist ein Pulldown mit den folgenden Optionen:

Exportieren als ODS; erzeugt eine Tabelle im Dateiformat von Open-/LibreOffice; die Zeilen- und Spaltenanordnung entspricht derjenigen der Bildschirmtabelle.

Exportieren als CSV; erzeugt eine CSV-Textdatei, um z.B. die Tabelle in neueren Versionen von MS Excel einzulesen.

Exportieren als...; öffnet ein Fenster mit einem weiteren Pulldown. In der Standardausführung von tine finden Sie hier ebenfalls das ODS-Format sowie das XLS-Format (MS Excel bis Version 2000/XP). Der Menüpunkt ist auch zum Ausbau für kundenspezifische Sonderformate vorgesehen.

Gegenstände importieren: Die Importfunktion für Inventargegenstände funktioniert analog derjenigen im Adressbuch, d.h. Sie sollten sich als ersten Schritt die CSV-Beispieldatei ansehen, die Sie im Bearbeitungsfenster Datei und Format wählen unter dem Link Beispieldatei herunterladen finden (vgl. [Adressverwaltung - Kontakte importieren](ba_Adressbuch.md/#kontakte-importieren)).

<!-- SCREENSHOT -->
![Abbildung: Inventargegenstände importieren]({{ img_url_desktop }}Inventarisierung/5_inventarisierung_import_light_1920x1020.png#only-light){.desktop-img}
![Abbildung: Inventargegenstände importieren]({{ img_url_desktop }}Inventarisierung/5_inventarisierung_import_dark_1920x1020.png#only-dark){.desktop-img}
![Abbildung: Inventargegenstände importieren]({{ img_url_mobile }}Inventarisierung/5_inventarisierung_import_light_1280x720.png#only-light){.mobile-img}
![Abbildung: Inventargegenstände importieren]({{ img_url_mobile }}Inventarisierung/5_inventarisierung_import_dark_1280x720.png#only-dark){.mobile-img}