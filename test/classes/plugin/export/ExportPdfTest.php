<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * tests for PMA\libraries\plugins\export\ExportPdf class
 *
 * @package PhpMyAdmin-test
 */
use PMA\libraries\plugins\export\ExportPdf;
use PMA\libraries\plugins\export\PMA_ExportPdf;

require_once 'libraries/export.lib.php';
require_once 'libraries/config.default.php';
require_once 'export.php';
require_once 'test/PMATestCase.php';

/**
 * tests for PMA\libraries\plugins\export\ExportPdf class
 *
 * @package PhpMyAdmin-test
 * @group medium
 */
class ExportPdfTest extends PMATestCase
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
     * Test for PMA\libraries\plugins\export\ExportPdf::setProperties
     *
     * @return void
     */
    public function testSetProperties()
    {
        $method = new ReflectionMethod('PMA\libraries\plugins\export\ExportPdf', 'setProperties');
        $method->setAccessible(true);
        $method->invoke($this->object, null);

        $attrProperties = new ReflectionProperty('PMA\libraries\plugins\export\ExportPdf', 'properties');
        $attrProperties->setAccessible(true);
        $properties = $attrProperties->getValue($this->object);

        $this->assertInstanceOf(
            'PMA\libraries\properties\plugins\ExportPluginProperties',
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
            'PMA\libraries\properties\options\groups\OptionsPropertyRootGroup',
            $options
        );

        $this->assertEquals(
            'Format Specific Options',
            $options->getName()
        );

        $generalOptionsArray = $options->getProperties();

        $generalOptions = array_shift($generalOptionsArray);

        $this->assertInstanceOf(
            'PMA\libraries\properties\options\groups\OptionsPropertyMainGroup',
            $generalOptions
        );

        $this->assertEquals(
            'general_opts',
            $generalOptions->getName()
        );

        $generalProperties = $generalOptions->getProperties();

        $property = array_shift($generalProperties);

        $this->assertInstanceOf(
            'PMA\libraries\properties\options\items\TextPropertyItem',
            $property
        );

        $this->assertEquals(
            'report_title',
            $property->getName()
        );

        $generalOptions = array_shift($generalOptionsArray);

        $this->assertInstanceOf(
            'PMA\libraries\properties\options\groups\OptionsPropertyMainGroup',
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
            'PMA\libraries\properties\options\items\RadioPropertyItem',
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
     * Test for PMA\libraries\plugins\export\ExportPdf::exportHeader
     *
     * @return void
     */
    public function testExportHeader()
    {
        $pdf = $this->getMockBuilder('PMA\libraries\plugins\export\PMA_ExportPdf')
            ->disableOriginalConstructor()
            ->getMock();

        $pdf->expects($this->once())
            ->method('Open');

        $pdf->expects($this->once())
            ->method('setAttributes');

        $pdf->expects($this->once())
            ->method('setTopMargin');

        $attrPdf = new ReflectionProperty('PMA\libraries\plugins\export\ExportPdf', '_pdf');
        $attrPdf->setAccessible(true);
        $attrPdf->setValue($this->object, $pdf);

        $this->assertTrue(
            $this->object->exportHeader()
        );
    }

    /**
     * Test for PMA\libraries\plugins\export\ExportPdf::exportFooter
     *
     * @return void
     */
    public function testExportFooter()
    {
        $pdf = $this->getMockBuilder('PMA\libraries\plugins\export\PMA_ExportPdf')
            ->disableOriginalConstructor()
            ->getMock();

        $pdf->expects($this->once())
            ->method('getPDFData');

        $attrPdf = new ReflectionProperty('PMA\libraries\plugins\export\ExportPdf', '_pdf');
        $attrPdf->setAccessible(true);
        $attrPdf->setValue($this->object, $pdf);

        $this->assertTrue(
            $this->object->exportFooter()
        );
    }

    /**
     * Test for PMA\libraries\plugins\export\ExportPdf::exportDBHeader
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
     * Test for PMA\libraries\plugins\export\ExportPdf::exportDBFooter
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
     * Test for PMA\libraries\plugins\export\ExportPdf::exportDBCreate
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
     * Test for PMA\libraries\plugins\export\ExportPdf::exportData
     *
     * @return void
     */
    public function testExportData()
    {
        $pdf = $this->getMockBuilder('PMA\libraries\plugins\export\PMA_ExportPdf')
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

        $attrPdf = new ReflectionProperty('PMA\libraries\plugins\export\ExportPdf', '_pdf');
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
     *     - PMA\libraries\plugins\export\ExportPdf::_setPdf
     *     - PMA\libraries\plugins\export\ExportPdf::_getPdf
     *
     * @return void
     */
    public function testSetGetPdf()
    {
        $setter = new ReflectionMethod('PMA\libraries\plugins\export\ExportPdf', '_setPdf');
        $setter->setAccessible(true);
        $setter->invoke($this->object, new PMA_ExportPdf);

        $getter = new ReflectionMethod('PMA\libraries\plugins\export\ExportPdf', '_getPdf');
        $getter->setAccessible(true);
        $this->assertInstanceOf(
            'PMA\libraries\plugins\export\PMA_ExportPdf',
            $getter->invoke($this->object)
        );
    }

    /**
     * Test for
     *     - PMA\libraries\plugins\export\ExportPdf::_setPdfReportTitle
     *     - PMA\libraries\plugins\export\ExportPdf::_getPdfReportTitle
     *
     * @return void
     */
    public function testSetGetPdfTitle()
    {
        $setter = new ReflectionMethod('PMA\libraries\plugins\export\ExportPdf', '_setPdfReportTitle');
        $setter->setAccessible(true);
        $setter->invoke($this->object, "title");

        $getter = new ReflectionMethod('PMA\libraries\plugins\export\ExportPdf', '_getPdfReportTitle');
        $getter->setAccessible(true);
        $this->assertEquals(
            'title',
            $getter->invoke($this->object)
        );
    }
}
