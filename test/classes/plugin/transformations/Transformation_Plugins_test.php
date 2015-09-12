<?php
/**
 * Tests for all input/output transformation plugins
 *
 * @package PhpMyAdmin-test
 */

use PMA\libraries\plugins\transformations\input\ImageJPEGUpload;
use PMA\libraries\plugins\transformations\input\TextPlainRegexValidation;
use PMA\libraries\plugins\transformations\input\TextPlainFileUpload;
use PMA\libraries\plugins\transformations\output\ApplicationOctetstreamDownload;
use PMA\libraries\plugins\transformations\output\ApplicationOctetstreamHex;
use PMA\libraries\plugins\transformations\output\ImageJPEGInline;
use PMA\libraries\plugins\transformations\output\ImageJPEGLink;
use PMA\libraries\plugins\transformations\output\ImagePNGInline;
use PMA\libraries\plugins\transformations\output\TextPlainDateformat;
use PMA\libraries\plugins\transformations\output\TextPlainExternal;
use PMA\libraries\plugins\transformations\output\TextPlainFormatted;
use PMA\libraries\plugins\transformations\output\TextPlainImagelink;
use PMA\libraries\plugins\transformations\output\TextPlainSql;
use PMA\libraries\plugins\transformations\TextPlainLink;
use PMA\libraries\plugins\transformations\TextPlainLongtoipv4;
use PMA\libraries\plugins\transformations\TextPlainPreApPend;
use PMA\libraries\plugins\transformations\TextPlainSubstring;

/*
 * Include to test.
 */
require_once 'libraries/php-gettext/gettext.inc';
require_once 'libraries/config.default.php';

/**
 * Tests for different input/output transformation plugins
 *
 * @package PhpMyAdmin-test
 */
