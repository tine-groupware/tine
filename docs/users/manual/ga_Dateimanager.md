# Dateimanager { data-ctx="/Filemanager" }
Der Dateimanager in tine ist zwar kein vollwertiges Dokumentenmanagementsystem (dazu fehlt es ihm an Funktionen, wie etwa der Verschlagwortung von Dokumenten), doch für die üblichen Anwendungsfälle im Rahmen einer Groupware ganz gut als solches zu gebrauchen. Vergegenwärtigen wir uns kurz, vor welchen Herausforderungen ein Unternehmen beispielsweise im Umgang mit den üblichen Vorlagen, wie Briefköpfe, Kalkulationen, Angebote, Rechnungen o.ä., steht – sie sollen

* allen Mitarbeitern zur Verfügung stehen, die damit arbeiten müssen,

* stets aktuell sein,

* zentral abgelegt sein,

* nach Projekten, Mitarbeitern, Kunden, Produkten o.ä. Kriterien zuzuordnen sein.

<!--Musterbrief-->
<!--Dokumentvorlagen-->
Diese Anforderungen erfüllt der tine-Dateimanager durchaus. Machen wir uns das an einem Fallbeispiel klar: Sie sollen eine Musterdatei für Ihr Unternehmen erstellen und ihn allen Mitarbeitern zur verbindlichen Verwendung bereitstellen. Dies wäre das Vorgehen:

1. Legen Sie einen Ordner "Vorlagen" an, sofern es diesen noch nicht gibt, und weisen Sie (ausschließlich!) den betroffenen Mitarbeitern die entsprechenden Zugriffsrechte zu.

2. Laden Sie die Dateien in den Ordner hoch.

3. Informieren Sie Ihre Kollegen.

Schauen wir uns die einzelnen Bedienelemente und Funktionen des Dateimanagers nun im Rahmen dieses Fallbeispiels an.

## Der Ordnerbaum { data-ctx="/Filemanager/MainScreen/Node" }
Der Baum Ordner enthält in der Grundkonfiguration einen Oberordner (alle Ordner) und drei Unterordner (Meine Ordner, Gemeinsame Ordner und Ordner anderer Benutzer). Meine Ordner wiederum hat immer auch noch den Unterordner Persönliche Dateien von... (Benutzername) – das ist der Ablageort für Ihre eigenen/persönlichen Dokumente, der automatisch mit dem Benutzer angelegt wird.

Klicken Sie auf Gemeinsame Ordner, öffnet sich die darunterliegende Baumstruktur.

