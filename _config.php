<?php

use SilverStripe\Control\Controller;
use SilverStripe\Control\Director;
use SilverStripe\Core\Environment;

// Add a benchmark helper
if (!function_exists('bm')) {
    function bm($cb = null)
    {
        \LeKoala\DevToolkit\Benchmark::run($cb);
    }
}
// Add a debug helper
if (!function_exists('d')) {
    function d(...$args)
    {
        // Don't show on live
        if (Director::isLive()) {
            return;
        }

        $req = null;
        if (Controller::has_curr()) {
            $req = Controller::curr()->getRequest();
        }
        $debugView = \SilverStripe\Dev\Debug::create_debug_view($req);
        // Also show latest object in backtrace
        if (!Director::is_ajax()) {
            foreach (debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT) as $row) {
                if (!empty($row['object'])) {
                    $args[] = $row['object'];
                    break;
                }
            }
        }
        // Show args
        $i = 0;
        $output = [];
        foreach ($args as $val) {
            $str = $debugView->debugVariable($val, \SilverStripe\Dev\Debug::caller(), true, $i);
            if (strlen($str) > 255) {
                $str = substr($str, 0, 252) . "...";
            }
            $i++;
        }
        exit();
    }
}
// Add a logger helper
if (!function_exists('l')) {
    function l()
    {
        $priority = 100;
        $extras = func_get_args();
        $message = array_shift($extras);
        if (!is_string($message)) {
            $message = json_encode($message);
        }
        \SilverStripe\Core\Injector\Injector::inst()->get(\Psr\Log\LoggerInterface::class)->log($priority, $message, $extras);
    }
}

// Timezone setting
$SS_TIMEZONE = Environment::getEnv('SS_TIMEZONE');
if ($SS_TIMEZONE) {
    if (!in_array($SS_TIMEZONE, timezone_identifiers_list())) {
        throw new Exception("Timezone $SS_TIMEZONE is not valid");
    }
    date_default_timezone_set($SS_TIMEZONE);
}

$SS_SERVERNAME = $_SERVER['SERVER_NAME'] ?? 'localhost';
if (Director::isDev()) {
    error_reporting(-1);
    ini_set('display_errors', true);

    // Enable IDEAnnotator
    if (in_array(substr($SS_SERVERNAME, strrpos($SS_SERVERNAME, '.') + 1), ['dev', 'local', 'localhost'])) {
        \SilverStripe\Core\Config\Config::modify()->set('SilverLeague\IDEAnnotator\DataObjectAnnotator', 'enabled', true);
        \SilverStripe\Core\Config\Config::modify()->merge('SilverLeague\IDEAnnotator\DataObjectAnnotator', 'enabled_modules', [
            'app'
        ]);
    }

    // Fixes https://github.com/silverleague/silverstripe-ideannotator/issues/122
    \SilverStripe\Core\Config\Config::modify()->set('SilverLeague\IDEAnnotator\Tests\Team', 'has_many', []);
}

// When running tests, use SQLite3
// @link https://docs.silverstripe.org/en/4/developer_guides/testing/
if (Director::is_cli()) {
    if (isset($_SERVER['argv'][0]) && $_SERVER['argv'][0] == 'vendor/bin/phpunit') {
        global $databaseConfig;
        if (class_exists(\SilverStripe\SQLite\SQLite3Database::class)) {
            $databaseConfig['type'] = 'SQLite3Database';
            $databaseConfig['path'] = ':memory:';
        }
    }
}
