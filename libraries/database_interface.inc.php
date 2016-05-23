<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Creates the database interface required for database interactions
 * and add it to GLOBALS.
 *
 * @package PhpMyAdmin-DBI
 */
use PMA\libraries\dbi\DBIDummy;
use PMA\libraries\di\Container;
use PMA\libraries\DatabaseInterface;
use PMA\libraries\dbi\DBIMysql;
use PMA\libraries\dbi\DBIMysqli;

if (! defined('PHPMYADMIN')) {
    exit;
}

if (defined('TESTSUITE')) {
    /**
     * For testsuite we use dummy driver which can fake some queries.
     */
    $extension = new DBIDummy();
} else {

    /**
     * First check for the mysqli extension, as it's the one recommended
     * for the MySQL server's version that we support
     * (if PHP 7+, it's the only one supported)
     */
    $extension = 'mysqli';
    if (!DatabaseInterface::checkDbExtension($extension)) {

        $docurl = PMA\libraries\Util::getDocuLink('faq', 'faqmysql');
        $doclink = sprintf(
            __('See %sour documentation%s for more information.'),
            '[a@' . $docurl  . '@documentation]',
            '[/a]'
        );

        if (PHP_VERSION_ID < 70000) {
            $extension = 'mysql';
            if (! PMA\libraries\DatabaseInterface::checkDbExtension($extension)) {
                // warn about both extensions missing and exit
                PMA_warnMissingExtension(
                    'mysqli',
                    true,
                    $doclink
                );
            } elseif (empty($_SESSION['mysqlwarning'])) {
                trigger_error(
                    __(
                        'You are using the mysql extension which is deprecated in '
                        . 'phpMyAdmin. Please consider installing the mysqli '
                        . 'extension.'
                    ) . ' ' . $doclink,
                    E_USER_WARNING
                );
                // tell the user just once per session
                $_SESSION['mysqlwarning'] = true;
            }
        } else {
            // mysql extension is not part of PHP 7+, so warn and exit
            PMA_warnMissingExtension(
                'mysqli',
                true,
                $doclink
            );
        }
    }

    /**
     * Including The DBI Plugin
     */
    switch($extension) {
    case 'mysql' :
        $extension = new DBIMysql();
        break;
    case 'mysqli' :
        include_once 'libraries/dbi/DBIMysqli.lib.php';
        $extension = new DBIMysqli();
        break;
    }
}
$GLOBALS['dbi'] = new DatabaseInterface($extension);

$container = Container::getDefaultContainer();
$container->set('PMA_DatabaseInterface', $GLOBALS['dbi']);
$container->alias('dbi', 'PMA_DatabaseInterface');
