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
 * Sends bufferized data
 */
if (isset($GLOBALS['cfg']['OBGzip']) && $GLOBALS['cfg']['OBGzip']
    && isset($GLOBALS['ob_mode']) && $GLOBALS['ob_mode']) {
     PMA_outBufferPost($GLOBALS['ob_mode']);
}
?>
