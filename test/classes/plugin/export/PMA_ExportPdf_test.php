<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * tests for ExportPdf class
 *
 * @package PhpMyAdmin-test
 */
require_once 'libraries/plugins/export/ExportPdf.class.php';
require_once 'libraries/plugins/export/PMA_ExportPdf.class.php';
require_once 'libraries/export.lib.php';
require_once 'libraries/Util.class.php';
require_once 'libraries/Theme.class.php';
require_once 'libraries/Config.class.php';
require_once 'libraries/php-gettext/gettext.inc';
require_once 'libraries/config.default.php';
require_once 'export.php';
/**
 * tests for ExportPdf class
 *
 * @package PhpMyAdmin-test
 * @group medium
 */
class PMA_ExportPdf_Test extends PHPUnit_Framework_TestCase
{
    protected $object;

    /**
     * Configures global environment.
     *
     * @return void
     */
    function setup()
    {
        $GLOBALS['server'] = 0;
        $GLOBALS['output_kanji_conversion'] = false;
        $GLOBALS['output_charset_conversion'] = false;
        $GLOBALS['buffer_needed'] = false;
        $GLOBALS['asfile'] = true;
        $GLOBALS['save_on_server'] = false;
        $this->object = new ExportPdf();
    }

    /**
     * tearDown for test cases
     *
     * @return void
     */
    public function tearDown()
    {
        unset($this->object);
    }

    /**
     * Test for ExportPdf::setProperties
     *
     * @return void
     */
    public function testSetProperties()
    {
        $method = new ReflectionMethod('ExportPdf', 'setProperties');
        $method->setAccessible(true);
        $method->invoke($this->object, null);

        $attrProperties = new ReflectionProperty('ExportPdf', 'properties');
        $attrProperties->setAccessible(true);
        $properties = $attrProperties->getValue($this->object);

        $this->assertInstanceOf(
            'ExportPluginProperties',
            $properties
        );

        $this->assertEquals(
            'PDF',
            $properties->getText()
        );

        $this->assertEquals(
            'pdf',
            $properties->getExtension()
        );

        $this->assertEquals(
            'application/pdf',
            $properties->getMimeType()
        );

        $this->assertEquals(
            'Options',
            $properties->getOptionsText()
        );

        $this->assertTrue(
            $properties->getForceFile()
        );

        $options = $properties->getOptions();

        $this->assertInstanceOf(
            'OptionsPropertyRootGroup',
            $options
        );

        $this->assertEquals(
            'Format Specific Options',
            $options->getName()
        );

        $generalOptionsArray = $options->getProperties();

        $generalOptions = array_shift($generalOptionsArray);

        $this->assertInstanceOf(
            'OptionsPropertyMainGroup',
            $generalOptions
        );

        $this->assertEquals(
            'general_opts',
            $generalOptions->getName()
        );

        $generalProperties = $generalOptions->getProperties();

        $property = array_shift($generalProperties);

        $this->assertInstanceOf(
            'TextPropertyItem',
            $property
        );

        $this->assertEquals(
            'report_title',
            $property->getName()
        );

        $generalOptions = array_shift($generalOptionsArray);

        $this->assertInstanceOf(
            'OptionsPropertyMainGroup',
            $generalOptions
        );

        $this->assertEquals(
            'dump_what',
            $generalOptions->getName()
        );

        $this->assertEquals(
            'Dump table',
            $generalOptions->getText()
        );

        $generalProperties = $generalOptions->getProperties();

        $property = array_shift($generalProperties);

        $this->assertInstanceOf(
            'RadioPropertyItem',
            $property
        );

        $this->assertEquals(
            'structure_or_data',
            $property->getName()
        );

        $this->assertEquals(
            array(
                'structure' => __('structure'),
                'data' => __('data'),
                'structure_and_data' => __('structure and data')
            ),
            $property->getValues()
        );

    }

