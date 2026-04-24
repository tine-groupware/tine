# Aufgaben
## Einleitung
Mit der Anwendung Aufgaben weisen Sie sich selbst oder einem beliebigen anderen Benutzer eine Aufgabe zu. Die vorhandenen Aufgaben werden, wie gewohnt, als Tabelle dargestellt:

<!-- SCREENSHOT -->
![Abbildung: Tabelle mit Aufgaben]({{ img_url_desktop }}Aufgaben/1_aufgaben_uebersicht_light_1920x1020.png#only-light){.desktop-img}
![Abbildung: Tabelle mit Aufgaben]({{ img_url_desktop }}Aufgaben/1_aufgaben_uebersicht_dark_1920x1020.png#only-dark){.desktop-img}
![Abbildung: Tabelle mit Aufgaben]({{ img_url_mobile }}Aufgaben/1_aufgaben_uebersicht_light_1280x720.png#only-light){.mobile-img}
![Abbildung: Tabelle mit Aufgaben]({{ img_url_mobile }}Aufgaben/1_aufgaben_uebersicht_dark_1280x720.png#only-dark){.mobile-img}

<!--ActiveSync-->
Eine Aufgabe beschreibt, was ein bestimmter Mitarbeiter bis zu einem bestimmten Datum erledigt haben sollte. Dazu bietet tine ein paar nützliche Eigenschaften an, die der besseren Übersicht als Filter dienen. Darauf kommen wir gleich zu sprechen. Außerdem ist die Übermittlung von Aufgaben auch Bestandteil des in tine implementierten Übertragungsprotokolls für mobile Endgeräte. Beachten Sie hier allerdings, dass nicht alle mobilen Geräte, die mit dem von tine benutzten ActiveSync-Übertragungsprotokoll arbeiten, auch von Haus aus das Synchronisieren von Aufgaben unterstützen. Abhilfe kann hier im Einzelfall die Installation von Zusatz-Applikationen schaffen. Konsultieren Sie dazu den Dienstleister, der Ihre tine-Installation betreut. [Aufgaben synchronisieren](fa_Aufgaben.md/#aufgaben-synchronisieren) enthält weitere Hinweise zur Synchronisation.

## Favoriten und Aufgabenlisten

Wie gewohnt, finden Sie auf der linken Seite unter Favoriten eine Reihe nützlicher Standardansichten bereits vorbereitet:

Alle Aufgaben für mich: Die Aufgaben, bei denen Sie Verantwortlicher sind.

Alle meine Aufgaben: Aufgaben, die in Ihren Aufgabenlisten stehen und weder "abgeschlossen" noch "abgebrochen" sind.

Aufgaben ohne Verantwortlichen: Alle Aufgaben im System, die nicht explizit einem Benutzer zugeordnet sind.

Offene Aufgaben für mich: Wie Alle Aufgaben für mich], jedoch ohne die bereits abgeschlossenen oder abgebrochenen.

Offene Aufgaben für mich (diese Woche): Wie Offene Aufgaben für mich, aber zusätzlich mit Fälligkeitstermin innerhalb der laufenden Woche.

Zuletzt von mir bearbeitet: Alle Aufgaben, an denen Sie zuletzt gearbeitet haben.

Wenn Sie beim Wechseln der Favoriten-Ansichten die Filterzeilen über der Tabelle im Blick haben, sehen Sie, dass die Favoriten durch Filter definierte Ansichten sind (vgl. [Allgemeine Hinweise zur Bedienung - Suchfilter für die Tabellenansicht](ca_StandardBedienhinweise.md/#suchfilter-fur-die-tabellenansicht)).

<span id="task-containertree"></span>
Die unter den Favoriten zu findenden Aufgabenlisten enthalten standardmäßig die drei üblichen Ordner Meine Aufgabenlisten, Gemeinsame Aufgabenlisten und Aufgabenlisten anderer Benutzer, wobei der Inhalt der beiden letztgenannten von dem Vorhandensein solcher Listen in Ihrem speziellen tine-System sowie von Ihren Zugriffsrechten abhängt. Im Ordner Meine Aufgabenlisten finden Sie auf jeden Fall Ihre persönliche Aufgabenliste vor, denn diese erzeugt tine mit der Anlage des Benutzers automatisch.

## Aufgabe hinzufügen

Klicken Sie im Bearbeitungsmenü ganz links den Button Aufgabe hinzufügen an:

<!-- SCREENSHOT -->
![Abbildung: Anlegen einer neuen Aufgabe]({{ img_url_desktop }}Aufgaben/2_aufgaben_neue_aufgabe_light_1920x1020.png#only-light){.desktop-img}
![Abbildung: Anlegen einer neuen Aufgabe]({{ img_url_desktop }}Aufgaben/2_aufgaben_neue_aufgabe_dark_1920x1020.png#only-dark){.desktop-img}
![Abbildung: Anlegen einer neuen Aufgabe]({{ img_url_mobile }}Aufgaben/2_aufgaben_neue_aufgabe_light_1280x720.png#only-light){.mobile-img}
![Abbildung: Anlegen einer neuen Aufgabe]({{ img_url_mobile }}Aufgaben/2_aufgaben_neue_aufgabe_dark_1280x720.png#only-dark){.mobile-img}

Die Bearbeitungsmaske enthält unter dem Reiter Aufgabe zunächst ein Feld Beschreibung. Dieses ist ein Pflichtfeld. Darunter folgt Fälligkeitsdatum (mit Kalenderfunktion) sowie rechts daneben die Uhrzeit als Pulldown. Daneben finden Sie eine ebenfalls als Pulldown unter vier verschiedenen Eskalationsstufen wählbare Priorität: niedrig, normal, hoch und dringend. Beachten Sie bei der Vergabe der Priorität, dass diese nicht nur als Hinweis, sondern vor allem als Filterkriterium für zu definierende Ansichten dient. Standardpriorität ist normal. Schließlich ist rechts noch das Feld für den Organisator der Aufgabe zu finden, als Pulldown mit einer Auswahl aus allen angelegten tine-Benutzern.

!!! info "Wichtig"
    Das Einstellen eines anderen Benutzers als Organisator bedeutet nicht automatisch, dass Sie dem anderen Benutzer eine Aufgabe in dessen Aufgabenliste schreiben. Dies geschieht erst durch Auswahl der richtigen Aufgabenliste. Sie finden das entsprechende Pulldown im unteren, grauen Bereich der Maske, rechts neben Gespeichert in. Andere Aufgabenlisten erhalten Sie dort allerdings nur angeboten, wenn Sie über entsprechende Berechtigungen verfügen. Im eigentlichen Wortsinn ist das Organisator-Feld dann zu nutzen, wenn es sich bei der Aufgabe um eine Gruppenaufgabe handelt – darum auch "Organisator" und nicht "Ausführender". Aber auch das bedeutet nicht, dass dem Organisator einer Aufgabe diese automatisch in dessen Aufgabenliste eingetragen wird. Auch hier müssen Sie dazu die Aufgabenliste explizit anwählen – in diesem Falle wahrscheinlich eine gemeinsame Aufgabenliste, auf die der Organisator dieser Aufgabe ebenfalls Zugriff hat.


Im Feld Anmerkungen versehen Sie die Aufgabe mit einer beliebigen Beschreibung. Schließlich finden Sie unter Anmerkungen noch eine Zeile für weitere Angaben: Ganz links unter Prozent besteht die Möglichkeit, als Pulldown in 10%-Schritten den Erfüllungsgrad abzuspeichern. Rechts daneben befindet sich das Pulldown Status, das Ihnen die vier Möglichkeiten Keine Antwort, Abgeschlossen, Abgebrochen und Laufend anbietet. Keine Antwort werden Sie dann verwenden, wenn Sie einem anderen Benutzer oder einer Gruppe eine Aufgabe zuweisen. Der Organisator erhält damit den Hinweis, dass er den Status entsprechend ändern muss. Status ist ebenfalls ein Pflichteingabefeld. Und ganz rechts steht unter Abgeschlossen wieder ein Datums- und Uhrzeitfeld.

Im grauen Randbereich des unteren Teils der Eingabemaske finden Sie links das Pulldown Gespeichert in. Hier können Sie nun, wie oben erwähnt, die Aufgabenliste auswählen, in der Sie die Aufgabe speichern wollen. Je nachdem, welche Berechtigungen für Sie gelten, finden Sie hier Aufgabenlisten anderer Benutzer sowie gemeinsame Aufgabenlisten. Sollten Sie hier nicht die Aufgabenlisten finden, die Sie suchen, schauen Sie sich die vorhandenen Aufgabenlisten und die zugehörigen Rechtevergaben an (Admin -> Container). Lesen Sie für genaue Informationen in [Administration - Container](oa_Administration.md/#container) nach.

<span id="task-alarmgrid"></span>
Neben den bereits von anderen Anwendungen bekannten und in [Allgemeine Hinweise zur Bedienung](ca_StandardBedienhinweise.md) beschriebenen Reitern Notizen, Anhänge, Verknüpfungen und Historie finden Sie hier noch einen Alarm. Öffnen Sie den entsprechenden Reiter.

<!-- SCREENSHOT -->
![Abbildung: Alarm zur Erfüllung einer Aufgabe]({{ img_url_desktop }}Aufgaben/3_aufgaben_alarm_light_1920x1020.png#only-light){.desktop-img}
![Abbildung: Alarm zur Erfüllung einer Aufgabe]({{ img_url_desktop }}Aufgaben/3_aufgaben_alarm_dark_1920x1020.png#only-dark){.desktop-img}
![Abbildung: Alarm zur Erfüllung einer Aufgabe]({{ img_url_mobile }}Aufgaben/3_aufgaben_alarm_light_1280x720.png#only-light){.mobile-img}
![Abbildung: Alarm zur Erfüllung einer Aufgabe]({{ img_url_mobile }}Aufgaben/3_aufgaben_alarm_dark_1280x720.png#only-dark){.mobile-img}

Das Pulldown Alarmzeit bestimmt, zu welchem Zeitpunkt vor der gespeicherten Fälligkeit Ihr Computer und/oder Mobilfunkgerät Alarm geben soll. Achtung: Die Fälligkeit ist keine Pflichteingabe! Gibt es also, aus welchen Gründen auch immer, für die Aufgabe keinen definierten Fälligkeitszeitpunkt und möchten Sie dennoch einen Alarm eintragen, nutzen Sie im Pulldown Alarmzeit den untersten Eintrag Benutzerdefinierter Zeitpunkt. Nach Anwahl dieses Menüpunkts werden das Datums- und Uhrzeitfeld rechts daneben aktiv, und Sie können hier Ihren Alarmzeitpunkt eingeben.

Wenn der Alarmierungszeitpunkt erreicht ist, löst der System-E-Mail-Dienst das Versenden einer E-Mail an den Benutzer aus, die entsprechende Angaben zur Aufgabe enthält.

!!! info "Wichtig"
    Legen Sie die Zeitspanne für die Alarmierung nicht zu knapp aus! Der Systemdienst kann u.U., je nach Auslastung, ein bis mehrere Minuten zur Zustellung der Alarm-E-Mail benötigen.

<!--ActiveSync-->
<!--CalDAV-->
## Aufgaben synchronisieren

Die Einrichtung der Synchronisation von tine mit Endgeräten ist zwar nicht Thema des Handbuches, dennoch wollen wir kurz beschreiben, wo Sie die hierfür notwendigen Parameter und Einstellungen in tine vorfinden.

Die _CalDAV_ URL zur Synchronisierung Ihrer Aufgaben können Sie über das Kontextmenü der jeweiligen Aufgabenliste einsehen: Mit Rechtsklick das Kontextmenü öffnen und auf Aufgabenliste Eigenschaften] klicken. Dort finden Sie eine Zeile mit der Beschriftung CalDAV URL. Diese URL müssen Sie in Ihrem Endgerät zur Synchronisierung eingeben.

Mögliche Einstellungen zur Synchronisation über _ActiveSync_ schlagen Sie bitte in [Benutzerspezifische Einstellungen - ActiveSync](na_Benutzereinstellungen.md/#activesync) sowie in [Administration - ActiveSync Geräte](oa_Administration.md/#activesync-gerate) nach.