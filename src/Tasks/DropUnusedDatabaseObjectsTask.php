<?php

namespace LeKoala\DevToolkit\Tasks;

use Exception;
use SilverStripe\ORM\DB;
use SilverStripe\Dev\BuildTask;
use SilverStripe\Core\ClassInfo;
use SilverStripe\ORM\DataObject;
use SilverStripe\Versioned\Versioned;
use LeKoala\DevToolkit\BuildTaskTools;
use SilverStripe\Core\Environment;
use SilverStripe\Control\HTTPRequest;

/**
 * SilverStripe never delete your tables or fields. Be careful if your database has other tables than SilverStripe!
 *
 * @author lekoala
 */
class DropUnusedDatabaseObjectsTask extends BuildTask
{
    use BuildTaskTools;

    /**
     * @var string
     */
    protected $title = "Drop unused database objects";

    /**
     * @var string
     */
    protected $description = 'Drop unused tables and fields from your db by comparing current database tables with your dataobjects.';

    /**
     * @var string
     */
    private static $segment = 'DropUnusedDatabaseObjectsTask';

    /**
     * @param HTTPRequest $request
     * @return void
     */
    public function run($request)
    {
        // This can be very long
        Environment::setTimeLimitMax(0);

        $this->request = $request;

        $this->addOption("tables", "Clean unused tables", true);
        $this->addOption("fields", "Clean unused fields", true);
        $this->addOption("reorder", "Reorder fields", false);
        $this->addOption("go", "Tick this to proceed", false);

        $options = $this->askOptions();

        $tables = $options['tables'];
        $fields = $options['fields'];
        $reorder = $options['reorder'];
        $go = $options['go'];

        if (!$go) {
            echo ('Previewing what this task is about to do.');
        } else {
            echo ("Let's clean this up!");
        }
        echo ('<hr/>');
        if ($tables) {
            $this->removeTables($request, $go);
        }
        if ($fields) {
            $this->removeFields($request, $go);
        }
        if ($reorder) {
            $this->reorderFields($request, $go);
        }
    }

    /**
     * @param HTTPRequest $request
     * @param bool $go
     * @return void
     */
    protected function reorderFields($request, $go = false)
    {
        $conn = DB::get_conn();
        $schema = DB::get_schema();
        $dataObjectSchema = DataObject::getSchema();
        $classes = $this->getClassesWithTables();
        $tableList = $schema->tableList();

        $this->message('<h2>Fields order</h2>');

        foreach ($classes as $class) {
            /** @var \SilverStripe\ORM\DataObject $singl */
            $singl = $class::singleton();
            $baseClass = $singl->baseClass();
            $table = $dataObjectSchema->tableName($class);
            $lcTable = strtolower($table);

            // It does not exist in the list, no need to worry about
            if (!isset($tableList[$lcTable])) {
                continue;
            }

            $fields = $dataObjectSchema->databaseFields($class);
            $baseFields = $dataObjectSchema->databaseFields($baseClass);

            $realFields = $fields;
            if ($baseClass != $class) {
                foreach ($baseFields as $k => $v) {
                    if ($k == "ID") {
                        continue;
                    }
                    unset($realFields[$k]);
                }

                // When extending multiple classes it's a mess to track, eg SubsitesVirtualPage
                if (isset($realFields['VersionID'])) {
                    unset($realFields['VersionID']);
                }
            }

            // We must pass the regular table name
            $list = $schema->fieldList($table);

            $fields_keys = array_keys($realFields);
            $list_keys = array_keys($list);

            if (json_encode($fields_keys) == json_encode($list_keys)) {
                continue;
            }

            $fieldsThatNeedToMove = [];
            foreach ($fields_keys as $k => $v) {
                if (!isset($list_keys[$k])) {
                    continue; // not sure why
                }
                if ($list_keys[$k] != $v) {
                    $fieldsThatNeedToMove[] = $v;
                }
            }

            if ($go) {
                $this->message("$table: moving " . implode(", ", $fieldsThatNeedToMove));

                // $conn->transactionStart();
                // fields contains the right order (the one from the codebase)
                $after = "first";
                foreach ($fields_keys as $k => $v) {
                    if (isset($list_keys[$k]) && $list_keys[$k] != $v) {
                        $col = $v;
                        $def = $list[$v] ?? null;
                        if (!$def) {
                            // This happens when extending another model
                            $this->message("Ignore $v that has no definition", "error");
                            continue;
                        }
                        // you CANNOT combine multiple columns reordering in a single ALTER TABLE statement.
                        $sql = "ALTER TABLE `$table` MODIFY `$col` $def $after";
                        $this->message($sql);
                        try {
                            $conn->query($sql);
                        } catch (Exception $e) {
                            $this->message($e->getMessage(), "error");
                        }
                    }
                    $after = "after $v";
                }
                // $conn->transactionEnd();
            } else {
                $this->message("$table: would move " . implode(", ", $fieldsThatNeedToMove));
            }
        }
    }

