<?php

namespace LeKoala\DevToolkit;

use Psr\Log\LoggerInterface;
use LeKoala\Base\Helpers\ClassHelper;
use SilverStripe\Core\Injector\Injector;

class Benchmark
{
    /**
     * @return \Monolog\Logger
     */
    public static function getLogger()
    {
        $parts = explode("\\", get_called_class());
        $class = array_pop($parts);
        return Injector::inst()->get(LoggerInterface::class)->withName(ClassHelper::getClassWithoutNamespace($class));
    }

    /**
     * A dead simple benchmark function
     *
     * Usage : bm(function() { // Insert here the code to benchmark });
     * Alternative usage : bm() ; // Code to test ; bm();
     *
     * @param callable $cb
     * @return void
     */
    public static function run($cb = null)
    {
        $data = self::benchmark($cb);
        if (!$data) {
            return;
        }
        printf("It took %s seconds and used %s memory", $data['time'], $data['memory']);
        die();
    }

    /**
     * @param null|callable $cb
     * @return false|array{'time': string, 'memory': string}
     */
    protected static function benchmark($cb = null)
    {
        static $data = null;

        // No callback scenario
        if ($cb === null) {
            if ($data === null) {
                $data = [
                    'startTime' => microtime(true),
                    'startMemory' => memory_get_usage(),
                ];
                // Allow another call
                return false;
            } else {
                $startTime = $data['startTime'];
                $startMemory = $data['startMemory'];

                // Clear for future calls
                $data = null;
            }
        } else {
            $startTime = microtime(true);
            $startMemory = memory_get_usage();

            $cb();
        }

        $endTime = microtime(true);
        $endMemory = memory_get_usage();

        $time = sprintf("%.6f", $endTime - $startTime);
        $memory = self::bytesToHuman($endMemory - $startMemory);

        return [
            'time' => $time,
            'memory' => $memory,
        ];
    }

    protected static function bytesToHuman(float $bytes, int $decimals = 2): string
    {
        if ($bytes == 0) {
            return "0.00 B";
        }
        $e = floor(log($bytes, 1024));
        return round($bytes / pow(1024, $e), 2) . ['B', 'KB', 'MB', 'GB', 'TB', 'PB'][$e];
    }

    /**
     * @param string $name
     * @param null|callable $cb
     * @return void
     */
    public static function log(string $name, $cb = null): void
    {
        $ignoredPaths = [
            'schema/'
        ];
        $requestUri = $_SERVER['REQUEST_URI'] ?? '';
        foreach ($ignoredPaths as $ignoredPath) {
            if (str_contains($requestUri, $ignoredPath)) {
                return;
            }
        }
        $data = self::benchmark($cb);
        if (!$data) {
            return;
        }

        $time = $data['time'];
        $memory = $data['memory'];

        $bt = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
        $line = $bt[1]['line'] ?? 0;
        $file = basename($bt[1]['file'] ?? "unknown");

        self::getLogger()->debug("$name : $time seconds | $memory memory.", [$requestUri, "$file:$line"]);
    }
}
