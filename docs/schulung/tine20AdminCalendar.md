Tine 2.0 Admin Schulung: Kalender
=================

Version: Egon 2016.11

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
