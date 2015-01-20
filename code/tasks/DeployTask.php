<?php

/**
 * DeployTask
 *
 * This task suppose that you run apache as your main user (if you have access, apache will have access)
 *
 * @link https://coderwall.com/p/moabdw/using-rsync-to-deploy-a-website-easy-one-liner-command
 * @author lekoala
 */
class DeployTask extends BuildTask
{
    protected $title       = "Deploy your app";
    protected $description = 'Deploy your app to a given server';

    public function run($request)
    {
        $target = $request->getVar('target');
        $go     = $request->getVar('go') ? true : false;
        if (!$target) {
            $this->out('You must pass a target, either ?target=live or ?target=staging');
            exit();
        }
        if (!is_file(Director::baseFolder().'/rsync_exclude.txt')) {
            $this->out('You must specify an exclusion list at the root of your folder. See sample file in /ressources');
            exit();
        }

        if ($target == 'staging') {
            $port   = DEPLOY_STAGING_PORT;
            $sshtarget = DEPLOY_STAGING_TARGET;
        } else if ($target == 'live') {
            $port   = DEPLOY_LIVE_PORT;
            $sshtarget = DEPLOY_LIVE_TARGET;
        } else {
            $this->out('Invalid target');
            exit();
        }

        if ($go) {
            $this->out('Executing actual deploy on '.$target);
        } else {
            $this->out('Executing dry run on '.$target);
        }

        $this->out('Running script as '.exec('whoami'));


        $output = '';
        chdir(Director::baseFolder());
        $script = 'rsync --dry-run -az --force --delete --progress --exclude-from=rsync_exclude.txt -e "ssh -p'.$port.'" ./ '.$sshtarget.' 2>&1';
        $this->out($script);
        exec($script, $output);
        foreach ($output as $line) {
            $this->out($line);
        }
        return $output;
    }

    public function out($message)
    {
        echo $message."<br/>";
    }
}