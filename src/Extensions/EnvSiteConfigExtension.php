<?php

namespace LeKoala\DevToolkit\Extensions;

use Exception;
use SilverStripe\Forms\FieldList;
use SilverStripe\Control\Director;
use SilverStripe\ORM\DataExtension;
use SilverStripe\Forms\TextareaField;
use SilverStripe\Security\Permission;

/**
 * Edit the .env file from the SiteConfig screen
 *
 * @author lekoala
 */
class EnvSiteConfigExtension extends DataExtension
{
    public function updateCMSFields(FieldList $fields)
    {
        $SS_ENVIRONMENT_FILE = Director::baseFolder() . '/.env';
        if ($SS_ENVIRONMENT_FILE && Permission::check('ADMIN')) {
            $class = TextareaField::class;
            $fields->addFieldToTab(
                'Root.Env',
                $field = $class::create(
                    'SS_Environment',
                    null,
                    file_get_contents($SS_ENVIRONMENT_FILE)
                )
            );
            if (!is_writable($SS_ENVIRONMENT_FILE)) {
                $field->setReadonly(true);
            }
        }
    }

    public function setSS_Environment($v)
    {
        $SS_ENVIRONMENT_FILE = Director::baseFolder() . '/.env';
        if (!is_writable($SS_ENVIRONMENT_FILE)) {
            throw new Exception('Environment file must be writable');
        } else {
            return file_put_contents($SS_ENVIRONMENT_FILE, $v);
        }
    }
}
