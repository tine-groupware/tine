Tine 2.0 Admin Schulung: ActionQueue + Worker
=================

Version: Nele 2018.11

Einleitung
=================

TODO: add more (was machen queue + worker?)

Welche Jobs landen in der Queue?
=================

- Kalender-Notifications
- File-Indizierung
- Preview-Generierung
- Virenscans?
- TODO: gibt es mehr?

Einrichtung der ActionQueue
=================

TODO: add more (config.inc.php)

Einrichtung des Workers
=================

TODO: add more (configuration)

    apt install tine20-worker

TODO: add low-prio queue configuration

Monitoring
=================

    php tine20.php --method Tinebase.monitoringCheckQueue

Anschauen / Leeren der Queue
=================

schauen, ob der worker was in der queue hat (-n DBNUMBER):

    [root@vmw02: ~ ] redis-cli --scan --pattern "*tine20worker*" -n 0


viele einträge löschen:

    redis-cli --scan --pattern '*PATTERN*' | xargs -L 1000 redis-cli unlink

oder

    redis-cli -h redis.host EVAL "return redis.call('del', 'defaultKey',unpack(redis.call('keys', ARGV[1])))" 0 tine20worker_*

eintrag in der queue anschauen

    redis-cli hval tine20workerData:UUID
