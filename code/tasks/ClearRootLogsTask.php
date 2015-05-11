<?php

/**
 * Clear root logs
 *
 * @author Koala
 */
class ClearRootLogsTask extends BuildTask
{
    protected $title       = "Clear root logs";
    protected $description = 'Clear logs from base folder';

    public function run($request)
    {
        $folder = Director::baseFolder();


        $di = new FilesystemIterator($folder,
            FilesystemIterator::SKIP_DOTS);
        foreach ($di as $file) {
            if($file->getExtension() == 'log') {
                echo "Removing " . $file->getFilename() . '<br/>';
                unlink($file);
            }
        }
        echo '<hr>Clear completed!';
    }
}