<?php

namespace LeKoala\DevToolkit\Extensions;

use Exception;
use SilverStripe\ORM\DB;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;
use SilverStripe\Core\ClassInfo;
use SilverStripe\Core\Extension;
use SilverStripe\ORM\DataObject;
use SilverStripe\Control\Director;
use SilverStripe\Control\HTTPRequest;
use LeKoala\DevToolkit\Helpers\DevUtils;
use LeKoala\DevToolkit\Helpers\FileHelper;
use LeKoala\DevToolkit\Helpers\SubsiteHelper;

/**
 * Allow the following functions before dev build
 * - renameColumns
 * - truncateSiteTree
 *
 * Allow the following functions after dev build:
 * - generateQueryTraits
 * - clearCache
 * - clearEmptyFolders
 * - provisionLocales
 *
 * Preserve current subsite
 *
 * @property \SilverStripe\Dev\DevBuildController $owner
 */
class DevBuildExtension extends Extension
{
    /**
     * @var \SilverStripe\Subsites\Model\Subsite|null
     */
    protected $currentSubsite;

    /**
     * @return \SilverStripe\Dev\DevBuildController
     */
    public function getExtensionOwner()
    {
        return $this->owner;
    }

    /**
     * @return HTTPRequest
     */
    public function getRequest()
    {
        return $this->getExtensionOwner()->getRequest();
    }

    /**
     * @return void
     */
    public function beforeCallActionHandler()
    {
        $this->currentSubsite = SubsiteHelper::currentSubsiteID();

        $annotate = $this->getRequest()->getVar('annotate');
        if ($annotate) {
            \SilverLeague\IDEAnnotator\DataObjectAnnotator::config()->enabled = true;
        } else {
            \SilverLeague\IDEAnnotator\DataObjectAnnotator::config()->enabled = false;
        }

        $renameColumns = $this->getRequest()->getVar('fixTableCase');
        if ($renameColumns) {
            $this->displayMessage("<div class='build'><p><b>Fixing tables case</b></p><ul>\n\n");
            $this->fixTableCase();
            $this->displayMessage("</ul>\n<p><b>Tables fixed!</b></p></div>");
        }

        $renameColumns = $this->getRequest()->getVar('renameColumns');
        if ($renameColumns) {
            $this->displayMessage("<div class='build'><p><b>Renaming columns</b></p><ul>\n\n");
            $this->renameColumns();
            $this->displayMessage("</ul>\n<p><b>Columns renamed!</b></p></div>");
        }

        $truncateSiteTree = $this->getRequest()->getVar('truncateSiteTree');
        if ($truncateSiteTree) {
            $this->displayMessage("<div class='build'><p><b>Truncating SiteTree</b></p><ul>\n\n");
            $this->truncateSiteTree();
            $this->displayMessage("</ul>\n<p><b>SiteTree truncated!</b></p></div>");
        }

        // Reverse the logic, don't populate by default
        DevUtils::updatePropCb($this->getRequest(), 'getVars', function ($arr) {
            $arr['dont_populate'] = !!$this->getRequest()->getVar('populate');
            return $arr;
        });
    }

    protected function fixTableCase(): void
    {
        if (!Director::isDev()) {
            throw new Exception("Only available in dev mode");
        }

        $conn = DB::get_conn();
        $dbName = $conn->getSelectedDatabase();

        $tablesSql = "SELECT table_name FROM information_schema.tables WHERE table_schema = '$dbName';";

        $result = DB::query($tablesSql);

        //TODO: check list of tables name and match any lowercased one to the right one from the db schema
    }

    protected function truncateSiteTree(): void
    {
        if (!Director::isDev()) {
            throw new Exception("Only available in dev mode");
        }

        $sql = <<<SQL
        TRUNCATE TABLE ErrorPage;
        TRUNCATE TABLE ErrorPage_Live;
        TRUNCATE TABLE ErrorPage_Versions;
        TRUNCATE TABLE SiteTree;
        TRUNCATE TABLE SiteTree_CrossSubsiteLinkTracking;
        TRUNCATE TABLE SiteTree_EditorGroups;
        TRUNCATE TABLE SiteTree_ImageTracking;
        TRUNCATE TABLE SiteTree_LinkTracking;
        TRUNCATE TABLE SiteTree_Live;
        TRUNCATE TABLE SiteTree_Versions;
        TRUNCATE TABLE SiteTree_ViewerGroups;
SQL;
        DB::query($sql);
        $this->displayMessage($sql);
    }

