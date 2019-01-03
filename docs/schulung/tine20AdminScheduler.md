Tine 2.0 Admin Schulung: Scheduler / Hintergrunddienste
=================

Version: Caroline 2017.11

Wie richte ich den Cronjob ein?
====

Tine 2.0 führt regelmäßige Hintergrund-Dienste aus. Diese werden durch den SystemCron angestoßen. Er läuft minütlich. Intern prüft Tine 2.0 ob aufgaben zu erledigen sind
(wie z.b. das Versenden einer Termin-Erinnerung) und delegiert die Aufgaben
entsprechend.

Der System Cron ist in der Konfigurationsdatei durch die Installationspakete hinterlegt

cat /etc/cron.d/tine20-tinebase:

    SHELL=/bin/bash
    PATH=/sbin:/bin:/usr/sbin:/usr/bin
    * * * * * www-data /usr/sbin/tine20-cli
    --method=Tinebase.triggerAsyncEvents | logger -p daemon.notice -t "Tine 2.0
    scheduler"
