# Zeiterfassung { data-ctx="/Timetracker" }

<!--Zeiterfassung-->
<!--ERP-->
<!--Zeitkonten-->
<!--Stundenzettel-->
Ein unabdingbarer Baustein im ERP-Bereich, den {{ branding.title }} ja ebenfalls in Teilen abdeckt, ist die Anwendung Zeiterfassung.

## Untermodule und Favoriten { data-ctx="/Timetracker/MainScreen/ModulPicker" }

Die Ansicht der linken Seite ist in diesem Programmteil eine etwas andere als in denen des klassischen Groupware-Bereichs, denn Zeiterfassung kennt z.B. keine Unterteilung in Datenbanken für "persönliche", "gemeinsame" oder Objekte "anderer Benutzer".

<!-- SCREENSHOT -->
![Abbildung: Module der Zeiterfassung]({{ img_url_desktop }}Zeiterfassung/1_zeiterfassung_module_light.png#only-light){.desktop-img}
![Abbildung: Module der Zeiterfassung]({{ img_url_desktop }}Zeiterfassung/1_zeiterfassung_module_dark.png#only-dark){.desktop-img}
![Abbildung: Module der Zeiterfassung]({{ img_url_mobile }}Zeiterfassung/1_zeiterfassung_module_light.png#only-light){.mobile-img}
![Abbildung: Module der Zeiterfassung]({{ img_url_mobile }}Zeiterfassung/1_zeiterfassung_module_dark.png#only-dark){.mobile-img}


Zeiterfassung hat zwei Untermodule: Stundenzettel und Zeitkonten. Die Stundenzettel sind immer eine Teilmenge eines Zeitkontos, d.h. Sie müssen zunächst Zeitkonten anlegen, um im zweiten Schritt Stundenzettel abspeichern zu können. Unter Module finden Sie diese beiden Programmteile zum Starten.

<a id="ctx:Timetracker.MainScreen.Timeaccount.FavoritesPicker"></a>
Favoriten sieht hier, je nach gewähltem Modul, unterschiedlich aus. Unter Stundenzettel finden Sie als vorangelegte Favoriten eine ganze Reihe (in der Standardvariante fünf) verschiedene Sortierkriterien, die sich alle auf Ihre eigene Person und verschiedene Zeitabschnitte beziehen. Unter dem Modul Zeitkonten werden Ihnen in der Standardvariante drei Ansichten angeboten, die entsprechend dem Abrechnungsstatus der Zeitkonten Abgerechnete Zeitkonten, Abzurechnende Zeitkonten und Nicht abgerechnete Zeitkonten heißen.

## Zeitkonten hinzufügen { data-ctx="/TimeTracker/EditDialog/TimeAccount" }
<a id="ctx:Timetracker.MainScreen.Timeaccount.ActionToolbar"></a>
Starten Sie das Modul durch Anklicken von Zeitkonten unter Module. Wir wollen ein neues Zeitkonto anlegen – gehen Sie dazu ins Bearbeitungsmenü und klicken Sie den Button Zeitkonto hinzufügen an.

<!-- SCREENSHOT -->
![Abbildung: Zeitkonto anlegen]({{ img_url_desktop }}Zeiterfassung/2_zeiterfassung_zeitkonto_neu_light.png#only-light){.desktop-img}
![Abbildung: Zeitkonto anlegen]({{ img_url_desktop }}Zeiterfassung/2_zeiterfassung_zeitkonto_neu_dark.png#only-dark){.desktop-img}
![Abbildung: Zeitkonto anlegen]({{ img_url_mobile }}Zeiterfassung/2_zeiterfassung_zeitkonto_neu_light.png#only-light){.mobile-img}
![Abbildung: Zeitkonto anlegen]({{ img_url_mobile }}Zeiterfassung/2_zeiterfassung_zeitkonto_neu_dark.png#only-dark){.mobile-img}

Unter dem Reiter Zeitkonto finden Sie in der ersten Zeile die Pflichtfelder Nummer und Titel. Nummer ist ein beliebiges Eingabefeld, in dem Sie sowohl Buchstaben als auch Zahlen speichern können. Vergewissern Sie sich, ob es für Zeitkonten-Bezeichnungen in Ihrem Unternehmen eine Regel gibt, und wenden Sie diese hier ggf. an. Titel kann ebenfalls ein beliebiger String sein. Alle weiteren Eingaben auf dieser Maske sind fakultativ.

Die drei Eingabefelder Einheit, Preis einer Einheit und Budget werden in {{ branding.title }} selbst nicht verarbeitet; sie dienen nur zur Übergabe an externe Fakturierungsprogramme.

Der Status eines Zeitkontos kann offen oder geschlossen sein, d.h. in diesem Zeitkonto können entweder noch Stundenzettel erfasst werden oder nicht mehr. Diese beiden möglichen Werte werden Ihnen entsprechend im Pulldown angeboten. Wenn ein Zeitkonto geschlossen wurde, wird es in der Tabelle ausgegraut angezeigt, und man kann keine Zeiten mehr auf dieses Konto buchen.

Im Feld Abgerechnet haben Sie die drei Werte noch nicht abgerechnet, abzurechnen und abgerechnet zur Auswahl. Diese Angaben dienen lediglich der Sortierung in die o.g. Favoriten-Ansichten.

Abgerechnet in dient der Angabe, in welcher Rechnung die Leistungen abgerechnet wurden, d.h. hier können Sie eine Rechnungsnummer eingeben.

Nach Überschreiten der Buchungsfrist können Anwender auf dieses Zeitkonto keine Stundenzettel mehr buchen. Ausnahme hiervon stellen Bediener dar, die Administratorrechte auf dieses Zeitkonto haben.

Abgerechnet am ist ein Datumsfeld und erlaubt Ihnen, das Datum der Abrechnung des betreffenden Zeitkontos zu setzen.

Im nächsten Feld können Sie dem Zeitkonto eine Kostenstelle zuweisen. Damit werden die hier erfassten Aufwände dieser Kostenstelle zugeordnet. Wie Kostenstellen angelegt werden, lesen Sie bitte im [Sales - Kostenstellen](ha_Sales.md/#kostenstellen) nach.

Mit dem letzten Feld auf dieser Maske weisen Sie dem Zeitkonto einen Verantwortlichen zu. Dabei können Sie auf den gesamten Kontaktpool Ihrer {{ branding.title }}-Installation zugreifen, d.h. es können auch externe Personen zu Verantwortlichen eines Zeitkontos gemacht werden, nicht nur {{ branding.title }}-Benutzer.

Der Checkbutton Stundenzettel sind abrechenbar ist lediglich ein Sortierkriterium: Beim Stundenzettel-Export können Sie damit alle Stundenzettel herausfiltern, die nicht abrechenbar sind. Die abrechenbaren hingegen können Sie exportieren und danach auf abgerechnet setzen.

Der zweite Reiter, Zugang, regelt die Zugangsberechtigungen für das anzulegende Zeitkonto:

<!-- SCREENSHOT -->
![Abbildung: Die Vergabe von Benutzerrechten für ein Zeitkonto]({{ img_url_desktop }}Zeiterfassung/3_zeiterfassung_zeitkonto_rechte_light.png#only-light){.desktop-img}
![Abbildung: Die Vergabe von Benutzerrechten für ein Zeitkonto]({{ img_url_desktop }}Zeiterfassung/3_zeiterfassung_zeitkonto_rechte_dark.png#only-dark){.desktop-img}
![Abbildung: Die Vergabe von Benutzerrechten für ein Zeitkonto]({{ img_url_mobile }}Zeiterfassung/3_zeiterfassung_zeitkonto_rechte_light.png#only-light){.mobile-img}
![Abbildung: Die Vergabe von Benutzerrechten für ein Zeitkonto]({{ img_url_mobile }}Zeiterfassung/3_zeiterfassung_zeitkonto_rechte_dark.png#only-dark){.mobile-img}

Standardmäßig ist die Rechtevergabe auf Zuweisung von Benutzergruppen eingestellt, was Sie an dem Text Suche nach Gruppen... im Pulldown-Eingabefeld sehen. Sie können diese Suche jedoch über das kleine Symbol (<img src="{{icon_url}}icon_group_full.svg" alt="drawing" width="16"/>) vor dem Eingabefeld ändern – dahinter versteckt sich ein Pulldown mit den Werten Benutzersuche und Gruppensuche, sodass Sie auch einzelne Benutzer mit spezifischen Berechtigungen zuweisen können.

Die Berechtigungen sind zu Beginn immer leer -- auch das ist der Stellung dieser Anwendung außerhalb der normalen Groupware-Funktionen geschuldet! Weisen Sie daher jeder Gruppe bzw. jedem Benutzer die spezifischen Berechtigungen über die Checkbuttons wie folgt zu:

* Eigene buchen – Die Berechtigung, zu diesem Zeitkonto eigene Stundenzettel hinzuzufügen.
* Alle sehen – Die Berechtigung, Stundenzettel anderer Benutzer dieses Zeitkontos zu sehen.
* Alle buchen – Die Berechtigung, zu diesem Zeitkonto Stundenzettel auch für andere Benutzer hinzuzufügen.
* Abrechenbar setzen – Die Berechtigung, dieses Zeitkonto abzurechnen und zu schließen.
* Export -- Die Berechtigung, Stundenzettel dieses Zeitkontos an andere Programme zu übergeben.
* Admin – Alle Berechtigungen (Darf Alles)

Damit haben wir alle Menüpunkte und Einstellungen für Zeitkonten besprochen; legen Sie bitte testweise ein Zeitkonto an, wenn es nicht schon welche gibt! Wir benötigen es im nächsten Unterabschnitt, wenn wir die Stundenzettel besprechen.

## Die restlichen Menüpunkte für Zeitkonten { data-ctx="/Timetracker/MainScreen/Timeaccount/ActionToolbar" }
Mit Zeitkonto bearbeiten erhalten Sie nach Markierung eines vorhandenen Zeitkontos dieselbe Bearbeitungsmaske wie bei Zeitkonto hinzufügen.

Zeitkonto löschen löscht das ausgewählte Zeitkonto, nach einer Sicherheitsabfrage.[^1]

[^1]:
    Achtung: Sie können in der derzeitigen Version von {{ branding.title }} ein Zeitkonto löschen, obwohl diesem gültige Stundenzettel zugeordnet sind. Diese hängen dann unzugeordnet im System. Achten Sie daher auf vollständige Abrechnung, bevor Sie ein Zeitkonto löschen!

Drucke Seite öffnet Ihren systemeigenen Druckerdialog und erzeugt standardmäßig eine DIN-A4-Hochformatseite mit einer Tabelle und den auf dem Bildschirm ausgewählten Feldern. Dabei passt sich die Schriftgröße dem Platzangebot dynamisch an.

Exportiere Zeitkonto ist zwar ein Pulldown; momentan ist aber nur der Export in das Open-/Libre-Office-Format (ODS) implementiert.

Einträge importieren - Die Importfunktion für Inventargegenstände funktioniert analog derjenigen im Adressbuch, d.h. Sie sollten sich als ersten Schritt die CSV-Beispieldatei ansehen, die Sie im Bearbeitungsfenster Datei und Format wählen unter dem Link Beispieldatei herunterladen finden (vgl. [Adressverwaltung - Kontakte importieren](ba_Adressbuch.md/#kontakte-importieren)).

## Stundenzettel hinzufügen { data-ctx="/Timetracker/EditDialog/Timesheet" }
<a id="ctx:Timetracker.MainScreen.Timesheet.ActionToolbar"></a>
Ein Stundenzettel ist, in Anlehnung an diesen Begriff aus der "alten" Produktionswelt, das Objekt zur Erfassung eines einzelnen Arbeitsaufwandes innerhalb eines bestimmten Zeitkontos:

<!-- SCREENSHOT -->
![Abbildung: Einen Stundenzettel zur Zeiterfassung erstellen]({{ img_url_desktop }}Zeiterfassung/4_zeiterfassung_stundenzettel_neu_light.png#only-light){.desktop-img}
![Abbildung: Einen Stundenzettel zur Zeiterfassung erstellen]({{ img_url_desktop }}Zeiterfassung/4_zeiterfassung_stundenzettel_neu_dark.png#only-dark){.desktop-img}
![Abbildung: Einen Stundenzettel zur Zeiterfassung erstellen]({{ img_url_mobile }}Zeiterfassung/4_zeiterfassung_stundenzettel_neu_light.png#only-light){.mobile-img}
![Abbildung: Einen Stundenzettel zur Zeiterfassung erstellen]({{ img_url_mobile }}Zeiterfassung/4_zeiterfassung_stundenzettel_neu_dark.png#only-dark){.mobile-img}

Daher sehen Sie, nach Öffnung der Bearbeitungsmaske für Stundenzettel (Module → Stundenzettel → Stundenzettel hinzufügen) auch oben als erste Eingabe ein Pulldown Zeitkonto zur Auswahl eines solchen. Wenn Sie im vorhergehenden Abschnitt ein Zeitkonto zum Test angelegt haben, wählen Sie es jetzt aus, denn die Angabe eines Zeitkontos ist natürlich Pflicht!

!!! note "Anmerkung"
    Neben den üblichen Schaltern zum Blättern in, ggf. vielen, Zeitkonten finden Sie hier rechts von diesen Schaltern auch noch einen Button Geschlossene anzeigen, mit denen Sie auch die bereits geschlossenen Zeitkonten in die Auswahl einbeziehen können. Die Erfassung eines Stundenzettels auf ein solches Konto ist nur noch Administratoren möglich, normale Anwender sehen diese Konten nicht.

Gehen wir weiter. In der nächsten Zeile finden Sie Zeitangaben: Zunächst Dauer, in dem 30 Minuten als Standard eingetragen sind. Mit den beiden Pfeilen "rauf" und "runter" können Sie diese Zeit in 15-Minuten-Intervallen ändern.

Rechts daneben Datum für die Eingabe des Tages, an welchem die Leistung erfolgte. Und schließlich Start für das Setzen der Startzeit der Leistung. Beide Eingaben sind fakultativ.

Beschreibung ist hier ein Pflichtfeld. Die Abrechnung einer Zeiteinheit bedingt zwangsläufig die Bestimmung des Arbeitsgegenstandes – das legen Verordnungen zur Rechnungsstellung gesetzlich fest.

In den Bereich Accounting/ Buchhaltung kann definieren wie viel % eines Stundenzettels abrechenbar ist. Dies ist z.B. bei Projektarbeiten von Relevanz. Gehen wir einmal die einzelnen Funktionen durch.
Abrechenbar setzt der Mitarbeiter, wenn er den Stundenzettel beim Kunden abgerechnet werden kann. Faktor beschreibt wie viel % der oben angegebenen Zeit abrechenbar sind. Der Faktor 1 steht hier für 100%. Egal ob man den Faktor oder Abrechenbare Zeit definiert, das jeweils andere wird von {{ branding.title }} automatisch ausgerechnet.
Ist der Chuckbutten deaktiviert, wird der Faktor auf 0 gesetzt und die restlichen Optionen ausgegraut. Hier handelt es sich dann um eine reine Zeiterfassung.

Abgerechnet dient hier wieder der Angabe einer Rechnungsnummer, in welcher der Stundenzettel abgerechnet wurde.

Ist ein Stundenzettel bereits abgerechnet, wird das Checkbutton menu[Arbeitszeit ist Abgerechnet aktiviert.

Der Checkbutton Klärungsbedarf. Dieser kann vom Mitarbeiter gesetzt werden, wenn es zu diesem Stundenzettel Unklarheiten gibt. Die Buchhaltung kann nur vor dem Abrechnen nach diesen Stundenzetteln Filtern.

Schließlich können Sie per Pulldown mit Auswahlmöglichkeit aus der Reihe der in {{ branding.title }} angelegten Benutzer einen solchen für diesen Stundenzettel zuweisen. Für die Faktura ist das nicht nötig, deshalb ist die Eingabe hier fakultativ. Beachten Sie aber innerbetriebliche Regelungen zum Controlling.

## Die restlichen Menüpunkte für Stundenzettel { data-ctx="/Timetracker/MainScreen/Timesheet/ActionToolbar" }
Mit Stundenzettel bearbeiten erhalten Sie nach Markierung eines vorhandenen Stundenzettels dieselbe Bearbeitungsmaske wie bei Stundenzettel hinzufügen.

 Stundenzettel löschen löscht den ausgewählten Stundenzettel, nach einer Sicherheitsabfrage.

Drucke Seite öffnet Ihren systemeigenen Druckerdialog und erzeugt standardmäßig eine DIN-A4-Hochformatseite mit einer Tabelle und den auf dem Bildschirm ausgewählten Feldern.

### Stundenzettel exportieren

Im rechten Teil des Bearbeitungsmenüs finden Sie ein Pulldown zum Export von Stundenzetteln aus {{ branding.title }}, Stundenzettel exportieren, mit den folgenden drei Möglichkeiten:

* Als ODS exportieren - diese Funktion erzeugt eine Tabelle im Dateiformat von Open-/LibreOffice; dabei entspricht die Zeilen- und Spaltenanordnung derjenigen auf der Bildschirmtabelle.

* Als CSV exportieren erzeugt eine CSV-Textdatei ("komma-getrennte Werte"). Diese Funktion können Sie z.B. auch benutzen, wenn Sie die Tabelle in MS Excel neuerer Versionen einlesen wollen. Zeilen- und Spaltenanordnung wie oben erklärt.

* Als ... exportieren - mit diesem Menüpunkt öffnet sich ein Fenster mit einem weiteren Pulldown, das mehrere Möglichkeiten vorsieht. In der Standardausführung von {{ branding.title }} finden Sie hier allerdings lediglich das ODS-Format. Der Menüpunkt ist auch zum Ausbau für kundenspezifische Sonderformate vorgesehen.
