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
require_once "PluginObserver.class.php";

/**
 * Provides a common interface that will have to be implemented by all of the
 * authentication plugins.
 *
 * @package PhpMyAdmin
 */
abstract class AuthenticationPlugin extends PluginObserver
{
    /**
     *
     *
     * @return void
     */
    abstract public function auth();

    /**
     *
     *
     * @return void
     */
    abstract public function authCheck();

    /**
     *
     *
     * @return void
     */
    abstract public function authSetUser();

    /**
     *
     *
     * @return void
     */
    abstract public function authFails();
}
?>