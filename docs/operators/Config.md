# Configuration

# cond.d
Tine can load additional configurations from a directory. This directory can be configured: `'confdfolder' => '/path/to/folder',`.

Configs files must evaluate to a config array analog to config.inc.php. Two formats are supported: `.inc.php` and `.inc.json`.

They are loaded in ascending order. The individual loaded config are deep merged.

```php
<?php

return [
    'broadcasthub' => [
        'active' => true,
        'url'    => 'ws://localhost:4003',
        'redis'  => [
            'host' => 'cache',
        ],
    ],
];
```

```json
{
    "broadcasthub": {
        "active": true,
        "url": "ws://localhost:4003",
        "redis" {
            "host": "cache"
        }
    }
}
```