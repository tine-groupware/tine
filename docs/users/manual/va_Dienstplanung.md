# Dienstplanung
## Einleitung

Über die Dienstplanung lassen sich die verschiedenen Teilnehmerrollen für Termine planen, in dem Benutzer einfach per Drag and Drop in entsprechende Rollen verteilt werden. Beim Speichern werden diese dann direkt in den Termin übertragen.

<!-- SCREENSHOT -->
![Abbildung: Die Dienstplanung]({{ img_url_desktop }}Dienstplanung/1_dienstplanung_overview_light_1920x1020.png#only-light){.desktop-img}
![Abbildung: Die Dienstplanung]({{ img_url_desktop }}Dienstplanung/1_dienstplanung_overview_dark_1920x1020.png#only-dark){.desktop-img}
![Abbildung: Die Dienstplanung]({{ img_url_mobile }}Dienstplanung/1_dienstplanung_overview_light_1280x720.png#only-light){.mobile-img}
![Abbildung: Die Dienstplanung]({{ img_url_mobile }}Dienstplanung/1_dienstplanung_overview_dark_1280x720.png#only-dark){.mobile-img}

Es gibt jedoch einige Ergänzungen, die eingestellt werden müssen, um dies zu ermöglichen. Diese sollen im folgenden Kapitel erläutert werden.


## Vorbereitung

Mit der Anwendung Dienstplanung wird die Kalender- und Adressbuch-Anwendung erweitert. Im Kalender gibt es zwei Änderungen. Zum einen kann nun beim Erstellen eines Termins auch eine Terminart eingestellt werden. Nur die Termine, die einer Terminart zugeordnet sind, werden innerhalb der Dienstplanungs-Anwendung angezeigt.

<!-- SCREENSHOT -->
![Abbildung: Terminarten in der Termin Einstellung]({{ img_url_desktop }}Dienstplanung/4_dienstplanung_terminarten_light_1920x1020.png#only-light){.desktop-img}
![Abbildung: Terminarten in der Termin Einstellung]({{ img_url_desktop }}Dienstplanung/4_dienstplanung_terminarten_dark_1920x1020.png#only-dark){.desktop-img}
![Abbildung: Terminarten in der Termin Einstellung]({{ img_url_mobile }}Dienstplanung/4_dienstplanung_terminarten_light_1280x720.png#only-light){.mobile-img}
![Abbildung: Terminarten in der Termin Einstellung]({{ img_url_mobile }}Dienstplanung/4_dienstplanung_terminarten_dark_1280x720.png#only-dark){.mobile-img}

Des weiteren gibt es nun auch den Reiter Dienstplanung. Hier kann eingestellt werden, wieviele unterschiedliche Mitarbeiter für diesen Termin benötigt werden.

<!-- SCREENSHOT -->
![Abbildung: Terminarten in der Termin Einstellung]({{ img_url_desktop }}Dienstplanung/5_dienstplanung_termineinstellungen_dienstplanungsreiter_light_1920x1020.png#only-light){.desktop-img}
![Abbildung: Terminarten in der Termin Einstellung]({{ img_url_desktop }}Dienstplanung/5_dienstplanung_termineinstellungen_dienstplanungsreiter_dark_1920x1020.png#only-dark){.desktop-img}
![Abbildung: Terminarten in der Termin Einstellung]({{ img_url_mobile }}Dienstplanung/5_dienstplanung_termineinstellungen_dienstplanungsreiter_light_1280x720.png#only-light){.mobile-img}
![Abbildung: Terminarten in der Termin Einstellung]({{ img_url_mobile }}Dienstplanung/5_dienstplanung_termineinstellungen_dienstplanungsreiter_dark_1280x720.png#only-dark){.mobile-img}

In der Adressbuch Anwendung gibt es ebenso zwei kleine Änderungen. Zum einen gibt es im Modul Gruppen nun auch die Möglichkeit Gruppen für die Dienstplanung zu erstellen. Diese Gruppe kann nun einer Dienstplanungs-Rolle zugeordnet werden. Dies geschieht über den Reiter Dienstplanung. Mit dieser Funktion kann dann ein Mitarbeiter dieser Gruppe zugeordnet werden, und die Anwendung Dienstplanung weiß, welche Person was für eine Tätigkeit hat.

<!-- SCREENSHOT -->
![Abbildung: Terminarten in der Termin Einstellung]({{ img_url_desktop }}Dienstplanung/6_dienstplanung_gruppen_dienstplanungsreiter_light_1920x1020.png#only-light){.desktop-img}
![Abbildung: Terminarten in der Termin Einstellung]({{ img_url_desktop }}Dienstplanung/6_dienstplanung_gruppen_dienstplanungsreiter_dark_1920x1020.png#only-dark){.desktop-img}
![Abbildung: Terminarten in der Termin Einstellung]({{ img_url_mobile }}Dienstplanung/6_dienstplanung_gruppen_dienstplanungsreiter_light_1280x720.png#only-light){.mobile-img}
![Abbildung: Terminarten in der Termin Einstellung]({{ img_url_mobile }}Dienstplanung/6_dienstplanung_gruppen_dienstplanungsreiter_dark_1280x720.png#only-dark){.mobile-img}

Mitglieder dieser Gruppe zuordnen passiert, wie gewohnt, im ersten Reiter Gruppe unter dem Punkt Mitglieder.

Dies führt uns direkt zur zweiten Änderung. Im Modul Kontakte in den Einstellungen eines Kontaktes finden wir nun auch den Reiter Dienstplanung. Hier kann eingestellt werden, an welchen Tag dieser Kontakt verfügbar ist.

Kommen wir zurück zur Anwendung Dienstplanung.
Über die Filter im linken, oberen Bereich lassen sich die Termine feiner filtern. Dies ist besonders nützlich, wenn sehr viele Termine zur Auswahl stehen. So können Sie zum Beispiel nur Termine für einen bestimmten Standort oder Zeitraum anzeigen lassen.
Im oberen Bereich der Dienstplanung finden Sie die Benutzer, die eingeplant werden können. Damit ein Benutzer zu einer Rolle zugewiesen werden kann, muss dieser zunächst einer entsprechenden Gruppe zugeordnet werden. Die Gruppe wiederum muss mit einer entsprechenden Dienstplanungsrolle verknüpft sein. Diese lässt sich sich im Gruppen-Bearbeitungsdialog unter dem Reiter Dienstplanung auswählen.


## Die Terminansicht

Die Termine werden zeilenweise angezeigt. Am Anfang finden sich Name und Zeitpunkt des Termins, dann folgen spaltenweise die zu planenden Rollen.
Jede Rolle besitzt eine Box mit einem farbigen Rahmen sowie einem Anzahl-Feld. Das Anzahl-Feld gibt Auskunft über die benötigte Anzahl von Personen für diese Rolle. Der farbige Rahmen zeigt wiederum, ob die benötigte Anzahl erreicht oder überschritten ist.

* Grün: Die erforderliche Anzahl ist erreicht
* Rot: Die erforderliche Anzahl ist noch nicht erreicht
* Blau: die erforderliche Anzahl ist überschritten

Durch einen Klick auf einzelne Felder oder den Spaltenkopf einer Rolle werden die verfügbaren Benutzer gefiltert. Es werden nur jene Benutzer angezeigt, die den entsprechenden Feldern zugeordnet werden können.

<!-- SCREENSHOT -->
![Abbildung: Eine Unterfilterung nach Küstern]({{ img_url_desktop }}Dienstplanung/2_dienstplanung_filter_light_1920x1020.png#only-light){.desktop-img}
![Abbildung: Eine Unterfilterung nach Küstern]({{ img_url_desktop }}Dienstplanung/2_dienstplanung_filter_dark_1920x1020.png#only-dark){.desktop-img}
![Abbildung: Eine Unterfilterung nach Küstern]({{ img_url_mobile }}Dienstplanung/2_dienstplanung_filter_light_1280x720.png#only-light){.mobile-img}
![Abbildung: Eine Unterfilterung nach Küstern]({{ img_url_mobile }}Dienstplanung/2_dienstplanung_filter_dark_1280x720.png#only-dark){.mobile-img}


## Die Teilnehmeransicht

Im oberen Bereich befinden sich die verfügbaren Teilnehmer in Form von Tokens. Ein Token setzt sich wie folgt zusammen:

* Ein roter Kreis mit einer Anzahl. Dieser gibt an, wie oft der Teilnehmer bereits verplant wurde.
* Der Name des Teilnehmers.
* Die Wochentage, an denen dieser Teilnehmer verfügbar ist.
* Ein Herz-Symbol, dies ist optional und gibt an, dass dieser Teilnehmer Lieblingspartner hat.
* Ein oder mehrere Rollensymbole, für die dieser Teilnehmer verfügbar ist. Die Symbole finden sich auch entsprechend im Kopf der Terminansicht.

<!-- SCREENSHOT -->
![Abbildung: Verschiedene Teilnehmer Token]({{ img_url_desktop }}Dienstplanung/3_dienstplanung_token_light_1920x1020.png#only-light){.desktop-img}
![Abbildung: Verschiedene Teilnehmer Token]({{ img_url_desktop }}Dienstplanung/3_dienstplanung_token_dark_1920x1020.png#only-dark){.desktop-img}
![Abbildung: Verschiedene Teilnehmer Token]({{ img_url_mobile }}Dienstplanung/3_dienstplanung_token_light_1280x720.png#only-light){.mobile-img}
![Abbildung: Verschiedene Teilnehmer Token]({{ img_url_mobile }}Dienstplanung/3_dienstplanung_token_dark_1280x720.png#only-dark){.mobile-img}


## Einen Termin planen

Um einen Termin zu planen, führen Sie einfach eine Drag&Drop-Geste auf den Teilnehmer-Token aus, den Sie verplanen möchten. Sobald Sie den Token „halten“, werden alle Felder, die unzulässig sind, ausgegraut. Dies können auf der einen Seite Rollen sein, für die dieser Teilnehmer nicht zuständig ist, aber auch Termine, die außerhalb der Tage liegt, zu denen dieser Teilnehmer zur Verfügung steht.
Ziehen Sie nun den Token in das gewünschte Feld.


### Lieblingspartner

Ein Benutzer verfügt möglicherweise über Lieblingspartner. Dies wird durch ein Herzsymbol angezeigt. Wenn Sie den Token an diesem Herzsymbol greifen, werden nicht nur dieser Token, sondern auch die Token der Lieblingspartner gegriffen. Diese können dann gemeinsam geplant werden.
Ob und welche Lieblingspartner ein Teilnehmer hat, kann im Adressbuch-Eintrag des Teilnehmers eingestellt werden. Wählen Sie hierfür im Kontakt-Bearbeitungsdialog den Reiter Dienstplanung aus.


### Zeitliche Beschränkung

Teilnehmer sind möglicherweise nur an bestimmten Wochentagen verfügbar. Dies wird gegebenenfalls im Teilnehmer-Token angezeigt.
Ob und wann ein Teilnehmer verfügbar ist, kann im Adressbuch-Eintrag des Teilnehmers eingestellt werden. Wählen Sie hierfür im Kontakt-Bearbeitungsdialog den Reiter Dienstplanung aus.