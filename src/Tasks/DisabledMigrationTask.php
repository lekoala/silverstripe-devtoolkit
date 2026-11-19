<?php

namespace LeKoala\DevToolkit\Tasks;

use SilverStripe\Dev\MigrationTask;

/**
 * This is just to hide a useless migration task
 */
abstract class DisabledMigrationTasks extends MigrationTask
{
    public function isEnabled(): bool
    {
        return get_called_class() != self::class;
    }
}
