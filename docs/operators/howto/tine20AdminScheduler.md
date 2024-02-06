# Tine Admin HowTo: Scheduler / Hintergrunddienste

## Wie richte ich den Cronjob ein?

tine Groupware führt regelmäßige Hintergrund-Dienste aus. Diese werden durch den System-Cron angestoßen.
Er läuft minütlich. Intern prüft tine, ob Aufgaben zu erledigen sind
(wie z.b. das Versenden einer Terminerinnerung) und delegiert die Aufgaben
entsprechend.

Der System-Cron ist im Docker-Image enthalten und muss bei Verwendung von Docker nicht angelegt werden.

cat /etc/cron.d/tine20-tinebase:

    SHELL=/bin/bash
    PATH=/sbin:/bin:/usr/sbin:/usr/bin
    * * * * * www-data /usr/sbin/tine20-cli
    --method=Tinebase.triggerAsyncEvents | logger -p daemon.notice -t "Tine 2.0
    scheduler"

## Wie kann ich die Scheduler-Jobs einsehen?

Im Admin-Bereich gibt es eine Anzeige der Jobs unter dem Punkt "Scheduler".
