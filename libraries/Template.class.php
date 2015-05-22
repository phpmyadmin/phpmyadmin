<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * hold PMA\Template class
 *
 * @package PMA
 */

namespace PMA;

if (! defined('PHPMYADMIN')) {
    exit;
}

/**
 * Class Template
 *
 * Handle front end templating
 *
 * @package PMA
 */
class Template
{

    protected $name = null;

    const BASE_PATH = 'templates/';

    /**
     * Template constructor
     *
     * @param string $name Template name
     */
    protected function __construct($name)
    {
        $this->name = $name;
    }

    /**
     * Template getter
     *
     * @param string $name Template name
     *
     * @return Template
     */
    public static function get($name)
    {
        return new Template($name);
    }

    /**
     * Remove whitespaces between tags and innerHTML
     *
     * @param string $content HTML to perform the trim method
     *
     * @return string
     */
    public static function trim($content)
    {
        $regexp = '/(<[^\/][^>]+>)\s+|\s+(<\/)/';

        return preg_replace($regexp, "$1$2", $content);
    }

    /**
     * Render template
     *
     * @param array $data Variables to provides for template
     * @param bool  $trim Trim content
     *
     * @return string
     */
    public function render($data = array(), $trim = true)
    {
        $template = static::BASE_PATH . $this->name . '.phtml';
        try {
            extract($data);
            ob_start();
            if (file_exists($template)) {
                include $template;
            } else {
                throw new \LogicException(
                    'The template "' . $template . '" not found.'
                );
            }
            if ($trim) {
                $content = Template::trim(ob_get_clean());
            } else {
                $content = ob_get_clean();
            }

            return $content;
        } catch (\LogicException $e) {
            ob_end_clean();
            throw new \LogicException($e->getMessage());
        }
    }
}
