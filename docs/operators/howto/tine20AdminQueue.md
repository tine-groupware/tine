Tine Admin HowTo: ActionQueue + Worker
=================

Version: Ellie 2023.11

Welche Jobs landen in der Queue?
=================

- Kalender-Notifications
- File-Indizierung
- Preview-Generierung
- Virenscans?
- TODO: gibt es mehr?

Monitoring
=================

    php tine20.php --method Tinebase.monitoringCheckQueue

Restart Worker (Docker)
=================

    ps aux | grep worker # get PID
    kill PID

-> worker process restarts.

Activate Worker Logging
=================

Add something like this to /etc/tine20/actionQueue.ini:

    general.logfile=/var/log/tine20/worker.log

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

Restore a job from the dead letter queue
=================

find out job id (tine.log):

    Tinebase_ActionQueue_Backend_Redis::send::286 queued job c0cf42818a1dc246ffe1319afd221df88f9cfec9 on queue
        besQueue (datastructname: PREFIXData)

look at deadletter queue job:

    redis-cli -h redishost hgetall PREFIXDeadLetter:c0cf42818a1dc246ffe1319afd221df88f9cfec9

restore:

    php tine20.php --method Tinebase.actionQueueRestoreDeadLetter -- jobId=c0cf42818a1dc246ffe1319afd221df88f9cfec9
