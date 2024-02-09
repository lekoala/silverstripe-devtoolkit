<?php

namespace LeKoala\DevToolkit\Helpers;

use Exception;
use SilverStripe\ORM\DB;
use SilverStripe\ORM\DataList;
use SilverStripe\Core\ClassInfo;
use SilverStripe\ORM\DataObject;
use SilverStripe\Security\Member;
use SilverStripe\Core\Config\Config;
use SilverStripe\Subsites\Model\Subsite;

/**
 * Merge members
 *
 * @author Koala
 */
class DuplicateMembersMerger
{

    /**
     * @param DataList $records
     * @return void
     */
    public static function merge($records)
    {
        $all            = array();
        $all_but_oldest = array();
        $all_but_latest = array();

        $latest = null;
        $oldest = null;
        foreach ($records as $r) {
            if (!is_object($r)) {
                $r = (object) $r;
            }
            if (!$r instanceof Member) {
                $r = Member::get()->byID($r->ID);
            }
            if (!$latest) {
                $latest = $r;
            } else {
                if (strtotime($r->LastEdited) > strtotime($latest->LastEdited)) {
                    $latest = $r;
                }
            }
            if (!$oldest) {
                $oldest = $r;
            } else {
                if ($r->ID < $oldest->ID) {
                    $oldest = $r->ID;
                }
            }

            $all[] = $r;
        }

        foreach ($all as $a) {
            if ($a->ID == $oldest->ID) {
                continue;
            }
            $all_but_oldest[] = $a;
        }

        foreach ($all as $a) {
            if ($a->ID == $latest->ID) {
                continue;
            }
            $all_but_latest[] = $a;
        }

        if (class_exists('Subsite')) {
            Subsite::$disable_subsite_filter = true;
        }

        Config::modify()->set(DataObject::class, 'validation_enabled', false);

        // Rewrite all relations so everything is pointing to oldest
        // For some reason, the code in merge fails to do this properly
        $tables  = DataObject::getSchema()->getTableNames();
        $objects = ClassInfo::subclassesFor('DataObject');
        foreach ($objects as $o) {
            $config = $o::config();
            if ($config->has_one) {
                foreach ($config->has_one as $name => $class) {
                    if ($class == 'Member') {
                        $table = ClassInfo::table_for_object_field(
                            $o,
                            $name . 'ID'
                        );
                        if ($table && in_array(strtolower($table), $tables)) {
                            foreach ($all_but_oldest as $a) {
                                $sql = "UPDATE $table SET " . $name . 'ID = ' . $oldest->ID . ' WHERE ' . $name . 'ID = ' . $a->ID;
                                DB::alteration_message($sql);
                                DB::query($sql);
                            }
                        }
                    }
                }
            }
            if ($config->has_many) {
                foreach ($config->has_many as $name => $class) {
                    if ($class == 'Member') {
                        $table = ClassInfo::table_for_object_field(
                            $o,
                            $name . 'ID'
                        );
                        if ($table && in_array(strtolower($table), $tables)) {
                            foreach ($all_but_oldest as $a) {
                                $sql = "UPDATE $table SET " . $name . 'ID = ' . $oldest->ID . ' WHERE ' . $name . 'ID = ' . $a->ID;
                                DB::alteration_message($sql);
                                DB::query($sql);
                            }
                        }
                    }
                }
            }
            if ($config->many_many) {
                foreach ($config->many_many as $name => $class) {
                    if ($class == 'Member') {
                        $table = ClassInfo::table_for_object_field(
                            $o,
                            $name . 'ID'
                        );
                        if ($table && in_array(strtolower($table), $tables)) {
                            foreach ($all_but_oldest as $a) {
                                $sql = "UPDATE $table SET " . $name . 'ID = ' . $oldest->ID . ' WHERE ' . $name . 'ID = ' . $a->ID;
                                DB::alteration_message($sql);
                                DB::query($sql);
                            }
                        }
                    }
                }
            }
        }

        // Now, we update to oldest record with the latest info

        $orgOldest = $oldest;
        $oldest->merge($latest, 'right', false);
        foreach ($all_but_oldest as $a) {
            $a->delete();
        }

        try {
            $oldest->write();
        } catch (Exception $ex) {
            $orgOldest->write();
        }
    }
}
