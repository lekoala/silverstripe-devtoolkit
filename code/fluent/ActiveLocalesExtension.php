<?php

/**
 * ActiveLocalesExtension
 *
 * A small Fluent extension to deal with active locales configurable through the cms
 *
 * Compatible with MySql only at the moment because of FIND_IN_SET
 *
 * @author lekoala
 */
class ActiveLocalesExtension extends DataExtension
{
    private static $db = array(
        'ActiveLocales' => 'Varchar(255)',
    );

    /**
     * Helper to detect if we are in admin or development admin
     * 
     * @return boolean
     */
    public function isAdminBackend()
    {
        /* @var $ctrl Controller */
        $ctrl = Controller::curr();
        if (
            $ctrl instanceof LeftAndMain ||
            $ctrl instanceof DevelopmentAdmin ||
            $ctrl instanceof DatabaseAdmin ||
            (class_exists('DevBuildController') && $ctrl instanceof DevBuildController)
        ) {
            return true;
        }

        return false;
    }

    public function augmentSQL(SQLQuery &$query, DataQuery &$dataQuery = null)
    {
        // Actives locales defined on a SiteConfig are there as a global setting
        if ($this->owner instanceof SiteConfig) {
            return;
        }

        // In admin, show everthing anyway
        if ($this->isAdminBackend()) {
            return;
        }

        $c = DB::getConn();
        if (!$c instanceof MySQLDatabase) {
            return;
        }

        $locale = $dataQuery->getQueryParam('Fluent.Locale') ? : Fluent::current_locale();

        $from = $query->getFrom();

        $where = $query->getWhere();

        $column = 'ActiveLocales';
        $table  = null;

        // Check on which table is the ActiveLocales field
        foreach ($from as $fromTable => $conditions) {
            if ($table === null) {
                $table = $fromTable;
            }
            $db = DataObject::custom_database_fields($fromTable);
            if ($db && isset($db[$column])) {
                $table = $fromTable;
                break;
            }
        }

        $identifier = "\"$table\".\"$column\"";

        $where[] = "$identifier IS NULL OR FIND_IN_SET ('$locale', $identifier) > 0";

        $query->setWhere($where);
    }

    public function updateCMSFields(FieldList $fields)
    {

        $languages = array();
        foreach (Fluent::locales() as $locale) {
            $languages[$locale] = i18n::get_locale_name($locale);
        }

        $fields->addFieldToTab('Root.Main',
            $lang = new ListboxField('ActiveLocales',
            _t('ActiveLocalesExtension.ACTIVELOCALE', 'Active Languages'),
            $languages));
        $lang->setMultiple(true);
        return $fields;
    }

    /**
     * Actives locales as defined on current object
     * 
     * @return \ArrayList
     */
    public function ThisActiveLocalesList()
    {
        $data = array();
        $list = $this->owner->ActiveLocales;
        foreach (explode(',', $list) as $locale) {
            $data[] = $this->owner->LocaleInformation($locale);
        }
        return new ArrayList($data);
    }

    /**
     * Actives locales as defined in siteconfig
     *
     * @return \ArrayList
     */
    public function ActiveLocalesList()
    {
        $data = array();
        $list = SiteConfig::current_site_config()->ActiveLocales;
        $ctrl = Controller::curr();
        foreach (explode(',', $list) as $locale) {
            $data[] = $ctrl->LocaleInformation($locale);
        }
        return new ArrayList($data);
    }
}