# Vorwort
<!-- icon: icon_resource_coffee.svg -->

## Was ist Groupware?

Eine Groupware dient, wie der Name schon sagt, der Zusammenarbeit einer oder auch mehrerer Arbeitsgruppen. Grundlage dieser Zusammenarbeit ist die Kommunikation über verschiedene technische Hilfsmittel, wie Telefon, E-Mail oder Instant Messaging. Indem eine Groupware die Teilnehmer wie auch deren Kommunikationskanäle verwaltet und organisiert, bildet sie die Grundlage dieser Zusammenarbeit. Das unterscheidet sie z.B. von einem System des "Enterprise Resource Planning" (ERP), das zwar ebenfalls Adressdaten speichert, allerdings hier als Unternehmensressource gleichberechtigt neben anderen Daten, wie z.B. Rohstoffen, Produktionsmitteln, Liegenschaften, Inventar, Preisen usw.

<!--ERP,Abgrenzung zur Groupware-->
Historisch gesehen sind ERP-Systeme deutlich älter, weil sie ihren Nutzen bereits als Einzelplatz- oder lokale Netzwerk-Installationen entfalten konnten. Groupware hingegen taucht erst mit dem Internet auf, insbesondere mit der zunehmenden Kommunikation via E-Mail. Ein Unternehmen ließ sich nunmehr auch über mehrere Standorte hinweg als (technische) Einheit verwalten. E-Mail gehört daher zu den Kernfunktionen von Groupware. Heute, durch die Entwicklung der sogenannten "Unified Communication", steuert eine moderne Groupware auch andere Kanäle, wie etwa das Telefon: Man wählt in der Software einen Kontakt samt Telefonnummer und muss nicht mehr die Telefontastatur bedienen. Unified Communication ist aber noch viel mehr: Sie umfasst zum Beispiel das gemeinsame Bearbeiten von Dokumenten, das Teilen von Kalendern, die Organisation von realen wie auch Online-Meetings usw.

<!--CRM,Abgrenzung zur Groupware-->
Neben dem Verhältnis zu ERP stellt sich häufig die Frage, inwieweit sich Groupware vom "Customer Relationship Management" (CRM) unterscheidet, denn auch CRM ist eine reine Kommunikationssoftware. Wie wir später sehen werden, beantwortet {{ branding.title }} die Frage ganz einfach, indem es beides bietet. Die Frage ist dennoch interessant, weil sie genauer klären hilft, was Groupware eigentlich ist.

Gehen wir von einem Unternehmen ohne Kommunikationssoftware aus, das zwischen der Einführung eines CRM und einer Groupware zu entscheiden hat. Als einfache Faustregel wollen wir hierbei annehmen:

* Je geringer die Fertigungs- oder Prozesstiefe im eigenen Haus, je _massenhafter_ das Produkt und je kürzer demzufolge ein Verkaufszyklus, desto mehr spricht für ein (reines) CRM.

CRM-Systeme zielen auf die Kommunikation mit dem Kunden ab; sie erlauben es, eine große Menge potentieller Kunden auf verschiedenen (Hierarchie-)Ebenen und über verschiedene Kanäle anzusprechen, zu gruppieren und diese Kontaktaufnahmen möglichst spezifisch zu dokumentieren – etwa nach Medium, Quelle oder Mitarbeiter. Dabei bilden sie die Mitarbeiter des eigenen Unternehmens eher in einer flachen Hierarchie ab. Die Struktur im eigenen Vertrieb steht nicht im Vordergrund, es geht vor allem um die Vielfalt der Verkaufsmöglichkeiten und deren Übersicht. Typische CRM-Anwender sind folglich z.B. Handelsbetriebe oder Vertriebsunternehmen im Finanzdienstleistungs-, Medien- oder Versorgungsbereich (Energie, Telekommunikation).

Daraus ergibt sich im Umkehrschluss als weitere Regel:

* Je größer die Fertigungstiefe und Wertschöpfung im eigenen Unternehmen, je spezifischer das Produkt, je größer die Anzahl der am Produktions- und Verkaufsprozess beteiligten Mitarbeiter, je länger der Kontakt zu einem Kunden zwischen Verkauf und Lieferung, je größer auch die Beteiligung von Lieferanten und/oder Mitarbeitern des Kundenunternehmens am Verkaufs- und Lieferprozess, desto eher empfiehlt sich eine Groupware.

