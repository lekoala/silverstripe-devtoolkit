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
        Debug::message('No target!');
    }
}