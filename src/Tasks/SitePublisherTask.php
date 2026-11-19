<?php

namespace LeKoala\DevToolkit\Tasks;

use LeKoala\DevToolkit\BuildTaskTools;
use SilverStripe\Dev\BuildTask;
use SilverStripe\Versioned\Versioned;
use SilverStripe\PolyExecution\PolyOutput;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;

/**
 */
class SitePublisherTask extends BuildTask
{
    use BuildTaskTools;

    protected static string $commandName = 'site-publisher';
    protected string $title = 'Site Publisher';
    protected static string $description = 'Publish selected versioned DataObject classes.';

    protected function execute(InputInterface $input, PolyOutput $output): int
    {
        $this->setTaskContext($input, $output);
        $classes = (string) $this->taskOption('classes', 'Page');
        $go = (bool) $this->taskOption('go');

        $cbSave = function ($List, $publish = false) {
            foreach ($List as $Item) {
                $this->message('Saving item "' . $Item->getTitle() . '"');
                if ($publish) {
                    $Item->publishRecursive();
                } else {
                    $Item->write();
                }
            }
        };

        $classesToPublish = explode(',', $classes);

        if ($go) {
            foreach ($classesToPublish as $class) {
                $this->message("Publishing class $class");

                $List = $class::get();
                $singl = $class::singleton();
                $fluent = $singl->has_extension("\\TractorCow\\Fluent\\Extension\\FluentExtension");

                $publish = $singl->has_extension(Versioned::class);

                // With fluent we need to change state when saving
                if ($fluent) {
                    $state = \TractorCow\Fluent\State\FluentState::singleton();
                    $allLocales = \TractorCow\Fluent\Model\Locale::get();
                    foreach ($allLocales as $locale) {
                        $this->message('Publishing with locale ' . $locale->Locale, "info");
                        $state->withState(function ($state) use ($locale, $List, $cbSave, $publish) {
                            $state->setLocale($locale->Locale);
                            $cbSave($List, $publish);
                        });
                    }
                } else {
                    $cbSave($List, $publish);
                }
            }
        } else {
            foreach ($classesToPublish as $class) {
                $this->message("Would publish " . $class::get()->count() . " items for class " . $class);
            }
        }

        return Command::SUCCESS;
    }

    /**
     * @return array<InputOption>
     */
    public function getOptions(): array
    {
        return [
            new InputOption('classes', null, InputOption::VALUE_REQUIRED, 'Comma-separated classes', 'Page'),
            new InputOption('go', null, InputOption::VALUE_NONE, 'Apply publishing'),
        ];
    }
}
