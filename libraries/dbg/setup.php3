<?php
/* $Id$ */
// vim: expandtab sw=4 ts=4 sts=4:

if (!defined('PMA_DBG_SETUP_INCLUDED')) {
  define('PMA_DBG_SETUP_INCLUDED', 1);

  if (isset($GLOBALS['cfg']['DBG']['enable']) && $GLOBALS['cfg']['DBG']['enable']) {
    /**
     * Loads the DBG extension if needed
     */
    if ( (PMA_PHP_INT_VERSION >= 40000 && !@ini_get('safe_mode') && @ini_get('enable_dl'))
        && @function_exists('dl')) {
      $extension = 'dbg';
      if (PMA_IS_WINDOWS) {
        $suffix = '.dll';
      } else {
        $suffix = '.so';
      }
      if (!@extension_loaded($extension)) {
        @dl($extension . $suffix);
      }
      if (!@extension_loaded($extension)) {
        echo sprintf($strCantLoad, 'DBG') . '<br />' . "\n"
          . '<a href="./Documentation.html#faqdbg" target="documentation">' . $GLOBALS['strDocu'] . '</a>' . "\n";
        exit();
      }
      $GLOBALS['DBG'] = true;
    } // end load mysql extension
  }
}

?>
