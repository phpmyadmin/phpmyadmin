<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * @package phpMyAdmin-DBG
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

/**
 * checks for DBG extension
 *
 * allways use $GLOBALS here, as this script is included by footer.inc.hp
 * which can also be included from inside a function
 */
if ($GLOBALS['cfg']['DBG']['php']) {
    /**
     * Loads the DBG extension if needed
     */
    if (! @extension_loaded('dbg') ) {
        $message = PMA_Message::error(__('The %s extension is missing. Please check your PHP configuration.'),
        $message->addParam(sprintf('[a@http://php.net/%1$s@Documentation][em]%1$s[/em][/a]', 'dbg'));
        $message->addMessage('<a href="./Documentation.html#faqdbg" target="documentation">', false);
        $message->addString(__('Documentation'));
        $message->addMessage('</a>', false);
        $message->display();
    } else {
        $GLOBALS['DBG'] = true;
    }
}
?>
