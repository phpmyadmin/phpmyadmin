<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Manages the rendering of pages in PMA
 *
 * @package PhpMyAdmin
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

require_once 'libraries/Header.class.php';
require_once 'libraries/Footer.class.php';

/**
 * Singleton class used to manage the rendering of pages in PMA
 *
 * @package PhpMyAdmin
 */
class PMA_Response
{
    /**
     * PMA_Response instance
     *
     * @access private
     * @static
     * @var object
     */
    private static $_instance;

    private $_header;
    private $_content;
    private $_footer;

    /**
     * Cretes a new class instance
     *
     * @return new PMA_Response object
     */
    private function __construct()
    {
        $this->_data    = array();
        $this->_header  = new PMA_Header();
        $this->_content = '';
        $this->_footer  = new PMA_Footer();
    }

    /**
     * Returns the singleton PMA_Response object
     *
     * @return PMA_Response object
     */
    public static function getInstance()
    {
        if (empty(self::$_instance)) {
            self::$_instance = new PMA_Response();
        }
        return self::$_instance;
    }

    public function getHeader()
    {
        return $this->_header;
    }

    public function getFooter()
    {
        return $this->_footer;
    }

    public function addHTML($content)
    {
        if (is_string($content)) {
            $this->_content .= $content;
        }
    }

    private function getDisplay()
    {
        $retval  = $this->_header->getDisplay();
        $retval .= $this->_content;
        $retval .= $this->_footer->getDisplay();
        return $retval;
    }

    public function response()
    {
        if (! $GLOBALS['is_ajax_request']) {
            echo $this->getDisplay();
        } else {
            // FIXME
        }
        exit;
    }
}

?>
