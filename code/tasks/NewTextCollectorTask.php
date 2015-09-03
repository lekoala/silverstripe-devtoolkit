<?php

class Newi18nTextCollector extends i18nTextCollector
{
    protected $clearUnused = false;

    function getClearUnused()
    {
        return $this->clearUnused;
    }

    function setClearUnused($clearUnused)
    {
        $this->clearUnused = $clearUnused;
    }

    public function run($restrictToModules = null, $mergeWithExisting = false)
    {
        $entitiesByModule = $this->collect($restrictToModules,
            $mergeWithExisting);

        $results = array();

        // Write each module language file
        if ($entitiesByModule) {
            foreach ($entitiesByModule as $module => $entities) {
                $results[$module] = $this->getWriter()->write($entities,
                    $this->defaultLocale, $this->baseSavePath.'/'.$module);
            }
        }

        return $results;
    }

    public function collect($restrictToModules = null, $mergeWithExisting = true)
    {
        $clearUnused = $this->getClearUnused();

        $glob    = glob($this->basePath.'/*', GLOB_ONLYDIR);
        $modules = array_map(function($item) {
            return basename($item);
        }, $glob);

        $themeFolders = array();

        // A master string tables array (one master per module)
        $entitiesByModule = array();

        // Scan themes
        foreach ($modules as $index => $module) {
            if ($module != 'themes') continue;
            else {
                $themes = scandir($this->basePath."/themes");
                if (count($themes)) {
                    foreach ($themes as $theme) {
                        if (is_dir($this->basePath."/themes/".$theme) && substr($theme,
                                0, 1) != '.' && is_dir($this->basePath."/themes/".$theme."/templates")) {

                            $themeFolders[] = 'themes/'.$theme;
                        }
                    }
                }
                $themesInd = $index;
            }
        }

        if (isset($themesInd)) {
            unset($modules[$themesInd]);
        }

        $modules = array_merge($modules, $themeFolders);

        foreach ($modules as $module) {
            if ($restrictToModules && !in_array($module, $restrictToModules)) {
                continue;
            }

            // Only search for calls in folder with a _config.php file (which means they are modules, including
            // themes folder)
            $isValidModuleFolder = (
                is_dir("$this->basePath/$module") && is_file("$this->basePath/$module/_config.php")
                && substr($module, 0, 1) != '.'
                ) || (
                substr($module, 0, 7) == 'themes/' && is_dir("$this->basePath/$module")
                );

            if (!$isValidModuleFolder) continue;

            // we store the master string tables
            $processedEntities = $this->processModule($module);

            if (isset($entitiesByModule[$module])) {
                $entitiesByModule[$module] = array_merge_recursive($entitiesByModule[$module],
                    $processedEntities);
            } else {
                $entitiesByModule[$module] = $processedEntities;
            }

            // extract all entities for "foreign" modules (fourth argument)
            foreach ($entitiesByModule[$module] as $fullName => $spec) {
                if (isset($spec[2]) && $spec[2] && $spec[2] != $module) {
                    $othermodule                               = $spec[2];
                    if (!isset($entitiesByModule[$othermodule]))
                            $entitiesByModule[$othermodule]            = array();
                    unset($spec[2]);
                    $entitiesByModule[$othermodule][$fullName] = $spec;
                    unset($entitiesByModule[$module][$fullName]);
                }
            }

            if ($mergeWithExisting) {
                $locale = $this->defaultLocale;

                // If we pass simple lang instead of locale, Zend_Translate will complain
                if (strlen($locale) == 2) {
                    if (class_exists('Fluent')) {
                        $fluentLocales = Fluent::locale_names();
                        foreach ($fluentLocales as $fluentLocale => $name) {
                            if (substr($fluentLocale, 0, 2) == $locale) {
                                $locale = $fluentLocale;
                            }
                        }
                    } else {
                        $locale = i18n::get_locale_from_lang($locale);
                    }
                }
                $adapter    = Injector::inst()->create('i18nRailsYamlAdapter');
                $masterFile = "{$this->basePath}/{$module}/lang/"
                    .$adapter->getFilenameForLocale($this->defaultLocale);
                if (!file_exists($masterFile)) {
                    continue;
                }

                $adapter->addTranslation(array(
                    'content' => $masterFile,
                    'locale' => $this->defaultLocale,
                    'reload' => true,
                    'clear' => true
                ));

                //do not overwrite by interverting
                $messages = array_map(
                    // Transform each master string from scalar value to array of strings
                    function($v) {
                    return array($v);
                }, $adapter->getMessages($this->defaultLocale)
                );

                $entitiesByModule[$module] = array_merge(
                    $entitiesByModule[$module], $messages
                );

                //clear by diffing

                if ($clearUnused) {
                    $unusedEntities = array_diff(array_keys($messages),
                        array_keys($processedEntities)
                    );

                    foreach ($unusedEntities as $unusedEntity) {
                        if (strpos($unusedEntity, '.db_') !== false) {
                            continue;
                        }
                        if (strpos($unusedEntity, '.has_one_') !== false) {
                            continue;
                        }
                        if (strpos($unusedEntity, '.has_many_') !== false) {
                            continue;
                        }
                        if (strpos($unusedEntity, '.many_many') !== false) {
                            continue;
                        }
                        if (strpos($unusedEntity, '.belongs_many_many') !== false) {
                            continue;
                        }

                        echo '<div style="color:red">Removed translations for '.$unusedEntity.'</div>';
                        unset($entitiesByModule[$module][$unusedEntity]);
                    }
                }
            }
        }

        // Restrict modules we update to just the specified ones (if any passed)
        if ($restrictToModules && count($restrictToModules)) {
            foreach (array_diff(array_keys($entitiesByModule),
                $restrictToModules) as $module) {
                unset($entitiesByModule[$module]);
            }
        }

        return $entitiesByModule;
    }
}

/**
 * NewTextCollectorTask
 *
 * @author lekoala
 */
class NewTextCollectorTask extends i18nTextCollectorTask
{
    protected $title       = "i18n Textcollector Task (improved)";
    protected $description = 'Create or update translation files';

    public function run($request)
    {
        increase_time_limit_to();

        $locale = $request->getVar('locale') ? $request->getVar('locale') : 'en';

        $merge = $request->getVar('merge') ? $request->getVar('merge') : true;
        $clear = $request->getVar('clear') ? $request->getVar('clear') : false;


        $c = new Newi18nTextCollector($locale, $merge);
        if ($clear) {
            $c->setClearUnused($clear);
        }

        $writer = $request->getVar('writer');
        if ($writer) {
            $c->setWriter(new $writer());
        }

        //Scope to mysite by default
        $restrictModules = ($request->getVar('module')) ? explode(',',
                $request->getVar('module')) : array('mysite');


        echo 'You can pass ?module=mymodule,myothermodule to restrict to a specific module list<br/>';
        echo 'You can specifty the locale you want to collect by using ?locale=fr<br/>';
        echo 'By default translations will be merged. Specify ?merge=0 to prevent that<br/>';
        echo 'You can clear unused translations by specifying ?clear=1<br/>';
        echo '<hr/>';

        echo 'Collecting text for '.implode(',', $restrictModules).' and locale '.$locale;
        echo '<hr/>';

        $result = $c->run($restrictModules, true);

        if (empty($result)) {
            echo "<div style='color:orange'>The text collector did not collect anything</div>";
        }

        foreach ($result as $module => $res) {
            if ($res) {
                echo "<div style='color:green'>Collected text from $module</div>";
            } else {
                echo "<div style='color:red'>Failed to collect text from $module</div>";
            }
        }
    }
}