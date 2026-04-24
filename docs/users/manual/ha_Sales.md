# Sales
Die tine-Anwendung Sales (_Verkauf_) ist kein typischer Teil einer Groupware, sondern später als ERP-Baustein auf Kundenwunsch hinzugekommen. Sie dient dem Erfassen von Produkten (auch Dienstleistungen fallen darunter), Verträgen und Kundendatensätzen sowie einigen zugehörigen buchhalterischen Daten. Diese können durch Verknüpfungen miteinander und auch mit anderen Einträgen, wie z.B. einem extra für einen Verkaufsvorgang angelegten Zeitkonto samt Stundenzetteln, verbunden werden. Damit ist es möglich, Produktions-, Verkaufs- und Liefervorgänge insoweit vollständig zu erfassen, als dem Rechnungswesen alle Daten zur Rechnungslegung zur Verfügung stehen.

Zu Beginn dieses Buches haben wir die Aufgabe einer Groupware in Abgrenzung vom reinen Kundenbeziehungsmanagementsystem (CRM) beschrieben: Im Vordergrund steht die Kommunikation in Unternehmen mit vergleichsweise längeren Wertschöpfungsprozessen, die zudem die Zusammenarbeit mehrerer Mitarbeiter erfordern. Bei solch komplexen Prozessen kann der Gesamtaufwand, der zur Lieferung eines Produktes und/oder einer Dienstleistung aufgewendet wurde, leicht aus dem Blick geraten. Damit das nicht geschieht, verfügt tine über die Anwendungen Sales, Zeiterfassung und HumanResources.

<!--Rechnungswesen-->
Das Rechnungswesen selbst ist (noch) nicht Bestandteil von tine. Aber es lassen sich, da wir es ja mit einer quelloffenen Software zu tun haben, bei Bedarf entsprechende Schnittstellen einrichten, welche die hier gespeicherten Daten an ein Fakturierungsprogramm übergeben.

Im Folgenden werden Sie feststellen, dass Sales unter den o.g. Programmteilen mit ERP-Funktion eine Sonderstellung einnimmt, da es auch die Untermodule Kostenstellen und Abteilung enthält, die nicht unmittelbar dem Verkaufsprozess zuzuordnen sind.[^1]

[^1]:
    Die Anordnung hier hat historische Gründe und wird in einer der nächsten Versionen von tine korrigiert werden.

Rufen Sie über den Reiter tinenet oder tinecom die Anwendung Sales auf (wenn sie nicht schon als Reiter angezeigt wird – in diesem Falle klicken Sie den Reiter an).

## Untermodule und Favoriten

Die Ansicht der linken Seite dieses Programmteils unterscheidet sich von den klassischen Groupware-Anwendungen, denn Sales kennt z.B. keine Unterteilung in Datenbanken für "persönliche", "gemeinsame" oder Objekte "anderer Benutzer".

