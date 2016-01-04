<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * tests for display_export.lib.php
 *
 * @package PhpMyAdmin-test
 */

/*
 * Include to test.
 */
require_once 'libraries/Util.class.php';
require_once 'libraries/Table.class.php';
require_once 'libraries/Advisor.class.php';
require_once 'libraries/php-gettext/gettext.inc';
require_once 'libraries/url_generating.lib.php';
require_once 'libraries/ServerStatusData.class.php';
require_once 'libraries/display_export.lib.php';
require_once 'libraries/Theme.class.php';
require_once 'libraries/database_interface.inc.php';
require_once 'libraries/charset_conversion.lib.php';
require_once 'libraries/Message.class.php';
require_once 'libraries/plugin_interface.lib.php';
require_once 'libraries/sanitizing.lib.php';
require_once 'libraries/js_escape.lib.php';
require_once 'libraries/relation.lib.php';

/**
 * class PMA_DisplayExport_Test
 *
 * this class is for testing display_export.lib.php functions
 *
 * @package PhpMyAdmin-test
 * @group large
 */
class PMA_DisplayExport_Test extends PHPUnit_Framework_TestCase
{
    /**
     * Test for setUp
     *
     * @return void
     */
    public function setUp()
    {
        //$GLOBALS
        $GLOBALS['cfg']['MaxRows'] = 10;
        $GLOBALS['cfg']['ExecTimeLimit'] = 300;
        $GLOBALS['cfg']['ServerDefault'] = "PMA_server";
        $GLOBALS['cfg']['TableNavigationLinksMode'] = 'icons';
        $GLOBALS['cfg']['LimitChars'] = 100;
        $GLOBALS['cfg']['ActionLinksMode'] = 'icons';
        $GLOBALS['cfg']['Server']['host'] = "localhost";
        $GLOBALS['cfg']['Server']['user'] = "pma_user";
        $GLOBALS['cfg']['ShowHint'] = true;
        $GLOBALS['cfg']['ActionLinksMode'] = 'icons';
        $GLOBALS['cfg']['ZipDump'] = true;
        $GLOBALS['cfg']['GZipDump'] = false;
        $GLOBALS['cfg']['BZipDump'] = false;
        $GLOBALS['cfg']['Export']['asfile'] = true;
        $GLOBALS['cfg']['Export']['file_template_server'] = "file_template_server";
        $GLOBALS['PMA_PHP_SELF'] = PMA_getenv('PHP_SELF');
        $GLOBALS['PMA_recoding_engine'] = "InnerDB";
        $GLOBALS['server'] = 0;

        $GLOBALS['table'] = "table";
        $GLOBALS['pmaThemeImage'] = 'image';
        $GLOBALS['db'] = "PMA";

        //$_SESSION
        $_SESSION['PMA_Theme'] = PMA_Theme::load('./themes/pmahomme');
        $_SESSION['PMA_Theme'] = new PMA_Theme();
        $_SESSION['relation'][$GLOBALS['server']] = "";

        $pmaconfig = $this->getMockBuilder('PMA_Config')
            ->disableOriginalConstructor()
            ->getMock();

        $pmaconfig->expects($this->any())
            ->method('getUserValue')
            ->will($this->returnValue('user value for test'));

        $GLOBALS['PMA_Config'] = $pmaconfig;
    }

    /**
     * Test for PMA_getHtmlForHiddenInput
     *
     * @return void
     */
    public function testPMAGetHtmlForHiddenInput()
    {
        $export_type = "server";
        $db = "PMA";
        $table = "PMA_test";
        $single_table_str = "PMA_single_str";
        $sql_query_str = "sql_query_str";

        //Call the test function
        $html = PMA_getHtmlForHiddenInput(
            $export_type,
            $db,
            $table,
            $single_table_str,
            $sql_query_str
        );

        //validate 1: PMA_URL_getHiddenInputs
        //$single_table
        $this->assertContains(
            '<input type="hidden" name="single_table" value="TRUE"',
            $html
        );
        //$export_type
        $this->assertContains(
            '<input type="hidden" name="export_type" value="server"',
            $html
        );
        $this->assertContains(
            '<input type="hidden" name="export_method" value="quick"',
            $html
        );
    }

