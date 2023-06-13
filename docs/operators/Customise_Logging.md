Customise Logging
=
You can customize logging in tine either by changing the `logger` section in your `config.inc.php` or by adding 
a customized logger config in your `conf.d` directory:


``` php title="./conf.d/sso.inc.php"
--8<-- "etc/tine20/conf.d/logger.inc.php.dist"
```

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
