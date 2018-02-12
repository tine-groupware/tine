Tine 2.0 Admin Schulung: Kalender
=================

Versionen: Egon 2016.11 + Caroline 2017.11

Konfiguration und Problemlösungen im Kalender-Modul von Tine 2.0

Problem: Termin ist verschwunden
=================

1. Sammeln von Daten

In der Datenbank (und evtl in den Logs) schauen, wer (bzw. welcher Client) das war und wann es passiert ist.

Finden des Termins im Kalender:

    > SELECT * FROM tine20_cal_events where summary LIKE '%TERMINTHEMA%';

Teilnehmer des Termins:

    > SELECT * FROM tine20_cal_attendee where cal_event_id = 'EVENTID';

Adressbucheintrag des TN (wenn es ein Kontakt ist):

    > SELECT * FROM tine20_addressbook where id = 'ATTENDEE_USERID';

Änderungshistorie des Termins:

    > SELECT * FROM timemachine_modlog where record_id = 'EVENTID';

Frage: Was passiert mit Terminen eines gelöschten Benutzers?
=================

Es gibt in der aktuellen Version 2017.11 folgende Einstellmöglichkeiten für den "Account Löschen"-Event:

/**
* possible values:
*
* _deletePersonalContainers => delete personal containers
* _keepAsContact => keep "account" as contact in the addressbook
* _keepOrganizerEvents => keep accounts organizer events as external events in the calendar
* _keepAsContact => keep accounts calender event attendee as external attendee
*/

Gesetzt wird das dann so (config.inc.php):

'accountDeletionEventConfiguration' => array(
  '_deletePersonalContainers' => true,
  '_keepAsContact' => true,
  // ...
),

Wenn die Einstellung nicht gesetzt ist, werden die Daten des Nutzers nicht gelöscht. Der Kontakt wird allerdings aus dem Adressbuch entfernt und ist damit auch nicht mehr als Teilnehmer oder Organizer sichtbar. Die Termine bleiben aber erhalten.


Problem: Link fehlt in Termin-Erinnerungs-Mail
=================

Um den richtigen Link in die Erinnerungs-E-Mail zu bekommen, muss folgender Eintrag in die Konfiguration geschrieben werden:

    'tine20URL' => 'https://my.tine20.domain'
