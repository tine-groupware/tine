<?php
/**
 * tine Groupware
 *
 * @package     Tinebase
 * @license     https://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2008-2026 Metaways Infosystems GmbH (https://www.metaways.de)
 *
 */

if (class_exists('Tideways\Profiler')) {
    $raii = null;
    if (!\Tideways\Profiler::isStarted()) {
        \Tideways\Profiler::start([]);
    }
    \Tideways\Profiler::enableCallgraphProfiler();
    \Tideways\Profiler::enableTracingProfiler();
    \Tideways\Profiler::watchCallback(
        'Tinebase_Frontend_Cli::handle',
        function($context) {
            \Tideways\Profiler::setTransactionName($name = 'CLI: ' . ($context['args'][0] ?? null)?->method);
        }
    );
    \Tideways\Profiler::watchCallback(
        'Tinebase_Server_Cors::handle',
        function($context) {
            \Tideways\Profiler::setTransactionName('cors');
            \Tideways\Profiler::ignoreTransaction();
        }
    );
    \Tideways\Profiler::watchCallback(
        'Tinebase_Expressive_RouteHandler::dispatch',
        function($context) {
            \Tideways\Profiler::setTransactionName($name = 'Expressive: ' . $context['object']->getName());
        }
    );
    \Tideways\Profiler::watchCallback(
        'Tinebase_Http_Server::handle',
        function($context) {
            \Tideways\Profiler::setTransactionName($name = 'HTTP: ' . ($context['args'][0]['method'] ?? 'unknown'));
        }
    );
    \Tideways\Profiler::watchCallback(
        'Tinebase_Server_WebDAV::handle',
        function($context) use(&$raii) {
            $raii = new Tinebase_RAII(function() {
                if (null === \Tideways\Profiler::getTransactionName()) {
                    \Tideways\Profiler::setTransactionName('WebDAV: unauthenticated/misc');
                }
            });
        }
    );
    \Tideways\Profiler::watchCallback(
        'Sabre\DAV\Server::start',
        function($context) {
            \Tideways\Profiler::setTransactionName('WebDAV: sabre');
        }
    );
    \Tideways\Profiler::watchCallback(
        'ActiveSync_Server_Http::handle',
        function($context) {
            \Tideways\Profiler::setTransactionName('ActiveSync');
        }
    );
    \Tideways\Profiler::watchCallback(
            'Zend_Json_Server::_handle',
            function($context) {
                \Tideways\Profiler::setTransactionName($name = 'Json: ' . $context['object']->getRequest()->getMethod());
            }
        );
}

// All server operations are done in UTC
date_default_timezone_set('UTC');

// disable magic_quotes_runtime
ini_set('magic_quotes_runtime', 0);

// display errors we can't handle ourselves
error_reporting(E_COMPILE_ERROR | E_CORE_ERROR | E_ERROR | E_PARSE);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('default_charset', 'UTF-8');

if (extension_loaded('mbstring')) {
    mb_internal_encoding("UTF-8");
}

// intialize composers autoloader
$autoloader = require __DIR__ . '/vendor/autoload.php';

// activate our own error handler after autoloader initialization
set_error_handler('Tinebase_Core::errorHandler', E_ALL | E_DEPRECATED);

$memoryReserve = str_repeat('x', 1024 * 1024 * 1); // reserve 1 MB of memory
register_shutdown_function(function () use (&$memoryReserve) {
    $memoryReserve = null; // release reserved memory
    $error = error_get_last();
    if ($error !== null && str_contains($error['message'], 'Allowed memory size')) {

        Tinebase_Exception::log(new Tinebase_Exception('OOM Error: ' . print_r($error, true)));
        
        if (!headers_sent()) {
            http_response_code(200);
            header('Content-Type: application/json');
            echo '{"error":{"code":-32000,"message":"Out Of Memory","data":{"message":"Out Of Memory","code":550}},"jsonrpc":"2.0"}';
        }
    }
});
