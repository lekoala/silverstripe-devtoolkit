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
            if (!defined('DEPLOY_STAGING_PORT') || !defined('DEPLOY_STAGING_TARGET')) {
                $this->out('Missing constant. Please define DEPLOY_STAGING_PORT and DEPLOY_STAGING_TARGET in your _ss_environment');
                exit();
            }
            $port      = DEPLOY_STAGING_PORT;
            $sshtarget = DEPLOY_STAGING_TARGET;
        } else if ($target == 'live') {
            if (!defined('DEPLOY_LIVE_PORT') || !defined('DEPLOY_LIVE_TARGET')) {
                $this->out('Missing constant. Please define DEPLOY_LIVE_PORT and DEPLOY_LIVE_TARGET in your _ss_environment');
                exit();
            }
            $port      = DEPLOY_LIVE_PORT;
            $sshtarget = DEPLOY_LIVE_TARGET;
        } else {
            $this->out('Invalid target');
            exit();
        }

        $hosts = exec('cat ~/.ssh/known_hosts');
        if(!strlen($hosts)) {
            $this->out('No known hosts to deploy to');
            exit();
        }

//        $this->out(shell_exec('ssh-add -l 2>&1'));
        
        $dry = '';
        if ($go) {
            $this->out('Building actual deploy on '.$target);
        } else {
            $dry = ' --dry-run';
            $this->out('Building dry run on '.$target . '. Pass &go=1 to build actual run script.');
        }

        $this->out('Running script as '.exec('whoami'));

        $verbosessh = '';

        $script = 'rsync'.$dry.' -az --force --delete --progress --exclude-from=rsync_exclude.txt -e "ssh'.$verbosessh.' -p'.$port.'" ./ '.$sshtarget.' > deploy.log';
        $this->out($script);
        $this->out('Run the above line in the command line');
        $this->out('You might need to change the file owner. If so, run:');

        $target_parts = explode(':', $sshtarget);

        $chown_script = 'ssh -p' . $port . ' ' . $sshtarget . " 'chown -R apache:apache ".$target_parts[1]."'";
        $this->out($chown_script);
    }

    public function out($message)
    {
        echo $message."<br/>";
    }
}