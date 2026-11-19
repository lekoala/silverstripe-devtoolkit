<?php

namespace LeKoala\DevToolkit\Tasks;

use LeKoala\DevToolkit\BuildTaskTools;
use SilverStripe\ORM\DB;
use SilverStripe\Assets\Image;
use SilverStripe\Dev\BuildTask;
use SilverStripe\Versioned\Versioned;
use SilverStripe\Assets\Storage\Sha1FileHashingService;
use SilverStripe\PolyExecution\PolyOutput;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;

/**
 * In case images are resized by an external utility
 * file hash is not valid anymore
 *
 * We need a way to rehash them
 */
class RehashImagesTask extends BuildTask
{
    use BuildTaskTools;

    protected static string $commandName = 'rehash-images';
    protected string $title = 'Rehash Images';
    protected static string $description = 'Recompute image file hashes and remove missing image records.';

    protected function execute(InputInterface $input, PolyOutput $output): int
    {
        $this->setTaskContext($input, $output);
        $go = (bool) $this->taskOption('go');

        $service = new Sha1FileHashingService();

        $images = Image::get();
        $tofix = $todelete = 0;
        foreach ($images as $image) {
            $hash = $image->getHash();

            $stream  = $image->getStream();
            if (!$stream) {
                $filename = $image->getFilename();
                $location = ASSETS_PATH . '/' . $filename;
                if (!is_file($location)) {
                    $todelete++;
                    if ($go) {
                        $this->message("Deleted file " . $image->ID . " since the file was not found");
                        $image->delete();
                    } else {
                        $this->message("Could not read file at $location. It would be deleted by this task.", "bad");
                    }
                    continue;
                }
                $stream = fopen($location, 'rb');
            }

            $fullhash = $service->computeFromStream($stream);

            if ($hash != $fullhash) {
                $tofix++;
                if ($go) {
                    DB::query("UPDATE File SET FileHash = '" . $fullhash . "' WHERE ID = " . $image->ID);
                    DB::query("UPDATE File_Live SET FileHash = '" . $fullhash . "' WHERE ID = " . $image->ID);
                    $this->message($image->ID . " has been fixed");
                } else {
                    $this->message($image->ID . " hash mismatch : $hash vs $fullhash. This task can fix this hash.");
                }
            }
        }

        if (!$tofix && !$todelete) {
            $this->message("All files are good", "good");
        }

        if (!$go) {
            $this->message('Dry run, pass --go to repair file hashes and remove missing records.', 'notice');
        }

        return Command::SUCCESS;
    }

    /**
     * @return array<InputOption>
     */
    public function getOptions(): array
    {
        return [
            new InputOption('go', null, InputOption::VALUE_NONE, 'Apply hash repairs and deletions'),
        ];
    }
}