<!-- SCREENSHOT -->
![Abbildung: Sales-Anwendung als Gesamtansicht]({{ img_url_desktop }}Sales/1_sales_uebersicht_light_1920x1020.png#only-light){.desktop-img}
![Abbildung: Sales-Anwendung als Gesamtansicht]({{ img_url_desktop }}Sales/1_sales_uebersicht_dark_1920x1020.png#only-dark){.desktop-img}
![Abbildung: Sales-Anwendung als Gesamtansicht]({{ img_url_mobile }}Sales/1_sales_uebersicht_light_1280x720.png#only-light){.mobile-img}
![Abbildung: Sales-Anwendung als Gesamtansicht]({{ img_url_mobile }}Sales/1_sales_uebersicht_dark_1280x720.png#only-dark){.mobile-img}

<!--ERP-->
Auch daran erkennen Sie, dass es sich hier eigentlich um ein Tool zur Ressourcenplanung und -erfassung (ERP) handelt. Im oberen Teil der linken Seite unter Module finden Sie dafür mehrere Unterprogramme.

<!-- SCREENSHOT -->
![Abbildung: Module der Sales-Anwendung]({{ img_url_desktop }}Sales/2_sales_module_light_1920x1020.png#only-light){.desktop-img}
![Abbildung: Module der Sales-Anwendung]({{ img_url_desktop }}Sales/2_sales_module_dark_1920x1020.png#only-dark){.desktop-img}
![Abbildung: Module der Sales-Anwendung]({{ img_url_mobile }}Sales/2_sales_module_light_1280x720.png#only-light){.mobile-img}
![Abbildung: Module der Sales-Anwendung]({{ img_url_mobile }}Sales/2_sales_module_dark_1280x720.png#only-dark){.mobile-img}

Die Einträge unter Favoriten wechseln je nach Modul. In Produkte und Verträge gibt es in der Standardvariante nur eine Ansicht, nämlich Meine Produkte (also jene, die Sie selbst angelegt haben) bzw. Meine Verträge (analog). Kunden, Kostenstellen und Abteilung haben gar keine Favoriten-Ansichten, da sie der Eingabe zentral verwalteter Daten (Kunden) bzw. Parameter für die Aufwandserfassung dienen.

<!--Produkt-->
## Produkte

### Produkt hinzufügen

Wählen Sie links unter Module den Menüpunkt Produkte. Das Bearbeitungsmenü enthält die Einträge Produkt hinzufügen, Produkt bearbeiten, Produkt löschen und Drucke Seite. Dass Produkt bearbeiten und Produkt löschen ausgegraut sind, wenn in der Tabelle kein Produkt angewählt ist, wird Ihnen schon geläufig sein.

Was Sie auch schon kennen, ist die Verfahrensweise mit der Tabellenansicht. Rechts außen finden Sie das Symbol zum An- und Abwählen von Tabellenspalten. Wenn Sie es aus der Standardansicht heraus anklicken, sehen Sie, dass die vier Felder Angelegt von, Angelegt am, Zuletzt geändert von und Letztes Modifikationsdatum nicht angezeigt werden. Dasselbe wird Ihnen auch im weiteren Verlauf der Sales-Anwendung bei den anderen zu besprechenden Objekten begegnen, denn es handelt sich hier um vom System automatisch vergebene Einträge. Lassen Sie sie jetzt auch ausgeblendet und schließen Sie das Spaltensymbol wieder. Es genügt zu wissen, dass sie bei Bedarf einzublenden sind.

Sollten im System bisher keine Produkte gespeichert sein, fügen wir jetzt eines hinzu, denn im weiteren Verlauf des Kapitels benötigen wir es für Verkettungs-Funktionen mit Verträgen und Kunden. Klicken Sie darum jetzt Produkt hinzufügen.

<!-- SCREENSHOT -->
![Abbildung: Neues Produkt anlegen]({{ img_url_desktop }}Sales/3_sales_produkt_neu_light_1920x1020.png#only-light){.desktop-img}
![Abbildung: Neues Produkt anlegen]({{ img_url_desktop }}Sales/3_sales_produkt_neu_dark_1920x1020.png#only-dark){.desktop-img}
![Abbildung: Neues Produkt anlegen]({{ img_url_mobile }}Sales/3_sales_produkt_neu_light_1280x720.png#only-light){.mobile-img}
![Abbildung: Neues Produkt anlegen]({{ img_url_mobile }}Sales/3_sales_produkt_neu_dark_1280x720.png#only-dark){.mobile-img}

In der Bearbeitungsmaske unter dem geöffneten Reiter Produkt finden Sie die in der Standardansicht der Tabelle angezeigten Felder wieder. Die oben erwähnten Daten zum Bearbeitungsverlauf finden Sie nicht, da sie, wie schon erwähnt, automatisch erstellt werden.


Kommen wir zu den Feldern zur Charakterisierung eines Produktes: Sie müssen sie nicht alle ausfüllen – nur Name und Preis sind Pflichtfelder. Beachten Sie bei Eingabe eines Preises, dass dieser in englischer Notation, also mit einem _Dezimalpunkt_ anstelle eines Kommas, eingegeben werden muss. Wenn Sie das Produkt zukaufen, d.h. der Hersteller ein anderes Unternehmen ist, können Sie das hier vermerken.


Ist der Hersteller des Produktes als Kontaktdatensatz in Ihrer tine-Adressdatenbank erfasst, können Sie ihn mit dem Produktdatensatz verknüpfen. Da diese Verknüpfung nicht als Programmfunktion in Sales vorgesehen ist, nutzen Sie dazu die allgemeine Verknüpfungsfunktion.


!!! info "Wichtig"
    Die Vergabe von Produktnamen und -kategorien kann in Ihrem Unternehmen verbindlich definiert sein – klären Sie dies ggf. mit Ihrer Produktionsabteilung.

Legen Sie nun ein Testprodukt an, um später die entsprechenden Verknüpfungsfunktionen nachzuvollziehen.

### Produkt bearbeiten

Beim Klick auf Produkt bearbeiten öffnet sich die gleiche Maske, wie Sie sie schon vom Anlegen eines Produktes kennen. Der Punkt des Bearbeitungsmenüs ist nur aktiv, wenn Sie in der Tabelle ein Produkt markiert haben. Sie erreichen das Menü auch über einen Doppelklick auf den Tabelleneintrag oder über einen Rechtsklick und das Kontextmenü.

### Produkt löschen

Der Menüpunkt löscht ein in der Tabelle angewähltes Produkt, immer mit Sicherheitsabfrage.[^2]

[^2]:
    Beachten Sie, dass Produkte auch dann gelöscht werden, wenn Sie z.B. mit Verträgen verknüpft sind, was natürlich zu unerwünschten Inkonsistenzen führen kann. Diese Unzulänglichkeit wird in einer künftigen Version von tine sicher behoben.

### Drucke Seite

Der Menüpunkt öffnet Ihren browserinternen Druckdialog und erzeugt standardmäßig eine Liste aller in der Tabellenansicht dargestellten Produkte im DIN-A4-Hochformat. Dabei werden exakt die Felder ausgedruckt, die auch in der Tabellenansicht ausgewählt wurden. Die Zeichengröße im Druckbild passt sich entsprechend dynamisch an.

<!--Kunden-->
## Kunden

In der Menü-Reihenfolge der Module wäre der nächste Punkt unter Produkte eigentlich das Modul Verträge; dass wir hier davon abweichen und zunächst das Kunden-Modul behandeln, hat seinen Grund: Die Verknüpfung der drei Objekte "Produkt", "Kunde" und "Vertrag" hat im "Vertrag" seinen Ausgangspunkt. Um das Procedere nachzuvollziehen, müssen also bereits Produkte und Kunden vorhanden sein.

Unter Kunden finden Sie im Bearbeitungsmenü analog zu Produkte die Einträge Kunde hinzufügen, Kunde bearbeiten, Kunde löschen und Drucke Seite, dazu noch den Menüpunkt Export. Die Tabellenansicht enthält bereits in der Standardvariante eine Vielzahl von Datenfeldern, zu denen (klicken Sie zur Probe einmal rechts außen das Tabellenkopfsymbol an) noch, analog zu Produkte, vier weitere kommen, die vom System gemäß dem Bearbeitungsverlauf automatisch vergebene Werte enthalten. Schauen wir uns die anderen Felder und ihre Bedeutung nun über die Bearbeitungsmaske näher an.

### Kunde hinzufügen

Klicken Sie im Bearbeitungsmenü Kunde hinzufügen an. Unter dem Reiter Kunde finden Sie eine Maske mit den Feldern, die auch in der Tabellenansicht zu sehen waren. Beachten Sie, dass jetzt die Felder Währung, Zahlungsziel in Tagen, Wechselkurs und Rabatt in Prozent bereits mit Standardwerten gefüllt sind.

<!-- SCREENSHOT -->
![Abbildung: Kunden hinzufügen]({{ img_url_desktop }}Sales/4_sales_kunden_neu_light_1920x1020.png#only-light){.desktop-img}
![Abbildung: Kunden hinzufügen]({{ img_url_desktop }}Sales/4_sales_kunden_neu_dark_1920x1020.png#only-dark){.desktop-img}
![Abbildung: Kunden hinzufügen]({{ img_url_mobile }}Sales/4_sales_kunden_neu_light_1280x720.png#only-light){.mobile-img}
![Abbildung: Kunden hinzufügen]({{ img_url_mobile }}Sales/4_sales_kunden_neu_dark_1280x720.png#only-dark){.mobile-img}

Zuerst weisen Sie, als Pflichteingabe und mit zwei Pfeilen zum Hoch- und Herunterzählen versehen, die Kundennummer zu. Sie können zwar beliebige Einträge vornehmen, im Hochzählen über den Pfeil wird aber die nächste Ganzzahl (ohne evtl. vorangestellte Nullen oder Buchstaben) eingetragen.

Bei Name tragen Sie die Firmenbezeichnung Ihres Kunden ein.

Das Auswählen einer Kontaktperson (extern) über das Pulldown aus allen gespeicherten Kontaktdatensätzen ergänzt im Sektor Postanschrift automatisch die Felder Straße, Postleitzahl, Ort, Region und Land gemäß den dortigen Eintragungen. In diesem Block stehen Ihnen noch zwei Felder Zusatz und Zusatz 2 zur Verfügung. Rechts daneben finden Sie einen kleinen rechteckigen Button, hinter dem sich die praktische Funktion Adresse in die Zwischenablage kopieren befindet, die Sie z.B. zum schnellen Ausfüllen einer Adresse in einem Musterbrief-Formular verwenden können.

Kontaktperson intern ist ein Pulldown, mit dem Sie aus dem gesamten Adressdatenbestand Ihrer tine-Installation eine interne Kontaktperson für diesen Kunden auswählen.

Damit haben Sie einen Großteil der Maske schon ausgefüllt. Sie sollten, wenn aus diesen Daten die Rechnungserstellung geplant ist, noch unter Abrechnung die USt.-ID sowie IBAN und BIC eingeben sowie die Standardeinstellungen für Zahlungsziel in Tagen, Wechselkurs und Rabatt in Prozent prüfen und bei Bedarf ändern.

Unter Sonstiges tragen Sie im Feld Internet ggf. die Webadresse des Kunden ein.

Der Reiter Rechnungsanschrift dient dem Anlegen einer oder mehrerer, ggf. von der Kundenadresse abweichender, Rechnungsadressen. Im unteren Rand des Fensters finden Sie die drei Buttons Rechnungsanschrift bearbeiten, Rechnungsanschrift hinzufügen und Rechnungsanschrift löschen. Klicken Sie jetzt Rechnungsanschrift hinzufügen, erhalten Sie eine weitere, kleinere Eingabemaske, in die Sie entweder, mit dem kleinen Zauberstab-Button links oben, die Adressdaten von der Kundenmaske übernehmen oder selbst händisch eine andere Adresse eintragen. Beachten Sie auch das Feld Debitorennummer; hier können Sie der Rechnungsadresse eine von der Kundennummer abweichende Debitorennummer zuweisen und so dem Kunden bei Bedarf über getrennte Wege Rechnungen stellen. Das ist insbesondere dann notwendig, wenn Sie mit Großunternehmen Geschäfte machen. Legen Sie jetzt unter Zuhilfenahme des Zauberstab-Buttons testweise eine Rechnungsanschrift an.

Lieferanschrift funktioniert analog zu Rechnungsanschrift, nur dass hier die Debitorennummer in der Maske fehlt.

### Kunden bearbeiten

Beim Klick auf Kunde bearbeiten öffnet sich die gleiche Maske, die Sie schon vom Anlegen eines Kunden kennen, nur dass hier natürlich bereits Daten in den Feldern stehen. Der Punkt des Bearbeitungsmenüs ist nur aktiv, wenn Sie in der Tabelle einen Kunden markiert haben. Sie erreichen das Menü auch über einen Doppelklick auf den Tabelleneintrag oder über einen Rechtsklick und das Kontextmenü.

### Kunden löschen

Der Menüpunkt löscht einen in der Tabelle angewählten Kunden, immer mit Sicherheitsabfrage.[^3]

[^3]:
    Auch hier erfolgt die Löschung trotz eventuell vorhandener Verknüpfungen (vgl. Fußnote in [Produkt löschen](ha_Sales.md/#produkt-loschen)).

### Drucke Seite

Der Menüpunkt öffnet Ihren browserinternen Druckdialog und erzeugt standardmäßig eine Liste aller in der Tabellenansicht dargestellten Kunden im DIN-A4-Hochformat. Dabei werden exakt die Felder ausgedruckt, die auch in der Tabellenansicht ausgewählt wurden. Die Zeichengröße im Druckbild passt sich entsprechend dynamisch an.

### Export

Über die Export-Funktion können Sie eine Tabellenkalkulationsdatei im ODS-Format (Open/LibreOffice) erstellen, die alle Felder der Kundendatenbank enthält, nicht nur die in der Tabellenansicht eingeblendeten. Nur die letzten vier Felder zum Bearbeitungsstatus werden nicht mit übertragen.

<!--Kostenstellen-->
## Kostenstellen

Wir gehen weiter in den Menüpunkten unterhalb von Kunden, denn auch Kostenstellen sollten wir vor Verträgen angelegt haben, damit wir dort dann die Verknüpfung vornehmen können.

!!! info "Wichtig"
    Die Nomenklatur von Kostenstellen wird branchen- und/oder unternehmensspezifisch nach verschiedenen Kriterien definiert und gilt i.d.R. für das ganze Unternehmen verbindlich. Das entsprechende Verzeichnis sollte Ihnen also bei der Anlage in tine vorliegen.

### Kostenstelle hinzufügen

Klicken Sie im Bearbeitungsmenü ganz links Kostenstelle hinzufügen. Die Bearbeitungsmaske enthält nur zwei Werte: Nummer und Anmerkung. Beide Felder sind alphanumerische Pflichteingaben. Tragen Sie hier einfach die Werte Ihres Kostenstellenverzeichnisses ein.

### Kostenstelle bearbeiten

Beim Klick auf Kostenstelle bearbeiten öffnet sich die gleiche Maske, nur dass hier natürlich bereits Daten in den Feldern stehen. Der Punkt des Bearbeitungsmenüs ist nur aktiv, wenn Sie in der Tabelle eine Kostenstelle markiert haben. Sie erreichen das Menü auch über einen Doppelklick auf den Tabelleneintrag oder über einen Rechtsklick und das Kontextmenü.

### Kostenstelle löschen

Der Menüpunkt löscht eine in der Tabelle angewählte Kostenstelle, immer mit Sicherheitsabfrage – und ebenfalls ohne Prüfung von Verknüpfungen.

### Drucke Seite

Der Menüpunkt öffnet Ihren browserinternen Druckdialog und erzeugt standardmäßig eine Liste aller in der Tabellenansicht dargestellten Kunden im DIN-A4-Hochformat. Es werden exakt die Felder ausgedruckt, die auch in der Tabellenansicht ausgewählt wurden – in diesem Fall sind es standardmäßig nur zwei. Sie können jedoch auch die vier Felder zur Dokumentation der Bearbeitung mit anwählen und ausdrucken.

<!--Verträge-->
## Verträge

Unter Verträge finden Sie im Bearbeitungsmenü analog zu Produkte die Einträge Vertrag hinzufügen, Vertrag bearbeiten, Vertrag löschen und Drucke Seite. Auch diese Tabellenansicht umfasst eine Vielzahl von Feldern, zu denen (analog zu den eben besprochenen Kunden) vier weitere kommen, die den Bearbeitungsverlauf automatisch dokumentieren.

### Vertrag hinzufügen

Klicken Sie Vertrag hinzufügen, finden Sie unter dem Reiter Vertrag eine Maske mit den Feldern, die auch in der Tabellenansicht zu sehen waren. Die vier ausgeblendeten Felder sind hier auch nicht in der Maske zu finden, denn sie enthalten vom System automatisch ausgefüllte Daten.

<!-- SCREENSHOT -->
![Abbildung: Neuen Vertrag hinzufügen]({{ img_url_desktop }}Sales/5_sales_vertrag_neu_light_1920x1020.png#only-light){.desktop-img}
![Abbildung: Neuen Vertrag hinzufügen]({{ img_url_desktop }}Sales/5_sales_vertrag_neu_dark_1920x1020.png#only-dark){.desktop-img}
![Abbildung: Neuen Vertrag hinzufügen]({{ img_url_mobile }}Sales/5_sales_vertrag_neu_light_1280x720.png#only-light){.mobile-img}
![Abbildung: Neuen Vertrag hinzufügen]({{ img_url_mobile }}Sales/5_sales_vertrag_neu_dark_1280x720.png#only-dark){.mobile-img}

Nummer ist ein Pflichtfeld und zur manuellen Eingabe oder automatischen Vergabe einer Vertragsnummer vorgesehen, d.h. Sie können (wenn Sie über die entsprechende Berechtigung verfügen) einstellen, ob hier eine Vertragsnummer frei eingegeben oder vom System automatisch hochgezählt werden soll. Die dazu erforderlichen Einstellungsmöglichkeiten finden Sie in der Anwendung Admin unter Anwendungen -> Sales -> Settings (vgl. [Administration - Sales](oa_Administration.md/#sales)). Außerdem wird dort auch festgelegt, welche Formen einer Vertragsnummer (nur Ziffern oder auch Text) erlaubt sein sollen.

!!! info "Wichtig"
    Diese Vorgaben sollten vor der Anlage erster Verträge erfolgen und danach nicht mehr verändert werden können.

Titel ist ebenfalls ein Pflichtfeld und kann eine beliebige Buchstaben/Zahlen-Kombination aufnehmen.

Unter Kunde erhalten Sie ein Pulldown, das Ihnen die im System bereits angelegten Kunden anbietet. Probieren Sie das jetzt einmal aus – Sie sollten mindestens den weiter oben angelegten Testkunden zur Auswahl haben. Weisen Sie ihn jetzt diesem Vertrag zu. Wenn Sie für den Kunden eine Rechnungsanschrift hinterlegt haben, wird diese nun automatisch im nächsten Feld übernommen. Bei mehreren Adressen bietet Ihnen ein Pulldown die Auswahl. Es erscheint eine eventuell vergebene Debitorennummer in Klammern dahinter (Rechnung – XX).

Anfang und Ende sind Datumsfelder, die für Vertragsanfang und -ende stehen.

Über Kontaktperson (extern) und Kontaktperson (intern) weisen Sie per Pulldown einen beliebigen in tine gespeicherten Kontakt zu. In beiden Fällen ist es möglich, sowohl einen Kontakt aus einem beliebigen Adressbuch als auch einen Benutzer von tine auszuwählen.

Über Hauptkostenstelle weisen Sie dem Vertrag eine Kostenstelle zu. Das funktioniert natürlich ebenfalls nur, wenn im System bereits Kostenstellen angelegt wurden (vgl. [Kostenstellen](ha_Sales.md/#kostenstellen)).

Im Feld Beschreibung speichern Sie beliebige Informationen über den Vertrag.

Klicken Sie nun den Reiter Produkte an.

<!-- SCREENSHOT -->
![Abbildung: Neuen Vertrag hinzufügen – hier für Produkte]({{ img_url_desktop }}Sales/6_sales_vertrag_neu_produkte_light_1920x1020.png#only-light){.desktop-img}
![Abbildung: Neuen Vertrag hinzufügen – hier für Produkte]({{ img_url_desktop }}Sales/6_sales_vertrag_neu_produkte_dark_1920x1020.png#only-dark){.desktop-img}
![Abbildung: Neuen Vertrag hinzufügen – hier für Produkte]({{ img_url_mobile }}Sales/6_sales_vertrag_neu_produkte_light_1280x720.png#only-light){.mobile-img}
![Abbildung: Neuen Vertrag hinzufügen – hier für Produkte]({{ img_url_mobile }}Sales/6_sales_vertrag_neu_produkte_dark_1280x720.png#only-dark){.mobile-img}

Sie sehen, dass Sie per Pulldown dem Vertrag beliebig viele Produkte in beliebiger Anzahl zuweisen können.

### Vertrag bearbeiten

Beim Klick auf Vertrag bearbeiten öffnet sich die gleiche Maske, wie beim Anlegen eines Vertrages. Der Punkt des Bearbeitungsmenüs ist nur aktiv, wenn Sie in der Tabelle einen Vertrag markiert haben. Sie erreichen das Menü auch über einen Doppelklick auf den Tabelleneintrag oder über einen Rechtsklick und das Kontextmenü.

### Vertrag löschen

Der Menüpunkt löscht einen in der Tabelle angewählten Vertrag, immer mit Sicherheitsabfrage.

### Drucke Seite

Dieser Menüpunkt öffnet Ihren browserinternen Druckdialog und erzeugt standardmäßig eine Liste aller in der Tabellenansicht dargestellten Verträge im DIN-A4-Hochformat. Es werden exakt die Felder ausgedruckt, die auch in der Tabellenansicht ausgewählt wurden.

## Abteilungen

Mit Abteilungen ist eine Einheit in der Unternehmensstruktur gemeint.

### Abteilung hinzufügen

Das Anlegen einer Abteilung funktioniert über den Button Abteilung hinzufügen. Die Bearbeitungsmaske enthält unter dem Reiter Division nur den Wert Titel. Tragen Sie dort die Bezeichnung Ihrer Abteilung ein. Die Abteilung wird in der Sales-Anwendung nicht mit anderen Objekten verknüpft, stattdessen in HumanResources mit dem Mitarbeiter.

### Abteilung bearbeiten

Beim Klick auf Abteilung bearbeiten öffnet sich die gleiche Maske, wie Sie sie schon vom Anlegen einer Abteilung her kennen. Der Punkt des Bearbeitungsmenüs ist nur aktiv, wenn Sie in der Tabelle eine Abteilung markiert haben. Sie erreichen das Menü auch über einen Doppelklick auf den Tabelleneintrag oder über einen Rechtsklick und das Kontextmenü.

### Abteilung löschen

Der Menüpunkt löscht eine in der Tabelle angewählte Abteilung, immer mit Sicherheitsabfrage.

### Drucke Seite

Dieser Menüpunkt öffnet Ihren browserinternen Druckdialog und erzeugt standardmäßig eine Liste aller in der Tabellenansicht dargestellten Abteilungen im DIN-A4-Hochformat. Es werden exakt die Felder ausgedruckt, die auch in der Tabellenansicht ausgewählt wurden – in diesem Falle ist es standardmäßig nur eines. Sie können jedoch auch die vier Felder zur Dokumentation der Bearbeitung mit anwählen und ausdrucken.
