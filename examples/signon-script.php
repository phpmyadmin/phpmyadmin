<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Single signon for phpMyAdmin
 *
 * This is just example how to use script based single signon with
 * phpMyAdmin, it is not intended to be perfect code and look, only
 * shows how you can integrate this functionality in your application.
 *
 * @package    PhpMyAdmin
 * @subpackage Example
 */


/**
 * This function returns username and password.
 *
 * It can optionally use configured username as parameter.
 *
 * @param string $user
 *
 * @return array
 */
function get_login_credentials($user)
{
    return array('root', '');
}

?>
