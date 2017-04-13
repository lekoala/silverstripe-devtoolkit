<?php

/**
 * DropUnusedDatabaseObjectsTask
 *
 * SilverStripe never delete your tables or fields. Be careful if your database has other tables than SilverStripe!
 *
 * @author lekoala
 */
class DropUnusedDatabaseObjectsTask extends BuildTask
{

    protected $title = "Drop Unused Database Objects";
    protected $description = 'Drop unused tables and fields from your db by comparing current database tables with your dataobjects.';

    public function run($request)
    {
        HTTP::set_cache_age(0);
        increase_time_limit_to(); // This can be a time consuming task

        $tables = $request->getVar('tables') ?: true;
        $fields = $request->getVar('fields') ?: true;
        $go = $request->getVar('go');
        if ($tables) {
            echo('Will delete all unused tables. Pass ?tables=false to target only fields.<br/>');
        }
        if ($fields) {
            echo('Will delete all unused fields. Pass ?fields=false to target only tables.<br/>');
        }
        if (!$go) {
            echo('Previewing with this task is about to do. Set ?go=1 to really delete the fields and tables');
        } else {
            echo("Let's clean this up!");
        }
        echo('<hr/>');
        $this->removeTables($request);
        $this->removeFields($request);
    }

    protected function removeFields($request)
    {
        $classes = ClassInfo::dataClassesFor('DataObject');
        $conn = DB::getConn();

        $go = $request->getVar('go');

        foreach ($classes as $class) {
            $hasTable = ClassInfo::hasTable($class);
            if (!$hasTable) {
                continue;
            }

            $toDrop = [];

            $fields = $class::database_fields($class);
            $list = $conn->fieldList($class);

            foreach ($list as $fieldName => $type) {
                if ($fieldName == 'ID') {
                    continue;
                }
                if (!isset($fields[$fieldName])) {
                    $toDrop[] = $fieldName;
                }
            }

            if (empty($toDrop)) {
                continue;
            }

            if ($go) {
                $this->dropColumns($class, $toDrop);
                DB::alteration_message("Dropped " . implode(',', $toDrop) . " for $class", "obsolete");
            } else {
                DB::alteration_message("Would drop " . implode(',', $toDrop) . " for $class", "obsolete");
            }
        }
    }

    protected function removeTables($request)
    {
        $conn = DB::getConn();
        $classes = ClassInfo::subclassesFor('DataObject');
        $dbTables = $conn->tableList();

        $go = $request->getVar('go');

        //make all lowercase
        $dbTablesLc = array_map('strtolower', $dbTables);
        $dbTablesMap = [];
        foreach ($dbTables as $k => $v) {
            $dbTablesMap[strtolower($v)] = $v;
        }

        foreach ($classes as $class) {
            if (ClassInfo::hasTable($class)) {
                $lcClass = strtolower($class);
                self::removeFromArray($lcClass, $dbTablesLc);
                //page modules
                self::removeFromArray($lcClass . '_live', $dbTablesLc);
                self::removeFromArray($lcClass . '_versions', $dbTablesLc);
                //relations
                $hasMany = Config::inst()->get($class, 'has_many');
                $manyMany = Config::inst()->get($class, 'many_many');
                if (!empty($hasMany)) {
                    foreach ($hasMany as $rel => $obj) {
                        self::removeFromArray($lcClass . '_' . strtolower($rel), $dbTablesLc);
                    }
                }
                if (!empty($manyMany)) {
                    foreach ($manyMany as $rel => $obj) {
                        self::removeFromArray($lcClass . '_' . strtolower($rel), $dbTablesLc);
                    }
                }
            }
        }

        //at this point, we should only have orphans table in dbTables var
        foreach ($dbTablesLc as $i => $lcTable) {
            $table = $dbTablesMap[$lcTable];
            if ($go) {
                DB::query('DROP TABLE `' . $table . '`');
                DB::alteration_message("Dropped $table", 'obsolete');
            } else {
                DB::alteration_message("Would drop $table", 'obsolete');
            }
        }
    }

    public static function removeFromArray($val, &$arr)
    {
        if (($key = array_search($val, $arr)) !== false) {
            unset($arr[$key]);
        }
    }

    public function dropColumns($table, $columns)
    {
        switch (get_class(DB::getConn())) {
            case 'SQLite3Database':
                $this->sqlLiteDropColumns($table, $columns);
                break;
            default:
                $this->sqlDropColumns($table, $columns);
                break;
        }
    }

    public function sqlDropColumns($table, $columns)
    {
        DB::query("ALTER TABLE \"$table\" DROP \"" . implode('", DROP "', $columns) . "\"");
    }

    public function sqlLiteDropColumns($table, $columns)
    {
        $newColsSpec = $newCols = [];
        foreach (DB::getConn()->fieldList($table) as $name => $spec) {
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