    /**
     * Loop on all DataObjects and look for rename_columns property
     *
     * It will rename old columns from old_value => new_value
     */
    protected function renameColumns(): void
    {
        $classes = $this->getDataObjects();

        foreach ($classes as $class) {
            if (!property_exists($class, 'rename_columns')) {
                continue;
            }

            $fields = $class::$rename_columns;

            $schema = DataObject::getSchema();
            $tableName = $schema->baseDataTable($class);

            $dbSchema = DB::get_schema();
            foreach ($fields as $oldName => $newName) {
                if ($dbSchema->hasField($tableName, $oldName)) {
                    if ($dbSchema->hasField($tableName, $newName)) {
                        $this->displayMessage("<li>$oldName still exists in $tableName. Data will be migrated to $newName and old column $oldName will be dropped.</li>");
                        // Migrate data
                        DB::query("UPDATE $tableName SET $newName = $oldName WHERE $newName IS NULL");
                        // Remove column
                        DB::query("ALTER TABLE $tableName DROP COLUMN $oldName");
                    } else {
                        $this->displayMessage("<li>Renaming $oldName to $newName in $tableName</li>");
                        $dbSchema->renameField($tableName, $oldName, $newName);
                    }
                } else {
                    $this->displayMessage("<li>$oldName does not exist anymore in $tableName</li>");
                }

                // Look for fluent
                $fluentTable = $tableName . '_Localised';
                if ($dbSchema->hasTable($fluentTable)) {
                    if ($dbSchema->hasField($fluentTable, $oldName)) {
                        if ($dbSchema->hasField($fluentTable, $newName)) {
                            $this->displayMessage("<li>$oldName still exists in $fluentTable. Data will be migrated to $newName and old column $oldName will be dropped.</li>");
                            // Migrate data
                            DB::query("UPDATE $fluentTable SET $newName = $oldName WHERE $newName IS NULL");
                            // Remove column
                            DB::query("ALTER TABLE $fluentTable DROP COLUMN $oldName");
                        } else {
                            $this->displayMessage("<li>Renaming $oldName to $newName in $fluentTable</li>");
                            $dbSchema->renameField($fluentTable, $oldName, $newName);
                        }
                    } else {
                        $this->displayMessage("<li>$oldName does not exist anymore in $fluentTable</li>");
                    }
                }
            }
        }
    }

    public function afterCallActionHandler(): void
    {
        // Other helpers
        $clearCache = $this->owner->getRequest()->getVar('clearCache');
        $clearEmptyFolders = $this->owner->getRequest()->getVar('clearEmptyFolders');

        $this->displayMessage("<div class='build'>");
        if ($clearCache) {
            $this->clearCache();
        }
        if ($clearEmptyFolders) {
            $this->clearEmptyFolders();
        }
        $this->displayMessage("</div>");

        // Restore subsite
        if ($this->currentSubsite) {
            SubsiteHelper::changeSubsite($this->currentSubsite);
        }

        $provisionLocales = $this->owner->getRequest()->getVar('provisionLocales');
        if ($provisionLocales) {
            $this->displayMessage("<div class='build'><p><b>Provisioning locales</b></p><ul>\n\n");
            try {
                \LeKoala\Multilingual\LangHelper::provisionLocales();
                $this->displayMessage("</ul>\n<p><b>Locales provisioned!</b></p></div>");
            } catch (Exception $ex) {
                $this->displayMessage($ex->getMessage() . '<br/>');
            }
        }
    }

    protected function clearCache(): void
    {
        $this->displayMessage("<strong>Clearing cache folder</strong>");
        $folder = Director::baseFolder() . '/silverstripe-cache';
        if (!is_dir($folder)) {
            $this->displayMessage("silverstripe-cache does not exist in base folder\n");
            return;
        }
        FileHelper::rmDir($folder);
        mkdir($folder, 0755);
        $this->displayMessage("Cleared silverstripe-cache folder\n");
    }

    protected function clearEmptyFolders(): void
    {
        $this->displayMessage("<strong>Clearing empty folders in assets</strong>");
        $folder = Director::publicFolder() . '/assets';
        if (!is_dir($folder)) {
            $this->displayMessage("assets folder does not exist in public folder\n");
            return;
        }

        $objects = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($folder), RecursiveIteratorIterator::SELF_FIRST);
        foreach ($objects as $name => $object) {
            if ($object->isDir()) {
                $path = $object->getPath();
                if (!is_readable($path)) {
                    $this->displayMessage("$path is not readable\n");
                    continue;
                }
                if (!FileHelper::dirContainsChildren($path)) {
                    rmdir($path);
                    $this->displayMessage("Removed $path\n");
                }
            }
        }
    }

    /**
     * @return array<string>
     */
    protected function getDataObjects()
    {
        $classes = ClassInfo::subclassesFor(DataObject::class);
        array_shift($classes); // remove dataobject
        return $classes;
    }

    /**
     * @param string $message
     */
    protected function displayMessage($message): void
    {
        echo Director::is_cli() ? strip_tags($message) : nl2br($message);
    }
}
