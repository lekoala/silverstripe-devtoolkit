<?php

/**
 * Description of TestOpcacheTask
 *
 * @author Koala
 */
class TestOpcacheTask extends BuildTask
{
    protected $title = "Test Opache";

    public function run($request)
    {
        if (!function_exists('opcache_get_status')) {
            $this->msg("opcache_get_status function is not defined");
        }

        $result = opcache_get_status();
        if ($result) {
            $this->msg("Opcache is active");

            echo '<pre>';
            print_r($result);
            echo '</pre>';
        } else {
            $this->msg("Opcache is disabled. It should be enabled to ensure optimal performances", "error");
        }
    }

    protected function msg($msg, $type = "")
    {
        DB::alteration_message($msg, $type);
    }
}