<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Used to render the footer of PMA's pages
 *
 * @package PhpMyAdmin
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

require_once 'libraries/Footnotes.class.php';

/**
 * Singleton class used to output the footer
 *
 * @package PhpMyAdmin
 */
class PMA_Footer
{
    /**
     * PMA_Footer instance
     *
     * @access private
     * @static
     * @var object
     */
    private static $_instance;

    /**
     * PMA_Footnotes instance
     *
     * @access private
     * @var object
     */
    private $_footnotes;

    /**
     * Cretes a new class instance
     *
     * @return new PMA_Footer object
     */
    private function __construct()
    {
        $this->_footnotes = new PMA_Footnotes();
    }

    /**
     * Returns the singleton PMA_Footer object
     *
     * @return PMA_Footer object
     */
    public static function getInstance()
    {
        if (empty(self::$_instance)) {
            self::$_instance = new PMA_Footer();
        }
        return self::$_instance;
    }

    /**
     * Returns the PMA_Footnotes object
     *
     * @return PMA_Footnotes object
     */
    public function getFootnotes()
    {
        return $this->_footnotes;
    }

    /**
     * Renders the footer
     *
     * @return string
     */
    public function getDisplay()
    {
        return $this->_footnotes->getDisplay();
    }
    /**
     * Renders and displays the footer
     *
     * @return void
     */
    public function display()
    {
        echo $this->getDisplay();
        // exit; FIXME
    }
}
