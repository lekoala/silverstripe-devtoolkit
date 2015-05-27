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

        $gf     = $form->Fields()->dataFieldByName($class);
        $config = $gf->getConfig();

        // If we have the bulk manager, enable by default
        if (class_exists('GridFieldBulkManager')) {
            if ($o->hasMethod('bulkManagerDisable') && $o->bulkManagerDisable) {

            } else {
                $config->addComponent($bulkManager = new GridFieldBulkManager());

                if ($o->hasMethod('bulkManagerAdd')) {
                    $actions = $o->bulkManagerAdd();

                    foreach ($actions as $key => $action) {
                        $bulkHandler = isset($action['Handler']) ? $action['Handler']
                                : null;
                        $bulkConfig  = isset($action['Config']) ? $action['Config']
                                : null;
                        $bulkManager->addBulkAction($action['Name'],
                            $action['Label'], $bulkHandler, $bulkConfig);
                    }
                }
            }
        }

        // If we have the export all button (form-extras module), enable
        if (class_exists('GridFieldExportAllButton')) {

            $config->addComponent(new GridFieldExportAllButton('before'));
        }

        if (!Director::isDev() || !Permission::check('ADMIN')) {
            return;
        }

        // Add buttons
        $config->addComponent(new GridFieldButtonRow('after'));
        $config->addComponent($btnEmpty = new DevToolkitEmptyButton('buttons-after-left'));
        if ($o->hasMethod('provideFake')) {
            $config->addComponent($btnAddFake = new DevToolkitAddFakeButton('buttons-after-left'));
        }
        if ($o->hasExtension('GeoExtension')) {
            $config->addComponent($btnAddFake = new DevToolkitFakeLocationsButton('buttons-after-left'));
        }
        $config->addComponent($btnDump = new DevToolkitDumpButton('buttons-after-left'));

        // Show session message
        $message = self::SessionMessage();
        if ($message) {
            $fields->insertBefore(new LiteralField("dev_message",
                "<div class='message {$message->Type}'>{$message->Content}</div>"),
                $gf->getName());
        }
    }

    /**
     * Set a session message that will be displayed by messenger on the next load
     * (useful after a redirect)
     *
     * @param string $message
     * @param string $type
     */
    public static function SetSessionMessage($message, $type = 'good')
    {
        Session::set('DevSessionMessage',
            array(
            'Type' => $type,
            'Content' => $message
        ));
    }

    /**
     * Get and clear session message
     * @param bool $clear
     * @return \ArrayData|boolean
     */
    public static function SessionMessage($clear = true)
    {
        $msg = Session::get('DevSessionMessage');
        if (!$msg) {
            return false;
        }
        if ($clear) {
            Session::clear('DevSessionMessage');
        }
        return new ArrayData($msg);
    }
}

class DevToolkitEmptyButton implements GridField_HTMLProvider, GridField_ActionProvider,
    GridField_URLHandler
{
    /**
     * Fragment to write the button to
     */
    protected $targetFragment;

    /**
     * @param string $targetFragment The HTML fragment to write the button into
     */
    public function __construct($targetFragment = "after")
    {
        $this->targetFragment = $targetFragment;
    }

    /**
     * Place the export button in a <p> tag below the field
     */
    public function getHTMLFragments($gridField)
    {
        $button = new GridField_FormAction(
            $gridField, 'empty', _t('DevToolkitEmptyButton.TITLE', 'Empty'),
            'empty', null
        );
        $button->addExtraClass('no-ajax');
        return array(
            $this->targetFragment => '<p class="grid-empty-button">'.$button->Field().'</p>',
        );
    }

    /**
     * export is an action button
     */
    public function getActions($gridField)
    {
        return array('empty');
    }

    public function handleAction(GridField $gridField, $actionName, $arguments,
                                 $data)
    {
        if ($actionName == 'empty') {
            return $this->handleEmpty($gridField);
        }
    }

    /**
     * it is also a URL
     */
    public function getURLHandlers($gridField)
    {
        return array(
            'empty' => 'handleEmpty',
        );
    }

    /**
     */
    public function handleEmpty($gridField, $request = null)
    {
        $class = $gridField->getModelClass();
        $all   = $class::get();
        foreach ($all as $r) {
            $r->delete();
        }
        $message = sprintf(
            _t('DevToolkitEmptyButton.REMOVEDALL',
                'Removed all records of type %s'), $class
        );

        DevToolkitAdminExtension::SetSessionMessage($message, 'good');

        return Controller::curr()->redirectBack();
    }
}