    /**
     * @param HTTPRequest $request
     * @param bool $go
     * @return void
     */
    protected function removeFields($request, $go = false)
    {
        $conn = DB::get_conn();
        $schema = DB::get_schema();
        $dataObjectSchema = DataObject::getSchema();
        $classes = $this->getClassesWithTables();
        $tableList = $schema->tableList();

        $this->message('<h2>Fields</h2>');

        $empty = true;

        $processedTables = [];
        foreach ($classes as $class) {
            /** @var \SilverStripe\ORM\DataObject $singl */
            $singl = $class::singleton();
            $baseClass = $singl->baseClass();
            $table = $dataObjectSchema->tableName($baseClass);
            $lcTable = strtolower($table);

            if (in_array($table, $processedTables)) {
                continue;
            }
            // It does not exist in the list, no need to worry about
            if (!isset($tableList[$lcTable])) {
                continue;
            }
            $processedTables[] = $table;
            $toDrop = [];

            $fields = $dataObjectSchema->databaseFields($class);
            // We must pass the regular table name
            $list = $schema->fieldList($table);
            // We can compare DataObject schema with actual schema
            foreach ($list as $fieldName => $type) {
                /// Never drop ID
                if ($fieldName == 'ID') {
                    continue;
                }
                if (!isset($fields[$fieldName])) {
                    $toDrop[] = $fieldName;
                }
            }

            if (!empty($toDrop)) {
                $empty = false;
                if ($go) {
                    $this->dropColumns($table, $toDrop);
                    $this->message("Dropped " . implode(',', $toDrop) . " for $table", "obsolete");
                } else {
                    $this->message("Would drop " . implode(',', $toDrop) . " for $table", "obsolete");
                }
            }

            // Many many support if has own base table
            $many_many = $singl::config()->many_many;
            foreach ($many_many as $manyName => $manyClass) {
                $toDrop = [];

                // No polymorphism support
                if (is_array($manyClass)) {
                    continue;
                }

                // This is very naive and only works in basic cases
                $manyTable = $table . '_' . $manyName;
                if (!$schema->hasTable($manyTable)) {
                    continue;
                }
                $baseManyTable = $dataObjectSchema->tableName($manyClass);
                $list = $schema->fieldList($manyTable);
                $props = $singl::config()->many_many_extraFields[$manyName] ?? [];
                if (empty($props)) {
                    continue;
                }

                // We might miss some!
                $validNames = array_merge([
                    'ID', $baseManyTable . 'ID', $table . 'ID', $table . 'ID', 'ChildID', 'SubsiteID',
                ], array_keys($props));
                foreach ($list as $fieldName => $fieldDef) {
                    if (!in_array($fieldName, $validNames)) {
                        $toDrop[] = $fieldName;
                    }
                }

                if (!empty($toDrop)) {
                    $empty = false;
                    if ($go) {
                        $this->dropColumns($manyTable, $toDrop);
                        $this->message("Dropped " . implode(',', $toDrop) . " for $manyTable ($table)", "obsolete");
                    } else {
                        $this->message("Would drop " . implode(',', $toDrop) . " for $manyTable ($table)", "obsolete");
                    }
                }
            }

            // Localised fields support
            if ($singl->hasExtension("\\TractorCow\\Fluent\\Extension\\FluentExtension")) {
                $toDrop = [];
                $localeTable = $table . '_Localised';
                //@phpstan-ignore-next-line
                $localeFields = $singl->getLocalisedFields($baseClass);
                $localeList = $schema->fieldList($localeTable);
                foreach ($localeList as $fieldName => $type) {
                    /// Never drop locale fields
                    if (in_array($fieldName, ['ID', 'RecordID', 'Locale'])) {
                        continue;
                    }
                    if (!isset($localeFields[$fieldName])) {
                        $toDrop[] = $fieldName;
                    }
                }
                if (!empty($toDrop)) {
                    $empty = false;
                    if ($go) {
                        $this->dropColumns($localeTable, $toDrop);
                        $this->message("Dropped " . implode(',', $toDrop) . " for $localeTable", "obsolete");
                    } else {
                        $this->message("Would drop " . implode(',', $toDrop) . " for $localeTable", "obsolete");
                    }
                }
            }
        }

        if ($empty) {
            $this->message("No fields to remove", "repaired");
        }
    }

