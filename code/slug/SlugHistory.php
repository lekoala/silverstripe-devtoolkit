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
        'Slug' => true
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