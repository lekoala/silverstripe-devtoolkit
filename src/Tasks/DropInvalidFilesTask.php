<?php

namespace LeKoala\DevToolkit\Tasks;

use FilesystemIterator;
use SilverStripe\ORM\DB;
use SilverStripe\Assets\File;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;
use SilverStripe\Dev\BuildTask;
use SilverStripe\ORM\DataObject;
use SilverStripe\Core\Environment;
use SilverStripe\Core\Config\Config;
use SilverStripe\Versioned\Versioned;
use LeKoala\DevToolkit\BuildTaskTools;
use SilverStripe\Assets\Flysystem\ProtectedAssetAdapter;

/**
 * @author lekoala
 */
class DropInvalidFilesTask extends BuildTask
{
    use BuildTaskTools;

    protected $title = "Drop Invalid Files";
    protected $description = 'Drop file objects that are not linked to a proper asset (warning ! experimental)';
    private static $segment = 'DropInvalidFilesTask';

    public function run($request)
    {
        $this->request = $request;

        $this->addOption("go", "Tick this to proceed", false);
        $this->addOption("remove_files", "Remove db files", false);
        $this->addOption("remove_local", "Remove local files", false);

        $options = $this->askOptions();

        $go = $options['go'];
        $remove_files = $options['remove_files'];
        $remove_local = $options['remove_local'];

        if (!$go) {
            echo ('Previewing what this task is about to do.');
        } else {
            echo ("Let's clean this up!");
        }
        echo ('<hr/>');
        if ($remove_files) {
            $this->removeFiles($request, $go);
        }
        if ($remove_local) {
            $this->removeLocalFiles($request, $go);
        }
    }

    protected function removeLocalFiles($request, $go = false)
    {
        $iter = new RecursiveDirectoryIterator(ASSETS_PATH);
        $iter2 = new RecursiveIteratorIterator($iter);

        foreach ($iter2 as $file) {
            // Ignore roots and _
            $startsWithSlash = strpos($file->getName(), '_') === 0;
            $hasVariant = strpos($file->getName(), '__') !== false;
            if ($startsWithSlash || $hasVariant) {
                // $this->message("Ignore " . $file->getPath());
                continue;
            }

            // Check for empty dirs
            if ($file->isDir()) {
                // ignores .dot files
                $dirFiles = scandir($file->getPath());
                $empty = (count($dirFiles) - 2) === 0;
                if ($empty) {
                    $this->message($file->getPath() . " is empty");
                    if ($go) {
                        rmdir($file->getPath());
                    }
                }
                continue;
            }

            // Check for files not matching anything in the db
            $thisPath = str_replace(ASSETS_PATH, "", $file->getPath());
            // $this->message($thisPath);
            // $dbFile = File::get()->filter("FileFilename", $thisPath);
        }
    }

    protected function removeFiles($request, $go = false)
    {
        $conn = DB::get_conn();
        $schema = DB::get_schema();
        $dataObjectSchema = DataObject::getSchema();
        $tableList = $schema->tableList();

        $files = File::get();

        if ($go) {
            $conn->transactionStart();
        }

        $i = 0;

        /** @var File $file  */
        foreach ($files as $file) {
            $path = self::getFullPath($file);
            if (!trim($file->getRelativePath(), '/')) {
                $this->message("#{$file->ID}: path is empty");
                if ($go) {
                    // $file->delete();
                    self::deleteFile($file->ID);
                    $i++;
                }
            }
            if (!file_exists($path)) {
                $this->message("#{$file->ID}: $path does not exist");
                if ($go) {
                    // $file->delete();
                    self::deleteFile($file->ID);
                    $i++;
                }
            } else {
                $this->message("#{$file->ID}: $path is valid", "success");
            }
            if ($go && $i % 100 == 0) {
                $conn->transactionEnd();
                $conn->transactionStart();
            }
        }

        if ($go) {
            $conn->transactionEnd();
        }
    }

    /**
     * ORM is just too slow for this
     *
     * @param int $ID
     * @return void
     */
    public static function deleteFile($ID)
    {
        DB::prepared_query("DELETE FROM File WHERE ID = ?", [$ID]);
        DB::prepared_query("DELETE FROM File_Live WHERE ID = ?", [$ID]);
        DB::prepared_query("DELETE FROM File_Versions WHERE RecordID = ?", [$ID]);
        DB::prepared_query("DELETE FROM File_ViewerGroups WHERE FileID = ?", [$ID]);
        DB::prepared_query("DELETE FROM File_EditorGroups WHERE FileID = ?", [$ID]);
    }

    public static function getFullPath(File $file)
    {
        return ASSETS_PATH . '/' . $file->getRelativePath();
    }

    public function getProtectedFullPath(File $file)
    {
        return self::getBaseProtectedPath() . '/' . $file->getRelativePath();
    }

    public static function getBaseProtectedPath()
    {
        // Use environment defined path or default location is under assets
        if ($path = Environment::getEnv('SS_PROTECTED_ASSETS_PATH')) {
            return $path;
        }

        // Default location
        return ASSETS_PATH . '/' . Config::inst()->get(ProtectedAssetAdapter::class, 'secure_folder');
    }
}
