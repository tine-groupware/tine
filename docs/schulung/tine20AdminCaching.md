Tine 2.0 Admin Schulung: Caching-Settings
=================

Version: Caroline 2017.11
Version: Nele 2018.11

Verwenden von Redis als Cache-Backend
=================

    apt install redis-server php5-redis
    
(bzw php-redis / php7.0-redis unter debian 9 / ...)

eintrag in der config.inc.php:

    'caching' => array (
        'active' => true,
        'path' => '/var/lib/tine20/cache',
        'lifetime' => 3600,
        'backend' => 'Redis',
        'redis' => array (
              'host' => '127.0.0.1',
              'port' => 6379,
              'prefix' => 'tine20site'
        ),
    ),
