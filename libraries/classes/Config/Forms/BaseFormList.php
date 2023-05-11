<?php
/**
 * User preferences form
 */

declare(strict_types=1);

namespace PhpMyAdmin\Config\Forms;

use PhpMyAdmin\Config\ConfigFile;

use function array_key_exists;
use function array_keys;
use function array_merge;

class BaseFormList
{
    /**
     * List of all forms
     *
     * @var array<string, class-string<BaseForm>>
     */
    protected static array $all = [];

    /** @var BaseForm[] */
    private array $forms = [];

    /** @return string[] */
    public static function getAllFormNames(): array
    {
        return array_keys(static::$all);
    }

    /** @param string $name Name */
    public static function isValid(string $name): bool
    {
        return array_key_exists($name, static::$all);
    }

    /**
     * @param string $name Name
     *
     * @psalm-return class-string<BaseForm>|null
     */
    public static function get(string $name): string|null
    {
        if (static::isValid($name)) {
            return static::$all[$name];
        }

        return null;
    }

    /** @param ConfigFile $cf Config file instance */
    final public function __construct(ConfigFile $cf)
    {
        foreach (static::$all as $class) {
            $this->forms[] = new $class($cf);
        }
    }

    /**
     * Processes forms, returns true on successful save
     *
     * @param bool $allowPartialSave allows for partial form saving
     *                               on failed validation
     * @param bool $checkFormSubmit  whether check for $_POST['submit_save']
     */
    public function process(bool $allowPartialSave = true, bool $checkFormSubmit = true): bool
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
    public function displayErrors(): string
    {
        $ret = '';
        foreach ($this->forms as $form) {
            $ret .= $form->displayErrors();
        }

        return $ret;
    }

    /**
     * Reverts erroneous fields to their default values
     */
    public function fixErrors(): void
    {
        foreach ($this->forms as $form) {
            $form->fixErrors();
        }
    }

    /**
     * Tells whether form validation failed
     */
    public function hasErrors(): bool
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
    public static function getFields(): array
    {
        $names = [];
        foreach (static::$all as $class) {
            $names = array_merge($names, $class::getFields());
        }

        return $names;
    }
}
