<?php
/* $Id$ */


// In this file you may add PHP or HTML statements that will be used to define
// the footer for phpMyAdmin pages.
?>

</html>
<?php

/**
 * Sends bufferized data
 */
if (isset($cfgOBGzip) && isset($ob_mode)) {
     out_buffer_post($ob_mode);
}
?> 
