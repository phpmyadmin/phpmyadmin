<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * hold PMA\libraries\Template class
 *
 * @package PMA\libraries
 */
namespace PMA\libraries;

use Twig_Environment;
use Twig_Loader_Filesystem;
use PMA\libraries\twig\I18nExtension;
use PMA\libraries\twig\SanitizeExtension;
use PMA\libraries\twig\UrlExtension;
use PMA\libraries\twig\UtilExtension;

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

    /**
     * Twig environment
     */
    protected $twig;

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

        $loader = new Twig_Loader_Filesystem(static::BASE_PATH);
        $cache_dir = $GLOBALS['PMA_Config']->getTempDir('twig');
        /* Twig expects false when cache is not configured */
        if (is_null($cache_dir)) {
            $cache_dir = false;
        }
        $this->twig = new Twig_Environment($loader, array(
            'auto_reload' => true,
            'cache' => $cache_dir,
            'debug' => false,
        ));
        $this->twig->addExtension(new I18nExtension());
        $this->twig->addExtension(new SanitizeExtension());
        $this->twig->addExtension(new UrlExtension());
        $this->twig->addExtension(new UtilExtension());
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
     *
     * @return void
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
     *
     * @return void
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
     *
     * @return void
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
     *
     * @return mixed
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
        $template = static::BASE_PATH . $this->name;

        if (file_exists($template . '.twig')) {
            $this->set($data);
            try {
                $template = $this->twig->load($this->name . '.twig');
            } catch (\RuntimeException $e) {
                /* Retry with disabled cache */
                $this->twig->setCache(false);
                $template = $this->twig->load($this->name . '.twig');
                /*
                 * The trigger error is intentionally after second load
                 * to avoid triggering error when disabling cache does not
                 * solve it.
                 */
                trigger_error(
                    sprintf(
                        __('Error while working with template cache: %s'),
                        $e->getMessage()
                    ),
                    E_USER_WARNING
                );
            }
            return $template->render($this->data);
        }

        $template = $template . '.phtml';
        try {
            $this->set($data);
            $this->helperFunctions = array_merge(
                $this->helperFunctions,
                $helperFunctions
            );
            extract($this->data);
            ob_start();
            if (file_exists($template)) {
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
