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
        if (Director::isDev()) {
            echo "User-agent: *
Disallow: /";
            exit();
        }
    }
}