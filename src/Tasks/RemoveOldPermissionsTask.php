<?php

namespace LeKoala\DevToolkit\Tasks;

use SilverStripe\ORM\DB;
use SilverStripe\Dev\BuildTask;
use SilverStripe\Security\Group;
use LeKoala\DevToolkit\BuildTaskTools;
use SilverStripe\Security\Permission;
use SilverStripe\Subsites\Model\Subsite;

/**
 * @author LeKoala <thomas@lekoala.be>
 */
class RemoveOldPermissionsTask extends BuildTask
{
    use BuildTaskTools;

    protected $title = "Remove 'other' permissions from the cms";
    private static $segment = 'RemoveOldPermissionsTask';

    public function run($request)
    {
        $this->request = $request;

        $permissions = Permission::get_codes(true);

        $other = $permissions['Other'];
        foreach ($other as $k => $infos) {
            DB::prepared_query("DELETE FROM Permission WHERE Code = ?", [$k]);
            DB::alteration_message("Deleting $k");
        }

        DB::alteration_message('All done!');
    }
}
