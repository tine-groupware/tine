# Administration
## Einleitung und Begriffsklärung

Einleitend zum Kapitel „Administration“ lassen Sie uns bitte noch einmal kurz auf die zu Beginn des Buches besprochenen Unterscheidungskriterien zwischen Groupware und CRM zu sprechen kommen: Wir hatten festgehalten, dass eine Groupware vor allem auszeichnet, die komplexen Interaktionsprozesse zwischen Mitarbeitern ein und desselben Unternehmens zu organisieren und weniger die Beziehungen von Mitarbeitern zu potenziellen oder tatsächlichen Kunden oder Lieferanten.

Und eben jene komplexen Interaktionsprozesse machen es nötig, die Mitarbeiter in funktionelle Gruppen einzuteilen, die sich insbesondere dadurch voneinander unterscheiden, auf welche Art sie mit den im System gespeicherten Daten umgehen können und dürfen. tine hat, z.B. mit Adressverwaltung, Kalender, Aufgabenliste, Dateimanager u.a., verschiedene Teilprogramme (hier im Buch werden sie durchgehend "Anwendungen" genannt), auf welche die einzelnen Mitarbeiter oder Mitarbeitergruppen des Unternehmens unterschiedlich abgestufte Zugriffsberechtigungen haben sollen. Und der Administrationsteil des Programms hat hauptsächlich damit zu tun, genau diese Rechte und Berechtigungen zu verwalten.

<!--Rechte-->
<!--Berechtigungen-->
Da die deutsche Sprache hier (im Unterschied zum Englischen) etwas unscharf ist, lassen Sie uns zu Beginn einige immer wiederkehrende Begriffe klären: Es ist nämlich vor allem wichtig, dass wir zwischen Rechten (engl. "rights") und Berechtigungen (engl. "grants[^1]") zu unterscheiden lernen. Als "Rechte" bezeichnen wir die prinzipiellen Zugriffsmöglichkeiten, die ein Benutzer auf eine bestimmte Anwendung von {branch} hat. Beispielsweise hat ein Benutzer das Recht, auf Adressbücher zuzugreifen. Das drückt sich schon beim Aufruf von {branch} dadurch aus, dass er die Bedienelemente für Adressbücher angeboten bekommt. Ein Mitarbeiter, dessen Rechte das nicht beinhaltet, wird diese Bedienelemente gar nicht sehen. Damit haben wir die sehr praktische Möglichkeit, jedem Benutzer ein quasi individuelles Software-Werkzeug anbieten zu können, in dem er nur genau die Möglichkeiten und Bedienelemente vorfindet, die er für seine Arbeit braucht.

[^1]:
wörtlich auch "Gewährung" oder "Bewilligung"


<!--Rolle-->
Eine zusammengefasste Definition solcher Benutzerrechte wird "Rolle" genannt. Analog zum Begriff der Rolle im Theater wird hier definiert, welche Rolle im gesamten System der Software ein Benutzer spielen darf. Das kann z.B. mit seinem Arbeitsgegenstand im Unternehmen, seinem Qualifikationsgrad oder seiner Stellung in der Unternehmenshierarchie zusammenhängen. Immer jedoch wird es mehrere Mitarbeiter geben, auf welche die gleiche Rolle zutrifft. Rechte werden daher in tine nicht direkt einzelnen Benutzern zugewiesen, sondern es werden zunächst Rollen angelegt, und danach wird dem einzelnen Benutzer eine Rolle zugewiesen. Zur Vereinfachung der Administration können Benutzer auch zu Benutzergruppen zusammengefasst werden, denen dann die Rolle als Gruppe zugewiesen wird -- darauf kommen wir noch zu sprechen.

