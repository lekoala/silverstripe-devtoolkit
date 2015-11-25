<?php

/**
 * Description of RemoveSpacesFromFilenames
 *
 * @author LeKoala <thomas@lekoala.be>
 */
class RemoveSpacesFromFilenames extends BuildTask
{
    protected $title = "Remove spaces from files in /assets";

    public function run($request)
    {
        $filesWithSpaces = File::get()->where('"Filename" LIKE \'% %\'');

        $filter = new FileNameFilter;

        foreach ($filesWithSpaces as $file) {
            DB::alteration_message("Updating file #".$file->ID." with filename ".$file->Filename);
            $file->Filename = $filter->filter($file->Filename);
            $file->write();
        }
        DB::alteration_message("All done!");
    }
}