<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Creates the database interface required for database interctions
 * and add it to GLOBALS.
 *
 * @package PhpMyAdmin-DBI
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

require_once './libraries/DatabaseInterface.class.php';

$extension = null;
if (defined('TESTSUITE')) {
    /**
     * For testsuite we use dummy driver which can fake some queries.
     */
    include_once './libraries/dbi/DBIDummy.class.php';
    $extension = new PMA_DBI_Dummy();
} else {

    /**
     * check for requested extension
     */
    $extensionName = $GLOBALS['cfg']['Server']['extension'];
    if (! PMA_DatabaseInterface::checkDbExtension($extensionName)) {

        // if it fails try alternative extension ...
        // and display an error ...
        $docurl = PMA_Util::getDocuLink('faq', 'faqmysql');
        $doclink = sprintf(
            __('See %sour documentation%s for more information.'),
            '[a@' . $docurl  . '@documentation]',
            '[/a]'
        );

        /**
         * @todo add different messages for alternative extension
         * and complete fail (no alternative extension too)
         */
        PMA_warnMissingExtension(
            $extensionName,
            false,
            $doclink
        );

        if ($extensionName === 'mysql') {
            $alternativ_extension = 'mysqli';
        } else {
            $alternativ_extension = 'mysql';
        }

        if (! PMA_DatabaseInterface::checkDbExtension($alternativ_extension)) {
            // if alternative fails too ...
            PMA_warnMissingExtension(
                $extensionName,
                true,
                $doclink
            );
        }

        $GLOBALS['cfg']['Server']['extension'] = $alternativ_extension;
        unset($alternativ_extension);
    }

    /**
     * Including The DBI Plugin
     */
    switch($GLOBALS['cfg']['Server']['extension']) {
    case 'mysql' :
        include_once './libraries/dbi/DBIMysql.class.php';
        $extension = new PMA_DBI_Mysql();
        break;
    case 'mysqli' :
        include_once './libraries/dbi/DBIMysqli.class.php';
        $extension = new PMA_DBI_Mysqli();
        break;
    case 'drizzle' :
        include_once './libraries/dbi/DBIDrizzle.class.php';
        $extension = new PMA_DBI_Drizzle();
        break;
    }
}
$GLOBALS['dbi'] = new PMA_DatabaseInterface($extension);
?>
