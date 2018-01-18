<?php

/**
 * Description of TestMemCacheTask
 *
 * @author Koala
 */
class TestMemcacheTask extends BuildTask
{
    protected $title = "Test Memcache";

    public function run($request)
    {
        if (!class_exists('Memcache')) {
            $this->msg("Memcache class does not exist. Make sure that the Memcache extension is installed");
        }

        $host = defined('MEMCACHE_HOST') ? MEMCACHE_HOST : 'localhost';
        $port = defined('MEMCACHE_PORT') ? MEMCACHE_PORT : 11211;

        $memcache = new Memcache;
        $connected = $memcache->connect($host, $port);

        if ($connected) {
            $this->msg("Server's version: " . $memcache->getVersion());

            $result = $memcache->get("key");

            if ($result) {
                $this->msg("Data found in cache");
            } else {
                $this->msg("Data not found in cache");
                $tmp_object = new stdClass;
                $tmp_object->str_attr = "test";
                $tmp_object->int_attr = 123;
                $tmp_object->time = time();
                $tmp_object->date = date('Y-m-d H:i:s');
                $tmp_object->arr = array(1, 2, 3);
                $memcache->set("key", $tmp_object, false, 10);
            }

            $this->msg("Store data in the cache (data will expire in 10 seconds)");
            $this->msg("Data from the cache:");
            echo '<pre>';
            var_dump($memcache->get("key"));
            echo '</pre>';
        } else {
            $this->msg("Failed to connect");
        }
    }

    protected function msg($msg, $type = "")
    {
        DB::alteration_message($msg, $type);
    }
}