    /**
     * Test for PMA_getHtmlForExportOptions
     *
     * @return void
     */
    public function testPMAGetHtmlForExportOptions()
    {
        global $cfg;
        $cfg['Export']['method'] = "XML";
        $cfg['SaveDir'] = "/tmp";

        $export_type = "server";
        $db = "PMA";
        $table = "PMA_test";
        $multi_values_str = "multi_values_str";
        $num_tables_str = "10";
        $unlim_num_rows_str = "unlim_num_rows_str";
        $single_table = "single_table";
        $GLOBALS['dbi']->cacheTableContent(array($db, $table, 'ENGINE'), 'MERGE');

        $columns_info = array(
            'test_column1' => array(
                'COLUMN_NAME' => 'test_column1'
            ),
            'test_column2' => array(
                'COLUMN_NAME' => 'test_column2'
            )
        );
        $dbi = $this->getMockBuilder('PMA_DatabaseInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $dbi->expects($this->any())->method('getColumnsFull')
            ->will($this->returnValue($columns_info));

        $GLOBALS['dbi'] = $dbi;

        /* Scan for plugins */
        $export_list = PMA_getPlugins(
            "export",
            'libraries/plugins/export/',
            array(
                'export_type' => $export_type,
                'single_table' => isset($single_table)
            )
        );

        //Call the test function
        $html = PMA_getHtmlForExportOptions(
            $export_type,
            $db,
            $table,
            $multi_values_str,
            $num_tables_str,
            $export_list,
            $unlim_num_rows_str
        );

        //validate 2: PMA_getHtmlForExportOptionsMethod
        $this->assertContains(
            $cfg['Export']['method'],
            $html
        );
        $this->assertContains(
            '<div class="exportoptions" id="quick_or_custom">',
            $html
        );
        $this->assertContains(
            __('Export method:'),
            $html
        );
        $this->assertContains(
            __('Custom - display all possible options'),
            $html
        );

        //validate 3: PMA_getHtmlForExportOptionsSelection
        $this->assertContains(
            '<div class="exportoptions" id="databases_and_tables">',
            $html
        );
        $this->assertContains(
            '<h3>' . __('Databases:') . '</h3>',
            $html
        );
        $this->assertContains(
            $multi_values_str,
            $html
        );

        //validate 4: PMA_getHtmlForExportOptionsQuickExport
        $this->assertContains(
            '<input type="checkbox" name="onserver" value="saveit" ',
            $html
        );
        $dir = htmlspecialchars(PMA_Util::userDir($cfg['SaveDir']));
        $this->assertContains(
            'Save on server in the directory <b>' . $dir . '</b>',
            $html
        );

        //validate 5: PMA_getHtmlForAliasModalDialog
        $this->assertContains(
            '<div id="alias_modal" class="hide" title="'
            . 'Rename exported databases/tables/columns">',
            $html
        );
        $this->assertContains(
            'Select database',
            $html
        );
        $this->assertContains(
            'Select table',
            $html
        );
        $this->assertContains(
            'New database name',
            $html
        );
        $this->assertContains(
            'New table name',
            $html
        );
        $this->assertContains(
            'test_column',
            $html
        );

        //validate 6: PMA_getHtmlForExportOptionsOutput
        $this->assertContains(
            '<div class="exportoptions" id="output">',
            $html
        );
        $this->assertContains(
            'user value for test',
            $html
        );

        //validate 7: PMA_getHtmlForExportOptionsFormat
        $this->assertContains(
            '<div class="exportoptions" id="format">',
            $html
        );
        $this->assertContains(
            '<h3>' . __('Format:') . '</h3>',
            $html
        );
    }

    /**
     * Test for PMA_getHtmlForAliasModalDialog
     *
     * @return void
     */
    public function testPMAGetHtmlForAliasModalDialog()
    {
        $columns_info = array(
            'test\'_db' => array(
                'test_<b>table' => array(
                    'co"l1' => array(
                        'COLUMN_NAME' => 'co"l1'
                    ),
                    'col<2' => array(
                        'COLUMN_NAME' => 'col<2'
                    )
                )
            )
        );

        $dbi = $this->getMockBuilder('PMA_DatabaseInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $dbi->expects($this->any())->method('getColumnsFull')
            ->will($this->returnValue($columns_info));

        $GLOBALS['dbi'] = $dbi;

        $html = PMA_getHtmlForAliasModalDialog();

        $this->assertContains(
            '<div id="alias_modal" class="hide" title="'
            . 'Rename exported databases/tables/columns">',
            $html
        );
        $this->assertContains(
            'test\'_db',
            $html
        );
        $this->assertContains(
            'test_&lt;b&gt;table',
            $html
        );
        $this->assertContains(
            'col&lt;2',
            $html
        );
        $this->assertContains(
            'co&quot;l1',
            $html
        );
        $this->assertContains(
            '<hr/>',
            $html
        );

        $name_attr =  'aliases[test\'_db][tables][test_&lt;b&gt;table][alias]';
        $id_attr = /*overload*/mb_substr(md5($name_attr), 0, 12);

        $this->assertContains(
            '<input type="text" value="" name="' . $name_attr . '" '
            . 'id="' . $id_attr . '" placeholder="'
            . 'test_&lt;b&gt;table alias" class="" disabled="disabled"/>',
            $html
        );
    }
}
