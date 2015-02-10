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

        $user  = 'apache';
        $group = 'apache';

        if ($target == 'staging') {
            if (!defined('DEPLOY_STAGING_PORT') || !defined('DEPLOY_STAGING_TARGET')) {
                $this->out('Missing constant. Please define DEPLOY_STAGING_PORT and DEPLOY_STAGING_TARGET in your _ss_environment');
                exit();
            }
            $port      = DEPLOY_STAGING_PORT;
            $sshtarget = DEPLOY_STAGING_TARGET;
            if (defined('DEPLOY_STAGING_USER')) {
                $user = DEPLOY_STAGING_USER;
            }
            if (defined('DEPLOY_STAGING_GROUP')) {
                $group = DEPLOY_STAGING_GROUP;
            }
        } else if ($target == 'live') {
            if (!defined('DEPLOY_LIVE_PORT') || !defined('DEPLOY_LIVE_TARGET')) {
                $this->out('Missing constant. Please define DEPLOY_LIVE_PORT and DEPLOY_LIVE_TARGET in your _ss_environment');
                exit();
            }
            $port      = DEPLOY_LIVE_PORT;
            $sshtarget = DEPLOY_LIVE_TARGET;
            if (defined('DEPLOY_LIVE_USER')) {
                $user = DEPLOY_LIVE_USER;
            }
            if (defined('DEPLOY_LIVE_GROUP')) {
                $group = DEPLOY_LIVE_GROUP;
            }
        } else {
            $this->out('Invalid target');
            exit();
        }

        $hosts = shell_exec('cat ~/.ssh/known_hosts');
        if (!strlen($hosts)) {
            $this->out('No known hosts to deploy to');
            exit();
        }

        $dry = '';
        if ($go) {
            $this->out('Building actual deploy on '.$target);
        } else {
            $dry = ' --dry-run';
            $this->out('Building dry run on '.$target.'. Pass &go=1 to build actual run script.');
        }

        $current_user = exec('whoami');
        $this->out('Running script as '.$current_user);

        $verbosessh = '';

        $target_parts  = explode(':', $sshtarget);
        $address       = $target_parts[0];
        $address_parts = explode('@', $address);
        $domain        = array_pop($address_parts);
        $folder        = $target_parts[1];

        $script       = 'rsync'.$dry.' -az --force --delete --progress --exclude-from=rsync_exclude.txt -e "ssh'.$verbosessh.' -p'.$port.'" ./ '.$sshtarget;
        $script_log   = $script.' > deploy.log';
        $chown_script = 'ssh -p'.$port.' '.$domain." 'chown -R $user:$group ".$folder."'";

        $this->out('Run the command line below from the terminal:');
        $this->out($script_log);

        $this->out('You might need to change the file owner. If so, also run:');
        $this->out($chown_script);
    }

    public function out($message)
    {
        echo nl2br($message)."<br/>";
    }
}