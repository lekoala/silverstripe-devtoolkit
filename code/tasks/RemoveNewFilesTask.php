<?php

/**
 * Description of RemoveNewFilesTask
 *
 * @author Koala
 */
class RemoveNewFilesTask extends BuildTask
{

    public function run($request)
    {
        DB::query("DELETE FROM File WHERE Title = 'new file'");
        DB::alteration_message("Done");
    }
}