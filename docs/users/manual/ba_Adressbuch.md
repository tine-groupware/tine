# Adressverwaltung

## Einleitung { data-ctx="/Addressbook" }

Bei der Festlegung der Kapitelreihenfolge sind wir davon ausgegangen, dass die meisten von Ihnen dieses Buch mit dem Wunsch zur Hand genommen haben, schnell erste Erfolge mit {{ branding.title }} zu erzielen. Daher haben wir die Adressverwaltung ganz bewusst an den Anfang gestellt, und nicht etwa [Allgemeine Hinweise zur Bedienung](ca_StandardBedienhinweise.md) zu den allgemeinen Bedienhinweisen. Auch als völlig unerfahrener Benutzer werden Sie nach der Lektüre dieses ersten Kapitels bereits mit dem Adressbuch und teilweise auch mit dem E-Mail-Client arbeiten können, ohne den Querverweisen auf andere Abschnitte folgen zu müssen. Eine Ausnahme bildet hier nur die mächtige Funktion des Filterns, die zwar in der Praxis hauptsächlich für Adressen verwendet wird, die wir aber erst im [Allgemeine Hinweise zur Bedienung](ca_StandardBedienhinweise.md) detailliert erklären.

Außerdem behandeln wir hier ausführlich das Thema "Datenübernahme aus anderen Systemen", sodass Sie bereits sehr bald mit Ihrem eigenen Kontaktdatenbestand arbeiten können.

Die wichtigste Regel zuerst: {{ branding.title }} erlaubt zwar das Anlegen beliebig vieler Adressbücher, jedoch sollten Sie der Versuchung widerstehen, diese Option zum logischen "Sortieren" von Adressdaten zu nutzen! Sie würden damit das grundsätzliche Ordnungs- und Zugriffsprinzip einer Groupware unterlaufen. Das Anlegen verschiedener Adressbücher dient hier nur einem Zweck: der Vergabe unterschiedlicher Zugriffsrechte.

Die Rechteverwaltung in {{ branding.title }} ist sehr umfangreich, darauf kommen wir in [Administration](oa_Administration.md) noch zu sprechen. Weil {{ branding.title }} – ganz anders als z.B. Google Mail – aus einer sog. "Private Cloud" zu betreiben ist, wird die Software von Anwendern geschätzt, denen der persönliche wie auch der Datenschutz des Unternehmens wichtig ist. Ein kleines Beispiel zur Verdeutlichung dieses Konzepts:

<!-- Datenschutz -->
<!-- Bring your own device -->
*[BYOD]: "Bring your own device", eine seit dem massenweisen Privatbesitz von Smartphones und Tablets auftretende Tendenz, im beruflichen Umfeld lieber das eigene, meist hochwertigere Mobilfunkgerät zu benutzen, anstelle der vom Unternehmen zur Verfügung gestellten Hardware. Gleichwohl diese Entwicklung als identitätsstiftend mit dem Beruf und dem Unternehmen gedeutet wird, stellt sie für Administratoren ein großes Sicherheitsproblem dar.

Ist dem Nutzer aufgrund der Firmenpolicy das Verwalten privater Adressen erlaubt, wird er sich ein privates Adressbuch und einen privaten Kalender anlegen, auf die nur er Zugriff hat. Da {{ branding.title }} auch mit einer ausgereiften Anbindung an mobile Geräte aufwartet, werden viele Anwender gerne ihre privaten Handykontakte und -termine von {{ branding.title }} verwalten lassen (statt sie bei Google oder iCloud quasi der Öffentlichkeit preiszugeben). Komfort geht hier mit der berechtigten Forderung "Meine Daten gehören mir!" zusammen; die firmeneigene {{ branding.title }}-Installation in der Private Cloud stellt sicher, dass Datenkraken keinen Zugriff auf private wie firmeninterne Daten haben. Gleichzeitig kann das Unternehmen mit dem Gestatten der Speicherung privater und abgeschotteter Daten auf der Firmen-Groupware der Gefahr des Eindringens von Datenspionen über die privaten Mobilfunkgeräte in das Firmennetz vorbeugen, ein mit dem Aufkommen von BYOD nicht zu unterschätzender Pluspunkt.

Wie nähern wir uns aber nun einem durchaus komplexen System wie {{ branding.title }}? Wo setzt die Beschreibung der Software an?

Wir könnten die Software nun einfach starten und ihre Menüs von links oben nach rechts unten beschreiben – was ebenso einfach wie sinnlos wäre.

<!-- Kontaktdaten,Import -->
Beginnen wir vielmehr mit einer Anforderung, die wohl jeder neue Anwender an eine Kontaktverwaltung stellt: dem Import eigener Daten. Erst mit der Sicherheit, wertvolle Daten "herübergerettet" zu haben, sind wir letztlich bereit, uns auch auf neue Funktionen einzulassen. Sollten Sie bereits über einen Datenbestand von mehreren Kontakten verfügen und sich lieber zunächst mit den alltäglichen Funktionen des Adressbuches beschäftigen wollen, können Sie den folgenden Unterabschnitt auch einfach überspringen.


## Kontakte importieren

In den allermeisten Fällen werden Sie vor der Arbeit mit {{ branding.title }} bereits einen E-Mail-Client oder andere Programme genutzt haben, die auch ein Adressbuch verwalten. Natürlich sollen diese Kontakte weiter zur Verfügung stehen – und das möglichst ohne Nacharbeit. Vielleicht haben Sie auch ein Smartphone voll mit Kontaktdaten, die Sie zukünftig ebenfalls über {{ branding.title }} verwalten möchten.  Sollten Sie Administrator sein, wissen Sie, dass die Akzeptanz der Einführung eines neuen IT-Systems mit der Datenübernahme aus Altsystemen steht und fällt. Solche Übernahmen laufen jedoch fast nie automatisch ab, denn dazu sind EDV-Systeme im Detail einfach zu verschieden. Das bedeutet – und hier sind Sorgfalt und Geduld gefordert – dass sich jede Minute, die Sie an dieser Stelle mehr investieren, später vielfach bezahlt machen wird!

{{ branding.title }} bietet zwar eine ganze Reihe von Standard-Schnittstellen (MS Outlook 2007, Google-Adressbuch, VCard, CSV im Outlook- oder macOS-Format) – jedoch funktionieren diese Schnittstellen nur dann quasi "automatisch", wenn das Ausgangsprogramm exakt die Datenstruktur verwendet, die den {{ branding.title }}-Programmierern zum Zeitpunkt der Erstellung der Schnittstelle bekannt war.

Da sich solche Strukturen erfahrungsgemäß schneller ändern als einem lieb ist, empfiehlt sich zunächst ein genauer Blick auf diese Datenstruktur und folglich die Anpassung Ihrer Quelldaten an eben diese Struktur von {{ branding.title }}. Wenn Sie die Grundlagen eines Tabellenkalkulationsprogramms, wie MS Excel oder Open-/LibreOffice Calc, beherrschen, stellt das für Sie keine große Hürde dar.

<!--CSV-Format-->
*[CSV-Schnittstelle]: Comma-separated Values, also eine mit eindeutigen Trennzeichen (z.B. Komma, Semikolon, Doppelpunkt etc.) ausgezeichnete Werteliste.

Lesen Sie zunächst die Quelldatei aus Ihrem Altsystem aus. Benutzen Sie dazu (das wird normalerweise immer angeboten) eine CSV-Schnittstelle. So entsteht eine Tabelle als Textdatei, deren Spalten durch "Sonderzeichen" getrennt sind. Holen Sie sich dann die Beispieldatei für Ihr Quellsystem aus {{ branding.title }}. Klicken Sie dazu in der Ansicht Adressbuch (Reiter {{ branding.title }} - Adressbuch) den Button Kontakte importieren.