class DevToolkitAddFakeButton implements GridField_HTMLProvider, GridField_ActionProvider,
    GridField_URLHandler
{
    /**
     * Fragment to write the button to
     */
    protected $targetFragment;

    /**
     * @param string $targetFragment The HTML fragment to write the button into
     */
    public function __construct($targetFragment = "after")
    {
        $this->targetFragment = $targetFragment;
    }

    /**
     * Place the export button in a <p> tag below the field
     */
    public function getHTMLFragments($gridField)
    {
        $button = new GridField_FormAction(
            $gridField, 'add_fake',
            _t('DevToolkitAddFakeButton.TITLE', 'Add fake'), 'add_fake', null
        );
        $button->addExtraClass('no-ajax');
        return array(
            $this->targetFragment => '<p class="grid-add-fake-button">'.$button->Field().'</p>',
        );
    }

    /**
     * export is an action button
     */
    public function getActions($gridField)
    {
        return array('add_fake');
    }

    public function handleAction(GridField $gridField, $actionName, $arguments,
                                 $data)
    {
        if ($actionName == 'add_fake') {
            return $this->handleAddFake($gridField);
        }
    }

    /**
     * it is also a URL
     */
    public function getURLHandlers($gridField)
    {
        return array(
            'add_fake' => 'handleAddFake',
        );
    }

    /**
     */
    public function handleAddFake($gridField, $request = null)
    {
        $class = $gridField->getModelClass();

        $extensions = $class::get_extensions($class);
        foreach ($extensions as $extension) {
            if (method_exists($extension, 'provideFake')) {
                $class = $extension;
            }
        }

        if (!method_exists($class, 'provideFake')) {
            DevToolkitAdminExtension::SetSessionMessage("This object does not implement provideFake method",
                'bad');
            return Controller::curr()->redirectBack();
        }

        $class::provideFake();

        DevToolkitAdminExtension::SetSessionMessage(_t('DevToolkitAddFakeButton.FAKE_RECORD_ADDED',
                'Fake record added'), 'good');

        return Controller::curr()->redirectBack();
    }
}

