<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * User preferences form
 *
 * @package PhpMyAdmin
 */
namespace PhpMyAdmin\Config\Forms;

use PhpMyAdmin\Config\ConfigFile;

class BaseFormList
{
    /**
     * List of all forms
     */
    protected static $all = array();

    protected static $ns = 'PhpMyAdmin\\Config\\Forms\\';

    private $_forms;

    public static function getAll()
    {
        return static::$all;
    }

    public static function isValid($name)
    {
        return in_array($name, static::$all);
    }

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
        $this->_forms = array();
        foreach (static::$all as $form) {
            $class = static::get($form);
            $this->_forms[] = new $class($cf);
        }
    }

    /**
     * Processes forms, returns true on successful save
     *
     * @param bool $allow_partial_save allows for partial form saving
     *                                 on failed validation
     * @param bool $check_form_submit  whether check for $_POST['submit_save']
     *
     * @return boolean whether processing was successful
     */
    public function process($allow_partial_save = true, $check_form_submit = true)
    {
        $ret = true;
        foreach ($this->_forms as $form) {
            $ret = $ret && $form->process($allow_partial_save, $check_form_submit);
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
