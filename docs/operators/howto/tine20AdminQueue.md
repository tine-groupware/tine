# Tine Admin HowTo: ActionQueue + Worker
=================

Version: Ellie 2023.11

## Welche Jobs landen in der Queue?

- Kalender-Notifications
- File-Indizierung
- Preview-Generierung
- Virenscans?
- TODO: gibt es mehr?

## tine Update ("waited for Action Queue to become empty for more than 300 sec")

This error occurs when the tine update (setup.php --update) runs and there are still some jobs in the queue.
When the jobs are not finished in 5 minutes, the update process stops.

You can force the update by giving the parameter `skipQueueCheck=1`:

    php setup.php --update -- skipQueueCheck=1

## Activate/Deactivate Queue & Worker in docker setup

You can just set this ENV variable (for example in docker-compose.yml) to deactivate the queue & worker (it is active by default in the PROD image):

    TINE20_ACTIONQUEUE: "false"

## Monitoring

    php tine20.php --method Tinebase.monitoringCheckQueue

## Restart Worker (Docker)

    ps aux | grep worker # get PID
    kill PID

-> worker process restarts.

## Activate Worker Logging

Add something like this to /etc/tine20/actionQueue.ini:

    general.logfile=/var/log/tine20/worker.log

## Anschauen / Leeren der Queue

schauen, ob der worker was in der queue hat (-n DBNUMBER):

    [root@vmw02: ~ ] redis-cli --scan --pattern "*tine20worker*" -n 0


viele einträge löschen:

    redis-cli --scan --pattern '*PATTERN*' | xargs -L 1000 redis-cli unlink

oder

    redis-cli -h redis.host EVAL "return redis.call('del', 'defaultKey',unpack(redis.call('keys', ARGV[1])))" 0 tine20worker_*

eintrag in der queue anschauen

    redis-cli hval tine20workerData:UUID

## Restore a job from the dead letter queue

find out job id (tine.log):

    Tinebase_ActionQueue_Backend_Redis::send::286 queued job c0cf42818a1dc246ffe1319afd221df88f9cfec9 on queue
        besQueue (datastructname: PREFIXData)

look at deadletter queue job:

    redis-cli -h redishost hgetall PREFIXDeadLetter:c0cf42818a1dc246ffe1319afd221df88f9cfec9

restore:

    php tine20.php --method Tinebase.actionQueueRestoreDeadLetter -- jobId=c0cf42818a1dc246ffe1319afd221df88f9cfec9

## Was ist der Unterschied zwischen Worker/Queue und dem tine Cronjob?

Der Worker kann dafür genutzt werden, bestimmte Jobs asynchron abzuarbeiten. Es kann hier z.b. die Anzahl der Child-Prozesse definiert werden, ausserdem kann eine "Long-Running"-Queue definiert werden, in der Jobs landen, die keine so hohe Priorität haben.

Der Cronjob (triggerAsyncEvents) wiederum sollte 1x pro Minute ausgeführt werden und steuert/startet die Jobs im Scheduler (die zu bestimmten Zeiten laufen sollen, siehe Admin/Scheduler).
