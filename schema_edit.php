<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * PDF schema editor
 *
 * @package PhpMyAdmin
 */

/**
 * Gets some core libraries
 */

require_once 'libraries/common.inc.php';
require_once 'libraries/db_common.inc.php';
require 'libraries/StorageEngine.class.php';

$active_page = 'db_operations.php';
require_once 'libraries/db_common.inc.php';
$url_query .= '&amp;goto=schema_edit.php';
require_once 'libraries/db_info.inc.php';

/**
 * get all variables needed for exporting relational schema
 * in $cfgRelation
 */
$cfgRelation = PMA_getRelationsParam();

/**
 * Now in ./libraries/relation.lib.php we check for all tables
 * that we need, but if we don't find them we are quiet about it
 * so people can't work without relational variables.
 * This page is absolutely useless if you didn't set up your tables
 * correctly, so it is a good place to see which tables we can and
 * complain ;-)
 */
if (! $cfgRelation['relwork']) {
    echo sprintf(__('<b>%s</b> table not found or not set in %s'), 'relation', 'config.inc.php') . '<br />' . "\n"
         . PMA_Util::showDocu('config', 'cfg_Servers_relation') . "\n";
    exit;
}

if (! $cfgRelation['displaywork']) {
    echo sprintf(__('<b>%s</b> table not found or not set in %s'), 'table_info', 'config.inc.php') . '<br />' . "\n"
         . PMA_Util::showDocu('config', 'cfg_Servers_table_info') . "\n";
    exit;
}

if (! isset($cfgRelation['table_coords'])) {
    echo sprintf(__('<b>%s</b> table not found or not set in %s'), 'table_coords', 'config.inc.php') . '<br />' . "\n"
         . PMA_Util::showDocu('config', 'cfg_Servers_table_coords') . "\n";
    exit;
}
if (! isset($cfgRelation['pdf_pages'])) {
    echo sprintf(__('<b>%s</b> table not found or not set in %s'), 'pdf_page', 'config.inc.php') . '<br />' . "\n"
         . PMA_Util::showDocu('config', 'cfg_Servers_pdf_pages') . "\n";
    exit;
}

if ($cfgRelation['pdfwork']) {

    /**
     * User object created for presenting the HTML options
     * so, user can interact with it and perform export of relations schema
     */

    include_once 'libraries/schema/User_Schema.class.php';
    $user_schema = new PMA_User_Schema();

    /**
     * This function will process the user defined pages
     * and tables which will be exported as Relational schema
     * you can set the table positions on the paper via scratchboard
     * for table positions, put the x,y co-ordinates
     *
     * @param string $do It tells what the Schema is supposed to do
     *                  create and select a page, generate schema etc
     */
    if (isset($_REQUEST['do'])) {
        $user_schema->setAction($_REQUEST['do']);
        $user_schema->processUserChoice();
    }

    /**
     * Show some possibility to select a page for the export of relation schema
     * Lists all pages created before and can select and edit from them
     */

    $user_schema->selectPage();

    /**
     * Create a new page where relations will be drawn
     */

    $user_schema->showCreatePageDialog($db);

    /**
     * After selection of page or creating a page
     * It will show you the list of tables
     * A dashboard will also be shown where you can position the tables
     */

    $user_schema->showTableDashBoard();

    if (isset($_REQUEST['do'])
        && ($_REQUEST['do'] == 'edcoord'
        || ($_REQUEST['do']== 'selectpage' && isset($user_schema->chosenPage)
        && $user_schema->chosenPage != 0)
        || ($_REQUEST['do'] == 'createpage' && isset($user_schema->chosenPage)
        && $user_schema->chosenPage != 0))
    ) {

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

?>
