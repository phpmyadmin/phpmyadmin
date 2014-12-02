<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Abstract class for the authentication plugins
 *
 * @package PhpMyAdmin
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

/* This class extends the PluginObserver class */
require_once 'PluginObserver.class.php';

/**
 * Provides a common interface that will have to be implemented by all of the
 * authentication plugins.
 *
 * @package PhpMyAdmin
 */
abstract class AuthenticationPlugin extends PluginObserver
{
    /**
     * Displays authentication form
     *
     * @return boolean
     */
    abstract public function auth();

    /**
     * Gets advanced authentication settings
     *
     * @return boolean
     */
    abstract public function authCheck();

    /**
     * Set the user and password after last checkings if required
     *
     * @return boolean
     */
    abstract public function authSetUser();

    /**
     * Stores user credentials after successful login.
     *
     * @return void
     */
    public function storeUserCredentials()
    {
    }

    /**
     * User is not allowed to login to MySQL -> authentication failed
     *
     * @return boolean
     */
    abstract public function authFails();
}
?>