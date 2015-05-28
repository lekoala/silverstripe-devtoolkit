<?php

/**
 * PhpInfoTask
 *
 * @author lekoala
 */
class PhpInfoTask extends BuildTask
{
    protected $title       = "Show PhpInfo";

    public function run($request)
    {
        echo phpinfo();
    }
}