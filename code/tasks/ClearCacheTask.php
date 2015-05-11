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
        $folder = TEMP_FOLDER;


        $di = new RecursiveDirectoryIterator($folder,
            FilesystemIterator::SKIP_DOTS);
        $ri = new RecursiveIteratorIterator($di,
            RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($ri as $file) {
            echo "Deleting " . $file . '<br/>';
            if($file->isFile()) {
                unlink($file);
            }
        }
        echo '<hr>Clear completed!';
    }
}