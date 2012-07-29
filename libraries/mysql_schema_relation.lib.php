<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 *
 * @package PhpMyAdmin
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

/**
 * This global variable represent the details for generating links inside
 * mysql schema.
 * Major element represent a table
 * 
 * This global variable has not modified anywhere
 */
$GLOBALS['mysql_schema_relation'] = array(
    'db' => array(
        'db' => array(
            'link_params' => array('db'),
            'default_page' => 'index.php'
        ),
        'user' => array(
            'link_params' => array('username'),
            'default_page' => 'server_privileges.php'
        )
        
    ),
    'proc' => array(
        'db' => array(
            'link_params' => array('db'),
            'default_page' => 'index.php'
        )
        
    ),
    'user' => array(
        'user' => array(
            'link_params' => array('username'),
            'default_page' => 'server_privileges.php'
        )
        
    )
);

?>
