
## Dienstplanungsrollen
Eine Dienstplanungsrolle beschreibt die Rolle (z.B. _Leitung_, _Zelebrant_, _Organist_, ...) die ein Teilnehmender in
Bezug auf den Dienst in einem Termin einnimmt. Teilnehmende eines Termins können im Rahmen des Termins eine oder
mehrere Dienstplanungsrollen einnehmen.

Dienstplanungsrollen werden systemweit in `Stammdaten`->`Dienstplanung`->`Dienstplanungsrollen` erstellt und konfiguriert.

Neben `Name`, `Farbe`, `Kürzel`, `Beschreibung` kann hier das genaue Verhalten der Dienstplanungsrolle verwaltet werden:

* `Standardmäßige minimale Teilnehmerrollen Anzahl:` Wird einem Termin eine Terminart zugewiesen die Teilnehmende dieser
  Dienstplanungsrolle erfordert ist dies die standardmäßige minimale Anzahl von Teilnehmenden die diese Rolle haben müssen.
* `Aktion bei Unterschreitung` Was soll passieren wenn bei einem Termin die benötigte Anzahl von Teilnehmenden mit dieser
Rolle nicht erreicht wird. Mögliche Aktionen sind:
  * `Keine:` Es wird keine Aktion ausgelöst, die Abweichung ist möglich.
  * `Verboten:` Es ist nicht möglich den Termin stattfinden zu lassen. Der Termin wird automatisch beim erreichen der
    `Vorlaufzeit` abgesagt wenn die benötigte Anzahl von Teilnehmenden nicht zugesagt hat.
  * `Termin vorläufig:` Der Termin-Status des Termins wird bei Abweichung auf `Vorläufig` gesetzt. Dies geschieht
    automatisch ab dem Anlegen eines Termins mit dieser Dienstplanungsrolle.
* `Aktion bei Überschreitung:` Was soll passieren wenn bei einem Termin die benötigte Anzahl von Teilnehmenden mit dieser
  Rolle überschritten wird. Die möglichen Aktionen sind die gleichen wie oben.
* `Vorlaufzeit` Anzahl der Tage bevor die Aktionen final angewendet werden. Dies ist zum einen die automatische Absage
  und zum anderen die Benachrichtigung der `Rollen Verantworlichen` und der `Organisierenden Person`. Werden Termnine 
  abgesagt, so werden die Teilnehmenden automatisch über die absage informiert.
* `Erforderliche Teilenhmer-Gruppen für diese Rolle:` Liste von Gruppen, in denen sich potenzielle Teilnehmende jeweils
  befinden müssen, um diese Rolle einnehmen zu dürfen.
* `Verhalten der erforderlichen Gruppen:` Bestimmt ob sich potenzielle Teilnehmende jeweils auf einer oder alle der oben
 aufgeführten Listen befinden müssen.
* `Berechtigungen (Karteireiter):` Liste von Berechtigungen rund um diese Dienstplanungsrolle.

> [!NOTE]  
> Wenn spezielle Aktionen angewendet werden wird das Management des Termins automatisch von der Dienstplanung übernommen.
> Es ist daher **nicht mehr möglich** den Termin-Status manuell zu setzten. Soll ein automatischer Termin beispielsweise
> doch stattfinden, so muss zuerst die Aktion in der Dienstplaungskonfiguration des Termins geändert werden bevor das
> manuelle zusagen des Termins möglich ist.

## Terminarten
Die Dienstplanung erweitert Terminarten (z.B. _Heilige Messe_, _Taufe_, ...) um `Dienstplanungsrollen Konfigurationen` 
bei denen pro Terminart hinterlegt werden kann wie viele Teilnehmende einer bestimmten _Dienstplanungsrolle_ für diese 
Terminart benötigt werden.

Darüber hinaus kann die Konfiguration der jeweiligen Dienstplanungsrolle im Rahmen dieser Terminart angepasst werden.
Wird die spezielle Konfiguration leer gelassen so greifen die Standard-Einstellungen der Dienstplanungsrolle.

Beispiel _Heilige Messe_:

- Benötigt einen Zelebrant
  - Abweichend vom Standard Zelebranten muss ein _Zelebrant_ für eine _Heilige Messe_ in einer der Gruppen `Pfarrer` oder 
    `Pastor - Pater - Kaplan` sein.
- Benötigt einen Küster
- Benötigt einen Organisten
- Benötigt Kommunionhelfer 
- ...

