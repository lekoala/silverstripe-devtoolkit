<?php

class FastDatabaseAdmin extends DatabaseAdmin
{
    private static $allowed_actions = array(
        'index',
        'build',
        'fastbuild',
        'cleanup',
        'import'
    );

    public function fastbuild()
    {
        $this->doFastBuild();
    }


    /**
     * Updates the database schema, creating tables & fields as necessary.
     */
    public function doFastBuild()
    {
        $startTime = microtime(true);

        $conn = DB::get_conn();

        $tmpFile = TEMP_FOLDER . DIRECTORY_SEPARATOR . 'fastbuild.json';
        $previousBuild = null;
        if (is_file($tmpFile)) {
            $previousBuild = json_decode(file_get_contents($tmpFile), JSON_OBJECT_AS_ARRAY);
        }

        $buildManifest = [];

		// Assumes database class is like "MySQLDatabase" or "MSSQLDatabase" (suffixed with "Database")
        $dbType = substr(get_class($conn), 0, -8);
        $dbVersion = $conn->getVersion();
        $databaseName = (method_exists($conn, 'currentDatabase')) ? $conn->getSelectedDatabase() : "";

        $this->msg(sprintf("Fast building database %s using %s %s", $databaseName, $dbType, $dbVersion));
        $this->msg("");

		// Set up the initial database
        if (!DB::is_active()) {
            die('Database must be created using a regular dev/build');
        }

		// Build the database.  Most of the hard work is handled by DataObject
        $dataClasses = ClassInfo::subclassesFor('DataObject');
        array_shift($dataClasses);

        $this->msg("Creating database tables");

		// Initiate schema update
        $dbSchema = DB::get_schema();
        $buildManifest['time'] = time();
        $buildManifest['datetime'] = date('Y-m-d H:i:s');
        $buildManifest['tableList'] = $dbSchema->tableList();

        $dbSchema->schemaUpdate(function () use ($dataClasses, &$buildManifest, $previousBuild) {
            $schemaStartTime = microtime(true);

            $log = [];
            $required = [];

            // This loop is slow as hell...
            foreach ($dataClasses as $dataClass) {
				// Check if class exists before trying to instantiate - this sidesteps any manifest weirdness
                if (!class_exists($dataClass)) {
                    $log[$dataClass] = "class_does_not_exist";
                    continue;
                };

				// Check if this class should be excluded as per testing conventions
                $SNG = singleton($dataClass);
                if ($SNG instanceof TestOnly) {
                    $log[$dataClass] = "test_only";
                    continue;
                };

				// Log data
                $this->msg($dataClass, "list");

                // Instruct the class to apply its schema to the database
                $lastRequireTime = null;
                if ($previousBuild) {
                    if (isset($previousBuild['required'][$dataClass])) {
                        $lastRequireTime = $previousBuild['required'][$dataClass];
                    }
                }

                // It should be the last file included...
                $includedFiles = get_included_files();
                $filename = array_pop($includedFiles);
                // .. but maybe not! (eg: file is misnamed or multiple classes in a file)
                if(strpos($filename, $dataClass) === false) {
                    $checkTime = null;
                }
                else {
                    $checkTime = $this->getCorrectMTime($filename);
                }
                if ($lastRequireTime && $checkTime && $lastRequireTime > $checkTime) {
                    $log[$dataClass] = "skipped";
                    $required[$dataClass] = $lastRequireTime;
                } else {
                    $SNG->requireTable();
                    $log[$dataClass] = "required";
                    $required[$dataClass] = time();
                }

            }

            $buildManifest['operations'] = $log;
            $buildManifest['required'] = $required;

            $schemaEndTime = microtime(true);
            $time = sprintf("%.6f seconds", $schemaEndTime - $schemaStartTime);

            $msg = "\nRequired tables were defined in $time";
            $this->msg($msg);
        });
        ClassInfo::reset_db_cache();

        file_put_contents($tmpFile, json_encode($buildManifest, JSON_PRETTY_PRINT));

        $endTime = microtime(true);
        $time = sprintf("%.6f seconds", $endTime - $startTime);

        $msg = "\n\nDatabase build completed in $time";
        $this->msg($msg);

        ClassInfo::reset_db_cache();
    }

    protected function getCorrectMTime($filePath)
    {

        $time = filemtime($filePath);

        $isDST = (date('I', $time) == 1);
        $systemDST = (date('I') == 1);

        $adjustment = 0;

        if ($isDST == false && $systemDST == true)
            $adjustment = 3600;

        else if ($isDST == true && $systemDST == false)
            $adjustment = -3600;

        else
            $adjustment = 0;

        return ($time + $adjustment);
    }

    protected function msg($msg, $type = "")
    {
        if (Director::is_cli()) {
            if ($type == "list") {
                $msg = " * $msg";
            }
            echo $msg . "\n";
        } else {
            $msg = nl2br($msg);
            if ($type == "list") {
                echo "<li>$msg</li>";
            } else {
                echo $msg . "<br/>";
            }
        }

    }
}