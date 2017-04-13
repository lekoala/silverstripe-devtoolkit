<?php

/**
 * DeployController
 *
 * @author Kalyptus SPRL <thomas@kalyptus.be>
 */
class DeployController extends Controller
{

    private static $allowed_actions = array(
        'pre',
        'post',
    );

    protected function auth()
    {
        $key = $this->getRequest()->getVar("key");
        $ip = $_SERVER['REMOTE_ADDR'];

        $whitelist = self::config()->ip_whitelist;

        if ($whitelist) {
            $ipLong = ip2long($ip);
            foreach ($whitelist as $white) {
                $parts = explode('-', $white);
                if (count($parts) == 1) {
                    if ($parts[0] == $ip) {
                        return;
                    }
                }
                if (count($parts) == 2) {
                    $min = ip2long($parts[0]);
                    $max = ip2long($parts[1]);
                    if ($ipLong >= $min && $ipLong <= $max) {
                        return;
                    }
                }
            }
        }
        if ($whitelist && in_array($ip, $whitelist)) {
            return;
        }
        if ($key && $key == self::config()->key) {
            return;
        }
        throw new Exception("Invalid key or ip not whitelisted");
    }

    public function index(SS_HTTPRequest $request)
    {
        $this->auth();
        die('Call either pre or post hooks');
    }

    public function pre(SS_HTTPRequest $request)
    {
        $this->auth();
        $this->extend('onPreDeploy');
    }

    public function post(SS_HTTPRequest $request)
    {
        $this->auth();

        $dataClasses = ClassInfo::subclassesFor('DataObject');
        $lastBuilt = DatabaseAdmin::lastBuilt();
        $manifest = SS_ClassLoader::instance()->getManifest();

        $update = false;
        foreach ($dataClasses as $class) {
            $path = $manifest->getItemPath($class);
            if (filemtime($path) > $lastBuilt) {
                $da = new DatabaseAdmin();
                $da->doBuild(true);
                $update = true;
                break;
            }
        }
        if ($update) {
            echo "Database was updated<br/>";
        } else {
            echo "Database was not updated<br/>";
        }

        SSViewer::flush_template_cache();
        echo "Cache was flushed</br/>";

        $this->extend('onPostDeploy');
    }
}