Zusätzlich wird noch festgelegt:
* `Verhalten wenn diese Rolle auch aus anderen Terminarten gefordert wird` Die Auswahlmöglichkeiten sind hier:
  * `Muss mit gleichem Teilnehmer besetzt werden:` Für _Zelebranten_ ist diese Auswahl sinnvoll, da es nur einen Zelebranten
     in einer Messe geben darf.
  * `Darf nicht mit gleichem Teilnehmer besetzt werden:` Für _Kommunionhelfer_ könnte dies eine sinnvolle Einstellung sein, 
    da unterschiedliche Aufgaben in der Messe zu erledigen sind.
  * `Darf mit gleichem Teilnehmer besetzt werden:` Wird diese Option gewählt können bei der Zuweisung der Dienste sowohl die
    selben Personen gewählt werden als auch unterschiedliche. Dies bietet die größte Flexibilität. So kann ein Dienst z.B.
    auf zwei Personen aufgeteilt werden wenn unterschiedliche Terminarten abweichende Qualifikationen erfordern und diese
    nicht durch eine der verfügbaren Personen gemeinsam erfüllbar sind.
* `Teilnehmer die diese Rolle besetzen dürfen auch noch andere Rollen im Termin einnehmen.` Diese Option kann gewählt
  werden wenn die Ausübung des Dienstes im Rahmen dieser Terminart die Ausübung eines anderen Dienstes nicht behindert.

## Termin - Dienstplanungskonfiguration
Sobald in der Kalender-Anwendung für einen Termin eine _Terminart_ zugefügt wird die für die Dienstplanung vorgesehen ist
wird der Karteireiter `Dienstplanung` im Termin-Bearbeiten-Dialog sichtbar. In diesem Karteireiter wird die 
_Dienstplanungskonfiguration_ für diesen speziellen Termin vorgenommen. 

Alle in dem Termin zugeordneten Terminarten hinterlegten Dienstplanungsrollen werden in die Dienstplanungskonfiguration aufgenommen.
Für jede Dienstplanungsrolle wird angezeigt für welche Terminarten sie benötigt werden. Die Anzahl der benötigen Teilnehmenden
wird gemäß der Konfigurationen entsprechend berechnet und auch Aktionen bei Unter- bzw. Über-schreitung der Teilnehmendenzahl
vorausgefüllt. 

Im Kontext eines speziellen Termins ist es jederzeit möglich die Dienstplanungskonfiguration, abweichend von den 
Einstellungen der _Terminart_ bzw. der _Dienstplanungsrolle_, anzupassen. So können weitere Dienstplanungsrollen zugefügt
werden auch wenn diese nicht in den hinterlegten Terminarten gefordert werden. Auch die Anzahl der benötigten
Teilnehmenden sowie weitere Detail-Konfiguration kann vorgenommen werden.

Lediglich die Einstellungen zur Qualifikation (also den Gruppen in denen sich die jeweiligen Teilnehmenden befinden müssen)
können nicht angepasst werden.

Maßgeblich für die Dienstplanung als solches (Also die Zuordnung von Teilnehmenden zum Termin mit ihren entsprechenden
Dienstplanungsrollen) ist die Dienstplanungskonfiguration am jeweiligen Termin.

@TODO
SRSA kann jetzt nur noch may und must sein. bei must muss ein attendee für alle Terminarten geeignet sein. bei may könnte die Dienstplanungskonfiguration aufgesplittet werden

## Standorte
Im **Kontakt** (Anwendung Adressbuch) werden alle Standorte hinterlegt für die diese Person eingesetzt werden darf. Ist
_kein_ Standort angegeben, so ist es erlaubt diese Person in _allen_ Standorten einzusetzen.

Im **Termin** wird der Standort festgelegt für den er geplant werden soll. Ist dort _kein_ Standort hinterlegt so werden
_keine_ Standort Bedingungen für diesen Termin angewendet.

FEATURE_SITE (Tinebase)
FEATURE_EVENT_TYPES (Calendar)

## Lieblingspartner

## Lieblingstage

## Terminumfragen


## Diensteinsatzplanung
In der Diensteinsatzplanung (Anwendung Dienstplanung) werden potenzielle Teilnehmende unter Berücksichtigung ihrer
`erlaubten Einsatzzwecke`, ihren `Vorlieben` der `Standorte` und der jeweiligen `Termin - Dienstplanungskonfiguration` 
den Terminen zugeordnet.

Darf von den Rollenverantwortlichen durchgeführt werden.

Status der Teilnehmenden wird automatisch auf `Zugesagt` gesetzt.

@TODO allg. Bedienung, anzeige der validierung etc.