Die (innerbetriebliche) Kommunikation hat hier eine deutlich größere Bedeutung als die reine Abwicklung eines Verkaufsprozesses. Wichtig ist auch die Abbildung von Hierarchien, Kompetenzen und Zuständigkeiten im eigenen Unternehmen. Typisch sind diese Anforderungen etwa bei Projektdienstleistern, wie z.B. Anlagenbauern oder Herstellern von kundenspezifischer Software, aber auch bei Banken oder Telekommunikations-Carriern.

Aber ist nicht auch bei einem eher vertriebsaffinen Unternehmen die innerbetriebliche Kommunikation wichtig, und muss nicht auch ein eher produktionsaffines Unternehmen viele Kontakte zu potentiellen Kunden haben, um kontinuierlich verkaufen zu können?

Das ist natürlich richtig. Die beiden genannten Bereiche beschreiben Extreme, die so in der Wirtschaft nur selten in Reinform zu finden sind. Es empfiehlt sich aber, vor der Auswahl einer Software die durchaus unterschiedlichen Grundanforderungen bzw. Ansätze für das eigene Unternehmen zu prüfen. In vielen Unternehmen sind beide Systeme im Einsatz, weil man eben keinen Kompromiss gefunden hat oder finden wollte.  Allerdings besteht hier die Gefahr, dass Adressdatenbestände evtl. doppelt gepflegt werden (müssten) – und dann gibt es ja auch noch die ERP-Systeme... Wenn dann noch z.B. die zentrale Telefonanlage mit eigenem Telefonbuch hinzukommt, die Mitarbeiter eigene Kontakte in ihren Mobiltelefonen haben und alle diese Systeme voneinander nichts "wissen", sind Datenchaos und Mitarbeiterfrust vorprogrammiert.

<!--LDAP-->
Die Herausforderung besteht also in der zuverlässigen Verknüpfung oder Vereinheitlichung der Adressbücher verschiedener Systeme (ERP, CRM, Groupware, Telefonanlage,...) über einen Standard wie z.B. LDAP.[^1] {{ branding.title }} beherrscht LDAP und empfiehlt sich daher auch für die Integration bereits vorhandener, bislang getrennter Adressdatenbestände.

[^1]:
_Lightweight Directory Access Protocol_, ein Standardprotokoll, das Betriebs-systemübergreifend in IP-Netzwerken die Abfragen von Nutzern an Verzeichnisdiensten gewährleistet und damit die zentralisierte Verwaltung von Adressbüchern erlaubt, auf die mehrere Systeme zugreifen.

Um allerdings generell die Anzahl von Schnittstellen gering und beherrschbar zu halten, wählen Sie eine Software, die die benötigten Prozesse weitgehend vereint und zudem über offene Schnittstellen verfügt. {{ branding.title }} ist eine solche Software: Open Source und im Kern eine Groupware mit einer sehr leistungsfähigen Adressverwaltung, mit Kalender und E-Mail-Client. Dazu finden Sie viele Erweiterungen aus dem ERP-Bereich, wie Personal-, Projekt- und Arbeitszeitverwaltung, Dokumentenmanagement und eben auch ein basisfunktionales CRM sowie eine Sales-Anwendung.

## {{ branding.title }} und Metaways

In diesem Abschnitt wollen wir uns kurz mit den folgenden Fragen beschäftigen:

* Was ist das Projekt "{{ branding.title }}"?

* Welche Rolle spielt die Firma Metaways?

* Wie ist das Verhältnis zwischen freier und kommerzieller Version?

* Welche Version behandelt dieses Buch (nicht)? Und warum?

{{ branding.title }} gibt es in zwei verschiedenen Editionen:

<!--Community Edition {{ branding.title }}-->
Zum einen gibt es die _Community Edition_ (CE), in deren Rahmen die Open Source Software {{ branding.title }} entwickelt wird. Sie richtet sich vornehmlich an Entwickler oder Anwender, die sich in den Entwicklungsprozess (z.B. durch Fehlersuche) einbringen wollen. Neue Funktionen werden zuerst in der CE entwickelt und dort gemeinsam getestet. Unterstützung gibt es für CE nur in Form von freiwilligem Community Support.

