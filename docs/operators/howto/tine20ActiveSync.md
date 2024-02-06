Tine Admin HowTo: ActiveSync
=================

Version: Anton 2019.11

Konfiguration und Problemlösungen im ActiveSync-Modul von Tine 2.0

Feature: Aussperren von bestimmten Geräten bzw. User-Agents
=================

es gibt dafür beiden configs:

```php
self::DEVICE_MODEL_DENY_LIST => array(
    //_('Device Model Agent Deny List')
    'label' => 'Device Model Agent Deny List',
    //_('Array of regular expressions of Device-Model strings')
    'description' => 'Array of regular expressions of Device-Model strings',
    'type' => 'array',
    'clientRegistryInclude' => FALSE,
    'setByAdminModule' => FALSE,
    'setBySetupModule' => FALSE,
    'default' => [
    // '/^Redmi 4X$/', // example if you like to deny all c models
    ],
),
self::USER_AGENT_DENY_LIST => array(
    //_('User Agent Deny List')
    'label' => 'User Agent Deny List',
    //_('Array of regular expressions of User-Agent strings')
    'description' => 'Array of regular expressions of User-Agent strings',
    'type' => 'array',
    'clientRegistryInclude' => FALSE,
    'setByAdminModule' => FALSE,
    'setBySetupModule' => FALSE,
    'default' => [
    // '/^Android-Mail.*/', // example if you like to deny all Android-Mail* clients
    ],
),
```

damit kann man die geblockten devices einstellen.

also könnte man z.b. diese config in die config.inc.php schreiben:

```php
'ActiveSync' => [
    'deviceModelDenyList' => ['/^Redmi 4X$/'],
    'userAgentDenyList' => ['/^Android-Mail.*/'],
];
```

Feature: Device Policies
=================

see https://github.com/tine20/tine20/wiki/EN:ActiveSync#activesync-device-policies
