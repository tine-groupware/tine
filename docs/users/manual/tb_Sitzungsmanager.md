# Sitzungsmanager {: context="MeetingManager" }
## Einleitung

Der Sitzungsmanager dient der Erfassung von Sitzungen für verschiedene Gremien. Ein Gremium entspricht einem gemeinsamen Ordner und ist Voraussetzung für das Anlegen einer Sitzung.

Legen Sie daher zuerst per rechte Maustaste eine neues Gremium über Gemeinsame Gremien - Gremium hinzufügen an. Nun können Sie eine neue Sitzung erfassen.

Eine Sitzung fasst allgemeine Informationen, zum Beispiel Beginn und Ende der Sitzung, wie auch Daten zu den Teilnehmern, Gästen und Tagesordnungspunkten zusammen.
So können An- und Abwesenheit der Teilnehmer festgehalten, sowie Protokolle und Beschlüsse zu Tagesordnungspunkten erfasst werden.


## Sitzungen anlegen und bearbeiten {: context="MeetingManager/EditDialog/Meeting" }
Über den Sitzung-bearbeiten-Dialog können neue Sitzungen erfasst oder bestehende bearbeitet werden.
Sitzungen werden per default automatisch gespeichert, um Datenverlust bei längerer Bearbeitungsdauer zu vermeiden. Wenn Sie dieses Verhalten nicht wünschen, können Sie die Funktion über die Schaltfläche Automatisch Speichern deaktivieren. Das automatische Speichern kann auf dem selben Weg jederzeit wieder aktiviert werden.

!!! note "Anmerkung"
    Wenn Automatisch Speichern aktiviert ist, ist die Schaltfläche dunkel.

Der Titel einer Sitzung ist vorbelegt, kann jedoch beliebig geändert werden. Sitzungsnummer ist hierbei ein Platzhalter und wird beim Speichern durch die Sitzungsnummer ersetzt.
Die Sitzungsnummer selbst wird automatisch vom System vergeben und kann nicht geändert werden. Sie setzt sich stets aus dem aktuellen Jahr und einer fortlaufenden Nummer zusammen.

