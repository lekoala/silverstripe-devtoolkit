<?php

/**
 * Make DataObjects easily sluggables
 *
 * You can configure which fields should be use in the slug through the slug_fields
 * property
 *
 * For the Link() method to work, you need to have a Page() method or relation defined
 *
 * @author Koala
 */
class SlugExtension extends DataExtension
{
    private static $db      = array(
        'Slug' => 'Varchar(150)',
    );
    private static $indexes = array(
        'Slug' => true,
    );

    public function onAfterWrite()
    {
        parent::onAfterWrite();

        $class  = $this->ownerBaseClass;
        $config = Config::inst()->forClass($class);

        // look for fields to use in slug
        $fields = array('Title');
        if ($config->slug_fields) {
            $fields = $config->slug_fields;
        }
        $needSlug = false;
        foreach ($fields as $field) {
            if ($this->owner->isChanged($field, 2)) {
                $needSlug = true;
                break;
            }
        }
        if (!$this->owner->Slug) {
            $needSlug = true;
        }

        // if we need a slug, compute it
        if ($needSlug && $this->owner->ID) {
            $slug = '';
            foreach ($fields as $field) {
                $slug .= ' '.$this->owner->$field;
            }
            $slug     = trim($slug);
            $baseSlug = $slug;

            $filter = new URLSegmentFilter;

            $oldSlug = $this->owner->Slug;
            $newSlug = substr($filter->filter($slug), 0, 140);

            $this->owner->Slug = $newSlug;

            // check for existing slugs
            $count  = 0;
            $record = self::getBySlug($class, $newSlug, $this->owner->ID);
            while ($record && $record->exists()) {
                $count++;
                $slug              = $baseSlug.'-'.$count;
                $newSlug           = $filter->filter($slug);
                $this->owner->Slug = $newSlug;
                $record            = self::getBySlug($class, $newSlug,
                        $this->owner->ID);
            }

            // prevent infinite loop because of onAfterWrite called multiple times
            if ($oldSlug == $newSlug) {
                return;
            }

            $this->owner->write();

            // store history
            if ($oldSlug && $oldSlug != $this->owner->Slug) {
                $count = SlugHistory::check($class, $oldSlug, $this->owner->ID);
                if ($count) {
                    // it already exists, no need to add twice
                    return;
                }

                SlugHistory::recordFromObject($this->owner, $oldSlug);
            }
        }
    }

    public function canWriteSlug()
    {
        $class  = $this->ownerBaseClass;
        $config = Config::inst()->forClass($class);

        // look for fields to use in slug
        $fields = array('Title');
        if ($config->slug_fields) {
            $fields = $config->slug_fields;
        }
        $canWrite = false;
        foreach ($fields as $field) {
            if ($this->owner->$field) {
                $canWrite = true;
            }
        }
        return $canWrite;
    }

    /**
     * Link to this record. Expect the "Page" method or relation to be set
     *
     * Page can either return a page object (method Link will be used) or
     * a string (slug will be appended)
     * 
     * @return string
     */
    public function Link()
    {
        if (!$this->owner->Slug && $this->owner->ID) {
            if ($this->canWriteSlug()) {
                $this->owner->write();
            } else {
                return '';
            }
        }
        $page = $this->owner->Page();
        if (is_string($page)) {
            return rtrim($page, '/') . '/' . $this->owner->Slug;
        }
        if (!$page) {
            if (Controller::has_curr()) {
                return Controller::curr()->Link('detail/'.$this->owner->Slug);
            }
            return '';
        }
        return $page->Link('detail/'.$this->owner->Slug);
    }

    /**
     * Get a record by its slug
     *
     * @param string $class
     * @param string $slug
     * @param int $excludeID Exclude this ID from searched slugs
     * @param bool $checkHistory
     * @return DataObject
     */
    public static function getBySlug($class, $slug, $excludeID = null,
                                     $checkHistory = true)
    {
        /* @var $datalist DataList */
        $datalist = $class::get()->filter('Slug', $slug);
        if ($excludeID) {
            $datalist = $datalist->exclude('ID', $excludeID);
        }
        $record = $datalist->first();
        if ((!$record || !$record->exists()) && $checkHistory) {
            $historyRecord = SlugHistory::getRecordByClass($class, $slug,
                    $excludeID);
            if ($historyRecord) {
                $record = $historyRecord;
            }
        }
        return $record;
    }

    public function updateBetterButtonsActions($actions)
    {
        $link = Director::absoluteURL($this->owner->Link());
        $view = _t('SlugExtension.OPEN', 'Open');
        $actions->push(
            new BetterButtonNewWindowLink(
            $view, $link
            )
        );
    }

    public function updateCMSFields(FieldList $fields)
    {
        if (!$this->owner->ID) {
            $fields->removeByName('Slug');
        } else {
            $slug = $fields->dataFieldByName('Slug');

            // slug could be removed by another extension
            if ($slug) {
                $slug->setReadonly(true);
            }
        }
    }
}
