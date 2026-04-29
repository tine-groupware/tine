# HumanResources
<!--Personalkonten-->
<!--Mitarbeiter-->

Die Anwendung HumanResources gehört nicht zu den Kernfunktionalitäten einer Groupware, ist auf Kundenwunsch entstanden und entspricht keinem vollständigen Personalmanagementsystem. Im Rahmen einer Groupwarelösung steht jedoch bereits ein umfangreiches Funktionspaket zur Personalverwaltung und Zeitwirtschaft zur Verfügung.

## Einleitung { data-ctx="/HumanResources" }
Die Anwendung Human Resources dient der Personalabteilung zur Verwaltung relevanter Mitarbeiterinformationen. Da hierzu auch vertrauliche Daten gehören, sollten Sie die Zugriffsrechte auf diesen Programmteil sehr genau überlegen.

Die Anwendung besteht aus fünf Modulen: Mitarbeiter, die zugehörigen Personalkonten, Tägliche Arbeitszeitberichte, Monatliche Arbeitszeitberichte und Abwesenheitsplanung.

<a id="ctx:HumanResources.MainScreen.ModulPicker"></a>
Starten Sie die Anwendung über den Reiter {{ branding.title }}, indem Sie dort HumanResources anklicken.

<!-- SCREENSHOT -->
![Abbildung: Module unter HumanResources]({{ img_url_desktop }}HumanResources/1_humanresources_module_light.png#only-light){.desktop-img}
![Abbildung: Module unter HumanResources]({{ img_url_desktop }}HumanResources/1_humanresources_module_dark.png#only-dark){.desktop-img}
![Abbildung: Module unter HumanResources]({{ img_url_mobile }}HumanResources/1_humanresources_module_light.png#only-light){.mobile-img}
![Abbildung: Module unter HumanResources]({{ img_url_mobile }}HumanResources/1_humanresources_module_dark.png#only-dark){.mobile-img}

<a id="ctx:HumanResources.MainScreen.Employee.FavoritesPicker"></a>
Die vorgefertigten Favoriten-Ansichten wechseln je nach Modul. So zeigen die Favoriten Alle Angestellten und Zur Zeit angestellt einerseits alle im Unternehmen bisher beschäftigten Mitarbeiter (inklusive der bereits ausgeschiedenen) und andererseits die derzeit beschäftigten Mitarbeiter (mit aktuell laufendem Arbeitsvertrag) an. Alle Konten zeigt alle Personalkonten an.

## Stammdaten

In den Stammdaten werden Basisdaten wie Lohnarten, Abwesenheitsarten und Arbeitszeitschemata verwaltet, die in der Anwendung HumanResources benötigt werden.
Im jeweiligen Anwendungsbereich verlinken wir auf die zugehörigen Stammdaten, wenn diese relevant sind.

### Lohnarten

Lohnarten werden in der Lohn- und Gehaltsabrechnung zur Steuerung abzurechnender Vorgänge verwendet. Dabei sind abrechnungsrelevante Eigenschaften für Lohn- und Gehaltszahlungen pro Lohnart definiert (Steuer-, Sozialversicherungs- und Auswertungsmerkmale).
Einige gängige und systemrelevante Lohnarten werden bereits bei Installation zur Verfügung gestellt. Sie haben hier die Möglichkeit, speziell in Abkürzungen und der Bezeichnung in Name, die Lohnarten parallel zu einem bestehenden Lohnbuchhaltungssystem zu führen. Grundsätzlich sind Sie jedoch frei bei der weiteren Anlage und Benennung der Lohnarten in {{ branding.title }}.

Die Angabe in System steuert, ob es sich dabei um eine systemnotwendige Lohnart handelt (notwendig für die automatische Berechnung der abrechnungsrelevanten Mitarbeiterdaten). Der Faktor steuert mit welchem Prozenzsatz die zu berechnenden Daten bewertet werden. Zusatzlohn enthält die Information, ob es sich um einen zusätzlichen Lohn- bzw. Gehaltsanteil handelt.


<!-- SCREENSHOT -->
![Abbildung: Lohnarten]({{ img_url_desktop }}HumanResources/2_humanresources_stammdaten_lohnarten_light.png#only-light){.desktop-img}
![Abbildung: Lohnarten]({{ img_url_desktop }}HumanResources/2_humanresources_stammdaten_lohnarten_dark.png#only-dark){.desktop-img}
![Abbildung: Lohnarten]({{ img_url_mobile }}HumanResources/2_humanresources_stammdaten_lohnarten_light.png#only-light){.mobile-img}
![Abbildung: Lohnarten]({{ img_url_mobile }}HumanResources/2_humanresources_stammdaten_lohnarten_dark.png#only-dark){.mobile-img}

### Abwesenheitsarten

Für Ihre Abwesenheitsverwaltung benötigen Sie unterschiedliche Abwesenheitsarten, wie z.B. Urlaub und Krankheit. Weisen Sie die Lohnart zu und steuern Sie damit die Abrechnung in Abhängigkeit der Abwesenheitsart. Somit kann z.B. zwischen bezahlten und unbezahltem Urlaub unterschieden werden.

Mit Hilfe der Abkürzung steuern Sie die Eingaben in der Schnellerfassungmatrix zu An- und Abwesenheitszeiten (siehe [Abwesenheitsplanung](ka_HumanResources.md/#abwesenheitsplanung)) und können letztendlich für einen schnellen Überblick in der Matrix die Abwesenheitsgründe bereits am Kürzel erkennen.

Weiterhin benötigen Sie die Angabe in System zur Steuerung, ob es sich um eine systemnotwendige Abwesenheitsart handelt (notwendig für die automatische Berechnung der An-/Abwesenheitszeiten der Mitarbeiter).

Die Einstellung Buchung erlauben steuert, ob diese Abwesenheitsart bei der Erfassung (Buchung) von Abwesenheitszeiten (über Terminal oder Anwendung) durch einen Mitarbeiter erlaubt ist.

Mit Planung erlauben legen Sie fest, ob diese Abwesenheitsart in der Matrix Abwesenheitsplanung zur Erfassung freigegeben ist.

Über Abwesenheits-Zeiterfassung aktivieren nehmen Sie Einfluss auf die Erfassung von An-und Abwesenheitszeiten und regeln, ob nach Eingabe einer Startzeit, die Eingabe einer Endzeit aktiv erforderlich ist. So kann durch die Aktivierung von Abwesenheits-Zeiterfassung aktivieren nach beispielsweise einem Auscheck zur Pause ein Wiedereincheck nach Beendigung erforderlich sein.
Wählen Sie ein Zeitkonto aus (über das Pulldown Menü), wenn Sie alle erfassten An-/Abwesenheitszeiten mit dieser Abwesenheitsart auf ein spezielles Arbeitszeitkonto wünschen. Die Anlage von Zeitkonten finden Sie im [Zeiterfassung](la_Zeiterfassung.md) und dort speziell im [Zeiterfassung - Zeitkonten hinzufügen](la_Zeiterfassung.md/#zeitkonten-hinzufugen).

!!! note "Anmerkung"
    Um An-und Abwesenheitszeiten im HumanRessources zu erfassen und zu buchen wird ein Standard Arbeitszeitkonto in der Konfiguration zur HumanRessource Anwendung hinterlegt. Sollte dieses in der Konfiguration nicht zugeordnet sein, generiert das System ein Standard Arbeitszeitkonto und nimmt die entsprechenden Verweise automatisch vor.

<!-- SCREENSHOT -->
![Abbildung: Abwesenheitsarten]({{ img_url_desktop }}HumanResources/3_humanresources_stammdaten_abwesenheitsarten_light.png#only-light){.desktop-img}
![Abbildung: Abwesenheitsarten]({{ img_url_desktop }}HumanResources/3_humanresources_stammdaten_abwesenheitsarten_dark.png#only-dark){.desktop-img}
![Abbildung: Abwesenheitsarten]({{ img_url_mobile }}HumanResources/3_humanresources_stammdaten_abwesenheitsarten_light.png#only-light){.mobile-img}
![Abbildung: Abwesenheitsarten]({{ img_url_mobile }}HumanResources/3_humanresources_stammdaten_abwesenheitsarten_dark.png#only-dark){.mobile-img}

### Arbeitszeitschemata

Mit Hilfe von Arbeitszeitschemata werden unterschiedliche Arbeitszeitmodelle definiert. Je nach Branche und Unternehmen entspricht der Inhalt und Aufbau den am häufigsten vorkommenden und vertraglich vereinbarten Arbeitszeitregelungen.

Diese Vorlagen werden in 3 Typen unterschieden:

- Vorlage: Bei Zuordnung dieses Arbeitszeitschema zu einem Mitarbeitervertrag wird das Schema kopiert und bei Speicherung wird für diesen Einzelvertrag ein eigenes Schema vom Typ Individuell gespeichert.
- Gemeinsam: Dieses Arbeitszeitschema kann bei diversen Arbeitsverträgen genutzt werden. Änderungen am Schema wirken sich entsprechend bei allen Mitarbeiterverträgen mit diesem Schema aus.
- Individuell: Bei diesem Typ handelt es sich um ein automatisch vom System angelegtes Schema (siehe Typ Vorlage).


<!-- SCREENSHOT -->
![Abbildung: Arbeitszeitschemata]({{ img_url_desktop }}HumanResources/4_humanresources_stammdaten_arbeitsschemata_liste_light.png#only-light){.desktop-img}
![Abbildung: Arbeitszeitschemata]({{ img_url_desktop }}HumanResources/4_humanresources_stammdaten_arbeitsschemata_liste_dark.png#only-dark){.desktop-img}
![Abbildung: Arbeitszeitschemata]({{ img_url_mobile }}HumanResources/4_humanresources_stammdaten_arbeitsschemata_liste_light.png#only-light){.mobile-img}
![Abbildung: Arbeitszeitschemata]({{ img_url_mobile }}HumanResources/4_humanresources_stammdaten_arbeitsschemata_liste_dark.png#only-dark){.mobile-img}

Klicken Sie im Bearbeitungsmenü links auf Arbeitszeitschema hinzufügen und erhalten Sie den Bearbeiten Dialog zur Erfassung eines Arbeitszeitschema.

<!-- SCREENSHOT -->
![Abbildung: Arbeitszeitschemata hinzufügen]({{ img_url_desktop }}HumanResources/5_humanresources_stammdaten_arbeitsschemata_dialog_light.png#only-light){.desktop-img}
![Abbildung: Arbeitszeitschemata hinzufügen]({{ img_url_desktop }}HumanResources/5_humanresources_stammdaten_arbeitsschemata_dialog_dark.png#only-dark){.desktop-img}
![Abbildung: Arbeitszeitschemata hinzufügen]({{ img_url_mobile }}HumanResources/5_humanresources_stammdaten_arbeitsschemata_dialog_light.png#only-light){.mobile-img}
![Abbildung: Arbeitszeitschemata hinzufügen]({{ img_url_mobile }}HumanResources/5_humanresources_stammdaten_arbeitsschemata_dialog_dark.png#only-dark){.mobile-img}

Wählen Sie den gewünschten Typ (Vorlage oder Gemeinsam) wie zuvor beschrieben und vergeben einen passenden Titel.
In den Feldern der einzelnen Wochentage (Mo. bis So.) legen Sie jeweils die Anzahl Stunden/Minuten für dieses Arbeitsprofil fest. Dabei wird die Anzahl Arbeitsstunden pro Woche automatisch kalkuliert.
Im Block Arbeitszeit Regeln wird das Regelwerk hinterlegt, wenn spezielle Anforderungen an die Berechnung von Arbeitszeiträumen und/oder festen Pausezeiten erfolgen soll. Klicken Sie dazu auf das Pulldown Typ und wählen Sie zwischen Break Time und Arbeitszeitlimitierung aus. Es öffnet sich jeweils ein weiteres Fenster.
Legen Sie bei Typ Break Time die Arbeitszeit in Stunden/Minuten fest, nach welcher automatisch eine Pause abgezogen werden soll. Die Dauer der Pause geben Sie in Pausenzeit ein und bestätigen mit OK.

Legen Sie bei Typ Arbeitszeitlimitierung den täglich anzurechnenden Arbeitszeitrahmen in Form von Startzeit und End time fest und bestätigen mit OK. Nur Zeiten innerhalb dieses Zeitbereiches werden dann als Arbeitszeit angerechnet und ausgewertet.

Die Übertragung der Regel erfolgt jeweils unmittelbar im Feld Konfiguration. Nach Fertigstellung aller Angaben speichern Sie das Schema durch Bestätigung auf Button OK.

## Mitarbeiter { data-ctx="/HumanResources/MainScreen/Employee" }
Wenn Sie links unter Module auf Mitarbeiter klicken, erhalten Sie rechts in der Tabelle zeilenweise die angelegten Mitarbeiter. Ein Klick auf das Tabellenkopf-Symbol ganz rechts außen zeigt Ihnen, dass die Tabelle aus Platzgründen nur etwa die Hälfte aller möglichen Angaben anzeigt.

<!-- SCREENSHOT -->
![Abbildung: Mitarbeiter Liste]({{ img_url_desktop }}HumanResources/1_humanresources_module_light.png#only-light){.desktop-img}
![Abbildung: Mitarbeiter Liste]({{ img_url_desktop }}HumanResources/1_humanresources_module_dark.png#only-dark){.desktop-img}
![Abbildung: Mitarbeiter Liste]({{ img_url_mobile }}HumanResources/1_humanresources_module_light.png#only-light){.mobile-img}
![Abbildung: Mitarbeiter Liste]({{ img_url_mobile }}HumanResources/1_humanresources_module_dark.png#only-dark){.mobile-img}

Schauen wir uns diese in ihrer Gesamtheit daher jetzt über die Bearbeitungsmaske näher an.

### Mitarbeiter hinzufügen { data-ctx="/HumanResources/EditDialog/Employee" }
Klicken Sie im Bearbeitungsmenü links außen den Button Mitarbeiter hinzufügen an.

<!-- SCREENSHOT -->
![Abbildung: Anlegen eines neuen Mitarbeiters]({{ img_url_desktop }}HumanResources/6_humanresources_mitarbeiter_dialog_light.png#only-light){.desktop-img}
![Abbildung: Anlegen eines neuen Mitarbeiters]({{ img_url_desktop }}HumanResources/6_humanresources_mitarbeiter_dialog_dark.png#only-dark){.desktop-img}
![Abbildung: Anlegen eines neuen Mitarbeiters]({{ img_url_mobile }}HumanResources/6_humanresources_mitarbeiter_dialog_light.png#only-light){.mobile-img}
![Abbildung: Anlegen eines neuen Mitarbeiters]({{ img_url_mobile }}HumanResources/6_humanresources_mitarbeiter_dialog_dark.png#only-dark){.mobile-img}

Zu beachten sind hier die beiden Reiter Kostenstellen und Verträge – dazu später mehr. Die Personalnummer (Pers.-Nr.) und das Benutzerkonto sind Pflichtfelder. Vergeben Sie jetzt eine Personalnummer entsprechend der in Ihrem Unternehmen gültigen Vorschriften und klicken Sie danach das Pulldown im Feld Benutzerkonto an. Es werden Ihnen die in {{ branding.title }} angelegten Benutzer angeboten. Wählen Sie testweise einen Beliebigen aus. Beachten Sie jetzt rechts neben diesem Feld den kleinen Button mit dem Zauberstab. Wenn Sie ihn anklicken, werden Name, Vorname und weitere relevante Daten des Benutzers im Mitarbeiterdatensatz übernommen.

<!-- SCREENSHOT -->
![Abbildung: Mit dem Zauberstab Daten übernehmen]({{ img_url_desktop }}HumanResources/7_humanresources_mitarbeiter_kontaktdaten_zauberstab_light.png#only-light){.desktop-img}
![Abbildung: Mit dem Zauberstab Daten übernehmen]({{ img_url_desktop }}HumanResources/7_humanresources_mitarbeiter_kontaktdaten_zauberstab_dark.png#only-dark){.desktop-img}
![Abbildung: Mit dem Zauberstab Daten übernehmen]({{ img_url_mobile }}HumanResources/7_humanresources_mitarbeiter_kontaktdaten_zauberstab_light.png#only-light){.mobile-img}
![Abbildung: Mit dem Zauberstab Daten übernehmen]({{ img_url_mobile }}HumanResources/7_humanresources_mitarbeiter_kontaktdaten_zauberstab_dark.png#only-dark){.mobile-img}

Ergänzen Sie nun über das Pulldown das Feld Anrede sowie gegebenenfalls im nächsten Feld einen Titel. Im Block Persönliche Informationen können Sie unter Berücksichtigung des Datenschutzes nützliche Informationen hinterlegen.

Im Block Interne Informationen können Sie als Vorgesetzten nur Nutzer aus dem Kreis der bereits in dieser Anwendung angelegten Mitarbeiter auswählen. Die Abteilung wählen Sie ebenfalls per Pulldown aus (zu Abteilungen vgl. [Sales - Abteilungen](ha_Sales.md/#abteilungen)).

Krankenkasse, Beruf und Position sind freie Textfelder und optional. Ein Pflichtfeld hingegen ist Angestellt von, wo Sie das Datum des Beschäftigungsbeginns hinterlegen. Sie können es ebenso wie Angestellt bis komfortabel über die Kalenderfunktion füllen.

Im Block Bankverbindung sind alle Eingaben fakultativ.

### Kostenstellen

Klicken Sie nun den Reiter Kostenstellen an.

<!-- SCREENSHOT -->
![Abbildung: Kostenstellen]({{ img_url_desktop }}HumanResources/8_humanresources_mitarbeiter_kostenstellen_light.png#only-light){.desktop-img}
![Abbildung: Kostenstellen]({{ img_url_desktop }}HumanResources/8_humanresources_mitarbeiter_kostenstellen_dark.png#only-dark){.desktop-img}
![Abbildung: Kostenstellen]({{ img_url_mobile }}HumanResources/8_humanresources_mitarbeiter_kostenstellen_light.png#only-light){.mobile-img}
![Abbildung: Kostenstellen]({{ img_url_mobile }}HumanResources/8_humanresources_mitarbeiter_kostenstellen_dark.png#only-dark){.mobile-img}

Zur innerbetrieblichen Kosten- und Leistungsverrechnung kann ein Mitarbeiter einer Kostenstelle zugewiesen werden. Die hier erfassten, gepflegten und gehaltsmäßig relevanten Daten aus {{ branding.title }} können über eine individuell zu programmierende Schnittstelle an die Buchhaltung übergeben werden und stehen entsprechend dem Controlling zur Verfügung. Somit ist gewährleistet, dass die Mitarbeiterkosten auf die gewünschte Kostenstelle laufen.

Unter dem Reiter Kostenstellen finden Sie zwei Eingabefelder: Einmal die Kostenstelle selbst als Pulldown und zusätzlich das Anfangsdatum, ab dem die Mitarbeiterkosten einer bestimmten Kostenstelle zugeordnet werden sollen.

!!! note "Anmerkung"
    Kostenstellen sind letztendlich unternehmensweite interne Konten die dem interbetrieblichen Finanz- und Kostencontrolling dienen.
    Die Definition und Festlegung der Kostenstellenstruktur unterliegt keiner Vorgabe und ist von Branche zu Branche bzw. von Unternehmen zu Unternehmen individuell. Erkundigen Sie sich bitte nach der für Ihr Unternehmen gültigen innerbetrieblichen Nomenklatur.

<!--Arbeitsverträge-->
### Verträge { data-ctx="/HumanResources/EditDialog/Employee/Contract" }
Arbeitsverträge legen Sie unter dem Reiter Verträge an. Klicken Sie im unteren Bereich des Tabellenfensters auf Vertrag hinzufügen.

<a id="ctx:HumanResources.EditDialog.Contract"></a>

<!-- SCREENSHOT -->
![Abbildung: Mitarbeiter einen Vertrag zuweisen]({{ img_url_desktop }}HumanResources/10_humanresources_mitarbeiter_vertragsdialog_light.png#only-light){.desktop-img}
![Abbildung: Mitarbeiter einen Vertrag zuweisen]({{ img_url_desktop }}HumanResources/10_humanresources_mitarbeiter_vertragsdialog_dark.png#only-dark){.desktop-img}
![Abbildung: Mitarbeiter einen Vertrag zuweisen]({{ img_url_mobile }}HumanResources/10_humanresources_mitarbeiter_vertragsdialog_light.png#only-light){.mobile-img}
![Abbildung: Mitarbeiter einen Vertrag zuweisen]({{ img_url_mobile }}HumanResources/10_humanresources_mitarbeiter_vertragsdialog_dark.png#only-dark){.mobile-img}

Anfang und Ende sind Datumsfelder mit Anfang als Pflichtfeld. Ebenfalls eine Pflichteingabe ist das Pulldown Feiertagskalender. Sollte es in Ihrem Unternehmen einen speziellen Feiertagskalender geben, weisen Sie diesen hier dem Mitarbeiter zu.
Wahrscheinlich handelt es sich bei einem vorhandenen Feiertagskalender um einen gemeinsamen Kalender in {{ branding.title }}. Ein vom System automatisch angelegter Kalender enthält keine Feiertage. Die Auswahl erfolgt über Andere Kalender wählen. Beachten Sie, dass im darauf folgenden Menü Bitte Kalender auswählen gemeinsame oder Kalender anderer Benutzer nur dann zur Auswahl stehen, wenn Sie über entsprechende Leserechte verfügen (vgl. dazu [Administration - Container](oa_Administration.md/#container)). In [Administration - HumanResources](oa_Administration.md/#humanresources) erfahren Sie auch, wie Sie den Feiertagskalender zu einer Standardeinstellung für alle Mitarbeiter machen.

Urlaubstage eines Kalenderjahrs ist ein numerisches Eingabefeld, in dem Sie den vertraglich vereinbarten Jahresurlaub in Tagen.

Die Zeile Arbeitszeit enthält unter dem Pulldown Arbeitszeit Schema drei in Deutschland übliche Arbeitszeitmodelle. Wenn Sie eine Vorlage ausgewählt haben, sehen Sie in dem Feld rechts daneben (Arbeitsstunden pro Woche) die entsprechende Wochenarbeitszeit. Die darunter liegende Zeile mit der Verteilung der Wochenarbeitsstunden auf die einzelnen Arbeitstage wird entsprechend Ihrer ausgewählten Vorlage automatisch eingestellt. Beim 40-Stunden-Modell bedeutet dies eine tägliche Arbeitszeit von 8 Stunden, von Montag bis Freitag. Natürlich können Sie alle diese Standardeinstellungen individuell anpassen. Weitere Informationen zur Anlage und den Inhalten von Arbeitszeitschemata entnehmen Sie [Stammdaten - Arbeitszeitschemata](ma_Stammdaten.md/#arbeitszeitschemata).

Haben Sie einen Arbeitsvertrag angelegt, klicken Sie in der Übersichtstabelle rechts außen auf das Tabellenkopf-Symbol und stellen fest, dass die vier standardmäßig nicht eingeblendeten Tabellenfelder nur Informationen zum Bearbeitungsverlauf enthalten. Die wesentlichen Punkte des Arbeitsvertrages sind also in der Standard-Tabellenansicht enthalten und Sie können diese Einstellung unverändert lassen.

<!-- SCREENSHOT -->
![Abbildung: Liste Mitarbeiter Verträge]({{ img_url_desktop }}HumanResources/9_humanresources_mitarbeiter_vertraege_light.png#only-light){.desktop-img}
![Abbildung: Liste Mitarbeiter Verträge]({{ img_url_desktop }}HumanResources/9_humanresources_mitarbeiter_vertraege_dark.png#only-dark){.desktop-img}
![Abbildung: Liste Mitarbeiter Verträge]({{ img_url_mobile }}HumanResources/9_humanresources_mitarbeiter_vertraege_light.png#only-light){.mobile-img}
![Abbildung: Liste Mitarbeiter Verträge]({{ img_url_mobile }}HumanResources/9_humanresources_mitarbeiter_vertraege_dark.png#only-dark){.mobile-img}

Markieren Sie nun als Tabellenzeile den eben eingegebenen oder einen beliebigen anderen Arbeitsvertrag. Der Button Vertrag löschen unterhalb des Tabellenfensters ist jetzt nicht mehr ausgegraut  – damit können Sie den Arbeitsvertrag löschen. Das Abspeichern mindestens eines Arbeitsvertrages für einen Arbeitnehmer führt ebenso dazu, dass die beiden Reiter Urlaub und Krankheit nicht mehr ausgegraut sind.

<!--Urlaubstage-->
### Urlaub – Krankheit { data-ctx="/HumanResources/EditDialog/Employee/FreeTime" }
Unter dem Reiter Urlaub finden Sie in der Tabelle die Urlaubstage des Arbeitnehmers.

Die erste Tabellenspalte entspricht dem Benutzerkonto, d.h. Sie sehen das Kalenderjahr, dem die Urlaubstage zugeordnet sind. Die anderen Tabellenspalten schauen wir uns jetzt in der Bearbeitungsmaske an. Klicken Sie dazu unten den Button Urlaubstage hinzufügen.

<!-- SCREENSHOT -->
![Abbildung: Urlaubstage hinzufügen]({{ img_url_desktop }}HumanResources/12_humanresources_mitarbeiter_urlaub_light.png#only-light){.desktop-img}
![Abbildung: Urlaubstage hinzufügen]({{ img_url_desktop }}HumanResources/12_humanresources_mitarbeiter_urlaub_dark.png#only-dark){.desktop-img}
![Abbildung: Urlaubstage hinzufügen]({{ img_url_mobile }}HumanResources/12_humanresources_mitarbeiter_urlaub_light.png#only-light){.mobile-img}
![Abbildung: Urlaubstage hinzufügen]({{ img_url_mobile }}HumanResources/12_humanresources_mitarbeiter_urlaub_dark.png#only-dark){.mobile-img}

<a id="ctx:HumanResources.EditDialog.FreeTime"></a>
Über das Pulldown Status legen Sie zunächst fest, ob es sich um einen beantragten, in Bearbeitung befindlichen, bereits angenommenen oder abgewiesenen Urlaubsantrag handelt. Standardmäßig steht der Status auf Angenommen. Beim Abspeichern würden die Urlaubstage dann vom Urlaubskonto des Arbeitnehmers abgebucht. Belassen Sie die Einstellung jetzt auf Angenommen und schauen Sie sich das nächste Pulldown, Personalkonto, an. Per Standard steht dieses Feld auf dem laufenden Jahr; klicken Sie das Pulldown, sehen Sie, dass Ihnen das laufende und mindestens noch das folgende Jahr angeboten werden. Diese beiden Personalkonten, auf die wir weiter unten noch zu sprechen kommen, hat das System beim Abspeichern eines Mitarbeiters automatisch angelegt. Belassen Sie jetzt die Standardeinstellung, d.h. das laufende Jahr. Im nächsten Feld, Übrig, werden Ihnen zur Kontrolle die restlichen Urlaubstage des Arbeitnehmers angezeigt. Dieses Feld ist eine automatisch erzeugte Differenz aus dem (ggf. anteiligen) Urlaubsanspruch und den genehmigten/angenommenen Urlaubstagen inkl. Resturlaub aus dem Vorjahr und kann nicht überschrieben werden.

Darunter finden Sie ein Kalenderfeld, über das Sie komfortabel die einzelnen Tage des jetzt zu bearbeitenden Urlaubs eingeben. Klicken Sie auf die gewünschten Tage, summiert das System diese automatisch – und zieht sie auch wieder ab, wenn Sie noch einmal darauf klicken. Haben Sie den Urlaub richtig eingegeben, speichern Sie ihn mit Ok ab. Nach dem Schließen dieser Bearbeitungsmaske sehen Sie, dass der eben eingegebene Urlaub in einer neuen Zeile der Tabelle registriert wurde. Wenn Sie jetzt eine Zeile markieren, lösen Sie mit den beiden Buttons Urlaubstage bearbeiten und Urlaubstage löschen unterhalb des Tabellenfensters die entsprechende Funktion aus. Das Ganze geht natürlich wie immer auch per rechtem Mausklick und Kontextmenü.

Der Reiter Krankheit funktioniert analog, nur dass es hier natürlich das Differenzfeld für "übrige" Tage nicht gibt. Das Statusfeld enthält im Pulldown die (selbsterklärenden) Werte Entschuldigt und Unentschuldigt.

<!--Krankheitstage-->

<!-- SCREENSHOT -->
![Abbildung: Krankheit und Krankheitsstage hinzufügen]({{ img_url_desktop }}HumanResources/13_humanresources_mitarbeiter_krankheit_light.png#only-light){.desktop-img}
![Abbildung: Krankheit und Krankheitsstage hinzufügen]({{ img_url_desktop }}HumanResources/13_humanresources_mitarbeiter_krankheit_dark.png#only-dark){.desktop-img}
![Abbildung: Krankheit und Krankheitsstage hinzufügen]({{ img_url_mobile }}HumanResources/13_humanresources_mitarbeiter_krankheit_light.png#only-light){.mobile-img}
![Abbildung: Krankheit und Krankheitsstage hinzufügen]({{ img_url_mobile }}HumanResources/13_humanresources_mitarbeiter_krankheit_dark.png#only-dark){.mobile-img}

Hier gibt es noch eine Sonderfunktion: Haben Sie unentschuldigte Krankheitstage gebucht, können Sie diese, nach dem Schließen der Eingabemaske, auf der Tabellenzeile über das Kontextmenü (Rechtsklick auf die entsprechende Tabellenzeile) als Urlaub buchen.

Ein Klick auf das Tabellenkopf-Symbol rechts außen zeigt Ihnen bei beiden Tabellen, dass die nicht eingeblendeten Felder nur Bearbeitungsvermerke enthalten. Sie können also im Normalfall die Standardeinstellungen unverändert lassen.

## Weitere Funktionen des Bearbeitungsmenüs { data-ctx="/HumanResources/MainScreen/Employee/ActionToolbar" }
Mitarbeiter bearbeiten: öffnet nach Markierung eines vorhandenen Mitarbeiters in der Tabelle dieselbe Bearbeitungsmaske wie Mitarbeiter hinzufügen.

Mitarbeiter löschen: löscht den ausgewählten Mitarbeiter nach einer Sicherheitsabfrage.

Drucke Seite: öffnet Ihren systemeigenen Druckerdialog und erzeugt standardmäßig eine DIN-A4-Hochformatseite mit einer Tabelle und den auf dem Bildschirm ausgewählten Feldern.

Mitarbeiter exportieren: bietet zwei Optionen:
Als ODS exportieren für eine Tabelle im Dateiformat von Open-/LibreOffice (Zeilen- und Spaltenanordnung entspricht derjenigen der Bildschirmtabelle) und Als XLS exportieren für eine XLS-Datei, also das Format für die Tabellenkalkulation MS Excel in der Version 2000/XP, wobei neuere Versionen abwärtskompatibel sind.

## Personalkonten { data-ctx="/HumanResources/MainScreen/Account" }
Wie bereits erwähnt, erzeugt das System selbständig Personalkonten, sobald Sie einen Mitarbeiter anlegen. Klicken Sie links unter Modules auf Personalkonten. Wenn Sie zuvor einen Mitarbeiter angelegt haben, sehen Sie jetzt mindestens dessen zwei Personalkonten für das laufende und das folgende Jahr. Wenn alle Mitarbeiter schon vor längerer Zeit angelegt wurden, kann es sein, dass ein neues Personalkonto angelegt werden muss, weil die Zeit fortgeschritten ist. Klicken Sie dazu im Bearbeitungsmenü den Button Personalkonten anlegen. Das System fragt Sie nach dem Jahr, für das es ein Konto anlegen soll, der Rest erfolgt automatisch.

<a id="ctx:HumanResources.EditDialog.Account"></a>
Klicken Sie nun Personalkonto bearbeiten im Bearbeitungsmenü oder im Kontextmenü der rechten Maustaste.

<!-- SCREENSHOT -->
![Abbildung: Personalkonto bearbeiten]({{ img_url_desktop }}HumanResources/14_humanresources_personalkonten_dialog_light.png#only-light){.desktop-img}
![Abbildung: Personalkonto bearbeiten]({{ img_url_desktop }}HumanResources/14_humanresources_personalkonten_dialog_dark.png#only-dark){.desktop-img}
![Abbildung: Personalkonto bearbeiten]({{ img_url_mobile }}HumanResources/14_humanresources_personalkonten_dialog_light.png#only-light){.mobile-img}
![Abbildung: Personalkonto bearbeiten]({{ img_url_mobile }}HumanResources/14_humanresources_personalkonten_dialog_dark.png#only-dark){.mobile-img}

In der Maske unter dem Reiter Zusammenfassung finden Sie eine ganze Reihe von Zahlen, die Sie nicht überschreiben können; sie entsprechen den oben unter den Arbeitsverträgen sowie den Urlaubs- und Krankheitstagen eingegebenen Werten dieses Arbeitnehmers im betreffendem Jahr. Sollten Sie jetzt nur Nullen sehen, wurde für den Arbeitnehmer kein Arbeitsvertrag im laufenden Jahr angelegt. Gehen Sie in diesem Fall bitte zurück auf Mitarbeiter und legen Sie, wie weiter oben besprochen, zumindest probehalber einen Arbeitsvertrag an. Wir wollen zum besseren Verständnis sehen, wie sich die dort eingegebenen Zahlen auf der Personalkonto-Maske auswirken.

## Tägliche Arbeitszeitberichte

Im Modul Tägliche Arbeitszeitberichte werden jeweils pro Tag und Mitarbeiter alle aktuellen Daten gesammelt und verwaltet.
{{ branding.title }} kann dabei so eingerichtet werden, dass diese Tägliche Arbeitszeitberichte automatisch generiert und aktualisiert werden (z.B. über Nacht per cronjob).

Alternativ besteht die Möglichkeit über den Button Berechnen alle Berichte im Bearbeitungsmenü die Erstellung und Aktualisierung der Daten auszulösen. Bitte beachten Sie dabei, dass die Berechnung in Abhängigkeit der Datenmengen einige Zeit in Anspruch nehmen kann.

Bereits in der Listenansicht erhalten Sie einen guten und schnellen Überblick über die wichtigsten täglichen Arbeitszeitdaten pro Mitarbeiter, wie Auswertungsstartzeit, Auswertungsendzeit, Arbeitszeit (Soll), Arbeitszeit (Ist) und Gesamt Arbeitszeit.

<!-- SCREENSHOT -->
![Abbildung: Übersicht Tägliche Arbeitszeitberichte]({{ img_url_desktop }}HumanResources/17_humanresources_tagesarbeitszeitberichte_liste_light.png#only-light){.desktop-img}
![Abbildung: Übersicht Tägliche Arbeitszeitberichte]({{ img_url_desktop }}HumanResources/17_humanresources_tagesarbeitszeitberichte_liste_dark.png#only-dark){.desktop-img}
![Abbildung: Übersicht Tägliche Arbeitszeitberichte]({{ img_url_mobile }}HumanResources/17_humanresources_tagesarbeitszeitberichte_liste_light.png#only-light){.mobile-img}
![Abbildung: Übersicht Tägliche Arbeitszeitberichte]({{ img_url_mobile }}HumanResources/17_humanresources_tagesarbeitszeitberichte_liste_dark.png#only-dark){.mobile-img}

Schauen wir uns nun einen der generierten Datensätze unter Tägliche Arbeitszeitberichte im Bearbeitungsdialog genauer an. Wählen Sie dazu per Klick den gewünschten Datensatz rechts in der Listenansicht aus und klicken im Bearbeitungsmenü links außen auf den Button [Täglicher Arbeitszeitbericht bearbeiten]. Ein Doppelklick auf den gewünschten Datensatz in der Listenansicht öffnet ebenfalls den Bearbeitungsdialog.

<!-- SCREENSHOT -->
![Abbildung: Täglicher Arbeitszeitbericht bearbeiten]({{ img_url_desktop }}HumanResources/18_humanresources_tagesarbeitszeitberichte_dialog_light.png#only-light){.desktop-img}
![Abbildung: Täglicher Arbeitszeitbericht bearbeiten]({{ img_url_desktop }}HumanResources/18_humanresources_tagesarbeitszeitberichte_dialog_dark.png#only-dark){.desktop-img}
![Abbildung: Täglicher Arbeitszeitbericht bearbeiten]({{ img_url_mobile }}HumanResources/18_humanresources_tagesarbeitszeitberichte_dialog_light.png#only-light){.mobile-img}
![Abbildung: Täglicher Arbeitszeitbericht bearbeiten]({{ img_url_mobile }}HumanResources/18_humanresources_tagesarbeitszeitberichte_dialog_dark.png#only-dark){.mobile-img}

Im Bearbeitungsdialog sind alle in {{ branding.title }} vorhandenen Daten pro Tag und Mitarbeiter in aufsummierter Form gesammelt, die sich in irgend einer Form auf die Arbeitszeit auswirken. Sie haben hier die Möglichkeit die systemisch berechneten Inhalte über weitere Felder zu korrigieren. Damit ist gewährleistet, dass die automatisch kalkulierten Werte und manuelle Korrekturen nachvollziehbar sind. Kalkulierte Felder sind dabei inaktiv dargestellt. Entsprechend sind die zur manuellen Bearbeitung vorgesehenen Felder aktiv und anklickbar.

Schauen wir uns nun die einzelnen Einträge genauer an.
Mitarbeiter enthält, wie der Titel schon verrät, den Namen des Mitarbeiters und Datum beinhaltet das Datum des Tages zu dem die Daten berechnet wurden. In Monatliche Arbeitszeitberichte wird der Monat aus dem zugehörigen Monatliche Arbeitszeitberichte eingetragen (siehe auch [Monatliche Arbeitszeitberichte](ka_HumanResources.md/#monatliche-arbeitszeitberichte)).

Die Auswertungsstartzeit und Auswertungsendzeit wird jeweils aus den für diesen Tag gültigen Arbeitszeit Regelungen im Vertrag des Mitarbeiters (siehe auch [Mitarbeiter - Verträge](ka_HumanResources.md/#vertrage) übernommen und enthält den maximalen Auswertungs- bzw. Arbeitszeitrahmen. Diesen täglichen Rahmen können Sie in Korrigierte Auswertungs Startzeit und Korrigierte Auswertungs Endzeit für diesen Tag ändern. Weiterhin wird die Arbeitszeit (Soll), [Pausenzeit (Netto) und Pausenzeit Abzug aus den Mitarbeiter Vertragsdaten entnommen, wobei Sie im Bedarfsfall die Soll Arbeitszeit für diesen Tag in Soll Arbeitszeit Korrektur korrigieren können. Die [Pausenzeit (Netto) und Pausenzeit Abzug sind nur berechnet, wenn tatsächlich Arbeitszeiten (Ist-Zeiten) für diesen Tag erfasst wurden.

Arbeitszeiten enthält eine Liste aller gesammelten Daten des Mitarbeiters für diesen Tag, die Grundlage für die berechneten Werte sind. Neben den Abwesenheitszeiten wie Urlaub und Krankheit sowie den gesetzlichen Feiertagen, sind hier die Arbeitszeiten gemäß vertraglicher Vereinbarung nach den einzelnen Lohnarten aufgeschlüsselt.

Die Summe aus allen Arbeitszeiterfassungs-Datensätzen dieses Mitarbeiters für diesen Tag ist in Arbeitszeit (Ist) berechnet und festgehalten. Sollte es vertraglich geregelte Pausezeiten geben, sind diese bereits abgezogen. In Arbeitszeit (Berichtigt) können Sie eine Korrektur vornehmen. Dabei tragen Sie nur die Zeit ein, um welche die Arbeitszeit (Ist) bei der Berechnung der Gesamt Arbeitszeit angepasst werden soll (z.B. - 00:30 = abzüglich 30 Minuten, 01:45 = zuzüglich 1 Stunden und 45 Minuten).

Die Gesamt Arbeitszeit des Mitarbeiters zum jeweiligen Tag wird hier berechnet (nach Speicherung) und ergibt sich aus Arbeitszeit (Ist) und Arbeitszeit (Berichtigt).

In Anmerkung des Systems werden u.U. Textinformationen eingetragen, wie z.B. Urlaub, Krankheit.
Dadurch ist schnell erkennbar, warum Gesamt Arbeitszeit z.B. 8 Stunden beträgt, es aber keinen Wert in Arbeitszeit (Ist) gibt. In Anmerkung können Sie eigene Informationen festhalten.

{{ branding.title }} kennzeichnet den Datensatz mit einem Häkchen in Fehler bei der Berechnung, sollte die Ermittlung der Daten nicht vollständig möglich gewesen sein oder es sind bei der Berechnung Unstimmigkeiten erkannt worden (z.B: Arbeitszeitüberschneidungen/doppelte Arbeitszeiterfassung bei gleicher Zeitangabe). Sie finden dann einen Hinweis in Anmerkung des Systems.

Speichern Sie Ihre Änderungen mit Klick auf den Button OK rechts unten im Bearbeitungsdialog, damit erfolgt die Aktualisierung der Gesamtarbeitszeit. Alternativ drücken Sie den Button Neuberechnung und erhalten im noch geöffneten Bearbeiten Dialog den aktuellen Wert.

!!! note "Anmerkung"
    Sind keine Istwerte abrufbar (keine Arbeitszeiten erfasst), wird dennoch pro Tag für jeden Mitarbeiter ein Täglicher Arbeitszeitbericht generiert. Enthalten sind dann nur die Abwesenheits-, Feiertags- und Soll-Werte.

## Monatliche Arbeitszeitberichte

Im Modul Monatliche Arbeitszeitberichte werden jeweils pro Monat und Mitarbeiter die Daten aus Tägliche Arbeitszeitberichte kummuliert und verwaltet.
{{ branding.title }} kann dabei so eingerichtet werden, dass diese Monatliche Arbeitszeitberichte automatisch generiert und aktualisiert werden (z.B. über Nacht per cronjob). Somit steht immer ein aktueller monatlicher Stand pro Mitarbeiter zur Verfügung.

Alternativ besteht die Möglichkeit über den Button Berechnen alle Berichte im Bearbeitungsmenü die Erstellung und Aktualisierung der Daten auszulösen. Bitte beachten Sie dabei, dass die Berechnung in Abhängigkeit der Datenmengen einige Zeit in Anspruch nehmen kann.

Bereits in der Listenansicht erhalten Sie einen guten und schnellen Überblick über die wichtigsten monatlichen Arbeitszeitdaten pro Mitarbeiter, wie Vormonats Saldo Arbeitszeit, Arbeitszeit (Ist), Arbeitszeit (Soll), Arbeitszeit (Berichtigt) und Gesamt Saldo Arbeitszeit.

<!-- SCREENSHOT -->
![Abbildung: Übersicht Monatliche Arbeitszeitberichte]({{ img_url_desktop }}HumanResources/19_humanresources_montasarbeitszeitberichte_liste_light.png#only-light){.desktop-img}
![Abbildung: Übersicht Monatliche Arbeitszeitberichte]({{ img_url_desktop }}HumanResources/19_humanresources_montasarbeitszeitberichte_liste_dark.png#only-dark){.desktop-img}
![Abbildung: Übersicht Monatliche Arbeitszeitberichte]({{ img_url_mobile }}HumanResources/19_humanresources_montasarbeitszeitberichte_liste_light.png#only-light){.mobile-img}
![Abbildung: Übersicht Monatliche Arbeitszeitberichte]({{ img_url_mobile }}HumanResources/19_humanresources_montasarbeitszeitberichte_liste_dark.png#only-dark){.mobile-img}

Schauen wir uns nun einen der generierten Datensätze unter Monatliche Arbeitszeitberichte im Bearbeitungsdialog genauer an. Wählen Sie dazu per Klick den gewünschten Datensatz rechts in der Listenansicht aus und klicken im Bearbeitungsmenü links außen auf den Button Monatlicher Arbeitszeitbericht bearbeiten. Ein Doppelklick auf den gewünschten Datensatz in der Listenansicht öffnet ebenfalls den Bearbeitungsdialog.

<!-- SCREENSHOT -->
![Abbildung: Monatlicher Arbeitszeitbericht bearbeiten]({{ img_url_desktop }}HumanResources/20_humanresources_montasarbeitszeitberichte_dialog_light.png#only-light){.desktop-img}
![Abbildung: Monatlicher Arbeitszeitbericht bearbeiten]({{ img_url_desktop }}HumanResources/20_humanresources_montasarbeitszeitberichte_dialog_dark.png#only-dark){.desktop-img}
![Abbildung: Monatlicher Arbeitszeitbericht bearbeiten]({{ img_url_mobile }}HumanResources/20_humanresources_montasarbeitszeitberichte_dialog_light.png#only-light){.mobile-img}
![Abbildung: Monatlicher Arbeitszeitbericht bearbeiten]({{ img_url_mobile }}HumanResources/20_humanresources_montasarbeitszeitberichte_dialog_dark.png#only-dark){.mobile-img}

Im Bearbeitungsdialog sehen Sie das Ergebnis der Summe aus allen täglichen Informationen aus Tägliche Arbeitszeitberichte pro Mitarbeiter innerhalb des Monats. Im gleichnamigen Block Tägliche Arbeitszeitberichte werden diese einzeln aufgeführt. Korrekturen unter Tägliche Arbeitszeitberichte wirken sich entsprechend nach einer Neuberechnung hier aus.

Schauen wir uns nun die weiteren Einträge an.
Mitarbeiter enthält, wie der Titel schon verrät, den Namen des Mitarbeiters und Monat beinhaltet die Periode zusammengesetzt aus Jahr und Monat.

Der Vormonats Saldo Arbeitszeit enthält den Gesamt Saldo Arbeitszeit der vorherigen Periode. So enthält beispielsweise der Monatliche Arbeitszeitberichte im Januar des aktuellen Jahres den Wert aus Dezember des Vorjahres in Vormonats Saldo Arbeitszeit.

Die Arbeitszeit (Ist) entspricht der Summe des Wertes Gesamt Arbeitszeit aller Datensätze in Tägliche Arbeitszeitberichte zu diesem Mitarbeiter in dem Monat.
Entsprechend enthält Arbeitszeit (Soll) als Summe das Ergebnis aus Arbeitszeit (Soll) + Soll Arbeitszeit Korrektur aller Datensätze in Tägliche Arbeitszeitberichte zu diesem Mitarbeiter in dem Monat.
In Arbeitszeit (Berichtigt) können Sie die monatliche Arbeitszeit bei Bedarf manuell korrigieren.

Der Gesamt Saldo Arbeitszeit entspricht dem kummulierten Ergebnis aus der Berechnung von (Arbeitszeit (Soll), Arbeitszeit (Ist)) mit Arbeitszeit (Berichtigt) und dem Vormonats Saldo Arbeitszeit.

Speichern Sie Ihre Änderungen mit Klick auf den Button OK rechts unten im Bearbeitungsdialog, damit erfolgt die Aktualisierung der Gesamt Saldo Arbeitszeit. Alternativ drücken Sie den Button Neuberechnung und erhalten im noch geöffneten Bearbeiten Dialog den aktuellen Wert.

!!! info "Wichtig"
    Abschließend und erst wenn Sie sicher sind, dass der Monat für diesem Mitarabeiter komplett bearbeitet und alle Ergebnisse gesammelt wurden, aktivieren Sie in Ist Freigegeben das Häkchen und Speichern. Dies bewirkt, dass für diese Monats-Periode nichts mehr an den Daten unter Tägliche Arbeitszeitberichte und an diesem Datensatz selbst geändert werden kann. Im Hintergrund werden vom System gleichzeitig ein paar Referenzen gesetzt, z.B: werden die Daten aus der Arbeitszeiterfassung mit diesem Monatliche Arbeitszeitberichte
Setzen Sie dieses Häkchen erst, wenn die Monatsdaten abgeschlossen und z.B. an die Lohnbuchhaltung übergeben werden sollen.

Für die monatliche Auswertung der Arbeitszeitdaten des Mitarbeiters wählen Sie den Button Exportiere Monatlicher Arbeitszeitbericht und wählen Sie monthlyWTReport. Sie erhalten ein Monatsprotokoll pro Mitarbeiter im Excel Format.

<!-- SCREENSHOT -->
![Abbildung: Beispiel Export Monatlicher Arbeitszeitbericht]({{ img_url_desktop }}HumanResources/21_humanresources_export_monatsprotokoll_light.png#only-light){.desktop-img}
![Abbildung: Beispiel Export Monatlicher Arbeitszeitbericht]({{ img_url_desktop }}HumanResources/21_humanresources_export_monatsprotokoll_dark.png#only-dark){.desktop-img}
![Abbildung: Beispiel Export Monatlicher Arbeitszeitbericht]({{ img_url_mobile }}HumanResources/21_humanresources_export_monatsprotokoll_light.png#only-light){.mobile-img}
![Abbildung: Beispiel Export Monatlicher Arbeitszeitbericht]({{ img_url_mobile }}HumanResources/21_humanresources_export_monatsprotokoll_dark.png#only-dark){.mobile-img}

!!! note "Anmerkung"
    Bei der Ausgabe der Informationen unter Spalte Bemerkung handelt es sich um den Namen der Lohnart aus den Stammdaten (siehe [Stammdaten - Lohnarten](ka_HumanResources.md/#lohnarten)).
    Hier wird z.B. bei Abwesenheit immer der Name der Lohnart ausgegeben, die in den Stammdaten zugewiesen ist (siehe [Stammdaten - Abwesenheitsarten](ka_HumanResources.md/#abwesenheitsarten)).
    Wünschen Sie in der Berichtsausgabe den Namen des Abwesenheitsgrundes, können Sie eine weitere Lohnart mit entsprechendem Namen anlegen und diese der Abwesenheitsart zuordnen.
    Dieses Vorgehen kann u.U. von Vorteil bei der Ausgabe von Krank, KindKrank und Krank über 7 Wochen sein oder Urlaub, Unbezahlter Urlaub und Sonderurlaub sein.

## Abwesenheitsplanung

Zur schnellen und einfachen Plannung von An- und Abwesenheitszeiten der Mitarbeiter mit Überblick über die Gesamtlage wurde das Modul Abwesenheitsplanung in Matrixform entwickelt. Neben einer schnellen und einfachen Erfassung von Abwesenheitszeiten, welche weiter untern beschrieben wird, erhalten Sie einen Gesamtüberblick über die An- und Abwesenheitszeiten Ihrer Mitarbeiter. Dabei können Sie mit den bekannten Filterfunktionen z.B nach bestimmten Mitarbeitergruppen (wie Abteilungen) und den zu betrachtenden Zeiträumen (wie innerhalb des aktuellen Jahres) selektieren. Navigieren Sie innerhalb dieser festgelegten Selektionen durch Blättern der Seiten und vorgegebenen Perioden (wie Monat) in der Matrix.

<!-- SCREENSHOT -->
![Abbildung: Matrix Abwesenheitsplanung]({{ img_url_desktop }}HumanResources/22_humanresources_free_time_planning_matrix_light.png#only-light){.desktop-img}
![Abbildung: Matrix Abwesenheitsplanung]({{ img_url_desktop }}HumanResources/22_humanresources_free_time_planning_matrix_dark.png#only-dark){.desktop-img}
![Abbildung: Matrix Abwesenheitsplanung]({{ img_url_mobile }}HumanResources/22_humanresources_free_time_planning_matrix_light.png#only-light){.mobile-img}
![Abbildung: Matrix Abwesenheitsplanung]({{ img_url_mobile }}HumanResources/22_humanresources_free_time_planning_matrix_dark.png#only-dark){.mobile-img}

Für die Schnellerfassung von Abwesenheitszeiten erkennt das System, anhand der Cursor-Position in der Matrix (Klick auf die Zelle - bestehend aus Zeile/Spalte oder auch x-/y-Koordinaten), um welche Daten es sich handelt und erleichtert Ihnen somit die Eingaben.
Die komfortabelste Variante ist dabei die direkte Eingabe des Kürzels für die gewünschte Abwesenheit (siehe [Stammdaten - Abwesenheitsarten](ka_HumanResources.md/#abwesenheitsarten)) in die gewünschte Zelle der Matrix Abwesenheitsplanung und es öffnet sich der Bearbeitungsdialog Freetime. Dabei werden schon viele Daten vorausgefüllt wie Mitarbeiter, der Typ der Abwesenheit, der Status, das zugehörige Personalkonto und das Datum der gewählten Zelle. Sie können nun die Daten ändern oder ergänzen und z.B. den Status ändern oder per Klick auf einzelne Tage im Minikalender den gewünschten Zeitraum anpassen. Markieren Sie zuvor in der Matrix mehrere Zellen mit der Maus (in Kombination mit der Hochstell/Umschalt-Taste) als Zeitraum über mehrere Tage und Sie erhalten im Bearbeitunsgdialog diesen Zeitraum im Minikalender bereits markiert.
Eine Besonderheit gilt bei Urlaubsabwesenheit - das System berechnet in Übrig die verbleibenden Anzahl Tage. Sobald Sie per Klick weitere Tage im Minikalender für den gewünschten Zeitraum vervollständigen, sehen Sie diese Änderung in Übrig.

Ein Doppelklick auf einer Zelle öffnet ebenfalls den Bearbeitungsdialog Freetime, kennt dann aber noch nicht den gewünschten Typ der Abwesenheit. Ergänzen Sie den Typ der Abwesenheit und Sie erhalten weitere Daten.

Haben Sie keinen Mitarbeiter (Matrixzeile) in der Matrix angeklickt, öffnet sich ein leerer Bearbeitungsdialog und Sie können die gewünschten Daten erfassen.

<!-- SCREENSHOT -->
![Abbildung: Abwesenheitsplanung bearbeiten]({{ img_url_desktop }}HumanResources/23_humanresources_free_time_planning_dialog_light.png#only-light){.desktop-img}
![Abbildung: Abwesenheitsplanung bearbeiten]({{ img_url_desktop }}HumanResources/23_humanresources_free_time_planning_dialog_dark.png#only-dark){.desktop-img}
![Abbildung: Abwesenheitsplanung bearbeiten]({{ img_url_mobile }}HumanResources/23_humanresources_free_time_planning_dialog_light.png#only-light){.mobile-img}
![Abbildung: Abwesenheitsplanung bearbeiten]({{ img_url_mobile }}HumanResources/23_humanresources_free_time_planning_dialog_dark.png#only-dark){.mobile-img}

!!! note "Anmerkung"
    Die Dialoginhalte werden flexibel in Abhängigkeit der Dateninhalte zur Laufzeit gebaut. Beispielsweise enthält der Bearbeitendialog in Abhängigkeit des Typ der Abwesenheit unterschiedliche Informationen zum Status.

## Arbeitszeiterfassung

Die Anwendung Human Resources kann grundsätzlich ohne die Erfassung von Arbeitszeiten genutzt werden, wodurch einige Module bzw. Funktionalitäten wirkungslos bleiben. Für die Arbeitszeiterfassung bietet {{ branding.title }} zwei Varianten an. Eine Möglichkeit ist es, die Zeiten manuell durch die Mitarbeiter in der {{ branding.title }} Anwendung [Zeiterfassung](la_Zeiterfassung.md) zu erfassen. Die zweite Möglichkeit setzt den Einsatz der Zusatzanwendung [DFCom](xa_DFCom.md) voraus und erfolgt über die Anbindung von Terminals.

### Arbeitszeiterfassung manuell in {{ branding.title }}

Die manuelle Arbeitszeiterfassung erfolgt durch die einzelnen Mitarbeiter in der {{ branding.title }} Anwendung [Zeiterfassung](la_Zeiterfassung.md). Bitte folgen Sie der Beschreibung unter [Zeiterfassung - Stundenzettel hinzufügen](la_Zeiterfassung.md/#stundenzettel-hinzufugen) für die Erfassung von Arbeitszeiten.
Bei einer reinen Arbeitszeiterfassung benötigen Sie nur einen Teil der dort geschilderten und möglichen Informationen. So können Sie z.B. auf abrechnungsrelevante Informationen verzichten, die im Wesentlichen zur Kalkulation von Projektarbeiten oder Arbeiten mit Nachweis zu bestimmten abrechnungsrelevanten Dienstleistungen notwendig sind.

Die Anwendung Human Resources greift durch die Integration der {{ branding.title }} Anwendungen für die automatische Berechnung und Kalkulation von Arbeitszeiten auf diese Daten zu (siehe [Tägliche Arbeitszeitberichte](ka_HumanResources.md/#tagliche-arbeitszeitberichte) und [Monatliche Arbeitszeitberichte](ka_HumanResources.md/#monatliche-arbeitszeitberichte)).

### Arbeitszeiterfassung mit DataFox

Die Anbindung der DataFox-Arbeitszeit-Terminals über die DFCom Anwendung (siehe [DFCom](xa_DFCom.md)), bietet die Möglichkeit einer verschlüsselten Internetanbindung. Hierdurch ist das Ein- und Aus-Stempeln via HomeOffice (Online mit {{ branding.title }}) oder im Büro durch das Terminal möglich. Sollte das Internet ausfallen, sind die Terminals weiter stabil Nutzbar. Die Terminals synchronisieren die gestempelten Zeiten mit {{ branding.title }}, sobald die Internetanbindung wieder steht. Eine weitere Besonderheit ist, das es keinen Zentralen Controller gibt, da {{ branding.title }}, mit der Hilfe der DFCom-Anwendung, sich direkt als Client integriert - ähnlich wie bei IoT (Internet of Things).

- Die Zeitstempel werden in wenigen Sekunden online geladen/sichtbar
- Das Terminal gibt Auskunft über Namen, Ausweisnummer und dem Zeit-Saldo des Mitarbeiters
- {{ branding.title }} liefert eine übersichtliche Darstellung der gestempelten Zeiten im Zeiterfassungsmodul
