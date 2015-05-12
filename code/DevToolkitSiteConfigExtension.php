<?php

/**
 * DevToolkitSiteConfigExtension
 *
 * @author lekoala
 */
class DevToolkitSiteConfigExtension extends DataExtension
{

    public function updateCMSFields(\FieldList $fields)
    {
        if (defined('SS_ENVIRONMENT_FILE') && Permission::check('ADMIN')) {
            $class = 'CodeEditorField';
            if (!class_exists($class)) {
                $class = 'TextareaField';
            }
            $fields->addFieldToTab('Root.Env',
                $field = new $class('SS_Environment', null,
                file_get_contents(SS_ENVIRONMENT_FILE)));
            if (class_exists('CodeEditorField')) {
                $field->setMode('php');
            }
            if(!is_writable(SS_ENVIRONMENT_FILE)) {
                $field->setReadonly(true);
            }
        }
    }

      public function setSS_Environment($v)
    {
        if (!is_writable(SS_ENVIRONMENT_FILE)) {
			throw new Exception('Environment file must be writable');
        } else {
            return file_put_contents(SS_ENVIRONMENT_FILE, $v);
        }
    }
}