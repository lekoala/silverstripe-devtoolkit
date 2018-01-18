<?php

class FastDatabaseAdmin extends DatabaseAdmin
{
    private static $allowed_actions = array(
        'index',
        'build',
        'fastbuild',
        'cleanup',
        'import'
    );

    public function fastbuild()
    {
        $this->msg("Fast building without manifest regeneration or table population");
        $this->doBuild(false, false);
    }

    protected function msg($msg)
    {
        if (Director::is_cli()) {
            echo $msg . "\n";
        } else {
            echo $msg . "<br/>";
        }

    }
}