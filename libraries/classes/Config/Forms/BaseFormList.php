<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * User preferences form
 *
 * @package PhpMyAdmin
 */
declare(strict_types=1);

namespace PhpMyAdmin\Config\Forms;

use PhpMyAdmin\Config\ConfigFile;

/**
 * Class BaseFormList
 * @package PhpMyAdmin\Config\Forms
 */
class BaseFormList
{
    /**
     * List of all forms
     */
    protected static $all = [];

    /**
     * @var string
     */
    protected static $ns = 'PhpMyAdmin\\Config\\Forms\\';

    /**
     * @var array
     */
    private $_forms;

    /**
     * @return array
     */
    public static function getAll()
    {
        return static::$all;
    }

    /**
     * @param string $name Name
     * @return bool
     */
    public static function isValid($name)
    {
        return in_array($name, static::$all);
    }

    /**
     * @param string $name Name
     * @return null|string
     */
    public static function get($name)
    {
        if (static::isValid($name)) {
            return static::$ns . $name . 'Form';
        }
        return null;
    }

    /**
     * Constructor
     *
     * @param ConfigFile $cf Config file instance
     */
    public function __construct(ConfigFile $cf)
    {
        $this->_forms = [];
        foreach (static::$all as $form) {
            $class = static::get($form);
            $this->_forms[] = new $class($cf);
        }
    }

    /**
     * Processes forms, returns true on successful save
     *
     * @param bool $allowPartialSave allows for partial form saving
     *                               on failed validation
     * @param bool $checkFormSubmit  whether check for $_POST['submit_save']
     *
     * @return boolean whether processing was successful
     */
    public function process($allowPartialSave = true, $checkFormSubmit = true)
    {
        $ret = true;
        foreach ($this->_forms as $form) {
            $ret = $ret && $form->process($allowPartialSave, $checkFormSubmit);
        }
        return $ret;
    }

    /**
     * Displays errors
     *
     * @return string HTML for errors
     */
    public function displayErrors()
    {
        $ret = '';
        foreach ($this->_forms as $form) {
            $ret .= $form->displayErrors();
        }
        return $ret;
    }

    /**
     * Reverts erroneous fields to their default values
     *
     * @return void
     */
    public function fixErrors()
    {
        foreach ($this->_forms as $form) {
            $form->fixErrors();
        }
    }

    /**
     * Tells whether form validation failed
     *
     * @return boolean
     */
    public function hasErrors()
    {
        $ret = false;
        foreach ($this->_forms as $form) {
            $ret = $ret || $form->hasErrors();
        }
        return $ret;
    }

    /**
     * Returns list of fields used in the form.
     *
     * @return string[]
     */
    public static function getFields()
    {
        $names = [];
        foreach (static::$all as $form) {
            $class = static::get($form);
            $names = array_merge($names, $class::getFields());
        }
        return $names;
    }
}
