<?php
/**
 * User preferences form
 */

declare(strict_types=1);

namespace PhpMyAdmin\Config\Forms;

use PhpMyAdmin\Config\ConfigFile;
use function array_merge;
use function in_array;

class BaseFormList
{
    /**
     * List of all forms
     *
     * @var array
     */
    protected static $all = [];

    /** @var string */
    protected static $ns = 'PhpMyAdmin\\Config\\Forms\\';

    /** @var array */
    private $forms;

    /**
     * @return array
     */
    public static function getAll()
    {
        return static::$all;
    }

    /**
     * @param string $name Name
     *
     * @return bool
     */
    public static function isValid($name)
    {
        return in_array($name, static::$all);
    }

    /**
     * @param string $name Name
     *
     * @return string|null
     */
    public static function get($name)
    {
        if (static::isValid($name)) {
            return static::$ns . $name . 'Form';
        }

        return null;
    }

    /**
     * @param ConfigFile $cf Config file instance
     */
    public function __construct(ConfigFile $cf)
    {
        $this->forms = [];
        foreach (static::$all as $form) {
            $class = static::get($form);
            $this->forms[] = new $class($cf);
        }
    }

    /**
     * Processes forms, returns true on successful save
     *
     * @param bool $allowPartialSave allows for partial form saving
     *                               on failed validation
     * @param bool $checkFormSubmit  whether check for $_POST['submit_save']
     *
     * @return bool whether processing was successful
     */
    public function process($allowPartialSave = true, $checkFormSubmit = true)
    {
        $ret = true;
        foreach ($this->forms as $form) {
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
        foreach ($this->forms as $form) {
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
        foreach ($this->forms as $form) {
            $form->fixErrors();
        }
    }

    /**
     * Tells whether form validation failed
     *
     * @return bool
     */
    public function hasErrors()
    {
        $ret = false;
        foreach ($this->forms as $form) {
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
