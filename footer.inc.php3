<?php
/* $Id$ */
// vim: expandtab sw=4 ts=4 sts=4:

// In this file you may add PHP or HTML statements that will be used to define
// the footer for phpMyAdmin pages.

/**
 * Close MySql non-persistent connections
 */
if (isset($GLOBALS['dbh']) && $GLOBALS['dbh']) {
    @mysql_close($GLOBALS['dbh']);
}
if (isset($GLOBALS['userlink']) && $GLOBALS['userlink']) {
    @mysql_close($GLOBALS['userlink']);
}
?>

</body>

</html>
<?php

/**
 * Generates profiling data if requested
 */
if (isset($GLOBALS['cfg']['DBG']['enable']) 
        && $GLOBALS['cfg']['DBG']['enable'] 
        && isset($GLOBALS['cfg']['DBG']['profile']['enable']) 
        && $GLOBALS['cfg']['DBG']['profile']['enable']) {
    //run the basic setup code first
    include('./libraries/dbg/setup.php3');
    //if the setup ran fine, then do the profiling
    if (isset($GLOBALS['DBG']) && $GLOBALS['DBG']) {
        include('./libraries/dbg/profiling.php3');
        dbg_dump_profiling_results();
    }
}

/**
 * Sends bufferized data
 */
if (isset($GLOBALS['cfg']['OBGzip']) && $GLOBALS['cfg']['OBGzip']
        && isset($GLOBALS['ob_mode']) && $GLOBALS['ob_mode']) {
    PMA_outBufferPost($GLOBALS['ob_mode']);
}

?>