<!--Business Edition {{ branding.title }}-->
Die _Business Edition_ (BE) ist eine durch die Hamburger Firma Metaways Infosystems GmbH gepflegte Version von {{ branding.title }} für den Einsatz in Unternehmen. Sie erfüllt alle Anforderungen an den Einsatz von Open Source im geschäftlichen Umfeld. Für die BE gibt es professionellen Support mit festen Reaktionszeiten. Eine Version der BE wird stets zwei Jahre lang kontinuierlich mit Updates versorgt. Im Rahmen der Softwarepflege erhält die BE im Gegensatz zur CE keine neuen Leistungsmerkmale, sondern nur Fehlerkorrekturen. Die {{ branding.title }} BE ist als reine Software oder Cloud Service jeweils mit Service und Support verfügbar. Nähere Informationen hierzu erhalten Sie bei Metaways oder einem Channel-Partner.

Dieses Buch bezieht sich auf die {{ branding.title }} BE in der Version 2019.11. Es soll einen Anwender im geschäftlichen Umfeld in die Lage versetzen, seine tägliche Arbeit mit {{ branding.title }} zu organisieren und zu optimieren.

Da sich {{ branding.title }} CE im Verhältnis zur BE rascher weiterentwickelt, wird der CE-Benutzer an einigen Stellen Abweichungen zum tatsächlichen Verhalten der Software feststellen. Diese sind jedoch in der Regel so gering, dass sich das vorliegende Handbuch ebenso gut für die Arbeit mit {{ branding.title }} CE empfiehlt.

## Über dieses Buch

### An wen wendet sich das Buch?

Dieses Handbuch wendet sich an Anwender von {{ branding.title }}. Gewisse Vorkenntnisse aus der Arbeit mit anderen Kommunikationsprogrammen wie Microsoft Outlook oder Thunderbird vereinfachen den Einstieg, sind aber nicht zwingend erforderlich.

Dieses Buch ist das offizielle Benutzerhandbuch für {{ branding.title }}. In diesem Buch finden Sie alle Informationen, die ein Benutzer von {{ branding.title }} bei seiner täglichen Arbeit mit {{ branding.title }} benötigt.

### Wo findet man weitere/andere Informationen (der Firma Metaways bzw. der Community)?

Weitere Informationen, besonders die Installation von {{ branding.title }} betreffend, erhalten Sie auf {{ branding.weburl }}. Dort finden Sie detaillierte Installationsanleitungen für alle unterstützten Betriebssysteme.

Als Softwareentwickler haben Sie über {{ branding.repo_url }} {{ branding.title }} den Zugang zu einer regen Community, in der Sie sich mit anderen Entwicklern und Anwendern austauschen können.

### Wie Sie dieses Buch lesen sollten

Wenn Sie noch nie mit {{ branding.title }} zu tun hatten, sollten Sie zuerst das [Addressverwaltung](ba_Adressbuch.md) und danach das [Allgemeine Hinweise zur Bedienung](ca_StandardBedienhinweise.md) durcharbeiten. Die weitere Reihenfolge ist nicht vorgegeben und hängt sicher auch davon ab, mit welchen Anwendungen von {{ branding.title }} Sie überhaupt arbeiten möchten – sofern Sie ein "normaler" Benutzer sind und keine administrativen Aufgaben, wie z.B. das Anlegen von anderen Benutzern oder Ressourcen, ausführen wollen. Beachten Sie auch, dass das Handbuch bei der Beschreibung der Funktionen i.d.R. von weitreichenden, d.h. Administratorrechten, ausgeht. Sollten Sie bestimmte beschriebene Funktionen nicht ausführen können, so liegt das wahrscheinlich an eingeschränkten Rechten - darauf weisen wir dann im Text hin.

Als Mitarbeiter mit Administratoraufgaben für {{ branding.title }} sollten Sie nach dem [Addressverwaltung](ba_Adressbuch.md) und dem [Allgemeine Hinweise zur Bedienung](ca_StandardBedienhinweise.md) zunächst zum  [Benutzereinstellungen](na_Benutzereinstellungen.md) springen und danach [Administration](oa_Administration.md) lesen.
