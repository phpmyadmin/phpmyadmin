<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * tests for mime.lib.php
 *
 * @package PhpMyAdmin-test
 */

/*
 * Include to test.
 */

require_once 'libraries/operations.lib.php';
require_once 'libraries/url_generating.lib.php';
require_once 'libraries/php-gettext/gettext.inc';
require_once 'libraries/relation.lib.php';
require_once 'libraries/CommonFunctions.class.php';

class PMA_operations_test extends PHPUnit_Framework_TestCase
{
    /**
     * Test for PMA_getHtmlForDatabaseComment
     */
    public function testPMA_getHtmlForDatabaseComment(){

        $_SESSION[' PMA_token '] = 'token';
        $GLOBALS['cfg']['PropertiesIconic'] = true;
        $this->assertEquals(
            '<div class="operations_half_width"><form method="post" action="db_operations.php"><input type="hidden" name="db" value="pma" /><input type="hidden" name="server" value="x" /><input type="hidden" name="lang" value="x" /><input type="hidden" name="collation_connection" value="x" /><input type="hidden" name="token" value="token" /><fieldset><legend><img class="icon ic_b_comment" src="themes/dot.gif" alt="" />Database comment: </legend><input type="text" name="comment" class="textfield" size="30"value="" /></fieldset><fieldset class="tblFooters"><input type="submit" value="Go" /></fieldset></form></div>',
            PMA_getHtmlForDatabaseComment("pma")
        );
    }

    /**
     * Test for PMA_getHtmlForRenameDatabase
     */
    public function testPMA_getHtmlForRenameDatabase(){

        $_REQUEST['db_collation'] = 'db1';
        $GLOBALS['cfg']['PropertiesIconic'] = true;
        $this->assertEquals(
            '<div class="operations_half_width"><form id="rename_db_form"  class="ajax" method="post" action="db_operations.php"onsubmit="return emptyFormElements(this, \'newname\')"><input type="hidden" name="db_collation" value="db1" />
<input type="hidden" name="what" value="data" /><input type="hidden" name="db_rename" value="true" /><input type="hidden" name="db" value="pma" /><input type="hidden" name="server" value="x" /><input type="hidden" name="lang" value="x" /><input type="hidden" name="collation_connection" value="x" /><input type="hidden" name="token" value="token" /><fieldset><legend><img src="b_edit.png" title="" alt="" />Rename database to:</legend><input id="new_db_name" type="text" name="newname" size="30" class="textfield" value="" /></fieldset><fieldset class="tblFooters"><input id="rename_db_input" type="submit" value="Go" /></fieldset></form></div>',
            PMA_getHtmlForRenameDatabase("pma")
        );
    }

    /**
     * Test for PMA_getHtmlForDropDatabaseLink
     */
    public function testPMA_getHtmlForDropDatabaseLink(){

        $GLOBALS['cfg']['PropertiesIconic'] = true;
        $this->assertEquals(
            '<div class="operations_half_width"><fieldset class="caution"><legend><img src="b_deltbl.png" title="" alt="" />Remove database</legend><ul><li><a href="sql.php?sql_query=DROP+DATABASE+%60pma%60&amp;back=db_operations.php&amp;goto=main.php&amp;reload=1&amp;purge=1&amp;message_to_show=Database+%60pma%60+has+been+dropped.&amp;server=x&amp;lang=x&amp;collation_connection=x&amp;token=token"id="drop_db_anchor" class="ajax">Drop the database (DROP)</a><a href="./url.php?url=http%3A%2F%2Fdev.mysql.com%2Fdoc%2Frefman%2F5.5%2Fen%2Fdrop-database.html&amp;server=x&amp;lang=x&amp;collation_connection=x&amp;token=token" target="mysql_doc"><img src="b_help.png" title="Documentation" alt="Documentation" /></a></li></ul></fieldset></div>',
            PMA_getHtmlForDropDatabaseLink("pma")
        );
    }