<!--Tags-->
Weiterhin haben wir jedoch auch die Möglichkeit, sehr spezifisch definierte Zugriffsberechtigungen von Mitarbeitern auf einzelne Datenbanken eines tine-Programmteils, wie bspw. bestimmte Adressbücher, zu definieren. Erinnern Sie sich noch daran, was wir im [Adressverwaltung](ba_Adressbuch.md) und dort speziell im [Adressverwaltung - Kontakte importieren](ba_Adressbuch.md/#kontakte-importieren) dazu gesagt hatten? Weshalb wir überhaupt nur von der Möglichkeit Gebrauch machen sollten, verschiedene Adressbücher anzulegen? Es war nicht die Sortierung von Adressen (das lässt sich über spezielle Filter und die genialen "Tags" viel besser machen!), sondern genau der hier genannte Grund -- darüber Zugriffsrechte definieren zu können! Diese Zugriffsrechte nennen wir (im Unterschied zu den o.g. "Rechten") "Berechtigungen". Z.B. kann ein Benutzer auf ein bestimmtes Adressbuch eine limitierte Schreibberechtigung haben, die es ihm zwar erlaubt, in diesem Adressbuch Kontakte zu verändern (z.B. Rufnummern upzudaten) oder neue Kontakte anzulegen, es ihm aber verbietet, Kontakte zu löschen. Das ist sehr sinnvoll, wenn z.B. ein Praktikant die Aufgabe hat, Adressbestände mit Hilfe des Internets auf den neuesten Stand zu bringen. Da braucht dann niemand Angst zu haben, dass dieser ungeübte Mitarbeiter aus Versehen ein ganzes Adressbuch ins Daten-Nirvana schickt.

<!--externe Mitarbeiter-->
In einer Groupware mit ausgefeilten Zugriffsrechten, wie es tine ist, können wir jedoch auch, z.B. beim Bilden von Arbeitsgemeinschaften mit anderen Unternehmen, externen oder freien Mitarbeitern einen Zugang einrichten, um sie an der gemeinsamen Arbeit an einem Projekt teilhaben zu lassen. Hier wäre es dann sicher sehr sinnvoll, dem Externen z.B. einen Datenexport nicht zu erlauben. All das ist möglich und wir werden es uns auf den nächsten Seiten Schritt für Schritt erarbeiten. Fühlen Sie sich also nicht abgeschreckt von dem Begriff „Administration“ - wir lernen keine kryptischen Konsolenbefehle, um Webserver in einem Rechenzentrum am anderen Ende der Welt zu starten. Bis auf wenige Ausnahmen (die uns aber im Rahmen dieses Buches auch nicht interessieren) lässt sich alles über die grafische Oberfläche einstellen und ist auch für Nicht-IT-Fachleute verständlich.

<!--Gruppen-->
Zurück zur Begriffsklärung: Was hat es mit den "Gruppen" auf sich? Mehrere Benutzer können zu Gruppen zusammengefasst werden. Das kann aus verschiedenen Gründen sinnvoll sein. Einer ist, nicht jedem Benutzer einzeln Rechte in Form von Rollen oder auch einzelne Berechtigungen zuweisen zu müssen, was anderenfalls bei größeren Unternehmen sehr mühselig werden könnte. Gruppen hingegen bieten den Vorteil, eine wohlstrukturierte und übersichtliche Benutzerlandschaft organisieren zu können. Dieser Vorteil kommt insbesondere dann zum Tragen, wenn der Administrator wechselt, neue Benutzer angelegt werden müssen oder Rechte von ganzen Benutzergruppen geändert werden sollen.

!!! note "Anmerkung"
    Wie im Kapitel Adressverwaltung im [Adressverwaltung - Gruppen](ba_Adressbuch.md/#gruppen) angesprochen, unterscheiden wir hier zwischen zwei Gruppen-Arten. Hier im Administrationskapitel reden wir ausschließlich über Systemgruppen (<img src="{{icon_url}}icon_group_full.svg" alt="drawing" width="16"/>)

Ein weiterer Vorteil von Gruppen hat mit der Administration unmittelbar nichts zu tun: Sie erlauben es, Aktionen wie bspw. das Versenden interner E-Mails oder das Einladen ganzer Abteilungen zu einem Besprechungstermin, rationeller abzuarbeiten. Anstatt sich im E-Mail-Client oder Kalender die einzelnen Benutzer zusammenzusuchen, können Sie die Aktion aus der Ansicht einer Gruppe heraus starten oder die Gruppe einladen, sparen sich eine Menge Zeit und können sicher sein, dass Sie auch alle Gruppenmitglieder erreichen. Im Kalender sorgt eine Gruppeneinladung sogar dafür, dass Gruppenmitglieder, die erst nach dem Zeitpunkt der Einladung der Gruppe zu dieser hinzugefügt wurden, noch eingeladen werden. Genauso werden sie wieder ausgeladen, wenn sie vor Verstreichen des Termins aus der Gruppe wieder entfernt werden. Diese Funktionen sind vor allem dann unschätzbar, wenn Sie tine in Unternehmen und Organisationen mit sehr vielen Mitarbeitern einsetzen.

Fassen wir kurz zusammen:

* Das "Recht" ist der definierte Zugriff auf eine gesamte Programmfunktion.
* Die "Berechtigung" ist immer auf einen konkreten Datenteil (in tine üblicherweise "Container" genannt) beschränkt und sehr fein (Lese-, Schreibrecht usw.) einstellbar.
* Die "Rolle" ist eine zusammengefasste Definition von Rechten.
* Die "Gruppe" ist eine unter einem Namen zusammengefasste Anzahl von Benutzern mit bestimmten gemeinsamen Eigenschaften.

Starten Sie tine. Natürlich müssen Sie sich jetzt als ein Benutzer anmelden, der Administratorrechte hat, denn sonst (siehe oben!) wird Ihnen das folgende Menü gar nicht angezeigt. Rufen Sie über den Reiter ganz links tine den Menüpunkt Admin auf (wenn er nicht schon als Reiter angezeigt wird). Sie erhalten links eine Reihe von Menüpunkten, denen wir jetzt von oben nach unten jeweils einen Unterabschnitt widmen werden.

## Benutzer

<!-- SCREENSHOT -->
![Abbildung: Die Benutzertabelle in der Admin-Anwendung]({{ img_url_desktop }}Administration/1_administration_benutzertabelle_light_1920x1020.png#only-light){.desktop-img}
![Abbildung: Die Benutzertabelle in der Admin-Anwendung]({{ img_url_desktop }}Administration/1_administration_benutzertabelle_dark_1920x1020.png#only-dark){.desktop-img}
![Abbildung: Die Benutzertabelle in der Admin-Anwendung]({{ img_url_mobile }}Administration/1_administration_benutzertabelle_light_1280x720.png#only-light){.mobile-img}
![Abbildung: Die Benutzertabelle in der Admin-Anwendung]({{ img_url_mobile }}Administration/1_administration_benutzertabelle_dark_1280x720.png#only-dark){.mobile-img}

<span id="user"></span>
In der Benutzeransicht sehen Sie in tabellarischer Form, welche Benutzer in Ihrer tine-Installation angelegt wurden. Außerdem werden hier ihre grundlegenden Eigenschaften angezeigt. Wenn an der Standardansicht nichts geändert worden ist, dann sehen Sie ganz links den Status -- ein Haken signalisiert, dass der Benutzer aktiv ist, ein X entsprechend einen nicht aktiven. Apropos Standardansicht: Sie können, wie Sie das ja auch schon von anderen Tabellenansichten in tine kennen, die anzuzeigenden Tabellenfelder auswählen. Ganz rechts im Tabellenkopf finden Sie dazu das Spaltensymbol. Wenn Sie es anklicken, werden Ihnen die für diese Ansicht gültigen Tabellenfelder als Checkbuttons zum Auswählen angezeigt. Prüfen Sie bitte, ob für die jetzt erforderliche (Standard-)Ansicht die folgenden Felder markiert sind: Status, Bildschirmname, Anmeldename, E-Mail, Zuletzt eingeloggt um, Letzter Login von, Passwort geändert und Verfällt

<!-- SCREENSHOT -->
![Abbildung: Eine Auswahl an Spalten, die in der Benutzertabelle angezeigt werden können]({{ img_url_desktop }}Administration/2_administration_spaltenauswahl_light_1920x1020.png#only-light){.desktop-img}
![Abbildung: Eine Auswahl an Spalten, die in der Benutzertabelle angezeigt werden können]({{ img_url_desktop }}Administration/2_administration_spaltenauswahl_dark_1920x1020.png#only-dark){.desktop-img}
![Abbildung: Eine Auswahl an Spalten, die in der Benutzertabelle angezeigt werden können]({{ img_url_mobile }}Administration/2_administration_spaltenauswahl_light_1280x720.png#only-light){.mobile-img}
![Abbildung: Eine Auswahl an Spalten, die in der Benutzertabelle angezeigt werden können]({{ img_url_mobile }}Administration/2_administration_spaltenauswahl_dark_1280x720.png#only-dark){.mobile-img}

Vielleicht fällt Ihnen jetzt ein Benutzer auf, der cronuser heißt. Dabei handelt es sich um einen Systemdienst, der immer vorhanden ist; beachten Sie ihn einfach nicht weiter.

<span id="user-grid"></span>
Machen wir weiter mit einzelnen Spalten der Tabellenansicht, und hier dem Bildschirmnamen. Das ist die Bezeichnung, unter welcher Sie als eingeloggter Benutzer am Bildschirm angezeigt werden. Sie finden ihn immer ganz oben rechts, direkt unter der Begrenzung des Browserfensters -- oder, wenn Sie tine im  Vollbildmodus betreiben -- unter dem Bildschirmrand, links neben dem Knopf zum Abmelden.

Der Anmeldename ist die Bezeichnung (eine zusammenhängende Zeichenkette, die aus Buchstaben und Zahlen bestehen kann), mit welcher Sie sich als Benutzer einloggen.

Sollte zu dem betreffenden Benutzer eine E-Mail-Adresse gespeichert sein, wird sie hier im nächsten Feld angezeigt.

Die weiteren Felder sind Statusmeldungen: Zuletzt eingeloggt um liefert Datum und Uhrzeit, Letzter Login von die IP-Adresse des Internet-Zugangs, von dem aus der Benutzer zuletzt Zugriff auf tine hatte.

!!! note "Anmerkung"
    Sollte Ihnen dort jetzt eine IP-Adresse angezeigt werden, die Ihnen unbekannt vorkommt, weil sie nicht derjenigen entspricht, mit der Ihr PC gerade ins Internet geht – es könnte auch Ihr mobiles Gerät gewesen sein, wenn es über ActiveSync an tine angebunden ist. Dazu weiter unten mehr.

Passwort geändert zeigt Datum und Uhrzeit der letzten Passwortänderung an und Verfällt das Datum und die Uhrzeit, an dem das Benutzerkonto automatisch deaktiviert wird - eine Funktion, die vor allem für temporäre Mitarbeiter gedacht ist. Sollte letzterer Eintrag leer sein, dann verfällt das Benutzerkonto nie – eine Auswahl, die Sie im Folgenden selbst treffen können.

<span id="user-actiontoolbar"></span>
Schauen wir uns nun die einzelnen Punkte des Bearbeitungsmenüs über der Tabelle an. Sie kennen das schon – die wichtigsten Aktionen in einem Programmteil von tine finden Sie immer als große Icons in der breiten blauen Leiste unter den Reitern für die Programmteile. Wir nennen es im ganzen Buch durchgängig "Bearbeitungsmenü". Sie erreichen übrigens alle diese Punkte auch über das Kontextmenü der rechten Maustaste, wenn Sie mit dem Zeiger auf dem entsprechenden Objekt stehen.

<span id="editdialog-user"></span>
Hier sind es Benutzer hinzufügen, Benutzer bearbeiten, Benutzer löschen (letztere beide ausgegraut, wenn keine aktive Tabellenzeile markiert ist), Drucke Seite, Benutzer aktivieren, Benutzer deaktivieren (wechselseitig ausgegraut – je nachdem ob die aktuelle Tabellenspalte auf einem aktiven oder inaktiven Benutzer steht), und Passwort zurücksetzen.

Lassen Sie uns probehalber einmal einen neuen Benutzer hinzufügen:

<!-- SCREENSHOT -->
![Abbildung: Die Bearbeitungsmaske zum Anlegen eines neuen Benutzers]({{ img_url_desktop }}Administration/3_administration_benutzer_neu_light_1920x1020.png#only-light){.desktop-img}
![Abbildung: Die Bearbeitungsmaske zum Anlegen eines neuen Benutzers]({{ img_url_desktop }}Administration/3_administration_benutzer_neu_dark_1920x1020.png#only-dark){.desktop-img}
![Abbildung: Die Bearbeitungsmaske zum Anlegen eines neuen Benutzers]({{ img_url_mobile }}Administration/3_administration_benutzer_neu_light_1280x720.png#only-light){.mobile-img}
![Abbildung: Die Bearbeitungsmaske zum Anlegen eines neuen Benutzers]({{ img_url_mobile }}Administration/3_administration_benutzer_neu_dark_1280x720.png#only-dark){.mobile-img}

<!--Passwort-->
<!--OpenID-->
<!--Single-Sign-On-ID-->
Die sich öffnende Eingabemaske enthält unter dem Reiter Benutzerkonto] den Vor- und Nachnamen (aus denen dann der o.g. Bildschirmname zusammengesetzt wird) sowie natürlich den Anmeldenamen und das Passwort. Dazu können Sie optional eine E-Mail-Adresse für den Benutzer angeben sowie seine OpenID. Im Normalfall brauchen Sie keine OpenID anzugeben. Diese entspricht dann dem Anmeldenamen.

Ein Wort der Erklärung zu dazu: Wenn Ihre tine-Installation eine sog. Stand-Alone-Installation, also nicht mit anderen Anwendungen Ihrer Systemumgebung verknüpft ist, dann entspricht die OpenID dem Benutzernamen und tine macht keinen Unterschied zwischen diesen beiden Bezeichnungen. Sollten Sie sich jedoch in einer sogenannten "Single-Sign-On"-Umgebung befinden, dann ist die OpenID möglicherweise eine andere und entspricht ihrer "Single-Sign-On"-ID.

!!! note "Anmerkung"
    OpenID ist ein Protokoll, mit dem Sie sich nur einmal an tine anmelden müssen und danach auf andere Webdienste zugreifen können, ohne sich erneut anmelden zu müssen. Der englische Fachbegriff dafür ist "Single-Sign-On". Ein weiterer Vorteil ist, dass das Passwort nicht an den anderen Webdienst übergeben, sondern immer nur innerhalb von tine überprüft wird.

Zu Beginn dieses Kapitels hatten wir schon besprochen, dass Benutzer zu Gruppen zusammengefasst werden können. Welchen Gruppen ein Benutzer insgesamt angehört, können Sie mit dem Reiter Gruppen festlegen, auf den wir weiter unten zu sprechen kommen. Hier in dieser Maske können Sie dem neu anzulegenden Benutzer innerhalb seiner Gruppen eine Stammgruppe zuweisen. Die Stammgruppe ist ein Begriff aus der LDAP-Welt: Wenn tine "stand-alone" - also ohne Kontakt zu Server-, Telefonanlagen- oder anderen Namensverzeichnissen betrieben wird, spielt die Stammgruppe keine Rolle. tine selbst benutzt diese Funktion nicht. Wenn es jedoch eine Verbindung zu einem LDAP-Namensverzeichnis in der Serverumgebung gibt, dann können Sie dem Benutzer von hier aus eine Stammgruppe innerhalb dieses Namensverzeichnisses zuweisen. Das Pulldown hinter diesem Menü bietet Ihnen dabei alle vorhandenen Benutzergruppen zur Auswahl an. Die Frage, welche Bedeutung eine Stammgruppe in der Welt der LDAP-Verzeichnisse hat, führt an dieser Stelle zu weit, nutzen Sie dazu bitte die entsprechende Fachliteratur oder wenden Sie sich an den bei Ihnen dafür verantwortlichen Systemadministrator.

Als Status des Benutzers ist standardmäßig aktiviert vorgegeben; Sie können hier jedoch per Pulldown zwischen verschiedenen Status wählen: Der Status aktiviert ist der Normalzustand, wie er auch in der tabellarischen Ansicht mit dem Haken dargestellt wird. In den Status deaktiviert kann man den Benutzer außer über dieses Pulldown auch über das Bearbeitungsmenü oben überführen. Damit kann sich der betreffende Benutzer nicht mehr bei tine anmelden und ein bereits angemeldeter Benutzer kann nicht mehr weiterarbeiten.

Die Anwahl von abgelaufen bewirkt, dass das Benutzerkonto sofort ungültig wird. Auch hier wird einem bereits angemeldeten Benutzer nicht erlaubt, weiterzuarbeiten.

Den Status gesperrt kann man nicht anwählen; er erscheint nur als Statusmeldung, wenn der betreffende Benutzer durch zu häufiges Anmelden mit einem falschen Passwort seinen Zugang gesperrt hat. Dann wird es vorkommen, dass dieser Benutzer bei Ihnen als Administrator vorstellig wird, weil er sein Konto wieder entsperrt haben will. Nun wissen Sie, wo Sie das tun können!

Neben dem eben beschriebenen Pulldown finden Sie den Eintrag Verfällt – der, wie oben bereits erwähnt, die Gültigkeit des Benutzerkontos betrifft. Hier können Sie, entweder direkt oder mittels des daneben aufrufbaren Kalenders, ein Datum eingeben. Wenn kein Ablaufdatum hinterlegt ist, läuft das Benutzerkonto nie ab.

!!! note "Anmerkung"
    Sollten Sie sich nicht sicher sein, was hier zu tun ist, erkundigen Sie sich im Unternehmen bei dem entsprechenden Verantwortlichen, ob Passwörter aus Sicherheitsgründen zeitlich befristet vergeben werden sollen. Wenn Sie selbst der Verantwortliche dafür sind und es noch nicht getan haben, dann sollten Sie mit Ihrer Geschäftsleitung ein Gespräch über eine firmeninterne Sicherheits-Policy führen. Dazu gehört nicht nur die Vergabe von Passwörtern, sondern u. a. auch Fragen wie:

    * Wem erlaube ich überhaupt Zugang zu tine (z.B. in Hinblick auf externe Benutzer...)?
    * Welche Rollen und Gruppen sollen eingerichtet werden?
    * Welche Administratoren und Vertreterpläne gibt es?

    Bei dieser Gelegenheit kann man auch andere Fragen, wie z.B. zur Verwendung von externen Geräten oder Datenträgern usw., als Teil der Policy festlegen. Das hat zwar zunächst mit unserem Thema Groupware nicht direkt etwas zu tun, aber spätestens, wenn Sie sich mit der Integration von Smartphones und Tablets der Mitarbeiter beschäftigen müssen, kommen Sie um die Festlegung von Sicherheitsregeln nicht herum. In vielen Unternehmen gibt es keine verbindlich festgelegte Sicherheits-Policy, was weitreichende Folgen hat: Wenn die Mitarbeiter nicht wissen, was genau als Fehler in diesem Bereich gilt, kann man ihnen auch keinen Vorwurf machen, wenn sie welche begehen. Fragen Sie hierzu einschlägige IT-Sicherheitsfirmen um Rat!


Sichtbarkeit und Gespeichert im Adressbuch erlauben es, per Pulldown auszuwählen, in welchem Adressbuch der Benutzer gespeichert wird und ob er für andere Benutzer dieses Adressbuches sichtbar sein soll. Benutzer sind genau so in Adressbüchern gespeichert wie andere Kontakte, unterscheiden sich von diesen in der Adressbuch-Tabellenansicht auf den ersten Blick jedoch durch das schwarze Kopf-Symbol ganz links.

Die Tatsache, ob ein Benutzer in einem Adressbuch gespeichert ist oder nicht, hat weitreichende Folgen: Nur in Adressbüchern registrierte Benutzer können in den verschiedensten tine-Anwendungen ausgewählt werden, die anderen sind nicht sichtbar!

Ein Anwendungsfall könnte bspw. auch sein, dass Sie zwei (oder mehr) verschiedene interne Adressbücher anlegen wollen, deren Benutzer sich gegenseitig nicht sehen sollen. Sie legen dann zwei Gruppen an, die jeweils nur Zugriff auf eines der beiden Adressbücher haben. Damit haben Sie zwei Benutzergruppen, die sich zwar alle an tine anmelden, sich aber gegenseitig nicht sehen können.

Die Checkbox Passwort muss geändert werden bewirkt, dass der Benutzer beim nächsten Login aufgefordert wird, ein neues Passwort zu setzen. Dies ist hilfreich, wenn z.B. ein neuer Mitarbeiter angestellt wurde und sein Benutzer angelegt wird. Hier kann man dann ein vorübergehendes Passwort vergeben, mit welchem der neue Mitarbeiter sich erstmalig anmeldet, bevor er sein eigenes Passwort wählt.

Unten schließlich sehen Sie, eingerahmt im Feld Informationen, die Login- und Passwortangaben ("Zeitpunkt, IP-Adresse, Passwort gesetzt") die wir weiter oben in der Tabellenansicht schon besprochen haben. In unserem Fall sind diese Felder natürlich leer, da der Benutzer (noch) nicht existiert.

Kommen wir zum nächsten Reiter -- Gruppen:

<!-- SCREENSHOT -->
![Abbildung: Das Zuweisen eines neuen Benutzers zu einer Gruppe]({{ img_url_desktop }}Administration/4_administration_benutzer_gruppe_light_1920x1020.png#only-light){.desktop-img}
![Abbildung: Das Zuweisen eines neuen Benutzers zu einer Gruppe]({{ img_url_desktop }}Administration/4_administration_benutzer_gruppe_dark_1920x1020.png#only-dark){.desktop-img}
![Abbildung: Das Zuweisen eines neuen Benutzers zu einer Gruppe]({{ img_url_mobile }}Administration/4_administration_benutzer_gruppe_light_1280x720.png#only-light){.mobile-img}
![Abbildung: Das Zuweisen eines neuen Benutzers zu einer Gruppe]({{ img_url_mobile }}Administration/4_administration_benutzer_gruppe_dark_1280x720.png#only-dark){.mobile-img}

Da die Bildung von Gruppen ein eigener Menüpunkt ist, wollen wir hier nur darauf eingehen, wie der Benutzer einer bereits definierten Gruppe zugewiesen werden kann: Sie sehen in der Maske das Pulldown Suche nach Gruppen... Wenn Sie es anklicken, dann werden Ihnen (ggf. nach kurzer Suche) die in Ihrer tine-Installation vorhandenen Benutzergruppen zur Auswahl angeboten. Je nachdem wie umfangreich Ihr System ist, können das wenige Einträge bis zu mehreren Seiten sein. Sie können den Benutzer hier einer oder der Reihe nach mehreren Gruppen zuweisen. Welche administratorischen Überlegungen dem zugrunde liegen, besprechen wir weiter unten, wenn wir die Gruppen anlegen.

Im nächsten Reiter Rollen werden die dem Benutzer zugewiesenen Rollen angezeigt. Auch das können eine oder mehrere sein. Auf die Aspekte von Rollen kommen wir ebenfalls zu sprechen, wenn wir sie weiter unten definieren. Hier nur soviel: Da Rechte in Rollen "positiv", also einschließlich definiert werden, kann es bei der Zugehörigkeit eines Benutzers zu mehreren Rollen nicht zu Widersprüchen kommen. Achten Sie nur ggf. darauf, mit der Mehrfachzuweisung einem Benutzer nicht fälschlicherweise Rechte zuzuweisen, die er nicht haben sollte.

Fileserver, IMAP, SMTP – Diese Reiter sind bei einer "stand-alone"-Installation von tine ausgegraut. Sollte Ihre tine-Installation jedoch mit einem File- und/oder E-Mail-Server verbunden sein, dann haben Sie über diese Reiter die Möglichkeit, bestimmte Eigenschaften dieser Server einzusehen oder zu verändern. Die Beschreibung dieser Möglichkeiten würde allerdings den Rahmen des vorliegenden Handbuches sprengen.

Mit dem Reiter Dateisystem können Sie dem Benutzer ein Kontingent an Datenvolumen geben, das ihm zur Verfügung steht. Außerdem bietet dieser Reiter die Übersicht, wie viel Datenvolumen der Benutzer belegt.

Schließen Sie die Eingabemaske mit Abbrechen, da wir jetzt keinen Benutzer anlegen wollen!

Wenn Sie nun die aktive Tabellenzeile auf einen beliebigen Benutzer stellen und Benutzer bearbeiten wählen (auch wie gewohnt über einen Doppelklick erreichbar), sehen Sie, dass die entsprechende Maske die gleichen Felder enthält wie die für Benutzer hinzufügen. Achten Sie hier auf die Felder unter Informationen – diese enthalten jetzt die Datums-, Uhrzeits- und IP-Adressinformationen, von denen wir oben sprachen.

<!-- SCREENSHOT -->
![Abbildung: Die Bearbeitungsmaske zum Editieren eines Benutzerkontos]({{ img_url_desktop }}Administration/5_administration_benutzer_editieren_light_1920x1020.png#only-light){.desktop-img}
![Abbildung: Die Bearbeitungsmaske zum Editieren eines Benutzerkontos]({{ img_url_desktop }}Administration/5_administration_benutzer_editieren_dark_1920x1020.png#only-dark){.desktop-img}
![Abbildung: Die Bearbeitungsmaske zum Editieren eines Benutzerkontos]({{ img_url_mobile }}Administration/5_administration_benutzer_editieren_light_1280x720.png#only-light){.mobile-img}
![Abbildung: Die Bearbeitungsmaske zum Editieren eines Benutzerkontos]({{ img_url_mobile }}Administration/5_administration_benutzer_editieren_dark_1280x720.png#only-dark){.mobile-img}

Benutzer löschen ist mit einer Sicherheitsabfrage versehen und ansonsten selbsterklärend.

Drucke Seite erzeugt einen tabellarischen Ausdruck, der genau den auf dem Bildschirm angezeigten Tabellenfeldern entspricht. Damit haben Sie die Möglichkeit, sich eine komplette Liste aller angelegten Benutzer ausdrucken zu lassen. Werfen Sie vorher ggf. noch einen Blick auf die ausgeblendeten Tabellenspalten (wie bekannt über das Tabellenkopfsymbol rechts außen)!

Benutzer aktivieren und Benutzer deaktivieren - die dahinter liegenden Funktionen hatten wir schon weiter oben bei Benutzer hinzufügen besprochen.

Passwort zurücksetzen vergibt für den ausgewählten Benutzer ein neues Passwort.

<!--Gruppen-->
## Gruppen
Bevor wir auf die Ansicht und Bedienelemente zu Gruppen zu sprechen kommen, müssen wir uns noch einmal kurz über den Sinn und Zweck von Benutzergruppen verständigen. Benutzergruppen dienen mehreren Zwecken:

1. Man kann mit Ihnen die Rollenzuweisung und damit die Administration von Benutzerrechten vereinfachen. Es ist sicher einfacher, über Rollen Benutzerrechte ganzen Benutzergruppen zuzuweisen, als das umständlich mit jedem Benutzer einzeln zu tun.

2. Genauso kann man Benutzergruppen natürlich auch verwenden, um damit bestimmte Massenbefehle für eine ganze Gruppe von Benutzern auszulösen. Beispielsweise ist es sehr einfach, einer ganzen Benutzergruppe mit einem einzigen Befehl eine E-Mail zuzuschicken, indem man diese Gruppe per Filter als Adressbuchansicht definiert (siehe [Adressverwaltung - Kontakte anzeigen, bearbeiten und filtern - Das E-Mail-Fenster und seine Funktionen](ba_Adressbuch.md/#das-e-mail-fenster-und-seine-funktionen)) und dann eine E-Mail an die Gruppe verfasst.

!!! info "Wichtig"
    Das erfordert natürlich, dass die Unternehmensstruktur in Benutzergruppen abgebildet wird. Sie müssen sich also an dieser Stelle, ehe Sie mit der Administration fortfahren, Gedanken über Ihre Unternehmensstruktur machen. Wahrscheinlich geht das, wie weiter oben bei den Überlegungen zur Sicherheit bereits erwähnt, nur in Zusammenarbeit mit Ihrer Unternehmensleitung. Verwenden Sie für diese strategischen Überlegungen lieber etwas mehr Zeit als zu wenig. Insbesondere, wenn es sich bei Ihrem Unternehmen um ein größeres handelt, ist eine gut durchdachte Benutzergruppenstruktur essenziell. Sie tun sich als Administrator einen großen Gefallen, wenn Sie gleich von Anfang an die richtigen Strukturen anlegen, bevor Sie sie später umständlich ändern müssen.

<span id="mainscreen-group-grid"></span>
Kommen wir nun wieder zurück zu unserem Programm und seinen Bedienelementen: Klicken Sie in der Admin-Oberfläche links auf den Menüpunkt Gruppen:

<!-- SCREENSHOT -->
![Abbildung: Verwaltung der Gruppen in tine.]({{ img_url_desktop }}Administration/6_administration_gruppen_light_1920x1020.png#only-light){.desktop-img}
![Abbildung: Verwaltung der Gruppen in tine.]({{ img_url_desktop }}Administration/6_administration_gruppen_dark_1920x1020.png#only-dark){.desktop-img}
![Abbildung: Verwaltung der Gruppen in tine.]({{ img_url_mobile }}Administration/6_administration_gruppen_light_1280x720.png#only-light){.mobile-img}
![Abbildung: Verwaltung der Gruppen in tine.]({{ img_url_mobile }}Administration/6_administration_gruppen_dark_1280x720.png#only-dark){.mobile-img}

Die Tabelle in der Hauptanzeige ist diesmal sehr einfach und enthält nur drei Spalten. In der Standard-Ansicht sind das Name, E-Mail und Beschreibung. Wie üblich können Sie die Tabelle durch das kleine Spaltensymbol am rechten Rand entsprechend verändern.

Wenn tine in einer unveränderten Erstinstallation vorliegt, dann dürften Sie jetzt zwei Gruppen sehen, nämlich Users und Administrators. Diese beiden Gruppen sind normalerweise in jeder tine-Installation enthalten. Natürlich können in Ihrer Installation auch noch viele weitere Gruppen enthalten sein. Damit Sie sich in der Tabellenansicht unter evtl. sehr vielen Gruppen die richtigen anzeigen lassen können, gibt es auch hier eine Suchfilter-Funktion: Eine im Suchfeld eingegebene Zeichenfolge filtert die Gruppen, allerdings nur über das Feld Name.

Schauen wir uns zunächst an, welche Eigenschaften eine Gruppe haben kann. Markieren Sie dazu in der Tabelle eine beliebige Gruppe und klicken Sie im Bearbeitungsmenü Gruppe bearbeiten an:

<!-- SCREENSHOT -->
![Abbildung: Die Maske zum Bearbeiten einer Benutzergruppe]({{ img_url_desktop }}Administration/7_administration_gruppen_editieren_light_1920x1020.png#only-light){.desktop-img}
![Abbildung: Die Maske zum Bearbeiten einer Benutzergruppe]({{ img_url_desktop }}Administration/7_administration_gruppen_editieren_dark_1920x1020.png#only-dark){.desktop-img}
![Abbildung: Die Maske zum Bearbeiten einer Benutzergruppe]({{ img_url_mobile }}Administration/7_administration_gruppen_editieren_light_1280x720.png#only-light){.mobile-img}
![Abbildung: Die Maske zum Bearbeiten einer Benutzergruppe]({{ img_url_mobile }}Administration/7_administration_gruppen_editieren_dark_1280x720.png#only-dark){.mobile-img}

Das folgende Bearbeitungsfenster zeigt Ihnen zunächst den Gruppennamen, dann eine Beschreibung der Gruppe und die Sichtbarkeit sowie in welchem Adressbuch die Gruppe gespeichert ist. Sie können also hier über das Gruppenmenü, genauso wie bei Einzelbenutzern, festlegen, ob bestimmte Benutzer im jeweiligen Adressbuch zu sehen sein sollen bzw. eine gesamte Gruppe von Benutzern in ein anderes Adressbuch verschieben. Beachten Sie das Feld Beschreibung und denken Sie dabei daran, dass auch andere Administratoren ihre Arbeit nachvollziehen können sollten. Hier ist es also wichtig, dass Sie die Eigenschaften dieser Benutzergruppe korrekt beschreiben.

Wie schon im [Adressverwaltung - Gruppen](ba_Adressbuch.md/#gruppen) beschrieben, bietet tine die Möglichkeit an Gruppen eine E-Mail-Adresse zu vergeben, dazu dient das Feld E-Mail. Um von dieser Funktion Gebrauch zu machen, müssen die entsprechenden Dienste des Mailservers, z.B. die sieve-Dienste, eingerichtet sein.

Das Pulldown Gruppenmitglieder dient der Neuaufnahme von Gruppenmitgliedern in die entsprechende Gruppe. Sie können dort entweder nach Teilstrings von Namen suchen oder über das Pulldown gezielt Gruppenmitglieder auswählen. Beachten Sie dabei, dass bei einer umfangreichen tine-Installation durchaus mehrere Seiten von Benutzern angeboten werden können.

Schließen Sie nun das Bearbeitungsfenster mit Abbrechen. Die anderen beiden Punkte des Bearbeitungsmenüs, Gruppe hinzufügen (Bedienung identisch zu Gruppe bearbeiten) und Gruppe löschen, sind nicht erklärungsbedürftig.

<!--Rollen-->
## Rollen
Kommen wir nun zu einem der wichtigsten Teile des Administrierens einer Groupware, dem Definieren von "Rollen". Wie wir weiter oben schon besprochen haben, dienen Rollen dem Zuweisen von "Rechten" zur Benutzung der verschiedenen Programmteile von tine zu bestimmten Benutzern oder Benutzergruppen - also welche Rolle ein Benutzer im Gesamtsystem spielen darf. Schauen wir uns das praktisch an:

Wählen Sie in der Admin-Oberfläche links per Mausklick den Menüpunkt Rollen aus. In der Tabelle gibt es nur zwei Spalten, Name und Beschreibung. Wie Sie das bereits von den Gruppen kennen, gibt es auch hier einen einfachen Suchfilter, der über das Feld Name wirkt.

Markieren Sie jetzt eine beliebige angezeigte Rolle und klicken Sie (entweder im Bearbeitungsmenü links über der Tabelle oder per Rechtsklick und Kontextmenü) Rolle bearbeiten:

<!-- SCREENSHOT -->
![Abbildung: Die Maske zum Zuweisen von Benutzern oder Gruppen zu einer Rolle (Reiter "Mitglieder")".]({{ img_url_desktop }}Administration/8_administration_rolle_editieren_light_1920x1020.png#only-light){.desktop-img}
![Abbildung: Die Maske zum Zuweisen von Benutzern oder Gruppen zu einer Rolle (Reiter "Mitglieder")".]({{ img_url_desktop }}Administration/8_administration_rolle_editieren_dark_1920x1020.png#only-dark){.desktop-img}
![Abbildung: Die Maske zum Zuweisen von Benutzern oder Gruppen zu einer Rolle (Reiter "Mitglieder")".]({{ img_url_mobile }}Administration/8_administration_rolle_editieren_light_1280x720.png#only-light){.mobile-img}
![Abbildung: Die Maske zum Zuweisen von Benutzern oder Gruppen zu einer Rolle (Reiter "Mitglieder")".]({{ img_url_mobile }}Administration/8_administration_rolle_editieren_dark_1280x720.png#only-dark){.mobile-img}

Sie sehen, dass es auch hier wieder ein Beschreibungsfeld gibt. Wie bereits weiter oben bei den Gruppen erwähnt, ist es wichtig, dass Sie bei einer neuen Rollendefinition die Beschreibung der definierten Rolle nachvollziehbar notieren, um anderen Administratoren nachfolgend die Arbeit zu erleichtern.

Das Bearbeitungsfenster für Rollen hat zwei Reiter: Mitglieder und Rechte. Unter Mitglieder sehen Sie ein Pulldown, in dem Suche nach Gruppen... steht. Wenn Sie es anklicken, sehen Sie, dass Ihnen Gruppen zur Auswahl angeboten werden. Sie können sowohl Benutzergruppen zu bestimmten Rollen zuweisen, als auch einzelne Benutzer. Der Umschalter ist als Pulldown hinter dem <img src="{{icon_url}}icon_group_full.svg" alt="drawing" width="16"/>-Symbol links neben Suche nach Gruppen... versteckt. Nach allem was wir bisher über Benutzergruppen und Rollen gelernt haben, ist es sinnvoller, Benutzergruppen zu verwenden. Eine Rolle ist das mächtigste Instrument, das dem Administrator in tine zur Verfügung steht. Einem einzelnen Benutzer eine Rolle zuzuweisen, erscheint daher wenig sinnvoll und nur in Ausnahmefällen angezeigt, z.B. wenn Sie einem zeitlich befristeten, externen Mitarbeiter ganz spezielle Rechte zuweisen wollen.

Übrigens können Sie sich die Rollen eines Benutzers auch in der Benutzerverwaltung anzeigen lassen, wie wir weiter oben unter [Benutzer](oa_Administration.md/#benutzer) gesehen haben.

Klicken Sie nun den Reiter Rechte an:

<!-- SCREENSHOT -->
![Abbildung: Die Maske zum Definieren der Rechte einer Rolle (Reiter "Rechte")".]({{ img_url_desktop }}Administration/9_administration_rolle_rechte_editieren_light_1920x1020.png#only-light){.desktop-img}
![Abbildung: Die Maske zum Definieren der Rechte einer Rolle (Reiter "Rechte")".]({{ img_url_desktop }}Administration/9_administration_rolle_rechte_editieren_dark_1920x1020.png#only-dark){.desktop-img}
![Abbildung: Die Maske zum Definieren der Rechte einer Rolle (Reiter "Rechte")".]({{ img_url_mobile }}Administration/9_administration_rolle_rechte_editieren_light_1280x720.png#only-light){.mobile-img}
![Abbildung: Die Maske zum Definieren der Rechte einer Rolle (Reiter "Rechte")".]({{ img_url_mobile }}Administration/9_administration_rolle_rechte_editieren_dark_1280x720.png#only-dark){.mobile-img}

Sie sehen im Fenster eine Baumstruktur, die den Anwendungen von tine entspricht. Hier können Sie also jeder Rolle genauestens zuweisen, welche Anwendungen sie benutzen darf.

!!! info "Wichtig"
    Die Rollendefinition ist positiv, d.h. Nutzerrechte addieren sich, wenn der Nutzer mehrere Rollen zugewiesen bekommen hat. Beachten Sie das bitte bei der Betrachtung dieser Anwendung – ein Benutzer kann über eine andere Rolle durchaus weitergehende Rechte haben, als Sie in einer Rolle sehen!

Gehen wir ins Detail - klappen Sie dazu mit Klick auf das kleine +-Zeichen vor der entsprechenden Anwendung die Checkbuttons auf:

1. ActiveSync - die Anwendung zum "Andocken" mobiler Geräte
    * ActiveSync Geräte verwalten - Darf der Benutzer gemeinsame und ActiveSync Geräte anlegen, bearbeiten oder löschen?
    * Admin - Darf der Benutzer Einstellungen verändern?
    * Ausführen - Sieht der Benutzer die Anwendung und darf er sie demzufolge auch bedienen?
    * Persönliche Tags - Darf der Benutzer persönliche Tags sehen und verwalten?
    * Reset ActiveSync devices - Darf der Benutzer ActiveSync-Geräte per Fernzugriff auf den Werkszustand zurücksetzen? Dieser Vorgang löscht alle Daten.
2. Admin – die Anwendung zur Administration der gesamten Groupware
    * Admin - Darf der Benutzer alle Einstellungen verändern? Diese Funktion sollte nur bei Administratoren aktiviert sein!
    * Anwendungen ansehen – Ist Anwendungen für den Benutzer im Admin-Menü aktiviert?
    * Anwendungen verwalten - Darf der Benutzer auf Einstellungen der Anwendungen im Admin-Menü zugreifen, d.h. diese bearbeiten, aktivieren und deaktivieren?
    * Ausführen - Sieht der Benutzer die Anwendung und darf er sie demzufolge auch bedienen?
    * Benutzerkonten ansehen - Ist Benutzer für den Benutzer im Admin-Menü aktiviert?
    * Benutzerkonten verwalten - Darf der Benutzer die Benutzerkonten bearbeiten, d.h. Benutzer und Gruppen anlegen und bearbeiten, Benutzer zu Gruppen zuordnen und Passwörter zurücksetzen?
    * Computer ansehen - Darf der Benutzer bei Verwendung der Samba-Integration die Computerkonten der Workstations sehen (siehe [Anmerkung](oa_Administration.md/#anmerkung))
    * Computer verwalten - Darf der Benutzer bei Verwendung der Samba-Integration die Computerkonten der Workstations anlegen, bearbeiten und löschen (siehe [Anmerkung](oa_Administration.md/#anmerkung))
    * Container ansehen - Ist Container für den Benutzer im Admin-Menü aktiviert?
    * Container verwalten - Darf der Benutzer Container anlegen, löschen und ändern sowie deren Berechtigungen setzen?
    * E-Mail-Konten ansehen - Darf der Benutzer gemeinsame und persönliche E-Mail-Konten ansehen?
    * E-Mail-Konten verwalten - Darf der Benutzer gemeinsame und persönliche E-Mail-Konten anlegen, bearbeiten oder löschen?
    * Gemeinsame Tags ansehen - Darf der Benutzer gemeinsame Tags ansehen?
    * Gemeinsame Tags verwalten – Darf der Benutzer gemeinsame Tags anlegen, bearbeiten oder löschen?
    * Quota Nutzung ansehen - Soll der Benutzer die persönliche Speicherbelegung sehen?
    * Rollen ansehen – Ist Rollen für den Benutzer im Admin-Menü aktiviert?
    * Rollen verwalten – Darf der Benutzer Rollen anlegen und bearbeiten, darf er Benutzerkonten Rollen zuordnen und Rollen Anwendungsrechte zuweisen?
    * Serverinformationen ansehen – Darf der Benutzer Serverinformationen ansehen?
    * Zugriffsprotokoll ansehen – Darf der Benutzer die Zugriffsprotokoll-Liste sehen?
    * Zugriffsprotokoll bearbeiten – Darf der Benutzer Zugriffsprotokoll-Einträge löschen?
    * Zusatzfelder ansehen – Darf der Benutzer die Liste der definierten Zusatzfelder sehen?
    * Zusatzfelder verwalten – Darf der Benutzer Zusatzfelder anlegen und editieren?
3. Adressbuch
    * Admin – Darf der Benutzer sämtliche Einstellungen in der  Adressbuch-Anwendung bearbeiten?
    * Ausführen – Sieht der Benutzer die Anwendung und darf er sie demzufolge auch bedienen?
    * E-Mail Optionen von Listen verwalten -
    * Gemeinsame Adressbuch-Favoriten verwalten - Darf der Benutzer gemeinsame Filter-Favoriten für Adressbücher anlegen?
    * Gemeinsame Adressbücher verwalten - Darf der Benutzer neue gemeinsame Adressbücher anlegen?
    * Persönliche Tags - Darf der Benutzer persönliche Tags sehen und verwalten?
    * Verwalte Gruppenfunktionen in den Stammdaten - Darf der Benutzer die Gruppenfunktionen verwalten?
    * Verwalte Listen in den Stammdaten - Darf der Benutzer die Gruppe verwalten?
4. Aufgaben
    * Admin – Darf der Benutzer sämtliche Einstellungen in der Aufgaben-Anwendung bearbeiten?
    * Ausführen – Sieht der Benutzer die Anwendung und darf er sie demzufolge auch bedienen?
    * Gemeinsame Aufgaben Favoriten verwalten - Darf der Benutzer Filter-Favoriten für Aufgabenlisten anlegen und ändern sowie diese mit anderen Anwendern teilen?
    * Gemeinsame Aufgabenlisten verwalten - Darf der Benutzer neue gemeinsame Aufgabenlisten anlegen?
    * Persönliche Tags - Darf der Benutzer persönliche Tags sehen und verwalten?
5. CRM
    * Admin – Darf der Benutzer sämtliche Einstellungen in der CRM-Anwendung bearbeiten?
    * Ausführen – Sieht der Benutzer die Anwendung und darf er sie demzufolge auch bedienen?
    * Gemeinsame Lead Favoriten verwalten - Darf der Benutzer Filter-Favoriten für Leadlisten anlegen und ändern sowie diese mit anderen Anwendern teilen?
    * Gemeinsame Leads Ordner bearbeiten - Darf der Benutzer neue gemeinsame Leads-Ordner anlegen?
    * Persönliche Tags - Darf der Benutzer persönliche Tags sehen und verwalten?
6. Dateimanager
    * Admin – Darf der Benutzer sämtliche Einstellungen im Dateimanager bearbeiten?
    * Anonyme Download Links verwalten - Darf der Benutzer anonyme Downloadlinks verwalten?
    * Ausführen – Sieht der Benutzer die Anwendung und darf er sie demzufolge auch bedienen?
    * Gemeinsame Ordner verwalten - Darf der Benutzer neue gemeinsame Ordner anlegen?
    * Persönliche Tags - Darf der Benutzer persönliche Tags sehen und verwalten?
7. E-Mail
    * Admin – Darf der Benutzer sämtliche Einstellungen im E-Mail-Client bearbeiten?
    * Ausführen – Sieht der Benutzer die Anwendung und darf er sie demzufolge auch bedienen?
    * E-Mailkonten hinzufügen - Darf der Benutzer neue E-Mail-Konten anlegen?
    * E-Mailkonten verwalten - Darf der Benutzer vorhandene E-Mail-Konten bearbeiten und löschen?
    * Persönliche Tags - Darf der Benutzer persönliche Tags sehen und verwalten?
8. HumanResources
    * Admin – Darf der Benutzer sämtliche Einstellungen in der HumanResources-Anwendung bearbeiten?
    * Ausführen – Sieht der Benutzer die Anwendung und darf er sie demzufolge auch bedienen?
    * Persönliche Tags - Darf der Benutzer persönliche Tags sehen und verwalten?
    * Private Daten des Mitarbeiters bearbeiten - Darf der Benutzer die internen und persönlichen Daten eines Mitarbeiters bearbeiten?
9. Inventarisierung
    * Admin – Darf der Benutzer sämtliche Einstellungen in der Inventarisierungs-Anwendung bearbeiten?
    * Ausführen - Sieht der Benutzer die Anwendung und darf er sie demzufolge auch bedienen?
    * Persönliche Tags - Darf der Benutzer persönliche Tags sehen und verwalten?
10. Kalender
    * Admin – Darf der Benutzer sämtliche Einstellungen in der Kalender-Anwendung bearbeiten?
    * Ausführen – Sieht der Benutzer die Anwendung und darf er sie demzufolge auch bedienen?
    * Gemeinsame Kalender Verwalten - Darf der Benutzer neue gemeinsame Kalender anlegen?
    * Gemeinsame Kalender-Favoriten verwalten - Darf der Benutzer Filter-Favoriten für gemeinsame Kalender anlegen und ändern sowie diese mit anderen Anwendern teilen?
    * Persönliche Tags - Darf der Benutzer persönliche Tags sehen und verwalten?
    * Ressourcen verwalten - Darf der Benutzer Ressourcen (Besprechungsräume, Beamer, Video...) verwalten?
11. Sales
    * Abteilungen verwalten – Darf der Benutzer Abteilungen hinzufügen, bearbeiten und löschen?
    * Admin – Darf der Benutzer sämtliche Einstellungen in der Sales-Anwendung bearbeiten?
    * Angebote verwalten – Darf der Benutzer Angebote hinzufügen, bearbeiten und löschen?
    * Auftragsbestätigungen verwalten – Darf der Benutzer Auftragsbestätigungen hinzufügen, bearbeiten und löschen?
    * Ausführen – Sieht der Benutzer die Anwendung und darf er sie demzufolge auch bedienen?
    * Eingangsrechnungen verwalten – Darf der Benutzer Eingangsrechnungen hinzufügen, bearbeiten und löschen?
    * Kostenstellen verwalten – Darf der Benutzer Kostenstellen hinzufügen, bearbeiten und löschen?
    * Kunden verwalten – Darf der Benutzer Kunden hinzufügen, bearbeiten und löschen?
    * Lieferanten verwalten – Darf der Benutzer Lieferanten hinzufügen, bearbeiten und löschen?
    * Nummer einer Auftragsbestätigung ändern –
    * Persönliche Tags – Darf der Benutzer persönliche Tags sehen und verwalten?
    * Produkte verwalten - Darf der Benutzer neue Produkte anlegen?
    * Rechnungsnummer manuell vergeben – Darf der Benutzer Rechnungsnummern manuell vergeben?
    * Rechnungen verwalten – Darf der Benutzer Rechnungen hinzufügen, bearbeiten und löschen?
    * Verträge verwalten – Darf der Benutzer Verträge hinzufügen, bearbeiten und löschen?
12. Stammdaten
    * Admin – Darf der Benutzer sämtliche Einstellungen in der Stammdaten bearbeiten?
    * Ausführen - Sieht der Benutzer die Anwendung und darf er sie demzufolge auch bedienen?
    * Persönliche Tags - Darf der Benutzer persönliche Tags sehen und verwalten?
13. Telefone
    * Admin – Darf der Benutzer sämtliche Einstellungen in der Telefon-Anwendung bearbeiten?
    * Ausführen – Sieht der Benutzer die Anwendung und darf er sie demzufolge auch bedienen?
    * Persönliche Tags – Darf der Benutzer persönliche Tags sehen und verwalten?
14. Tinebase
    * Admin – Darf der Benutzer sämtliche Einstellungen in Tinebase bearbeiten?
    * Ausführen – Sieht der Benutzer die Anwendung und darf er sie demzufolge auch bedienen?
    * Eigenen Client-Status ändern/setzen - Darf der Benutzer seinen eigenen Client-Status setzen und verändern (wird beim Abspeichern und Verlassen des Programms der letzte Status gespeichert und beim nächsten Start dort begonnen)?
    * Eigenes Profil bearbeiten - Darf der Benutzer sein eigenes Profil bearbeiten?
    * Fehler melden - Darf der Benutzer Fehlermeldungen an den Hersteller senden, wenn diese auftreten?
    * Persönliche Tags – Darf der Benutzer persönliche Tags sehen und verwalten?
    * Replikation – Darf der Benutzer auf Replikationsdaten aller Anwendungen zugreifen?
    * Version prüfen - Erhält der Benutzer Mitteilungen über Software-Updates?
    * Wartung – Darf der Benutzer die tine Installation im Wartungsmodus verwenden?
15. Zeiterfassung
    * Admin – Darf der Benutzer sämtliche Einstellungen in der Zeiterfassung bearbeiten?
    * Ausführen – Sieht der Benutzer die Anwendung und darf er sie demzufolge auch bedienen?
    * Gemeinsame Stundenzettel-Favoriten verwalten - Darf der Benutzer Filter-Favoriten für gemeinsame Stundenzettel anlegen und ändern sowie diese mit anderen Anwendern teilen?
    * Gemeinsame Zeitkonten-Favoriten verwalten - Darf der Benutzer Filter-Favoriten für gemeinsame Zeitkonten anlegen und ändern sowie diese mit anderen Anwendern teilen?
    * Persönliche Tags – Darf der Benutzer persönliche Tags sehen und verwalten?
    * Zeitkonten hinzufügen - Darf der Benutzer neue Zeitkonten anlegen?
    * Zeitkonten verwalten - Darf der Benutzer Zeitkonten und Stundenzettel anlegen, bearbeiten oder löschen?

<a id="anmerkung"></a>
!!! note "Anmerkung"
    <!--Samba-->
    <span id="sambamachine"></span>
    Bei einer Integration von tine in ein Active Directory[^2] können Sie an dieser Stelle die Konten für Computer verwalten, die sich am Active Directory anmelden dürfen. tine unterstützt die Integration in Samba[^3] oder Microsoft-basierte Active-Directory-Umgebungen.
    Bei einer "stand-alone"-Installation wird Ihnen unter diesem Menüpunkt nichts angezeigt.

[^2]:
    Microsoft "Active Directory" oder ab Windows Server 2008 "Active Directory Domain Services" heißt der Verzeichnisdienst auf Microsoft Servern. Er ermöglicht, ein Netzwerk entsprechend der realen Struktur oder der räumlichen Verteilung eines Unternehmens oder einer Institution zu gliedern und alle Komponenten, wie Benutzer, Gruppen, Server, Drucker u.ä. zu verwalten.

[^3]:
    Samba ist eine freie Software, die es u.a. ermöglicht, Linux-Server mit Microsoft-Windows-Clients laufen zu lassen, indem die Windows-Datei- und Druckdienste sowie -Domain-Controller emuliert werden.

## Anwendungen

<!-- SCREENSHOT -->
![Abbildung: Die einzelnen Anwendungen von tine]({{ img_url_desktop }}Administration/10_administration_anwendungen_light_1920x1020.png#only-light){.desktop-img}
![Abbildung: Die einzelnen Anwendungen von tine]({{ img_url_desktop }}Administration/10_administration_anwendungen_dark_1920x1020.png#only-dark){.desktop-img}
![Abbildung: Die einzelnen Anwendungen von tine]({{ img_url_mobile }}Administration/10_administration_anwendungen_light_1280x720.png#only-light){.mobile-img}
![Abbildung: Die einzelnen Anwendungen von tine]({{ img_url_mobile }}Administration/10_administration_anwendungen_dark_1280x720.png#only-dark){.mobile-img}

Das grundlegende Ziel des Moduls Anwendungen ist sehr schnell erklärt: Die Tabelle zeigt Ihnen alle in ihrer tine-Installation aktiven Anwendungen, die Sie als Administrator hier einzeln und für alle Benutzer aktivieren oder deaktivieren können. Damit können Sie an dieser Stelle nicht nur Rollen-bezogen, sondern für die gesamte tine-Installation das Aussehen und die Funktion des Systems anpassen. Ausgenommen von dieser Funktion sind das Adressbuch sowie die Anwendungen Admin und Tinebase (die grundlegende Datenbank), da es sich hier um die elementarsten Funktionen der Groupware handelt, die nicht ausgeschaltet werden können.

Bei folgenden Anwendungen gibt es darüber hinaus noch Einstellungen; Sie erkennen das daran, dass der Button Einstellungen aktiv wird, wenn Sie die betreffende Anwendung auswählen:

### Admin

Admin – hier kann das Standardadressbuch für die Eintragung neuer Kontakte eingestellt werden:

<!-- SCREENSHOT -->
![Abbildung: Einstellungen der Admin-Anwendung]({{ img_url_desktop }}Administration/11_administration_admin_einstellung_light_1920x1020.png#only-light){.desktop-img}
![Abbildung: Einstellungen der Admin-Anwendung]({{ img_url_desktop }}Administration/11_administration_admin_einstellung_dark_1920x1020.png#only-dark){.desktop-img}
![Abbildung: Einstellungen der Admin-Anwendung]({{ img_url_mobile }}Administration/11_administration_admin_einstellung_light_1280x720.png#only-light){.mobile-img}
![Abbildung: Einstellungen der Admin-Anwendung]({{ img_url_mobile }}Administration/11_administration_admin_einstellung_dark_1280x720.png#only-dark){.mobile-img}

### Kalender

<!--Resource-->
#### Ressourcen anlegen

<!-- SCREENSHOT -->
![Abbildung: Die Maske zum Anlegen einer neuen Ressource für die Kalender-Anwendung]({{ img_url_desktop }}Administration/13_administration_kalender_ressource_neu_light_1920x1020.png#only-light){.desktop-img}
![Abbildung: Die Maske zum Anlegen einer neuen Ressource für die Kalender-Anwendung]({{ img_url_desktop }}Administration/13_administration_kalender_ressource_neu_dark_1920x1020.png#only-dark){.desktop-img}
![Abbildung: Die Maske zum Anlegen einer neuen Ressource für die Kalender-Anwendung]({{ img_url_mobile }}Administration/13_administration_kalender_ressource_neu_light_1280x720.png#only-light){.mobile-img}
![Abbildung: Die Maske zum Anlegen einer neuen Ressource für die Kalender-Anwendung]({{ img_url_mobile }}Administration/13_administration_kalender_ressource_neu_dark_1280x720.png#only-dark){.mobile-img}

In diesem Dialog können Sie eine neue Ressource anlegen.
Geben Sie Ihr hierfür zunächst einen Namen an, zum Beispiel „Meeting-Raum 1“. Als nächstes benötigt die Ressource eine eindeutige E-Mail Adresse, dies ist für die Synchronisation mit anderen Kalendersystemen wichtig. An diese Adresse werden außerdem Benachrichtigungen an die Ressource versendet, wie Termineinladungen. Für den Typ wählen wir passend zu unserem Meeting-Raum den „Raum“ aus.
Als nächstes finden Sie den Punkt Kalender Hierarchie/Name. Über dieses Feld können Sie festlegen, unter welcher Ordnerstruktur und welchem Namen der Kalender der Ressource später zu finden ist und so Ihre Ressourcen besser kategorisieren. In unserem Beispiel befindet sich der Meeting-Raum in der Zentrale und ist dort eher unter dem Namen „Meeting-Raum Groß“ bekannt. Wir wählen also als Hierarchie:

Zentrale/Meeting-Raum Groß

Nun befindet sich der Kalender der Ressource in einem Unterordner „Zentrale“ mit dem Namen „Meeting-Raum Groß“. Später könnten wir in diesem Verzeichnis noch andere Räume, zum Beispiel „Meeting-Raum Klein“, hinterlegen.
Beachten Sie hierbei, dass diese Bezeichnung nur für den Kalender gilt! Die Ressource wird weiterhin nur über ihren Namen, also „Meeting-Raum 1“, gefunden und eingeladen. Unter diesem Namen erscheint sie auch in Exporten oder Ausdrucken.
Über den Standard Teilnehmerstatus lässt sich festlegen, welchen Status eine Ressource bei der Einladung hat.
Der Belegt Typ gibt hingegen an, wie sich eine Ressource in Konfliktfällen verhält. Bei der Standardauswahl „Belegt“ entspricht das Verhalten dem eines normalen Teilnehmers. Es wird eine Warnung angezeigt, die es jedoch erlaubt, den Termin-Konflikt zu ignorieren. Ist der Belegt-Typ hingegen „Nicht Verfügbar“, ist es nicht möglich, die Warnung zu ignorieren. Doppelte Buchungen der Ressource sind somit nicht möglich!
Nun können Sie noch eine Maximale Teilnehmerzahl und einen Ort festlegen. Der Ort ist ein Adressbuch-Kontakt. Wenn eine Ressource ein Raum ist und über einen Ort verfügt, wird dieser automatisch in das Feld „Ort“ im Termin übernommen.
Abschließend können Sie auswählen, ob Sie alle Benachrichtigungen zu dieser Ressource unterdrücken möchten.


#### Ressourcen Zugriffsrechte

<!-- SCREENSHOT -->
![Abbildung: Das Zuweisen von Zugriffsrechten zu einer angelegten Ressource]({{ img_url_desktop }}Administration/14_administration_kalender_ressource_rechte_light_1920x1020.png#only-light){.desktop-img}
![Abbildung: Das Zuweisen von Zugriffsrechten zu einer angelegten Ressource]({{ img_url_desktop }}Administration/14_administration_kalender_ressource_rechte_dark_1920x1020.png#only-dark){.desktop-img}
![Abbildung: Das Zuweisen von Zugriffsrechten zu einer angelegten Ressource]({{ img_url_mobile }}Administration/14_administration_kalender_ressource_rechte_light_1280x720.png#only-light){.mobile-img}
![Abbildung: Das Zuweisen von Zugriffsrechten zu einer angelegten Ressource]({{ img_url_mobile }}Administration/14_administration_kalender_ressource_rechte_dark_1280x720.png#only-dark){.mobile-img}

Im Reiter „Zugriffsrechte“ werden zuletzt die Rechte der Ressource festgelegt. In ihren Rechten unterscheiden sich Ressourcen von anderen Datensätzen: Ressourcen verfügen über Zugriffsrechte, die Sie selbst betreffen und solche die Ihren Kalender betreffen. Im Folgenden finden Sie eine Auflistung aller Ressourcen-Rechte:

Ressource einladen:

* darf die Ressource zum Termin einladen
* Zum Verschieben von bestehenden Terminen mit Ressource wird das Einladen-Recht nicht benötigt.

Ressource lesen:

* darf Ressource sehen
* darf die Ressource in der Ressourcenliste sehen
* darf die Details der Ressource im Bearbeiten Dialog der Ressource sehen
* darf vom Termin aus (rechte Maustaste) den Ressourcen Bearbeiten Dialog öffnen
* reicht nicht aus zum Lesen von Terminen, bei denen die Ressource eingeladen ist
* reicht nicht aus zum Lesen von Terminen, die originär im Ressourcen-Kalender gespeichert sind

Ressource bearbeiten:

* darf die Ressource selbst bearbeiten
* reicht nicht aus, um Zugriffsrechte zu vergeben

Ressource administrieren:

* darf die Ressource bearbeiten und administrieren

Termin hinzufügen:

* darf Termine originär im Ressourcenkalender anlegen/speichern

Termin lesen:

* darf Termine lesen, bei denen die Ressource eingeladen ist
* darf Termine lesen, die originär im Ressourcen-Kalender gespeichert sind
* reicht nicht zum Einladen der Ressource

Termine exportieren:

* darf den Ressourcen-Kalender exportieren
* braucht Termin lesen, damit der Ressourcen-Kalender angezeigt wird

Termin synchronisieren:

* darf den Ressourcen Kalender synchronisieren
* braucht Termin lesen, damit der Ressourcen-Kalender angezeigt wird

Termin Frei/Belegt:

* darf frei-/ belegt-Informationen von Terminen mit dieser Ressource sehen

Termin bearbeiten:

* darf Termine, die originär im Ressourcenkalender sind, bearbeiten
* darf Teilnehmerstatus des Ressourcenteilnehmers bearbeiten
* bekommt Benachrichtigungen bei Einladungen/Absagen/Status-Änderungen der Ressource

Diese Benachrichtigungen müssen in der Grundkonfiguration von tine aktiviert werden. Wenden Sie sich hierfür an Ihren Administrator.

Termin löschen:

* darf Termine die originär im Ressourcenkalender sind löschen
* reicht nicht zum Löschen der Ressource selbst, dies ist. Nur über Rollen-Recht "Ressourcen verwalten" möglich.

#### Rollen-Recht Ressourcen Verwalten

Um Ressourcen in den Stammdaten hinzufügen oder löschen zu können, wird das Rollen-Recht "Ressourcen verwalten" benötigt.

### CRM

<!--Lead,Typen-->
<!--Lead,Quellen-->
<!--Lead,Status-->
CRM – Sie finden sie in der Tabelle unter Bezeichner, Verfügbare Lead Status, Verfügbare Lead Quellen und Verfügbare Lead Typen. Gehen Sie in der Tabelle einen nach links unter Werte} finden Sie die jeweiligen Einstellungsmöglichkeiten. Details hierzu finden Sie unter [Kundenbeziehungsmanagement (CRM)](ia_CRM.md) und dort insbesondere im Abschnitt [Kundenbeziehungsmanagement (CRM) - Lead hinzufügen](ia_CRM.md/#lead-hinzufugen). Dort wird auch darauf hingewiesen, dass Sie, sollten Ihnen die standardmäßig angebotenen Klassifizierungskriterien für Ihre Leads nicht ausreichen oder Sie andere benötigen, dieselben hier ändern oder ergänzen können.

<!-- SCREENSHOT -->
![Abbildung: Die Standardeinstellungen der CRM-Anwendung: Lead-Status, -Quelle und -Typ]({{ img_url_desktop }}Administration/15_administration_crm_einstellungen_light_1920x1020.png#only-light){.desktop-img}
![Abbildung: Die Standardeinstellungen der CRM-Anwendung: Lead-Status, -Quelle und -Typ]({{ img_url_desktop }}Administration/15_administration_crm_einstellungen_dark_1920x1020.png#only-dark){.desktop-img}
![Abbildung: Die Standardeinstellungen der CRM-Anwendung: Lead-Status, -Quelle und -Typ]({{ img_url_mobile }}Administration/15_administration_crm_einstellungen_light_1280x720.png#only-light){.mobile-img}
![Abbildung: Die Standardeinstellungen der CRM-Anwendung: Lead-Status, -Quelle und -Typ]({{ img_url_mobile }}Administration/15_administration_crm_einstellungen_dark_1280x720.png#only-dark){.mobile-img}

Dazu dienen die drei Drop-Down Menüs. Bei den Einstellungsmöglichkeiten für Verfügbare Lead Status können Sie, neben dem Definieren eigener Lead-Status, auch die damit verbundenen Umsatz-Wahrscheinlichkeiten (probability) festlegen:

<!-- SCREENSHOT -->
![Abbildung: Die Maske zum Verwalten der möglichen Lead-Status der CRM-Anwendung]({{ img_url_desktop }}Administration/16_administration_crm_lead_status_light_1920x1020.png#only-light){.desktop-img}
![Abbildung: Die Maske zum Verwalten der möglichen Lead-Status der CRM-Anwendung]({{ img_url_desktop }}Administration/16_administration_crm_lead_status_dark_1920x1020.png#only-dark){.desktop-img}
![Abbildung: Die Maske zum Verwalten der möglichen Lead-Status der CRM-Anwendung]({{ img_url_mobile }}Administration/16_administration_crm_lead_status_light_1280x720.png#only-light){.mobile-img}
![Abbildung: Die Maske zum Verwalten der möglichen Lead-Status der CRM-Anwendung]({{ img_url_mobile }}Administration/16_administration_crm_lead_status_dark_1280x720.png#only-dark){.mobile-img}

Unter den anderen beiden Drop-Down-Menüs haben Sie die Möglichkeit, die verfügbaren Lead-Typen und -Quellen neu anzulegen oder vorhandene zu löschen, wenn Sie diese in Ihrem Vertriebskontext nicht benötigen.

### HumanResources

<!-- SCREENSHOT -->
![Abbildung: Die Einstellungen der HumanResources-Anwendung]({{ img_url_desktop }}Administration/17_administration_hr_einstellungen_light_1920x1020.png#only-light){.desktop-img}
![Abbildung: Die Einstellungen der HumanResources-Anwendung]({{ img_url_desktop }}Administration/17_administration_hr_einstellungen_dark_1920x1020.png#only-dark){.desktop-img}
![Abbildung: Die Einstellungen der HumanResources-Anwendung]({{ img_url_mobile }}Administration/17_administration_hr_einstellungen_light_1280x720.png#only-light){.mobile-img}
![Abbildung: Die Einstellungen der HumanResources-Anwendung]({{ img_url_mobile }}Administration/17_administration_hr_einstellungen_dark_1280x720.png#only-dark){.mobile-img}

<!--Feiertagskalender-->
HumanResources – hier finden Sie zwei Einstellungen: Standard-Feiertagskalender als Pulldown, sowie Urlaub verfällt als Eingabefeld.

* Über Standard Feiertagskalender können Sie, wie Sie das auch von der Kalender-Anwendung her kennen, einen beliebigen Kalender zuweisen. Dieser sollte die für Ihr Unternehmen gültigen Feiertage enthalten, denn sie werden bei der Eingabe und Abrechnung von Urlaubs- und Krankheitstagen vom System in die Berechnung einbezogen.

!!! info "Wichtig"
    Wenn hier kein Kalender angegeben wird, finden die entsprechenden Berechnungen ohne Berücksichtigung von Feiertagen statt!

* Urlaub verfällt – Die hier vorgefundene Angabe MM-DD interpretieren Sie als Monat und Tag, und zwar immer des folgenden Jahres. Vom System vorgegeben wird hier der 15.03. Das entspricht der in Deutschland allgemein üblichen Regelung, dass nicht genommener Urlaub im März des Folgejahres verfällt. Ändern Sie die Angabe, wenn in Ihrem Unternehmen eine andere Regelung gilt.

### Sales

<!-- SCREENSHOT -->
![Abbildung: Die Einstellungen der Sales-Anwendung]({{ img_url_desktop }}Administration/18_administration_sales_einstellungen_light_1920x1020.png#only-light){.desktop-img}
![Abbildung: Die Einstellungen der Sales-Anwendung]({{ img_url_desktop }}Administration/18_administration_sales_einstellungen_dark_1920x1020.png#only-dark){.desktop-img}
![Abbildung: Die Einstellungen der Sales-Anwendung]({{ img_url_mobile }}Administration/18_administration_sales_einstellungen_light_1280x720.png#only-light){.mobile-img}
![Abbildung: Die Einstellungen der Sales-Anwendung]({{ img_url_mobile }}Administration/18_administration_sales_einstellungen_dark_1280x720.png#only-dark){.mobile-img}

Sales – Hier erhalten Sie mehrere Pulldown-Schalter.

* Eigene Währung ist selbsterklärend. Hier stellt man die Währung ein, in der tine fakturieren soll.[^4]

[^4]:
In der zum Zeitpunkt der Handbucherstellung vorliegende Software-Version ist nur Euro verfügbar. Es sollen aber weitere Währungen hinzugefügt werden.

* Vertragsnummer-Erstellung: bietet folgende Einstellungen: automatically -- hier wird bei Neuanlage eines Vertrages die Vertragsnummer, beginnend bei "1", automatisch vergeben. Wenn Sie manually wählen, müssen Sie die Vertragsnummer per Hand eingeben.

* Vertragsnummer-Validierung: stellt ein, wie die Vertragsnummer auf Plausibilität geprüft wird und ist nur wirksam, wenn der o.g. Schalter Vertragsnummer-Erstellung auf manually steht. Number erlaubt nur Ziffern, Text sowohl Ziffern als auch Buchstaben.

* menu[Produktnummern Erstellung und Produktnummern-Validierung: funktionieren in gleicherweise wie Vertragsnummer..., mit dem Unterschied dass hier die Produktnummern vergeben werden.

* Produktnummern Präfix: gibt an, mit welchen Zeichen eine Produktnummer starten soll.

* Product Number Zero Fill: Gibt an wie viele Ziffern tine für eine Produktnummer verwendet werden.

### Aufgaben

In der Tabelle bei Wert versteckt sich, wie auch bei CRM, ein Drop-Down Menü. Hier kann eingestellt werden, welcher Wert als "Standard" gilt. Des Weiteren ist es möglich, personalisierte Werte zu definieren.

### Zeiterfassung

Genau wie bei Aufgaben kann man im Drop-Down Menü einstellen, welcher Wert als "Standard" gilt und personalisierte Werte definieren.

### Tinebase

<!-- SCREENSHOT -->
![Abbildung: Die Profil-Einstellungen der Tinebase-Anwendung.]({{ img_url_desktop }}Administration/19_administration_tinebase_einstellungen_light_1920x1020.png#only-light){.desktop-img}
![Abbildung: Die Profil-Einstellungen der Tinebase-Anwendung.]({{ img_url_desktop }}Administration/19_administration_tinebase_einstellungen_dark_1920x1020.png#only-dark){.desktop-img}
![Abbildung: Die Profil-Einstellungen der Tinebase-Anwendung.]({{ img_url_mobile }}Administration/19_administration_tinebase_einstellungen_light_1280x720.png#only-light){.mobile-img}
![Abbildung: Die Profil-Einstellungen der Tinebase-Anwendung.]({{ img_url_mobile }}Administration/19_administration_tinebase_einstellungen_dark_1280x720.png#only-dark){.mobile-img}

Tinebase: Unter dem einzigen Reiter, Profilinformation finden Sie eine Reihe von Feldern für Adressdaten und jeweils zwei zugehörige Checkbuttons: Lesen und Bearbeiten. Hier können Sie nach Bedarf, zusätzlich zu den Standardfeldern, die möglichen Dateneingaben in der Adressdatenbank erweitern. Die zusätzlichen Felder könnten insbesondere dann Bedeutung erlangen, wenn Sie Adressdaten aus anderen Programmen, wie z.B. Microsoft Outlook in tine einlesen. Outlook hat sehr viele mögliche Felder in der Adressdatenbank. Ob diese belegt sind, sehen Sie, wenn Sie sich die CSV-Tabelle anschauen, die Outlook beim Auslesen erzeugt. Schlagen Sie für nähere Erläuterungen hierzu bitte im [Adressverwaltung - Kontakte importieren](ba_Adressbuch.md/#kontakte-importieren) nach!

## Container
<!--Container-->

Kommen wir zu den weiteren Optionen auf der linken Seite.

"Container" werden die einzelnen Datenbanken genannt, die jeweils über die Anwendungen von tine erzeugt werden können. Zum Beispiel können das die schon allseits bekannten Adressbücher sein, aber auch Kalender, Aufgabenlisten, Zeitkonten usw.

Das heißt, jeder berechtigte Benutzer kann Container anlegen, nicht nur Sie als Administrator. Das hier beschriebene Modul dient somit eher dem zusammenfassenden Überblick über die angelegten Container und dem Zuweisen von Berechtigungen, wie wir weiter unten noch sehen werden. Dazu hat die Tabelle in der Standardansicht drei Spalten: Containername, Anwendung und Typ.

In der Standardansicht erhalten Sie jetzt nur die Container angezeigt, die vom Typ gemeinsam sind, d.h. die zur Verwendung durch mehrere Benutzer vorgesehen und freigegeben wurden. Wir wollen uns jetzt jedoch einmal auch Ihre eigenen Container ansehen. Entfernen Sie dazu den Filter (links über der Tabelle -).

Wenn Sie sich die nun angezeigten Container ansehen, dann werden Sie feststellen, dass es eine ganze Reihe davon gibt, die offenbar vom System automatisch erzeugt werden, wenn wir einen Benutzer anlegen. Es sind dies: xxx-Benutzers persönliche Projekte, ... Aufgaben, ... Leads, ... Kalender, und ... Adressbuch. Das erscheint auch sinnvoll, weil ein Benutzer von tine diese Datenbanken i.d.R. zur Verfügung haben sollte.

<!--Admin-Modus-->
!!! info "Wichtig"
    Wenn Sie an den Standardeinstellungen nichts ändern, dann werden an vielen Stellen im Programm (Adressbuch, Kalender usw.) neu angelegte Datensätze in diesen persönlichen Containern gespeichert. Es kann jedoch bspw. gewünscht und Firmendoktrin sein, dass ein Benutzer Adressen, Termine oder auch andere Daten vorzugsweise oder auch ausschließlich in einen anderen, z.B. einen gemeinsamen, Container speichern soll. Hierzu können Sie für sich selbst oder auch (sofern Sie die Berechtigung als Administrator haben) für alle tine-Benutzer, die Standardeinstellungen auf gemeinsame Container umschalten. Schlagen Sie dazu im [Benutzerspezifische Einstellungen](na_Benutzereinstellungen.md) nach und hier insbesondere im [Benutzerspezifische Einstellungen - Admin-Modus](na_Benutzereinstellungen.md/#admin-modus)!

<!--Container-->
### Container bearbeiten

<!-- SCREENSHOT -->
![Abbildung: Eine Übersicht der angelegten Container (gefiltert)]({{ img_url_desktop }}Administration/24_administration_container_light_1920x1020.png#only-light){.desktop-img}
![Abbildung: Eine Übersicht der angelegten Container (gefiltert)]({{ img_url_desktop }}Administration/24_administration_container_dark_1920x1020.png#only-dark){.desktop-img}
![Abbildung: Eine Übersicht der angelegten Container (gefiltert)]({{ img_url_mobile }}Administration/24_administration_container_light_1280x720.png#only-light){.mobile-img}
![Abbildung: Eine Übersicht der angelegten Container (gefiltert)]({{ img_url_mobile }}Administration/24_administration_container_dark_1280x720.png#only-dark){.mobile-img}

Schauen wir uns einmal an, welche allgemeingültigen Daten so ein Container enthält. Klicken Sie dazu einen in der Tabelle vorhandenen Container doppelt an, oder markieren einen vorhandenen und klicken dann im Bearbeitungsmenü Container bearbeiten:

<!-- SCREENSHOT -->
![Abbildung: Die Maske zur Container-Bearbeitung enthält nicht nur Angaben zum Datenmodell, sondern auch die Benutzer- oder Gruppenzuweisung sowie die zugehörige Vergabe von Berechtigungen.]({{ img_url_desktop }}Administration/25_administration_container_editieren_light_1920x1020.png#only-light){.desktop-img}
![Abbildung: Die Maske zur Container-Bearbeitung enthält nicht nur Angaben zum Datenmodell, sondern auch die Benutzer- oder Gruppenzuweisung sowie die zugehörige Vergabe von Berechtigungen.]({{ img_url_desktop }}Administration/25_administration_container_editieren_dark_1920x1020.png#only-dark){.desktop-img}
![Abbildung: Die Maske zur Container-Bearbeitung enthält nicht nur Angaben zum Datenmodell, sondern auch die Benutzer- oder Gruppenzuweisung sowie die zugehörige Vergabe von Berechtigungen.]({{ img_url_mobile }}Administration/25_administration_container_editieren_light_1280x720.png#only-light){.mobile-img}
![Abbildung: Die Maske zur Container-Bearbeitung enthält nicht nur Angaben zum Datenmodell, sondern auch die Benutzer- oder Gruppenzuweisung sowie die zugehörige Vergabe von Berechtigungen.]({{ img_url_mobile }}Administration/25_administration_container_editieren_dark_1280x720.png#only-dark){.mobile-img}

Sie sehen als oberste Eingabefelder den Namen des Containers, die Anwendung, in welcher der Container läuft, das Modell (eine weitere Unterteilung, denn manche Anwendungen, wie z.B. Sales, beinhalten mehrere Arten von Containern), den Typ (gemeinsam oder persönlich) und die Farbe des Containers.

Im da darunterliegenden Pulldown können Sie Benutzergruppen (oder, rechts außen über den kleinen Schalter mit dem Kopfsymbol, auch einzelne Benutzer oder "Jeden") dem Container als Benutzer zuweisen. Sodann sehen Sie die Benutzer in der da darunterliegenden Tabelle als Zeile und können für sie mit den Checkbuttons einzelne Berechtigungen definieren.

!!! note "Anmerkung"
    Das gleiche Fenster zum Zuweisen von Berechtigungen erreichen Sie als Administrator auch, wenn Sie sich in der jeweiligen Anwendung befinden. Gehen Sie dazu mit der Maus links in der Baumansicht auf den entsprechenden Container, rufen mit Rechtsklick das Kontextmenü auf und wählen Xxxx Berechtigungen verwalten.

Bis auf die persönlichen Kalender-Container (dort gibt es zwei zusätzliche Einstellungen, Frei/Belegt und Privat, die im [Kalender - Termin hinzufügen/bearbeiten](da_Kalender.md/#termin-hinzufugenbearbeiten) näher erläutert werden) sind die angebotenen Berechtigungen immer dieselben:

* Lesen: Der Benutzer darf nur lesend auf die Daten zugreifen.

* Hinzufügen: Der Benutzer darf Datensätze hinzufügen.

* Bearbeiten: Der Benutzer darf vorhandene Datensätze ändern.

* Löschen: Der Benutzer darf vorhandene Datensätze löschen.

* Export: Der Benutzer darf vorhandene Datensätze auslesen.

* Sync: Der Benutzer darf die Datensätze per ActiveSync, WebDAV, CalDAV oder CardDAV auf sein Smartphone oder Computer synchronisieren. Mit dieser Einstellung können Sie (mit Einschränkungen) verhindern, dass Daten aus tine einfach über diese Funktion heraus kopiert werden können.

* Admin: Der Benutzer hat uneingeschränkte Rechte (= ist Administrator).

Mit dem Button Entferne Eintrag unten links können Sie einen Benutzer bzw. eine Benutzergruppe auch wieder aus dem Kreis der berechtigten Nutzer eines Containers ausschließen.

### Container hinzufügen

Sie können hier jedoch auch Container neu anlegen und müssen dazu nicht in die jeweilige Anwendung wechseln. Klicken Sie dazu in der Bedienzeile auf Container hinzufügen:

<!-- SCREENSHOT -->
![Abbildung: Die Bearbeitungsmaske zum Anlegen eines neuen Containers]({{ img_url_desktop }}Administration/26_administration_container_neu_light_1920x1020.png#only-light){.desktop-img}
![Abbildung: Die Bearbeitungsmaske zum Anlegen eines neuen Containers]({{ img_url_desktop }}Administration/26_administration_container_neu_dark_1920x1020.png#only-dark){.desktop-img}
![Abbildung: Die Bearbeitungsmaske zum Anlegen eines neuen Containers]({{ img_url_mobile }}Administration/26_administration_container_neu_light_1280x720.png#only-light){.mobile-img}
![Abbildung: Die Bearbeitungsmaske zum Anlegen eines neuen Containers]({{ img_url_mobile }}Administration/26_administration_container_neu_dark_1280x720.png#only-dark){.mobile-img}

Sie erhalten die gleiche Maske wie oben, nur dass Sie hier die Felder in der ersten Zeile natürlich erst füllen müssen. Für Anwendung und Typ erhalten Sie per Pulldown Vorgaben. Die Benutzer bzw. Benutzergruppen wählen Sie aus und die Berechtigungen weisen Sie zu wie oben beschrieben.

### Container löschen

Container löschen löscht den markierten Container, nach Rückfrage.

### Drucke Seite

Drucke Seite erzeugt über den computerinternen Druckdialog eine Tabelle der in der aktiven Ansicht sichtbaren Container im DIN-A4-Hochformat.

## Gemeinsame Tags

<!--Tags,gemeinsame-->
Tags sind die kleinen Markierungen an beliebigen Datensätzen, die es uns erlauben, bestimmte Daten aus einer großen Menge herauszufiltern. Dabei unterscheidet tine in Gemeinsame Tags und Persönliche Tags. Persönliche Tags können Sie als Benutzer in beliebigen Teilen des Programms setzen, ohne über Administratorrechte zu verfügen. Wir haben darüber bereits im [Adressverwaltung](ba_Adressbuch.md) gesprochen. Hier geht es jedoch um die gemeinsamen Tags, die für ganze Benutzergruppen eingerichtet werden und die nur ein Administrator verwalten darf.

Klicken Sie in der Admin-Oberfläche links auf Gemeinsame Tags:

<!-- SCREENSHOT -->
![Abbildung: Eine Liste der gemeinsamen Tags, die jeder Nutzer verwenden kann]({{ img_url_desktop }}Administration/21_administration_gemeinsame_tags_light_1920x1020.png#only-light){.desktop-img}
![Abbildung: Eine Liste der gemeinsamen Tags, die jeder Nutzer verwenden kann]({{ img_url_desktop }}Administration/21_administration_gemeinsame_tags_dark_1920x1020.png#only-dark){.desktop-img}
![Abbildung: Eine Liste der gemeinsamen Tags, die jeder Nutzer verwenden kann]({{ img_url_mobile }}Administration/21_administration_gemeinsame_tags_light_1280x720.png#only-light){.mobile-img}
![Abbildung: Eine Liste der gemeinsamen Tags, die jeder Nutzer verwenden kann]({{ img_url_mobile }}Administration/21_administration_gemeinsame_tags_dark_1280x720.png#only-dark){.mobile-img}

In der Tabelle sehen Sie drei Spalten: Farbe, Name und Beschreibung. Sollten Sie in der Tabelle keine tatsächlich angelegten Tags sehen, dann klicken Sie in der Bedienzeile auf Tag hinzufügen:

<!-- SCREENSHOT -->
![Abbildung: Das Zuweisen von Benutzern oder Gruppen zu einem gemeinsamen Tag und die Definition der entsprechenden Benutzerrechte]({{ img_url_desktop }}Administration/22_administration_gemeinsame_tags_rechte_light_1920x1020.png#only-light){.desktop-img}
![Abbildung: Das Zuweisen von Benutzern oder Gruppen zu einem gemeinsamen Tag und die Definition der entsprechenden Benutzerrechte]({{ img_url_desktop }}Administration/22_administration_gemeinsame_tags_rechte_dark_1920x1020.png#only-dark){.desktop-img}
![Abbildung: Das Zuweisen von Benutzern oder Gruppen zu einem gemeinsamen Tag und die Definition der entsprechenden Benutzerrechte]({{ img_url_mobile }}Administration/22_administration_gemeinsame_tags_rechte_light_1280x720.png#only-light){.mobile-img}
![Abbildung: Das Zuweisen von Benutzern oder Gruppen zu einem gemeinsamen Tag und die Definition der entsprechenden Benutzerrechte]({{ img_url_mobile }}Administration/22_administration_gemeinsame_tags_rechte_dark_1280x720.png#only-dark){.mobile-img}

Oben links können Sie einen beliebigen Tag-Namen vergeben. Bevor sie das daneben liegende Feld Beschreibung ausfüllen, denken Sie bitte einmal kurz daran, dass es sich hierbei um einen gemeinsamen Tag handelt. Es gilt das bereits an mehreren Stellen Gesagte: Eine Beschreibung muss so nachvollziehbar sein, dass alle anderen Administratoren und in diesem Falle auch alle anderen Benutzer des Tags wissen, wofür er eingerichtet wurde. Die Farbe, die Sie einem Tag zuweisen und mit der dann Ihre Datenbank-Objekte in den entsprechenden Tabellenansichten mit einen kleinen farbigen Punkt markiert sind, können Sie über das Pulldown-Menü ganz rechts außen auswählen. Es werden Ihnen die üblichen HTML-Systemfarben angezeigt.

Kommen wir nun zu den beiden Reitern Benutzerrechte und Kontexte: Klicken Sie den Reiter Benutzerrechte jetzt an, wenn er nicht schon standardmäßig geöffnet ist! Benutzerrechte werden üblicherweise über Benutzergruppen verwaltet. Demzufolge steht der Standard hier auch auf der Gruppenauswahl und Sie können mit dem unter den Reitern befindlichen Pulldown Suche nach Gruppen... die betreffenden Benutzergruppen auswählen, für die der eingerichtete Tag verfügbar sein soll. Sollten Sie die Suche hier auf einzelne Personen oder "Alle" umstellen wollen, so ist das möglich, indem Sie das hinter dem Symbol mit den schwarzen Köpfen befindliche Pulldown betätigen und entweder Benutzersuche oder Jeder hinzufügen auswählen.

Haben Sie den entsprechenden Nutzerkreis für den Tag ausgewählt, können Sie, wie Sie an den links befindlichen zwei Checkbuttons sehen, die Benutzerrechte Zeigen und Verwenden zuweisen.

Zeigen bedeutet, dass der Benutzer, der dieses Recht hat, das Tag in der Auswahl angezeigt bekommt. Er kann es somit als Filter verwenden und sich entsprechende Datensätze gefiltert anzeigen lassen.

Verwenden bedeutet hingegen, dass der entsprechende Benutzer auch das Recht hat, selbst einen beliebigen Datensatz mit dem Tag zu kennzeichnen oder auch den Tag wieder zu entfernen.

Klicken Sie nun den Reiter Kontexte an:

<!-- SCREENSHOT -->
![Abbildung: Die Einstellung, in welchen Anwendungen ein gemeinsamer Tag anwendbar und gültig sein soll]({{ img_url_desktop }}Administration/23_administration_gemeinsame_tags_kontexte_light_1920x1020.png#only-light){.desktop-img}
![Abbildung: Die Einstellung, in welchen Anwendungen ein gemeinsamer Tag anwendbar und gültig sein soll]({{ img_url_desktop }}Administration/23_administration_gemeinsame_tags_kontexte_dark_1920x1020.png#only-dark){.desktop-img}
![Abbildung: Die Einstellung, in welchen Anwendungen ein gemeinsamer Tag anwendbar und gültig sein soll]({{ img_url_mobile }}Administration/23_administration_gemeinsame_tags_kontexte_light_1280x720.png#only-light){.mobile-img}
![Abbildung: Die Einstellung, in welchen Anwendungen ein gemeinsamer Tag anwendbar und gültig sein soll]({{ img_url_mobile }}Administration/23_administration_gemeinsame_tags_kontexte_dark_1280x720.png#only-dark){.mobile-img}

Sie sehen eine Reihe von Checkbuttons, die den einzelnen Programmbausteinen von tine entsprechen. Hier können Sie also die Gültigkeit des gerade definierten Tags auf einzelne Programmteile, bspw. das Adressbuch, beschränken. Standardmäßig sollten alle Anwendungen ausgewählt sein.

<!--Zusatzfelder-->
## Zusatzfelder

<!-- SCREENSHOT -->
![Abbildung: Eine Übersicht aller im System angelegten Zusatzfelder]({{ img_url_desktop }}Administration/27_administration_zusatzfelder_light_1920x1020.png#only-light){.desktop-img}
![Abbildung: Eine Übersicht aller im System angelegten Zusatzfelder]({{ img_url_desktop }}Administration/27_administration_zusatzfelder_dark_1920x1020.png#only-dark){.desktop-img}
![Abbildung: Eine Übersicht aller im System angelegten Zusatzfelder]({{ img_url_mobile }}Administration/27_administration_zusatzfelder_light_1280x720.png#only-light){.mobile-img}
![Abbildung: Eine Übersicht aller im System angelegten Zusatzfelder]({{ img_url_mobile }}Administration/27_administration_zusatzfelder_dark_1280x720.png#only-dark){.mobile-img}

tine erlaubt Ihnen, bei Bedarf in den Datenbanken Zusatzfelder zu definieren, die in der Standardvariante nicht angeboten werden.

Klicken Sie Zusatzfelder -> Zusatzfeld hinzufügen an:

<!-- SCREENSHOT -->
![Abbildung: Das Bearbeitungsfenster zum Anlegen eines neuen Zusatzfeldes]({{ img_url_desktop }}Administration/28_administration_zusatzfelder_neu_light_1920x1020.png#only-light){.desktop-img}
![Abbildung: Das Bearbeitungsfenster zum Anlegen eines neuen Zusatzfeldes]({{ img_url_desktop }}Administration/28_administration_zusatzfelder_neu_dark_1920x1020.png#only-dark){.desktop-img}
![Abbildung: Das Bearbeitungsfenster zum Anlegen eines neuen Zusatzfeldes]({{ img_url_mobile }}Administration/28_administration_zusatzfelder_neu_light_1280x720.png#only-light){.mobile-img}
![Abbildung: Das Bearbeitungsfenster zum Anlegen eines neuen Zusatzfeldes]({{ img_url_mobile }}Administration/28_administration_zusatzfelder_neu_dark_1280x720.png#only-dark){.mobile-img}

In der Eingabemaske erhalten Sie eine Reihe von Merkmalen zur Definition eines Zusatzfeldes angeboten, wovon die meisten (Anwendung, Modell, Typ, Name und Bezeichner) Pflichteingaben sind.

Anwendung ist als Pulldown ausgeführt; es bietet Ihnen die relevanten tine-Anwendungen an, in deren Datencontainern Sie Zusatzfelder definieren können.

Modell ist ebenfalls ein Pulldown und kann nur in Abhängigkeit von Anwendung eingestellt werden. In den meisten Anwendungen haben Sie hier nur eine Auswahl, da es nur eine Art von Containern pro Anwendung gibt. Die Ausnahme ist HumanResources, hier haben Sie die Wahl zwischen Mitarbeiter und Personalkonto, denn in dieser Anwendung gibt es diese beiden Arten von Containern.

Unter Zusatzfeld-Definition müssen Sie nun dem Zusatzfeld einige unerlässliche Eigenschaften zuweisen.

Typ bietet eine Reihe von Angaben, die den Typ der einzugebenden Daten definieren:

* Text bedeutet, dass die Eingabe von beliebigen alphanumerischen Zeichen erlaubt ist.
* Textarea erzeugt ein mehrzeiliges Textfeld
* Zahl erlaubt nur die Eingabe von numerischen Werten.
* Datum erlaubt nur Datumsangaben, entweder händisch in der Form tt.mm.yyyy oder über den einblendbaren Kalender.
* Datum mit Uhrzeit erzeugt zwei Felder; ein Datumsfeld wie vorher beschrieben und ein Uhrzeitfeld, das als Pulldown mit Zeitangaben von 00:00 bis 23:45 im Viertelstundenabstand erscheint.
* Uhrzeit erzeugt ein Uhrzeitfeld, wie vorher beschrieben.
* Logischer Term erzeugt einen Checkbutton, dessen Wert beim Anklicken "Ja" oder "Wahr" bedeutet.
* Suchfeld erzeugt ein Suchfeld

* Schlüsselfeld: Hier können Sie eine Liste von (numerischen) Schlüsseln und zugehörigen Werten hinterlegen, die der Benutzer bei Eingabe des Feldes als Pulldown abrufen kann. In der Oberfläche sieht der Benutzer immer nur die Werte. In der Datenbank werden aber nur die Schlüssel gespeichert. Damit kann man auch noch später die Werte ändern, ohne dass die Daten in der DB geändert werden müssen. Der Checkbutton Standard bestimmt, welcher Wert als Standardwert übernommen wird, ohne dass der Benutzer eine Auswahl trifft. Bleibt er leer, bleibt in diesem Falle auch das Feld leer.
* Datensatz: Hier können Sie eine Anwendung und einen Datentyp auswählen. Zum Beispiel Adressbuch und Kontakt. In der Oberfläche kann der Benutzer dann in diesem Zusatzfeld über ein Pulldown einen Kontakt aus dem Adressbuch auswählen. Sie kennen diese Art der Bestimmung von Datenfeldinhalten schon aus tine, wo sie in vielen Anwendungen für die Bildung von Verknüpfungen genutzt wird.
* menu[Datensatz Erlaubt das Verknüpfen von einem Datensatz
* menu[Datensatz Liste Erlaubt das Verknüpfen von mehreren Datensätzen

Unter Name vergeben Sie für Ihr Zusatzfeld einen eindeutigen Namen, wie er im Datenmodell sinnvoll und schlüssig ist. Dieser Name taucht in keinem Benutzermenü auf, er ist nur die eindeutige Bezeichnung des Feldes.

Im Gegensatz dazu ist Bezeichner die später auf der Eingabemaske für den Nutzer erscheinende Bezeichnung des Datenfeldes.

Eine Länge können Sie als numerische Anzahl definieren, wenn Sie als Typ Text vergeben haben. Damit wird die Eingabe auf eine bestimmte Anzahl Zeichen begrenzt.

Der Checkbutton Erforderlich macht das Feld zur Pflichteingabe.

Im unteren Teil des Eigenschaftsfensters haben Sie unter Zusätzliche Eigenschaften des Zusatzfeldes noch die folgenden drei Eingabemöglichkeiten:

* Karteireiter:
* Gruppe: Bei der Eingabe einer Gruppenbezeichnung wird das Zusatzfeld (zweckmäßigerweise gemeinsam mit anderen zur gleichen Gruppe gehörig definierten Feldern) in einem extra Rahmen mit der Gruppenbezeichnung als Überschrift dargestellt.
* Sortierung: Innerhalb dieser Gruppe werden die Felder gemäß der Angabe hier sortiert, d.h. in dieses Feld können Sie nur numerische Zeichen eingeben.

<!--Kontingents-Nutzung-->
## Kontingents-Nutzung

Die Funktion Kontingents-Nutzung bietet eine Übersicht darüber, wieviel Datenspeicher die unterschiedlichen Anwendungen von tine nutzen.

<!--ActiveSync,Geräte-->
## ActiveSync Geräte
Die unter diesem Menüpunkt angezeigte Tabelle listet Ihnen die für Ihre tine-Instanz angemeldeten Geräte auf, die sich über ActiveSync mit der tine-Datenbank abgleichen. Dabei werden alle Geräte angezeigt, die jemals angemeldet waren, sofern sie nicht hier gelöscht wurden.

<!-- SCREENSHOT -->
![Abbildung: Übersicht aller ActiveSync Geräte]({{ img_url_desktop }}Administration/30_administration_activesync_devices_light_1920x1020.png#only-light){.desktop-img}
![Abbildung: Übersicht aller ActiveSync Geräte]({{ img_url_desktop }}Administration/30_administration_activesync_devices_dark_1920x1020.png#only-dark){.desktop-img}
![Abbildung: Übersicht aller ActiveSync Geräte]({{ img_url_mobile }}Administration/30_administration_activesync_devices_light_1280x720.png#only-light){.mobile-img}
![Abbildung: Übersicht aller ActiveSync Geräte]({{ img_url_mobile }}Administration/30_administration_activesync_devices_dark_1280x720.png#only-dark){.mobile-img}

Das Bearbeitungsmenü für diesen Programmteil enthält nur drei Funktionen: Sync Gerät bearbeiten, Sync Gerät löschen und Drucke Seite. Eine Funktion zum Erstellen fehlt hier, denn die Einträge kommen ausschließlich durch das Anmelden eines ActiveSync-Gerätes zustande.

!!! tip "Tipp"
    Sollte sich ein Gerät nicht mehr mit tine über ActiveSync synchronisieren, dann entfernen Sie das Gerät mit Klick auf Sync Gerät löschen aus dieser Liste. Das Gerät meldet sich daraufhin neu bei tine an, und die Synchronisation funktioniert wieder.

Wenn Sie eine Tabellenzeile markieren und Sync Gerät bearbeiten anklicken, erhalten Sie die folgende Maske:

<!-- SCREENSHOT -->
![Abbildung: Angaben eines ActiveSync Gerätes bearbeiten.]({{ img_url_desktop }}Administration/31_administration_activesync_devices_editieren_light_1920x1020.png#only-light){.desktop-img}
![Abbildung: Angaben eines ActiveSync Gerätes bearbeiten.]({{ img_url_desktop }}Administration/31_administration_activesync_devices_editieren_dark_1920x1020.png#only-dark){.desktop-img}
![Abbildung: Angaben eines ActiveSync Gerätes bearbeiten.]({{ img_url_mobile }}Administration/31_administration_activesync_devices_editieren_light_1280x720.png#only-light){.mobile-img}
![Abbildung: Angaben eines ActiveSync Gerätes bearbeiten.]({{ img_url_mobile }}Administration/31_administration_activesync_devices_editieren_dark_1280x720.png#only-dark){.mobile-img}

Auf der Maske finden Sie einige Angaben, die nicht in jedem Falle vollständig sein müssen, da das Übermitteln der zugehörigen Daten von den entsprechenden Geräten abhängt und nicht durchgängig standardisiert ist.

* Die Geräte ID ist eine Zahlen-Buchstaben-Kombination und wird vom Gerät übermittelt.

* Der Gerätetyp ist die Hersteller- und Typenbezeichnung des Gerätes.

* Der Eigentümer ist der Eigentümer des Gerätes. Diese Angabe ist immer vorhanden, da sie aus der Kontobezeichnung des tine-Benutzers übernommen wird.

* Über die Richtlinie können verschiedene Einstellungen für das Gerät festgelegt werden. Zum Beispiel, ob das Gerät über ein Passwort geschützt werden muss und wie lang das Passwort sein muss. Diese Richtlinien lassen sich nur direkt in der Datenbank definieren und einem Gerät zuweisen.

* Die AS Version enthält die Versionsnummer der ActiveSync-Software, wie sie das externe Gerät benutzt.

* Der Useragent enthält eine Zeichenkette, mit deren Hilfe man die auf dem Gerät verwendete Softwareversion identifizieren kann.

* Das Modell steht für die Modellbezeichnung des Herstellers. Diese Information wird nicht von allen Geräten übermittelt.

* Die IMEI ist eine eindeutige ID des Gerätes und ist auf der ganzen Welt eindeutig. Die IMEI wird nicht von allen Geräten übermittelt.

* Der Friendly Name sollte den Namen angeben, den der Benutzer dem Gerät zugewiesen hat. Der Friendly Name wird nicht von allen Geräten übermittelt.

* Das OS ist das Betriebssystem des ActiveSync-Gerätes, ggf. mit Versionsnummer.

* Die OS Sprache ist Sprache des auf dem Gerät verwendeten Betriebssystems.

* Die Telefonnummer ist die Mobilfunknummer des Gerätes. Auch hier gilt wieder, dass die Telefonnummer nicht von allen Geräten übertragen wird.


## Zugriffslog

<!-- SCREENSHOT -->
![Abbildung: Das Zugriffslog]({{ img_url_desktop }}Administration/20_administration_zugriffslog_light_1920x1020.png#only-light){.desktop-img}
![Abbildung: Das Zugriffslog]({{ img_url_desktop }}Administration/20_administration_zugriffslog_dark_1920x1020.png#only-dark){.desktop-img}
![Abbildung: Das Zugriffslog]({{ img_url_mobile }}Administration/20_administration_zugriffslog_light_1280x720.png#only-light){.mobile-img}
![Abbildung: Das Zugriffslog]({{ img_url_mobile }}Administration/20_administration_zugriffslog_dark_1280x720.png#only-dark){.mobile-img}

<!--JSON-->
Das "Zugriffslog" bietet dem Administrator die Möglichkeit, sich alle Logins der berechtigten Benutzer anzeigen zu lassen. Das können ganz schnell sehr viele werden - hier spielt also die Filterfunktion eine wichtige Rolle. Wenn Sie das Modul Zugriffslog] nach Ihrem eigenen Login das erste Mal aufrufen, ist deshalb standardmäßig ein Filter gesetzt, der Ihnen nur die Logins der letzten Woche und diejenigen vom Client-Typ TineJson anzeigt. Was hat es mit dem Letzteren auf sich? "JSON" bedeutet "JavaScript Object Notation" und ist ein definiertes Datenaustauschformat in der IT. In unserem konkreten Fall steht der Client-Typ TineJson für ein Login über den Webbrowser des PCs, also auch das Login, mit dem Sie heute Ihre tine-Sitzung begonnen haben.

Die Tabelle der Logins zeigt Ihnen in der Standardansicht den Benutzernamen, den vollständigen Namen, die IP-Adresse, von der aus sich der entsprechende Benutzer eingeloggt hat, die Einlogzeit und die Auslogzeit sowie das Ergebnis, d.h. ob der Anmeldevorgang erfolgreich war oder nicht. Dazu wird ganz rechts noch der o.g. Client-Typ angezeigt, in unserem Fall nur die bewussten TineJson-Logins.

<!--ActiveSync-->
Was passiert, wenn wir die gesetzten Filter entfernen? Gehen Sie probehalber bitte einmal auf die beiden --Symbole oben links über dem Tabellenkopf und klicken sie weg – die Verfahrensweise ist Ihnen wahrscheinlich schon vom [Adressverwaltung](ba_Adressbuch.md) bekannt. Gehen Sie jetzt auf Suche starten (d.h. aktivieren Sie eine neue Ansicht, diesmal ohne Filter): Möglicherweise sehen Sie jetzt wesentlich mehr Logins. Nicht nur weil Sie die Zeitbeschränkung (letzte Woche) aufgehoben haben, sondern auch, weil Logins von anderen Geräten angezeigt werden, sofern es welche gab. Schauen Sie ganz rechts auf die Spalte Client-Typ! Sehen Sie Einträge mit der Bezeichnung TineActiveSync? Damit hat es folgende Bewandnis: Wie Sie vielleicht schon wissen, ist ein großer Vorteil von tine gegenüber anderen im Markt befindlichen Systemen dieser Art die einfache Einbindung von Handheld-Devices, wie Smartphones und Tablets. Dabei ist es gleichgültig, welches Betriebssystem  das Gerät hat (Android, iOS, Windows Mobile/Phone, Blackberry), sofern es nur eine sog. ActiveSync-Funktion aufweist. Diese Funktion ist ursprünglich ein Microsoft-Patent (in manchen Staaten, wie z.B. USA, ist es das heute noch, hier gilt es besondere Bedingungen beim Einsatz von tine zu beachten!) und als solches quasi zum Industriestandard geworden. Die ActiveSync-Schnittstelle ist sehr robust und garantiert eine fehlerfreie wechselseitige Datensynchronisation von E-Mail, Kontakten und Kalenderdaten zwischen tine und dem Handheld-Device, ohne dass auf diesem irgendwelche Apps installiert werden müssten. Ganz nebenbei: Wenn Sie auf diesem Wege Ihre sensiblen Kontakt- und Termindaten auf einem tine-System abspeichern, anstatt bei Google (wenn Sie ein Android-Smartphone nutzen) oder Apple (beim iPhone), entgehen Sie auch der Ausspähung und potenziellen Weiterleitung Ihrer Daten durch die genannten Unternehmen an die Geheimdienste und andere Behörden.

## Server-Informationen

<!-- SCREENSHOT -->
![Abbildung: Informationen zum Server]({{ img_url_desktop }}Administration/29_administration_serverinfo_light_1920x1020.png#only-light){.desktop-img}
![Abbildung: Informationen zum Server]({{ img_url_desktop }}Administration/29_administration_serverinfo_dark_1920x1020.png#only-dark){.desktop-img}
![Abbildung: Informationen zum Server]({{ img_url_mobile }}Administration/29_administration_serverinfo_light_1280x720.png#only-light){.mobile-img}
![Abbildung: Informationen zum Server]({{ img_url_mobile }}Administration/29_administration_serverinfo_dark_1280x720.png#only-dark){.mobile-img}

Der Menüpunkt Server-Informationen dient der Anzeige von Informationen für den Systemadministrator; darauf gehen wir im Rahmen dieses Buches nicht näher ein.

## E-Mailkonten

Hier können Sie unter anderen die in tine genutzten E-Mailkonten bearbeiten. Dies wurde im E-Mail Kapitel unter dem [E-Mail - Das Bearbeitungsmenü](ea_EMail.md/#das-bearbeitungsmenu) schon besprochen. Für weitere Information navigieren Sie dort hin.

Neben der Bearbeitung von den E-Mail-Konten, haben Sie auch die Möglichkeit, verschiedene neue E-Mail-Kontotypen hinzuzufügen. Hierzu klicken Sie Konto hinzufügen.
Kontoname ist selbsterklärend. Darunter befindet sich die Auswahl der Kontotypen.
Zur Auswahl steht Ihnen Gemeinsames Systemkonto, Persönliches Extra-Systemkonto, Persönliches externes E-Mail-Konto und Persönliches Standard-Systemkonto welche im Folgenden erläutert werden.

* Gemeinsames Systemkonto:
  Hiermit wird ein und dasselbe E-Mail-Konto denjenigen zur Verfügung gestellt, die die entsprechenden Rechte besitzen.

Das Lesen-Recht erlaubt das Lesen der E-Mails, wobei jegliche Bearbeitung untersagt wird. Hierzu zählt auch das Antworten auf eine E-Mail.

Mit Bearbeiten verteilen Sie das Recht, E-Mails zu bearbeiten. Hierzu zählt z.B. das Markieren oder Löschen von Mails, nicht aber das Schreiben. Hierfür gibt es das E-Mails schreiben-Recht.

* Persönliches Extra-Systemkonto:

Mit dieser Option können Sie ein weiteres E-Mail-Konto erstellen, welches über Benutzer einem individuellen User zugeteilt werden kann. Das neue E-Mail-Konto wird dem ausgewählten Benutzer nach einem "Refresch" von tine zur Verfügung stehen.

* Persönliches externes E-Mail-Konto:

Mit Persönliches externes E-Mail-Konto kann man externe E-Mail-Konten zu tine hinzufügen[^5]. Auch hier definiert Benutzer, welchem Benutzer dieses Konto zugeordnet werden soll.

[^5]:
    Beispielsweise Gmail, Hotmail, GMX etc.

* Persönliches Standard-Systemkonto:

Diese Option kann nicht ausgewählt werden. Sie ist hier nur der Vollständigkeit halber aufgelistet. Ein persönliches Standard-Systemkonto wird beim Erstellen eines Users eingerichtet. Klicken Sie z.B. auf ein E-Mail-Konto eines bestehenden Benutzers werden Sie sehen, dass hier diese Option ausgewählt ist.
