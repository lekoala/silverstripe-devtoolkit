<?php

/**
 * Adds an "Fast Export" button to the bottom of a {@link GridField}.
 * 
 * It performs a raw query on the table instead of trying to iterate over a list of objects
 */
class FastExportButton implements
    GridField_HTMLProvider,
    GridField_ActionProvider,
    GridField_URLHandler
{

    /**
     * @var string
     */
    protected $csvSeparator = ",";

    /**
     * @var array Map of a property name on the exported objects, with values being the column title in the file.
     * Note that titles are only used when {@link $hasHeader} is set to TRUE.
     */
    protected $exportColumns;

    /**
     * Fragment to write the button to
     */
    protected $targetFragment;

    /**
     * @var boolean
     */
    protected $hasHeader = true;

    /**
     * @var string
     */
    protected $exportName = null;

    /**
     *
     * @var string
     */
    protected $buttonTitle = null;

    /**
     *
     * @var array
     */
    protected $listFilters = array();

    /**
     * Static instance counter to allow multiple instances to work together
     * @var int
     */
    protected static $instances = 0;

    /**
     * Current instance count
     * @var int
     */
    protected $instance;

    /**
     * @param string $targetFragment The HTML fragment to write the button into
     * @param array $exportColumns The columns to include in the export
     */
    public function __construct($targetFragment = "after", $exportColumns = null)
    {
        $this->targetFragment = $targetFragment;
        $this->exportColumns = $exportColumns;
        self::$instances++;
        $this->instance = self::$instances;
    }

    public function getActionName()
    {
        return 'fastexport_' . $this->instance;
    }

    /**
     * Place the export button in a <p> tag below the field
     */
    public function getHTMLFragments($gridField)
    {
        $title = $this->buttonTitle ? $this->buttonTitle : _t(
            'TableListField.FASTEXPORT',
            'Fast Export'
        );

        $name = $this->getActionName();

        $button = new GridField_FormAction(
            $gridField,
            $name,
            $title,
            $name,
            null
        );
        $button->addExtraClass('no-ajax action_export');
        $button->setForm($gridField->getForm());

        return array(
            $this->targetFragment => '<p class="grid-fastexport-button">' . $button->Field() . '</p>',
        );
    }

    /**
     * export is an action button
     */
    public function getActions($gridField)
    {
        return array($this->getActionName());
    }

    public function handleAction(
        GridField $gridField,
        $actionName,
        $arguments,
        $data
    ) {
        if (in_array($actionName, $this->getActions($gridField))) {
            return $this->handleExport($gridField);
        }
    }

    /**
     * it is also a URL
     */
    public function getURLHandlers($gridField)
    {
        return array($this->getActionName() => 'handleExport');
    }

    /**
     * Handle the export, for both the action button and the URL
     */
    public function handleExport($gridField, $request = null)
    {
        $now = Date("Ymd_Hi");

        if ($fileData = $this->generateExportFileData($gridField)) {
            $name = $this->exportName;
            $ext = 'csv';
            $fileName = "$name-$now.$ext";

            return SS_HTTPRequest::send_file($fileData, $fileName, 'text/csv');
        }
    }

    public static function allFieldsForClass($class)
    {
        $dataClasses = ClassInfo::dataClassesFor($class);
        $fields = array();
        foreach ($dataClasses as $dataClass) {
            $databaseFields = DataObject::database_fields($dataClass);

            $dataFields = [];
            foreach ($databaseFields as $name => $type) {
                if ($type == 'Text' || $type == 'HTMLText') {
                    continue;
                }
                $dataFields[] = $name;
            }
            $fields = array_merge(
                $fields,
                $dataFields
            );
        }
        return array_combine($fields, $fields);
    }

    public static function exportFieldsForClass($class)
    {
        $singl = singleton($class);
        if ($singl->hasMethod('exportedFields')) {
            return $singl->exportedFields();
        }
        $exportedFields = Config::inst()->get($class, 'exported_fields');
        if (!$exportedFields) {
            $exportedFields = array_keys(self::allFieldsForClass($class));
        }
        $unexportedFields = Config::inst()->get($class, 'unexported_fields');
        if ($unexportedFields) {
            $exportedFields = array_diff($exportedFields, $unexportedFields);
        }
        return array_combine($exportedFields, $exportedFields);
    }

    /**
     * Generate export fields
     *
     * @param GridField $gridField
     * @return string
     */
    public function generateExportFileData($gridField)
    {
        $class = $gridField->getModelClass();
        $columns = ($this->exportColumns) ? $this->exportColumns : self::exportFieldsForClass($class);
        $fileData = '';

        // If we don't have an associative array
        if (!ArrayLib::is_associative($columns)) {
            array_combine($columns, $columns);
        }

        $singl = singleton($class);

        $singular = $class ? $singl->i18n_singular_name() : '';
        $plural = $class ? $singl->i18n_plural_name() : '';

        $filter = new FileNameFilter;
        if ($this->exportName) {
            $this->exportName = $filter->filter($this->exportName);
        } else {
            $this->exportName = $filter->filter('fastexport-' . $plural);
        }

        $fileData = '';
        $separator = $this->csvSeparator;

        $class = $gridField->getModelClass();
        $singl = singleton($class);
        $baseTable = $singl->baseTable();

        $stream = fopen('data://text/plain,' . "", 'w+');

        // Filter columns
        $sqlFields = [];
        $baseFields = ['ID', 'Created', 'LastEdited'];

        $joins = [];
        $isSubsite = false;
        $map = [];
        if ($singl->hasMethod('fastExportMap')) {
            $map = $singl->fastExportMap();
        }
        foreach ($columns as $columnSource => $columnHeader) {
            // Allow mapping methods to plain fields
            if ($map && isset($map[$columnSource])) {
                $columnSource = $map[$columnSource];
            }
            if ($columnSource == 'SubsiteID') {
                $isSubsite = true;
            }
            if (in_array($columnSource, $baseFields)) {
                $sqlFields[] = $baseTable . '.' . $columnSource;
                continue;
            }
            // Naive join support
            if (strpos($columnSource, '.') !== false) {
                $parts = explode('.', $columnSource);

                $joinSingl = singleton($parts[0]);
                $joinBaseTable = $joinSingl->baseTable();

                if (!isset($joins[$joinBaseTable])) {
                    $joins[$joinBaseTable] = [];
                }
                $joins[$joinBaseTable][] = $parts[1];

                $sqlFields[] = $joinBaseTable . '.' . $parts[1];
                continue;
            }
            $fieldTable = ClassInfo::table_for_object_field($class, $columnSource);
            if ($fieldTable != $baseTable || !$fieldTable) {
                unset($columns[$columnSource]);
            } else {
                $sqlFields[] = $fieldTable . '.' . $columnSource;
            }
        }

        if ($this->hasHeader) {
            $headers = array();

            // determine the headers. If a field is callable (e.g. anonymous function) then use the
            // source name as the header instead
            foreach ($columns as $columnSource => $columnHeader) {
                $headers[] = (!is_string($columnHeader) && is_callable($columnHeader))
                    ? $columnSource : $columnHeader;
            }

            $row = array_values($headers);
            // fputcsv($stream, $row, $separator);

             // force quotes
             fputs($stream, implode(",", array_map("self::encodeFunc", $row)) . "\n");
        }

        if (empty($sqlFields)) {
            $sqlFields = ['ID', 'Created', 'LastEdited'];
        }

        $where = [];
        $sql = 'SELECT ' . implode(',', $sqlFields) . ' FROM ' . $baseTable;
        foreach ($joins as $joinTable => $joinFields) {
            $foreignKey = $joinTable . 'ID';
            $sql .= ' LEFT JOIN ' . $joinTable . ' ON ' . $joinTable . '.ID = ' . $baseTable . '.' . $foreignKey;
        }
        // Basic subsite support
        if ($isSubsite && class_exists('Subsite') && Subsite::currentSubsiteID()) {
            $where[] = $baseTable . '.SubsiteID = ' . Subsite::currentSubsiteID();
        }

        $singl->extend('updateFastExport', $sql, $where);

        // Basic where clause
        if (!empty($where)) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }

        $query = DB::query($sql);

        foreach ($query as $row) {
            // fputcsv($stream, $row, $separator);

            // force quotes
            fputs($stream, implode(",", array_map("self::encodeFunc", $row)) . "\n");
        }

        rewind($stream);
        $fileData = stream_get_contents($stream);
        fclose($stream);

        return $fileData;
    }

    public static function encodeFunc($value)
    {
        ///remove any ESCAPED double quotes within string.
        $value = str_replace('\\"', '"', $value);
        //then force escape these same double quotes And Any UNESCAPED Ones.
        $value = str_replace('"', '\"', $value);
        //force wrap value in quotes and return
        return '"' . $value . '"';
    }

    /**
     * @return array
     */
    public function getExportColumns()
    {
        return $this->exportColumns;
    }

    /**
     * @param array
     */
    public function setExportColumns($cols)
    {
        $this->exportColumns = $cols;
        return $this;
    }

    /**
     * @return boolean
     */
    public function getHasHeader()
    {
        return $this->hasHeader;
    }

    /**
     * @param boolean
     */
    public function setHasHeader($bool)
    {
        $this->hasHeader = $bool;
        return $this;
    }

    /**
     * @return string
     */
    public function getExportName()
    {
        return $this->exportName;
    }

    /**
     * @param string $exportName
     * @return \$this
     */
    public function setExportName($exportName)
    {
        $this->exportName = $exportName;
        return $this;
    }

    /**
     * @return string
     */
    public function getButtonTitle()
    {
        return $this->buttonTitle;
    }

    /**
     * @param string $buttonTitle
     * @return \$this
     */
    public function setButtonTitle($buttonTitle)
    {
        $this->buttonTitle = $buttonTitle;
        return $this;
    }

    /**
     * @return string
     */
    public function getCsvSeparator()
    {
        return $this->csvSeparator;
    }

    /**
     * @param string
     */
    public function setCsvSeparator($separator)
    {
        $this->csvSeparator = $separator;
        return $this;
    }
}