    /**
     * Test for PMA_getHtmlForCopyDatabase
     */
    public function testPMA_getHtmlForCopyDatabase(){

        $_REQUEST['db_collation'] = 'db1';
        $GLOBALS['cfg']['PropertiesIconic'] = true;
        $this->assertEquals(
            '<div class="operations_half_width clearfloat"><form id="copy_db_form"  class="ajax" method="post" action="db_operations.php"onsubmit="return emptyFormElements(this\'newname\')"><input type="hidden" name="db_collation" value="db1" />
<input type="hidden" name="db_copy" value="true" />
<input type="hidden" name="db" value="pma" /><input type="hidden" name="server" value="x" /><input type="hidden" name="lang" value="x" /><input type="hidden" name="collation_connection" value="x" /><input type="hidden" name="token" value="token" /><fieldset><legend><img src="b_edit.png" title="" alt="" />Copy database to:</legend><input type="text" name="newname" size="30" class="textfield" value="" /><br /><input type="radio" name="what" id="what_structure" value="structure" />
<label for="what_structure">Structure only</label><br />
<input type="radio" name="what" id="what_data" value="data" checked="checked" />
<label for="what_data">Structure and data</label><br />
<input type="radio" name="what" id="what_dataonly" value="dataonly" />
<label for="what_dataonly">Data only</label><br />
<input type="checkbox" name="create_database_before_copying" value="1" id="checkbox_create_database_before_copying"checked="checked" /><label for="checkbox_create_database_before_copying">CREATE DATABASE before copying</label><br /><input type="checkbox" name="drop_if_exists" value="true"id="checkbox_drop" /><label for="checkbox_drop">Add DROP TABLE / DROP VIEW</label><br /><input type="checkbox" name="sql_auto_increment" value="1" checked="checked" id="checkbox_auto_increment" /><label for="checkbox_auto_increment">Add AUTO_INCREMENT value</label><br /><input type="checkbox" name="add_constraints" value="1"id="checkbox_constraints" /><label for="checkbox_constraints">Add constraints</label><br /><input type="checkbox" name="switch_to_new" value="true"id="checkbox_switch"/><label for="checkbox_switch">Switch to copied database</label></fieldset><fieldset class="tblFooters"><input type="submit" name="submit_copy" value="Go" /></fieldset></form></div>',
            PMA_getHtmlForCopyDatabase("pma")
        );
    }

    /**
     * Test for PMA_getHtmlForChangeDatabaseCharset
     */
    public function testPMA_getHtmlForChangeDatabaseCharset(){

        $_REQUEST['db_collation'] = 'db1';
        $GLOBALS['cfg']['PropertiesIconic'] = true;
        $this->assertEquals(
            '<div class="operations_half_width"><form id="change_db_charset_form"  class="ajax" method="post" action="db_operations.php"><input type="hidden" name="db" value="pma" /><input type="hidden" name="table" value="bookmark" /><input type="hidden" name="server" value="x" /><input type="hidden" name="lang" value="x" /><input type="hidden" name="collation_connection" value="x" /><input type="hidden" name="token" value="token" /><fieldset>
    <legend><img src="s_asci.png" title="" alt="" /><label for="select_db_collation">Collation:</label>
</legend>
</fieldset><fieldset class="tblFooters"><input type="submit" name="submitcollation" value="Go" />
</fieldset>
</form></div>
',
            PMA_getHtmlForChangeDatabaseCharset("pma", "bookmark")
        );
    }

    /**
     * Test for PMA_getHtmlForExportRelationalSchemaView
     */
    public function testPMA_getHtmlForExportRelationalSchemaView(){

        $GLOBALS['cfg']['PropertiesIconic'] = true;
        $this->assertEquals(
            '<div class="operations_full_width"><fieldset><a href="schema_edit.php?id=001&name=pma"><img src="b_edit.png" title="" alt="" />Edit or export relational schema</a></fieldset></div>',
            PMA_getHtmlForExportRelationalSchemaView("id=001&name=pma")
        );
    }

