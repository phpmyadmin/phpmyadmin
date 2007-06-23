<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Cleanup, at logout time, of user choices stored in session
 *
 * @version $Id$
 */

unset($_SESSION['navi_limit_offset']);
?>
