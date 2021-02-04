<?php

namespace LeKoala\DevToolkit\Helpers;

use SilverStripe\Control\Director;
use SilverStripe\Control\Controller;
use SilverStripe\Control\HTTPRequest;

class EnvironmentChecker
{
    /**
     * @param HTTPRequest $request
     * @return boolean
     */
    public static function isLocalIp(HTTPRequest $request = null)
    {
        if (!$request) {
            $request = Controller::curr()->getRequest();
        }
        return in_array($request->getIP(), ['127.0.0.1', '::1', '1']);
    }

    /**
     * Temp folder should always be there
     *
     * @return void
     */
    public static function ensureTempFolderExists()
    {
        $tempFolder = Director::baseFolder() . '/silverstripe-cache';
        if (!is_dir($tempFolder)) {
            mkdir($tempFolder, 0755);
        }
    }
}
