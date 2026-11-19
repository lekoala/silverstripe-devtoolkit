<?php

namespace LeKoala\DevToolkit;

use SilverStripe\Core\ClassInfo;
use SilverStripe\ORM\DataObject;
use SilverStripe\Control\Director;
use SilverStripe\Core\Environment;
use SilverStripe\PolyExecution\PolyOutput;
use Symfony\Component\Console\Input\InputInterface;

/**
 * Makes your life easier with build tasks
 */
trait BuildTaskTools
{
    protected ?InputInterface $taskInput = null;

    protected ?PolyOutput $taskOutput = null;

    /**
     * Set the PolyCommand input and output for this task execution.
     */
    protected function setTaskContext(InputInterface $input, PolyOutput $output): void
    {
        $this->taskInput = $input;
        $this->taskOutput = $output;
    }

    /**
     * Read a task option from the current PolyCommand input.
     */
    protected function taskOption(string $name, mixed $default = null): mixed
    {
        if (!$this->taskInput) {
            return $default;
        }

        $value = $this->taskInput->getOption($name);
        return $value ?? $default;
    }

    /**
     * Increase time limit
     *
     * @return void
     */
    protected function increaseTimeLimitTo(?int $timeLimit = null): void
    {
        Environment::increaseTimeLimitTo($timeLimit);
        if (!$timeLimit) {
            $this->message("Time limit is disabled", "info");
        } else {
            $this->message("Time limit has been set to $timeLimit seconds", "info");
        }
    }

    /**
     * All dataobjects
     *
     * @return array
     */
    protected function getValidDataObjects(): array
    {
        $list = ClassInfo::getValidSubClasses(DataObject::class);
        array_shift($list);
        return $list;
    }

    protected function message(mixed $message, string $type = 'default'): void
    {
        if (!$this->taskOutput) {
            throw new \LogicException('Task context has not been initialised.');
        }

        if (!is_string($message)) {
            $message = json_encode($message, JSON_UNESCAPED_SLASHES) ?: print_r($message, true);
        }
        $prefix = match ($type) {
            'success', 'created', 'changed', 'repaired' => '+ ',
            'error', 'bad', 'obsolete', 'deleted' => '- ',
            'notice' => '! ',
            default => '',
        };
        $this->taskOutput->writeln($prefix . strip_tags($message));
    }

    protected function isDev(): bool
    {
        return Director::isDev();
    }

    protected function isLive(): bool
    {
        return Director::isLive();
    }
}
