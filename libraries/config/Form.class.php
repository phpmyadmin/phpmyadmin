<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Form handling code.
 *
 * @package PhpMyAdmin
 */

/**
 * Base class for forms, loads default configuration options, checks allowed
 * values etc.
 *
 * @package PhpMyAdmin
 */
class Form
{
    /**
     * Form name
     * @var string
     */
    public $name;

    /**
     * Arbitrary index, doesn't affect class' behavior
     * @var int
     */
    public $index;

    /**
     * Form fields (paths), filled by {@link readFormPaths()}, indexed by field name
     * @var array
     */
    public $fields;

    /**
     * Stores default values for some fields (eg. pmadb tables)
     * @var array
     */
    public $default;

    /**
     * Caches field types, indexed by field names
     * @var array
     */
    private $_fieldsTypes;

    /**
     * ConfigFile instance
     * @var ConfigFile
     */
    private $_configFile;

    /**
     * Constructor, reads default config values
     *
     * @param string     $form_name Form name
     * @param array      $form      Form data
     * @param ConfigFile $cf        Config file instance
     * @param int        $index     arbitrary index, stored in Form::$index
     */
    public function __construct(
        $form_name, array $form, ConfigFile $cf, $index = null
    ) {
        $this->index = $index;
        $this->_configFile = $cf;
        $this->loadForm($form_name, $form);
    }

    /**
     * Returns type of given option
     *
     * @param string $option_name path or field name
     *
     * @return string|null  one of: boolean, integer, double, string, select, array
     */
    public function getOptionType($option_name)
    {
        $key = ltrim(substr($option_name, strrpos($option_name, '/')), '/');
        return isset($this->_fieldsTypes[$key])
            ? $this->_fieldsTypes[$key]
            : null;
    }

    /**
     * Returns allowed values for select fields
     *
     * @param string $option_path
     *
     * @return array
     */
    public function getOptionValueList($option_path)
    {
        $value = $this->_configFile->getDbEntry($option_path);
        if ($value === null) {
            trigger_error("$option_path - select options not defined", E_USER_ERROR);
            return array();
        }
        if (!is_array($value)) {
            trigger_error("$option_path - not a static value list", E_USER_ERROR);
            return array();
        }
        // convert array('#', 'a', 'b') to array('a', 'b')
        if (isset($value[0]) && $value[0] === '#') {
            // remove first element ('#')
            array_shift($value);
        } else {
            // convert value list array('a', 'b') to array('a' => 'a', 'b' => 'b')
            $has_string_keys = false;
            $keys = array();
            for ($i = 0; $i < count($value); $i++) {
                if (!isset($value[$i])) {
                    $has_string_keys = true;
                    break;
                }
                $keys[] = is_bool($value[$i]) ? (int)$value[$i] : $value[$i];
            }
            if (! $has_string_keys) {
                $value = array_combine($keys, $value);
            }
        }

        // $value has keys and value names, return it
        return $value;
    }

    /**
     * array_walk callback function, reads path of form fields from
     * array (see file comment in setup.forms.php or user_preferences.forms.inc)
     *
     * @param mixed $value
     * @param mixed $key
     * @param mixed $prefix
     *
     * @return void
     */
    private function _readFormPathsCallback($value, $key, $prefix)
    {
        static $group_counter = 0;

        if (is_array($value)) {
            $prefix .= $key . '/';
            array_walk($value, array($this, '_readFormPathsCallback'), $prefix);
        } else {
            if (!is_int($key)) {
                $this->default[$prefix . $key] = $value;
                $value = $key;
            }
            // add unique id to group ends
            if ($value == ':group:end') {
                $value .= ':' . $group_counter++;
            }
            $this->fields[] = $prefix . $value;
        }
    }

    /**
     * Reads form paths to {@link $fields}
     *
     * @param array $form
     *
     * @return void
     */
    protected function readFormPaths($form)
    {
        // flatten form fields' paths and save them to $fields
        $this->fields = array();
        array_walk($form, array($this, '_readFormPathsCallback'), '');

        // $this->fields is an array of the form: [0..n] => 'field path'
        // change numeric indexes to contain field names (last part of the path)
        $paths = $this->fields;
        $this->fields = array();
        foreach ($paths as $path) {
            $key = ltrim(substr($path, strrpos($path, '/')), '/');
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
            if (strpos($name, ':group:') === 0) {
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
     * @param string $form_name
     * @param array  $form
     *
     * @return void
     */
    public function loadForm($form_name, $form)
    {
        $this->name = $form_name;
        $this->readFormPaths($form);
        $this->readTypes();
    }
}
?>
