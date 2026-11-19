<?php

namespace LeKoala\DevToolkit\Tasks;

use SilverStripe\ORM\DB;
use SilverStripe\Dev\BuildTask;
use SilverStripe\Security\Group;
use LeKoala\DevToolkit\BuildTaskTools;
use SilverStripe\Security\Permission;
use SilverStripe\Subsites\Model\Subsite;
use SilverStripe\PolyExecution\PolyOutput;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;

/**
 * @author LeKoala <thomas@lekoala.be>
 */
class RemoveOldPermissionsTask extends BuildTask
{
    use BuildTaskTools;

    protected static string $commandName = 'remove-other-permissions';
    protected string $title = "Remove 'other' permissions from the CMS";
    protected static string $description = 'Delete permission codes no longer provided by registered code.';

    protected function execute(InputInterface $input, PolyOutput $output): int
    {
        $this->setTaskContext($input, $output);
        $go = (bool) $this->taskOption('go');

        $permissions = Permission::get_codes(true);

        $other = $permissions['Other'];
        foreach ($other as $k => $infos) {
            $this->message(($go ? 'Deleting ' : 'Would delete ') . $k);
            if ($go) {
                DB::prepared_query("DELETE FROM Permission WHERE Code = ?", [$k]);
            }
        }

        if (!$go) {
            $this->message('Dry run, pass --go to delete these permissions.', 'notice');
        }

        return Command::SUCCESS;
    }

    /**
     * @return array<InputOption>
     */
    public function getOptions(): array
    {
        return [
            new InputOption('go', null, InputOption::VALUE_NONE, 'Delete obsolete permissions'),
        ];
    }
}
