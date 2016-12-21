<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * File for vendor customisation, you can change here paths or some behaviour,
 * which vendors such as Linux distibutions might want to change.
 *
 * For changing this file you should know what you are doing. For this reason
 * options here are not part of normal configuration.
 *
 * @package PhpMyAdmin
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

/**
 * Path to changelog file, can be gzip compressed. Useful when you want to
 * have documentation somewhere else, eg. /usr/share/doc.
 */
define('CHANGELOG_FILE', './ChangeLog');

/**
 * Path to license file. Useful when you want to have documentation somewhere
 * else, eg. /usr/share/doc.
 */
define('LICENSE_FILE', './LICENSE');

/**
 * Directory where configuration files are stored.
 * It is not used directly in code, just a convenient
 * define used further in this file.
 */
define('CONFIG_DIR', './');

/**
 * Filename of a configuration file.
 */
define('CONFIG_FILE', CONFIG_DIR . 'config.inc.php');

/**
 * Filename of custom header file.
 */
define('CUSTOM_HEADER_FILE', CONFIG_DIR . 'config.header.inc.php');

/**
 * Filename of custom footer file.
 */
define('CUSTOM_FOOTER_FILE', CONFIG_DIR . 'config.footer.inc.php');

/**
 * Default value for check for version upgrades.
 */
define('VERSION_CHECK_DEFAULT', true);

/**
 * Path to gettext.inc file. Useful when you want php-gettext somewhere else,
 * eg. /usr/share/php/gettext/gettext.inc.
 */
define('GETTEXT_INC', './libraries/php-gettext/gettext.inc');
/**
 * Path to tcpdf.php file. Useful when you want to use system tcpdf,
 * eg. /usr/share/php/tcpdf/tcpdf.php.
 */
define('TCPDF_INC', './libraries/tcpdf/tcpdf.php');
?>
