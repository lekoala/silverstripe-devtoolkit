<?php

/**
 * DevToolkitAdminExtension
 *
 * @author lekoala
 */
class DevToolkitAdminExtension extends DataExtension
{

    public function updateEditForm(CMSForm &$form)
    {
        $fields = $form->Fields();

        $class = $this->owner->modelClass;

        $o = singleton($class);

        $gf = $form->Fields()->dataFieldByName($class);
        $config = $gf->getConfig();

        // If we have the bulk manager, enable by default
        if (class_exists('GridFieldBulkManager')) {
            if ($o->hasMethod('bulkManagerDisable') && $o->bulkManagerDisable) {

            } else {
                $config->addComponent($bulkManager = new GridFieldBulkManager());

                if ($o->hasMethod('bulkManagerAdd')) {
                    $actions = $o->bulkManagerAdd();

                    foreach ($actions as $key => $action) {
                        $bulkHandler = isset($action['Handler']) ? $action['Handler'] : null;
                        $bulkConfig = isset($action['Config']) ? $action['Config'] : null;
                        $bulkManager->addBulkAction($action['Name'], $action['Label'], $bulkHandler, $bulkConfig);
                    }
                }
            }
        }

        // Add a fast export button
        if($o->canView()) {
            $config->addComponent(new FastExportButton('buttons-before-left'));
        }
       
    }
}
