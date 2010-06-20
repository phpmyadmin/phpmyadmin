<?php
/**
 * Form handling code.
 *
 * @package    phpMyAdmin
 * @license    http://www.gnu.org/licenses/gpl.html GNU GPL 2.0
 */

/**
 * Base class for forms, loads default configuration options, checks allowed
 * values etc.
 *
 * @package    phpMyAdmin-setup
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
    private $fieldsTypes;

    /**
     * Constructor, reads default config values
     *
     * @param string  $form_name
     * @param array   $form
     * @param int     $index      arbitrary index, stored in Form::$index
     */
    public function __construct($form_name, array $form, $index = null)
    {
        $this->index = $index;
        $this->loadForm($form_name, $form);
    }

    /**
     * Returns type of given option
     *
     * @param   string  $option_name path or field name
     * @return  string|null  one of: boolean, integer, double, string, select, array
     */
    public function getOptionType($option_name)
    {
        $key = ltrim(substr($option_name, strrpos($option_name, '/')), '/');
        return isset($this->fieldsTypes[$key])
            ? $this->fieldsTypes[$key]
            : null;
    }

    /**
     * Returns allowed values for select fields
     *
     * @param   string  $option_path
     * @return  array
     */
    public function getOptionValueList($option_path)
    {
        $value = ConfigFile::getInstance()->getDbEntry($option_path);
        if ($value === null) {
            trigger_error("$option_path - select options not defined", E_USER_ERROR);
            return array();
        }
        if (!is_array($value)) {
            trigger_error("$option_path - not a static value list", E_USER_ERROR);
            return array();
        }
        return $value;
    }

    /**
     * array_walk callback function, reads path of form fields from
     * array (see file comment in setup.forms.php or user_preferences.forms.inc)
     *
     * @param   mixed   $value
     * @param   mixed   $key
     * @param   mixed   $prefix
     */
    private function _readFormPathsCallback($value, $key, $prefix)
    {
        if (is_array($value)) {
            $prefix .= $key . '/';
            array_walk($value, array($this, '_readFormPathsCallback'), $prefix);
        } else {
            if (!is_int($key)) {
                $this->default[$prefix . $key] = $value;
                $value = $key;
            }
            $this->fields[] = $prefix . $value;
        }
    }

    /**
     * Reads form paths to {@link $fields}
     *
     * @param array $form
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
     * Reads fields' types to $this->fieldsTypes
     */
    protected function readTypes()
    {
        $cf = ConfigFile::getInstance();
        foreach ($this->fields as $name => $path) {
            $v = $cf->getDbEntry($path);
            if ($v !== null) {
                $type = is_array($v) ? 'select' : $v;
            } else {
                $type = gettype($cf->getDefault($path));
            }
            $this->fieldsTypes[$name] = $type;
        }
    }

    /**
     * Reads form settings and prepares class to work with given subset of
     * config file
     *
     * @param string $form_name
     * @param array  $form
     */
    public function loadForm($form_name, $form)
    {
        $this->name = $form_name;
        $this->readFormPaths($form);
        $this->readTypes();
    }
}
?>