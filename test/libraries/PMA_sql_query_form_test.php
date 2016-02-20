<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * tests for sql_query_form.lib.php
 *
 * @package PhpMyAdmin-test
 */

//the following definition should be used globally
use PMA\libraries\Theme;

$GLOBALS['server'] = 0;

/*
 * Include to test.
*/

require_once 'libraries/url_generating.lib.php';
require_once 'libraries/relation.lib.php';


require_once 'libraries/sanitizing.lib.php';
require_once 'libraries/js_escape.lib.php';
require_once 'libraries/database_interface.inc.php';
require_once 'libraries/sql_query_form.lib.php';
require_once 'libraries/kanji-encoding.lib.php';
require_once 'libraries/mysql_charsets.inc.php';

/**
 * class PMA_SqlQueryForm_Test
 *
 * this class is for testing sql_query_form.lib.php functions
 *
 * @package PhpMyAdmin-test
 */
class PMA_SqlQueryForm_Test extends PHPUnit_Framework_TestCase
{
    /**
     * Test for setUp
     *
     * @return void
     */
    public function setUp()
    {
        //$GLOBALS
        $GLOBALS['max_upload_size'] = 100;
        $GLOBALS['PMA_PHP_SELF'] = PMA_getenv('PHP_SELF');
        $GLOBALS['db'] = "PMA_db";
        $GLOBALS['table'] = "PMA_table";
        $GLOBALS['pmaThemeImage'] = 'image';
        $GLOBALS['text_dir'] = "text_dir";

        $GLOBALS['cfg']['GZipDump'] = false;
        $GLOBALS['cfg']['BZipDump'] = false;
        $GLOBALS['cfg']['ZipDump'] = false;
        $GLOBALS['cfg']['ServerDefault'] = "default";
        $GLOBALS['cfg']['TextareaAutoSelect'] = true;
        $GLOBALS['cfg']['TextareaRows'] = 100;
        $GLOBALS['cfg']['TextareaCols'] = 11;
        $GLOBALS['cfg']['DefaultTabDatabase'] = "structure";
        $GLOBALS['cfg']['RetainQueryBox'] = true;
        $GLOBALS['cfg']['ActionLinksMode'] = 'both';
        $GLOBALS['cfg']['DefaultTabTable'] = 'browse';
        $GLOBALS['cfg']['CodemirrorEnable'] = true;
        $GLOBALS['cfg']['DefaultForeignKeyChecks'] = 'default';

        //_SESSION
        $_SESSION['relation'][0] = array(
            'PMA_VERSION' => PMA_VERSION,
            'table_coords' => "table_name",
            'displaywork' => 'displaywork',
            'db' => "information_schema",
            'table_info' => 'table_info',
            'relwork' => 'relwork',
            'relation' => 'relation',
            'bookmarkwork' => 'bookmarkwork',
        );
        //$GLOBALS
        $GLOBALS['cfg']['Server']['user'] = "user";
        $GLOBALS['cfg']['Server']['pmadb'] = "pmadb";
        $GLOBALS['cfg']['Server']['bookmarktable'] = "bookmarktable";

        //$_SESSION
        $_SESSION['PMA_Theme'] = Theme::load('./themes/pmahomme');
        $_SESSION['PMA_Theme'] = new Theme();

        //Mock DBI
        $dbi = $this->getMockBuilder('PMA\libraries\DatabaseInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $fetchResult = array("index1"=>"table1", "index2"=>"table2");
        $dbi->expects($this->any())
            ->method('fetchResult')
            ->will($this->returnValue($fetchResult));

        $getColumns = array(
            array(
                "Field" => "field1",
                "Comment" => "Comment1"
            )
        );
        $dbi->expects($this->any())
            ->method('getColumns')
            ->will($this->returnValue($getColumns));

        $GLOBALS['dbi'] = $dbi;
    }

    /**
     * Test for PMA_getHtmlForSqlQueryFormInsert
     *
     * @return void
     */
    public function testPMAGetHtmlForSqlQueryFormInsert()
    {
        //Call the test function
        $query = "select * from PMA";
        $html = PMA_getHtmlForSqlQueryFormInsert($query);

        //validate 1: query
        $this->assertContains(
            htmlspecialchars($query),
            $html
        );

        //validate 2: enable auto select text in textarea
        $auto_sel = ' onclick="selectContent(this, sql_box_locked, true);"';
        $this->assertContains(
            $auto_sel,
            $html
        );

        //validate 3: showMySQLDocu
        $this->assertContains(
            PMA\libraries\Util::showMySQLDocu('SELECT'),
            $html
        );

        //validate 4: $fields_list
        $this->assertContains(
            '<input type="button" value="DELETE" id="delete"',
            $html
        );
        $this->assertContains(
            '<input type="button" value="UPDATE" id="update"',
            $html
        );
        $this->assertContains(
            '<input type="button" value="INSERT" id="insert"',
            $html
        );
        $this->assertContains(
            '<input type="button" value="SELECT" id="select"',
            $html
        );
        $this->assertContains(
            '<input type="button" value="SELECT *" id="selectall"',
            $html
        );

        //validate 5: Clear button
        $this->assertContains(
            '<input type="button" value="DELETE" id="delete"',
            $html
        );
        $this->assertContains(
            __('Clear'),
            $html
        );
    }

    /**
     * Test for PMA_getHtmlForSqlQueryForm
     *
     * @return void
     */
    public function testPMAGetHtmlForSqlQueryForm()
    {
        //Call the test function
        $GLOBALS['is_upload'] = true;
        $query = "select * from PMA";
        $html = PMA_getHtmlForSqlQueryForm($query);

        //validate 1: query
        $this->assertContains(
            htmlspecialchars($query),
            $html
        );

        //validate 2: $enctype
        $enctype = ' enctype="multipart/form-data"';
        $this->assertContains(
            $enctype,
            $html
        );

        //validate 3: sqlqueryform
        $this->assertContains(
            'id="sqlqueryform" name="sqlform">',
            $html
        );

        //validate 4: $db, $table
        $table  = $GLOBALS['table'];
        $db     = $GLOBALS['db'];
        $this->assertContains(
            PMA_URL_getHiddenInputs($db, $table),
            $html
        );

        //validate 5: $goto
        $goto = empty($GLOBALS['goto']) ? 'tbl_sql.php' : $GLOBALS['goto'];
        $this->assertContains(
            htmlspecialchars($goto),
            $html
        );

        //validate 6: PMA_Kanji_encodingForm
        $this->assertContains(
            PMA_Kanji_encodingForm(),
            $html
        );
    }
}
