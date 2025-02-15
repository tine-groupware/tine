# Tine Admin HowTo: ActionQueue + Worker
=================

Version: Ellie 2023.11

## Welche Jobs landen in der Queue?

- Kalender-Notifications
- File-Indizierung
- Preview-Generierung
- Virenscans?
- TODO: gibt es mehr?

## Activate/Deactivate Queue & Worker in non-docker setup

You can start a worker with this command:

    sudo -u USER worker.php

It is recommended to have some kind of process control for the worker for (auto-)starting / stopping.

Example UWSGI config:

~~~ini
[uwsgi]
master = True
vacuum = True
workers = 1
threads = 1
uid = www-data
gid = www-data
attach-daemon = php -d include_path=/etc/tine20/ /path/to/worker.php --config /etc/tine20/actionQueue.ini
~~~

Example actionQueue.ini:
~~~ini
general.daemonize=0
general.logfile=/path/to/logs/daemon.log
general.loglevel=6
tine20.shutDownWait=10
tine20.maxChildren=2
~~~

## Activate/Deactivate Queue & Worker in docker setup

You can just set this ENV variable (for example in docker-compose.yml) to deactivate the queue & worker (it is active by default in the PROD image):

    TINE20_ACTIONQUEUE: "false"

### Stop worker.php process via supervisord

    supervisorctl stop worker

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

schauen, ob der worker was in der queue hat (-n DBNUMBER, *queueName* from 'actionqueue' config):

    redis-cli --scan --pattern "*queueName*" -n 0

viele einträge löschen:

    redis-cli --scan --pattern '*queueName*' | xargs -L 1000 redis-cli unlink

oder

    redis-cli -h redis.host EVAL "return redis.call('del', 'defaultKey',unpack(redis.call('keys', ARGV[1])))" 0 queueName_*

Docker setup (container name "tine-cache-1" may vary, queueName = actionqueue):

    docker exec -it tine20-cache-1 sh
    redis-cli --scan --pattern 'actionqueue*' | xargs -L 1000 redis-cli unlink

eintrag in der queue anschauen

    redis-cli hval queueNameData:UUID

## tine Update ("waited for Action Queue to become empty for more than 300 sec")

This error occurs when the tine update (setup.php --update) runs and there are still some jobs in the queue.
When the jobs are not finished in 5 minutes, the update process stops.

You can force the update by giving the parameter `skipQueueCheck=1`:

    php setup.php --update -- skipQueueCheck=1

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
