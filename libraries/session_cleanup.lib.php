<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Cleanup, at logout time, of user choices stored in session
 *
 * @version $Id: cleanup.lib.php 10142 2007-03-20 10:32:13Z cybot_tm $
 */

unset($_SESSION['navi_limit_offset']);
?>
