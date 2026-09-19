<?php

/**
 * Stub for phpMyAdmin's `customFooterFile` extension point.
 *
 * phpMyAdmin's libraries/vendor_config.php hardcodes the path
 * `<phpmyadmin>/config.footer.inc.php`. This 3-line stub keeps the plugin
 * itself in its own subdirectory (`phpMyAdmin-Ask-AI/`) and just forwards
 * the include to the real footer file there.
 *
 * Copy this file to your phpMyAdmin install root (alongside `index.php` and
 * `config.inc.php`). 
 * 
 * NOTE: If you already have a `config.footer.inc.php`, append
 * the `include` line below to your existing file instead of overwriting it.
 */

if (! defined('PHPMYADMIN')) {
  return;
}

include __DIR__ . '/phpMyAdmin-Ask-AI/main.php';
