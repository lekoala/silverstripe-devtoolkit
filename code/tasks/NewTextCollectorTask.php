<?php

class Newi18nTextCollector extends i18nTextCollector
{

    public function collect($restrictToModules = null, $mergeWithExisting = true)
    {
        $modules      = scandir($this->basePath);
        $themeFolders = array();

        // A master string tables array (one mst per module)
        $entitiesByModule = array();

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
                $adapter    = new i18nRailsYamlAdapter(array('locale' => $this->defaultLocale,
                    'disableNotices' => true));
//				$adapter = Injector::inst()->create('i18nRailsYamlAdapter');
                $masterFile = "{$this->basePath}/{$module}/lang/"
                    .$adapter->getFilenameForLocale($this->defaultLocale);
                if (!file_exists($masterFile)) {
                    continue;
                }

                $adapter->addTranslation(array(
                    'content' => $masterFile,
                    'locale' => $this->defaultLocale,
                    'reload' => true,
//					'clear' => true
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
    protected $title = "i18n Textcollector Task (improved)";

    public function run($request)
    {
        increase_time_limit_to();

        $locale = $request->getVar('locale') ? $request->getVar('locale') : 'en';

        $c = new Newi18nTextCollector($locale);

        $writer = $request->getVar('writer');
        if ($writer) {
            $c->setWriter(new $writer());
        }

        //Scope to mysite by default
        $restrictModules = ($request->getVar('module')) ? explode(',',
                $request->getVar('module')) : 'mysite';
        return $c->run($restrictModules, true);
    }
}