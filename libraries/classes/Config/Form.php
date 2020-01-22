<?php
/**
 * Form handling code.
 */
declare(strict_types=1);

namespace PhpMyAdmin\Config;

use function array_combine;
use function array_shift;
use function array_walk;
use function count;
use function is_array;
use function is_bool;
use function is_int;
use function ltrim;
use function mb_strrpos;
use function mb_substr;
use function trigger_error;
use const E_USER_ERROR;
use function mb_strpos;

/**
 * Base class for forms, loads default configuration options, checks allowed
 * values etc.
 */
class Form
{
    /**
     * Form name
     *
     * @var string
     */
    public $name;

    /**
     * Arbitrary index, doesn't affect class' behavior
     *
     * @var int
     */
    public $index;

    /**
     * Form fields (paths), filled by {@link readFormPaths()}, indexed by field name
     *
     * @var array
     */
    public $fields;

    /**
     * Stores default values for some fields (eg. pmadb tables)
     *
     * @var array
     */
    public $default;

    /**
     * Caches field types, indexed by field names
     *
     * @var array
     */
    private $_fieldsTypes;

    /**
     * ConfigFile instance
     *
     * @var ConfigFile
     */
    private $_configFile;

    /**
     * Reads default config values
     *
     * @param string     $formName Form name
     * @param array      $form     Form data
     * @param ConfigFile $cf       Config file instance
     * @param int        $index    arbitrary index, stored in Form::$index
     */
    public function __construct(
        $formName,
        array $form,
        ConfigFile $cf,
        $index = null
    ) {
        $this->index = $index;
        $this->_configFile = $cf;
        $this->loadForm($formName, $form);
    }

    /**
     * Returns type of given option
     *
     * @param string $optionName path or field name
     *
     * @return string|null one of: boolean, integer, double, string, select, array
     */
    public function getOptionType($optionName)
    {
        $key = ltrim(
            mb_substr(
                $optionName,
                (int) mb_strrpos($optionName, '/')
            ),
            '/'
        );
        return $this->_fieldsTypes[$key] ?? null;
    }

    /**
     * Returns allowed values for select fields
     *
     * @param string $optionPath Option path
     *
     * @return array
     */
    public function getOptionValueList($optionPath)
    {
        $value = $this->_configFile->getDbEntry($optionPath);
        if ($value === null) {
            trigger_error($optionPath . ' - select options not defined', E_USER_ERROR);
            return [];
        }
        if (! is_array($value)) {
            trigger_error($optionPath . ' - not a static value list', E_USER_ERROR);
            return [];
        }
        // convert array('#', 'a', 'b') to array('a', 'b')
        if (isset($value[0]) && $value[0] === '#') {
            // remove first element ('#')
            array_shift($value);
            // $value has keys and value names, return it
            return $value;
        }

        // convert value list array('a', 'b') to array('a' => 'a', 'b' => 'b')
        $hasStringKeys = false;
        $keys = [];
        for ($i = 0, $nb = count($value); $i < $nb; $i++) {
            if (! isset($value[$i])) {
                $hasStringKeys = true;
                break;
            }
            $keys[] = is_bool($value[$i]) ? (int) $value[$i] : $value[$i];
        }
        if (! $hasStringKeys) {
            $value = array_combine($keys, $value);
        }

        // $value has keys and value names, return it
        return $value;
    }

    /**
     * array_walk callback function, reads path of form fields from
     * array (see docs for \PhpMyAdmin\Config\Forms\BaseForm::getForms)
     *
     * @param mixed $value  Value
     * @param mixed $key    Key
     * @param mixed $prefix Prefix
     *
     * @return void
     */
    private function _readFormPathsCallback($value, $key, $prefix)
    {
        static $groupCounter = 0;

        if (is_array($value)) {
            $prefix .= $key . '/';
            array_walk($value, [$this, '_readFormPathsCallback'], $prefix);
            return;
        }

        if (! is_int($key)) {
            $this->default[$prefix . $key] = $value;
            $value = $key;
        }
        // add unique id to group ends
        if ($value == ':group:end') {
            $value .= ':' . $groupCounter++;
        }
        $this->fields[] = $prefix . $value;
    }

    /**
     * Reads form paths to {@link $fields}
     *
     * @param array $form Form
     *
     * @return void
     */
    protected function readFormPaths(array $form)
    {
        // flatten form fields' paths and save them to $fields
        $this->fields = [];
        array_walk($form, [$this, '_readFormPathsCallback'], '');

        // $this->fields is an array of the form: [0..n] => 'field path'
        // change numeric indexes to contain field names (last part of the path)
        $paths = $this->fields;
        $this->fields = [];
        foreach ($paths as $path) {
            $key = '';
            if (mb_strpos((string) $path, ':group:') === 0) {
                $key = ':group:';
            }
            $key .= ltrim(
                mb_substr($path, (int) mb_strrpos($path, '/')),
                '/'
            );
            $this->fields[$key] = $path;
        }
        // now $this->fields is an array of the form: 'field name' => 'field path'
    }

    /**
     * Reads fields' types to $this->_fieldsTypes
     *
     * @return void
     */
    protected function readTypes()
    {
        $cf = $this->_configFile;
        foreach ($this->fields as $name => $path) {
            if (mb_strpos((string) $name, ':group:') === 0) {
                $this->_fieldsTypes[$name] = 'group';
                continue;
            }
            $v = $cf->getDbEntry($path);
            if ($v !== null) {
                $type = is_array($v) ? 'select' : $v;
            } else {
                $type = gettype($cf->getDefault($path));
            }
            $this->_fieldsTypes[$name] = $type;
        }
    }

    /**
     * Reads form settings and prepares class to work with given subset of
     * config file
     *
     * @param string $formName Form name
     * @param array  $form     Form
     *
     * @return void
     */
    public function loadForm($formName, array $form)
    {
        $this->name = $formName;
        $this->readFormPaths($form);
        $this->readTypes();
    }
}
