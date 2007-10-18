<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * @version $Id$
 */

/**
 * checks for DBG extension and trys to load if not loaded
 *
 * allways use $GLOBALS here, as this script is included by footer.inc.hp
 * which can also be included from inside a function
 */
if ($GLOBALS['cfg']['DBG']['enable']) {
    /**
     * Loads the DBG extension if needed
     */
    if (! @extension_loaded('dbg') && ! PMA_dl('dbg')) {
        $message = PMA_Message::error('strCantLoad');
        $message->addParam('DBG');
        $message->addMessage('<a href="./Documentation.html#faqdbg" target="documentation">', false);
        $message->addString('strDocu');
        $message->addMessage('</a>', false);
        $message->display();
    } else {
        $GLOBALS['DBG'] = true;
    }
}
?>
