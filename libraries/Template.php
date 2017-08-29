<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * hold PMA\libraries\Template class
 *
 * @package PMA\libraries
 */
namespace PMA\libraries;

/**
 * Class Template
 *
 * Handle front end templating
 *
 * @package PMA\libraries
 */
class Template
{
    /**
     * Name of the template
     */
    protected $name = null;

    /**
     * Data associated with the template
     */
    protected $data;

    /**
     * Helper functions for the template
     */
    protected $helperFunctions;

    const BASE_PATH = 'templates/';

    /**
     * Template constructor
     *
     * @param string $name            Template name
     * @param array  $data            Variables to be provided to the template
     * @param array  $helperFunctions Helper functions to be used by template
     */
    protected function __construct($name, $data = array(), $helperFunctions = array())
    {
        $this->name = $name;
        $this->data = $data;
        $this->helperFunctions = $helperFunctions;
    }

    /**
     * Template getter
     *
     * @param string $name            Template name
     * @param array  $data            Variables to be provided to the template
     * @param array  $helperFunctions Helper functions to be used by template
     *
     * @return Template
     */
    public static function get($name, $data = array(), $helperFunctions = array())
    {
        return new Template($name, $data, $helperFunctions);
    }

    /**
     * Adds more entries to the data for this template
     *
     * @param array|string $data  containing data array or data key
     * @param string       $value containing data value
     */
    public function set($data, $value = null)
    {
        if(is_array($data) && ! $value) {
            $this->data = array_merge(
                $this->data,
                $data
            );
        } else if (is_string($data)) {
            $this->data[$data] = $value;
        }
    }

    /**
     * Adds a function for use by the template
     *
     * @param string   $funcName function name
     * @param callable $funcDef  function definition
     */
    public function setHelper($funcName, $funcDef)
    {
        if (! isset($this->helperFunctions[$funcName])) {
            $this->helperFunctions[$funcName] = $funcDef;
        } else {
            throw new \LogicException(
                'The function "' . $funcName . '" is already associated with the template.'
            );
        }
    }

    /**
     * Removes a function
     *
     * @param string $funcName function name
     */
    public function removeHelper($funcName)
    {
        if (isset($this->helperFunctions[$funcName])) {
            unset($this->helperFunctions[$funcName]);
        } else {
            throw new \LogicException(
                'The function "' . $funcName . '" is not associated with the template.'
            );
        }
    }

    /**
     * Magic call to locally inaccessible but associated helper functions
     *
     * @param string $funcName  function name
     * @param array  $arguments function arguments
     */
    public function __call($funcName, $arguments)
    {
        if (isset($this->helperFunctions[$funcName])) {
            return call_user_func_array($this->helperFunctions[$funcName], $arguments);
        } else {
            throw new \LogicException(
                'The function "' . $funcName . '" is not associated with the template.'
            );
        }
    }

    /**
     * Render template
     *
     * @param array $data            Variables to be provided to the template
     * @param array $helperFunctions Helper functions to be used by template
     *
     * @return string
     */
    public function render($data = array(), $helperFunctions = array())
    {
        $template = static::BASE_PATH . $this->name . '.phtml';
        try {
            $this->set($data);
            $this->helperFunctions = array_merge(
                $this->helperFunctions,
                $helperFunctions
            );
            extract($this->data);
            ob_start();
            if (@file_exists($template)) {
                include $template;
            } else {
                throw new \LogicException(
                    'The template "' . $template . '" not found.'
                );
            }
            $content = ob_get_clean();

            return $content;
        } catch (\LogicException $e) {
            ob_end_clean();
            throw new \LogicException($e->getMessage());
        }
    }
}
