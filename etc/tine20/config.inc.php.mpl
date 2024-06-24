<?php

return array(
    'buildtype' => '{{getenv "TINE20_BUILDTYPE" "RELEASE"}}',
    'confdfolder' => '/etc/tine20/conf.d',

    'filesdir'  => '{{getenv "TINE20_FILESDIR" "/var/lib/tine20/files"}}',
    'tmpdir' => '{{getenv "TINE20_TMPDIR" "/var/lib/tine20/tmp"}}',

    'database' => array(
        'host' => '{{.Env.TINE20_DATABASE_HOST}}',
        'dbname' => '{{.Env.TINE20_DATABASE_DBNAME}}',
        'username' => '{{.Env.TINE20_DATABASE_USERNAME}}',
        'password' => '{{.Env.TINE20_DATABASE_PASSWORD}}',
        'tableprefix'  => '{{getenv "TINE20_DATABASE_TABLEPREFIX" "tine20_"}}',
        'adapter' => '{{getenv "TINE20_DATABASE_ADAPTER" "pdo_mysql"}}',
    ),

    'setupuser' => array(
        {{if not (eq (getenv "TINE20_SETUPUSER_USERNAME" "") "")}}
        'username' => '{{.Env.TINE20_SETUPUSER_USERNAME}}',
        {{end}}
        {{if not (eq (getenv "TINE20_SETUPUSER_PASSWORD" "") "")}}
        'password' => '{{.Env.TINE20_SETUPUSER_PASSWORD}}',
        {{end}}
    ),

    'login' => array(
        {{if not (eq (getenv "TINE20_LOGIN_USERNAME" "") "")}}
        'username' => '{{.Env.TINE20_LOGIN_USERNAME}}',
        {{end}}
        {{if not (eq (getenv "TINE20_LOGIN_PASSWORD" "") "")}}
        'password' => '{{.Env.TINE20_LOGIN_PASSWORD}}',
        {{end}}
    ),

    'caching' => array (
       'active' => {{getenv "TINE20_CACHING_ACTIVE" "true"}},
       'lifetime' => {{getenv "TINE20_CACHING_LIFETIME" "3600"}},
       'backend' => '{{getenv "TINE20_CACHING_BACKEND" "File"}}',
       'redis' => array (
           'host' => '{{getenv "TINE20_CACHING_REDIS_HOST" ""}}',
           'port' => {{getenv "TINE20_CACHING_REDIS_PORT" "6379"}},
           'prefix' => '{{getenv "TINE20_CACHING_REDIS_PREFIX" "master"}}',
       ),
       'path' => '{{getenv "TINE20_CACHING_PATH" "/var/lib/tine20/caching"}}',
    ),

    'filesystem' => array(
        'index_content' => {{getenv "TINE20_FILESYSTEM_INDEX_CONTENT" "true"}},
        'modLogActive' => {{getenv "TINE20_FILESYSTEM_MODLOG_ACTIVE" "true"}},
    ),

    'session' => array (
        'lifetime' => {{getenv "TINE20_SESSION_LIFETIME" "86400"}},
        'backend' => '{{getenv "TINE20_SESSION_BACKEND" "File"}}',
        'path' => '{{getenv "TINE20_SESSIONP_PATH" "/var/lib/tine20/sessions"}}',
        'host' => '{{getenv "TINE20_SESSION_HOST" ""}}',
        'port' => '{{getenv "TINE20_SESSION_PORT" "6379"}}',
        {{if not (eq (getenv "TINE20_SESSION_PREFIX" "") "")}}
        'prefix' => '{{getenv "TINE20_SESSION_PREFIX"}}',
        {{end}}
    ),

    'credentialCacheSharedKey' => '{{.Env.TINE20_CREDENTIALCACHESHAREDKEY}}',

    {{if (eq (getenv "TINE20_LOGGGER" "true") "true")}}
    'logger' => array (
        'active' => true,
        'filename' => '{{getenv "TINE20_LOGGER_FILENAME" "php://stdout"}}',
        'priority' => {{getenv "TINE20_LOGGER_PRIORITY" "5"}},
        'logruntime' => true,
        'logdifftime' => true,
        'traceQueryOrigins' => true,
        {{if not (eq (getenv "TINE20_LOGGER_ADDITIONALWRITERS_FILENAME" "") "")}}
        'additionalWriters' => array(array(
            'active' => true,
            'filename' => '{{getenv "TINE20_LOGGER_ADDITIONALWRITERS_FILENAME"}}',
            'priority' => {{getenv "TINE20_LOGGER_ADDITIONALWRITERS_PRIORITY" "5"}},
        )),
        {{end}}
    ),
    {{end}}

    {{if (eq (getenv "TINE20_ACTIONQUEUE" "true") "true")}}
    'actionqueue' => array(
        'active' => true,
        'backend' => 'Redis',
        'host' => '{{getenv "TINE20_ACTIONQUEUE_HOST" ""}}',
        'port' => {{getenv "TINE20_ACTIONQUEUE_PORT" "6379"}},
        'queueName' => '{{getenv "TINE20_ACTIONQUEUE_QUEUENAME" "actionqueue"}}',
        {{if not (eq (getenv "TINE20_ACTIONQUEUE_QUEUES" "") "")}}
        'queues' => {{getenv "TINE20_ACTIONQUEUE_QUEUES"}},
        {{end}}
        {{if (eq (getenv "TINE20_ACTIONQUEUE_LONG_RUNNING_QUEUE" "false") "true")}}
        'longRunning' => 'LR',
        {{end}}
    ),
    {{end}}

    'fulltext' => array(
        'tikaJar' => '/usr/local/bin/tika.jar',
    ),

    {{if not (eq (getenv "TINE20_URL" "") "")}}
    'tine20URL' => '{{getenv "TINE20_URL"}}',
    {{ end }}
);
