<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * The PluginManager class is used alongside PluginObserver to implement
 * the Observer Design Pattern.
 *
 * @package PhpMyAdmin
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

/**
 * This class implements the SplSubject interface
 *
 * @todo    implement all methods
 * @package PhpMyAdmin
 * @link    http://php.net/manual/en/class.splsubject.php
 *
 */
class PluginManager implements SplSubject
{
    /**
     * Contains a list with all the plugins that attach to it
     *
     * @var SplObjectStorage
     */
    private $_storage;

    /**
     * Contains information about the current plugin state
     *
     * @var string
     */
    private $_status;

    /**
     * Constructor
     * Initializes $_storage with an empty SplObjectStorage
     */
    public function __construct()
    {
        $this->_storage = new SplObjectStorage();
    }

    /**
     * Attaches an SplObserver so that it can be notified of updates
     *
     * @param SplObserver $observer The SplObserver to attach
     *
     * @return void
     */
    function attach (SplObserver $observer )
    {
        $this->_storage->attach($observer);
    }

    /**
     * Detaches an observer from the subject to no longer notify it of updates
     *
     * @param SplObserver $observer The SplObserver to detach
     *
     * @return void
     */
    function detach (SplObserver $observer)
    {
         $this->_storage->detach($observer);
    }

    /**
     * It is called after setStatus() was run by a certain plugin, and has
     * the role of sending a notification to all of the plugins in $_storage,
     * by calling the update() method for each of them.
     *
     * @todo implement
     * @return void
     */
    function notify ()
    {
    }

    /* ~~~~~~~~~~~~~~~~~~~~ Getters and Setters ~~~~~~~~~~~~~~~~~~~~ */

    /**
     * Gets the list with all the plugins that attach to it
     *
     * @return SplObjectStorage
     */
    public function getStorage()
    {
        return $this->_storage;
    }

    /**
     * Setter for $_storage
     *
     * @param SplObjectStorage $_storage the list with all the plugins that
     *                                  attach to it
     *
     * @return void
     */
    public function setStorage($_storage)
    {
        $this->_storage = $_storage;
    }

    /**
     * Gets the information about the current plugin state
     * It is called by all the plugins in $_storage in their update() method
     *
     * @return string
     */
    public function getStatus()
    {
        return $this->_status;
    }

    /**
     * Setter for $_status
     * If a plugin changes its status, this has to be remembered in order to
     * notify the rest of the plugins that they should update
     *
     * @param string $_status contains information about the current plugin state
     *
     * @return void
     */
    public function setStatus($_status)
    {
        $this->_status = $_status;
    }
}
?>