<!-- SCREENSHOT ABBILDUNG -->
![Abbildung: Sitzung-bearbeiten-Dialog]({{ img_url_desktop }}Sitzungsmanager/1_sitzungsmanager_dialog_light_1920x1020.png#only-light){.desktop-img}
![Abbildung: Sitzung-bearbeiten-Dialog]({{ img_url_desktop }}Sitzungsmanager/1_sitzungsmanager_dialog_dark_1920x1020.png#only-dark){.desktop-img}
![Abbildung: Sitzung-bearbeiten-Dialog]({{ img_url_mobile }}Sitzungsmanager/1_sitzungsmanager_dialog_light_1280x720.png#only-light){.mobile-img}
![Abbildung: Sitzung-bearbeiten-Dialog]({{ img_url_mobile }}Sitzungsmanager/1_sitzungsmanager_dialog_dark_1280x720.png#only-dark){.mobile-img}


### Teilnehmer einladen

Teilnehmer werden entweder direkt oder über  Gruppen erfasst. Jeder Teilnehmer kann dabei als "Anwesend" oder "Entschuldigt" markiert sein. Hieraus errechnen sich die Angaben zu anwesenden und abwesenden Teilnehmern.


### Tagesordnungspunkte verwalten

Unter dem Reiter TOP können Tagesordnungspunkte erstellt werden. Jeder TOP hat einen Protokoll- und einen Beschluss-Eintrag.
Wenn Sie die Beschreibung eines Tagesordnungspunkts editieren möchten, müssen Sie zunächst den Eintrag in der Liste selektieren. In dem Beschreibungsfeld (TOP - Inhaltstext) auf der rechten Seite erscheint dann der zugehörige Beschreibungstext. Dieselbe Funktionalität finden Sie ebenfalls im Reiter Protokoll und Beschreibung.

<!-- SCREENSHOT ABBILDUNG -->
![Abbildung: Tagesordnungspunkt]({{ img_url_desktop }}Sitzungsmanager/2_sitzungsmanager_top_light_1920x1020.png#only-light){.desktop-img}
![Abbildung: Tagesordnungspunkt]({{ img_url_desktop }}Sitzungsmanager/2_sitzungsmanager_top_dark_1920x1020.png#only-dark){.desktop-img}
![Abbildung: Tagesordnungspunkt]({{ img_url_mobile }}Sitzungsmanager/2_sitzungsmanager_top_light_1280x720.png#only-light){.mobile-img}
![Abbildung: Tagesordnungspunkt]({{ img_url_mobile }}Sitzungsmanager/2_sitzungsmanager_top_dark_1280x720.png#only-dark){.mobile-img}

Tagesordnungspunkte können mit einer „drag and drop“-Geste umsortiert werden. Dies ist jedoch nur möglich, so lange noch keine Protokoll-Einträge oder ein Beschluss vorliegen. Ein Protokoll liegt dann vor, wenn Änderungen unter dem Reiter Protokoll vorgenommen wurden und der Eintrag somit nicht mehr die initialen Werte beinhaltet. Ähnlich besteht ein Beschluss sobald das Häkchen bei Beschluss unter dem Reiter Beschluss gesetzt wurde.
Ebenfalls abhängig davon, ob ein Tagesordnungspunkt bereits ein Protokoll oder einen Beschluss besitzt, stellt das Kontextmenü zusätzliche Funktionen bereit.

Entfernen:
Löscht einen Tagesordnungspunkt, sofern noch kein Protokoll oder Beschluss vorhanden ist.

TOP in Sitzung verschieben:
Erlaubt es einen Tagesordnungspunkt in eine andere bestehende Sitzung zu verschieben. Ein verschobener TOP erhält automatisch einen entsprechenden Protokolleintrag.

TOP in Sitzung kopieren:
Kopiert einen Tagesordnungspunkt in diese oder eine andere bestehende Sitzung.

[WARNING]
=============== 
Die automatische Nummerierung ändert sich beim Sortieren, Löschen oder Verschieben von Tagesordnungspunkten.
=============== 

Im Normalfall sollten Sie diese Funktionen nur während der Planung Ihrer Sitzung verwenden. Im späteren Verlauf werden Sie feststellen, dass Protokolle oder Beschlüsse viele dieser Funktionen blockieren. Sollten Sie dennoch Änderungen vornehmen wollen, müssen Sie die entsprechenden Protokolle und Beschlüsse wieder entfernen, sprich alle gesetzten Felder zurück setzen.
Hiervon sollten Sie jedoch in der Regel absehen!


### Protokolle

Wie bereits erwähnt, besitzt jeder TOP einen zugehörigen Protokoll-Eintrag unter dem Reiter Protokoll. Hier können weitere Informationen zum Protokoll sowie natürlich das Protokoll selbst erfasst werden.

<!-- SCREENSHOT ABBILDUNG -->
![Abbildung: Protokoll]({{ img_url_desktop }}Sitzungsmanager/3_sitzungsmanager_minute_light_1920x1020.png#only-light){.desktop-img}
![Abbildung: Protokoll]({{ img_url_desktop }}Sitzungsmanager/3_sitzungsmanager_minute_dark_1920x1020.png#only-dark){.desktop-img}
![Abbildung: Protokoll]({{ img_url_mobile }}Sitzungsmanager/3_sitzungsmanager_minute_light_1280x720.png#only-light){.mobile-img}
![Abbildung: Protokoll]({{ img_url_mobile }}Sitzungsmanager/3_sitzungsmanager_minute_dark_1280x720.png#only-dark){.mobile-img}


### Beschluss

Unter dem Reiter Beschluss wird entsprechend ein Beschluss erfasst.
Ein TOP ist dann beschlossen, wenn das Häkchen bei Beschluss gesetzt wurde. Der Eintrag wird dann auch nicht mehr ausgegraut angezeigt.

<!-- SCREENSHOT ABBILDUNG -->
![Abbildung: Beschluss]({{ img_url_desktop }}Sitzungsmanager/4_sitzungsmanager_decision_light_1920x1020.png#only-light){.desktop-img}
![Abbildung: Beschluss]({{ img_url_desktop }}Sitzungsmanager/4_sitzungsmanager_decision_dark_1920x1020.png#only-dark){.desktop-img}
![Abbildung: Beschluss]({{ img_url_mobile }}Sitzungsmanager/4_sitzungsmanager_decision_light_1280x720.png#only-light){.mobile-img}
![Abbildung: Beschluss]({{ img_url_mobile }}Sitzungsmanager/4_sitzungsmanager_decision_dark_1280x720.png#only-dark){.mobile-img}


### Gäste

Möchten Sie zu Ihrer Sitzung Gäste einladen, die gegebenenfalls nicht in ihrem Adressbuch existieren, so können Sie dies über den Reiter Gäste tun.
Zusätzlich lassen sich hier sachkundige Personen erfassen.


### Befangenheit

Unter dem Reiter Befangenheit können Sie die Befangenheit einzelner Sitzungsteilnehmer zu beschlossenen Tagesordnungspunkten festhalten.


## Termineinladung

In der Kopfzeile des Sitzungsbearbeitungsdialogs finden Sie die Schaltfläche Termineinladung. Durch Klicken auf diese Schaltfläche öffnet sich ein vor-ausgefüllter Terminbearbeitungsdialog. Bestätigen Sie den Dialog mit OK, wird der Termin im Kalender angelegt. Zusätzlich finden Sie den Termin nun im Sitzungsbearbeitungsdialog unter Verknüpfungen. Außerdem wurde eine Tagesordnung erzeugt, diese finden Sie nun unter Anhänge.
Sollten Sie Änderungen an Teilnehmern oder TOPs vorgenommen haben und wollen eine neue Version der Tagesordnung erzeugen, so klicken Sie nochmals die Schaltfläche Termineinladung. Der bestehende Termin wird dann aktualisiert und ein neues Dokument erzeugt.


## Sitzungsdokumente erzeugen

In der Kopfzeile des Sitzungsbearbeitungsdialogs finden Sie die Schaltfläche Sitzungsdokumente erzeugen. Wenn Sie auf diese Schaltfläche klicken, erscheint eine Auswahl von verfügbaren Dokumenten, die Sie hier herunterladen können.