<?php

namespace LeKoala\DevToolkit\Tasks;

use SilverStripe\Dev\BuildTask;

/**
 */
class PhpInfoTask extends BuildTask
{
    protected $title = "Php Info";
    protected $description = 'Simply read your php info values.';
    private static $segment = 'PhpInfoTask';

    public function run($request)
    {
        echo phpinfo();
    }
}
