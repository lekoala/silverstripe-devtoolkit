<?php

namespace LeKoala\DevToolkit\Tasks;

use stdClass;
use Predis\Client;
use Predis\Command\ServerInfo;
use SilverStripe\Dev\BuildTask;
use LeKoala\DevToolkit\BuildTaskTools;
use SilverStripe\Core\Injector\Injector;
use LeKoala\Base\Cache\RedisCacheFactory;

/**
 */
class TestCacheSupportTask extends BuildTask
{
    use BuildTaskTools;

    protected $title = "Test Cache Support";
    protected $description = 'Check what cache backends are available and working.';
    private static $segment = 'TestCacheSupportTask';

    public function run($request)
    {
        $this->request = $request;

        $this->testMemcache();
        $this->testOpcache();
        $this->testRedis();
    }

    protected function testRedis()
    {
        $predis = new Client('tcp://127.0.0.1:6379');
        $this->message($predis->executeCommand(new ServerInfo));

        $args = [];
        $redisCache = Injector::inst()->createWithArgs(RedisCacheFactory::class, $args);
        $this->message($redisCache);
    }

    protected function testOpcache()
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
    protected function testMemcache()
    {
        if (!class_exists('Memcache')) {
            $this->msg("Memcache class does not exist. Make sure that the Memcache extension is installed");
        }

        $host = defined('MEMCACHE_HOST') ? MEMCACHE_HOST : 'localhost';
        $port = defined('MEMCACHE_PORT') ? MEMCACHE_PORT : 11211;

        $memcache = new \Memcache;
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
}
