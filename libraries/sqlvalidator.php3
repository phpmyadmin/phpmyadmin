<?php
/* $Id$ */

/** SQL Validator interface for phpMyAdmin
 *
 * Copyright 2002 Robin Johnson <robbat2@users.sourceforge.net>
 * http://www.orbis-terrarum.net/?l=people.robbat2
 * 
 * This function uses the Mimer SQL Validator service 
 * <http://developer.mimer.com/validator/index.htm> from phpMyAdmin
 *
 * All data is transported over HTTP-SOAP
 * And uses the PEAR SOAP Module
 *
 * Install instructions for PEAR SOAP
 * Make sure you have a really recent PHP with PEAR support
 * run this: "pear install Mail_Mime Net_DIME SOAP"
 *
 * Enable the SQL Validator options in the configuration file
 * $cfg['SQLQuery']['Validate'] = TRUE;
 * $cfg['SQLValidator']['use']  = FALSE;
 *
 * Also set a username and password if you have a private one
 */
 
if (!defined('PMA_SQL_VALIDATOR_INCLUDED')) {
    define('PMA_SQL_VALIDATOR_INCLUDED', 1);

    // We need the PEAR libraries, so do a minimum version check first
    // I'm not sure if PEAR was available before this point
    // For now we actually use a configuration flag
    if ($cfg['SQLValidator']['use'] == TRUE) {
        include_once('sqlvalidator.class.php3');

        function validateSQL($sql)
        {
            global $cfg;
            $srv = new SQLValidator();
            if($cfg['SQLValidator']['username'] != '') {
                $srv->setCredentials($cfg['SQLValidator']['username'], $cfg['SQLValidator']['password']);
            }
            $srv->appendCallingProgram('phpMyAdmin',PMA_VERSION);
            $srv->setTargetDbms('MySQL',PMA_MYSQL_STR_VERSION);
            $srv->start();
            $str = $srv->ValidationString($sql);
            if($cfg['SQLValidator']['DisplayCopyright'] != TRUE) {
                $match = "reserved.<br/>\n<br/>";
                $pos = strpos($str,$match);
                $pos += strlen($match);
                $str = substr($str,$pos);
            }
            return $str;

        } // function validateSQL($sql)

    } // if ($cfg['SQLValidator']['use'] == TRUE)

} //$__PMA_SQL_VALIDATOR__

?>
