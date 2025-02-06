# Logging

You can customize logging in tine either by changing the `logger` section in your `config.inc.php` or by adding 
a customized logger config in your `conf.d` directory:

``` php title="./conf.d/logger.inc.php"
--8<-- "etc/tine20/conf.d/logger.inc.php.dist"
```

## Log Format

Example log line:

    c352c2 26d1a setupuser 648ms 0ms - 2025-02-06T14:08:37+00:00 WARN (4): Tinebase_FileSystem::_isFileIndexingActive::180 Indexing active but tikaJar config is not set

- c352c2 => Request ID (random string created by server)
- 26d1a => Transaction ID (random string created by client - from $REQUEST)
- setupuser => Current User (login name)
- 648ms => Total time of the request
- 0ms => Time passed since the last logged line
- 2025-02-06T14:08:37+00:00 => Timestamp in configured TZ (default: UTC, see "Set Timezone for Logging")
- WARN (4) => Log level (see "Logger Priorities")
- Tinebase_FileSystem::_isFileIndexingActive::180 => Class name::function name::line number in PHP file
- Indexing active but tikaJar config is not set => Log message

## Logger Priorities

``` php title="Posible log priorities"
    const EMERG   = 0;  // Emergency: system is unusable
    const ALERT   = 1;  // Alert: action must be taken immediately
    const CRIT    = 2;  // Critical: critical conditions
    const ERR     = 3;  // Error: error conditions
    const WARN    = 4;  // Warning: warning conditions
    const NOTICE  = 5;  // Notice: normal but significant condition
    const INFO    = 6;  // Informational: informational messages
    const DEBUG   = 7;  // Debug: debug messages
    const TRACE   = 8;  // Trace: trace messages
```

## Set Timezone for Logging

see https://github.com/tine-groupware/tine/issues/44

The logger timezone can be configured via the 'tz' option:

~~~php
'logger' => [
  'tz' => 'America/Los_Angeles',
  // [...]
]
~~~

see https://www.php.net/manual/de/function.date-default-timezone-set.php for supported timezones.