    /**
     * @param HTTPRequest $request
     * @param bool $go
     * @return void
     */
    protected function removeTables($request, $go = false)
    {
        $conn = DB::get_conn();
        $schema = DB::get_schema();
        $dataObjectSchema = DataObject::getSchema();
        $classes = $this->getClassesWithTables();
        $allDataObjects = array_values($this->getValidDataObjects());
        $tableList = $schema->tableList();
        $tablesToRemove = $tableList;

        $this->message('<h2>Tables</h2>');

        foreach ($classes as $class) {
            /** @var \SilverStripe\ORM\DataObject $singl */
            $singl = $class::singleton();
            $table = $dataObjectSchema->tableName($class);
            $lcTable = strtolower($table);

            // It does not exist in the list, keep to remove later
            if (!isset($tableList[$lcTable])) {
                continue;
            }

            self::removeFromArray($lcTable, $tablesToRemove);
            // Remove from the list versioned tables
            if ($singl->hasExtension(Versioned::class)) {
                self::removeFromArray($lcTable . '_live', $tablesToRemove);
                self::removeFromArray($lcTable . '_versions', $tablesToRemove);
            }
            // Remove from the list fluent tables
            if ($singl->hasExtension("\\TractorCow\\Fluent\\Extension\\FluentExtension")) {
                self::removeFromArray($lcTable . '_localised', $tablesToRemove);
                self::removeFromArray($lcTable . '_localised_live', $tablesToRemove);
                self::removeFromArray($lcTable . '_localised_versions', $tablesToRemove);
            }

            // Relations
            $hasMany = $class::config()->has_many;
            if (!empty($hasMany)) {
                foreach ($hasMany as $rel => $obj) {
                    self::removeFromArray($lcTable . '_' . strtolower($rel), $tablesToRemove);
                }
            }
            // We catch relations without own classes later on
            $manyMany = $class::config()->many_many;
            if (!empty($manyMany)) {
                foreach ($manyMany as $rel => $obj) {
                    self::removeFromArray($lcTable . '_' . strtolower($rel), $tablesToRemove);
                }
            }
        }

        //at this point, we should only have orphans table in dbTables var
        foreach ($tablesToRemove as $lcTable => $table) {
            // Remove many_many tables without own base table
            if (strpos($table, '_') !== false) {
                $parts = explode('_', $table);
                $potentialClass = $parts[0];
                $potentialRelation = $parts[1];
                foreach ($allDataObjects as $dataObjectClass) {
                    $classParts = explode('\\', $dataObjectClass);
                    $tableClass = end($classParts);
                    if ($tableClass == $potentialClass) {
                        $manyManyRelations = $dataObjectClass::config()->many_many;
                        if (isset($manyManyRelations[$potentialRelation])) {
                            unset($tablesToRemove[$lcTable]);
                            continue 2;
                        }
                    }
                }
            }
            if ($go) {
                DB::query('DROP TABLE `' . $table . '`');
                $this->message("Dropped $table", 'obsolete');
            } else {
                $this->message("Would drop $table", 'obsolete');
            }
        }

        if (empty($tablesToRemove)) {
            $this->message("No table to remove", "repaired");
        }
    }

    /**
     * @return array<string>
     */
    protected function getClassesWithTables()
    {
        return ClassInfo::dataClassesFor(DataObject::class);
    }

    /**
     * @param mixed $val
     * @param array<mixed> $arr
     * @return void
     */
    public static function removeFromArray($val, &$arr)
    {
        if (isset($arr[$val])) {
            unset($arr[$val]);
        }
    }

    /**
     * @param string $table
     * @param array<string> $columns
     * @return void
     */
    public function dropColumns($table, $columns)
    {
        switch (get_class(DB::get_conn())) {
            case \SilverStripe\SQLite\SQLite3Database::class:
            case 'SQLite3Database':
                $this->sqlLiteDropColumns($table, $columns);
                break;
            default:
                $this->sqlDropColumns($table, $columns);
                break;
        }
    }

    /**
     * @param string $table
     * @param array<string> $columns
     * @return void
     */
    public function sqlDropColumns($table, $columns)
    {
        DB::query("ALTER TABLE \"$table\" DROP \"" . implode('", DROP "', $columns) . "\"");
    }

    /**
     * @param string $table
     * @param array<string> $columns
     * @return void
     */
    public function sqlLiteDropColumns($table, $columns)
    {
        $newColsSpec = $newCols = [];
        foreach (DataObject::getSchema()->databaseFields($table) as $name => $spec) {
            if (in_array($name, $columns)) {
                continue;
            }
            $newColsSpec[] = "\"$name\" $spec";
            $newCols[] = "\"$name\"";
        }

        $queries = [
            "BEGIN TRANSACTION",
            "CREATE TABLE \"{$table}_cleanup\" (" . implode(',', $newColsSpec) . ")",
            "INSERT INTO \"{$table}_cleanup\" SELECT " . implode(',', $newCols) . " FROM \"$table\"",
            "DROP TABLE \"$table\"",
            "ALTER TABLE \"{$table}_cleanup\" RENAME TO \"{$table}\"",
            "COMMIT"
        ];

        foreach ($queries as $query) {
            DB::query($query . ';');
        }
    }
}
