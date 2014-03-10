<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * The PluginObserver class is used alongside PluginManager to implement
 * the Observer Design Pattern.
 *
 * @package PhpMyAdmin
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

/* Each PluginObserver instance contains a PluginManager instance */
require_once 'PluginManager.class.php';

/**
 * This class implements the SplObserver interface
 *
 * @package PhpMyAdmin
 * @link    http://php.net/manual/en/class.splobserver.php
 */
abstract class PluginObserver implements SplObserver
{
    /**
     * PluginManager instance that contains a list with all the observer
     * plugins that attach to it
     *
     * @var PluginManager
     */
    private $_pluginManager;

    /**
     * Constructor
     *
     * @param PluginManager $pluginManager The Plugin Manager instance
     */
    public function __construct($pluginManager)
    {
        $this->_pluginManager = $pluginManager;
    }

    /**
     * This method is called when any PluginManager to which the observer
     * is attached calls PluginManager::notify()
     *
     * TODO Declare this function abstract, removing its body,
     * as soon as we drop support for PHP 5.3.x.
     * See bug #3625
     *
     * @param SplSubject $subject The PluginManager notifying the observer
     *                            of an update.
     *
     * @return void
     *
     * @throws Exception
     */
    public function update (SplSubject $subject)
    {
        throw new Exception(
            'PluginObserver::update must be overridden in child classes.'
        );
    }

    /* ~~~~~~~~~~~~~~~~~~~~ Getters and Setters ~~~~~~~~~~~~~~~~~~~~ */


    /**
     * Gets the PluginManager instance that contains the list with all the
     * plugins that attached to it
     *
     * @return PluginManager
     */
    public function getPluginManager()
    {
        return $this->_pluginManager;
    }

    /**
     * Setter for $_pluginManager
     *
     * @param PluginManager $_pluginManager the private instance that it will
     *                                      attach to
     *
     * @return void
     */
    public function setPluginManager($_pluginManager)
    {
        $this->_pluginManager = $_pluginManager;
    }
}
?>
