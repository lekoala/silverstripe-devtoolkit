<?php

/**
 * DropUnusedFieldsTask
 *
 * Silverstripe never delete a field, so your tables become messy
 * 
 * @author lekoala
 */
class DropUnusedFieldsTask extends BuildTask
{
    protected $title       = "Drop Unused Fields";
    protected $description = 'Drop unused fields from your tables. At each run, remove all obsolete and rename fields to obsolete. Run twice to clean all fields.';

    public function run($request)
    {
        increase_time_limit_to();

        $classes = ClassInfo::dataClassesFor('DataObject');
        $conn    = DB::getConn();

        foreach ($classes as $class) {
            $fields = $class::database_fields($class);
            $list   = $conn->fieldList($class);

            foreach ($list as $fieldName => $type) {
                if ($fieldName == 'ID') {
                    continue;
                }
                if (strpos($fieldName, '_obsolete_') === 0) {
                    $this->dropColumns($class, array($fieldName));
                    DB::alteration_message("Dropped $fieldName for $class", "obsolete");
                    continue;
                }
                if (!isset($fields[$fieldName])) {
                    $conn->dontRequireField($class, $fieldName);
                }
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
