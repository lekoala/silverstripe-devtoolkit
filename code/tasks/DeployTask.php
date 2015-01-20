<?php

/**
 * DeployTask
 * 
 * @author lekoala
 */
class DeployTask extends BuildTask
{
    protected $title       = "Deploy your app";
    protected $description = 'Deploy your app to a given server';

    public function run($request)
    {
        $target = $request->getVar('target');
        $go = $request->getVar('go') ? true : false;
        if(!$target) {
            return 'You must pass a target, either ?target=live or ?target=staging';
        }
        $output = '';
        exec('rsync --dry-run -az --force --delete --progress --exclude-from=rsync_exclude.txt -e "ssh -p121" ./../../../ rooot@vps128787.ovh.net:/websites/dgsport.eu', $output);

        return $output;
    }
}