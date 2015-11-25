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
            $parts    = explode('/', $file->Filename);
            $filtered = array_map(function($item) use($filter) {
                $filter->filter($item);
            }, $parts);
            $file->Filename = implode('/', $parts);
            $file->write();
        }
        DB::alteration_message("All done!");
    }
}