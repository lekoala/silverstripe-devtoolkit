<?php

use Ifsnop\Mysqldump as IMysqldump;

/**
 * Description of DumpDbTask
 *
 * @author Koala
 */
class DumpDbTask extends BuildTask
{
    protected $title       = "Dump Database";
    protected $description = 'Easily backup your database before any drastic change...';

    /**
     * @param SS_HTTPRequest $request
     */
    public function run($request)
    {
        increase_time_limit_to();

        DB::alteration_message("Pass ?folder=myfolder to specify the output folder. By default, it's going to be one level outside the web root to protected your dumps.");;

        $host     = SS_DATABASE_SERVER;
        $dbname   = SS_DATABASE_NAME;
        $username = SS_DATABASE_USERNAME;
        $password = SS_DATABASE_PASSWORD;

        $folder = dirname(Director::baseFolder());
        if ($request->getVar('folder')) {
            $folder = Director::baseFolder().'/'.trim($folder, '/');
        }

        $filename = date('Ymd').'-'.$dbname.'.sql';

        try {
            $dumpSettings = array();

            $dump = new IMysqldump\Mysqldump('mysql:host='.$host.';dbname='.$dbname,
                $username, $password, $dumpSettings);
            $dump->start($folder.'/'.$filename);
            DB::alteration_message($folder.'/'.$filename . ' has been created successfully', 'created');
        } catch (\Exception $e) {
            DB::alteration_message('mysqldump-php error: '.$e->getMessage(),
                'error');
        }
    }
}