    /**
     * Test for PMA_getHtmlForOrderTheTable
     */
    public function testPMA_getHtmlForOrderTheTable(){

        $this->assertEquals(
            '<div class="operations_half_width"><form method="post" id="alterTableOrderby" action="tbl_operations.php"  class="ajax"><input type="hidden" name="db" value="test_db" /><input type="hidden" name="table" value="table" /><input type="hidden" name="server" value="x" /><input type="hidden" name="lang" value="x" /><input type="hidden" name="collation_connection" value="x" /><input type="hidden" name="token" value="token" /><fieldset id="fieldset_table_order"><legend>Alter table order by</legend><select name="order_field"><option value="c">c</option>
<option value="c">c</option>
</select> (singly)<select name="order_order"><option value="asc">Ascending</option><option value="desc">Descending</option></select></fieldset><fieldset class="tblFooters"><input type="submit" name="submitorderby" value="Go" /></fieldset></form></div>',
            PMA_getHtmlForOrderTheTable(array("column1", "column2"))
        );
    }

    /**
     * Test for PMA_getHtmlForTableRow
     */
    public function testPMA_getHtmlForTableRow(){

        $this->assertEquals(
            '<tr><td><label for="name">lable</label></td><td><input type="checkbox" name="name" id="name" value="1"/></td></tr>',
            PMA_getHtmlForTableRow("name", "lable", "value")
        );
    }

    /**
     * Test for PMA_getMaintainActionlink
     */
    public function testPMA_getMaintainActionlink(){

        $this->assertEquals(
            '<li><a class="maintain_action" href="tbl_operations.phpserver=x&amp;lang=x&amp;collation_connection=x&amp;token=token">post</a><a href="./url.php?url=http%3A%2F%2Fdev.mysql.com%2Fdoc%2Frefman%2F5.5%2Fen%2Fvalue.html&amp;server=x&amp;lang=x&amp;collation_connection=x&amp;token=token" target="mysql_doc"><img src="b_help.png" title="Documentation" alt="Documentation" /></a></li>',
            PMA_getMaintainActionlink("post", array("name", "value"), "lable", "value")
        );
    }

    /**
     * Test for PMA_getHtmlForDeleteDataOrTable
     */
    public function testPMA_getHtmlForDeleteDataOrTable(){

        $this->assertEquals(
            '<div class="operations_half_width"><fieldset class="caution"><legend>Delete data or table</legend><ul><li><a href="sql.php?0=truncate&amp;server=x&amp;lang=x&amp;collation_connection=x&amp;token=token"id="truncate_tbl_anchor" class="ajax">Empty the table (TRUNCATE)</a><a href="./url.php?url=http%3A%2F%2Fdev.mysql.com%2Fdoc%2Frefman%2F5.5%2Fen%2Ftruncate-table.html&amp;server=x&amp;lang=x&amp;collation_connection=x&amp;token=token" target="mysql_doc"><img src="b_help.png" title="Documentation" alt="Documentation" /></a></li><li><a href="sql.php?0=drop&amp;server=x&amp;lang=x&amp;collation_connection=x&amp;token=token"id="drop_tbl_anchor" class="ajax">Delete the table (DROP)</a><a href="./url.php?url=http%3A%2F%2Fdev.mysql.com%2Fdoc%2Frefman%2F5.5%2Fen%2Fdrop-table.html&amp;server=x&amp;lang=x&amp;collation_connection=x&amp;token=token" target="mysql_doc"><img src="b_help.png" title="Documentation" alt="Documentation" /></a></li></ul></fieldset></div>',
            PMA_getHtmlForDeleteDataOrTable(array("truncate"), array("drop"))
        );
    }

    /**
     * Test for PMA_getDeleteDataOrTablelink
     */
    public function testPMA_getDeleteDataOrTablelink(){

        $this->assertEquals(
            PMA_getDeleteDataOrTablelink(array("param"), "TRUNCATE_TABLE", "/phpmyadmin", 001),
            '<li><a href="sql.php?0=param&amp;server=x&amp;lang=x&amp;collation_connection=x&amp;token=token"id="1" class="ajax">/phpmyadmin</a><a href="./url.php?url=http%3A%2F%2Fdev.mysql.com%2Fdoc%2Frefman%2F5.5%2Fen%2Ftruncate-table.html&amp;server=x&amp;lang=x&amp;collation_connection=x&amp;token=token" target="mysql_doc"><img src="b_help.png" title="Documentation" alt="Documentation" /></a></li>'
        );
    }

