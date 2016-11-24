<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * tests for PMA\libraries\plugins\export\ExportLatex class
 *
 * @package PhpMyAdmin-test
 */
use PMA\libraries\plugins\export\ExportLatex;

require_once 'libraries/export.lib.php';
require_once 'libraries/config.default.php';
require_once 'libraries/relation.lib.php';
require_once 'libraries/transformations.lib.php';
require_once 'export.php';
require_once 'test/PMATestCase.php';

/**
 * tests for PMA\libraries\plugins\export\ExportLatex class
 *
 * @package PhpMyAdmin-test
 * @group medium
 */
class ExportLatexTest extends PMATestCase
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
        $GLOBALS['plugin_param'] = array();
        $GLOBALS['plugin_param']['export_type'] = 'table';
        $GLOBALS['plugin_param']['single_table'] = false;
        $GLOBALS['cfgRelation']['relation'] = true;
        $this->object = new ExportLatex();
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
     * Test for PMA\libraries\plugins\export\ExportLatex::setProperties
     *
     * @return void
     */
    public function testSetProperties()
    {
        $GLOBALS['plugin_param']['export_type'] = '';
        $GLOBALS['plugin_param']['single_table'] = false;
        $GLOBALS['cfgRelation']['mimework'] = true;

        $method = new ReflectionMethod('PMA\libraries\plugins\export\ExportLatex', 'setProperties');
        $method->setAccessible(true);
        $method->invoke($this->object, null);

        $attrProperties = new ReflectionProperty('PMA\libraries\plugins\export\ExportLatex', 'properties');
        $attrProperties->setAccessible(true);
        $properties = $attrProperties->getValue($this->object);

        $this->assertInstanceOf(
            'PMA\libraries\properties\plugins\ExportPluginProperties',
            $properties
        );

        $this->assertEquals(
            'LaTeX',
            $properties->getText()
        );

        $this->assertEquals(
            'tex',
            $properties->getExtension()
        );

        $this->assertEquals(
            'application/x-tex',
            $properties->getMimeType()
        );

        $this->assertEquals(
            'Options',
            $properties->getOptionsText()
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
            'PMA\libraries\properties\options\items\BoolPropertyItem',
            $property
        );

        $this->assertEquals(
            'caption',
            $property->getName()
        );

        $this->assertEquals(
            'Include table caption',
            $property->getText()
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

        // hide structure
        $generalOptions = array_shift($generalOptionsArray);

        $this->assertInstanceOf(
            'PMA\libraries\properties\options\groups\OptionsPropertyMainGroup',
            $generalOptions
        );

        $this->assertEquals(
            'structure',
            $generalOptions->getName()
        );

        $this->assertEquals(
            'Object creation options',
            $generalOptions->getText()
        );

        $this->assertEquals(
            'data',
            $generalOptions->getForce()
        );

        $generalProperties = $generalOptions->getProperties();

        $property = array_shift($generalProperties);

        $this->assertInstanceOf(
            'PMA\libraries\properties\options\items\TextPropertyItem',
            $property
        );

        $this->assertEquals(
            'structure_caption',
            $property->getName()
        );

        $this->assertEquals(
            'Table caption:',
            $property->getText()
        );

        $this->assertEquals(
            'faq6-27',
            $property->getDoc()
        );

        $property = array_shift($generalProperties);

        $this->assertInstanceOf(
            'PMA\libraries\properties\options\items\TextPropertyItem',
            $property
        );

        $this->assertEquals(
            'structure_continued_caption',
            $property->getName()
        );

        $this->assertEquals(
            'Table caption (continued):',
            $property->getText()
        );

        $this->assertEquals(
            'faq6-27',
            $property->getDoc()
        );

        $property = array_shift($generalProperties);

        $this->assertInstanceOf(
            'PMA\libraries\properties\options\items\TextPropertyItem',
            $property
        );

        $this->assertEquals(
            'structure_label',
            $property->getName()
        );

        $this->assertEquals(
            'Label key:',
            $property->getText()
        );

        $this->assertEquals(
            'faq6-27',
            $property->getDoc()
        );

        $property = array_shift($generalProperties);

        $this->assertInstanceOf(
            'PMA\libraries\properties\options\items\BoolPropertyItem',
            $property
        );

        $this->assertEquals(
            'relation',
            $property->getName()
        );

        $this->assertEquals(
            'Display foreign key relationships',
            $property->getText()
        );

        $property = array_shift($generalProperties);

        $this->assertInstanceOf(
            'PMA\libraries\properties\options\items\BoolPropertyItem',
            $property
        );

        $this->assertEquals(
            'comments',
            $property->getName()
        );

        $this->assertEquals(
            'Display comments',
            $property->getText()
        );

        $property = array_shift($generalProperties);

        $this->assertInstanceOf(
            'PMA\libraries\properties\options\items\BoolPropertyItem',
            $property
        );

        $this->assertEquals(
            'mime',
            $property->getName()
        );

        $this->assertEquals(
            'Display MIME types',
            $property->getText()
        );

        // data options
        $generalOptions = array_shift($generalOptionsArray);

        $this->assertInstanceOf(
            'PMA\libraries\properties\options\groups\OptionsPropertyMainGroup',
            $generalOptions
        );

        $this->assertEquals(
            'data',
            $generalOptions->getName()
        );

        $this->assertEquals(
            'Data dump options',
            $generalOptions->getText()
        );

        $this->assertEquals(
            'structure',
            $generalOptions->getForce()
        );

        $generalProperties = $generalOptions->getProperties();

        $property = array_shift($generalProperties);

        $this->assertInstanceOf(
            'PMA\libraries\properties\options\items\BoolPropertyItem',
            $property
        );

        $this->assertEquals(
            'columns',
            $property->getName()
        );

        $this->assertEquals(
            'Put columns names in the first row:',
            $property->getText()
        );

        $property = array_shift($generalProperties);

        $this->assertInstanceOf(
            'PMA\libraries\properties\options\items\TextPropertyItem',
            $property
        );

        $this->assertEquals(
            'data_caption',
            $property->getName()
        );

        $this->assertEquals(
            'Table caption:',
            $property->getText()
        );

        $this->assertEquals(
            'faq6-27',
            $property->getDoc()
        );

        $property = array_shift($generalProperties);

        $this->assertInstanceOf(
            'PMA\libraries\properties\options\items\TextPropertyItem',
            $property
        );

        $this->assertEquals(
            'data_continued_caption',
            $property->getName()
        );

        $this->assertEquals(
            'Table caption (continued):',
            $property->getText()
        );

        $this->assertEquals(
            'faq6-27',
            $property->getDoc()
        );

        $property = array_shift($generalProperties);

        $this->assertInstanceOf(
            'PMA\libraries\properties\options\items\TextPropertyItem',
            $property
        );

        $this->assertEquals(
            'data_label',
            $property->getName()
        );

        $this->assertEquals(
            'Label key:',
            $property->getText()
        );

        $this->assertEquals(
            'faq6-27',
            $property->getDoc()
        );

        $property = array_shift($generalProperties);

        $this->assertInstanceOf(
            'PMA\libraries\properties\options\items\TextPropertyItem',
            $property
        );

        $this->assertEquals(
            'null',
            $property->getName()
        );

        $this->assertEquals(
            'Replace NULL with:',
            $property->getText()
        );

        // case 2
        $GLOBALS['plugin_param']['export_type'] = 'table';
        $GLOBALS['plugin_param']['single_table'] = false;

        $method->invoke($this->object, null);
        $properties = $attrProperties->getValue($this->object);

        $generalOptionsArray = $options->getProperties();

        $this->assertCount(
            4,
            $generalOptionsArray
        );
    }

    /**
     * Test for PMA\libraries\plugins\export\ExportLatex::exportHeader
     *
     * @return void
     */
    public function testExportHeader()
    {
        if (!defined("PMA_MYSQL_STR_VERSION")) {
            define("PMA_MYSQL_STR_VERSION", "5.0.0");
        }

        $GLOBALS['crlf'] = "\n";
        $GLOBALS['cfg']['Server']['port'] = 80;
        $GLOBALS['cfg']['Server']['host'] = 'localhost';

        ob_start();
        $this->assertTrue(
            $this->object->exportHeader()
        );
        $result = ob_get_clean();

        $this->assertContains(
            "\n% Host: localhost:80",
            $result
        );
    }

    /**
     * Test for PMA\libraries\plugins\export\ExportLatex::exportFooter
     *
     * @return void
     */
    public function testExportFooter()
    {
        $this->assertTrue(
            $this->object->exportFooter()
        );
    }

    /**
     * Test for PMA\libraries\plugins\export\ExportLatex::exportDBHeader
     *
     * @return void
     */
    public function testExportDBHeader()
    {
        $GLOBALS['crlf'] = "\n";

        $this->expectOutputString(
            "% \n% Database: 'testDB'\n% \n"
        );

        $this->assertTrue(
            $this->object->exportDBHeader('testDB')
        );
    }

    /**
     * Test for PMA\libraries\plugins\export\ExportLatex::exportDBFooter
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
     * Test for PMA\libraries\plugins\export\ExportLatex::exportDBCreate
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
     * Test for PMA\libraries\plugins\export\ExportLatex::exportData
     *
     * @return void
     */
    public function testExportData()
    {
        $GLOBALS['latex_caption'] = true;
        $GLOBALS['latex_data_caption'] = 'latex data caption';
        $GLOBALS['latex_data_continued_caption'] = 'continued caption';
        $GLOBALS['latex_columns'] = true;
        $GLOBALS['latex_data_label'] = 'datalabel';
        $GLOBALS['latex_null'] = 'null';
        $GLOBALS['cfg']['Server']['host'] = 'localhost';
        $GLOBALS['cfg']['Server']['verbose'] = 'verb';
        $dbi = $this->getMockBuilder('PMA\libraries\DatabaseInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $dbi->expects($this->once())
            ->method('numFields')
            ->with(null)
            ->will($this->returnValue(1));

        $dbi->expects($this->at(2))
            ->method('fieldName')
            ->with(null, 0)
            ->will($this->returnValue('f1'));

        $dbi->expects($this->at(3))
            ->method('fetchAssoc')
            ->with(null)
            ->will($this->returnValue(array('f1' => 'foo$%')));

        $dbi->expects($this->at(4))
            ->method('fetchAssoc')
            ->with(null)
            ->will($this->returnValue(array('f1' => null)));

        $dbi->expects($this->at(5))
            ->method('fetchAssoc')
            ->with(null)
            ->will($this->returnValue(null));

        $GLOBALS['dbi'] = $dbi;

        ob_start();
        $this->assertTrue(
            $this->object->exportData('db', 'tbl', "\n", "example.com", "SELECT")
        );
        $result = ob_get_clean();

        $this->assertEquals(
            "\n" . '%' . "\n" .
            '% Data: tbl' . "\n" .
            '%' . "\n" .
            ' \\begin{longtable}{|l|} ' . "\n" .
            ' \\hline \\endhead \\hline \\endfoot \\hline ' . "\n" .
            ' \\caption{latex data caption} \\label{datalabel} ' .
            '\\\\\\hline \\multicolumn{1}{|c|}{\\textbf{f1}} ' .
            '\\\\ \\hline \hline  \\endfirsthead ' . "\n" .
            '\caption{continued caption} \\\\ \hline \multicolumn{1}' .
            '{|c|}{\textbf{f1}} \\\\ \hline \hline \endhead \endfoot' . "\n" .
            'foo\$\% \\\\ \hline ' . "\n" .
            'null \\\\ \hline ' . "\n" .
            ' \end{longtable}' . "\n",
            $result
        );

        // case 2
        unset($GLOBALS['latex_columns']);
        $dbi = $this->getMockBuilder('PMA\libraries\DatabaseInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $dbi->expects($this->once())
            ->method('numFields')
            ->with(null)
            ->will($this->returnValue(1));

        $dbi->expects($this->at(2))
            ->method('fieldName')
            ->with(null, 0)
            ->will($this->returnValue('f1'));

        $dbi->expects($this->at(3))
            ->method('fetchAssoc')
            ->with(null)
            ->will($this->returnValue(array('f1' => 'foo$%')));

        $dbi->expects($this->at(4))
            ->method('fetchAssoc')
            ->with(null)
            ->will($this->returnValue(array('f1' => null)));

        $dbi->expects($this->at(5))
            ->method('fetchAssoc')
            ->with(null)
            ->will($this->returnValue(null));

        $GLOBALS['dbi'] = $dbi;

        ob_start();
        $this->assertTrue(
            $this->object->exportData('db', 'tbl', "\n", "example.com", "SELECT")
        );
        $result = ob_get_clean();

        $this->assertContains(
            '{datalabel} \\\\\\\\ \hlinefoo',
            $result
        );
    }

    /**
     * Test for PMA\libraries\plugins\export\ExportLatex::exportStructure
     *
     * @return void
     */
    public function testExportStructure()
    {
        // $this->object = $this->getMockBuilder('PMA\libraries\plugins\export\ExportHtmlword')
        //     ->setMethods(array('formatOneColumnDefinition'))
        //     ->getMock();

        $keys = array(
            array(
                'Non_unique' => 0,
                'Column_name' => 'name1'
            ),
            array(
                'Non_unique' => 1,
                'Column_name' => 'name2'
            )
        );

        // case 1

        $dbi = $this->getMockBuilder('PMA\libraries\DatabaseInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $dbi->expects($this->once())
            ->method('getTableIndexes')
            ->with('database', '')
            ->will($this->returnValue($keys));

        $dbi->expects($this->exactly(2))
            ->method('fetchResult')
            ->willReturnOnConsecutiveCalls(
                array(),
                array(
                    'name1' => array(
                        'values' => 'test-',
                        'transformation' => 'testfoo',
                        'mimetype' => 'testmimetype_'
                    )
                )
            );

        $columns = array(
            array(
                'Null' => 'Yes',
                'Field' => 'name1',
                'Key' => 'PRI',
                'Type' => 'set(abc)enum123'
            ),
            array(
                'Null' => 'NO',
                'Field' => 'fields',
                'Key' => 'COMP',
                'Type' => '',
                'Default' => 'def'
            )
        );
        $dbi->expects($this->once())
            ->method('getColumns')
            ->with('database', '')
            ->will($this->returnValue($columns));

        $dbi->expects($this->any())
            ->method('query')
            ->will($this->returnValue(true));

        $dbi->expects($this->any())
            ->method('numRows')
            ->will($this->returnValue(1));

        $dbi->expects($this->any())
            ->method('fetchAssoc')
            ->will(
                $this->returnValue(
                    array(
                        'comment' => array('name1' => 'testComment')
                    )
                )
            );

        $GLOBALS['dbi'] = $dbi;
        if (isset($GLOBALS['latex_caption'])) {
            unset($GLOBALS['latex_caption']);
        }

        $GLOBALS['cfgRelation']['relation'] = true;
        $_SESSION['relation'][0] = array(
            'PMA_VERSION' => PMA_VERSION,
            'relwork' => true,
            'commwork' => true,
            'mimework' => true,
            'db' => 'database',
            'relation' => 'rel',
            'column_info' => 'col'
        );

        ob_start();
        $this->assertTrue(
            $this->object->exportStructure(
                'database',
                '',
                "\n",
                "example.com",
                'test',
                'test',
                true,
                true,
                true
            )
        );
        $result = ob_get_clean();

        //echo $result; die;
        $this->assertEquals(
            "\n" . '%' . "\n" .
            '% Structure: ' . "\n" .
            '%' . "\n" .
            ' \\begin{longtable}{|l|c|c|c|l|l|} ' . "\n" .
            ' \\hline \\multicolumn{1}{|c|}{\\textbf{Column}} & ' .
            '\\multicolumn{1}{|c|}{\\textbf{Type}} & \\multicolumn{1}{|c|}' .
            '{\\textbf{Null}} & \\multicolumn{1}{|c|}{\\textbf{Default}} &' .
            ' \\multicolumn{1}{|c|}{\\textbf{Comments}} & \\multicolumn{1}' .
            '{|c|}{\\textbf{MIME}} \\\\ \\hline \\hline' . "\n" .
            '\\endfirsthead' . "\n" . ' \\hline \\multicolumn{1}{|c|}' .
            '{\\textbf{Column}} & \\multicolumn{1}' . '{|c|}{\\textbf{Type}}' .
            ' & \\multicolumn{1}{|c|}{\\textbf{Null}} & \\multicolumn' .
            '{1}{|c|}{\\textbf{Default}} & \\multicolumn{1}{|c|}{\\textbf' .
            '{Comments}} & \\multicolumn{1}{|c|}{\\textbf{MIME}} \\\\ ' .
            '\\hline \\hline \\endhead \\endfoot ' . "\n" . '\\textbf{\\textit' .
            '{name1}} & set(abc) & Yes & NULL &  ' .
            '& Testmimetype/ \\\\ \\hline ' . "\n" .
            'fields &   & No & def &  &  \\\\ \\hline ' . "\n" .
            ' \\end{longtable}' . "\n",
            $result
        );

        // case 2

        $dbi = $this->getMockBuilder('PMA\libraries\DatabaseInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $dbi->expects($this->exactly(2))
            ->method('fetchResult')
            ->willReturnOnConsecutiveCalls(
                array(
                    'name1' => array(
                        'foreign_table' => 'ftable',
                        'foreign_field' => 'ffield'
                    ),
                    'foreign_keys_data' => array()
                ),
                array(
                    'field' => array(
                        'values' => 'test-',
                        'transformation' => 'testfoo',
                        'mimetype' => 'test<'
                    )
                )
            );

        $dbi->expects($this->once())
            ->method('getTableIndexes')
            ->with('database', '')
            ->will($this->returnValue($keys));

        $dbi->expects($this->once())
            ->method('getColumns')
            ->with('database', '')
            ->will($this->returnValue($columns));

        $dbi->expects($this->any())
            ->method('query')
            ->will($this->returnValue(true));

        $dbi->expects($this->any())
            ->method('numRows')
            ->will($this->returnValue(1));

        $dbi->expects($this->any())
            ->method('fetchAssoc')
            ->will(
                $this->returnValue(
                    array(
                        'comment' => array('field' => 'testComment')
                    )
                )
            );

        $GLOBALS['dbi'] = $dbi;

        $GLOBALS['cfgRelation']['relation'] = true;
        $_SESSION['relation'][0] = array(
            'PMA_VERSION' => PMA_VERSION,
            'relwork' => true,
            'commwork' => true,
            'mimework' => true,
            'db' => 'database',
            'relation' => 'rel',
            'column_info' => 'col'
        );

        ob_start();
        $this->assertTrue(
            $this->object->exportStructure(
                'database',
                '',
                "\n",
                "example.com",
                'test',
                'test',
                true,
                true,
                true
            )
        );
        $result = ob_get_clean();

        $this->assertContains(
            '\\textbf{\\textit{name1}} & set(abc) & Yes & NULL & ' .
            'ftable (ffield) &  &  \\\\ \\hline',
            $result
        );

        // case 3

        $dbi = $this->getMockBuilder('PMA\libraries\DatabaseInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $dbi->expects($this->once())
            ->method('getTableIndexes')
            ->with('database', '')
            ->will($this->returnValue($keys));

        $dbi->expects($this->once())
            ->method('getColumns')
            ->with('database', '')
            ->will($this->returnValue($columns));

        $dbi->expects($this->any())
            ->method('query')
            ->will($this->returnValue(true));

        $dbi->expects($this->any())
            ->method('numRows')
            ->will($this->returnValue(1));

        $dbi->expects($this->any())
            ->method('fetchAssoc')
            ->will(
                $this->returnValue(
                    array(
                        'comment' => array('field' => 'testComment')
                    )
                )
            );

        $GLOBALS['dbi'] = $dbi;

        $GLOBALS['cfgRelation']['relation'] = true;
        $GLOBALS['latex_caption'] = true;
        $GLOBALS['latex_structure_caption'] = 'latexstructure';
        $GLOBALS['latex_structure_label'] = 'latexlabel';
        $GLOBALS['latex_structure_continued_caption'] = 'latexcontinued';
        $GLOBALS['cfg']['Server']['host'] = 'localhost';
        $GLOBALS['cfg']['Server']['verbose'] = 'verb';

        $_SESSION['relation'][0] = array(
            'PMA_VERSION' => PMA_VERSION,
            'relwork' => false,
            'commwork' => false,
            'mimework' => false,
            'db' => 'database',
            'relation' => 'rel',
            'column_info' => 'col'
        );

        ob_start();
        $this->assertTrue(
            $this->object->exportStructure(
                'database',
                '',
                "\n",
                "example.com",
                'test',
                'test'
            )
        );
        $result = ob_get_clean();

        $this->assertContains(
            '\\caption{latexstructure} \\label{latexlabel}',
            $result
        );

        $this->assertContains(
            'caption{latexcontinued}',
            $result
        );

        // case 4
        $this->assertTrue(
            $this->object->exportStructure(
                'database',
                '',
                "\n",
                "example.com",
                'triggers',
                'test'
            )
        );
    }

    /**
     * Test for PMA\libraries\plugins\export\ExportLatex::texEscape
     *
     * @return void
     */
    public function testTexEscape()
    {
        $this->assertEquals(
            '\\$\\%\\{foo\\&bar\\}\\#\\_\\^',
            ExportLatex::texEscape('$%{foo&bar}#_^')
        );
    }
}
