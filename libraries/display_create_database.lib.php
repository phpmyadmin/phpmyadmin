<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Displays form for creating database (if user has privileges for that)
 *
 * @package PhpMyAdmin
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

/**
 *
 */
require_once './libraries/check_user_privileges.lib.php';

if ($is_create_db_priv) {
    // The user is allowed to create a db
    $html .= '<form method="post" action="db_create.php"'
        . ' id="create_database_form" class="ajax"><strong>';
    $html .= '<label for="text_create_db">'
        . PMA_Util::getImage('b_newdb.png')
        . " " . __('Create database')
        . '</label>&nbsp;'
        . PMA_Util::showMySQLDocu('CREATE_DATABASE');
    $html .= '</strong><br />';
    $html .= PMA_URL_getHiddenInputs('', '', 5);
    $html .= '<input type="hidden" name="reload" value="1" />';
    $html .= '<input type="text" name="new_db" value="' . $db_to_create
        . '" maxlength="64" class="textfield" id="text_create_db" '
        . 'required placeholder="' . __('Database name') . '"/>';

    include_once './libraries/mysql_charsets.inc.php';
    $html .= PMA_generateCharsetDropdownBox(
        PMA_CSDROPDOWN_COLLATION,
        'db_collation',
        null,
        null,
        true
    );

    if (! empty($dbstats)) {
        $html .= '<input type="hidden" name="dbstats" value="1" />';
    }

    $html .= '<input type="submit" value="' . __('Create') . '" id="buttonGo" />';
    $html .= '</form>';
} else {
    $html .= '<!-- db creation no privileges message -->';
    $html .= '<strong>' . __('Create database:') . '&nbsp;'
        . PMA_Util::showMySQLDocu('CREATE_DATABASE')
        . '</strong><br />';

    $html .= '<span class="noPrivileges">'
        . PMA_Util::getImage(
            's_error2.png',
            '',
            array('hspace' => 2, 'border' => 0, 'align' => 'middle')
        )
        . '' . __('No Privileges') . '</span>';
} // end create db form or message
