<?php
namespace PMA;

if (! defined('PHPMYADMIN')) {
    exit;
}

/**
 * Class Template
 *
 * Handle template using
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
     * Render template
     *
     * @param array $data Variables to provides for template
     *
     * @return string
     */
    public function render($data = array())
    {
        $template = static::BASE_PATH . $this->name . '.php';
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
            $content = ob_get_clean();
            return $content;
        } catch (\LogicException $e) {
            ob_end_clean();
            throw new \LogicException($e->getMessage());
        }
    }
}