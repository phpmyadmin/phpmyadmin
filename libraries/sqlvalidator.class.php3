<?php
/* $Id$ */

/**
* PHP interface to MimerSQL Validator
*
* Copyright 2002 Robin Johnson <robbat2@users.sourceforge.net>
* http://www.orbis-terrarum.net/?l=people.robbat2
*
* All data is transported over HTTP-SOAP
* And uses the PEAR SOAP Module
*
* Install instructions for PEAR SOAP
* Make sure you have a really recent PHP with PEAR support
* run this: "pear install Mail_Mime Net_DIME SOAP"
*
*
* @access   public
* @author   Robin Johnson <robbat2@users.sourceforge.net>
* @version  $Id$ 
*/

if (!defined('PMA_SQL_VALIDATOR_CLASS_INCLUDED')) {
    define('PMA_SQL_VALIDATOR_CLASS_INCLUDED', 1);

    @include("SOAP/Client.php");
    if(!class_exists('SOAP_Client') {
        $GLOBALS['sqlvalidator_error'] = TRUE;
    } else {

    // Ok, so we have SOAP Support, so let's use it!

    class SQLValidator {

        var $url;
        var $serviceName; 
        var $wsdl;
        var $outputType; 

        var $username;
        var $password;
        var $callingProgram;
        var $callingProgramVersion;
        var $targetDbms;
        var $targetDbmsVersion;
        var $connectionTechnology;
        var $connectionTechnologyVersion;
        var $interactive;

        var $serviceLink = NULL;
        var $sessionData = NULL;

        function dataInit()
        {
            $this->url = "http://sqlvalidator.mimer.com/v1/services";
            $this->serviceName = 'SQL99Validator';
            $this->wsdl = '?wsdl';
            
            $this->outputType = 'html';

            $this->username = 'anonymous';
            $this->password = '';
            $this->callingProgram = 'PHP_SQLValidator';
            $this->callingProgramVersion = '$Revision$';
            $this->targetDbms = 'N/A';
            $this->targetDbmsVersion = 'N/A';
            $this->connectionTechnology = 'PHP';
            $this->connectionTechnologyVersion = phpversion();
            $this->interactive = 1;

            $this->serviceLink = NULL;
            $this->sessionData = NULL;
        }

        function SQLValidator()
        {   
            $this->dataInit();
        }

        function setCredentials($username,$password)
        {   
            $this->username = $username; 
            $this->password = $password; 
        }

        function setCallingProgram($callingProgram,$callingProgramVersion)
        {
            $this->callingProgram = $callingProgram;
            $this->callingProgramVersion = $callingProgramVersion;
        }

        function appendCallingProgram($callingProgram,$callingProgramVersion)
        {
            $this->callingProgram .= ' - ' . $callingProgram;
            $this->callingProgramVersion .= ' - ' . $callingProgramVersion;
        }

        function setTargetDbms($targetDbms,$targetDbmsVersion)
        {
            $this->targetDbms = $targetDbms;
            $this->targetDbmsVersion = $targetDbmsVersion;
        }

        function appendTargetDbms($targetDbms,$targetDbmsVersion)
        {
            $this->targetDbms .= ' - ' . $targetDbms;
            $this->targetDbmsVersion .= ' - ' . $targetDbmsVersion;
        }

        function setConnectionTechnology($connectionTechnology,$connectionTechnologyVersion)
        {
            $this->connectionTechnology = $connectionTechnology;
            $this->connectionTechnologyVersion = $connectionTechnologyVersion;
        }

        function appendConnectionTechnology($connectionTechnology,$connectionTechnologyVersion)
        {
            $this->connectionTechnology .= ' - ' . $connectionTechnology;
            $this->connectionTechnologyVersion .= ' - ' . $connectionTechnologyVersion;
        }

        function setInteractive($interactive)
        {
            $this->interactive = $interactive;
        }
        
        function setOutputType($outputtype)
        {
            $this->OutputType = $outputtype;
        }

        function start()
        {   
            $this->startService();
            $this->startSession();
        }

        function startService()
        {
            $this->serviceLink = $this->_openService($this->url.'/'.$this->serviceName.$this->wsdl);
        }

        function startSession()
        {
            $this->sessionData = $this->_openSession($this->serviceLink, $this->username, $this->password, $this->callingProgram, $this->callingProgramVersion, $this->targetDbms, $this->targetDbmsVersion, $this->connectionTechnology, $this->connectionTechnologyVersion, $this->interactive);

            if( isset($this->sessionData) &&
            ($this->sessionData != NULL) &&
            ($this->sessionData->target != $this->url))
            {   
                // Reopen the service on the new URL that was provided 
                $url = $this->sessionData->target;
                $this->startService();
            }
        }

        /**
         *  Call to determine just if a query is valid or not.
         *
         * @param  string SQL statement to validate
         * 
         * @return string Validator string from Mimer
         * 
         * @see _validate
         */
        function isValid($sql)
        {
            $res = $this->_validate($sql);
            return $res->standard;
        }
        
        /**
         * Call for complete validator response
         *
         * @param  string SQL statement to validate
         *
         * @return string Validator string from Mimer
         * 
         * @see _validate
         */
        function ValidationString($sql)
        {
            $res = $this->_validate($sql);
            return $res->data;
        }

        /** Private functions beyond here
         * You don't need to mess with these
         */

        /**
         * Service opening
         *
         * @param  string  URL of Mimer SQL Validator WSDL file
         *
         * @return object  Object to use
         */
        function _openService($url)
        {   
            $obj = new SOAP_Client($url,TRUE);
            return $obj;
        }

        /**
         * Service initializer to connect to server
         *
         * @param  object  Service object
         * @param  string  Username
         * @param  string  Password 
         * @param  string  Name of calling program
         * @param  string  Version of calling program
         * @param  string  Target DBMS
         * @param  string  Version of target DBMS
         * @param  string  Connection Technology
         * @param  string  version of Connection Technology
         * @param  number  boolean of 1/0 to specify if we are an interactive system 
         *
         * @return object stdClass return object with data
         */
        function _openSession($obj, $username, $password, $callingProgram, $callingProgramVersion, $targetDbms, $targetDbmsVersion, $connectionTechnology, $connectionTechnologyVersion, $interactive)
        {	

            $ret = $obj->openSession($username, $password, $callingProgram, $callingProgramVersion, $targetDbms, $targetDbmsVersion, $connectionTechnology, $connectionTechnologyVersion, $interactive);

            return $ret;
        }

        /**
         * Validator sytem call
         *
         * @param  object  Service object
         * @param  object  Session object
         * @param  string  SQL Query to validate
         * @param  string  Data return type 
         *
         * @return object  stClass return with data
         */
        function _validateSQL($obj,$session,$sql,$method)
        {	
            $res = $obj->validateSQL($session->sessionId, $session->sessionKey, $sql, $this->outputType);
            return $res;
        }

        /**
         * Validator sytem call
         *
         * @param  string  SQL Query to validate
         *
         * @return object  stClass return with data
         */
        function _validate($sql)
        {   
            $ret = $this->_validateSQL($this->serviceLink, $this->sessionData, $sql, $this->outputType); 
            return $ret;
        }
    }

}  // $__PMA_SQL_VALIDATOR_CLASS__

?>
