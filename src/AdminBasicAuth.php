<?php

namespace LeKoala\DevToolkit;

use SilverStripe\Core\Environment;

/**
 * A dead simple alternative to built-in basic auth that is controlled with SS_USE_BASIC_AUTH
 * This one will simply check for .env admin and use native php functions to return the response
 */
class AdminBasicAuth
{
    /**
     * Require admin login
     *
     * @param string $user
     * @param string $password
     * @return void
     */
    public static function protect($user = null, $password = null)
    {
        if (!$user) {
            $user = Environment::getEnv('SS_DEFAULT_ADMIN_USERNAME');
        }
        if (!$password) {
            $password = Environment::getEnv('SS_DEFAULT_ADMIN_PASSWORD');
        }
        header('Cache-Control: no-cache, must-revalidate, max-age=0');
        $hasSuppliedCredentials = !(empty($_SERVER['PHP_AUTH_USER']) && empty($_SERVER['PHP_AUTH_PW']));
        if ($hasSuppliedCredentials) {
            $isNotAuthenticated = ($_SERVER['PHP_AUTH_USER'] != $user || $_SERVER['PHP_AUTH_PW'] != $password);
        } else {
            $isNotAuthenticated = true;
        }
        if ($isNotAuthenticated) {
            header('HTTP/1.1 401 Authorization Required');
            header('WWW-Authenticate: Basic realm="Access denied"');
            exit;
        }
    }
}
