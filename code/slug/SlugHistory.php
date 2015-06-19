<?php

/**
 * Store history of slugs
 *
 * @author Koala
 */
class SlugHistory extends DataObject
{
    private static $db      = array(
        'RecordID' => 'Int',
        'ObjectClass' => 'Varchar',
        'Slug' => 'Varchar(255)'
    );
    private static $indexes = array(
        'RecordID' => true,
        'ObjectClass' => true,
        'Slug' => true,
        // make an index for faster searches by class and slug
        'ObjectSlug' => array(
            'type' => 'index',
            'value' => '"ObjectClass","Slug"'
        )
    );

    /**
     * Record a slug history from a given object in a standard way
     * 
     * @param DataObject $object
     * @param string $slug
     * @return int
     */
    public static function recordFromObject(DataObject $object, $slug)
    {
        if (!$object->ID) {
            return;
        }
        $history              = new SlugHistory;
        $history->RecordID    = $object->ID;
        $history->ObjectClass = $object->ClassName;
        $history->Slug        = $slug;
        return $history->write();
    }

    /**
     * Get slug by class
     * 
     * @param string $class
     * @param string $slug
     * @return DataList
     */
    public static function getByClass($class, $slug)
    {
        return self::get()->filter(
                array(
                    'ObjectClass' => $class,
                    'Slug' => $slug
                )
        );
    }

    /**
     * Get a record from its old slug
     *
     * @param string $class
     * @param string $slug
     * @param int $excludeID
     * @return DataObject
     */
    public static function getRecordByClass($class, $slug, $excludeID = null)
    {
        $historylist = self::getByClass($class, $slug);
        if ($excludeID) {
            $historylist = $historylist->exclude('RecordID', $excludeID);
        }
        $record = null;
        $history = $historylist->first();
        if ($history && $history->exists()) {
            $record = $class::get()->byID($history->RecordID);
        }
        return $record;
    }

    /**
     * Check if a given slug is already stored
     *
     * @param string $class
     * @param string $slug
     * @param int $id
     * @return int
     */
    public static function check($class, $slug, $id)
    {
        return self::get()->filter(
                array(
                    'ObjectClass' => $class,
                    'Slug' => $slug,
                    'RecordID' => $id
                )
            )->count();
    }
}