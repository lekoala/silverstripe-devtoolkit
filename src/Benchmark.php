<?php

namespace LeKoala\DevToolkit;

use Psr\Log\LoggerInterface;
use LeKoala\Base\Helpers\ClassHelper;
use SilverStripe\Core\Injector\Injector;

class Benchmark
{
    /**
     * @var array<string,array<mixed>>
     */
    protected static array $metrics = [];

    /**
     * For example 0.001
     *
     * @var float
     */
    public static float $slow_threshold = 0;

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
     * @param string|null $name
     * @return false|array{'time': string, 'memory': string}
     */
    protected static function benchmark($cb = null, $name = null)
    {
        static $_data = null;

        if ($name) {
            $data = self::$metrics[$name] ?? null;
        } else {
            $data = $_data;
        }

        // No callback scenario
        if ($cb === null) {
            if ($data === null) {
                $data = [
                    'startTime' => microtime(true),
                    'startMemory' => memory_get_usage(),
                ];
                if ($name) {
                    self::$metrics[$name] = $data;
                }
                // Allow another call
                return false;
            } else {
                $startTime = $data['startTime'];
                $startMemory = $data['startMemory'];

                // Clear for future calls
                if (!$name) {
                    $_data = null;
                }
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
        // Helps dealing with nasty ajax calls in the admin
        $ignoredPaths = [
            'schema/'
        ];
        $requestUri = $_SERVER['REQUEST_URI'] ?? '';
        foreach ($ignoredPaths as $ignoredPath) {
            if (str_contains($requestUri, $ignoredPath)) {
                return;
            }
        }
        $data = self::benchmark($cb, $name);
        if (!$data) {
            return;
        }

        $time = $data['time'];
        $memory = $data['memory'];

        if (self::$slow_threshold && $time < self::$slow_threshold) {
            return;
        }

        $bt = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
        $line = $bt[1]['line'] ?? 0;
        $file = basename($bt[1]['file'] ?? "unknown");

        self::getLogger()->debug("$name: $time seconds | $memory memory.", [$requestUri, "$file:$line"]);
    }
}
