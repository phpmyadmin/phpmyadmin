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

require_once 'libraries/OutputBuffering.class.php';

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
        $buffer = \PMA_OutputBuffering::getInstance();
        try {
            extract($data);
            $buffer->start();
            if (file_exists($template)) {
                include $template;
            } else {
                throw new \LogicException(
                    'The template "' . $template . '" not found.'
                );
            }

            $buffer->stop();
            $content = $buffer->getContents();
            if ($trim) {
                $content = Template::trim($content);
            }

            return $content;
        } catch (\LogicException $e) {
            $buffer->stop();
            throw new \LogicException($e->getMessage());
        }
    }
}
