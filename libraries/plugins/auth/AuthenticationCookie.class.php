<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Cookie Authentication plugin for phpMyAdmin
 *
 * @package    PhpMyAdmin-Authentication
 * @subpackage Cookie
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

/* Get the authentication interface */
require_once "libraries/plugins/AuthenticationPlugin.class.php";

/**
 * Handles the cookie authentication method
 *
 * @package PhpMyAdmin-Authentication
 */
class AuthenticationCookie extends AuthenticationPlugin
{
    /**
     *
     *
     * @return void
     */
    public function auth()
    {
    }

    /**
     *
     *
     * @return void
     */
    public function authCheck()
    {
    }

    /**
     *
     *
     * @return void
     */
    public function authSetUser()
    {
    }

    /**
     *
     *
     * @return void
     */
    public function authFails()
    {
    }

    /**
     *
     *
     * @return void
     */
    private function blowfishEncrypt()
    {
    }

    /**
     *
     *
     * @return void
     */
    private function blowfishDecrypt()
    {
    }

    /**
     * This method is called when any PluginManager to which the observer
     * is attached calls PluginManager::notify()
     *
     * @param SplSubject $subject The PluginManager notifying the observer
     *                            of an update.
     *
     * @return void
     */
    public function update (SplSubject $subject)
    {
    }
}