    /**
     * Test for ExportPdf::exportHeader
     *
     * @return void
     */
    public function testExportHeader()
    {
        $pdf = $this->getMockBuilder('PMA_ExportPdf')
            ->disableOriginalConstructor()
            ->getMock();

        $pdf->expects($this->once())
            ->method('Open');

        $pdf->expects($this->once())
            ->method('setAttributes');

        $pdf->expects($this->once())
            ->method('setTopMargin');

        $attrPdf = new ReflectionProperty('ExportPdf', '_pdf');
        $attrPdf->setAccessible(true);
        $attrPdf->setValue($this->object, $pdf);

        $this->assertTrue(
            $this->object->exportHeader()
        );
    }

    /**
     * Test for ExportPdf::exportFooter
     *
     * @return void
     */
    public function testExportFooter()
    {
        $pdf = $this->getMockBuilder('PMA_ExportPdf')
            ->disableOriginalConstructor()
            ->getMock();

        $pdf->expects($this->once())
            ->method('getPDFData');

        $attrPdf = new ReflectionProperty('ExportPdf', '_pdf');
        $attrPdf->setAccessible(true);
        $attrPdf->setValue($this->object, $pdf);

        $this->assertTrue(
            $this->object->exportFooter()
        );
    }

    /**
     * Test for ExportPdf::exportDBHeader
     *
     * @return void
     */
    public function testExportDBHeader()
    {
        $this->assertTrue(
            $this->object->exportDBHeader('testDB')
        );
    }

    /**
     * Test for ExportPdf::exportDBFooter
     *
     * @return void
     */
    public function testExportDBFooter()
    {
        $this->assertTrue(
            $this->object->exportDBFooter('testDB')
        );
    }

    /**
     * Test for ExportPdf::exportDBCreate
     *
     * @return void
     */
    public function testExportDBCreate()
    {
        $this->assertTrue(
            $this->object->exportDBCreate('testDB', 'database')
        );
    }

    /**
     * Test for ExportPdf::exportData
     *
     * @return void
     */
    public function testExportData()
    {
        $pdf = $this->getMockBuilder('PMA_ExportPdf')
            ->disableOriginalConstructor()
            ->getMock();

        $pdf->expects($this->once())
            ->method('setAttributes')
            ->with(
                array(
                    'currentDb' => 'db', 'currentTable' => 'table',
                    'dbAlias' => 'db', 'tableAlias' => 'table',
                    'aliases' => array()
                )
            );

        $pdf->expects($this->once())
            ->method('mysqlReport')
            ->with('SELECT');

        $attrPdf = new ReflectionProperty('ExportPdf', '_pdf');
        $attrPdf->setAccessible(true);
        $attrPdf->setValue($this->object, $pdf);

        $this->assertTrue(
            $this->object->exportData(
                'db', 'table', "\n", "phpmyadmin.net/err", 'SELECT'
            )
        );
    }

    /**
     * Test for
     *     - ExportPdf::_setPdf
     *     - ExportPdf::_getPdf
     *
     * @return void
     */
    public function testSetGetPdf()
    {
        $setter = new ReflectionMethod('ExportPdf', '_setPdf');
        $setter->setAccessible(true);
        $setter->invoke($this->object, new PMA_ExportPdf);

        $getter = new ReflectionMethod('ExportPdf', '_getPdf');
        $getter->setAccessible(true);
        $this->assertInstanceOf(
            'PMA_ExportPdf',
            $getter->invoke($this->object)
        );
    }

    /**
     * Test for
     *     - ExportPdf::_setPdfReportTitle
     *     - ExportPdf::_getPdfReportTitle
     *
     * @return void
     */
    public function testSetGetPdfTitle()
    {
        $setter = new ReflectionMethod('ExportPdf', '_setPdfReportTitle');
        $setter->setAccessible(true);
        $setter->invoke($this->object, "title");

        $getter = new ReflectionMethod('ExportPdf', '_getPdfReportTitle');
        $getter->setAccessible(true);
        $this->assertEquals(
            'title',
            $getter->invoke($this->object)
        );
    }
}
