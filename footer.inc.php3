<?php
/* $Id$ */


// In this file you may add PHP or HTML statements that will be used to define
// the footer for phpMyAdmin pages.

/**
 * Close MySql non-persistent connections
 */
if (!$cfgPersistentConnections
    && (isset($dbh) && $dbh)) {
    @mysql_close($dbh);
}
if (!$cfgPersistentConnections
    && (isset($userlink) && $userlink)) {
    @mysql_close($userlink);
}
?>

</body>

</html>
<?php

/**
 * Sends bufferized data
 */
if (isset($cfgOBGzip) && $cfgOBGzip
    && isset($ob_mode) && $ob_mode) {
     PMA_outBufferPost($ob_mode);
}
?> 
