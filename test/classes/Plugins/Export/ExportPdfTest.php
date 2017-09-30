<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * tests for PhpMyAdmin\Plugins\Export\ExportPdf class
 *
 * @package PhpMyAdmin-test
 */
namespace PhpMyAdmin\Tests\Plugins\Export;

use PhpMyAdmin\Plugins\Export\ExportPdf;
use PhpMyAdmin\Plugins\Export\Helpers\Pdf;
use PhpMyAdmin\Tests\PmaTestCase;
use ReflectionMethod;
use ReflectionProperty;

/**
 * tests for PhpMyAdmin\Plugins\Export\ExportPdf class
 *
 * @package PhpMyAdmin-test
 * @group medium
 */
class ExportPdfTest extends PmaTestCase
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
     * Test for PhpMyAdmin\Plugins\Export\ExportPdf::setProperties
     *
     * @return void
     */
    public function testSetProperties()
    {
        $method = new ReflectionMethod('PhpMyAdmin\Plugins\Export\ExportPdf', 'setProperties');
        $method->setAccessible(true);
        $method->invoke($this->object, null);

        $attrProperties = new ReflectionProperty('PhpMyAdmin\Plugins\Export\ExportPdf', 'properties');
        $attrProperties->setAccessible(true);
        $properties = $attrProperties->getValue($this->object);

        $this->assertInstanceOf(
            'PhpMyAdmin\Properties\Plugins\ExportPluginProperties',
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
            'PhpMyAdmin\Properties\Options\Groups\OptionsPropertyRootGroup',
            $options
        );

        $this->assertEquals(
            'Format Specific Options',
            $options->getName()
        );

        $generalOptionsArray = $options->getProperties();

        $generalOptions = array_shift($generalOptionsArray);

        $this->assertInstanceOf(
            'PhpMyAdmin\Properties\Options\Groups\OptionsPropertyMainGroup',
            $generalOptions
        );

        $this->assertEquals(
            'general_opts',
            $generalOptions->getName()
        );

        $generalProperties = $generalOptions->getProperties();

        $property = array_shift($generalProperties);

        $this->assertInstanceOf(
            'PhpMyAdmin\Properties\Options\Items\TextPropertyItem',
            $property
        );

        $this->assertEquals(
            'report_title',
            $property->getName()
        );

        $generalOptions = array_shift($generalOptionsArray);

        $this->assertInstanceOf(
            'PhpMyAdmin\Properties\Options\Groups\OptionsPropertyMainGroup',
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
            'PhpMyAdmin\Properties\Options\Items\RadioPropertyItem',
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
     * Test for PhpMyAdmin\Plugins\Export\ExportPdf::exportHeader
     *
     * @return void
     */
    public function testExportHeader()
    {
        $pdf = $this->getMockBuilder('PhpMyAdmin\Plugins\Export\Helpers\Pdf')
            ->disableOriginalConstructor()
            ->getMock();

        $pdf->expects($this->once())
            ->method('Open');

        $pdf->expects($this->once())
            ->method('setAttributes');

        $pdf->expects($this->once())
            ->method('setTopMargin');

        $attrPdf = new ReflectionProperty('PhpMyAdmin\Plugins\Export\ExportPdf', '_pdf');
        $attrPdf->setAccessible(true);
        $attrPdf->setValue($this->object, $pdf);

        $this->assertTrue(
            $this->object->exportHeader()
        );
    }

    /**
     * Test for PhpMyAdmin\Plugins\Export\ExportPdf::exportFooter
     *
     * @return void
     */
    public function testExportFooter()
    {
        $pdf = $this->getMockBuilder('PhpMyAdmin\Plugins\Export\Helpers\Pdf')
            ->disableOriginalConstructor()
            ->getMock();

        $pdf->expects($this->once())
            ->method('getPDFData');

        $attrPdf = new ReflectionProperty('PhpMyAdmin\Plugins\Export\ExportPdf', '_pdf');
        $attrPdf->setAccessible(true);
        $attrPdf->setValue($this->object, $pdf);

        $this->assertTrue(
            $this->object->exportFooter()
        );
    }

    /**
     * Test for PhpMyAdmin\Plugins\Export\ExportPdf::exportDBHeader
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
     * Test for PhpMyAdmin\Plugins\Export\ExportPdf::exportDBFooter
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
     * Test for PhpMyAdmin\Plugins\Export\ExportPdf::exportDBCreate
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
     * Test for PhpMyAdmin\Plugins\Export\ExportPdf::exportData
     *
     * @return void
     */
    public function testExportData()
    {
        $pdf = $this->getMockBuilder('PhpMyAdmin\Plugins\Export\Helpers\Pdf')
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

        $attrPdf = new ReflectionProperty('PhpMyAdmin\Plugins\Export\ExportPdf', '_pdf');
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
     *     - PhpMyAdmin\Plugins\Export\ExportPdf::_setPdf
     *     - PhpMyAdmin\Plugins\Export\ExportPdf::_getPdf
     *
     * @return void
     */
    public function testSetGetPdf()
    {
        $setter = new ReflectionMethod('PhpMyAdmin\Plugins\Export\ExportPdf', '_setPdf');
        $setter->setAccessible(true);
        $setter->invoke($this->object, new Pdf);

        $getter = new ReflectionMethod('PhpMyAdmin\Plugins\Export\ExportPdf', '_getPdf');
        $getter->setAccessible(true);
        $this->assertInstanceOf(
            'PhpMyAdmin\Plugins\Export\Helpers\Pdf',
            $getter->invoke($this->object)
        );
    }

    /**
     * Test for
     *     - PhpMyAdmin\Plugins\Export\ExportPdf::_setPdfReportTitle
     *     - PhpMyAdmin\Plugins\Export\ExportPdf::_getPdfReportTitle
     *
     * @return void
     */
    public function testSetGetPdfTitle()
    {
        $setter = new ReflectionMethod('PhpMyAdmin\Plugins\Export\ExportPdf', '_setPdfReportTitle');
        $setter->setAccessible(true);
        $setter->invoke($this->object, "title");

        $getter = new ReflectionMethod('PhpMyAdmin\Plugins\Export\ExportPdf', '_getPdfReportTitle');
        $getter->setAccessible(true);
        $this->assertEquals(
            'title',
            $getter->invoke($this->object)
        );
    }
}