    /**
     * Test for PMA_getHtmlForPartitionMaintenance
     */
    public function testPMA_getHtmlForPartitionMaintenance(){

        $this->assertEquals(
            PMA_getHtmlForPartitionMaintenance(array("partition1", "partion2"), array("param1", "param2")),
            '<div class="operations_half_width"><form method="post" action="tbl_operations.php"><input type="hidden" name="db" value="test_db" /><input type="hidden" name="table" value="table" /><input type="hidden" name="server" value="x" /><input type="hidden" name="lang" value="x" /><input type="hidden" name="collation_connection" value="x" /><input type="hidden" name="token" value="token" /><fieldset><legend>Partition maintenance</legend>Partition <select name="partition_name">
<option value="partition1">partition1</option>
<option value="partion2">partion2</option>
</select>
<input type="radio" name="partition_operation" id="partition_operation_ANALYZE" value="ANALYZE" />
<label for="partition_operation_ANALYZE">Analyze</label>
<input type="radio" name="partition_operation" id="partition_operation_CHECK" value="CHECK" />
<label for="partition_operation_CHECK">Check</label>
<input type="radio" name="partition_operation" id="partition_operation_OPTIMIZE" value="OPTIMIZE" />
<label for="partition_operation_OPTIMIZE">Optimize</label>
<input type="radio" name="partition_operation" id="partition_operation_REBUILD" value="REBUILD" />
<label for="partition_operation_REBUILD">Rebuild</label>
<input type="radio" name="partition_operation" id="partition_operation_REPAIR" value="REPAIR" />
<label for="partition_operation_REPAIR">Repair</label>
<a href="./url.php?url=http%3A%2F%2Fdev.mysql.com%2Fdoc%2Frefman%2F5.5%2Fen%2Fpartitioning-maintenance.html&amp;server=x&amp;lang=x&amp;collation_connection=x&amp;token=token" target="mysql_doc"><img src="b_help.png" title="Documentation" alt="Documentation" /></a><br /><a href="sql.php?0=param1&amp;1=param2&amp;sql_query=ALTER+TABLE+%60table%60+REMOVE+PARTITIONING%3B&amp;server=x&amp;lang=x&amp;collation_connection=x&amp;token=token">Remove partitioning</a></fieldset><fieldset class="tblFooters"><input type="submit" name="submit_partition" value="Go" /></fieldset></form></div>'
        );
    }

    /**
     * Test for PMA_getHtmlForReferentialIntegrityCheck
     */
    public function testPMA_getHtmlForReferentialIntegrityCheck(){

        $this->assertEquals(
            PMA_getHtmlForReferentialIntegrityCheck(array("foreign1", "foreign2"), array("param1", "param2")),
            '<div class="operations_half_width"><fieldset><legend>Check referential integrity:</legend><ul>"\n"<li><a href="sql.php?0=param1&amp;1=param2&amp;sql_query=SELECT+%60table%60.%2A+FROM+%60table%60+LEFT+JOIN+%60f%60+ON+%60table%60.%600%60+%3D+%60f%60.%60f%60+WHERE+%60f%60.%60f%60+IS+NULL+AND+%60table%60.%600%60+IS+NOT+NULL&amp;server=x&amp;lang=x&amp;collation_connection=x&amp;token=token">0&nbsp;->&nbsp;f.f</a></li>
<li><a href="sql.php?0=param1&amp;1=param2&amp;sql_query=SELECT+%60table%60.%2A+FROM+%60table%60+LEFT+JOIN+%60f%60+ON+%60table%60.%601%60+%3D+%60f%60.%60f%60+WHERE+%60f%60.%60f%60+IS+NULL+AND+%60table%60.%601%60+IS+NOT+NULL&amp;server=x&amp;lang=x&amp;collation_connection=x&amp;token=token">1&nbsp;->&nbsp;f.f</a></li>
</ul></fieldset></div>'
        );
    }


}
