<?php

/**
 * Description of DropOldVersionsTask
 *
 * @author Koala
 */
class DropOldVersionsTask extends BuildTask
{
    protected $description = "automatically delete old published & draft versions from all classes extending the SiteTree (like Page)";

    public function run($request)
    {
        $db = DB::tableList();

        foreach (self::GetVersionedClass() as $class) {
            $table  = ClassInfo::table_for_object_field($class, 'ID');
            $vTable = $table.'_versions';
            if (!in_array($vTable, $db)) {
                continue;
            }

            echo "Clear records for class $class <br/>";

            // TODO: WE SHOULD CLEAR BY ID TO AVOID INCONSISTENT DB
            
            // Keep 50 last records
            DB::query("DELETE FROM $vTable
  WHERE id <= (
    SELECT id
    FROM (
      SELECT id
      FROM $vTable
      ORDER BY id DESC
      LIMIT 1 OFFSET 50
    ) selection
  )");
            $this->vacuumTable($vTable);
        }
    }

    static function GetVersionedClass()
    {
        $classes = array();
        foreach (ClassInfo::subClassesFor('DataObject') as $class) {
            if (Object::has_extension($class, 'Versioned')) {
                $classes[] = $class;
            }
        }
        return $classes;
    }

    /**
     * Optimize the table
     * @param string
     * @return null
     */
    protected function vacuumTable($table)
    {
        global $databaseConfig;
        if (preg_match('/mysql/i', $databaseConfig['type'])) {
            DB::query('OPTIMIZE table "'.$table.'"');
        } elseif (preg_match('/postgres/i', $databaseConfig['type'])) {
            DB::query('VACUUM "'.$table.'"');
        }
        /* Sqlite just optimizes the database, not each table */
        if (preg_match('/sqlite/i', $databaseConfig['type'])) {
            DB::query('VACUUM');
        }
    }
}