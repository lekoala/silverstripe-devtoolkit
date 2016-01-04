<?php

/**
 * Manage dynamic cms access
 *
 * @author Koala
 */
class DevToolkitLeftAndMainExtension extends LeftAndMainExtension
{

    public function init()
    {
        if (!Controller::has_curr()) {
            return;
        }

        /* @var $ctrl Controller */
        $ctrl = Controller::curr();
        /* @ var $req SS_HTTPRequest */
        $req  = $ctrl->getRequest();

        // Otherwise it will get excluded if it does not have access to all subsites...
        if (class_exists('Subsite')) {
            Subsite::$disable_subsite_filter = true;
        }
        $base           = AdminRootController::config()->url_base;
        $defaultPanel   = AdminRootController::config()->default_panel;
        $currentSegment = $req->getURL();

        // We will fail if we are redirected to a panel without the proper permission
        if (($currentSegment == $base || $currentSegment == $base.'/pages') && $defaultPanel
            == 'CMSPagesController' && !Permission::check('CMS_ACCESS_CMSMain')) {
            // Instead, let's redirect to something we can access
            if (Permission::check('CMS_ACCESS')) {
                $member      = Member::currentUser();
                $permissions = Permission::permissions_for_member($member->ID);

                foreach ($permissions as $permission) {
                    if (strpos($permission, 'CMS_ACCESS_') === 0) {
                        $class   = str_replace('CMS_ACCESS_', '', $permission);
                        $segment = Config::inst()->get($class, 'url_segment');
                        $url     = Director::absoluteBaseURL().$base.'/'.$segment;

                        header('Location:'.$url);
                        exit();
                    }
                }
            }
        }

        if (class_exists('Subsite')) {
            Subsite::$disable_subsite_filter = false;
        }
    }
}
