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
require_once './libraries/db_common.inc.php';
require './libraries/StorageEngine.class.php';

$active_page = 'db_operations.php';
require_once './libraries/db_common.inc.php';
$url_query .= '&amp;goto=export_relation_schema.php';
require_once './libraries/db_info.inc.php';

/**
 * Includ settings for relation stuff
 * get all variables needed for exporting relational schema 
 * in $cfgRelation
 */
require_once './libraries/relation.lib.php';
$cfgRelation = PMA_getRelationsParam();

/** 
 * This is to avoid "Command out of sync" errors. Before switching this to
 * a value of 0 (for MYSQLI_USE_RESULT), please check the logic
 * to free results wherever needed.
 */
$query_default_option = PMA_DBI_QUERY_STORE;

/**
 * Now in ./libraries/relation.lib.php we check for all tables
 * that we need, but if we don't find them we are quiet about it
 * so people can't work without relational variables.
 * This page is absolutely useless if you didn't set up your tables
 * correctly, so it is a good place to see which tables we can and
 * complain ;-)
 */
if (!$cfgRelation['relwork']) {
    echo sprintf(__('<b>%s</b> table not found or not set in %s'), 'relation', 'config.inc.php') . '<br />' . "\n"
         . PMA_showDocu('relation') . "\n";
    require_once './libraries/footer.inc.php';
}

if (!$cfgRelation['displaywork']) {
    echo sprintf(__('<b>%s</b> table not found or not set in %s'), 'table_info', 'config.inc.php') . '<br />' . "\n"
         . PMA_showDocu('table_info') . "\n";
    require_once './libraries/footer.inc.php';
}

if (!isset($cfgRelation['table_coords'])){
    echo sprintf(__('<b>%s</b> table not found or not set in %s'), 'table_coords', 'config.inc.php') . '<br />' . "\n"
         . PMA_showDocu('table_coords') . "\n";
    require_once './libraries/footer.inc.php';
}
if (!isset($cfgRelation['pdf_pages'])) {
    echo sprintf(__('<b>%s</b> table not found or not set in %s'), 'pdf_page', 'config.inc.php') . '<br />' . "\n"
         . PMA_showDocu('pdf_pages') . "\n";
    require_once './libraries/footer.inc.php';
}

if ($cfgRelation['pdfwork']) {

   /**
    * User object created for presenting the HTML options
    * so, user can interact with it and perform export of relations schema
    */
	
    require_once './libraries/schema/User_Schema.class.php';
    $user_schema = new PMA_User_Schema();

    /**
     * This function will process the user input
     */

    $user_schema->processUserPreferences($do);

    /**
     * Now first show some possibility to select a page for the export of relation schema
     */

    $user_schema->selectPage();

    /**
     * Possibility to create a new page: 
     */

    $user_schema->createPage();

    /**
     * After selection of page or creating a page 
     * It will show you the list of tables 
     * A dashboard will also be shown where you can position the tables
     */

    $user_schema->showTableDashBoard();

    if (isset($do)
    && ($do == 'edcoord'
       || ($do == 'selectpage' && isset($chpage) && $chpage != 0)
       || ($do == 'createpage' && isset($chpage) && $chpage != 0))) {

      /** 
       * show Export schema generation options
       */
       $user_schema->displaySchemaGenerationOptions();

        if ((isset($showwysiwyg) && $showwysiwyg == '1')) {
			?>
			<script type="text/javascript">
			//<![CDATA[
			ToggleDragDrop('pdflayout');
			//]]>
			</script>
            <?php
      }
    } // end if
} // end if ($cfgRelation['pdfwork'])


/**
 * Displays the footer
 */
echo "\n";
require_once './libraries/footer.inc.php';
?>