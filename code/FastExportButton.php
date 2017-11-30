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
    )
    {
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
            $fields = array_merge(
                $fields,
                array_keys($databaseFields)
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
        $table = $singl->baseTable();

        $stream = fopen('data://text/plain,' . "", 'w+');

        // Filter columns
        $sqlFields = [];
        foreach ($columns as $columnSource => $columnHeader) {
            $table = ClassInfo::table_for_object_field($class, $columnSource);
            if($table == 'DataObject' || !$table) {
                unset($columns[$columnSource]);
            } else {
                $sqlFields[] = $table . '.' . $columnSource;
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

            fputcsv($stream, array_values($headers), $separator);
        }

        $sql = 'SELECT ' . implode(',', $sqlFields) . ' FROM ' . $table;
        $query = DB::query($sql);

        foreach ($query as $row) {
            fputcsv($stream, $row, $separator);
        }

        rewind($stream);
        $fileData = stream_get_contents($stream);
        fclose($stream);

        return $fileData;
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