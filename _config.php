<?php
if (!defined('DEVTOOLKIT_PATH')) {
    define('DEVTOOLKIT_PATH', rtrim(basename(dirname(__FILE__))));
}

// Ignore jpeg warnings
ini_set('gd.jpeg_ignore_warning', 1);

// Use _ss_environment.php, otherwise Director::isDev won't work properly
// See sample _ss_environment file in /ressources folder
require_once('conf/ConfigureFromEnv.php');

// Define logging - don't forget to disable access to log files in htaccess, see ressources folder for sample htaccess
ini_set('error_log', Director::baseFolder() . '/error.log');
SS_Log::add_writer(new SS_LogFileWriter(Director::baseFolder() . '/silverstripe.log'), SS_Log::INFO, '<=');

// Set a cache (disabled in dev mode anyway)
//HTTP::set_cache_age(60 * 30); // 30 min
//Might be better to add this to "Page::init"
// Configure according to environment
if (Director::isDev()) {
    // Display all errors
    error_reporting(-1);

    // SS3.6 and PHP7 still have some issue
    if ((float)phpversion() >= 7) {
        error_reporting(E_ALL ^ E_DEPRECATED);
    }

    // Add a debug logger
    SS_Log::add_writer(new SS_LogFileWriter(Director::baseFolder() . '/debug.log'), SS_Log::DEBUG, '=');

    // Send emails to admin
    Email::send_all_emails_to(Email::config()->admin_email);

    // Disable DynamicCache
    if (class_exists('DynamicCache')) {
        DynamicCache::config()->enabled = false;
    }

    // See where are included files except if FileAttachmentField is used
    if (class_exists('FileAttachmentField') || Director::is_ajax()) {
        Config::inst()->update('SSViewer', 'source_file_comments', false);
    } else {
        Config::inst()->update('SSViewer', 'source_file_comments', true);
    }

    // Fix this issue https://github.com/silverstripe/silverstripe-framework/issues/4146
    if (isset($_GET['flush'])) {
        i18n::get_cache()->clean(Zend_Cache::CLEANING_MODE_ALL);
    }
} else {
    // In production, sanitize php environment to avoid leaking information
    ini_set('display_errors', false);

    // Hide where are included files
    Config::inst()->update('SSViewer', 'source_file_comments', false);

    // Warn admin if errors occur
    SS_Log::add_writer(new SS_LogEmailWriter(Email::config()->admin_email), SS_Log::ERR, '<=');
}

// Protect website if env = isTest
if (Director::isTest()) {
    // If php runs under cgi, Http auth might not work by default. Don't forget to update htaccess
    if (!isset($_SERVER['PHP_AUTH_USER'])) {
        if (isset($_SERVER['HTTP_AUTHORIZATION']) && (strlen($_SERVER['HTTP_AUTHORIZATION']) > 0)) {
            list($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW']) = explode(':', base64_decode(substr($_SERVER['HTTP_AUTHORIZATION'], 6)));
            if (strlen($_SERVER['PHP_AUTH_USER']) == 0 || strlen($_SERVER['PHP_AUTH_PW']) == 0) {
                unset($_SERVER['PHP_AUTH_USER']);
                unset($_SERVER['PHP_AUTH_PW']);
            }
        }
    }

    $ip = isset($_SERVER['HTTP_X_REAL_IP']) ? $_SERVER['HTTP_X_REAL_IP'] : null;
    if (!$ip && isset($_SERVER['REMOTE_ADDR'])) {
        $ip = $_SERVER['REMOTE_ADDR'];
    }
    $allowedIps = ['127.0.0.1'];
    if (defined('ALLOWED_IPS')) {
        $allowedIps = ALLOWED_IPS;
        if (!is_array($allowedIps)) {
            $allowedIps = explode('|', ALLOWED_IPS);
        }
    }
    if (!in_array($ip, $allowedIps)) {
        BasicAuth::protect_entire_site();
    }
}

// CodeEditorField integration
if (class_exists('CodeEditorField')) {
    HtmlEditorConfig::get('cms')->enablePlugins(array(
        'aceeditor' => '../../../codeeditorfield/javascript/tinymce/editor_plugin_src.js'
    ));
    HtmlEditorConfig::get('cms')->insertButtonsBefore('fullscreen', 'aceeditor');
    HtmlEditorConfig::get('cms')->removeButtons('code');
}

if (defined('DEVTOOLKIT_USE_APC') && DEVTOOLKIT_USE_APC) {
    SS_Cache::add_backend('two_level', 'Two-Levels', array(
        'slow_backend' => 'File',
        'fast_backend' => 'APC',
        'slow_backend_options' => array(
            'cache_dir' => TEMP_FOLDER
        )
    ));
    SS_Cache::pick_backend('two_level', 'any', 10);
}
if (defined('DEVTOOLKIT_USE_MEMCACHED') && DEVTOOLKIT_USE_MEMCACHED) {
    // Note : this use the Memcache extension, not the Memcached extension
    // (with a 'd' - which use libmemcached)
    // Install from https://pecl.php.net/package/memcache
    // For windows : https://mnshankar.wordpress.com/2011/03/25/memcached-on-64-bit-windows/
    SS_Cache::add_backend('two_level', 'Two-Levels', array(
        'slow_backend' => 'File',
        'fast_backend' => 'Memcached',
        'slow_backend_options' => array(
            'cache_dir' => TEMP_FOLDER
        ),
        'fast_backend_options' => array(
            'servers' => array(
                'host' => defined('MEMCACHE_HOST') ? MEMCACHE_HOST : 'localhost',
                'port' => defined('MEMCACHE_PORT') ? MEMCACHE_PORT : 11211,
                'persistent' => true,
                'weight' => 1,
                'timeout' => 5,
                'retry_interval' => 15,
                'status' => true,
                'failure_callback' => null
            )
        )
    ));
    SS_Cache::pick_backend('two_level', 'any', 10);
}

// Really basic newrelic integration
if (defined('NEWRELIC_APP_NAME') && function_exists('newrelic_set_appname')) {
    newrelic_set_appname(NEWRELIC_APP_NAME . ";Silverstripe");
}