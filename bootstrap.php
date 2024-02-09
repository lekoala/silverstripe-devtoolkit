<?php

require 'src/Benchmark.php';

// Add a benchmark logger helper
if (!function_exists('bml')) {
    function bml(string $name): void
    {
        \LeKoala\DevToolkit\Benchmark::log($name);
    }
}
