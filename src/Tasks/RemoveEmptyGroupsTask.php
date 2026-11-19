<?php

namespace LeKoala\DevToolkit\Tasks;

use SilverStripe\ORM\DB;
use SilverStripe\Dev\BuildTask;
use SilverStripe\Security\Group;
use LeKoala\DevToolkit\BuildTaskTools;
use SilverStripe\Subsites\Model\Subsite;
use SilverStripe\PolyExecution\PolyOutput;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;

/**
 * @author LeKoala <thomas@lekoala.be>
 */
class RemoveEmptyGroupsTask extends BuildTask
{
    use BuildTaskTools;

    protected static string $commandName = 'remove-empty-groups';
    protected string $title = 'Remove empty/duplicate groups from the CMS';
    protected static string $description = 'Remove or merge groups with explicit operation flags.';

    protected function execute(InputInterface $input, PolyOutput $output): int
    {
        $this->setTaskContext($input, $output);
        $groups = Group::get();
        $merge = (bool) $this->taskOption('merge');
        $drop = (bool) $this->taskOption('drop');
        $dropNoPermission = (bool) $this->taskOption('permission');
        $subsite = (bool) $this->taskOption('subsite');
        if (!$merge && !$drop) {
            $this->message('Pass --drop or --merge to perform an operation.', 'notice');
            return Command::FAILURE;
        }

        if (class_exists(Subsite::class) && $subsite) {
            Subsite::$disable_subsite_filter = true;
        }

        if ($drop) {
            $this->message("Dropping groups with no members");
            if ($dropNoPermission) {
                $this->message("Also dropping groups with no permissions");
            }
            foreach ($groups as $group) {
                if (!$group->Members()->count()) {
                    $this->message(
                        "Removing group  {$group->ID} because it has no members",
                        "deleted"
                    );
                    $group->delete();
                }
                if ($dropNoPermission) {
                    $c = $group->Permissions()->count();
                    if (!$c) {
                        $this->message(
                            "Removing group {$group->ID} because it has no permissions",
                            "deleted"
                        );
                        $group->delete();
                    }
                }
            }
        }
        if ($merge) {
            $this->message("Merging groups with duplicated codes");
            $index = array();

            /* @var $group Group */
            foreach ($groups as $group) {
                $this->message("Found group " . $group->Code);
                if (!isset($index[$group->Code])) {
                    $index[$group->Code] = $group;
                    $this->message("First instance of group, do not merge");
                    continue;
                }

                $mergeGroup = $index[$group->Code];


                $this->message(
                    'Merge group ' . $group->ID . ' with ' . $mergeGroup->ID,
                    'repaired'
                );

                $i = 0;
                foreach ($group->Members() as $m) {
                    $i++;
                    $mergeGroup->Members()->add($m);
                }
                $this->message(
                    'Added ' . $i . ' members to group',
                    'created'
                );

                $this->message(
                    "Group " . $group->ID . ' was deleted',
                    'deleted'
                );
                $group->delete();
            }
        }

        $this->message('All done!');

        return Command::SUCCESS;
    }

    /**
     * @return array<InputOption>
     */
    public function getOptions(): array
    {
        return [
            new InputOption('drop', null, InputOption::VALUE_NONE, 'Drop groups without members'),
            new InputOption('permission', null, InputOption::VALUE_NONE, 'Also drop groups without permissions'),
            new InputOption('merge', null, InputOption::VALUE_NONE, 'Merge groups with duplicate codes'),
            new InputOption('subsite', null, InputOption::VALUE_NONE, 'Disable subsite filtering'),
        ];
    }
}