<!-- SCREENSHOT -->
![Abbildung: Adressbuch mit Importfenster]({{ img_url_desktop }}Adressbuch/1_adressbuch_importfenster_light.png#only-light){.desktop-img}
![Abbildung: Adressbuch mit Importfenster]({{ img_url_desktop }}Adressbuch/1_adressbuch_importfenster_dark.png#only-dark){.desktop-img}
![Abbildung: Adressbuch mit Importfenster]({{ img_url_mobile }}Adressbuch/1_adressbuch_importfenster_light.png#only-light){.mobile-img}
![Abbildung: Adressbuch mit Importfenster]({{ img_url_mobile }}Adressbuch/1_adressbuch_importfenster_dark.png#only-dark){.mobile-img}

Sie erhalten das Fenster Datei und Format wählen. Dort gehen Sie im unteren Bereich auf das Pulldown-Menü CSV-Import für Kontakte und suchen sich das für Ihre Ausgangsdaten geeignetste Format heraus. Im Zweifelsfall belassen Sie es auf der Ausgangsstellung CSV-Import für Kontakte. Klicken Sie nun auf den Link Beispieldatei herunterladen und speichern Sie die Beispieldatei irgendwo, wo Sie sie leicht wiederfinden. Schließen Sie das Fenster wieder durch Klick auf Abbrechen (unten rechts). Schauen Sie sich die heruntergeladene Datei (das ist auch eine CSV-Datei) dann in einem Tabellenkalkulationsprogramm (MS Excel, Open-/LibreOffice Calc o.ä.) an und vergleichen Sie jede Spalte mit Ihrer Quelldatei! Die Spalten _müssen_ in der Bezeichnung, nicht jedoch in der Reihenfolge übereinstimmen, da {{ branding.title }} die Datenfelder in der Regel anhand der Spaltenbezeichnung zuordnet. Passen Sie entsprechend die Namen der Felder (Spalten) in Ihrer Quelldatei an.

Da wir uns gerade beim Zuordnen von Datenfeldern befinden: Bei der Datenübernahme aus einem anderen System sollten Sie sich vor Augen führen, wie dieses einen "Kontakt" definiert – im Unterschied zu "Firma", "Organisation" usw.

Klassische CRM-Systeme (SalesForce, SugarCRM etc.) geben häufig fest in der Datenbank definierte Strukturen vor, d.h. es gibt die Möglichkeit, ein Unternehmen (mit Adresse, Telefonnummern usw.) anzulegen und als damit verknüpfte Untereinträge die eigentlichen Kontakte, also Personen innerhalb dieses Unternehmens, wiederum mit ergänzenden Kontaktdaten. Auch in unternehmensspezifisch angepassten Lotus-Notes-Installationen findet man häufig diese Konstellation.

Andere E-Mail-Programme, wie z.B. MS Outlook, Evolution, Thunderbird usw., bieten diese Unterscheidung nicht. Dort gibt es nur den Kontaktdatensatz, mit einem Feld für die Firma, aber auch mit Feldern für Name, Vorname usw. Evolution und Thunderbird bieten beispielsweise die Untergliederung in verschiedene Adressbücher, und MS Outlook kann Kontaktdatensätze hierarchisch, wie in einem Dateisystem, anordnen. Damit könnte man theoretisch Unternehmensstrukturen abbilden; das lässt sich aber nur händisch umsetzen und wird in der Praxis eher selten genutzt.

Auch {{ branding.title }} verzichtet auf die Unterscheidung von "Kontakt" und "Firma" – es gibt nur Kontakte. Allerdings hat jeder Kontaktdatensatz ein Datenfeld Firma. Und da {{ branding.title }} (wir kommen weiter unten noch dazu) über sehr detaillierte Filter-Einstellungen und Verkettungsfunktionen verfügt, ist die Gruppierung und Bearbeitung mehrerer Kontakte über einen Filter "gleiche Firma" oder über gesetzte Verkettungen unproblematisch.

Wenn Sie jetzt bei Ihrer Quelldatei darauf achten, dass die Einträge bei "Firma" (je nach Quellsystem auch "Organisation", "org_unit" o.ä. genannt) bei mehreren Kontakten einer Firma wirklich identisch sind, vereinfacht das später das gemeinsame Verwalten dieser Kontakte.

<!-- Tags -->
Eine andere Möglichkeit wäre das _Taggen_, also das Markieren mit einem speziellen Kürzel (_Tag_): Sollte es Ihnen jetzt nicht möglich sein, die Firmen-Identität der Kontaktdatensätze herzustellen (oder die Bezeichnungen von Organisationseinheiten sollen verschieden sein, obwohl es sich um dasselbe Unternehmen handelt), dann können Sie auch darauf verzichten. Später werden wir auf die Tags als Technik der Gruppierung zu sprechen kommen, die die Feldinhalte unverändert lässt.

Hier noch zwei technische Hinweise zum reibungslosen Importieren von Kontakten über CSV-Dateien:

<!-- Zeichensätze -->
Zeichensätze können verschieden sein!
<div style="margin-left: 40px">
Beim Einlesen von CSV-Dateien in z.B. MS Excel oder Open-/LibreOffice Calc werden Sie nach dem zu wählenden Zeichensatz gefragt. Der häufig passendste (achten Sie auf `ß` und Umlaute!) ist Unicode UTF-8. Probieren Sie lieber etwas herum – Sie sparen sich später viel Nacharbeit, wenn Ihre Umlaute in {{ branding.title }} korrekt angezeigt werden.
</div>

CSV-Dateien sind nicht standardisiert!
<div style="margin-left: 40px">
<p>Die Trennung der Datenfelder kann vom Quellsystem anstatt über Kommata auch durch andere Zeichen oder Tabulatoren erzeugt worden sein. {{ branding.title }} trennt aber nur mittels Kommata. Achten Sie darum bei Ihrer Quelldatei darauf, dass die einzelnen Felder tatsächlich mit Kommata getrennt und die Feldinhalte jeweils von doppelten Anführungszeichen eingeschlossen werden. Also:</p>

"Spalte 1 - Inhalt 1", "Spalte 2 - Inhalt 1"<br>
"Spalte 1 - Inhalt 2", "Spalte 2 - Inhalt 2"<br>
...

<p>Die Anführungszeichen um die Feldinhalte sorgen v.a. dafür, dass z.B. Kommata innerhalb der Felder als Inhalt und nicht als Feldtrenner interpretiert werden.</p>
</div>

Aber wie verändern Sie nun die Beschaffenheit einer CSV-Datei, wenn sie nicht korrekt erzeugt wurde? MS Excel ist an dieser Stelle beispielsweise wenig hilfreich: Es bietet Ihnen keine Möglichkeit, das Aussehen Ihrer CSV-Datei vor dem Speichern unter zu beeinflussen – besser ist hier die Verwendung von OnlyOffice oder LibreOffice Calc. Bei Speichern unter bieten diese freien Tabellenkalkulationsprogramme die Auswahlmöglichkeit Text CSV, und Sie bekommen dann einen Checkbutton Filtereinstellungen bearbeiten angeboten. Wenn Sie diesen anklicken, erhalten Sie vor dem Abspeichern Ihrer CSV-Datei einen Dialog Textexport, auf dem Sie Zeichensatz, Feldtrenner und Texttrenner einstellen.

Wählen Sie als Zeichensatz Unicode (UTF-8), als Feldtrenner das Komma (,) und als Texttrenner die doppelten Anführungszeichen ("). So gerüstet, sollten unangenehme Überraschungen beim Einlesen der Daten ausbleiben.

Überlegen Sie sich anschließend, welches {{ branding.title }}-Adressbuch für Ihre zu importierenden Kontakte in Frage kommt. Öffnen Sie die Adressbuch-Ansicht und sehen Sie links unter dem Ordner Adressbücher - Alle Adressbücher folgende Unterordner:

* Meine Adressbücher

* Gemeinsame Adressbücher

* Adressbücher anderer Benutzer (diese Ansicht ist benutzerabhängig)


Handelt es sich um Kontakte, die allen Mitarbeitern zugänglich sein sollen, benutzen Sie ein gemeinsames Adressbuch. Stimmen Sie sich hierzu mit dem Administrator Ihrer {{ branding.title }}-Installation ab! Keinesfalls sollten Sie, ohne Nachfrage oder eigene Planung, "mal eben" ein Adressbuch anlegen! {{ branding.title }} bietet mehrere elegantere Möglichkeiten, Adressen logisch zu separieren und zu gruppieren. Nur wenn Sie tatsächlich private Adressen einlesen, legen Sie bitte ein privates Adressbuch an oder benutzen Ihr bereits vorhandenes privates. Standardmäßig sind in {{ branding.title }} zwei Adressbücher angelegt:

Meine Adressbücher
<div style="margin-left: 40px">
Ihr privates Adressbuch, das auch Ihren Benutzernamen trägt
</div>

Gemeinsame Adressbücher/Internal contacts
<div style="margin-left: 40px">
das gemeinsame Standard-Adressbuch für alle Mitarbeiter des Unternehmens, eben jener "Group", für die die Groupware in erster Linie da sein soll.
</div>

Beachten Sie, dass Ihre {{ branding.title }}-Installation von diesem Schema abweichen kann. So könnte z.B. das gemeinsame interne Adressbuch den Namen Ihres Unternehmens oder Ihrer Organisationseinheit tragen. Außerdem könnte sich in dem Ordner z.B. ein Adressbuch External Contacts o.ä. befinden, das der Speicherung von Kunden, Lieferanten oder anderer externer Kontakte dient.

Wenn Sie sich nun über das passende Adressbuch für Ihre Daten im Klaren sind und diese entsprechend aufbereitet haben, starten Sie den Import über Adressbuch -> Kontakte importieren. In dem sich öffnenden Fenster Datei und Format wählen, wählen Sie die Datei mit Ihren Kontakten aus und prüfen das Import-Format. Standardmäßig ist CSV-Import für Kontakte eingestellt; haben Sie Ihre Import-Datei, wie oben beschrieben, vorbereitet und ausgewählt, gehen Sie Vorwärts (rechts unten).

<!-- SCREENSHOT -->
![Abbildung: Import-Optionen setzen]({{ img_url_desktop }}Adressbuch/4_adressbuch_mit_import_optionen_setzen_light.png#only-light){.desktop-img}
![Abbildung: Import-Optionen setzen]({{ img_url_desktop }}Adressbuch/4_adressbuch_mit_import_optionen_setzen_dark.png#only-dark){.desktop-img}
![Abbildung: Import-Optionen setzen]({{ img_url_mobile }}Adressbuch/4_adressbuch_mit_import_optionen_setzen_light.png#only-light){.mobile-img}
![Abbildung: Import-Optionen setzen]({{ img_url_mobile }}Adressbuch/4_adressbuch_mit_import_optionen_setzen_dark.png#only-dark){.mobile-img}

In der nächsten Ansicht (Import-Optionen setzen) wählen Sie das Adressbuch aus, in das die Kontakte importiert werden sollen. Als Voreinstellung finden Sie jeweils das zuletzt verwendete Adressbuch. Darunter wird Ihnen standardmäßig ein _Tag_ vorgegeben – Importliste (Tagesdatum). Dieser dient dazu, Ihre jetzt einzulesenden Kontakte als Ganzes zu markieren, um diesen Einlesevorgang später eingrenzen und ggf. rückgängig machen zu können. Natürlich können Sie diesen Tag später, wenn Sie sich davon überzeugt haben, dass der Einlesevorgang korrekt abgelaufen ist, wieder löschen. Außerdem können Sie hier auf dieser Maske ggf. auch noch weitere Tags vergeben, mit denen die einzulesenden Kontakte versehen werden sollen – darauf kommen wir gleich.

<!--Tags -->
Was ist ein _Tag_? Es ist ein Etikett, d.h. eine Markierung, die einen Datensatz irgendwie kennzeichnet und nach dem (das ist der Sinn der Sache) der Benutzer später die Datensätze, in unserem Falle die Kontakte, suchen oder filtern kann. Mit dem standardmäßig vergebenen Tag Importliste (Tagesdatum) können Sie sich also später die heute eingelesenen Kontaktdatensätze noch einmal gefiltert aufrufen. Das Pulldown-Menü hinter dem Feld Tag Name zeigt Ihnen alle bereits angelegten allgemeinen und persönlichen Tags, die Sie zur Kennzeichnung der einzulesenden Datensätze – als Zuordnung zu den bereits vorhandenen Datensätzen mit diesem Tag – benutzen können. Mit Klick auf das + am rechten Rand, definieren Sie sich einen neuen persönlichen Tag, der die Datensätze noch einmal extra kennzeichnet.

Das Konzept der Tags wird uns noch oft begegnen, denn es steht in {{ branding.title }} für alle Arten von Datensätzen zur Verfügung.

Aber zurück zur Datenübernahme: Jetzt, nach der exakten Vorarbeit, sollten wir startklar sein. Dann los: Mit dem Button Vorwärts (unten rechts) startet der letzte Prüfschritt vor dem Einlesevorgang. Etwas Geduld – das System macht eine Volltextsuche, denn im Hintergrund wird einerseits geprüft, ob das Datenformat der angebotenen Datei dem {{ branding.title }}-Format für Adressbücher entspricht, und andererseits, ob es Dubletten gibt. Wenn alles klar gegangen ist, meldet das System im Fenster Zusammenfassung:

* Wir haben n Datensätze in der Import-Datei gefunden.

* Es werden n als neue Datensätze hinzugefügt: Adressbuch_Name.

* "Alle Datensätze werden mit Importliste (Tagesdatum) getaggt, damit sie einfach gefunden werden können." (Hier würden ggf. auch Ihre neu angelegten Tags mit aufgelistet werden.)

Sollte diese Meldung nicht erscheinen, erhalten Sie stattdessen:

* Entweder eine Liste mit Fehlern, versehen mit der Datensatznummer.

<!-- SCREENSHOT -->
![Abbildung: Import einer CSV-Datei in {{ branding.title }} mit Einlesefehlern]({{ img_url_desktop }}Adressbuch/6_adressbuch_import_einlesefehler_light.png#only-light){.desktop-img}
![Abbildung: Import einer CSV-Datei in {{ branding.title }} mit Einlesefehlern]({{ img_url_desktop }}Adressbuch/6_adressbuch_import_einlesefehler_dark.png#only-dark){.desktop-img}
![Abbildung: Import einer CSV-Datei in {{ branding.title }} mit Einlesefehlern]({{ img_url_mobile }}Adressbuch/6_adressbuch_import_einlesefehler_light.png#only-light){.mobile-img}
![Abbildung: Import einer CSV-Datei in {{ branding.title }} mit Einlesefehlern]({{ img_url_mobile }}Adressbuch/6_adressbuch_import_einlesefehler_dark.png#only-dark){.mobile-img}

Die genaue Fehlerbeschreibung bekommen Sie, wenn Sie das + aufklappen. Mögliche Fehler haben ihre Ursache regelmäßig in der Nichteinhaltung der oben erläuterten Konventionen (sind Feldbegrenzer (,) und Textbegrenzer (") richtig gesetzt?). Brechen Sie den Einlesevorgang ab (unten rechts Abbrechen) und schauen Sie sich zur Kontrolle die Einlesedatei einmal mit einem normalen Textprogramm an (sind die Text- und Feldbegrenzer vorhanden?). Lesen Sie gegebenenfalls oben noch einmal nach.

* Oder Sie sehen den Dialog Konflikte auflösen.

<!-- SCREENSHOT -->
![Abbildung: Konflikte auflösen beim Import fehlerhafter Datensätze]({{ img_url_desktop }}Adressbuch/7_adressbuch_mit_import_konflikte_aufloesen_light.png#only-light){.desktop-img}
![Abbildung: Konflikte auflösen beim Import fehlerhafter Datensätze]({{ img_url_desktop }}Adressbuch/7_adressbuch_mit_import_konflikte_aufloesen_dark.png#only-dark){.desktop-img}
![Abbildung: Konflikte auflösen beim Import fehlerhafter Datensätze]({{ img_url_mobile }}Adressbuch/7_adressbuch_mit_import_konflikte_aufloesen_light.png#only-light){.mobile-img}
![Abbildung: Konflikte auflösen beim Import fehlerhafter Datensätze]({{ img_url_mobile }}Adressbuch/7_adressbuch_mit_import_konflikte_aufloesen_dark.png#only-dark){.mobile-img}

In diesem Fall gab es keine Formatfehler, jedoch Konflikte (Dubletten-Datensätze). Diese Funktion ist sehr mächtig und komfortabel: Das System zeigt Ihnen oben links die Anzahl der konfliktbehafteten Kontaktdatensätze. Dabei gibt es für {{ branding.title }} eine genaue Definition, wann ein Kontaktdatensatz einen Konflikt aufweist. Standardmäßig ist ein Konflikt dann vorhanden, wenn ein einzulesender Datensatz entweder bei der E-Mail-Adresse oder gemeinsam beim Vornamen, Namen und der Firmenbezeichnung mit einem bereits vorhandenen Datensatz identisch ist. Diese Definition kann der Administrator jedoch unternehmensspezifisch anpassen. Die Prüfung auf Dubletten erfolgt konsequenterweise über alle Adressbücher.

Im Dialogfenster Konflikte auflösen werden Ihnen in Tabellenform alle gefundenen Konflikte (Dopplungen) des jeweiligen Konflikt-Datensatzes mit bereits gespeicherten Kontaktdaten angezeigt: Unter Mein Wert ist der Eintrag aufgeführt, der in der einzulesenden Datei steht, unter Existierender Wert der bereits in {{ branding.title }} gespeicherte Wert, und unter Endgültiger Wert derjenige, welcher in {{ branding.title }} übernommen würde, wenn die Aktion durchgeführt wird, die oben links im Pulldown-Menü Aktion ausgewählt ist. Sie können somit für jeden einzelnen Datensatz entscheiden, was genau mit den Einträgen geschehen soll.

Wenn Sie die Aktion ändern, sehen Sie das Ergebnis _live_ in der Tabelle. Solange Sie nicht oben links Konflikt ist aufgelöst drücken, passiert jedoch nichts. Probieren Sie also gefahrlos aus, welche Konfliktlösung die beste ist. Standardmäßig steht die Auswahl auf Zusammenführen - existierende Werte behalten – was für die meisten Fälle auch die beste Lösung sein wird, denn der sich bereits in {{ branding.title }} befindliche Datenbestand sollte, wegen der strengen Prüfung, ja bereits weitgehend konsistent sein. Wenn Sie sich darüber im Klaren sind, klicken Sie Konflikt ist aufgelöst. Gehen Sie so durch alle Konflikt-Datensätze, um sicherzustellen, dass alle Ihre Daten nach diesem Einlesevorgang wirklich konsistent sind und bleiben. Sollten Sie sich sicher sein, dass z.B. Ihre vorhandenen Daten bereits insgesamt die beste Variante darstellen und nur noch um die Felder ergänzt werden können, die nur in der neuen Datei belegt sind, dann können Sie auch Zusammenführen - existierende Werte behalten wählen, rechts neben Konflikt ist aufgelöst das kleine Pulldown-Symbol anklicken und Alle Konflikte auflösen wählen. Das verkürzt den Vorgang. In jedem Fall müssen Sie so lange durch die Konflikte (mit den Vorwärts-Rückwärts-Pfeilen links oben) gehen, bis der Button Ende rechts unten nicht mehr ausgegraut ist. Erst wenn alle Konflikte bearbeitet sind, wird er bedienbar. Das System zeigt Ihnen dann auch (wie oben schon erklärt) die Zusammenfassung des bevorstehenden Einlesevorgangs im Fenster Zusammenfassung mit einer oder mehrerer der folgenden Statusmeldungen an:

* Wir haben n Datensätze in der Import-Datei gefunden.

* Es wurden n Datensätze als Dubletten identifiziert.

* Es werden n Dubletten mit existierenden Kontakten zusammengeführt.

* Es werden n als neue Datensätze hinzugefügt. Adressbuch_Name

* "Alle Datensätze werden mit Importliste (Tagesdatum) getaggt, damit sie einfacher gefunden werden können."

Hat Ihr System keine weiteren Fehler festgestellt? Dann sind Sie jetzt bereit, die Daten zu übernehmen. Klicken Sie also unten ganz rechts Ende, und die Daten werden jetzt endgültig eingelesen. Da auch in Ihrer Quelldatei selbst schon Konflikte vorhanden sein können, kann es jetzt noch weitere Konfliktmeldungen geben (Sie merken schon – {{ branding.title }} will unter allen Umständen konsistente Daten sichern). Das System meldet dann: n DATENSÄTZE HATTEN FEHLER UND WURDEN VERWORFEN:

<!-- SCREENSHOT -->
![Abbildung: Die Einlese-Zusammenfassung mit einer Auflistung von Einlesefehlern]({{ img_url_desktop }}Adressbuch/8_adressbuch_mit_import_zusammenfassung_light.png#only-light){.desktop-img}
![Abbildung: Die Einlese-Zusammenfassung mit einer Auflistung von Einlesefehlern]({{ img_url_desktop }}Adressbuch/8_adressbuch_mit_import_zusammenfassung_dark.png#only-dark){.desktop-img}
![Abbildung: Die Einlese-Zusammenfassung mit einer Auflistung von Einlesefehlern]({{ img_url_mobile }}Adressbuch/8_adressbuch_mit_import_zusammenfassung_light.png#only-light){.mobile-img}
![Abbildung: Die Einlese-Zusammenfassung mit einer Auflistung von Einlesefehlern]({{ img_url_mobile }}Adressbuch/8_adressbuch_mit_import_zusammenfassung_dark.png#only-dark){.mobile-img}

Sie können wieder über das Aufklappen des Pluszeichens kontrollieren, welche Fehler aufgetreten sind: Dopplungen werden nach demselben Prinzip gefunden, wie oben bereits beschrieben. Wichtig: Hier wird immer der zweite Datensatz, in dem die Dopplung gefunden wurde, verworfen.

Überzeugen Sie sich nach dem Einlesen bitte durch ein paar Stichproben (mit Klick auf irgendeine Kontaktzeile), dass auch wirklich alle Daten an den richtigen Stellen sind (also keine Telefonnummern unter E-Mail o.ä.). Sollten Sie bei allen Stichproben und mit Regelmäßigkeit Fehler entdecken, haben Sie beim Anpassen der Struktur etwas übersehen und Tabellenspalten falsch benannt. Sie können die Datensätze über das gesetzte Tag filtern (siehe [Allgemeine Hinweise zur Bedienung](ca_StandardBedienhinweise.md)), anschließend löschen und den gesamten Vorgang, nach Korrektur in der Quelldatei, noch einmal wiederholen.

Nein, es gibt keine Fehler, alles ist an der richtigen Stelle? Gratulation!

Die erste große Hürde beim Einsatz von {{ branding.title }} haben Sie damit erfolgreich gemeistert – Ihre bisherigen Daten stehen Ihnen wieder zur Verfügung und Sie können jetzt damit arbeiten.


## Kontakte anzeigen, bearbeiten und filtern

In diesem Abschnitt soll es darum gehen, wie man Kontaktdaten anzeigen, bearbeiten und nach einzelnen und miteinander verketteten Kriterien filtern kann.

Öffnen Sie den Reiter Adressbuch und schauen Sie zunächst bitte nach links auf den Verzeichnisbaum. Dort können Sie unter ADRESSBÜCHER auswählen, welches Adressbuch Sie sich anzeigen lassen wollen. Oder Sie wählen unter FAVORITEN eine Ansicht über mehrere Adressbücher, beispielsweise Alle Kontakte.


### Die Tabellenansicht für Kontakte { data-ctx="/Addressbook/MainScreen/Contact/Grid" }

<!-- Tabellenkopfsymbol -->
Egal, wofür Sie sich entschieden haben: Sie sollten jetzt in der Tabelle eine Reihe von Kontakten sehen. Wenn Sie sich daran erinnern, wie wir weiter oben in diesem Kapitel die Importfunktion besprochen und Sie sich die Beispieldatei zum Kontakt-Import angesehen haben, wird Ihnen auffallen, dass es weit mehr Datenfelder in der Importtabelle gab, als jetzt auf Ihrem Bildschirm erscheinen. Klicken Sie darum am rechten Rand das kleine Tabellenkopfsymbol an!

<!-- SCREENSHOT -->
![Abbildung: Die ausgeklappte Leiste mit den Checkbuttons zur Auswahl als anzuzeigende Tabellenspalten]({{ img_url_desktop }}Adressbuch/9_adressbuch_mit_spaltenauswahl_light.png#only-light){.desktop-img}
![Abbildung: Die ausgeklappte Leiste mit den Checkbuttons zur Auswahl als anzuzeigende Tabellenspalten]({{ img_url_desktop }}Adressbuch/9_adressbuch_mit_spaltenauswahl_dark.png#only-dark){.desktop-img}
![Abbildung: Die ausgeklappte Leiste mit den Checkbuttons zur Auswahl als anzuzeigende Tabellenspalten]({{ img_url_mobile }}Adressbuch/9_adressbuch_mit_spaltenauswahl_light.png#only-light){.mobile-img}
![Abbildung: Die ausgeklappte Leiste mit den Checkbuttons zur Auswahl als anzuzeigende Tabellenspalten]({{ img_url_mobile }}Adressbuch/9_adressbuch_mit_spaltenauswahl_dark.png#only-dark){.mobile-img}

Dahinter werden Ihnen als Checkbuttons alle verfügbaren Felder angezeigt – es sind insgesamt 42 (allerdings werden aus Platzgründen, je nach Bildschirmauflösung, wahrscheinlich auch jetzt nicht alle angezeigt – dann können Sie mit den kleinen Pfeiltasten nach unten und oben scrollen)! Dabei dienen die letzten vier Felder (Erstelldatum, Erstellt von, Letztes Modifikationsdatum und Zuletzt geändert von) zum Dokumentieren des Bearbeitungsverlaufs des Kontaktdatensatzes; sie werden daher vom System automatisch ausgefüllt.

Diese Menge an Datenfeldern ist dem Industriestandard, hier vor allem MS Outlook, geschuldet, dass zwar in der Standardmaske auch nur einige wenige Felder anbietet, aber die Speicherung vieler zusätzlicher Daten vorhält, weil es – im Gegensatz zum hier viel flexibleren Open-Source-System {{ branding.title }} – als proprietäre Software das Definieren eigener zusätzlicher Felder nicht erlauben kann. In den meisten Fällen werden Sie diese vordefinierten Felder daher nicht benötigen, stattdessen lieber bei Bedarf auf selbst definierte Felder zurückgreifen. Schauen Sie sich dazu das Fallbeispiel zur Definition von Zusatzfeldern in [Administration - Zusatzfelder](oa_Administration.md/#zusatzfelder) an!

### Kontakte in der Kontaktmaske bearbeiten { data-ctx="/Addressbook/EditDialog/Contact" }

Nachdem Sie sich für eine Adressauswahl entschieden haben, klicken Sie bitte einen einzelnen Kontakt in der Tabellenansicht an! Es öffnet sich die Kontaktmaske mit diesem Kontakt. Das gleiche Ergebnis hätten Sie auch mit Markieren des Kontaktes und Klicken des Buttons Kontakt bearbeiten erzielt. Diese Prozedur funktioniert auch über mehrere Kontakte – dazu kommen wir weiter unten.

<!-- SCREENSHOT -->
![Abbildung: Die Eingabemaske zum Bearbeiten eines bestehenden Kontaktes]({{ img_url_desktop }}Adressbuch/10_adressbuch_kontakt_bearbeiten_light.png#only-light){.desktop-img}
![Abbildung: Die Eingabemaske zum Bearbeiten eines bestehenden Kontaktes]({{ img_url_desktop }}Adressbuch/10_adressbuch_kontakt_bearbeiten_dark.png#only-dark){.desktop-img}
![Abbildung: Die Eingabemaske zum Bearbeiten eines bestehenden Kontaktes]({{ img_url_mobile }}Adressbuch/10_adressbuch_kontakt_bearbeiten_light.png#only-light){.mobile-img}
![Abbildung: Die Eingabemaske zum Bearbeiten eines bestehenden Kontaktes]({{ img_url_mobile }}Adressbuch/10_adressbuch_kontakt_bearbeiten_dark.png#only-dark){.mobile-img}

Gehen wir zunächst in die Buttonleiste über den Reitern in der Kontaktmaske. Dort finden Sie (von links beginnend):

Adresse einlesen
: Obgleich Sie für den Test dieser Funktion auch den jetzt geöffneten vorhandenen Kontakt überschreiben könnten, wollen wir stattdessen einen neuen erzeugen. Schließen Sie dazu bitte den bereits gespeicherten Kontakt (unten rechts Abbrechen – ohne Speicherung, oder Ok – mit Speicherung) und öffnen Sie mit dem Button Kontakt hinzufügen rechts oben im Hauptmenü eine leere Kontaktmaske. Lassen Sie sich dabei fürs Erste nicht von dem kleinen Pfeil rechts an diesem Button irritieren – dahinter verbirgt sich ein Pulldown-Menü mit einigen anderen Funktionen zur Neuanlage von {{ branding.title }}-Objekten, die nichts mit dem Adressbuch zu tun haben und die wir daher später besprechen werden.

Starten Sie nun die Funktion Adresse einlesen mit Klick auf den Button über der Reiterleiste. Das System blendet Ihnen ein leeres Textfenster ein.

<!-- SCREENSHOT -->
![Abbildung: Kontakte können per Drag & Drop über die Zwischenablage bequem eingelesen werden.]({{ img_url_desktop }}Adressbuch/11_adressbuch_kontakt_neu_einlesen_light.png#only-light){.desktop-img}
![Abbildung: Kontakte können per Drag & Drop über die Zwischenablage bequem eingelesen werden.]({{ img_url_desktop }}Adressbuch/11_adressbuch_kontakt_neu_einlesen_dark.png#only-dark){.desktop-img}
![Abbildung: Kontakte können per Drag & Drop über die Zwischenablage bequem eingelesen werden.]({{ img_url_mobile }}Adressbuch/11_adressbuch_kontakt_neu_einlesen_light.png#only-light){.mobile-img}
![Abbildung: Kontakte können per Drag & Drop über die Zwischenablage bequem eingelesen werden.]({{ img_url_mobile }}Adressbuch/11_adressbuch_kontakt_neu_einlesen_dark.png#only-dark){.mobile-img}

Dort hinein können Sie über die Zwischenablage eine beliebige Adresse per Copy & Paste aus einer beliebigen Datei einlesen, beispielsweise aus einem Brief im PDF-Format. Probieren Sie das jetzt einmal aus! Wenn Sie gerade nichts Passendes parat haben, können Sie auch – nur um die Funktion zu demonstrieren – einfach eine Adresse in das Fenster eintippen. Auf die "genormte" Reihenfolge (Name / Straße Nr. / PLZ Ort) kommt es dabei gar nicht an – das ist gerade das Clevere an dieser Funktion! Wenn Sie OK drücken, liest {{ branding.title }} die Teile, die es unmissverständlich versteht (i.d.R. die PLZ und den Ort) gleich selbstständig in die entsprechenden Felder Ihres neuen Kontaktes ein. Was nicht automatisch zuzuordnen ist, wird als einzelne Zeichenketten rechts im Fenster Beschreibung zwischengeparkt. Von dort können Sie die einzelnen Teile ganz leicht mit der Maus "anfassen" (linke Taste gedrückt halten) und dann in das richtige Eingabefeld der Kontaktmaske ablegen. Achten Sie beim Navigieren auf den Cursor und das kleine Symbol links neben der Zeichenkette: Kreis mit einem X heißt "Feld nicht gefunden", Kreis mit einem Häkchen bedeutet "Gefunden, bereit zur Ablage" – lassen Sie dann einfach die Maustaste los! Wenn Sie fertig sind und noch einmal alles geprüft haben (es sollten sich keine Adressfragmente mehr im Feld Beschreibung befinden), schließen Sie die Funktion mit Klick auf den Button Beende Merkmalsmodus ab. Normalerweise müssen Sie jetzt natürlich auch noch Ihren neu angelegten Kontakt abspeichern.

Kontakt drucken
<div style="margin-left: 40px">
öffnet die Druckervorschau, von wo aus ein Druck in die Wege geleitet werden kann.
</div>

Exportiere Kontakt
<div style="margin-left: 40px">
öffnet ein Dropdown Menü mit vier Optionen:

* PDF - Details
* Word - Details
* Word - Brief
* Exportiere als ...

</div>

Die ersten beiden Optionen erzeugen ein Übersichtsblatt im PDF oder Word-Format mit allen gespeicherten Informationen zum betreffenden Kontakt.
Die Option Word - Brief öffnet ein Word-Dokument im Format eines Briefes. Hier wird der betreffende Kontakt im Briefkopf eingetragen.
Exportiere als ... bietet die Option, diesen Kontakt als CSV-, Excel- oder OpenDocument-Datei zu exportieren.

Damit hätten wir alle Funktionsbuttons für Kontakte besprochen. Als Nächstes beschäftigen wir uns mit den verschiedenen Ansichten, also der Reiterleiste im Kontaktmenü. Standardmäßig ist der Reiter Kontakt geöffnet, dessen Ansicht die Kontaktdaten selbst enthält. Nun gibt es noch die Reiter Karte, Termine, Notizen, Anhänge, Verknüpfungen und Historie:

<!--OpenStreetMap-->
Mit Klick auf Karte können Sie sich die geografische Lage des Kontaktes auf einer Landkarte anzeigen lassen. Dabei greift {{ branding.title }} auf den Internet-Kartendienst von OpenStreetMap zu.

<!-- SCREENSHOT -->
![Abbildung: Das Anzeigen der Adresse mittels OpenStreetMap.]({{ img_url_desktop }}Adressbuch/12_adressbuch_kontakt_karte_light.png#only-light){.desktop-img}
![Abbildung: Das Anzeigen der Adresse mittels OpenStreetMap.]({{ img_url_desktop }}Adressbuch/12_adressbuch_kontakt_karte_dark.png#only-dark){.desktop-img}
![Abbildung: Das Anzeigen der Adresse mittels OpenStreetMap.]({{ img_url_mobile }}Adressbuch/12_adressbuch_kontakt_karte_light.png#only-light){.mobile-img}
![Abbildung: Das Anzeigen der Adresse mittels OpenStreetMap.]({{ img_url_mobile }}Adressbuch/12_adressbuch_kontakt_karte_dark.png#only-dark){.mobile-img}

Wenn dieser Reiter ausgegraut ist, kann das mehrere Ursachen haben:

* Entweder, und das ist der häufigste Grund, ist die Adresse nicht korrekt eingegeben. Achten Sie auf die Belegung der Felder Straße, Postleitzahl und Ort. Sofern diese Angaben Tippfehler enthalten, kann {{ branding.title }} die gesuchte Adresse in der OpenStreetMap-Datenbank nicht lokalisieren und graut dann den Reiter aus.

* Oder {{ branding.title }} hat auf diesen Dienst keinen Zugriff, z.B. weil Sie auf einem lokalen System ohne Internet-Zugang arbeiten. Fragen Sie Ihren zuständigen System-Administrator!

<a id="ctx:Addressbook.EditDialog.Contact"></a>
Der Reiter Termine bietet eine Verknüpfung zum Kalender von {{ branding.title }}. Hier werden alle Termine des jeweiligen Kontakts angezeigt. Auch hier kann man das Filtersystem von {{ branding.title }} nutzen. Die Funktionen des Kalenders werden ausführlich in [Kalender](da_Kalender.md) erläutert.

<a id="ctx:Addressbook.EditDialog.Contact.NotesGrid"></a>
Notizen: {{ branding.title }} kennt drei Arten von Notizen: einfache Notizen (Klebezettel), Telefonnotizen und E-Mail-Notizen. Durch den Klick auf dem Button Notizen hinzufügen öffnet sich ein Fenster für die Notiz. Standardmäßig werden Einfache Notizen erzeugt. Die anderen beiden Arten erreichen Sie über das Pulldown-Menü (den kleinen Pfeil neben Notiz). Alle Notizen erhalten mit Speicherung automatisch einen Datums- und Zeiteintrag. Telefonnotizen dienen der Notierung von wichtigen Telefonaten mit diesem Kontakt. Um E-Mail-Notizen nutzen zu können, muss dieses Feature eingeschaltet sein. Sofern das der Fall ist, kann so ein Vermerk hinterlegt werden, dass der Kontakt eine E-Mail erhalten hat. Letztere müssen Sie hier an dieser Stelle nicht händisch eingeben, wenn Sie beim Schreiben von E-Mails daran denken, dort den Button E-Mail-Notiz anlegen zu klicken. In [Benutzerspezifische Einstellungen](na_Benutzereinstellungen.md) zeigen wir, wie dieser Vorgang als Automatismus angelegt wird. Das Arbeiten mit Notizen ist insbesondere bei externen Kontakten sehr bedeutsam: So kann jeder Mitarbeiter nachvollziehen, was mit diesem externen Kontakt zu welcher Zeit besprochen wurde. Hier weist {{ branding.title }} eine der wichtigsten Funktionen eines CRM-Systems auf.

Erwähnenswert ist noch, dass jegliche Notizen, die hier angelegt werden, nicht veränderbar sind. Grund dafür ist, dass dieses Feature ursprünglich aus der Historie (siehe dazu [Allgemeine Hinweise zur Bedienung - Historie](ca_StandardBedienhinweise.md/#historie)) kommt, welche per Definition nicht bearbeitbar sind.

!!! info "Wichtig"
    Sollten Sie E-Mail-Notizen aktiviert haben, denken Sie an den Datenschutz. Hierdurch kann jeder User, der Zugang zu dem Kontakt hat, den E-Mail-Verkehr mitschneiden.

<a id="ctx:Addressbook.EditDialog.Contact.AttachmentsGrid"></a>
Der Reiter Anhänge dient dem Zuordnen von beliebigen Dateien zu einem Datensatz; eine Funktion, die Ihnen im Prinzip zu allen Datenbank-Objekten in {{ branding.title }} angeboten wird. Wir behandeln sie ausführlich in [ Allgemeine Hinweise und Bedienung - Anhänge](ca_StandardBedienhinweise.md/#anhange).

<a id="ctx:Addressbook.EditDialog.Contact.HistoryGrid"></a>
An dieser Stelle möchten wir kurz den Reiter Historie vorziehen, da der Bereich Verknüpfungen: sehr umfangreich ist.

In Historie finden Sie Einträge über die Erstellung und Bearbeitung des betreffenden Kontakts. Wurde beispielsweise ein Datenfeld geändert, so können Sie nachvollziehen, was wie von welchem Benutzer zu welchem Zeitpunkt geändert wurde. Die Historie selber ist vor Änderungen geschützt, weshalb eine Bearbeitung dieser Einträge nicht möglich ist.

<a id="ctx:Addressbook.EditDialog.Contact.RelationsGrid"></a>
Kommen wir nun zu den Verknüpfungen: Wie wir bei den Notizen schon besprochen haben und später mit anderen Objekten noch sehen werden, kann {{ branding.title }} verschiedene Arten von Daten sinnvoll miteinander verknüpfen. Öffnen Sie dazu den Reiter Verknüpfungen. Wenn Ihre Version von {{ branding.title }} noch ganz "frisch" ist, werden Sie jetzt eine leere Tabelle sehen. Oben links, direkt unter der Reiterleiste, sehen Sie ein Pulldown-Menü, das auf Kontakt (Adressbuch) steht. Klicken Sie es einmal an.

<!-- SCREENSHOT -->
![Eine Verknüpfung eines Adresseintrags mit einem Objekt einer anderen Anwendung erstellen]({{ img_url_desktop }}1_adressverwaltung/13_adressbuch_kontakt_bearbeiten_verknuepfung_links_light.png#only-light){.desktop-img}
![Eine Verknüpfung eines Adresseintrags mit einem Objekt einer anderen Anwendung erstellen]({{ img_url_desktop }}1_adressverwaltung/13_adressbuch_kontakt_bearbeiten_verknuepfung_links_dark.png#only-dark){.desktop-img}
![Eine Verknüpfung eines Adresseintrags mit einem Objekt einer anderen Anwendung erstellen]({{ img_url_mobile }}1_adressverwaltung/13_adressbuch_kontakt_bearbeiten_verknuepfung_links_light.png#only-light){.mobile-img}
![Eine Verknüpfung eines Adresseintrags mit einem Objekt einer anderen Anwendung erstellen]({{ img_url_mobile }}1_adressverwaltung/13_adressbuch_kontakt_bearbeiten_verknuepfung_links_dark.png#only-dark){.mobile-img}

Die Auswahl enthält alle anderen {{ branding.title }}-Objekte, mit denen Kontakte sinnvoll verknüpft werden können, wie Mitarbeiter (HumanResources), Zeitkonto (Zeiterfassung), Inventar Gegenstand (Inventarisierung) usw. Nur ein Beispiel soll jetzt genügen; wir gehen im Laufe der weiteren Beschreibung immer wieder auf Verknüpfungen ein: Sie können natürlich auch Kontakte mit anderen Kontakten verknüpfen, um damit beispielsweise eine Lieferantenkette darzustellen. Stellen Sie die Auswahl links dazu wieder auf Kontakt (Adressbuch). Klicken Sie das rechte Pulldown-Menü an. Es werden Ihnen alle verfügbaren Kontakte in einer auf den ersten Blick eher "chaotischen" Reihenfolge angezeigt.

<!-- SCREENSHOT -->
![Abbildung: Verknüpfung eines Kontaktes mit einem anderen Kontakt]({{ img_url_desktop }}1_adressverwaltung/14_adressbuch_kontakt_bearbeiten_verknuepfung_rechts_light.png#only-light){.desktop-img}
![Abbildung: Verknüpfung eines Kontaktes mit einem anderen Kontakt]({{ img_url_desktop }}1_adressverwaltung/14_adressbuch_kontakt_bearbeiten_verknuepfung_rechts_dark.png#only-dark){.desktop-img}
![Abbildung: Verknüpfung eines Kontaktes mit einem anderen Kontakt]({{ img_url_mobile }}1_adressverwaltung/14_adressbuch_kontakt_bearbeiten_verknuepfung_rechts_light.png#only-light){.mobile-img}
![Abbildung: Verknüpfung eines Kontaktes mit einem anderen Kontakt]({{ img_url_mobile }}1_adressverwaltung/14_adressbuch_kontakt_bearbeiten_verknuepfung_rechts_dark.png#only-dark){.mobile-img}

Nicht verzweifeln! Geben Sie einfach mindestens drei Buchstaben eines zu suchenden Kontaktes ein, und das System beginnt selbständig zu filtern. Wichtig: Das ist eine Volltextsuche, und sie beginnt erst bei drei Zeichen! Probieren Sie es einfach aus: Wenn Sie den richtigen Kontakt gefunden haben, klicken Sie ihn an, und die Verknüpfung ist hergestellt. So können Sie beliebig viele Verknüpfungen anlegen. Ein Klick mit der rechten Maustaste auf einen Verknüpfungseintrag in der Tabelle öffnet ein Kontextmenü, mit dem Sie den verknüpften Datensatz entweder bearbeiten oder die Verknüpfung wieder lösen können.

Nun weiter zu den eigentlichen Adressfeldern. Gehen Sie dazu zurück auf den Reiter Kontakt: Sie finden neben den Feldern, in die Sie frei Daten eingeben können, einige Felder (Anrede, Branche, Land) mit Pulldown-Menüs, die Ihnen eine Vorauswahl anbieten. Das ist kein Hexenwerk, und auch Geburtstag (mit Kalender-Auswahl) oder die Möglichkeit, ein Foto zu speichern (Zum Bearbeiten klicken) wird Ihnen keine Rätsel aufgeben. Spannender sind da schon die Felder rechts außen: Beschreibung und Tags.
Beschreibung ist standardmäßig geöffnet. Hier können Sie beliebigen Text zur "verbalen" Beschreibung des Kontaktes eingeben. Erinnern Sie sich? Dieses Feld haben wir vorhin bei der Funktion Adresse einlesen schon einmal zur Zwischenablage unserer Zeichenketten benutzt. Wir hatten Ihnen abschließend empfohlen, darauf zu achten, dass es nach Beendigung der Prozedur leer ist – nun wird klar, warum. Hier wollen wir ja Platz für Beschreibungen zum Kontakt haben – keine halben Adressfragmente.

<!--Tags-->
Zu den _Tags_ haben wir in [Kontakte importieren](ba_Adressbuch.md/#kontakte-importieren) schon etwas gesagt. Hier können Sie sich die zu einem Kontakt gespeicherten Tags auflisten lassen. Wenn Sie, wie in [Kontakte importieren](ba_Adressbuch.md/#kontakte-importieren) beschrieben, den gerade aufgerufenen Kontakt aus einem anderen System importiert haben, sollten Sie jetzt zumindest einen Tag Importliste (Importdatum) sehen. Hinter dem Feld Tag Name verbirgt sich ein Pulldown mit allen bereits vorhandenen Tags, die Sie dem Kontakt über diese Auswahlliste zuweisen können:

<!-- SCREENSHOT -->
![Abbildung: Einen Kontakt mit einem vorhandenen Tag markieren]({{ img_url_desktop }}Adressbuch/15_adressbuch_tag_hinzu_light.png#only-light){.desktop-img}
![Abbildung: Einen Kontakt mit einem vorhandenen Tag markieren]({{ img_url_desktop }}Adressbuch/15_adressbuch_tag_hinzu_dark.png#only-dark){.desktop-img}
![Abbildung: Einen Kontakt mit einem vorhandenen Tag markieren]({{ img_url_mobile }}Adressbuch/15_adressbuch_tag_hinzu_light.png#only-light){.mobile-img}
![Abbildung: Einen Kontakt mit einem vorhandenen Tag markieren]({{ img_url_mobile }}Adressbuch/15_adressbuch_tag_hinzu_dark.png#only-dark){.mobile-img}


Über das + können Sie hier einzeln neue persönliche Tags definieren und dem Kontakt zuweisen.[^1]

[^1]:
    Die Funktion der persönlichen Tags kann vom Admin eingeschränkt werden, dazu mehr im [Administration - Rollen](oa_Administration.md/#rollen).

<!-- SCREENSHOT -->
![Abbildung: Das Hinzufügen eines persönlichen Tags.]({{ img_url_desktop }}Adressbuch/16_adressbuch_persoenlicher_tag_hinzu_light.png#only-light){.desktop-img}
![Abbildung: Das Hinzufügen eines persönlichen Tags.]({{ img_url_desktop }}Adressbuch/16_adressbuch_persoenlicher_tag_hinzu_dark.png#only-dark){.desktop-img}
![Abbildung: Das Hinzufügen eines persönlichen Tags.]({{ img_url_mobile }}Adressbuch/16_adressbuch_persoenlicher_tag_hinzu_light.png#only-light){.mobile-img}
![Abbildung: Das Hinzufügen eines persönlichen Tags.]({{ img_url_mobile }}Adressbuch/16_adressbuch_persoenlicher_tag_hinzu_dark.png#only-dark){.mobile-img}


Das Definieren gemeinsamer Tags erfolgt nicht hier, sondern nur in der Admin-Anwendung (siehe [Administration - Gemeinsame Tags](oa_Administration.md/#gemeinsame-tags)), da Sie dazu die entsprechende Berechtigung haben müssen.

Wenn Sie einen Tag in der Liste mit Rechtsklick anklicken, bietet Ihnen ein Kontextmenü die Möglichkeit, den Tag von diesem Kontakt zu entfernen, ihn (sofern es sich um einen Ihrer eigenen Tags handelt) zu bearbeiten, oder ihn ganz zu löschen. Sollte Ihnen die Bedeutung von Tags jetzt noch nicht ganz einleuchten – keine Bange! Wir werden uns im Weiteren mit den Sortier- und Gruppiermöglichkeiten von Daten (u.a. auch von Kontaktdaten) in {{ branding.title }} praktisch beschäftigen. Und spätestens dann werden Sie sehen, welch sinnvolle Erfindung Tags sind.

### Kontakte aus der Tabellenansicht heraus bearbeiten

Im Hauptfenster des Adressbuchs werden die gespeicherten Kontakte in Form einer Tabelle angezeigt. Das Aussehen, d.h. welche Tabellenspalten (Kontaktdatenfelder) angezeigt werden und in welcher Sortierung, steuern Sie einfach mit Mausklicks:

* Ein Klick auf das entsprechende Feld im Tabellenkopf aktiviert dieses Feld als Sortierkriterium; ein weiterer Klick kehrt die Sortierung um.

* Die Breite der Tabellenspalten justieren Sie (wie in einem Tabellenkalkulationsprogramm) mit der Maus.

* Gehen Sie in der Tabellenzeile mit den Bezeichnungen ganz nach rechts außen – dort finden Sie einen Knopf mit einem kleinen Tabellensymbol <img src="{{icon_url}}icon_column.svg" alt="drawing" width="16"/>. Klicken Sie ihn an, erlaubt Ihnen das aufklappende Checkbutton-Menü, die anzuzeigenden Tabellenspalten (Kontaktdatenfelder) auszuwählen.

* Beachten Sie, dass Ihr aktives Fenster (auch mit Scrollen) standardmäßig immer nur 50 Kontakte anzeigt (wie Sie den möglichen Wert zwischen 15 und 100 variieren, beschreibt [Benutzerspezifische Einstellungen](na_Benutzereinstellungen.md)). Links oberhalb des Tabellenkopfes springen Sie mit den Pfeiltasten zu den nächsten (vorhergehenden <img src="{{icon_url}}icon_arrow_left.svg" alt="drawing" width="16"/>, nächsten <img src="{{icon_url}}icon_arrow_right.svg" alt="drawing" width="16"/>, letzten <img src="{{icon_url}}icon_arrow_action_last.svg" alt="drawing" width="16"/> oder ersten <img src="{{icon_url}}icon_arrow_back_last.svg" alt="drawing" width="16"/>) 50 Kontakten.


Kommen wir nun zur Bearbeitung von Kontakten aus der Tabelle heraus: Oben links, unterhalb der Reiterleiste, finden Sie eine Reihe von Buttons (Kontakt hinzufügen usw.), deren Funktionen teilweise selbsterklärend sind; einige (Kontakt bearbeiten) haben wir schon besprochen. Bis auf Kontakt hinzufügen und Seite drucken beziehen sich diese Funktionen immer auf die markierten Kontakte. Natürlich können Sie auch mehrere Kontakte auswählen. Probieren Sie jetzt einmal aus, was passiert, wenn Sie mehrere Kontakte markieren (__Ctrl[]__ und Mausklick für einzelne, __Shift[]__ und Mausklick für Gruppen) und dann Nachricht verfassen wählen: Folgerichtig öffnet sich ein E-Mail-Fenster, in dem alle markierten Kontakte als Empfänger eingetragen sind.

<!-- SCREENSHOT -->
![Abbildung: Versenden von Massenmails über Filterkriterien]({{ img_url_desktop }}Adressbuch/17_adressbuch_email_viele_empfaenger_light.png#only-light){.desktop-img}
![Abbildung: Versenden von Massenmails über Filterkriterien]({{ img_url_desktop }}Adressbuch/17_adressbuch_email_viele_empfaenger_dark.png#only-dark){.desktop-img}
![Abbildung: Versenden von Massenmails über Filterkriterien]({{ img_url_mobile }}Adressbuch/17_adressbuch_email_viele_empfaenger_light.png#only-light){.mobile-img}
![Abbildung: Versenden von Massenmails über Filterkriterien]({{ img_url_mobile }}Adressbuch/17_adressbuch_email_viele_empfaenger_dark.png#only-dark){.mobile-img}

Sie wollten immer schon einmal wissen, wie Spammer arbeiten? Markieren Sie das gesamte Adressbuch (mit dem kleinen Pulldown-Menü n ausgewählt rechts am Rand, oberhalb des Tabellenkopfes, indem Sie die Option Alle Seiten auswählen verwenden) und drücken Sie auf E-Mail verfassen... Aber bitte nicht abschicken! Zum E-Mail-Fenster kommen wir weiter unten noch detailliert.

Ebenfalls eine wichtige und häufig verwendete Massenbearbeitungsfunktion ist das Arbeiten mit Tags. Markieren Sie hierzu noch einmal eine beliebige Anzahl von Kontaktdatensätzen in der Tabelle und rufen Sie dann, mit dem Mauszeiger auf einem der markierten Objekte, über einen Rechtsklick das Kontextmenü auf. Wählen Sie Tag hinzufügen:

<!-- SCREENSHOT -->
![Abbildung: Einen Tag auswählen und mehreren markierten Kontakten zuweisen]({{ img_url_desktop }}Adressbuch/18_adressbuch_kontakten_tags_zuweisen_light.png#only-light){.desktop-img}
![Abbildung: Einen Tag auswählen und mehreren markierten Kontakten zuweisen]({{ img_url_desktop }}Adressbuch/18_adressbuch_kontakten_tags_zuweisen_dark.png#only-dark){.desktop-img}
![Abbildung: Einen Tag auswählen und mehreren markierten Kontakten zuweisen]({{ img_url_mobile }}Adressbuch/18_adressbuch_kontakten_tags_zuweisen_light.png#only-light){.mobile-img}
![Abbildung: Einen Tag auswählen und mehreren markierten Kontakten zuweisen]({{ img_url_mobile }}Adressbuch/18_adressbuch_kontakten_tags_zuweisen_dark.png#only-dark){.mobile-img}

Über das Pulldown werden Ihnen alle für Sie gültigen, d.h. sowohl die gemeinsamen als auch die persönlichen, Tags angeboten. Sie können nur jeweils einen Tag je Arbeitsgang zuweisen. Rückwärts funktioniert dies mit Tag(s) entfernen analog, nur dass Sie hier über Checkbuttons mehrere Tags zum Entfernen auswählen können.

!!! warning "Warnung"
    Das Bearbeitungs- wie auch das Kontextmenü bieten weitere Funktionen zum Bearbeiten über mehrere Datensätze an. Wenn Sie die Mehrfachauswahl z.B. auf Kontakte bearbeiten anwenden, erhalten Sie eine Maske, in der die Felder, die bei Ihren ausgewählten Kontakten jeweils verschiedene Inhalte haben können, leer und ockerfarbig dargestellt sind. Lassen Sie sich nicht verwirren – das ist logisch: Was sollte {{ branding.title }} Ihnen sonst hier anzeigen? Das Ganze hat jedoch einen tieferen Sinn: Sie können damit Datenfelder über mehrere Datensätze mit einem gemeinsamen Inhalt füllen – eine sehr mächtige Funktion, wenn Sie diese mit Bedacht anwenden. Also denken Sie bitte daran, dass Sie ggf. alte Feldinhalte überschreiben, die Sie hier gar nicht sehen! Zur Sicherheit erhalten Sie beim Hineinklicken in ein solches Feld (korrekterweise: mehrere Felder!) noch einmal eine Warnung.

<!--Kontaktdaten,Export-->
Über den Button Kontakte importieren haben wir schon unter [Kontakte importieren](ba_Adressbuch.md/#kontakte-importieren) gesprochen, bliebe noch Kontakte exportieren: Hier werden Ihnen, wieder bezogen auf die markierten Kontakte, diverse Exportformate angeboten: PDF, das schon besprochene CSV, aber auch ODS (das Format der Open- oder LibreOffice-Systeme), sowie MS Excel. Beachten Sie hier, analog zum oben Gesagten, dass das Datenformat zwar zu Ihrer Zielsoftware passen muss, die wichtigere Überlegung aber die Reihenfolge der Datenfelder (d.h. der Tabellenspalten) ist, die Sie vor dem Einlesen auf Übereinstimmung mit der Zielsoftware prüfen sollten.

### Das E-Mail-Fenster und seine Funktionen

Dem Arbeiten mit dem E-Mail-Client von {{ branding.title }} widmet sich [E-Mail](ea_EMail.md) – wir beschränken uns darum hier auf die Funktion "E-Mail versenden aus der Adressverwaltung", damit Sie jetzt nicht im Buch springen müssen.

Markieren Sie sich jetzt bitte einen Kontakt und klicken Sie Nachricht verfassen. Es öffnet sich das Fenster des E-Mail-Editors.

Oben finden Sie einige Buttons: Senden und Abbrechen sind klar, Datei hinzufügen öffnet einen Dialog, mit dem Sie Datei-Anhänge auswählen können – was Sie sicher auch schon von anderen Programmen kennen. Mit Suche Empfänger können Sie beliebig viele weitere Empfänger für Ihre E-Mail hinzunehmen. Dazu wird Ihnen die schon beschriebene Kontakttabelle eingeblendet. Hier können Sie Kontakte durch Auswahl mit der Maus oder durch Filteralgorithmen bestimmen. Wie der hier eingeblendete Such- und Filteralgorithmus bedient wird, erläutert [Kontakte filtern](ba_Adressbuch.md/#kontakte-filtern).

Als Entwurf speichern und Als Vorlage speichern erzeugen in den jeweiligen Ordnern Ihres E-Mail-Servers (wenn diese dort angelegt sind) einen Eintrag. Über {{ branding.title }} kommen Sie an diese Einträge heran, wenn Sie die Anwendung E-Mail aufrufen. Voraussetzung ist natürlich, dass das betreffende E-Mail-Konto als IMAP-Konto ordnungsgemäß und mit den entsprechenden Ordnern bei Ihrem E-Mail-Provider angelegt und auch bei {{ branding.title }} eingerichtet wurde ([E-Mail](ea_EMail.md)). Ein Klick auf Lesebestätigung bewirkt, dass Sie vom Empfänger eine Bestätigungsmail erhalten, wenn er Ihre E-Mail gelesen hat. Auch diese Funktion dürfte Ihnen bekannt sein, wenn Sie schon mit anderen E-Mail-Programmen gearbeitet haben.

Interessant ist Nachricht ablegen. Mit einem Klick auf den Menüpunkt legen sie eine E-Mail Notiz an. Sie erinnern sich bestimmt an [Kontakte in der Kontaktmaske bearbeiten](ba_Adressbuch.md/#kontakte-in-der-kontaktmaske-bearbeiten), wo wir bereits von Notizen geredet haben – und hier ist nun die Funktion. Wenn Sie den Button anklicken, bevor Sie die E-Mail abschicken, wird bei allen Kontakten, an die die E-Mail versandt wird, eine Notiz dazu angelegt. Eine sehr sinnvolle Funktion, wenn Sie für sich und andere den Überblick darüber behalten wollen, wann und worüber Sie mit dem entsprechenden Kontakt kommuniziert haben. Im Nachhinein sehen Sie die E-Mail dann als Notiz in Ihrem Kontaktdatensatz:

<!-- SCREENSHOT -->
![Abbildung: Dem Empfänger einer E-Mail diese als Notiz zuweisen.]({{ img_url_desktop }}Adressbuch/20_adressbuch_email_als_notiz_light.png#only-light){.desktop-img}
![Abbildung: Dem Empfänger einer E-Mail diese als Notiz zuweisen.]({{ img_url_desktop }}Adressbuch/20_adressbuch_email_als_notiz_dark.png#only-dark){.desktop-img}
![Abbildung: Dem Empfänger einer E-Mail diese als Notiz zuweisen.]({{ img_url_mobile }}Adressbuch/20_adressbuch_email_als_notiz_light.png#only-light){.mobile-img}
![Abbildung: Dem Empfänger einer E-Mail diese als Notiz zuweisen.]({{ img_url_mobile }}Adressbuch/20_adressbuch_email_als_notiz_dark.png#only-dark){.mobile-img}

Mit einem Klick auf kleinen Pfeil neben Nachricht ablegen öffnet sich ein Dropdown-Menü. Die erste Checkbox würde wiederum eine E-Mail-Notiz bei dem Kontakt anlegen. Die Option Dateimanager bietet die Möglichkeit, die E-Mail an eine gewünschte Stelle im Datenmanager zu sichern. Mit Anhang kann die E-Mail z.B. mit Lieferanten oder einer Rechnung verlinkt werden.

Kommen wir zu den Versende-Daten für Ihre E-Mail: {{ branding.title }} hat keinen eigenen E-Mail-Server. Stattdessen können Sie (oder Ihr Administrator) Ihr E-Mail-Konto (oder auch mehrere) in {{ branding.title }} anlegen, ganz so wie Sie es vielleicht aus anderen E-Mail-Programmen, wie MS Outlook (wenn es ohne Exchange-Server installiert ist), Thunderbird oder Evolution kennen. Sie finden die Erläuterungen dazu in [Administration](oa_Administration.md). Somit bietet das E-Mail-Fenster von {{ branding.title }} zunächst oben (neben Von) ein Pulldown-Menü an, in dem Sie aus den für Sie im System verfügbaren E-Mail-Adressen auswählen können.

<!-- SCREENSHOT -->
![Abbildung: Empfänger aus dem Adressbuch auswählen]({{ img_url_desktop }}Adressbuch/21_adressbuch_email_empfaenger_adressbuchliste_light.png#only-light){.desktop-img}
![Abbildung: Empfänger aus dem Adressbuch auswählen]({{ img_url_desktop }}Adressbuch/21_adressbuch_email_empfaenger_adressbuchliste_dark.png#only-dark){.desktop-img}
![Abbildung: Empfänger aus dem Adressbuch auswählen]({{ img_url_mobile }}Adressbuch/21_adressbuch_email_empfaenger_adressbuchliste_light.png#only-light){.mobile-img}
![Abbildung: Empfänger aus dem Adressbuch auswählen]({{ img_url_mobile }}Adressbuch/21_adressbuch_email_empfaenger_adressbuchliste_dark.png#only-dark){.mobile-img}

Die Zeile An enthält schon eine Zieladresse, wenn Sie den E-Mail-Editor nach Auswahl einer oder mehrerer Kontakte in der Tabelle aufgerufen haben; andernfalls ist die Zeile leer. Direkt rechts neben An gibt es ein kleines Pulldown-Menü; es dient der Auswahl, ob die Adresse direkt, als Cc oder als Bcc angeschrieben werden soll.

Das Cc (_Carbon Copy_) und das Bcc zeigen dem Empfänger, dass er nicht Hauptempfänger einer Nachricht ist, sondern sie nur informativ erhält; dabei sieht er allerdings in der Nachricht, wer der Hauptempfänger ist. Der Unterschied zwischen Cc und Bcc (_Blind carbon copy_) ist, dass keiner der anderen E-Mail-Empfänger den oder die weiteren Bcc-Empfänger sieht. Diese Funktion eignet sich daher besonders zum Versenden von Mails an mehrere Empfänger unter Einhaltung des Datenschutzes, das heißt, ohne dass jeder Empfänger alle anderen Mailadressen der Empfängerliste mitgeliefert bekommt. Denken Sie daran, wenn Sie einmal eine Massen-E-Mail versenden – wie schnell können Sie sich durch Unachtsamkeit hier eine Menge Ärger einhandeln. Das muss nicht sein!

Dass eine E-Mail einen Betreff enthalten sollte, ist sicher nichts Neues ({{ branding.title }} warnt Sie übrigens, wenn Sie eine E-Mail ohne Betreff abschicken wollen). Zur Auswahl stehen dann noch eine Reihe von Gestaltungselementen, wie Sie sie von anderen Editoren
auch kennen. Praktisch ist der kleine Radiergummi ganz rechts außen, genannt Formatierungen entfernen: Er dient dazu, lästige Objekte, wie Hyperlinks, Unterstreichungen, verschiedene Schriftgrößen usw. zu tilgen, wenn diese, z.B. durch Drag&Drop aus anderen Quellen, in den E-Mail-Text hineingeraten sind und dort nichts zu suchen haben.

Wenn Sie oder Ihr Administrator beim Anlegen Ihrer E-Mail-Konten eine E-Mail-Signatur gespeichert haben ([E-Mail](ea_EMail.md)), so erscheint diese im Text der E-Mail automatisch nach Auswahl der E-Mail-Adresse, von der aus Sie die E-Mail versenden wollen. Sofern Sie über mehrere Signaturen verfügen, können diese unter dem Punkt Signatur geändert werden. Die Signatur muss vor dem Schreiben der Mail gewählt werden, ein nachträgliches Ändern der Signatur ist nicht möglich.

### Kontakte filtern { data-ctx="/Addressbook/MainScreen/Contact/Grid/FilterToolbar" }
Damit sind alle "einfachen" Funktionen des Adressbuchs besprochen. Kommen wir nun zu den komplexen Auswahlalgorithmen. Sicher können Sie sich vorstellen, dass in einem Unternehmen, das über mehrere Standorte tätig ist und ein paar hundert Mitarbeiter hat, schnell mehrere tausend Kontaktdaten zusammenkommen. Nun hatten wir Ihnen versprochen, dass Sie die Übersicht auch dann behalten, wenn es nur wenige Adressbücher gibt. Dafür hat {{ branding.title }} die Filterfunktionen vorgesehen:

Wie schon weiter oben bemerkt, besitzt {{ branding.title }} mächtige Werkzeuge zur Anzeige und Filterung von Daten, dem sog. _Data Mining_. Wiewohl Data-Mining unserer Erfahrung nach von den meisten {{ branding.title }}-Anwendern mit Adressdaten betrieben wird (es gibt Nutzer, die Millionen Datensätze damit verwalten!), können Sie es nach den gleichen Prinzipien auch mit allen anderen in Ihrer {{ branding.title }}-Datenbank gespeicherten Daten umsetzen. Daher erklären wir das Filtern hier an dieser Stelle nicht näher, sondern verweisen auf das [ Allgemeine Hinweise zur Bedienung - Suchfilter für die Tabellenansicht](ca_StandardBedienhinweise.md/#suchfilter-fur-die-tabellenansicht).

<!--Gruppen-->
<!--Listen-->
## Gruppen

{{ branding.title }} hat zwei verschiedene Arten von Gruppen. Systemgruppen <img src="{{icon_url}}icon_group_full.svg" alt="drawing" width="16"/> und Gruppen <img src="{{icon_url}}icon_address_group.svg" alt="drawing" width="16"/>, wobei beide vereinfacht in {{ branding.title }} als "Gruppe" dargestellt werden und sich nur anhand des Symbols unterscheiden lassen.
<!-- `<img src="../../../../{{ branding.title }}20/images/icon-set/icon_address_group.svg" alt="drawing"/>` -->

<!-- SCREENSHOT -->
![Abbildung: Gruppen Übersicht]({{ img_url_desktop }}Adressbuch/22_adressbuch_gruppen_uebersicht_light.png#only-light){.desktop-img}
![Abbildung: Gruppen Übersicht]({{ img_url_desktop }}Adressbuch/22_adressbuch_gruppen_uebersicht_dark.png#only-dark){.desktop-img}
![Abbildung: Gruppen Übersicht]({{ img_url_mobile }}Adressbuch/22_adressbuch_gruppen_uebersicht_light.png#only-light){.mobile-img}
![Abbildung: Gruppen Übersicht]({{ img_url_mobile }}Adressbuch/22_adressbuch_gruppen_uebersicht_dark.png#only-dark){.mobile-img}

Systemgruppen <img src="{{icon_url}}icon_group_full.svg" alt="drawing" width="16"/> werden ausführlich im [Administration - Gruppen](oa_Administration.md/#gruppen) erklärt. Es handelt sich hierbei um eine Funktion der Rechteverteilung. Einzelne User können in Systemgruppen zusammengefasst werden, unterschiedlichen Systemgruppen können verschiedene Rechte und Rollen zugewiesen werden.

Bei einfachen Gruppen <img src="{{icon_url}}icon_address_group.svg" alt="drawing" width="16"/> geht es nicht um die Rechteverteilung. Hier können vielmehr einzelne Kontakte, egal ob diese Systembenutzer sind oder nicht, in Gruppen zusammengelegt werden.

Für den übrigen Teil dieses Kapitels wird der Begriff "Gruppe" für die Gruppenart, die mit <img src="{{icon_url}}icon_address_group.svg" alt="drawing" width="16"/> gekennzeichnet ist, verwendet.

Um eine Gruppe einzurichten, muss man sich innerhalb des Adressbuches im Gruppenmodul befinden.

<!-- SCREENSHOT -->
![Abbildung: Gruppen Übersicht]({{ img_url_desktop }}Adressbuch/23_adressbuch_gruppen_modul_light.png#only-light){.desktop-img}
![Abbildung: Gruppen Übersicht]({{ img_url_desktop }}Adressbuch/23_adressbuch_gruppen_modul_dark.png#only-dark){.desktop-img}
![Abbildung: Gruppen Übersicht]({{ img_url_mobile }}Adressbuch/23_adressbuch_gruppen_modul_light.png#only-light){.mobile-img}
![Abbildung: Gruppen Übersicht]({{ img_url_mobile }}Adressbuch/23_adressbuch_gruppen_modul_dark.png#only-dark){.mobile-img}

Klicken Sie hier oben links auf Gruppe hinzufügen. Es öffnet sich ein neues Fenster, wo Sie die gewünschten Gruppeninformationen eingeben können.

Das Feld Name dient zur Benennung der Gruppe. Um E-Mail nutzen zu können, müssen auf Seiten des Mailservers Sieve-Dienste eingerichtet sein. Sofern dies der Fall ist, kann hier eine E-Mail-Adresse für die Gruppe vergeben werden. Diese Gruppe wird so auch zu einem E-Mail-Verteiler. Es muss nur an die Gruppen-E-Mail-Adresse eine E-Mail geschrieben werden, und jeder einzelne Kontakt in dieser Gruppe bekommt eine Kopie in sein persönliches Postfach.

Sollte Sie E-Mail leer lassen, funktioniert die Gruppe wie eine normale Gruppe. Sie ist also eine Gruppierung verschiedener Kontakte und dient zur Sortierung und/oder schnellen Zuweisung von Terminen, E-Mails, etc.

Listen Typ dient ausschließlich der Übersichtlichkeit. Hier kann man über das Dropdown-Menü die Gruppenart auswählen. Diese wird dann in der Übersicht der Gruppen angezeigt. Dies ist eine rein informative Einstellung und hat keinerlei Auswirkungen auf das System.

Mitglieder fügt man einer Gruppe hinzu, indem man auf Suche nach Kontakten... klickt. Hier kann nun der Namen des gewünschten Kontaktes eingetippt werden und {{ branding.title }} sucht automatisch nach dem passenden Kontakt (die Suche fängt, wie in allen Suchfeldern, nach dem dritten Zeichen an). Alternativ kann man über das Dropdown-Menü die entsprechenden Kontakte einzeln suchen. Jedem der Mitglieder kann eine Gruppenfunktion zugewiesen werden. Dazu klicken Sie in das leere Feld unterhalb Gruppenfunktion.
Die Definition einer Gruppenfunktion ist auch ein rein informatives Feld und hat keinerlei Auswirkung auf andere {{ branding.title }}-Anwendungen. Gruppenfunktionen werden in der Anwendung Stammdaten angelegt ([Stammdaten](ma_Stammdaten.md)). Öffen sie dazu Stammdaten und navigieren Sie zu Adressbuch -> Gruppenfunktionen. Hier können Sie Gruppenfunktion hinzufügen und bestehende bearbeiten oder löschen.

<!--ActiveSync-->
<!--CalDAV-->
## Kontakte synchronisieren

Die Einrichtung der Synchronisation von {{ branding.title }} mit Endgeräten ist zwar nicht Thema des Handbuches, dennoch wollen wir kurz beschreiben, wo Sie die hierfür notwendigen Parameter und Einstellungen in {{ branding.title }} vorfinden.

Die _CalDAV_ URL zur Synchronisierung Ihrer Kontakte können Sie über das Kontextmenü des jeweiligen Adressbuches einsehen: Mit Rechtsklick das Kontextmenü öffnen und auf Adressbuch Eigenschaften klicken. Dort finden Sie eine Zeile mit der Beschriftung CalDAV URL. Diese URL müssen Sie in Ihrem Endgerät zur Synchronisierung eingeben.

Mögliche Einstellungen zur Synchronisation über _ActiveSync_ lesen Sie bitte in [Benutzerspezifische Einstellungen - ActiveSync](na_Benutzereinstellungen.md/#activesync), sowie in [Administration - ActiveSync Geräte](oa_Administration.md/#activeSync-gerate) nach.