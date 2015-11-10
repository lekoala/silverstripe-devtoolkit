<?php

/**
 * A simple controller to prevent indexing on dev websites
 *
 * @author Koala
 */
class DevRobotsController extends Controller
{
    private static $allowed_actions = array('index');

    public function index()
    {
        $robots = BASE_PATH . '/robots.txt';
        if(is_file($robots)) {
            echo file_get_contents($robots);
            exit();
        }
        if (Director::isDev()) {
            echo "User-agent: *
Disallow: /";
            exit();
        }
        else {
            echo "User-agent: *
Disallow: /admin
Disallow: /?flush
Disallow: /assets/private
Disallow: /assets/owner
Allow: /";
        }
    }
}