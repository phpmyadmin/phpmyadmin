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

require_once 'libraries/CommonFunctions.class.php';
require_once 'libraries/php-gettext/gettext.inc';
require_once 'libraries/build_html_for_db.lib.php';
require_once 'libraries/js_escape.lib.php';
require_once 'libraries/Theme.class.php';

class PMA_build_html_for_db_test extends PHPUnit_Framework_TestCase
{
    /**
     * Test for PMA_getColumnOrder
     */
    public function testPMA_getColumnOrder(){

        if (! function_exists('PMA_getServerCollation')) {
            function PMA_getServerCollation()
            {
                return 'footer';
            }
        }

        $this->assertEquals(
            PMA_getColumnOrder(),
            array(
                'DEFAULT_COLLATION_NAME' => array(
                    'disp_name' => __('Collation'),
                    'description_function' => 'PMA_getCollationDescr',
                    'format'    => 'string',
                    'footer'    => 'footer'
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
            )
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
    public function testPMA_buildHtmlForDb($current, $is_superuser, $checkall, $url_query,$column_order, $replication_types, $replication_info, $output){

        if (! function_exists('PMA_is_system_schema')) {
            function PMA_is_system_schema()
            {
                return false;
            }
        }
        if (! function_exists('p')) {
            function p()
            {
                return;
            }
        }
        if (! defined('PMA_DRIZZLE')) {
            define('PMA_DRIZZLE', false);
        }

        $GLOBALS['cfg']['PropertiesIconic'] = true;
        $_SESSION['PMA_Theme'] = new PMA_Theme();
        $GLOBALS['pmaThemeImage'] = '';

        $this->assertEquals(
            PMA_buildHtmlForDb($current, $is_superuser, $checkall, $url_query,
                $column_order, $replication_types, $replication_info),
            $output
        );
    }

    public function providerForTestPMA_buildHtmlForDb(){
        return array(
            array(
                array('SCHEMA_NAME' => 'pma'),
                true,
                '',
                'target=main.php',
                array(
                    'SCHEMA_NAME' => 'pma',
                    'footer' => 1,
                    'format' => 'byte',
                    'description_function' => 'onClick'
                ),
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
                array(
                    0 => array(
                        'SCHEMA_NAME' => 'pma',
                        'footer' => 1,
                        'format' => 'byte',
                        'description_function' => 'onClick'
                    ),
                    1 => '<td class="tool"><input type="checkbox" name="selected_dbs[]" class="checkall" title="pma" value="pma" /></td><td class="name">        <a onclick="if (window.parent.openDb &amp;&amp; window.parent.openDb(\'pma\')) return false;" href="index.php?target=main.php&amp;db=pma" title="Jump to database" target="_parent"> pma</a></td><td class="value"><dfn title="">pma</dfn></td><td class="tool" style="text-align: center;"><span class="nowrap"><img src="s_cancel.png" title="Not replicated" alt="Not replicated" /></span></td>'
                )
            )
        );
    }
}
