<?php

/**
 * DropUnusedTableTask
 *
 * Silverstripe never delete your tables. Be careful if your database has other tables than Silverstripe!
 * 
 * @author lekoala
 */
class DropUnusedTableTask extends BuildTask
{
    protected $title       = "Drop Unused Tables";
    protected $description = 'Drop unused tables from your db. Warning ! this is not bulletproof but should cover most cases.';

    public function run($request)
    {
        $classes  = ClassInfo::subclassesFor('DataObject');
        $dbTables = DB::query(DB::getConn()->allTablesSQL())->column();

        $go = $request->getVar('go');
        if (!$go) {
            DB::alteration_message('Set ?go=1 to really delete the tables');
        }

        //make all lowercase
        $dbTablesLc = array_map('strtolower', $dbTables);

        foreach ($classes as $class) {
            if (ClassInfo::hasTable($class)) {
                $lcClass  = strtolower($class);
                self::removeFromArray($lcClass, $dbTablesLc);
                //page modules
                self::removeFromArray($lcClass.'_live', $dbTablesLc);
                self::removeFromArray($lcClass.'_versions', $dbTablesLc);
                //relations
                $hasMany  = Config::inst()->get($class, 'has_many');
                $manyMany = Config::inst()->get($class, 'many_many');
                if (!empty($hasMany)) {
                    foreach ($hasMany as $rel => $obj) {
                        self::removeFromArray($lcClass.'_'.strtolower($rel),
                            $dbTablesLc);
                    }
                }
                if (!empty($manyMany)) {
                    foreach ($manyMany as $rel => $obj) {
                        self::removeFromArray($lcClass.'_'.strtolower($rel),
                            $dbTablesLc);
                    }
                }
            }
        }

        //at this point, we should only have orphans table in dbTables var
        foreach ($dbTablesLc as $i => $table) {
            if ($go) {
                DB::query('DROP TABLE `'.$table . '`');
                DB::alteration_message("Dropped $table", 'obsolete');
            } else {
                DB::alteration_message("Would drop $table", 'obsolete');
            }
        }
    }

    static function removeFromArray($val, &$arr)
    {
        if (($key = array_search($val, $arr)) !== false) {
            unset($arr[$key]);
        }
    }
}