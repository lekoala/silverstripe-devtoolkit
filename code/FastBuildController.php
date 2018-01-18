<?php

class FastBuildController extends Controller
{

    private static $url_handlers = array(
        '' => 'fastbuild',
        'regenclassmanifest' => 'regenclassmanifest',
    );

    private static $allowed_actions = array(
        'fastbuild',
        'regenclassmanifest',
    );

    public function doRegenClassManifest()
    {
        $this->msg("Regenerating class manifest");
        $cache = SS_ClassLoader::instance()->getManifest()->regenerate();
        $this->msg("Manifest generated");
    }

    public function regenclassmanifest($request)
    {
        $this->wrapCall('doRegenClassManifest');
    }

    protected function msg($msg)
    {
        echo $msg . "<br/>";
    }

    public function fastbuild($request)
    {
        $this->wrapCall('doFastBuild');
    }

    protected function doFastBuild()
    {
        $da = FastDatabaseAdmin::create();
        $da->fastbuild();
    }

    protected function wrapCall($method)
    {
        if (Director::is_cli()) {
            return $this->$method();
        } else {
            $renderer = DebugView::create();
            $renderer->writeHeader();
            $renderer->writeInfo("Environment Builder", Director::absoluteBaseURL());
            echo "<div class=\"build\">";

            $this->$method();

            echo "</div>";
            $renderer->writeFooter();
        }
    }

}
