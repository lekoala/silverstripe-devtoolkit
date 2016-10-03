<?php

/**
 * DropUnusedFieldsTask
 *
 * SilverStripe never delete a field, so your tables become messy
 *
 * @author lekoala
 */
class DropUnusedFieldsTask extends BuildTask
{
    protected $title       = "Drop Unused Fields";
    protected $description = 'Drop unused fields from your tables by comparing current database fields with your dataobjects.';

    public function run($request)
    {
        HTTP::set_cache_age(0);
        increase_time_limit_to(); // This can be a time consuming task

        $classes = ClassInfo::dataClassesFor('DataObject');
        $conn    = DB::getConn();

        $go = $request->getVar('go');
        if (!$go) {
            echo('Set ?go=1 to really delete the fields');
            echo('<hr/>');
        }

        foreach ($classes as $class) {
            $hasTable = ClassInfo::hasTable($class);
            if (!$hasTable) {
                continue;
            }

            $toDrop = array();

            $fields = $class::database_fields($class);
            $list   = $conn->fieldList($class);

            foreach ($list as $fieldName => $type) {
                if ($fieldName == 'ID') {
                    continue;
                }
                if (!isset($fields[$fieldName])) {
                    $toDrop[] = $fieldName;
                }
            }

            if(empty($toDrop)) {
                continue;
            }

            if ($go) {
                $this->dropColumns($class, $toDrop);
                DB::alteration_message("Dropped ".implode(',',$toDrop)." for $class",
                    "obsolete");
            } else {
                DB::alteration_message("Would drop ".implode(',',$toDrop)." for $class",
                    "obsolete");
            }
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
        DB::query("ALTER TABLE \"$table\" DROP \"".implode('", DROP "', $columns)."\"");
    }

    public function sqlLiteDropColumns($table, $columns)
    {
        $newColsSpec = $newCols     = array();
        foreach (DB::getConn()->fieldList($table) as $name => $spec) {
            if (in_array($name, $columns)) {
                continue;
            }
            $newColsSpec[] = "\"$name\" $spec";
            $newCols[]     = "\"$name\"";
        }

        $queries = array(
            "BEGIN TRANSACTION",
            "CREATE TABLE \"{$table}_cleanup\" (".implode(',', $newColsSpec).")",
            "INSERT INTO \"{$table}_cleanup\" SELECT ".implode(',', $newCols)." FROM \"$table\"",
            "DROP TABLE \"$table\"",
            "ALTER TABLE \"{$table}_cleanup\" RENAME TO \"{$table}\"",
            "COMMIT"
        );

        foreach ($queries as $query) {
            DB::query($query.';');
        }
    }
}