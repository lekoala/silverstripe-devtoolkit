<?php

namespace LeKoala\DevToolkit\Helpers;

/**
 * Helper class to find spaces in your current scope that break everything in ajax requests...
 */
class EmptySpaceFinder
{
    const REGEX_OPENING = '/^[\s]+<\?php/';
    const REGEX_CLOSING = '/\?>[\s]+$/';

    public static function findSpacesInFiles($files)
    {
        echo '<pre>';
        echo "Finding opened or closed tags ...\n\n";

        $openings = [];
        $closings = [];
        foreach ($files as $file) {
            $content = file_get_contents($file);

            $matches = null;
            preg_match_all(self::REGEX_OPENING, $content, $matches);

            if (!empty($matches[0])) {
                $openings[] = $file;
            }

            $matches = null;
            preg_match_all(self::REGEX_CLOSING, $content, $matches);

            if (!empty($matches[0])) {
                $closings[] = $file;
            }
        }

        if (!empty($openings)) {
            echo "Files with opening tags that may need fixing\n";
            foreach ($openings as $file) {
                echo "$file\n";
            }
            echo "***\n";
        }
        if (!empty($closings)) {
            echo "Files with closing tags that may need fixing\n";
            foreach ($closings as $file) {
                echo "$file\n";
            }
            echo "***\n";
        }

        echo "\nDone!";
        echo '</pre>';
        die();
    }

    public static function findSpacesInIncludedFiles()
    {
        $files = get_included_files();
        self::findSpacesInFiles($files);
    }
}
