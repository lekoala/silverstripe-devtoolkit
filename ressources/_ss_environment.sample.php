<?php
/* Global php config */
error_reporting(-1);
ini_set("log_errors", true);
ini_set('display_errors', true);
date_default_timezone_set('Europe/Brussels');

/* What kind of environment is this: development, test, or live (ie, production)? */
define('SS_ENVIRONMENT_TYPE', 'dev');

/* Database connection */
global $database;
define('SS_DATABASE_SERVER', '127.0.0.1');
define('SS_DATABASE_USERNAME', 'root');
define('SS_DATABASE_PASSWORD', 'root');
define('SS_DATABASE_NAME', '');
$database = SS_DATABASE_NAME;

/* Configure a default username and password to access the CMS on all sites in this environment. */
define('SS_DEFAULT_ADMIN_USERNAME', 'admin');
define('SS_DEFAULT_ADMIN_PASSWORD', 'admin');
define('EMAIL_TEST_ADDRESS', '');
define('EMAIL_FROM_ADDRESS', '');
define('SS_ERROR_LOG', 'silverstripe.log');

/* Mandrill */
define('MANDRILL_API_KEY', '');

/* Deploy */
define('DEPLOY_LIVE_PORT', '');
define('DEPLOY_LIVE_TARGET', 'root@host:/path/to/website');

/* Don't forget to map properly the website to the folder otherwise it won't work in cli */
global $_FILE_TO_URL_MAPPING;
if (php_sapi_name() == 'cli') {
    $_FILE_TO_URL_MAPPING[dirname(dirname($_SERVER['SCRIPT_FILENAME']))] = 'http://www.mywebsite.ext';
}
$_FILE_TO_URL_MAPPING[''] = '';