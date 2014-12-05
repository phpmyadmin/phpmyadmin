<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Tests for libraries/tbl_gis_visualization.lib.php
 *
 * @package PhpMyAdmin-test
 */

/*
 * Include to test.
 */
require_once 'libraries/tbl_gis_visualization.lib.php';
require_once 'libraries/Util.class.php';
require_once 'libraries/database_interface.inc.php';
require_once 'libraries/php-gettext/gettext.inc';
require_once 'libraries/relation.lib.php';
require_once 'libraries/url_generating.lib.php';

/**
 * Tests for libraries/tbl_gis_visualization.lib.php
 *
 * @package PhpMyAdmin-test
 */
class PMA_TblGisVisualizaionTest extends PHPUnit_Framework_TestCase
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

        $GLOBALS['PMA_Config'] = new PMA_MockConfig();
        $dbi = $this->getMockBuilder('PMA_DatabaseInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $GLOBALS['dbi'] = $dbi;
    }

    /**
     * Tests for PMA_getHtmlForOptionsList() method.
     *
     * @return void
     * @test
     */
    public function testPMAGetHtmlForOptionsList()
    {
        $options= array("option1", "option2");
        $select = array("option2");

        $html = PMA_getHtmlForOptionsList($options, $select);

        $this->assertEquals(
            '<option value="option1">option1</option>'
            . '<option value="option2" selected="selected" >option2</option>',
            $html
        );
    }

    /**
     * Tests for PMA_getHtmlForUseOpenStreetMaps() method.
     *
     * @return void
     * @test
     */
    public function testPMAGetHtmlForUseOpenStreetMaps()
    {
        $isSelected = true;

        $html = PMA_getHtmlForUseOpenStreetMaps($isSelected);

        $this->assertContains(
            '<input type="checkbox" name="visualizationSettings[choice]"',
            $html
        );
        $this->assertContains(
            __("Use OpenStreetMaps as Base Layer"),
            $html
        );
    }

    /**
     * Tests for PMA_getHtmlForSelect() method.
     *
     * @return void
     * @test
     */
    public function testPMAGetHtmlForSelect()
    {
        $column = "labelColumn";
        $columnCandidates = array("option1", "option2", "option3");
        $visualizationSettings = array("labelColumn" => "option3", "height" => "10");

        $html = PMA_getHtmlForSelect(
            $column, $columnCandidates, $visualizationSettings
        );

        $this->assertContains(
            '<label for="' . $column . '">',
            $html
        );

        $this->assertContains(
            __("Label column"),
            $html
        );

        $output = PMA_getHtmlForOptionsList(
            $columnCandidates, array($visualizationSettings[$column])
        );
        $this->assertContains(
            $output,
            $html
        );
    }

    /**
     * Tests for PMA_getHtmlForGisVisualization() method.
     *
     * @return void
     * @test
     */
    public function testPMAGetHtmlForGisVisualization()
    {
        $url_params = array("url_params");
        $labelCandidates = array("option1", "option2");
        $spatialCandidates = array("option2", "option3");
        $visualizationSettings = array(
            'width' => 10,
            'height' => 12,
            'labelColumn' => 'labelColumn',
            'spatialColumn' => 'spatialColumn',
            'choice' => 'choice',
        );
        $sql_query = "sql_query";
        $visualization = "visualization";
        $svg_support = array();
        $data = array();

        $html = PMA_getHtmlForGisVisualization(
            $url_params, $labelCandidates, $spatialCandidates,
            $visualizationSettings, $sql_query,
            $visualization, $svg_support, $data
        );

        $this->assertContains(
            '<legend>' . __('Display GIS Visualization') . '</legend>',
            $html
        );

        $this->assertContains(
            PMA_URL_getHiddenInputs($url_params),
            $html
        );

        $output = PMA_getHtmlForSelect(
            "labelColumn", $labelCandidates, $visualizationSettings
        );
        $this->assertContains(
            $output,
            $html
        );

        $output = PMA_getHtmlForSelect(
            "spatialColumn", $spatialCandidates, $visualizationSettings
        );
        $this->assertContains(
            $output,
            $html
        );

        $this->assertContains(
            htmlspecialchars($sql_query),
            $html
        );

        $this->assertContains(
            '>PNG</a>',
            $html
        );

        $this->assertContains(
            '>PDF</a>',
            $html
        );

        $this->assertContains(
            htmlspecialchars($visualizationSettings['width']),
            $html
        );

        $this->assertContains(
            htmlspecialchars($visualizationSettings['height']),
            $html
        );

        $this->assertContains(
            $visualization,
            $html
        );
    }
}

/**
 * Mock class for PMA_Config
 *
 * @package PhpMyAdmin-test
 */
class PMA_MockConfig
{
    /**
     * isHttps() method.
     *
     * @return bool
     * @test
     */
    public function isHttps()
    {
        return true;
    }
}
?>
