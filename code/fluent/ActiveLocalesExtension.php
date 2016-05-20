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

        // Find in set is only compatible with MySql
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
        $localesNames = Fluent::locale_names();
        if (!$this->owner instanceof SiteConfig) {
            // If the ActiveLocales has been applied to SiteConfig, restrict locales to allowed ones
            $conf = SiteConfig::current_site_config();
            if ($conf->hasExtension('ActiveLocalesExtension') && $conf->ActiveLocales) {
                $localesNames = $conf->ActiveLocalesNames();
            }
        }

        // Avoid clutter if we only have one locale anyway
        if (count($localesNames) === 1) {
            return;
        }

        $fields->addFieldToTab('Root.Main',
            $lang = new ListboxField('ActiveLocales',
            _t('ActiveLocalesExtension.ACTIVELOCALE', 'Active Languages'),
            $localesNames));
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
        if (!$this->owner->hasMethod('LocaleInformation')) {
            return new ArrayList();
        }
        $data = array();
        $list = $this->owner->ActiveLocales;
        if (!$list) {
            return $this->owner->Locales();
        }
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
        if (!$this->owner->hasMethod('LocaleInformation')) {
            return new ArrayList();
        }
        $data   = array();
        $config = SiteConfig::current_site_config();
        $list   = $config->ActiveLocales;
        $ctrl   = null;
        if (Controller::has_curr()) {
            $ctrl = Controller::curr();
        }
        if (!$list) {
            if ($ctrl && $ctrl->hasMethod('Locales')) {
                return $ctrl->Locales();
            }
            return $config->Locales();
        }
        $validLocales = Fluent::locales();
        foreach (explode(',', $list) as $locale) {
            if(!in_array($locale, $validLocales)) {
                continue;
            }
            if ($ctrl && $ctrl->hasMethod('LocaleInformation')) {
                $data[] = $ctrl->LocaleInformation($locale);
            } else {
                $data[] = $this->owner->LocaleInformation($locale);
            }
        }
        return new ArrayList($data);
    }

    /**
     * Return a list of actives locales
     * 
     * @return array
     */
    public function ActiveLocalesNames()
    {
        $locales = array();
        $list    = $this->owner->ActiveLocales;
        if (!$list) {
            return Fluent::locale_names();
        }
        foreach (explode(',', $list) as $locale) {
            $locales[$locale] = i18n::get_locale_name($locale);
        }
        return $locales;
    }

    /**
     * If only one locale is active
     * 
     * @return string|boolean The active locale or false
     */
    public function HasOnlyOneLocale()
    {
        $list = $this->owner->ActiveLocales;
        if (!$list) {
            $list = implode(',', array_keys(Fluent::locale_names()));
        }
        $list = trim($list, ',');
        if ($list && strpos($list, ',') === false) {
            return $list;
        }
        return false;
    }
}