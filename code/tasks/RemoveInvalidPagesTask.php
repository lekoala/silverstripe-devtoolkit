<?php

/**
 * Description of RemoveInvalidPagesTask
 *
 * @author LeKoala <thomas@lekoala.be>
 */
class RemoveInvalidPagesTask extends BuildTask
{
    protected $title = "Remove invalid pages from the database";

    public function run($request)
    {
        $classes = ClassInfo::subclassesFor('SiteTree');

        $classes = array_map(function($item) {
            return "'$item'";
        }, $classes);

        DB::query('DELETE FROM "SiteTree" WHERE "ClassName" NOT IN ('.implode(',',
                $classes).')');
        DB::query('DELETE FROM "SiteTree_Live" WHERE "ClassName" NOT IN ('.implode(',',
                $classes).')');
        DB::query('DELETE FROM "SiteTree_Versions" WHERE "ClassName" NOT IN ('.implode(',',
                $classes).')');
        DB::alteration_message('All done!');
    }
}