class DevToolkitDumpButton implements GridField_HTMLProvider, GridField_ActionProvider,
    GridField_URLHandler
{
    /**
     * Fragment to write the button to
     */
    protected $targetFragment;

    /**
     * @param string $targetFragment The HTML fragment to write the button into
     */
    public function __construct($targetFragment = "after")
    {
        $this->targetFragment = $targetFragment;
    }

    /**
     * Place the export button in a <p> tag below the field
     */
    public function getHTMLFragments($gridField)
    {
        $button = new GridField_FormAction(
            $gridField, 'dump_sql',
            _t('DevToolkitDumpButton.TITLE', 'Dump to sql'), 'dump_sql', null
        );
        $button->addExtraClass('no-ajax');
        return array(
            $this->targetFragment => '<p class="grid-dump-button">'.$button->Field().'</p>',
        );
    }

    /**
     * export is an action button
     */
    public function getActions($gridField)
    {
        return array('dump_sql');
    }

    public function handleAction(GridField $gridField, $actionName, $arguments,
                                 $data)
    {
        if ($actionName == 'dump_sql') {
            return $this->handleDump($gridField);
        }
    }

    /**
     * it is also a URL
     */
    public function getURLHandlers($gridField)
    {
        return array(
            'dump_sql' => 'handleDump',
        );
    }

    /**
     */
    public function handleDump($gridField, $request = null)
    {
        $class = $gridField->getModelClass();

        $dumpSettings = array(
            'compress' => 'NONE',
            'include-tables' => array($class),
            'add-drop-database' => false,
            'add-drop-table' => true,
            'single-transaction' => true,
            'lock-tables' => false,
            'add-locks' => true,
            'extended-insert' => true,
            'disable-foreign-keys-check' => true
        );
        $dump         = new Ifsnop\Mysqldump\Mysqldump(DB::getConn()->currentDatabase(),
            SS_DATABASE_USERNAME, SS_DATABASE_PASSWORD, SS_DATABASE_SERVER,
            'mysql', $dumpSettings);
        $webdir       = '/private/dump/';

        $folder   = Folder::find_or_make($webdir);
        $filename = $class.'-'.date('YmdhIs').'.txt'; //store as text otherwise default asset configuration kick us
        $file     = $folder->getFullPath().$filename;

        $res = $dump->start($file);
        $folder->syncChildren();

        $link = '/assets'.$webdir.$filename;
        return Controller::curr()->redirect($link);
    }
}

class DevToolkitFakeLocationsButton implements GridField_HTMLProvider, GridField_ActionProvider,
    GridField_URLHandler
{
    /**
     * Fragment to write the button to
     */
    protected $targetFragment;

    /**
     * @param string $targetFragment The HTML fragment to write the button into
     */
    public function __construct($targetFragment = "after")
    {
        $this->targetFragment = $targetFragment;
    }

    /**
     * Place the export button in a <p> tag below the field
     */
    public function getHTMLFragments($gridField)
    {
        $button = new GridField_FormAction(
            $gridField, 'fake_locations',
            _t('DevToolkitFakeLocationsButton.TITLE', 'Add fake locations'),
            'fake_locations', null
        );
        $button->addExtraClass('no-ajax');
        return array(
            $this->targetFragment => '<p class="grid-dump-button">'.$button->Field().'</p>',
        );
    }

    /**
     * export is an action button
     */
    public function getActions($gridField)
    {
        return array('fake_locations');
    }

    public function handleAction(GridField $gridField, $actionName, $arguments,
                                 $data)
    {
        if ($actionName == 'fake_locations') {
            return $this->handleFakeLocations($gridField);
        }
    }

    /**
     * it is also a URL
     */
    public function getURLHandlers($gridField)
    {
        return array(
            'fake_locations' => 'handleFakeLocations',
        );
    }

    /**
     */
    public function handleFakeLocations($gridField, $request = null)
    {
        $class = $gridField->getModelClass();

        $all = $class::get();
        foreach ($all as $record) {
            if (!strlen(trim($record->StreetName))) {
                $tries = 0;

                do {
                    $coords = FakeRecordGenerator::latLon(null, null, 200);
                    $infos  = Geocoder::reverseGeocode($coords['lat'],
                            $coords['lng']);
                    $tries++;
                } while (!$infos && $tries < 5);

                if ($infos) {
                    $record->Latitude     = $infos->getLatitude();
                    $record->Longitude    = $infos->getLongitude();
                    $record->StreetName   = $infos->getStreetName();
                    $record->StreetNumber = $infos->getStreetNumber();
                    $record->PostalCode   = $infos->getPostalCode();
                    $record->CountryCode  = $infos->getCountryCode();
                    $record->Locality     = $infos->getLocality();
                    $record->write();
                }
            }
        }
        $message = sprintf(
            _t('DevToolkitFakeLocationsButton.ADD_FAKE_LOCATIONS',
                'Added fake locations to %s'), $class
        );

        DevToolkitAdminExtension::SetSessionMessage($message, 'good');

        return Controller::curr()->redirectBack();
    }
}