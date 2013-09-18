<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Tests for libraries/tbl_chart.lib.php
 *
 * @package PhpMyAdmin-test
 */

/*
 * Include to test.
 */
require_once 'libraries/tbl_chart.lib.php';
require_once 'libraries/Util.class.php';
require_once 'libraries/Index.class.php';
require_once 'libraries/Message.class.php';
require_once 'libraries/database_interface.inc.php';
require_once 'libraries/php-gettext/gettext.inc';
require_once 'libraries/relation.lib.php';
require_once 'libraries/url_generating.lib.php';
require_once 'libraries/Theme.class.php';
require_once 'libraries/sanitizing.lib.php';

/**
 * Tests for libraries/tbl_chart.lib.php
 *
 * @package PhpMyAdmin-test
 */
class PMA_TblChartTest extends PHPUnit_Framework_TestCase
{

    /**
     * Setup function for test cases
     *
     * @access protected
     * @return void
     */
    protected function setUp()
    {
        /**
         * SET these to avoid undefined index error
         */
        $GLOBALS['server'] = 1;
        $GLOBALS['cfg']['Server']['pmadb'] = '';
        $GLOBALS['pmaThemeImage'] = 'theme/';
        $GLOBALS['cfg']['ServerDefault'] = "server";
        $GLOBALS['cfg']['ShowHint'] = true;

        $dbi = $this->getMockBuilder('PMA_DatabaseInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $GLOBALS['dbi'] = $dbi;

        //$_SESSION
        $_SESSION['PMA_Theme'] = PMA_Theme::load('./themes/pmahomme');
        $_SESSION['PMA_Theme'] = new PMA_Theme();
    }

    /**
     * Tests for PMA_getHtmlForPmaTokenAndUrlQuery() method.
     *
     * @return void
     * @test
     */
    public function testPMAGetHtmlForPmaTokenAndUrlQuery()
    {
        $url_query = "url_query";
        $_SESSION[' PMA_token '] = "PMA_token";

        $html = PMA_getHtmlForPmaTokenAndUrlQuery($url_query);

        $this->assertContains(
            $_SESSION[' PMA_token '],
            $html
        );
        $this->assertContains(
            $url_query,
            $html
        );
    }

    /**
     * Tests for PMA_getHtmlForChartTypeOptions() method.
     *
     * @return void
     * @test
     */
    public function testPMAGetHtmlForChartTypeOptions()
    {
        $html = PMA_getHtmlForChartTypeOptions();

        $this->assertContains(
            _pgettext('Chart type', 'Bar'),
            $html
        );
        $this->assertContains(
            _pgettext('Chart type', 'Column'),
            $html
        );
        $this->assertContains(
            _pgettext('Chart type', 'Line'),
            $html
        );
        $this->assertContains(
            _pgettext('Chart type', 'Spline'),
            $html
        );
        $this->assertContains(
            _pgettext('Chart type', 'Area'),
            $html
        );
        $this->assertContains(
            _pgettext('Chart type', 'Pie'),
            $html
        );
        $this->assertContains(
            _pgettext('Chart type', 'Timeline'),
            $html
        );
    }

    /**
     * Tests for PMA_getHtmlForStackedOption() method.
     *
     * @return void
     * @test
     */
    public function testPMAGetHtmlForStackedOption()
    {
        $html = PMA_getHtmlForStackedOption();

        $this->assertContains(
            __('Stacked'),
            $html
        );
    }

    /**
     * Tests for PMA_getHtmlForChartXAxisOptions() method.
     *
     * @return void
     * @test
     */
    public function testPMAGetHtmlForChartXAxisOptions()
    {
        $keys = array(
            "x1" => "value1",
            "x2" => "value2",
        );
        $yaxis = null;

        $html = PMA_getHtmlForChartXAxisOptions($keys, $yaxis);

        $this->assertContains(
            __('X-Axis:'),
            $html
        );

        //x-Axis values
        $this->assertContains(
            "x1",
            $html
        );
        $this->assertContains(
            "value1",
            $html
        );
        $this->assertContains(
            "x2",
            $html
        );
        $this->assertContains(
            "value2",
            $html
        );
    }
}
?>
