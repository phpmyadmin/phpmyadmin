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
 * Copyright for Server side validator systems:
 * "All SQL statements are stored anonymously for statistical purposes.
 * Mimer SQL Validator, Copyright 2002 Upright Database Technology. 
 * All rights reserved."
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
    } // if ($cfg['SQLValidator']['use'] == TRUE)

    /**
     * This function utilizes the Mimer SQL Validator service
     * to validate an SQL query
     *
     * <http://developer.mimer.com/validator/index.htm>
     *
     * @param   string   SQL query to validate 
     *
     * @return  string   Validator result string 
     */
    function validateSQL($sql)
    {
        global $cfg;
        $str = '';
        if ($cfg['SQLValidator']['use'] == TRUE) {
            // create new class instance
            $srv = new SQLValidator();

            // check for username settings
            // The class defaults to anonymous with an empty password
            // automatically
            if($cfg['SQLValidator']['username'] != '') {
                $srv->setCredentials($cfg['SQLValidator']['username'], $cfg['SQLValidator']['password']);
            }

            // Identify ourselves to the server properly
            $srv->appendCallingProgram('phpMyAdmin',PMA_VERSION);

            // And specify what database system we are using
            $srv->setTargetDbms('MySQL',PMA_MYSQL_STR_VERSION);

            // Log on to service
            $srv->start();

            // Do service validation
            $str = $srv->ValidationString($sql);
            
            // Strip out the copyright if requested
            if($cfg['SQLValidator']['DisplayCopyright'] != TRUE) {
                $match = "reserved.<br/>\n<br/>";
                $pos = strpos($str,$match);
                $pos += strlen($match);
                $str = substr($str,$pos);
            }
            
        } else {
            
            // The service is not available
            // So note that properly
            $str = $GLOBALS['strValidatorDisabled'];

        }

        // Give string back to caller
        return $str;

    } // function validateSQL($sql)


} //$__PMA_SQL_VALIDATOR__

?>
