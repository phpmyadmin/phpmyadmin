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

require_once 'libraries/OutputBuffering.class.php';
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
     * Whether we are servicing an ajax request.
     * We can't simply use $GLOBALS['is_ajax_request']
     * here since it may have not been initialised yet.
     *
     * @access private
     * @var bool
     */
    private $_isAjax;

    /**
     * Cretes a new class instance
     *
     * @return new PMA_Response object
     */
    private function __construct()
    {
        $this->_isAjax = false;
        if (isset($_REQUEST['ajax_request']) && $_REQUEST['ajax_request'] == true) {
            $this->_isAjax = true;
        }
        $buffer = PMA_OutputBuffering::getInstance();
        $buffer->start();
        $this->_data    = array();
        $this->_header  = new PMA_Header();
        $this->_header->isAjax($this->_isAjax);
        $this->_content = '';
        $this->_footer  = new PMA_Footer();
        $this->_footer->isAjax($this->_isAjax);
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

    public function disable()
    {
        $this->_header->disable();
        $this->_footer->disable();
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

    public function simpleResponse()
    {
        echo $this->getDisplay();
        exit;
    }

    public function ajaxResponse()
    {
        echo $this->getDisplay();
        //PMA_ajaxResponse($this->getDisplay()); // FIXME
        exit;
    }

    public static function response()
    {
        $response = PMA_Response::getInstance();
        $buffer = PMA_OutputBuffering::getInstance();
        if (empty($response->_content)) {
            $response->_content = $buffer->getContents();
        }
        if ($response->_isAjax) {
            $response->ajaxResponse();
        } else {
            $response->simpleResponse();
        }
        $buffer->flush();
    }
}

?>