class Transformation_Plugins_Test extends PHPUnit_Framework_TestCase
{
    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     *
     * @access protected
     * @return void
     */
    protected function setUp()
    {
        // For Application Octetstream Download plugin
        global $row, $fields_meta;
        $fields_meta = array();
        $row = array("pma"=>"aaa", "pca"=>"bbb");

        // For Image_*_Inline plugin
        $GLOBALS['PMA_Config'] = new PMA\libraries\Config();
        $GLOBALS['PMA_Config']->enableBc();

        // For Date Format plugin
        date_default_timezone_set('UTC');
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     *
     * @access protected
     * @return void
     */
    protected function tearDown()
    {
    }

    /**
     * Data provider for testGetMulti
     *
     * @return array with test data
     */
    public function multiDataProvider()
    {
        return array(
            // Test data for PMA\libraries\plugins\transformations\input\ImageJPEGUpload plugin
            array(
                new ImageJPEGUpload(),
                'getName',
                'Image upload'
            ),
            array(
                new ImageJPEGUpload(),
                'getInfo',
                'Image upload functionality which also displays a thumbnail.'
                . ' The options are the width and height of the thumbnail'
                . ' in pixels. Defaults to 100 X 100.'
            ),
            array(
                new ImageJPEGUpload(),
                'getMIMEType',
                'Image'
            ),
            array(
                new ImageJPEGUpload(),
                'getMIMESubtype',
                'JPEG'
            ),
            array(
                new ImageJPEGUpload(),
                'getScripts',
                array('transformations/image_upload.js')
            ),
            array(
                new ImageJPEGUpload(),
                'getInputHtml',
                '<img src="" width="150" height="100" '
                . 'alt="Image preview here"/><br/><input type="file" '
                . 'name="fields_uploadtest" accept="image/*" class="image-upload"/>',
                array(
                    array(),
                    0,
                    'test',
                    array('150'),
                    '',
                    'ltr',
                    0,
                    0,
                    0
                )
            ),
            array(
                new ImageJPEGUpload(),
                'getInputHtml',
                '<input type="hidden" name="fields_prev2ndtest" '
                . 'value="736f6d657468696e67"/><input type="hidden" '
                . 'name="fields2ndtest" value="736f6d657468696e67"/>'
                . '<img src="transformation_wrapper.php?table=a" width="100" '
                . 'height="100" alt="Image preview here"/><br/><input type="file" '
                . 'name="fields_upload2ndtest" accept="image/*" '
                . 'class="image-upload"/>',
                array(
                    array(),
                    0,
                    '2ndtest',
                    array(
                        'wrapper_link' => '?table=a'
                    ),
                    'something',
                    'ltr',
                    0,
                    0,
                    0
                )
            ),
            // Test data for TextPlainFileupload plugin
            array(
                new TextPlainFileUpload(),
                'getName',
                'Text file upload'
            ),
            array(
                new TextPlainFileUpload(),
                'getInfo',
                'File upload functionality for TEXT columns. '
                . 'It does not have a textarea for input.'
            ),
            array(
                new TextPlainFileUpload(),
                'getMIMEType',
                'Text'
            ),
            array(
                new TextPlainFileUpload(),
                'getMIMESubtype',
                'Plain'
            ),
            array(
                new TextPlainFileUpload(),
                'getScripts',
                array()
            ),
            array(
                new TextPlainFileUpload(),
                'getInputHtml',
                '<input type="file" name="fields_uploadtest"/>',
                array(
                    array(),
                    0,
                    'test',
                    array(),
                    '',
                    'ltr',
                    0,
                    0,
                    0
                )
            ),
            array(
                new TextPlainFileUpload(),
                'getInputHtml',
                '<input type="hidden" name="fields_prev2ndtest" '
                . 'value="something"/><input type="hidden" name="fields2ndtest" '
                . 'value="something"/><input type="file" '
                . 'name="fields_upload2ndtest"/>',
                array(
                    array(),
                    0,
                    '2ndtest',
                    array(),
                    'something',
                    'ltr',
                    0,
                    0,
                    0
                )
            ),
            // Test data for Text_Plain_Regexvalidation plugin
            array(
                new TextPlainRegexValidation(),
                'getName',
                'Regex Validation'
            ),
            array(
                new TextPlainRegexValidation(),
                'getInfo',
                'Validates the string using regular expression '
                . 'and performs insert only if string matches it. '
                . 'The first option is the Regular Expression.'
            ),
            array(
                new TextPlainRegexValidation(),
                'getMIMEType',
                'Text'
            ),
            array(
                new TextPlainRegexValidation(),
                'getMIMESubtype',
                'Plain'
            ),
            array(
                new TextPlainRegexValidation(),
                'getInputHtml',
                '',
                array(
                    array(), 0, '', array(), '', 'ltr', 0, 0, 0
                )
            ),
            // Test data for PMA\libraries\plugins\transformations\output\ApplicationOctetstreamDownload plugin
            array(
                new ApplicationOctetstreamDownload(),
                'getName',
                'Download'
            ),
            array(
                new ApplicationOctetstreamDownload(),
                'getInfo',
                'Displays a link to download the binary data of the column. You can'
                . ' use the first option to specify the filename, or use the second'
                . ' option as the name of a column which contains the filename. If'
                . ' you use the second option, you need to set the first option to'
                . ' the empty string.'
            ),
            array(
                new ApplicationOctetstreamDownload(),
                'getMIMEType',
                'Application'
            ),
            array(
                new ApplicationOctetstreamDownload(),
                'getMIMESubtype',
                'OctetStream'
            ),
            // Test data for PMA\libraries\plugins\transformations\output\ApplicationOctetstreamHex plugin
            array(
                new ApplicationOctetstreamHex(),
                'getName',
                'Hex'
            ),
            array(
                new ApplicationOctetstreamHex(),
                'getInfo',
                'Displays hexadecimal representation of data. Optional first'
                . ' parameter specifies how often space will be added (defaults'
                . ' to 2 nibbles).'
            ),
            array(
                new ApplicationOctetstreamHex(),
                'getMIMEType',
                'Application'
            ),
            array(
                new ApplicationOctetstreamHex(),
                'getMIMESubtype',
                'OctetStream'
            ),
            // Test data for PMA\libraries\plugins\transformations\output\ImageJPEGInline plugin
            array(
                new ImageJPEGInline(),
                'getName',
                'Inline'
            ),
            array(
                new ImageJPEGInline(),
                'getInfo',
                'Displays a clickable thumbnail. The options are the maximum width'
                . ' and height in pixels. The original aspect ratio is preserved.'
            ),
            array(
                new ImageJPEGInline(),
                'getMIMEType',
                'Image'
            ),
            array(
                new ImageJPEGInline(),
                'getMIMESubtype',
                'JPEG'
            ),
            // Test data for PMA\libraries\plugins\transformations\output\ImageJPEGLink plugin
            array(
                new ImageJPEGLink(),
                'getName',
                'ImageLink'
            ),
            array(
                new ImageJPEGLink(),
                'getInfo',
                'Displays a link to download this image.'
            ),
            array(
                new ImageJPEGLink(),
                'getMIMEType',
                'Image'
            ),
            array(
                new ImageJPEGLink(),
                'getMIMESubtype',
                'JPEG'
            ),
            array(
                new ImageJPEGLink(),
                'applyTransformationNoWrap',
                null
            ),
            // Test data for PMA\libraries\plugins\transformations\output\ImagePNGInline plugin
            array(
                new ImagePNGInline(),
                'getName',
                'Inline'
            ),
            array(
                new ImagePNGInline(),
                'getInfo',
                'Displays a clickable thumbnail. The options are the maximum width'
                . ' and height in pixels. The original aspect ratio is preserved.'
            ),
            array(
                new ImagePNGInline(),
                'getMIMEType',
                'Image'
            ),
            array(
                new ImagePNGInline(),
                'getMIMESubtype',
                'PNG'
            ),
            // Test data for PMA\libraries\plugins\transformations\output\TextPlainDateformat plugin
            array(
                new TextPlainDateformat(),
                'getName',
                'Date Format'
            ),
            array(
                new TextPlainDateformat(),
                'getInfo',
                'Displays a TIME, TIMESTAMP, DATETIME or numeric unix timestamp'
                . ' column as formatted date. The first option is the offset (in'
                . ' hours) which will be added to the timestamp (Default: 0). Use'
                . ' second option to specify a different date/time format string.'
                . ' Third option determines whether you want to see local date or'
                . ' UTC one (use "local" or "utc" strings) for that. According to'
                . ' that, date format has different value - for "local" see the'
                . ' documentation for PHP\'s strftime() function and for "utc" it'
                . ' is done using gmdate() function.'
            ),
            array(
                new TextPlainDateformat(),
                'getMIMEType',
                'Text'
            ),
            array(
                new TextPlainDateformat(),
                'getMIMESubtype',
                'Plain'
            ),
            // Test data for PMA\libraries\plugins\transformations\output\TextPlainExternal plugin
            array(
                new TextPlainExternal(),
                'getName',
                'External'
            ),
            array(
                new TextPlainExternal(),
                'getInfo',
                'LINUX ONLY:'
                . ' Launches an external application and feeds it the column'
                . ' data via standard input. Returns the standard output of the'
                . ' application. The default is Tidy, to pretty-print HTML code.'
                . ' For security reasons, you have to manually edit the file'
                . ' libraries/plugins/transformations/output/TextPlainExternal'
                . '.php and list the tools you want to make available.'
                . ' The first option is then the number of the program you want to'
                . ' use and the second option is the parameters for the program.'
                . ' The third option, if set to 1, will convert the output using'
                . ' htmlspecialchars() (Default 1). The fourth option, if set to 1,'
                . ' will prevent wrapping and ensure that the output appears all on'
                . ' one line (Default 1).'
            ),
            array(
                new TextPlainExternal(),
                'getMIMEType',
                'Text'
            ),
            array(
                new TextPlainExternal(),
                'getMIMESubtype',
                'Plain'
            ),
            array(
                new TextPlainExternal(),
                'applyTransformationNoWrap',
                true,
                array(
                    array("/dev/null -i -wrap -q", "/dev/null -i -wrap -q")
                )
            ),
            array(
                new TextPlainExternal(),
                'applyTransformationNoWrap',
                true,
                array(
                    array(
                        "/dev/null -i -wrap -q",
                        "/dev/null -i -wrap -q",
                        "/dev/null -i -wrap -q", 1
                    )
                )
            ),
            array(
                new TextPlainExternal(),
                'applyTransformationNoWrap',
                true,
                array(
                    array(
                        "/dev/null -i -wrap -q",
                        "/dev/null -i -wrap -q",
                        "/dev/null -i -wrap -q", "1"
                    )
                )
            ),
            array(
                new TextPlainExternal(),
                'applyTransformationNoWrap',
                false,
                array(
                    array(
                        "/dev/null -i -wrap -q",
                        "/dev/null -i -wrap -q",
                        "/dev/null -i -wrap -q",
                        2
                    )
                )
            ),
            // Test data for PMA\libraries\plugins\transformations\output\TextPlainFormatted plugin
            array(
                new TextPlainFormatted(),
                'getName',
                'Formatted'
            ),
            array(
                new TextPlainFormatted(),
                'getInfo',
                'Displays the contents of the column as-is, without running it'
                . ' through htmlspecialchars(). That is, the column is assumed'
                . ' to contain valid HTML.'
            ),
            array(
                new TextPlainFormatted(),
                'getMIMEType',
                'Text'
            ),
            array(
                new TextPlainFormatted(),
                'getMIMESubtype',
                'Plain'
            ),
            // Test data for PMA\libraries\plugins\transformations\output\TextPlainImagelink plugin
            array(
                new TextPlainImagelink(),
                'getName',
                'Image Link'
            ),
            array(
                new TextPlainImagelink(),
                'getInfo',
                'Displays an image and a link; '
                . 'the column contains the filename. The first option'
                . ' is a URL prefix like "http://www.example.com/". '
                . 'The second and third options'
                . ' are the width and the height in pixels.'
            ),
            array(
                new TextPlainImagelink(),
                'getMIMEType',
                'Text'
            ),
            array(
                new TextPlainImagelink(),
                'getMIMESubtype',
                'Plain'
            ),
            // Test data for PMA\libraries\plugins\transformations\output\TextPlainSql plugin
            array(
                new TextPlainSql(),
                'getName',
                'SQL'
            ),
            array(
                new TextPlainSql(),
                'getInfo',
                'Formats text as SQL query with syntax highlighting.'
            ),
            array(
                new TextPlainSql(),
                'getMIMEType',
                'Text'
            ),
            array(
                new TextPlainSql(),
                'getMIMESubtype',
                'Plain'
            ),
            // Test data for PMA\libraries\plugins\transformations\TextPlainLink plugin
            array(
                new TextPlainLink(),
                'getName',
                'TextLink'
            ),
            array(
                new TextPlainLink(),
                'getInfo',
                'Displays a link; the column contains the filename. The first option'
                . ' is a URL prefix like "http://www.example.com/".'
                . ' The second option is a title for the link.'
            ),
            array(
                new TextPlainLink(),
                'getMIMEType',
                'Text'
            ),
            array(
                new TextPlainLink(),
                'getMIMESubtype',
                'Plain'
            ),
            // Test data for PMA\libraries\plugins\transformations\TextPlainLongtoipv4 plugin
            array(
                new TextPlainLongtoipv4(),
                'getName',
                'Long To IPv4'
            ),
            array(
                new TextPlainLongtoipv4(),
                'getInfo',
                'Converts an (IPv4) Internet network address stored as a BIGINT'
                . ' into a string in Internet standard dotted format.'
            ),
            array(
                new TextPlainLongtoipv4(),
                'getMIMEType',
                'Text'
            ),
            array(
                new TextPlainLongtoipv4(),
                'getMIMESubtype',
                'Plain'
            ),
            // Test data for TextPlainPreApPend plugin
            array(
                new TextPlainPreApPend(),
                'getName',
                'PreApPend'
            ),
            array(
                new TextPlainPreApPend(),
                'getInfo',
                'Prepends and/or Appends text to a string. First option is text'
                . ' to be prepended, second is appended (enclosed in single'
                . ' quotes, default empty string).'
            ),
            array(
                new TextPlainPreApPend(),
                'getMIMEType',
                'Text'
            ),
            array(
                new TextPlainPreApPend(),
                'getMIMESubtype',
                'Plain'
            ),
            // Test data for PMA\libraries\plugins\transformations\TextPlainSubstring plugin
            array(
                new TextPlainSubstring(),
                'getName',
                'Substring'
            ),
            array(
                new TextPlainSubstring(),
                'getInfo',
                'Displays a part of a string. The first option is the number '
                . 'of characters to skip from the beginning of the string '
                . '(Default 0). The second option is the number of characters '
                . 'to return (Default: until end of string). The third option is '
                . 'the string to append and/or prepend when truncation occurs '
                . '(Default: "…").'
            ),
            array(
                new TextPlainSubstring(),
                'getMIMEType',
                'Text'
            ),
            array(
                new TextPlainSubstring(),
                'getMIMESubtype',
                'Plain'
            ),
            array(
                new TextPlainSubstring(),
                'getOptions',
                array('foo', 'bar', 'baz'),
                array(
                    array(),
                    array('foo', 'bar', 'baz')
                )
            ),
            array(
                new TextPlainSubstring(),
                'getOptions',
                array('foo', 'bar', 'baz'),
                array(
                    array('foo', 'bar', 'baz'),
                    array('foo', 'bar', 'baz')
                )
            ),
            array(
                new TextPlainSubstring(),
                'getOptions',
                array('foo', 'bar', 'baz'),
                array(
                    array('foo', 'bar', 'baz'),
                    array(1, 2, 3)
                )
            ),
        );
    }

    /**
     * Tests for getInfo, getName, getMIMEType, getMIMESubtype
     * getScripts, applyTransformationNoWrap, getOptions
     *
     * @param object $object   instance of the plugin
     * @param string $method   the method name
     * @param mixed  $expected the expected output
     * @param array  $args     the array of arguments
     *
     * @return void
     *
     * @dataProvider multiDataProvider
     * @group medium
     */
    public function testGetMulti($object, $method, $expected, $args = array())
    {
        if (method_exists($object, $method)) {
            $reflectionMethod = new ReflectionMethod($object, $method);
            $this->assertEquals(
                $expected,
                $reflectionMethod->invokeArgs($object, $args)
            );
        }
    }

    /**
     * Data provider for testTransformation
     *
     * @return array with test data
     */
    public function transformationDataProvider()
    {
        return array(
            array(
                new ImageJPEGUpload(),
                array(
                    'test',
                    array(150, 100)
                ),
                'test'
            ),
            array(
                new TextPlainFileUpload(),
                array(
                    'test',
                    array()
                ),
                'test'
            ),
            array(
                new TextPlainRegexValidation(),
                array(
                    'phpMyAdmin',
                    array('/php/i')
                ),
                'phpMyAdmin',
                true,
                ''
            ),
            array(
                new TextPlainRegexValidation(),
                array(
                    'qwerty',
                    array('/^a/')
                ),
                'qwerty',
                false,
                'Validation failed for the input string qwerty.'
            ),
            array(
                new ApplicationOctetstreamDownload(),
                array(
                    'PMA_BUFFER',
                    array("filename", 'wrapper_link'=>'PMA_wrapper_link')
                ),
                '<a href="transformation_wrapper.phpPMA_wrapper_link'
                . '&amp;ct=application/octet-stream&amp;cn=filename" '
                . 'title="filename" class="disableAjax">filename</a>'
            ),
            array(
                new ApplicationOctetstreamDownload(),
                array(
                    'PMA_BUFFER',
                    array("", 'cloumn', 'wrapper_link'=>'PMA_wrapper_link')
                ),
                '<a href="transformation_wrapper.phpPMA_wrapper_link&amp;'
                . 'ct=application/octet-stream&amp;cn=binary_file.dat" '
                . 'title="binary_file.dat" class="disableAjax">binary_file.dat</a>'
            ),
            array(
                new ApplicationOctetstreamHex(),
                array(
                    '11111001',
                    array(3)
                ),
                '313 131 313 130 303 1 '
            ),
            array(
                new ApplicationOctetstreamHex(),
                array(
                    '11111001',
                    array(0)
                ),
                '3131313131303031'
            ),
            array(
                new ApplicationOctetstreamHex(),
                array(
                    '11111001',
                    array()
                ),
                '31 31 31 31 31 30 30 31 '
            ),
            array(
                new ImageJPEGInline(),
                array(
                    'PMA_JPEG_Inline',
                    array("./image/", "200", "wrapper_link"=>"PMA_wrapper_link")
                ),
                '<a href="transformation_wrapper.phpPMA_wrapper_link" '
                . 'target="_blank"><img src="transformation_wrapper.php'
                . 'PMA_wrapper_link&amp;resize=jpeg&amp;newWidth=./image/&amp;'
                . 'newHeight=200" alt="PMA_JPEG_Inline" border="0" /></a>'
            ),
            array(
                new ImageJPEGLink(),
                array(
                    'PMA_IMAGE_LINK',
                    array("./image/", "200", "wrapper_link"=>"PMA_wrapper_link")
                ),
                '<a class="disableAjax" target="_new"'
                . ' href="transformation_wrapper.phpPMA_wrapper_link"'
                . ' alt="PMA_IMAGE_LINK">[BLOB]</a>'
            ),
            array(
                new ImagePNGInline(),
                array(
                    'PMA_PNG_Inline',
                    array("./image/", "200", "wrapper_link"=>"PMA_wrapper_link")
                ),
                '<a href="transformation_wrapper.phpPMA_wrapper_link"'
                . ' target="_blank"><img src="transformation_wrapper.php'
                . 'PMA_wrapper_link&amp;'
                . 'resize=jpeg&amp;newWidth=./image/&amp;newHeight=200" '
                . 'alt="PMA_PNG_Inline" border="0" /></a>'
            ),
            array(
                new TextPlainDateformat(),
                array(
                    12345,
                    array(0),
                    (object) array(
                        'type' => 'int'
                    )
                ),
                '<dfn onclick="alert(\'12345\');" title="12345">'
                . 'Jan 01, 1970 at 03:25 AM</dfn>'
            ),
            array(
                new TextPlainDateformat(),
                array(
                    12345678,
                    array(0),
                    (object) array(
                        'type' => 'string'
                    )
                ),
                '<dfn onclick="alert(\'12345678\');" title="12345678">'
                . 'May 23, 1970 at 09:21 PM</dfn>'
            ),
            array(
                new TextPlainDateformat(),
                array(
                    123456789,
                    array(0),
                    (object) array(
                        'type' => null
                    )
                ),
                '<dfn onclick="alert(\'123456789\');" title="123456789">'
                . 'Nov 29, 1973 at 09:33 PM</dfn>'
            ),
            array(
                new TextPlainDateformat(),
                array(
                    '20100201',
                    array(0),
                    (object) array(
                        'type' => null
                    )
                ),
                '<dfn onclick="alert(\'20100201\');" title="20100201">'
                . 'Feb 01, 2010 at 12:00 AM</dfn>'
            ),
            array(
                new TextPlainExternal(),
                array(
                    'PMA_BUFFER',
                    array("/dev/null -i -wrap -q", "/dev/null -i -wrap -q")
                ),
                'PMA_BUFFER'
            ),
            array(
                new TextPlainFormatted(),
                array(
                    "<a ref='http://ci.phpmyadmin.net/'>PMA_BUFFER</a>",
                    array("option1", "option2")
                ),
                "<a ref='http://ci.phpmyadmin.net/'>PMA_BUFFER</a>"
            ),
            array(
                new TextPlainImagelink(),
                array(
                    'PMA_IMAGE',
                    array("./image/", "200")
                ),
                '<a href="./image/PMA_IMAGE" target="_blank">'
                . '<img src="./image/PMA_IMAGE" border="0" width="200" '
                . 'height="50" />PMA_IMAGE</a>'
            ),
            array(
                new TextPlainSql(),
                array(
                    'select *',
                    array("option1", "option2")
                ),
                '<code class="sql"><pre>' . "\n"
                . 'select *' . "\n"
                . '</pre></code>'
            ),
            array(
                new TextPlainLink(),
                array(
                    'PMA_TXT_LINK',
                    array("./php/", "text_name")
                ),
                '<a href="./php/PMA_TXT_LINK"'
                . ' title="text_name" target="_new">text_name</a>'
            ),
            array(
                new TextPlainLongtoipv4(),
                array(
                    42949672,
                    array("option1", "option2")
                ),
                '2.143.92.40'
            ),
            array(
                new TextPlainLongtoipv4(),
                array(
                    4294967295,
                    array("option1", "option2")
                ),
                '255.255.255.255'
            ),
            array(
                new TextPlainPreApPend(),
                array(
                    'My',
                    array('php', 'Admin')
                ),
                'phpMyAdmin'
            ),
            array(
                new TextPlainSubstring(),
                array(
                    'PMA_BUFFER',
                    array(1, 3, 'suffix')
                ),
                'suffixMA_suffix'
            ),
        );
    }

    /**
     * Tests for applyTransformation, isSuccess, getError
     *
     * @param object $object      instance of the plugin
     * @param array  $applyArgs   arguments for applyTransformation
     * @param string $transformed the expected output of applyTransformation
     * @param bool   $success     the expected output of isSuccess
     * @param string $error       the expected output of getError
     *
     * @return void
     *
     * @dataProvider transformationDataProvider
     * @group medium
     */
    public function testTransformation(
        $object, $applyArgs, $transformed, $success = true, $error = ''
    ) {
        $reflectionMethod = new ReflectionMethod($object, 'applyTransformation');
        $this->assertEquals(
            $transformed,
            $reflectionMethod->invokeArgs($object, $applyArgs)
        );

        // For output transformation plugins, this method may not exist
        if (method_exists($object, 'isSuccess')) {
            $this->assertEquals(
                $success,
                $object->isSuccess()
            );
        }

        // For output transformation plugins, this method may not exist
        if (method_exists($object, 'getError')) {
            $this->assertEquals(
                $error,
                $object->getError()
            );
        }
    }
}
