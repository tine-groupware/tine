tine Docker: how to check the logs
---

The tine docker container sends its logs to STDOUT by default - the logs are then accessible via the standard docker log facilities.

See this website for more information about the build-in "docker logs" command: https://geekflare.com/check-docker-logs/

## LOGGER Configuration

Find the default logger config in the container in the file /etc/tine20/config.inc.php:

~~~
    
    'logger' => array (
        'active' => true,
        'filename' => 'php://stdout',
        'priority' => 5,
        'logruntime' => true,
        'logdifftime' => true,
        'traceQueryOrigins' => true,
        
    ),

~~~

It is possible the change the log level (priority) via the docker-compose.yml.
For example, this setting changes to DEBUG log level:

~~~
TINE20_LOGGER_PRIORITY: "7"
~~~

## EXAMPLES

Find all tine logs containing the string "permission denied" between the date 2023-08-15 and 2023-08-20:

~~~shell
$ docker logs tine20-web-1 --since 2023-08-15 --until 2023-08-20 2>&1 | grep -i "permission denied"
~~~

Find more information on how to use timestamps for since/until here:
https://stackoverflow.com/questions/44443062/how-to-use-since-option-with-docker-logs-command

Follow only tine-web-1 nginx access logs:

~~~shell
$ docker logs -f tine-web-1 2>&1 | grep request_time
~~~
