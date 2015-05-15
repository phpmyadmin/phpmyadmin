<?php
namespace PMA;

if (! defined('PHPMYADMIN')) {
    exit;
}

class Template {

    protected $name = null;

    const BASE_PATH = 'templates/';

    protected function __construct($name)
    {
        $this->name = $name;
    }

    public static function get($name)
    {
        return new Template($name);
    }

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