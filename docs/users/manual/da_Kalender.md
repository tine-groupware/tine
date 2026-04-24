# Kalender
## Einleitung

<!--ActiveSync-->
Der Kalender ist, neben dem Adressbuch und dem E-Mail-Client, wohl eine der drei Kernfunktionen einer Groupware. Da ein Kalender, wenn er den Ansprüchen an die Zusammenarbeit von Gruppen genügen soll, auch mit verschiedenen Geräteplattformen zurechtkommen muss, ist tine mit Schnittstellen ausgerüstet. Das ist zum einen _ActiveSync_ von Microsoft, das die Synchronisation mit den Kalendern mobiler Geräte zulässt, aber auch MS-Outlook-Kalender-Benutzer anbindet, z.B. externe Projektpartner, die die Microsoft-Groupware verwenden.

<!--CalDAV-->
<!--iCal-->
<!--ICS-Format-->
Eine weitere Möglichkeit, andere Kalender anzubinden, ist _CalDAV_, ein Protokoll für den Austausch standardisierter Termindaten. Verbreitete Clients, die dieses Protokoll verarbeiten, sind z.B. Evolution (ein unter diversen Linux-Distributionen verbreiteter, aber auch für Windows verfügbarer  E-Mail- und Kalender-Client), iCal (ab Mac OS X 10.5 Leopard), iPhone (ab Version 3.0), Sunbird/Thunderbird sowie Android (mit mehreren verfügbaren Synchronisationsprogrammen). Auch für MS Outlook gibt es CalDAV-Zusatzmodule. Die Liste der CalDAV-Server ist noch weitaus länger. Wie genau diese in tine eingebunden werden, ist nicht Thema dieses Benutzerhandbuchs ([Termine synchronisieren](da_Kalender.md/#termine-synchronisieren) enthält nur einige grundlegende Hinweise), wir werden in [Termine importieren](da_Kalender.md/#termine-importieren) aber beschreiben, wie Sie externe Kalenderdateien im ICS-Format einlesen bzw. einbinden können.

!!! note "Anmerkung"
    Bei der Nutzung von iCal gibt es die Einschränkung, dass gemeinsame Kalender angezeigt werden, aber von anderen freigegebenen Benutzern nicht abgeändert oder gelöscht werden können. Es handelt sich hierbei um eine Besonderheit/Limitierung der Apple-Implementationen, die die Bearbeitung eines Termins in einem gemeinsamen Kalender einschränkt, sobald dieser Teilnehmer hat.
    
    Das Grundproblem ist:
    Apples eigener Kalender-Server kennt keine gemeinsamen Kalender mit Teilnehmern, Teilnehmer gibt es bei Apple nur in persönlichen Kalendern (ein ähnliches Problem gibt es auch mit dem "Privat"-Flag und Apple-Produkten).
    
    Die momentan einzige Lösung, die zugegebenermaßen nicht schön ist, aber funktioniert:
    <ul>
    <li> Auf gemeinsame Kalender verzichten,</li>
    <li> Ressourcen und gemeinsame Kalender über einzelne Nutzer abbilden.</li>
    </ul>
    Darüber hinaus sind gemeinsame Termine, an denen man selbst teilnimmt, auch immer im eigenen persönlichen Kalender vorhanden und können somit über diesen Weg synchronisiert werden.


Klicken Sie nun den Reiter Kalender an oder aktivieren Sie ihn über den Reiter tine.

<!-- SCREENSHOT -->
![Abbildung 3.1: Monatsansicht des Kalenders]({{ img_url_desktop }}Kalender/1_kalender_monatsuebersicht_light_1920x1020.png#only-light){.desktop-img}
![Abbildung 3.1: Monatsansicht des Kalenders]({{ img_url_desktop }}Kalender/1_kalender_monatsuebersicht_dark_1920x1020.png#only-dark){.desktop-img}
![Abbildung 3.1: Monatsansicht des Kalenders]({{ img_url_mobile }}Kalender/1_kalender_monatsuebersicht_light_1280x720.png#only-light){.mobile-img}
![Abbildung 3.1: Monatsansicht des Kalenders]({{ img_url_mobile }}Kalender/1_kalender_monatsuebersicht_dark_1280x720.png#only-dark){.mobile-img}

Die Ansicht ist unspektakulär, wie bei jedem Kalender. Allerdings handelt es sich hier um eine Groupware; es stehen Ihnen also neben der Eingabe eigener Termine eine ganze Reihe weiterer, Personen-übergreifender Funktionen zur Verfügung, wie zum Beispiel das Einladen anderer Teilnehmer zu Terminen, das Buchen von Ressourcen für Besprechungen, das Anzeigen von Terminen anderer Benutzer (sofern Sie über die Rechte dazu verfügen) oder das Filtern von Kalendereinträgen (auch anderer Benutzer) nach verschiedenen Kriterien in speziell dafür definierten Ansichten.

## Favoriten und verschiedene Kalender

<!--Favoriten-Ansicht-->
Auch im Kalender können Sie bestimmte gefilterte Ansichten als FAVORITEN speichern (vgl. [Adressverwaltung - Kontakte filtern](ba_Adressbuch.md/#kontakte-filtern)). Standardmäßig sind bereits vier praktische Favoriten-Filter angelegt. Abgelehnte Termine, Alle meine Termine, Erwartet Antwort und Ich bin Organisator.

<!-- SCREENSHOT -->
![Abbildung: Favoriten des Kalenders]({{ img_url_desktop }}Kalender/2_kalender_favoriten_ausschnit_light_1920x1020.png#only-light){.desktop-img}
![Abbildung: Favoriten des Kalenders]({{ img_url_desktop }}Kalender/2_kalender_favoriten_ausschnit_dark_1920x1020.png#only-dark){.desktop-img}
![Abbildung: Favoriten des Kalenders]({{ img_url_mobile }}Kalender/2_kalender_favoriten_ausschnit_light_1280x720.png#only-light){.mobile-img}
![Abbildung: Favoriten des Kalenders]({{ img_url_mobile }}Kalender/2_kalender_favoriten_ausschnit_dark_1280x720.png#only-dark){.mobile-img}

Probieren Sie jetzt einmal die vier Standard-Favoriten-Ansichten aus, und schauen Sie sich dabei auch die entsprechenden syntaktischen Definitionen in den Filterzeilen an. So gewinnen Sie Übung für die Definition eigener FAVORITEN-Ansichten. Die Kalender-Anwendung ist hier ähnlich vielseitig, aber eben auch ähnlich anspruchsvoll, wie das Adressbuch.

<span id="event-attendeefilter"></span>
<span id="event-containertree"></span>
Die Besonderheit im Kalender besteht in zwei weiteren Einstellkriterien auf der linken Seite. Neben der bekannten Container-Baumstruktur unter KALENDER, über die Sie weitere im System angelegte Kalender wählen, blenden Sie unter der Rubrik TEILNEHMER die Termine anderer Teilnehmer ein. Klicken Sie dazu das ausgegraute Teilnehmer hinzufügen an.

<!-- SCREENSHOT -->
![Abbildung: Kalender anderer tine-Benutzer hinzufügen]({{ img_url_desktop }}Kalender/Kalender_teilnehmer_hinzu_light_1920x1020.png#only-light){.desktop-img}
![Abbildung: Kalender anderer tine-Benutzer hinzufügen]({{ img_url_desktop }}Kalender/Kalender_teilnehmer_hinzu_dark_1920x1020.png#only-dark){.desktop-img}
![Abbildung: Kalender anderer tine-Benutzer hinzufügen]({{ img_url_mobile }}Kalender/Kalender_teilnehmer_hinzu_light_1280x720.png#only-light){.mobile-img}
![Abbildung: Kalender anderer tine-Benutzer hinzufügen]({{ img_url_mobile }}Kalender/Kalender_teilnehmer_hinzu_dark_1280x720.png#only-dark){.mobile-img}

Hier können Sie anfangen den Namen des Benutzer einzutippen und tine fängt automatisch an zu suchen. Alternativ können Sie auf den kleinen Pfeil klicken daraufhin öffnende Pulldown-Menü bietet Ihnen die angelegten tine-Benutzer, aber auch alle anderen gespeicherten Kontakte zur Auswahl an.
Welchen Sinn hat das nun? Externe Personen, wie Kunden oder Lieferanten, haben doch sicher nur in Ausnahmefällen ein Benutzerkonto und damit einen eigenen Kalender in Ihrer tine-Groupware – das ist richtig, doch können auch diese von tine-Benutzern zu Meetings eingeladen werden. Und wenn Sie z.B. sehen wollen, zu welchen Terminen ein bestimmter externer Teilnehmer, der kein Benutzerkonto bei Ihnen hat, in einem bestimmten Zeitabschnitt eingeladen wurde, wählen Sie diese Person hier unter TEILNEHMER als Ansicht aus und sehen all deren Termine auf einen Blick. Wie Sie externe Personen einladen, schauen wir uns in [Allgemeine Termindaten eingeben und Teilnehmer einladen](da_Kalender.md/#allgemeine-termindaten-eingeben-und-teilnehmer-einladen) an.

!!! info "Wichtig"
    Achten Sie beim Auswählen von Kalendern in der Baumstruktur oder von Teilnehmern darauf, dass eventuell vorher in FAVORITEN eingestellte Filter (z.B. Erwartet Antwort) erhalten bleiben, wenn sie nicht im Widerspruch zu den zuletzt gemachten Einstellungen stehen. Sie sollten also immer, wenn Sie eine spezielle Ansicht in der linken Seite definiert haben, auch ein Auge auf den Filterkriterien über dem Kalenderfenster haben.

<span id="event-minidatepicker"></span>
Letzte angebotene Einstellmöglichkeit auf der linken Seite ist der Minikalender. Damit springen Sie schnell zu einem gewünschten Zeitabschnitt, und obwohl der Minikalender immer einen Monat anzeigt, können Sie per Mausklick auch die gewünschte Ansicht im Zielzeitraum wählen.

<!-- SCREENSHOT -->
![Abbildung: Minikalender mit KW-Auswahl]({{ img_url_desktop }}Kalender/4_kalender_minikalender_light_1920x1020.png#only-light){.desktop-img}
![Abbildung: Minikalender mit KW-Auswahl]({{ img_url_desktop }}Kalender/4_kalender_minikalender_dark_1920x1020.png#only-dark){.desktop-img}
![Abbildung: Minikalender mit KW-Auswahl]({{ img_url_mobile }}Kalender/4_kalender_minikalender_light_1280x720.png#only-light){.mobile-img}
![Abbildung: Minikalender mit KW-Auswahl]({{ img_url_mobile }}Kalender/4_kalender_minikalender_dark_1280x720.png#only-dark){.mobile-img}

Klicken Sie auf das Monatsfeld, geht der große Kalender in die Monatsansicht; klicken Sie links auf KW, wird Ihnen die zugehörige Wochenansicht angeboten – wenn Sie den Tag anklicken, die Tagesansicht.

Das Ändern der Ansicht geht alternativ auch mit einem Klick auf die Buttons Tag, menu[Woche] und menu[Monat], welche sich auf der rechten oberen Seite befinden.

<!-- SCREENSHOT -->
![Abbildung: Ändern der Kalenderansicht]({{ img_url_desktop }}Kalender/14_kalender_ansicht_light_1920x1020.png#only-light){.desktop-img}
![Abbildung: Ändern der Kalenderansicht]({{ img_url_desktop }}Kalender/14_kalender_ansicht_dark_1920x1020.png#only-dark){.desktop-img}
![Abbildung: Ändern der Kalenderansicht]({{ img_url_mobile }}Kalender/14_kalender_ansicht_light_1280x720.png#only-light){.mobile-img}
![Abbildung: Ändern der Kalenderansicht]({{ img_url_mobile }}Kalender/14_kalender_ansicht_dark_1280x720.png#only-dark){.mobile-img}

## Das Bearbeitungsmenü

Das Bearbeitungsmenü auf der linken Seite über der Tabelle enthält die Buttons Termin hinzufügen, Termin bearbeiten, Termin löschen, Drucke Seite, Importiere Termine, Exportiere TermineNachricht verfassen, Split (bisher nur in der CE), Blatt, Liste und Farben

Mit Split, Blatt und Liste wählen Sie verschiedene Ansichten für den Kalender. Standard als Blatt ist die Wochenansicht; die Anzeige als Liste ist vor allem für den Ausdruck besser geeignet, denn sie enthält in Tabellenform eine Reihe weiterer Informationen über einen Termin, die im Blatt nicht sofort sichtbar sind. Sie können hier, wie in jeder Tabellenansicht von tine, mit dem kleinen Tabellensymbol rechts außen, Spalten nach Bedarf an- und abwählen.

<!-- SCREENSHOT -->
![Abbildung: Der Kalender mit Terminen in der Listenansicht]({{ img_url_desktop }}Kalender/5_kalender_termine_listenansicht_light_1920x1020.png#only-light){.desktop-img}
![Abbildung: Der Kalender mit Terminen in der Listenansicht]({{ img_url_desktop }}Kalender/5_kalender_termine_listenansicht_dark_1920x1020.png#only-dark){.desktop-img}
![Abbildung: Der Kalender mit Terminen in der Listenansicht]({{ img_url_mobile }}Kalender/5_kalender_termine_listenansicht_light_1280x720.png#only-light){.mobile-img}
![Abbildung: Der Kalender mit Terminen in der Listenansicht]({{ img_url_mobile }}Kalender/5_kalender_termine_listenansicht_dark_1280x720.png#only-dark){.mobile-img}

Sowohl Blatt als auch Liste lassen sich mit den drei kleinen Buttons am rechten Rand – direkt über dem eigentlichen Kalenderblatt – als Tages-, Wochen- oder Monatsansicht anzeigen.

Die neu hinzugekommene Ansicht Split ist nur in der Blattansicht wirksam; sie zeigt die Kalender mehrerer ausgewählter Benutzer nebeneinander.

Der Button Farbe soll der Übersichtlichkeit dienen. Hier können die angezeigten Termine farblich nach Organisator, Teilnehmer, Tag oder Rollen unterschieden werden.

## Termin hinzufügen/bearbeiten

### Allgemeine Termindaten eingeben und Teilnehmer einladen

Klicken Sie nun den ersten Button des Bearbeitungsmenüs, Termin hinzufügen.

<!-- SCREENSHOT -->
![Abbildung: Bearbeitungsmaske zum Anlegen eines Termins]({{ img_url_desktop }}Kalender/6_kalender_neuer_termin_light_1920x1020.png#only-light){.desktop-img}
![Abbildung: Bearbeitungsmaske zum Anlegen eines Termins]({{ img_url_desktop }}Kalender/6_kalender_neuer_termin_dark_1920x1020.png#only-dark){.desktop-img}
![Abbildung: Bearbeitungsmaske zum Anlegen eines Termins]({{ img_url_mobile }}Kalender/6_kalender_neuer_termin_light_1280x720.png#only-light){.mobile-img}
![Abbildung: Bearbeitungsmaske zum Anlegen eines Termins]({{ img_url_mobile }}Kalender/6_kalender_neuer_termin_dark_1280x720.png#only-dark){.mobile-img}

<!--Termin,hinzufügen-->
Sie erhalten die Objektmaske mit dem aus anderen Anwendungen bereits bekannten Standardreitern und einigen weiteren im unteren Teil. Beachten Sie bitte, dass ein Aufruf dieser Maske über den Button Termin hinzufügen das Termindatum und den Zeitpunkt auf die aktuelle Systemzeit setzt. Sollten Sie hingegen einen Termin an einem beliebigen anderen Zeitpunkt eintragen wollen, tun Sie das besser über einen Rechtsklick und (Termin hinzufügen) an der betreffenden Stelle im Kalenderblatt. Die sich öffnende Maske ist dieselbe.

!!! tip "Tipp"
    Zum schnellen Eingeben eines Termins ohne die Eingabemaske ziehen Sie mit gedrückter linker Maustaste vom Beginn bis zum gewünschten Ende des gewünschten Termins über den Kalender. In diesem Falle müssen Sie nur noch das Thema eingeben.

Das Thema ist ein Pflichtfeld – tragen Sie darum zum Test irgend etwas ein. Das Pulldown Ansicht steht standardmäßig beim Öffnen des Fensters auf Organisator, also desjenigen, der den Termin angelegt hat und verwalten darf. In der Ansicht Teilnehmer sind wesentliche Einstellungsoptionen ausgegraut. Das ist der Fall, wenn Sie einen Termin aufrufen, den Sie selbst nicht angelegt haben – darauf kommen wir noch zu sprechen. Belassen Sie Ansicht also jetzt auf Organisator.

Im Feld Ort müssen Sie keine Eingabe machen. Beginn und Ende sind natürlich Datums- und Zeiteingaben; beachten Sie, dass Ihnen hier sowohl die direkte Eingabe über die Tastatur als auch eine komfortable Anwahl über den rechts befindlichen Kalenderbutton bzw. ein Pulldown bei der Zeit zur Verfügung steht. Der Checkbutton ganztägig bewirkt, dass der Termin zwar als belegt von 00:00 bis 23:59 eingetragen, in der Tages- und Wochenansicht des Kalenderblatts jedoch nicht über die gesamte Tageszeit markiert wird, sondern nur als schmaler Streifen direkt unterhalb des Tabellenkopfes.

In das freie Feld rechts neben der Zeitangabe für Ende können Sie nichts eintippen. Wenn Sie den Termin speichern und wieder aufrufen, sehen Sie, dass das System dieses Feld automatisch mit der Zeitzone ausfüllt. tine ist damit auch in multinationalen Unternehmen zur Terminplanung einsetzbar, die Zeitzonen-übergreifend arbeiten.

Das Pulldown Gespeichert in dient zur Auswahl eines Kalenders für die Speicherung des Termins. Als Vorgabe wird der in den benutzerspezifischen Einstellungen (siehe [Benutzerspezifische Einstellungen](na_Benutzereinstellungen.md)) ausgewählte Kalender benutzt; standardmäßig ist das Ihr eigener. Klicken Sie jetzt dieses Pulldown an. Es werden Ihnen zunächst Ihr eigener (oder auch mehrere eigene) Kalender sowie die drei Kalender angezeigt, auf die Sie zuletzt zugegriffen haben. Sollten Sie jetzt zum ersten Mal dieses Pulldown angeklickt haben, ist die Liste noch leer; einzig der Menüpunkt Andere Kalender wählen... wird angeboten. Klicken Sie diesen an, öffnet sich ein Fenster mit der Kalender-Baumstruktur Ihrer tine-Installation:

<!-- SCREENSHOT -->
![Abbildung: Auswahl, in welchem Kalender ein Termin gespeichert werden soll]({{ img_url_desktop }}Kalender/7_kalender_neuer_termin_kalenderauswahl_light_1920x1020.png#only-light){.desktop-img}
![Abbildung: Auswahl, in welchem Kalender ein Termin gespeichert werden soll]({{ img_url_desktop }}Kalender/7_kalender_neuer_termin_kalenderauswahl_dark_1920x1020.png#only-dark){.desktop-img}
![Abbildung: Auswahl, in welchem Kalender ein Termin gespeichert werden soll]({{ img_url_mobile }}Kalender/7_kalender_neuer_termin_kalenderauswahl_light_1280x720.png#only-light){.mobile-img}
![Abbildung: Auswahl, in welchem Kalender ein Termin gespeichert werden soll]({{ img_url_mobile }}Kalender/7_kalender_neuer_termin_kalenderauswahl_dark_1280x720.png#only-dark){.mobile-img}

Je nachdem, welche Kalender angelegt sind und welche Zugriffsberechtigungen Sie darauf besitzen, können Sie hier noch weitere Kalender zum Speichern Ihres Termins auswählen. Zum Beispiel könnten Sie unter dem Ordner Gemeinsame Kalender einen dort angelegten Team-Kalender verwenden oder auch unter Kalender anderer Benutzer einem anderen Mitarbeiter einen Termin zuweisen, sofern Sie auf dessen Kalender Schreibrechte besitzen. In jedem Falle würde beim nächsten Aufruf des Kalenderauswahlmenüs dieser Kalender schon beim anklicken des Pulldowns Gespeichert in zur vereinfachten Bedienung mit angeboten werden.

In der Anwendung Admin ([Administration](oa_Administration.md)) finden Sie alle Kalender als sogenannte Container wieder. Dort können Sie Berechtigungen prüfen und gegebenenfalls ändern, sofern Sie wiederum dazu berechtigt sind.

Rechts neben den Ort- und Zeitfeldern finden Sie ein Quadrat mit drei Checkbuttons: nicht-blockierend, Vorläufig und Privat. Die Anwahl von nicht-blockierend führt dazu, dass ein später eingegebener und sich mit diesem zeitlich überschneidender Termin nicht zu einer Warnmeldung führt. Die Kennzeichnung eines Termins als Vorläufig bedeutet, das Sie als Organisator einen Termin zwar geplant, aber selbst bisher noch nicht verbindlich zugesagt haben. Diese Information wird evtl. eingeladenen anderen Teilnehmern übermittelt. Zudem können Sie Vorläufig auch als Filter setzen und sich so nur die vorläufigen Termine anzeigen lassen, unabhängig davon, ob es sich um Termine mit anderen Teilnehmern oder nur eigene handelt.

Was bewirkt die Markierung von Privat? Die "normalen" Rechte des Kalenders werden hiermit außer Kraft gesetzt und nur Teilnehmer können den Inhalt des Termins sehen. Standardmäßig wird anderen tine-Benutzern beim Aktivieren von Privat nur Datum und Uhrzeit angezeigt. (Lesen Sie im [Administration - Kalender](oa_Administration.md/#kalender), wie man dies ändert oder ergänzen kann.) Man kann anderen tine-Benutzern aber auch die Berechtigung für seine "Privat"-markierten Termine geben. Dazu navigieren Sie zu den Berechtigungen für Ihren persönlichen Kalender (erreichbar über Kalender -> Meine Kalender -> <Ihr Name> persönlicher Kalender, Rechtsklick, Kalender Berechtigungen verwalten). Hier finden Sie ebenfalls einen Checkbutton Privat – ist er markiert, kann jeder Benutzer, der Zugriff auf Ihren Kalender hat, auch die "Privat"-markierten Termine einsehen. Mit diesen beiden Einstellungen entscheiden Sie also über Ihre Privatsphäre bei Termineingaben und darüber, ob andere Kalenderbenutzer Ihre privaten Termine in deren Zeitplanung einbeziehen sollen oder nicht.

Außerdem gibt es in den Berechtigungen noch einen Checkbutton frei/belegt; wenn Sie diesen markieren, werden andere Benutzer bei der Terminvergabe vor Überschneidungen mit Ihren Terminen gewarnt, wenn jene Sie einladen wollen.

<!--Termin, andere Teilnehmer-->
<span id="event-attendeegrid"></span>
Im linken unteren Teil der Eingabemaske finden Sie noch drei weitere, für die Termineingabe wichtige Reiter: Im Reiter Teilnehmer sehen Sie standardmäßig drei Spalten: Rolle, Name und Status. Alternativ können über dem Tabellensymbol auf der rechten Seite Gespeichert in und Typ hinzugefügt werden.
Als ersten Teilnehmer sehen Sie natürlich sich selbst.

<!-- SCREENSHOT -->
![Abbildung: Hinzufügen von Teilnehmern zu einem Termin]({{ img_url_desktop }}Kalender/8_kalender_teilnehmer_hinzu_light_1920x1020.png#only-light){.desktop-img}
![Abbildung: Hinzufügen von Teilnehmern zu einem Termin]({{ img_url_desktop }}Kalender/8_kalender_teilnehmer_hinzu_dark_1920x1020.png#only-dark){.desktop-img}
![Abbildung: Hinzufügen von Teilnehmern zu einem Termin]({{ img_url_mobile }}Kalender/8_kalender_teilnehmer_hinzu_light_1280x720.png#only-light){.mobile-img}
![Abbildung: Hinzufügen von Teilnehmern zu einem Termin]({{ img_url_mobile }}Kalender/8_kalender_teilnehmer_hinzu_dark_1280x720.png#only-dark){.mobile-img}

Bei dem Feld Rolle findet man bei jeden Teilnehmer ein Pulldown Menü mit den beiden Werten Erforderlich und Freiwillig – hier legen Sie fest, welche eingeladenen Personen einen Termin zusagen _müssen_, damit er zustande kommt, und welche Teilnehmer anwesend sein _können_. Die Standardeinstellung ist Erforderlich – tine geht also davon aus, dass alle eingeladenen Teilnehmer auch zusagen müssen, damit der Termin zustande kommt.

Legen Sie nun einen neuen Teilnehmer an! Gehen Sie dazu auf die Zeile unter dem zuletzt angelegten Teilnehmer (in unserem Falle sollten das jetzt nur Sie selbst sein) und klicken Sie auf das Feld Klicken Sie hier, um neue Teilnehmer einzuladen.... Sie können jetzt direkt den Namen der Personen eintippen. Wenn Sie die ersten drei Buchstaben eingetippt haben, fängt tine automatisch an die Kontakte zu filtern. Als Teilnehmer werden Benutzer, Gruppen oder Ressourcen verstanden. Dazu gleich mehr.

Die Option Typ zeigt Ihnen an, um welchen Teilnehmer-Typen es sich handelt, also ob der Teilnehmer eine Gruppe, Ressource oder ein Benutzer ist.
Sie können dieses Feld auch als "Vorab-Filter" nutzen. Klicken Sie dazu auf das leere Feld, dass sich im Reiter Typ befindet und wählen Sie die Teilnehmer-Art aus. Nun werden in der Suche nur Teilnehmer dieses Typs angezeigt.

<!-- SCREENSHOT -->
![Abbildung: Typen von Teilnehmern]({{ img_url_desktop }}Kalender/9_kalender_termin_teilnehmertyp_light_1920x1020.png#only-light){.desktop-img}
![Abbildung: Typen von Teilnehmern]({{ img_url_desktop }}Kalender/9_kalender_termin_teilnehmertyp_dark_1920x1020.png#only-dark){.desktop-img}
![Abbildung: Typen von Teilnehmern]({{ img_url_mobile }}Kalender/9_kalender_termin_teilnehmertyp_light_1280x720.png#only-light){.mobile-img}
![Abbildung: Typen von Teilnehmern]({{ img_url_mobile }}Kalender/9_kalender_termin_teilnehmertyp_dark_1280x720.png#only-dark){.mobile-img}

Im Feld Gespeichert in sehen Sie bei der Neuanlage eines Termins zunächst nichts. Es zeigt, in welchen Kalender der Teilnehmer diesen Termin gespeichert hat. Bei Ihnen selbst wird das der mit dem Pulldown Gespeichert in ausgewählte Kalender sein; bei einem anderen Teilnehmer entsprechend ein anderer – gemäß Standardeinstellungen ist es der persönliche Kalender des Teilnehmers.

!!! tip "Tipp"
    Um einen Termin zu finden, wo mehrere Teilnehmer Zeit haben, können Sie, nachdem Sie die entsprechenden Teilnehmer ausgewählt haben, ganz oben links auf Termin finden klicken. Das öffnet eine Kalenderansicht mit den Terminen der jeweiligen Teilnehmer.

<!--Ressource-->
Ein Benutzer, oder im Fall des Kalenders, ein Teilnehmer kann ein in tine angelegter Benutzer sein oder eine beliebige Person aus irgendeinem tine-Adressbuch sein. Sie können also auch Kunden, Lieferanten oder wen auch immer einladen – insofern ist die Bezeichnung "Benutzer" hier etwas irreführend.
Mit Gruppe ist natürlich eine Ihrer gültigen tine-Benutzergruppen gemeint, die Sie über diese Funktion geschlossen zum Termin einladen können.
Eine Ressource ist ein Hilfsmittel, z.B. ein Besprechungsraum oder ein Präsentationsgerät, das Sie für den Termin reservieren wollen.

Falls Ihnen das Programm jetzt keine Ressourcen anbietet: Diese können sowohl in den Stammdaten als auch der Admin-Anwendung verwaltet werden. Mehr zu Ressourcen und ihrer Konfiguration können Sie in diesem Kapitel nachlesen [Administration - Kalender](oa_Administration.md/#kalender).

Ressourcen können über einen eigenen Kalender verfügen, genau wie jeder tine-Benutzer. Sie rufen den Kalender darum auch über die TEILNEHMER-Ansicht links neben dem Kalenderfenster auf. Wenn Sie also eine Ressource buchen wollen, sollten Sie zuvor den entsprechenden Kalender einblenden, um deren Verfügbarkeit im Blick zu haben.

Nachdem Sie eine Ressource angelegt haben, buchen Sie diese zum Termin und laden Sie sich probehalber von jedem Typ einen Teilnehmer ein – bei Benutzer einen echten tine-Benutzer und einen fremden Teilnehmer aus dem Adressbestand. Bei Benutzer werden Ihnen alle gültigen Adressen Ihrer tine-Adressbücher zur Auswahl angeboten, nicht nur die eigentlichen Benutzer der Anwendung. Sie können in das Fenster sogar eine beliebige E-Mail-Adresse eingeben; der Adressinhaber erhält dann eine Einladung und seine E-Mail-Adresse landet als Kontaktdatensatz in Ihrem Standardadressbuch.

<!--ICS-Format-->
Sie sollten jetzt einen Organisator, mindestens noch einen Teilnehmer aus dem Kreis Ihrer tine-Benutzer, einen externen Teilnehmer, eine Benutzergruppe und eine Ressource – also insgesamt fünf Teilnehmer – testweise eingeladen haben. Sehen Sie sich nun das Feld Status an. Bei Ihrem eigenen Eintrag steht ein Haken und daneben Zugesagt – klar, es ist ja auch Ihr eigener Termin. Alle anderen Teilnehmer dürften jedoch mit dem Eintrag Keine Antwort versehen sein. Bei dem externen Teilnehmer ist der Inhalt des Statusfeldes nicht ausgegraut, denn es handelt sich um ein Pulldown. Klicken Sie es an und Sie erhalten die Varianten Zugesagt, Abgesagt und Vorläufig. Diese Möglichkeit, Eingaben manuell zu machen, ist nur für den Fall vorgesehen, dass der externe Teilnehmer kein mit dem ICS-Standard[^1] kompatibles Kalenderprogramm nutzt. tine versendet an diese Teilnehmer eine Einladungs-E-Mail mit einem Antwort-Menü. Wenn der Teilnehmer diese E-Mail mit einem ICS-kompatiblen Client bearbeitet, der selbst auch einen Kalender enthält, erzeugt dieses Programm eine Antwort-E-Mail, die an den tine-Organisator zurückgesendet und verarbeitet wird, sodass der Termin ohne weitere Bearbeitung als zu- oder abgesagt gemeldet werden kann.

[^1]:
    iCalendar ist ein Datenaustauschformat, das speziell für Kalenderinhalte definiert wurde und bei Öffnung des Dokuments (mit der Dateiendung ics) dem Empfänger automatisch einen Eintrag in seinem (ICS-kompatiblen) Kalender erzeugt (vgl. RFC 5545).

Jeder eingeladene Teilnehmer, der selbst einer Ihrer tine-Benutzer ist, erhält vom System ebenfalls eine automatische Benachrichtigungs-E-Mail. Im Text dieser E-Mail findet der Empfänger auch die Menüpunkte Annehmen, Vielleicht und Ablehnen. Wenn er eine dieser Möglichkeiten anklickt, wird dem Organisator darüber eine Bestätigungs-E-Mail zugesandt.

Darüber hinaus wird, ebenfalls vollautomatisch, der Termin bereits mit Einladung des Teilnehmers in seinen eigenen Terminkalender eingetragen sowie die o.g. Entscheidung ebenfalls jederzeit upgedatet. Wenn der Teilnehmer den Termin aufruft, sieht er anhand des Eintrages in Ansicht (hier jedoch nicht Organisator, sondern Teilnehmer!), dass es sich um den Termin eines anderen Organisators handelt.

!!! warning "Achtung"
    Wenn man einen Teilnehmer einlädt, der eine andere tine-Instanz benutzt, erhält dieser eine E-Mail mit einer anhängenden ICS-Datei, aber keinen Kalendereintrag und auch nicht das o.g. Antwortmenü.
    Wie kommt er dennoch zu einem Eintrag und der Einladende zu seiner Antwort?
    Naheliegenderweise öffnet er dazu vielleicht die anhängende ICS-Datei. Diese Anfrage übergibt der Browser aber jetzt an das lokale Computersystem und das lässt entweder fragen, welches Programm die ICS-Datei öffnen soll (wenn diese Art von Datei dem System bisher unbekannt war) oder es öffnet sie mit der für ICS-Dateien hinterlegten Standardsoftware (bspw. MS Outlook oder Evolution). Das führt natürlich nicht zu dem gewünschten Ziel, also der Eintragung des Termins in die entsprechende tine-Instanz. Es lässt sich aber nicht vermeiden, da tine als Webanwendung natürlich auf einem Computersystem nicht "bekannt" ist. Wie Sie dennoch den Termin einlesen können, erfahren Sie am Ende dieses Kapitels unter [Termine importieren](da_Kalender.md/#termine-importieren).

<!-- SCREENSHOT -->
![Abbildung: Termin eines anderen Organisators]({{ img_url_desktop }}Kalender/10_kalender_termin_anderer_organisator_light_1920x1020.png#only-light){.desktop-img}
![Abbildung: Termin eines anderen Organisators]({{ img_url_desktop }}Kalender/10_kalender_termin_anderer_organisator_dark_1920x1020.png#only-dark){.desktop-img}
![Abbildung: Termin eines anderen Organisators]({{ img_url_mobile }}Kalender/10_kalender_termin_anderer_organisator_light_1280x720.png#only-light){.mobile-img}
![Abbildung: Termin eines anderen Organisators]({{ img_url_mobile }}Kalender/10_kalender_termin_anderer_organisator_dark_1280x720.png#only-dark){.mobile-img}

So funktioniert prinzipiell auch das Bestätigen der Buchung einer Ressource. In der Logik der Kalender-Anwendung ist eine Ressource ein Teilnehmer wie jeder andere. Will jemand eine Ressource buchen, die bereits ein anderer reserviert hat, zeigt das System eine entsprechende Meldung. Hier ist die Bestätigung also nicht notwendig. Die Bestätigung der Buchung durch einen Verantwortlichen kann aber durchaus gewollt und sinnvoll sein, wenn man im Unternehmen die Benutzung von Ressourcen explizit steuern möchte, z.B. bei wertvolleren Ressourcen, wie Dienstwagen.

Teilnehmer, die nicht an Ihr tine-System angeschlossen sind, aber eine gültige E-Mail-Adresse in ihrem Adressdatensatz besitzen, erhalten ebenfalls automatisch eine E-Mail. An diese E-Mail ist ebenfalls das oben bereits erwähnte ICS-Dokument angehängt.

!!! info "Wichtig"
    Die hier beschriebenen Benachrichtigungsvorgänge werden nicht nur beim Anlegen, sondern auch bei jeder Änderung und bei der Absage eines Termins in Gang gesetzt. Sie müssen also nicht extra dafür sorgen, dass die Teilnehmer benachrichtigt werden.

Darüber hinaus haben Sie als Organisator die Möglichkeit, allen eingeladenen Personen in einem Arbeitsgang eine E-Mail zu senden – z.B. als Erinnerung oder mit ergänzenden Informationen.

<!-- SCREENSHOT -->
![Abbildung: Kontextmenü eines Termins]({{ img_url_desktop }}Kalender/11_kalender_termin_kontextmenue_light_1920x1020.png#only-light){.desktop-img}
![Abbildung: Kontextmenü eines Termins]({{ img_url_desktop }}Kalender/11_kalender_termin_kontextmenue_dark_1920x1020.png#only-dark){.desktop-img}
![Abbildung: Kontextmenü eines Termins]({{ img_url_mobile }}Kalender/11_kalender_termin_kontextmenue_light_1280x720.png#only-light){.mobile-img}
![Abbildung: Kontextmenü eines Termins]({{ img_url_mobile }}Kalender/11_kalender_termin_kontextmenue_dark_1280x720.png#only-dark){.mobile-img}

Über Nachricht verfassen im Bearbeitungsmenü oder das Kontextmenü eines Termins öffnet sich ein E-Mail-Fenster mit der Terminbezeichnung im Betreff und allen in den Adressdatensätzen hinterlegten E-Mail-Adressen der eingeladenen Teilnehmer.

Eine E-Mail können Sie zudem auch aus dem Terminfenster heraus versenden, allerdings nur an einzelne Teilnehmer. Rufen Sie dazu den eben gespeicherten Termin noch einmal auf (Rechtsklick Termin bearbeiten) und klicken Sie ebenfalls wieder rechts auf den entsprechenden Teilnehmer. Das Kontextmenü erlaubt Ihnen, den Teilnehmer zu löschen oder ihm eine E-Mail zuzusenden (oder, allerdings nur bei angeschlossener Telefonanlage, den Teilnehmer anzurufen). Wählen Sie jetzt Nachricht verfassen, es öffnet sich ein E-Mail-Fenster mit der E-Mail Adresse des Kontaktes.

Lassen Sie das Terminfenster geöffnet – wir wollen noch die weiteren Tabellenspalten besprechen:
Unter Gespeichert in sehen Sie bei den internen Teilnehmern die Bezeichnungen der Kalender dieser tine-Benutzer, bei den externen keine Einträge, da sie ja nicht an Ihr System angeschlossen sind. Unter Status steht wahrscheinlich jetzt immer noch Keine Antwort. Für die externen Teilnehmer können Sie selbst den Status hier über das Pulldown verändern; das werden Sie dann tun, wenn Ihnen der Teilnehmer eine Antwort auf Ihre E-Mail geschickt hat. Mögliche Einstellungen sind, wie oben schon besprochen: zugesagt, abgesagt und vorläufig. Beachten Sie, dass bei den internen Teilnehmern das System diese Einstellungen selbst entsprechend der Antwort des Teilnehmers setzt.

Als eingeladener Benutzer sehen Sie den Termin zunächst wie einen eigenen. Rufen Sie ihn auf, sehen Sie in Ihrer Zeile den Status jetzt nicht mehr ausgegraut, sondern als Pulldown. Damit können Sie Ihre Antwort per Mausklick einstellen. Es geht aber auch schneller.

<!-- SCREENSHOT -->
![Abbildung: Antwort des Eingeladenen]({{ img_url_desktop }}Kalender/12_kalender_termin_antworten_light_1920x1020.png#only-light){.desktop-img}
![Abbildung: Antwort des Eingeladenen]({{ img_url_desktop }}Kalender/12_kalender_termin_antworten_dark_1920x1020.png#only-dark){.desktop-img}
![Abbildung: Antwort des Eingeladenen]({{ img_url_mobile }}Kalender/12_kalender_termin_antworten_light_1280x720.png#only-light){.mobile-img}
![Abbildung: Antwort des Eingeladenen]({{ img_url_mobile }}Kalender/12_kalender_termin_antworten_dark_1280x720.png#only-dark){.mobile-img}

Sie müssen das Termin-Bearbeitungsfenster gar nicht erst öffnen, sondern können schon auf dem Kalenderblatt mit Rechtsklick das Kontextmenü aufrufen und über Meine Antwort setzen dem Organisator zurückmelden, ob Sie (vorläufig oder nicht) teilnehmen. Bei Abgesagt verschwindet der Termin nach ein paar Sekunden (wenn er gespeichert wurde) aus Ihrem Kalender – sofern Ihre Filterkriterien entsprechend eingestellt sind, was standardmäßig der Fall ist. Allerdings können Sie Ihre Kalenderfilter auch so anpassen, dass abgesagte Termine angezeigt bleiben. Wie die Filterkriterien eingestellt werden, erläutern wir in [Allgemeine Hinweise zur Bedienung - Suchfilter für die Tabellenansicht](ca_StandardBedienhinweise.md/#suchfilter-fur-die-tabellenansicht); die Ausführungen dort gelten nicht nur für die in den anderen tine-Anwendungen übliche Tabellenansicht, sondern auch für den Kalender.

Übrigens erhalten Sie auch unterhalb des Kalenderblatts Statusinformationen zu diesem Termin, solange Sie in Ihrem Kalender auf keine andere Stelle klicken.

<!-- SCREENSHOT Abbildung 3.14 -->
![Abbildung: Alle Informationen zu einem Termin unterhalb der Kalenderansicht]({{ img_url }})

Diese Meldungen bleiben bei abgesagten Terminen bis zum nächsten Browser-Refresh oder Mausklick auf eine andere Stelle des Kalenders stehen.

Kommen wir noch schnell zum Pulldown Ansicht, dessen Besprechung wir weiter oben verschoben hatten. Die Funktion der Ansicht hat nur Sinn bei mehreren Teilnehmern. Mit dieser Funktion können Sie als Organisator sich in die Lage der anderen Teilnehmer versetzen und den Termin aus ihrer Perspektive sehen.

### Wiederholungen

<!--Termin,Wiederholungen-->
Über den Reiter Wiederholungen im mittleren Teil des Termineingabefensters können Sie sich wiederholende Termine definieren. Klicken Sie diesen Reiter an – die Standardeinstellung ist Keine.

Klicken Sie nun den Button Täglich an; im Feld neben Jeden geben Sie als Zahl den Wiederholungsabstand an, d.h. ob sich ein Termin jeden, oder nur jeden 2., 3. usw. Tag wiederholen soll. Darunter gibt es die Möglichkeit, ein Ende der Terminkette zu definieren. Der Standard steht auf niemals, d.h. auf einer endlosen Kette. Sie können stattdessen ein explizites Datum oder ein Ende nach einer bestimmten Anzahl von Terminen bestimmen.

Unter Wöchentlich finden Sie wieder den Wiederholungsabstand, diesmal in Wochen, sowie sieben, den Wochentagen entsprechende Checkbuttons, an denen die Terminwiederholungen stattfinden. Die Definition des Endes der Terminkette ist analog.

Die Einstellungen zu Monatlich bieten Ihnen, neben dem schon bekannten Prinzip des Wiederholungsabstandes und des Endes der Terminkette, zwei Dropdown-Menüs an, mit denen sie die Auswahl des ersten, zweiten, dritten, vierten oder letzten Wochentages steuern können. Alternativ können Sie mit Hilfe des darunterliegenden Textfeldes eine Angabe zu einen bestimmten Monatstag als Zahl machen.

Bei der Auswahl Jährlich wird naheliegenderweise kein Wiederholungsabstand mehr angeboten. Sie können hier den Tag entweder als ersten, zweiten, dritten, vierten oder letzten Wochentag oder als über eine Ordinalzahl bestimmten Tag eines bestimmten Monats definieren. Die Festlegung des Terminketten-Endes erfolgt wieder wie oben.

### Alarm

<!--Termin,Alarm auslösen-->
Standardmäßig ist festgelegt, dass keine Alarmzeit aktiviert ist. Diese Einstellung können Sie verändern; lesen Sie dazu bitte unter [Benutzerspezifische Einstellungen - Kalender](na_Benutzereinstellungen.md/#kalender) nach. Die Alarmbenachrichtigung [Umfrage](da_Kalender.md/#umfrage) erledigt das System über eine E-Mail, die es allen bestätigten Teilnehmern des Termines zusendet.

Wenn Sie den Reiter Alarm anklicken, haben sie die Möglichkeit sich über den Punkt Alarmzeit eine der vordefinierten Alarmzeiten zu konfigurieren.
Sollte keiner der Alarme Ihnen zusagen, wählen sie den Punkt Benutzerdefinierter Zeitpunkt. Hier können Sie nun Datum und Uhrzeit der Alarmbenachrichtigung genau bestimmen.


!!! warning "Achtung"
    Diese E-Mail wird, in Abhängigkeit von der Größe Ihrer tine-Installation und anderen Faktoren (wie z.B. Leistungsfähigkeit der Server-Hardware), mit Zeitverzögerung von einer bis zu mehreren Minuten versendet. Sie sollten also bei der Bemessung der Benachrichtigungszeiten diese Verzögerungen mit einkalkulieren.

### Umfrage
<!--Termin,Umfragetool-->
Das Umfrage-Tool soll bei der Terminfindung eine Hilfestellung geben.
Über Optionen können Sie die Einstellungen der Umfrage bearbeiten. Zum Beispiel welchen Namen die Umfrage haben soll, ob diese Passwort geschützt ist usw… Des Weiteren finden Sie hier den Link zu der von Ihnen kreierten Termin-Umfrage. Andere zur Auswahl stehende Termine fügen Sie unter dem Reiter Alternative Termin

!!! warning "Achtung"
    Der Termin wird bei der Aktivierung der Umfrage automatisch auf "Vorläufig" gesetzt.

#### Umfrage Link
Der Link zur Umfrage wird an alle Teilnehmer automatisch per E-Mail gesendet.

<!--
SCREENSHOT Abbildung 3.15
-->
![Abbildung: Umfrage Übersicht]({{ img_url }}

Mit einem Klick auf den Briefumschlag sagt man den Termin zu. Dies wird auch mit einer grünen Farbe verdeutlicht. Ein zweiter Klick färbt das Feld Rot, hier ist der Termin nun abgesagt. Ein dritter Klick auf das gleiche Feld verändert den Status auf vorläufig, dies wird durch die gelbe Farbe verdeutlicht. Diesen Prozess wiederholt man dann für jeden der Termine.

Man kann den Umfrage-Link auch manuell an Teilnehmer senden, diese werden dann nicht automatisch mit Ihrem Namen in der Umfrage auftauchen. Diese Teilnehmer haben dann die Möglichkeit, ihren Namen zu der Umfrage hinzuzufügen.

!!! info "Wichtig"
    Um Ihre Einträge zu speichern, müssen Sie am Ende auf Speichern klicken. Hierfür gibt es keinen Automatismus.

## Termin löschen

Wenn Sie als Organisator einen Termin löschen, ist er sowohl in Ihrem als auch in allen Kalendern der von Ihnen eingeladenen Personen gelöscht. Sowohl interne als auch externe Teilnehmer erhalten eine Mitteilung, dass der Termin abgesagt wurde. Außerdem verschwindet bei den internen Teilnehmern der Termin vom Kalender. Sollten Sie – nicht als Organisator, sondern als eingeladener Teilnehmer – einen Termin aus Ihrem Kalender löschen (das ist möglich), verschwindet er aus Ihrem Kalender, aber natürlich nicht aus dem des Organisators. Dieser erhält nur automatisch eine Absage von Ihnen.

## Drucke Seite

Mit dieser Funktion erhalten Sie eine Seite im Querformat, die, je nach gewählter Ansicht (Blatt, Liste, Tag, Woche, Monat) und angewählten Feldern, die Termine ausdruckt.

## Termine importieren

<!--Termin,Fremdquellen einlesen-->
<!--ICS-Format-->
<!--iCal-->
Die Funktion Importiere Termine ist gewissermaßen dreiteilig. Sie sehen das, wenn Sie das zugehörige Bearbeitungsmenü öffnen und dort unter Wählen Sie den Typ der Quelle aus das Pulldown Hochladen anklicken.

Sie erhalten als Auswahl Hochladen, was bedeutet, dass Sie eine Datei mit Termindaten von Ihrem Dateisystem einlesen können, und Entfernt/ICS, was Ihnen die (einmalige oder dauerhafte) Replikation mit einem externen Kalender erlaubt. Zuletzt gibt es noch die Option Remote / CalDAV (BETA)

Beschäftigen wir uns zunächst mit der Variante Hochladen: Der Button Wählen Sie die Datei mit Ihren Termine öffnet den browserinternen Dialog zum Datei-Upload. Damit können Sie eine entsprechende Datei mit Kalenderdaten auswählen. Die derzeitigen unterstützten Formate dafür sind iCAl und CSV. Sie erhalten dazu im unteren Bereich des Fensters ein Auswahl-Pulldown wo Sie Ihr gewähltes Format Einstellen können.

In der Mitte des Fensters können Sie unter Allgemeine Einstellungen über das Pulldown einen vorhandenen Kalender auswählen. Die Anwahl von Andere Kalender wählen bietet Ihnen dazu in einem neuen Fenster die Baumstruktur der Kalender an, wie Sie sie von der linken Seite des tine-Bildschirms her kennen.

Wenn Sie mit Wählen Sie die Datei mit Ihren Termine den Browserdialog zum Hochladen aktiviert und eine Datei mit *.ICS-Endung oder *.CSV ausgewählt haben, verändert sich der Button Ende rechts unten von "Ausgegraut" auf "Aktiv" und Sie können den Einlesevorgang mit Klicken auf diesen Button abschließen.

!!! warning "Achtung"
    Die Software nimmt derzeit keine vorhergehende Prüfung dieser *ICS-Datei vor! Sollte Ihre Datei nicht den Konventionen von iCal entsprechen, bringt der Einleseversuch das Fenster zum Absturz. Sie erhalten zunächst eine Meldung Programmabbruch mit der Aufforderung, eine Fehlerbeschreibung einzuschicken. Das ist das Standardprozedere für solche Fälle und Sie können es hier getrost ignorieren, denn der Fehler ist klar. Wenn Sie also hier auf Abbrechen klicken, bleibt der Importdialog mit der Meldung Importiere Termine stehen. Schließen Sie einfach das Fenster mit einem Klick auf das Schließ-Symbol (je nach Browser und Betriebssystem verschieden, links oder rechts).
    Prüfen Sie sodann die Datei auf Korrektheit (z.B. indem Sie sich mit einer anderen Kalendersoftware, wie Evolution oder MS Outlook, einen Termin exportieren und die Syntax der beiden Dateien vergleichen).

Wenn der Einlesevorgang korrekt abgelaufen ist, erscheint eine Vollzugsmeldung, die Sie mit Klicken auf Ok schließen. Die eingelesenen Termine sind dann (allerdings mit einer systembedingten Zeitverzögerung von einer bis zu mehreren Minuten) im ausgewählten Kalender (also auch in dessen Farbe) sichtbar.

Kommen wir nun im weiteren zu der zweiten Möglichkeit, Entfernt/ICS.

Das Eingabefeld mit der Inschrift `http://example.ics` dient der Eingabe einer vollständigen Quellenangabe einer *.ICS-Datei im Internet, aus der sich Ihr (analog vorhergehendem Abschnitt beschriebener) Kalender die zu importierenden Termine holen soll. Wie oft er das tut, können Sie mit dem Pulldown Aktualisierungszeit (das besser "Aktualisierungsintervall" heißen sollte) einstellen: Standardmäßig steht die Auswahl auf einmalig; zur Auswahl haben Sie noch stündlich, täglich und wöchentlich.

!!! warning "Achtung"
    Das Quellen-Eingabefeld `http://example.ics` wird derzeit keiner Plausibilitätsprüfung unterzogen! Sollten Sie hier eine falsche Quelle, und das muss nicht etwa nur eine ICS-Datei mit falscher Syntax, sondern kann sogar eine andere URL oder eine beliebige Zeichenkette sein, eingeben, wird tine Ihnen dennoch immer die gleiche Meldung (Definition erfolgreich importiert! ausgeben. Allerdings werden Sie in diesem Falle natürlich keine eingelesenen Termine einer externen Quelle in Ihrem Kalender vorfinden.

Beim erfolgreichen Einlesen der Quelldatei werden Ihnen die importierten Termine in Ihrem im o.g. Dialog eingestellten Kalender angezeigt. Sollten Sie nicht einmaliges Einlesen, sondern eines der o.a. Replikations-Intervalle eingestellt haben, läuft dieses ab sofort automatisch ab.

!!! warning "Achtung"
    In der Systemmeldung über das erfolgreiche Einlesen ist von einem sog. "Cronjob" die Rede. Das ist eine Routine, die die hier eingegebene Webadresse abfragt und die Daten einliest. Diese Routine ist nicht ständig aktiv, sondern nur in z.T. größeren Zeitintervallen. Haben Sie also beim Warten auf Ihre einzulesenden Termine Geduld - es kann im Einzelfall durchaus mehr als eine Stunde dauern, ehe ein Ergebnis zu sehen ist.

Die Option Remote / CalDAV (BETA) ist noch als Beta eingestuft. Hier ist der Vorgang ähnlich wie bei der Option Entfernt/ICS. Der einzige Unterschied liegt darin, dass CalDav einen Benutzernamen und das dazugehörige Passwort braucht, um auf die Termindaten zugreifen zu können.


<!--ActiveSync-->
<!--CalDAV-->
## Termine synchronisieren

Die Einrichtung der Synchronisation von tine mit Endgeräten ist zwar nicht Thema des Handbuches, dennoch wollen wir kurz beschreiben, wo Sie die hierfür notwendigen Parameter und Einstellungen in tine vorfinden.

Die _CalDAV_ URL zur Synchronisierung Ihrer Termine können Sie über das Kontextmenü des jeweiligen Kalenders einsehen: Mit Rechtsklick das Kontextmenü öffnen und auf Kalender Eigenschaften klicken. Dort finden Sie eine Zeile mit der Beschriftung CalDAV URL. Diese URL müssen Sie in Ihrem Endgerät zur Synchronisierung eingeben.

Mögliche Einstellungen zur Synchronisation über _ActiveSync_ schlagen Sie bitte in [Benutzerspezifische Einstellungen - ActiveSync](na_Benutzereinstellungen.md/#activesync) sowie in [Administration - ActiveSync Geräte](oa_Administration.md/#activesync-gerate) nach.