<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 *
 * @version $Id$
 * @package phpMyAdmin
 */

/**
 * Gets some core libraries
 */
require_once './libraries/common.inc.php';
require './libraries/StorageEngine.class.php';

/**
 * Includ settings for relation stuff
 * get all variables needed for exporting relational schema 
 * in $cfgRelation
 */
require_once './libraries/relation.lib.php';
$cfgRelation = PMA_getRelationsParam();

/**
 * Settings for relation stuff
 */
require_once './libraries/transformations.lib.php';
require_once './libraries/Index.class.php';

/** 
 * This is to avoid "Command out of sync" errors. Before switching this to
 * a value of 0 (for MYSQLI_USE_RESULT), please check the logic
 * to free results wherever needed.
 */
$query_default_option = PMA_DBI_QUERY_STORE;

    /**
     * get all the export options and verify
     * call and include the appropriate Schema Class depending on $export_type
     *  
        /**
         * default is PDF
         */ 
        global  $db,$export_type;
        $export_type            = isset($export_type) ? $export_type : 'pdf';
        PMA_DBI_select_db($db);

        include("./libraries/schema/".ucfirst($export_type)."_Relation_Schema.class.php");
        $obj_schema = eval("new PMA_".ucfirst($export_type)."_Relation_Schema();");
