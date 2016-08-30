<?php

/**
 * Clear cache
 *
 * @author Koala
 */
class ClearCacheTask extends BuildTask
{
    protected $title       = "Clear cache";
    protected $description = 'Clear cache from the temp folder';

    public function run($request)
    {
        // Flush memcache
        if (defined('DEVTOOLKIT_USE_MEMCACHED') && DEVTOOLKIT_USE_MEMCACHED) {
            $host = defined('MEMCACHE_HOST') ? MEMCACHE_HOST : 'localhost';
            $port = defined('MEMCACHE_PORT') ? MEMCACHE_PORT : 11211;

            $memcache  = new Memcache;
            $connected = $memcache->connect($host, $port);
            if($connected) {
                echo "Flush memcache<br/>";
                $memcache->flush();
            }
        }

        // Clear file cache
        $folder = TEMP_FOLDER;

        $di = new RecursiveDirectoryIterator($folder,
            FilesystemIterator::SKIP_DOTS);
        $ri = new RecursiveIteratorIterator($di,
            RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($ri as $file) {
            echo "Deleting ".$file.'<br/>';
            if ($file->isFile()) {
                unlink($file);
            }
        }
        echo '<hr>Clear completed!';
    }
}