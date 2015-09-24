<?php

/**
 * @author LeKoala <thomas@lekoala.be>
 */
class RemoveEmptyGroupsTask extends BuildTask
{
    protected $title       = "Remove empty groups from the cms";

    public function run($request)
    {
        $groups = Group::get();
        foreach($groups as $group) {
            if(!$group->Members()->count()) {
                DB::alteration_message("Removing group {$group->ID}","deleted");
                $group->delete();
            }
        }
        DB::alteration_message('All done!');
    }
}