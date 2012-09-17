<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * tests for build_html_for_db.lib.php
 *
 * @package PhpMyAdmin-test
 */

/*
 * Include to test.
 */

$GLOBALS['server'] = 0;
require_once 'libraries/CommonFunctions.class.php';
require_once 'libraries/php-gettext/gettext.inc';
require_once 'libraries/build_html_for_db.lib.php';
require_once 'libraries/js_escape.lib.php';
require_once 'libraries/Theme.class.php';
require_once 'libraries/database_interface.lib.php';
require_once 'libraries/Tracker.class.php';
require_once 'libraries/Types.class.php';
require_once 'libraries/mysql_charsets.lib.php';

class PMA_build_html_for_db_test extends PHPUnit_Framework_TestCase
{
    /**
     * Prepares environment for the test.
     */
    public function setUp()
    {
        global $cfg;

        $cfg['ShowFunctionFields'] = false;
        $GLOBALS['server'] = 0;
        $cfg['ServerDefault'] = 1;
        $GLOBALS['lang'] = 'en';
        $_SESSION[' PMA_token '] = 'token';
        $cfg['MySQLManualType'] = 'viewable';
        $cfg['MySQLManualBase'] = 'http://dev.mysql.com/doc/refman';

        $GLOBALS['PMA_Types'] = new PMA_Types_MySQL();
        $_SESSION['PMA_Theme'] = new PMA_Theme();
        $GLOBALS['cfg']['PropertiesIconic'] = true;

        $GLOBALS['pmaThemePath'] = $_SESSION['PMA_Theme']->getPath();
        $GLOBALS['pmaThemeImage'] = 'theme/';

        $_SESSION[' PMA_token '] = 'token';
    }

    /**
     * Test for PMA_getColumnOrder
     */
    public function testPMA_getColumnOrder()
    {

        $this->assertEquals(
            array(
                'DEFAULT_COLLATION_NAME' => array(
                    'disp_name' => __('Collation'),
                    'description_function' => 'PMA_getCollationDescr',
                    'format'    => 'string',
                    'footer'    => 'utf8_general_ci'
                ),
                'SCHEMA_TABLES' => array(
                    'disp_name' => __('Tables'),
                    'format'    => 'number',
                    'footer'    => 0
                ),
                'SCHEMA_TABLE_ROWS' => array(
                    'disp_name' => __('Rows'),
                    'format'    => 'number',
                    'footer'    => 0
                ),
                'SCHEMA_DATA_LENGTH' => array(
                    'disp_name' => __('Data'),
                    'format'    => 'byte',
                    'footer'    => 0
                ),
                'SCHEMA_INDEX_LENGTH' => array(
                    'disp_name' => __('Indexes'),
                    'format'    => 'byte',
                    'footer'    => 0
                ),
                'SCHEMA_LENGTH' => array(
                    'disp_name' => __('Total'),
                    'format'    => 'byte',
                    'footer'    => 0
                ),
                'SCHEMA_DATA_FREE' => array(
                    'disp_name' => __('Overhead'),
                    'format'    => 'byte',
                    'footer'    => 0
                )
            ),
            PMA_getColumnOrder()
        );
    }

    /**
     * Test for PMA_buildHtmlForDb
     *
     * @param array   $current
     * @param boolean $is_superuser
     * @param string  $checkall
     * @param string  $url_query
     * @param array   $column_order
     * @param array   $replication_types
     * @param array   $replication_info
     * @param $output
     *
     * @dataProvider providerForTestPMA_buildHtmlForDb
     *
     * @group medium
     */
    public function testPMA_buildHtmlForDb($current, $is_superuser, $checkall,
        $url_query, $column_order, $replication_types, $replication_info, $output
    ) {
        $this->assertEquals(
            array($column_order, $output),
            PMA_buildHtmlForDb(
                $current, $is_superuser, $checkall, $url_query,
                $column_order, $replication_types, $replication_info
            )
        );
    }

    public function providerForTestPMA_buildHtmlForDb()
    {
        return array(
            array(
                array('SCHEMA_NAME' => 'pma'),
                true,
                '',
                'target=main.php',
                PMA_getColumnOrder(),
                array(
                    'SCHEMA_NAME' => 'pma',
                ),
                array(
                    'pma' => array(
                        'status' => 'true',
                        'Ignore_DB' => array(
                                        'pma' => 'pma'
                                       ),
                    )
                ),
                '<td class="tool"><input type="checkbox" name="selected_dbs[]" class="checkall" title="pma" value="pma" /></td><td class="name">        <a onclick="if (window.parent.openDb &amp;&amp; window.parent.openDb(\'pma\')) return false;" href="index.php?target=main.php&amp;db=pma" title="Jump to database" target="_parent"> pma</a></td><td class="tool" style="text-align: center;"><span class="nowrap"><img src="theme/s_cancel.png" title="Not replicated" alt="Not replicated" /></span></td><td class="tool"><a onclick="if (window.parent.setDb) window.parent.setDb(\'`pma`\');" href="server_privileges.php?target=main.php&amp;checkprivs=pma" title="Check privileges for database &quot;pma&quot;."> <span class="nowrap"><img src="theme/s_rights.png" title="Check Privileges" alt="Check Privileges" /></span></a></td>'
            )
        );
    }
}
