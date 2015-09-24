<?php

/**
 * @author LeKoala <thomas@lekoala.be>
 */
class RemoveEmptyGroupsTask extends BuildTask
{
    protected $title = "Remove empty groups from the cms";

    public function run($request)
    {
        $groups           = Group::get();
        echo 'Want more dropping? Pass ?permission=1 to also drop groups without permissions even if they have members';
        echo '<hr/>';
        $dropNoPermission = $request->getVar('permission');
        foreach ($groups as $group) {
            if (!$group->Members()->count()) {
                DB::alteration_message("Removing group  {$group->ID} because it has no members", "deleted");
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
        DB::alteration_message('All done!');
    }
}