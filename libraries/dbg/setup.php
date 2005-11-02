<?php
/* $Id$ */
// vim: expandtab sw=4 ts=4 sts=4:

if (isset($GLOBALS['cfg']['DBG']['enable']) && $GLOBALS['cfg']['DBG']['enable']) {
    /**
     * Loads the DBG extension if needed
     */
    if (!@extension_loaded('dbg')) {
        PMA_dl('dbg');
    }
    if (!@extension_loaded('dbg')) {
        echo '<div class="warning">'
            .sprintf($strCantLoad, 'DBG')
            .' <a href="./Documentation.html#faqdbg" target="documentation">' 
            .$GLOBALS['strDocu'] . '</a>'
            .'</div>';
        require_once('./footer.inc.php');
    }
    $GLOBALS['DBG'] = true;
}

?>
