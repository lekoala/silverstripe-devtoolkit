<?php

/**
 * @author LeKoala <thomas@lekoala.be>
 */
class RemoveEmptyGroupsTask extends BuildTask
{
    protected $title = "Remove empty/duplicate groups from the cms";

    public function run($request)
    {
        $groups = Group::get();
        echo 'Pass ?drop=1 to drop groups without members<br/>';
        echo 'Want more dropping? Pass ?permission=1 to also drop groups without permissions even if they have members<br/>';
        echo 'Pass ?merge=1 to merge groups with the same code<br/>';
        echo 'Want to merge across subsites ? Pass ?subsite=1 to disable subsite filters<br/>';

        echo '<hr/>';
        $merge            = $request->getVar('merge');
        $drop             = $request->getVar('drop');
        $dropNoPermission = $request->getVar('permission');
        $subsite          = $request->getVar('subsite');

        if (class_exists('Subsite') && $subsite) {
            Subsite::$disable_subsite_filter = true;
        }

        if ($drop) {
            DB::alteration_message("Dropping groups with no members");
            if ($dropNoPermission) {
                DB::alteration_message("Also dropping groups with no permissions");
            }
            foreach ($groups as $group) {
                if (!$group->Members()->count()) {
                    DB::alteration_message("Removing group  {$group->ID} because it has no members",
                        "deleted");
                    $group->delete();
                }
                if ($dropNoPermission) {
                    $c = $group->Permissions()->count();
                    if (!$c) {
                        DB::alteration_message("Removing group {$group->ID} because it has no permissions",
                            "deleted");
                        $group->delete();
                    }
                }
            }
        }
        if ($merge) {
            DB::alteration_message("Merging groups with duplicated codes");
            $index = array();

            /* @var $group Group */
            foreach ($groups as $group) {
                DB::alteration_message("Found group ".$group->Code);
                if (!isset($index[$group->Code])) {
                    $index[$group->Code] = $group;
                    DB::alteration_message("First instance of group, do not merge");
                    continue;
                }

                $mergeGroup = $index[$group->Code];


                DB::alteration_message('Merge group '.$group->ID.' with '.$mergeGroup->ID,
                    'repaired');

                $i = 0;
                foreach ($group->Members() as $m) {
                    $i++;
                    $mergeGroup->Members()->add($m);
                }
                DB::alteration_message('Added '.$i.' members to group',
                    'created');

                DB::alteration_message("Group ".$group->ID.' was deleted',
                    'deleted');
                $group->delete();
            }
        }

        DB::alteration_message('All done!');
    }
}