<!-- SCREENSHOT -->
![Abbildung: Baumstruktur des Dateimanagers]({{ img_url_desktop }}Dateimanager/1_dateimanager_baumstruktur_light_1920x1020.png#only-light){.desktop-img}
![Abbildung: Baumstruktur des Dateimanagers]({{ img_url_desktop }}Dateimanager/1_dateimanager_baumstruktur_dark_1920x1020.png#only-dark){.desktop-img}
![Abbildung: Baumstruktur des Dateimanagers]({{ img_url_mobile }}Dateimanager/1_dateimanager_baumstruktur_light_1280x720.png#only-light){.mobile-img}
![Abbildung: Baumstruktur des Dateimanagers]({{ img_url_mobile }}Dateimanager/1_dateimanager_baumstruktur_dark_1280x720.png#only-dark){.mobile-img}

Prüfen Sie, ob es bereits einen Ordner Vorlagen gibt, und legen Sie ihn gegebenenfalls an.

Klicken Sie mit der rechten Maustaste auf Gemeinsame Ordner, erhalten Sie ein Kontextmenü mit den Punkten Ordner hinzufügen und Ordner neu laden. Letzterer dient dem Refresh getätigter Eingaben, sodass Sie beispielsweise auch die soeben erst hochgeladenen Dateien sehen können. Sie kennen das: In einem Webbrowser wird nicht sofort alles angezeigt, was Sie gerade gemacht haben, da sich die Daten ja nicht auf Ihrem Rechner, sondern auf einem Server im Internet befinden. Dieser Menüpunkt aktualisiert die Anzeige im Browser, sodass Sie nun sehen, was aktueller Arbeitsstand ist.

Wir wollen hier noch einen neuen Ordner anlegen; klicken Sie also Ordner hinzufügen.

<!-- SCREENSHOT -->
![Abbildung: Hinzufügen eines neuen Ordners]({{ img_url_desktop }}Dateimanager/2_dateimanager_neuer_ordner_light_1920x1020.png#only-light){.desktop-img}
![Abbildung: Hinzufügen eines neuen Ordners]({{ img_url_desktop }}Dateimanager/2_dateimanager_neuer_ordner_dark_1920x1020.png#only-dark){.desktop-img}
![Abbildung: Hinzufügen eines neuen Ordners]({{ img_url_mobile }}Dateimanager/2_dateimanager_neuer_ordner_light_1280x720.png#only-light){.mobile-img}
![Abbildung: Hinzufügen eines neuen Ordners]({{ img_url_mobile }}Dateimanager/2_dateimanager_neuer_ordner_dark_1280x720.png#only-dark){.mobile-img}

Schreiben Sie "Vorlagen" in das angebotene Feld, wenn es diesen Ordner nicht schon gibt – sonst wählen Sie testweise einen beliebigen anderen Namen und löschen diesen Ordner später wieder. Den gleichen Arbeitsschritt können Sie übrigens auch vom Bearbeitungsmenü aus ausführen; dort lautet der Befehl Ordner anlegen – wir kommen noch darauf zurück.

Sie sollten jetzt als Unterordner von Gemeinsame Ordner einen Ordner Vorlagen haben. Übrigens lassen sich – wie im Dateisystem auf Ihrem PC – Ordner auch per Drag&Drop verschieben.

Nun geben wir diesem Ordner die passenden Berechtigungen, sodass alle Mitarbeiter des Unternehmens die Vorlagen aufrufen, bearbeiten und ausdrucken können. Letzteres umfasst i.d.R. auch das Erzeugen von PDF-Dokumenten, die dann, z.B. auftrags- oder kundenbezogen, an anderen Stellen in tine wieder abgelegt werden. Welche Berechtigungen darüber hinaus erteilt werden, erfordert hier, wie überall in tine beim Anlegen von sog. Containern, grundsätzliche Überlegungen. Dazu mehr in [Administration - Container](oa_Administration.md/#container).

Klicken Sie nun den eben erzeugten Ordner wieder mit der rechten Maustaste an – das Kontextmenü ist jetzt, da Sie in diesem Bereich Administratorrechte haben, wesentlich umfangreicher:

<!-- SCREENSHOT -->
![Abbildung: Kontextmenü zu einem selbst angelegten Ordner]({{ img_url_desktop }}Dateimanager/3_dateimanager_ordner_kontextmenu_light_1920x1020.png#only-light){.desktop-img}
![Abbildung: Kontextmenü zu einem selbst angelegten Ordner]({{ img_url_desktop }}Dateimanager/3_dateimanager_ordner_kontextmenu_dark_1920x1020.png#only-dark){.desktop-img}
![Abbildung: Kontextmenü zu einem selbst angelegten Ordner]({{ img_url_mobile }}Dateimanager/3_dateimanager_ordner_kontextmenu_light_1280x720.png#only-light){.mobile-img}
![Abbildung: Kontextmenü zu einem selbst angelegten Ordner]({{ img_url_mobile }}Dateimanager/3_dateimanager_ordner_kontextmenu_dark_1280x720.png#only-dark){.mobile-img}

Der darüberliegende Ordner Gemeinsame Ordner ist ja ein Systemordner, in dem auch Ihre Rechte per se eingeschränkt sind. Sie dürfen diesen Ordner nicht löschen, umbenennen oder seine Berechtigungen verwalten. Ihren Ordner Vorlagen haben Sie jedoch selbst erzeugt, deshalb haben Sie diese Rechte hier. Ein normaler Benutzer ohne Administratorrechte hätte auch bei diesem Ordner nur die ersten beiden Optionen – er hätte ihn ja auch nicht anlegen können.

Ordner löschen und Ordner umbenennen sind selbsterklärend; klicken Sie daher jetzt Eigenschaften bearbeiten an. Alternativ können Sie den Ordner auswählen und oben auf Eigenschaften bearbeiten klicken. In beiden Fällen öffnet sich ein neues Fenster zum bearbeiten der Ordner Eigenschaften. Im Bereich Ordner können Sie den Ordnernamen ggf. ändern. Interessanter sind hier die nächsten drei Menüpunkte Öffentliche Links, Verbrauch und Berechtigungen. Für unser Fallbeispiel fangen wir mit dem letzteren an. Klicken Sie auf Berechtigungen, erhalten Sie ein Bearbeitungsfenster, in dem Sie dem Ordner Benutzer oder Gruppen zuweisen und deren Berechtigungen definieren. Als Administrator sollten Sie hier alle verfügbaren Rechte besitzen, also Lesen, Hinzufügen, Bearbeiten, Löschen, Sync, Herunterladen, Veröffentlichen und Admin.

<!-- SCREENSHOT -->
![Abbildung: Vergeben von Berechtigungen für Ordner]({{ img_url_desktop }}Dateimanager/5_dateimanager_ordner_rechte_light_1920x1020.png#only-light){.desktop-img}
![Abbildung: Vergeben von Berechtigungen für Ordner]({{ img_url_desktop }}Dateimanager/5_dateimanager_ordner_rechte_dark_1920x1020.png#only-dark){.desktop-img}
![Abbildung: Vergeben von Berechtigungen für Ordner]({{ img_url_mobile }}Dateimanager/5_dateimanager_ordner_rechte_light_1280x720.png#only-light){.mobile-img}
![Abbildung: Vergeben von Berechtigungen für Ordner]({{ img_url_mobile }}Dateimanager/5_dateimanager_ordner_rechte_dark_1280x720.png#only-dark){.mobile-img}

Fahren Sie mit der Maus der Reihe nach über die einzelnen Felder. Im Kontext sehen Sie dabei kurze Definitionen der Berechtigungen. Sollte Ihnen jetzt nicht absolut klar sein, was die einzelnen Berechtigungen bedeuten, schlagen Sie dazu bitte in [Administration - Container](oa_Administration.md/#container) nach.

An dieser Stelle müssen Sie sich zunächst entscheiden, wer diesen Ordner überhaupt sehen soll. Das werden wohl Benutzergruppen sein (weshalb die Gruppenauswahl auch als Standard angeboten wird), Sie können aber auch nur einzelne Benutzer zuweisen. Dazu würden Sie jetzt oben am linken Rand das Pulldown-Menü mit den drei schwarzen Köpfen betätigen und die Auswahl auf Benutzersuche stellen.

Wir bleiben hier jedoch bei der Gruppenauswahl. Wenn Sie den Ordner neu angelegt haben, werden Sie jetzt nur drei Einträge vorfinden: Administrator und User sind 2 default Gruppen die tine hinzufügt. Standardmäßig ist User jedem neu geschaffenen Container (also auch einem Dateiordner) mit den Rechten Lesen und Sync zugewiesen, was "auf die Schnelle" sicher für die meisten Anwendungsfälle passt. Entscheiden Sie dennoch jetzt selbst, ob das für Ihren vorliegenden Fall so in Ordnung ist. Zusätzlich sollten Sie auch sich selbst als Benutzer mit allen (inkl. Administrator-)Rechten sehen. Wollen Sie Ihrem Ordner konkreten, im System bereits definierte Gruppen zuweisen und diesen auch bestimmte Berechtigungen erteilen, entfernen Sie zunächst die Gruppe User aus der Auswahl, entweder mit dem Button Entferne Eintrag unten links oder durch Drücken der rechten Maustaste auf der Gruppe und Anwahl von Entferne Eintrag. Wählen Sie danach über das Pulldown-Menü oben die gewünschte Benutzergruppe – oder auch mehrere nacheinander – aus.


Anschließend prüfen Sie die angebotenen Berechtigungen und ändern diese bei Bedarf. In unserem Fall wären hier für alle in Frage kommenden Benutzer nur die Berechtigungen Lesen und Bearbeiten auszuwählen.


Natürlich können Sie verschiedenen Benutzergruppen verschiedene Berechtigungen zuweisen – gerade das ist ja der Sinn hinter dem Konzept der Benutzergruppen. Experimentieren Sie ein wenig mit der Anlage von Ordnern und Unterordnern; vergessen Sie aber nicht, alles wieder zu löschen, was nicht benötigt wird.

<!--Veröffentlichen von Dokumenten-->
An dieser Stelle wollen wir kurz noch die beiden Punkte Öffentliche Links und Verbrauch erwähnen.
tine bietet die Möglichkeit Ordner oder Dateien zu veröffentlichen. Klicken sie dafür mit der rechten Maustaste auf die Datei oder den Ordner und wählen Sie Veröffentlichen. Hiermit erzeugen Sie einen standardmäßig einen Monat lang gültigen öffentlichen Link, unter dem die Datei aus dem Internet aufrufbar ist. Nach Ablauf der Frist, die Sie auch über einen Klick auf das Datumsfeld rechts in der Tabelle individuell anpassen können, ist der Link nicht mehr gültig. Dieser Link ist übrigens anonym, d.h. ohne Anmeldung benutzbar!
Öffentliche Links dient zum Tracken dieser Veröffentlichungen
Verbrauch gibt Ihnen eine Übersicht über den Internet verbrauch von dem ausgewählten Ordner bzw. der Datei.

## Das Bearbeitungsmenü

Kommen wir zurück zu unserem Fallbeispiel. Im Bearbeitungsmenü auf der linken Seite über der Tabelle finden Sie die Punkte Hochladen, Eigenschaften bearbeiten, Löschen, Ordner anlegen, Aufwärts, Lokal speichern und Veröffentlichen. Wenn Sie einen Ordner markiert haben, sind die Punkte Eigenschaften bearbeiten, Löschen, Lokal speichern und Veröffentlichen jedoch ausgegraut, weil sie ausschließlich auf Dateien anwendbar sind.

<!-- SCREENSHOT -->
![Abbildung: Bearbeitungsmenü]({{ img_url_desktop }}Dateimanager/6_dateimanager_bearbeitungsmenu_light_1920x1020.png#only-light){.desktop-img}
![Abbildung: Bearbeitungsmenü]({{ img_url_desktop }}Dateimanager/6_dateimanager_bearbeitungsmenu_dark_1920x1020.png#only-dark){.desktop-img}
![Abbildung: Bearbeitungsmenü]({{ img_url_mobile }}Dateimanager/6_dateimanager_bearbeitungsmenu_light_1280x720.png#only-light){.mobile-img}
![Abbildung: Bearbeitungsmenü]({{ img_url_mobile }}Dateimanager/6_dateimanager_bearbeitungsmenu_dark_1280x720.png#only-dark){.mobile-img}

Hochladen öffnet das browserinterne Datei-Auswahlmenü mit den an Ihrem Arbeitsplatz verfügbaren Datenträgern. Sie können eine beliebige Datei auswählen und hochladen – in unserem Beispiel die vielleicht vorbereiteten Musterdatei. Dateien lassen sich auch über Drag&Drop in einen Ordner des Dateimanagers übertragen.

Sie sehen nun in der Standardansicht des Tabellenfensters die verfügbaren Eigenschaften zu dieser Datei.
Um die Eigenschaften gespeicherter Dateien zu ändern, gibt es wieder zwei Wege: über das Bearbeitungsmenü oder über das Kontextmenü.

<!-- SCREENSHOT -->
![Abbildung: Der Reiter "Datei" zeigt Name (änderbar) und Datumsangaben]({{ img_url_desktop }}Dateimanager/8_dateimanager_eigenschaften_datei_light_1920x1020.png#only-light){.desktop-img}
![Abbildung: Der Reiter "Datei" zeigt Name (änderbar) und Datumsangaben]({{ img_url_desktop }}Dateimanager/8_dateimanager_eigenschaften_datei_dark_1920x1020.png#only-dark){.desktop-img}
![Abbildung: Der Reiter "Datei" zeigt Name (änderbar) und Datumsangaben]({{ img_url_mobile }}Dateimanager/8_dateimanager_eigenschaften_datei_light_1280x720.png#only-light){.mobile-img}
![Abbildung: Der Reiter "Datei" zeigt Name (änderbar) und Datumsangaben]({{ img_url_mobile }}Dateimanager/8_dateimanager_eigenschaften_datei_dark_1280x720.png#only-dark){.mobile-img}

Der Menüpunkt Löschen ist selbsterklärend und ist wieder auch über das Maus-Kontextmenü aufzurufen. Natürlich umfasst er eine Sicherheitsabfrage.

Ordner anlegen haben wir bereits besprochen; dort war es der Punkt Ordner hinzufügen im Kontextmenü des Ordnerbaumes.

Mit dem Button Aufwärts gehen Sie nach oben durch die Baumstruktur bis zum Ordner Alle Ordner. Dort angekommen, erscheint der Button ausgegraut.

Lokal speichern funktioniert nur, wenn Dateien markiert sind – die Funktion bietet Ihnen erwartungsgemäß den für Ihren verwendeten Internetbrowser vorgesehenen Dialog zum Herunterladen einer Datei aus dem Web an.

Natürlich stehen auch in dieser Anwendung bei vielen Dateieinträgen Suchfilter zur Verfügung (vgl. [Allgemeine Hinweise zur Bedienung - Suchfilter für die Tabellenansicht](ca_StandardBedienhinweise.md/#suchfilter-fur-die-tabellenansicht)).

## Anti-Virus, Vorschau & Only Office

Sollten Sie Ihre tine-Lizenz durch den OnlyOffice-Integrator, den Preview-Service und/oder Anti-Virus erweitert haben, stehen diese unter anderem auch im Dateimanager zur Verfügung. Was diese sind und wie Sie von den Erweiterungen Gebrauch machen können, entnehmen Sie den speziellen Kapiteln [Only Office Integration](ta_OnlyOffice.md), [Preview Service](ra_PreviewService.md) und [Anti-Viren Service](qa_AntiVirus.md).