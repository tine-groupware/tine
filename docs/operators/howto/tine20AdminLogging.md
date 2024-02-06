Tine Admin HowTo: Logging
=================

Version: Amon 2020.11

Konfiguration der verschiedenen Logger

Logger-Konfiguration (Beispiel)
=================

    'logger' => array (
        'active' => true,
        'filename' => '/tine/logs/tine20_notice.log',
        'priority' => '5',
        'logruntime' => true,
        'logdifftime' => true,
        'colorize' => true,
        'additionalWriters' => array(array(
            'active' => true,
            'filename' => '/tine/logs/debug.log',
            'priority' => '7',
            'filter'   => array(
            ),
        ), array(
            'active' => true,
            'filename' => '/tine/logs/json.log',
            'priority' => '6',
            'formatter' => 'json',
            'filter'   => array(
            ),
        )),
    ),

Zeiten im Log
=================

Folgende Konfigurationsoptionen können genutzt werden, um die Gesamtlaufzeit eines Requests (logruntime) 
 und die Zeitdauer/Abstand vom letzten Logeintrag (logdifftime) anzuzeigen:

    'logger' => array (
        [...]
        'logruntime' => true,
        'logdifftime' => true,
    )
 
Logging nach Standard-Out
=================

    'logger' => array (
        [...]
        'filename' => 'php://stdout',
    ),

Farbige Logs
=================

    'logger' => array (
        [...]
        'colorize' => true,
    ),

Json-Logger
=================

Log-Nachrichten werden JSON-kodiert abgelegt.

    'logger' => array (
        [...]
        'formatter' => 'json',
    ),
    
Zusätzliche Log-Dateien (z.B. für bestimmte Benutzer)
=================

    'logger' => array (
        [...]
        'additionalWriters' => array(array(
            'active' => true,
            'filename' => '/tine/logs/debug.log',
            'priority' => '7',
            'filter'   => array(
                'user'    => 'pschuele',
                // message filters can also be used:
                #'message' => '/Tinebase_Import_Abstract::_handleImportException/',
                #'message' => '/Addressbook_Frontend_Http/',
                #'message' => '/Tinebase_Backend_Sql_Abstract/',
            ),
        )),
    ),
    
Logging von Cache-Hits/Tests/Saves
=================

    'caching' => array(
        [...]
        'logging' => true,
    ),

Aktivierung des DB-Loggers
=================

Der DB-Logger schreibt seine Log-Einträge direkt in die Datenbank, sie können dann im Admin-Bereich eingesehen werden.

    'dblogger' => array(
        'active' => true,
        'priority' => '4',
    ),
