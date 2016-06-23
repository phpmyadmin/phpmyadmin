<?php
/**
 * Tests for all input/output transformation plugins
 *
 * @package PhpMyAdmin-test
 */

use PMA\libraries\plugins\transformations\input\Image_JPEG_Upload;
use PMA\libraries\plugins\transformations\input\Text_Plain_RegexValidation;
use PMA\libraries\plugins\transformations\input\Text_Plain_FileUpload;
use PMA\libraries\plugins\transformations\output\Application_Octetstream_Download;
use PMA\libraries\plugins\transformations\output\Application_Octetstream_Hex;
use PMA\libraries\plugins\transformations\output\Image_JPEG_Inline;
use PMA\libraries\plugins\transformations\output\Image_JPEG_Link;
use PMA\libraries\plugins\transformations\output\Image_PNG_Inline;
use PMA\libraries\plugins\transformations\output\Text_Plain_Dateformat;
use PMA\libraries\plugins\transformations\output\Text_Plain_External;
use PMA\libraries\plugins\transformations\output\Text_Plain_Formatted;
use PMA\libraries\plugins\transformations\output\Text_Plain_Imagelink;
use PMA\libraries\plugins\transformations\output\Text_Plain_Sql;
use PMA\libraries\plugins\transformations\Text_Plain_Link;
use PMA\libraries\plugins\transformations\Text_Plain_Longtoipv4;
use PMA\libraries\plugins\transformations\Text_Plain_PreApPend;
use PMA\libraries\plugins\transformations\Text_Plain_Substring;

/*
 * Include to test.
 */
require_once 'libraries/config.default.php';
require_once 'test/PMATestCase.php';

/**
 * Tests for different input/output transformation plugins
 *
 * @package PhpMyAdmin-test
 */
class TransformationPluginsTest extends PMATestCase
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
            // Test data for PMA\libraries\plugins\transformations\input\Image_JPEG_Upload plugin
            array(
                new Image_JPEG_Upload(),
                'getName',
                'Image upload'
            ),
            array(
                new Image_JPEG_Upload(),
                'getInfo',
                'Image upload functionality which also displays a thumbnail.'
                . ' The options are the width and height of the thumbnail'
                . ' in pixels. Defaults to 100 X 100.'
            ),
            array(
                new Image_JPEG_Upload(),
                'getMIMEType',
                'Image'
            ),
            array(
                new Image_JPEG_Upload(),
                'getMIMESubtype',
                'JPEG'
            ),
            array(
                new Image_JPEG_Upload(),
                'getScripts',
                array('transformations/image_upload.js')
            ),
            array(
                new Image_JPEG_Upload(),
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
                new Image_JPEG_Upload(),
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
                new Text_Plain_FileUpload(),
                'getName',
                'Text file upload'
            ),
            array(
                new Text_Plain_FileUpload(),
                'getInfo',
                'File upload functionality for TEXT columns. '
                . 'It does not have a textarea for input.'
            ),
            array(
                new Text_Plain_FileUpload(),
                'getMIMEType',
                'Text'
            ),
            array(
                new Text_Plain_FileUpload(),
                'getMIMESubtype',
                'Plain'
            ),
            array(
                new Text_Plain_FileUpload(),
                'getScripts',
                array()
            ),
            array(
                new Text_Plain_FileUpload(),
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
                new Text_Plain_FileUpload(),
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
                new Text_Plain_RegexValidation(),
                'getName',
                'Regex Validation'
            ),
            array(
                new Text_Plain_RegexValidation(),
                'getInfo',
                'Validates the string using regular expression '
                . 'and performs insert only if string matches it. '
                . 'The first option is the Regular Expression.'
            ),
            array(
                new Text_Plain_RegexValidation(),
                'getMIMEType',
                'Text'
            ),
            array(
                new Text_Plain_RegexValidation(),
                'getMIMESubtype',
                'Plain'
            ),
            array(
                new Text_Plain_RegexValidation(),
                'getInputHtml',
                '',
                array(
                    array(), 0, '', array(), '', 'ltr', 0, 0, 0
                )
            ),
            // Test data for PMA\libraries\plugins\transformations\output\Application_Octetstream_Download plugin
            array(
                new Application_Octetstream_Download(),
                'getName',
                'Download'
            ),
            array(
                new Application_Octetstream_Download(),
                'getInfo',
                'Displays a link to download the binary data of the column. You can'
                . ' use the first option to specify the filename, or use the second'
                . ' option as the name of a column which contains the filename. If'
                . ' you use the second option, you need to set the first option to'
                . ' the empty string.'
            ),
            array(
                new Application_Octetstream_Download(),
                'getMIMEType',
                'Application'
            ),
            array(
                new Application_Octetstream_Download(),
                'getMIMESubtype',
                'OctetStream'
            ),
            // Test data for PMA\libraries\plugins\transformations\output\Application_Octetstream_Hex plugin
            array(
                new Application_Octetstream_Hex(),
                'getName',
                'Hex'
            ),
            array(
                new Application_Octetstream_Hex(),
                'getInfo',
                'Displays hexadecimal representation of data. Optional first'
                . ' parameter specifies how often space will be added (defaults'
                . ' to 2 nibbles).'
            ),
            array(
                new Application_Octetstream_Hex(),
                'getMIMEType',
                'Application'
            ),
            array(
                new Application_Octetstream_Hex(),
                'getMIMESubtype',
                'OctetStream'
            ),
            // Test data for PMA\libraries\plugins\transformations\output\Image_JPEG_Inline plugin
            array(
                new Image_JPEG_Inline(),
                'getName',
                'Inline'
            ),
            array(
                new Image_JPEG_Inline(),
                'getInfo',
                'Displays a clickable thumbnail. The options are the maximum width'
                . ' and height in pixels. The original aspect ratio is preserved.'
            ),
            array(
                new Image_JPEG_Inline(),
                'getMIMEType',
                'Image'
            ),
            array(
                new Image_JPEG_Inline(),
                'getMIMESubtype',
                'JPEG'
            ),
            // Test data for PMA\libraries\plugins\transformations\output\Image_JPEG_Link plugin
            array(
                new Image_JPEG_Link(),
                'getName',
                'ImageLink'
            ),
            array(
                new Image_JPEG_Link(),
                'getInfo',
                'Displays a link to download this image.'
            ),
            array(
                new Image_JPEG_Link(),
                'getMIMEType',
                'Image'
            ),
            array(
                new Image_JPEG_Link(),
                'getMIMESubtype',
                'JPEG'
            ),
            array(
                new Image_JPEG_Link(),
                'applyTransformationNoWrap',
                null
            ),
            // Test data for PMA\libraries\plugins\transformations\output\Image_PNG_Inline plugin
            array(
                new Image_PNG_Inline(),
                'getName',
                'Inline'
            ),
            array(
                new Image_PNG_Inline(),
                'getInfo',
                'Displays a clickable thumbnail. The options are the maximum width'
                . ' and height in pixels. The original aspect ratio is preserved.'
            ),
            array(
                new Image_PNG_Inline(),
                'getMIMEType',
                'Image'
            ),
            array(
                new Image_PNG_Inline(),
                'getMIMESubtype',
                'PNG'
            ),
            // Test data for PMA\libraries\plugins\transformations\output\Text_Plain_Dateformat plugin
            array(
                new Text_Plain_Dateformat(),
                'getName',
                'Date Format'
            ),
            array(
                new Text_Plain_Dateformat(),
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
                new Text_Plain_Dateformat(),
                'getMIMEType',
                'Text'
            ),
            array(
                new Text_Plain_Dateformat(),
                'getMIMESubtype',
                'Plain'
            ),
            // Test data for PMA\libraries\plugins\transformations\output\Text_Plain_External plugin
            array(
                new Text_Plain_External(),
                'getName',
                'External'
            ),
            array(
                new Text_Plain_External(),
                'getInfo',
                'LINUX ONLY:'
                . ' Launches an external application and feeds it the column'
                . ' data via standard input. Returns the standard output of the'
                . ' application. The default is Tidy, to pretty-print HTML code.'
                . ' For security reasons, you have to manually edit the file'
                . ' libraries/plugins/transformations/output/Text_Plain_External'
                . '.php and list the tools you want to make available.'
                . ' The first option is then the number of the program you want to'
                . ' use and the second option is the parameters for the program.'
                . ' The third option, if set to 1, will convert the output using'
                . ' htmlspecialchars() (Default 1). The fourth option, if set to 1,'
                . ' will prevent wrapping and ensure that the output appears all on'
                . ' one line (Default 1).'
            ),
            array(
                new Text_Plain_External(),
                'getMIMEType',
                'Text'
            ),
            array(
                new Text_Plain_External(),
                'getMIMESubtype',
                'Plain'
            ),
            array(
                new Text_Plain_External(),
                'applyTransformationNoWrap',
                true,
                array(
                    array("/dev/null -i -wrap -q", "/dev/null -i -wrap -q")
                )
            ),
            array(
                new Text_Plain_External(),
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
                new Text_Plain_External(),
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
                new Text_Plain_External(),
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
            // Test data for PMA\libraries\plugins\transformations\output\Text_Plain_Formatted plugin
            array(
                new Text_Plain_Formatted(),
                'getName',
                'Formatted'
            ),
            array(
                new Text_Plain_Formatted(),
                'getInfo',
                'Displays the contents of the column as-is, without running it'
                . ' through htmlspecialchars(). That is, the column is assumed'
                . ' to contain valid HTML.'
            ),
            array(
                new Text_Plain_Formatted(),
                'getMIMEType',
                'Text'
            ),
            array(
                new Text_Plain_Formatted(),
                'getMIMESubtype',
                'Plain'
            ),
            // Test data for PMA\libraries\plugins\transformations\output\Text_Plain_Imagelink plugin
            array(
                new Text_Plain_Imagelink(),
                'getName',
                'Image Link'
            ),
            array(
                new Text_Plain_Imagelink(),
                'getInfo',
                'Displays an image and a link; '
                . 'the column contains the filename. The first option'
                . ' is a URL prefix like "http://www.example.com/". '
                . 'The second and third options'
                . ' are the width and the height in pixels.'
            ),
            array(
                new Text_Plain_Imagelink(),
                'getMIMEType',
                'Text'
            ),
            array(
                new Text_Plain_Imagelink(),
                'getMIMESubtype',
                'Plain'
            ),
            // Test data for PMA\libraries\plugins\transformations\output\Text_Plain_Sql plugin
            array(
                new Text_Plain_Sql(),
                'getName',
                'SQL'
            ),
            array(
                new Text_Plain_Sql(),
                'getInfo',
                'Formats text as SQL query with syntax highlighting.'
            ),
            array(
                new Text_Plain_Sql(),
                'getMIMEType',
                'Text'
            ),
            array(
                new Text_Plain_Sql(),
                'getMIMESubtype',
                'Plain'
            ),
            // Test data for PMA\libraries\plugins\transformations\Text_Plain_Link plugin
            array(
                new Text_Plain_Link(),
                'getName',
                'TextLink'
            ),
            array(
                new Text_Plain_Link(),
                'getInfo',
                'Displays a link; the column contains the filename. The first option'
                . ' is a URL prefix like "http://www.example.com/".'
                . ' The second option is a title for the link.'
            ),
            array(
                new Text_Plain_Link(),
                'getMIMEType',
                'Text'
            ),
            array(
                new Text_Plain_Link(),
                'getMIMESubtype',
                'Plain'
            ),
            // Test data for PMA\libraries\plugins\transformations\Text_Plain_Longtoipv4 plugin
            array(
                new Text_Plain_Longtoipv4(),
                'getName',
                'Long To IPv4'
            ),
            array(
                new Text_Plain_Longtoipv4(),
                'getInfo',
                'Converts an (IPv4) Internet network address stored as a BIGINT'
                . ' into a string in Internet standard dotted format.'
            ),
            array(
                new Text_Plain_Longtoipv4(),
                'getMIMEType',
                'Text'
            ),
            array(
                new Text_Plain_Longtoipv4(),
                'getMIMESubtype',
                'Plain'
            ),
            // Test data for Text_Plain_PreApPend plugin
            array(
                new Text_Plain_PreApPend(),
                'getName',
                'PreApPend'
            ),
            array(
                new Text_Plain_PreApPend(),
                'getInfo',
                'Prepends and/or Appends text to a string. First option is text'
                . ' to be prepended, second is appended (enclosed in single'
                . ' quotes, default empty string).'
            ),
            array(
                new Text_Plain_PreApPend(),
                'getMIMEType',
                'Text'
            ),
            array(
                new Text_Plain_PreApPend(),
                'getMIMESubtype',
                'Plain'
            ),
            // Test data for PMA\libraries\plugins\transformations\Text_Plain_Substring plugin
            array(
                new Text_Plain_Substring(),
                'getName',
                'Substring'
            ),
            array(
                new Text_Plain_Substring(),
                'getInfo',
                'Displays a part of a string. The first option is the number '
                . 'of characters to skip from the beginning of the string '
                . '(Default 0). The second option is the number of characters '
                . 'to return (Default: until end of string). The third option is '
                . 'the string to append and/or prepend when truncation occurs '
                . '(Default: "…").'
            ),
            array(
                new Text_Plain_Substring(),
                'getMIMEType',
                'Text'
            ),
            array(
                new Text_Plain_Substring(),
                'getMIMESubtype',
                'Plain'
            ),
            array(
                new Text_Plain_Substring(),
                'getOptions',
                array('foo', 'bar', 'baz'),
                array(
                    array(),
                    array('foo', 'bar', 'baz')
                )
            ),
            array(
                new Text_Plain_Substring(),
                'getOptions',
                array('foo', 'bar', 'baz'),
                array(
                    array('foo', 'bar', 'baz'),
                    array('foo', 'bar', 'baz')
                )
            ),
            array(
                new Text_Plain_Substring(),
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
                new Image_JPEG_Upload(),
                array(
                    'test',
                    array(150, 100)
                ),
                'test'
            ),
            array(
                new Text_Plain_FileUpload(),
                array(
                    'test',
                    array()
                ),
                'test'
            ),
            array(
                new Text_Plain_RegexValidation(),
                array(
                    'phpMyAdmin',
                    array('/php/i')
                ),
                'phpMyAdmin',
                true,
                ''
            ),
            array(
                new Text_Plain_RegexValidation(),
                array(
                    'qwerty',
                    array('/^a/')
                ),
                'qwerty',
                false,
                'Validation failed for the input string qwerty.'
            ),
            array(
                new Application_Octetstream_Download(),
                array(
                    'PMA_BUFFER',
                    array("filename", 'wrapper_link'=>'PMA_wrapper_link')
                ),
                '<a href="transformation_wrapper.phpPMA_wrapper_link'
                . '&amp;ct=application/octet-stream&amp;cn=filename" '
                . 'title="filename" class="disableAjax">filename</a>'
            ),
            array(
                new Application_Octetstream_Download(),
                array(
                    'PMA_BUFFER',
                    array("", 'cloumn', 'wrapper_link'=>'PMA_wrapper_link')
                ),
                '<a href="transformation_wrapper.phpPMA_wrapper_link&amp;'
                . 'ct=application/octet-stream&amp;cn=binary_file.dat" '
                . 'title="binary_file.dat" class="disableAjax">binary_file.dat</a>'
            ),
            array(
                new Application_Octetstream_Hex(),
                array(
                    '11111001',
                    array(3)
                ),
                '313 131 313 130 303 1 '
            ),
            array(
                new Application_Octetstream_Hex(),
                array(
                    '11111001',
                    array(0)
                ),
                '3131313131303031'
            ),
            array(
                new Application_Octetstream_Hex(),
                array(
                    '11111001',
                    array()
                ),
                '31 31 31 31 31 30 30 31 '
            ),
            array(
                new Image_JPEG_Inline(),
                array(
                    'PMA_JPEG_Inline',
                    array("./image/", "200", "wrapper_link"=>"PMA_wrapper_link")
                ),
                '<a href="transformation_wrapper.phpPMA_wrapper_link" '
                . 'target="_blank"><img src="transformation_wrapper.php'
                . 'PMA_wrapper_link&amp;resize=jpeg&amp;newWidth=./image/&amp;'
                . 'newHeight=200" alt="[PMA_JPEG_Inline]" border="0" /></a>'
            ),
            array(
                new Image_JPEG_Link(),
                array(
                    'PMA_IMAGE_LINK',
                    array("./image/", "200", "wrapper_link"=>"PMA_wrapper_link")
                ),
                '<a class="disableAjax" target="_new"'
                . ' href="transformation_wrapper.phpPMA_wrapper_link"'
                . ' alt="[PMA_IMAGE_LINK]">[BLOB]</a>'
            ),
            array(
                new Image_PNG_Inline(),
                array(
                    'PMA_PNG_Inline',
                    array("./image/", "200", "wrapper_link"=>"PMA_wrapper_link")
                ),
                '<a href="transformation_wrapper.phpPMA_wrapper_link"'
                . ' target="_blank"><img src="transformation_wrapper.php'
                . 'PMA_wrapper_link&amp;'
                . 'resize=jpeg&amp;newWidth=./image/&amp;newHeight=200" '
                . 'alt="[PMA_PNG_Inline]" border="0" /></a>'
            ),
            array(
                new Text_Plain_Dateformat(),
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
                new Text_Plain_Dateformat(),
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
                new Text_Plain_Dateformat(),
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
                new Text_Plain_Dateformat(),
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
                new Text_Plain_External(),
                array(
                    'PMA_BUFFER',
                    array("/dev/null -i -wrap -q", "/dev/null -i -wrap -q")
                ),
                'PMA_BUFFER'
            ),
            array(
                new Text_Plain_Formatted(),
                array(
                    "<a ref='http://ci.phpmyadmin.net/'>PMA_BUFFER</a>",
                    array("option1", "option2")
                ),
                "<a ref='http://ci.phpmyadmin.net/'>PMA_BUFFER</a>"
            ),
            array(
                new Text_Plain_Imagelink(),
                array(
                    'PMA_IMAGE',
                    array("./image/", "200")
                ),
                '<a href="./image/PMA_IMAGE" target="_blank">'
                . '<img src="./image/PMA_IMAGE" border="0" width="200" '
                . 'height="50" />PMA_IMAGE</a>'
            ),
            array(
                new Text_Plain_Sql(),
                array(
                    'select *',
                    array("option1", "option2")
                ),
                '<code class="sql"><pre>' . "\n"
                . 'select *' . "\n"
                . '</pre></code>'
            ),
            array(
                new Text_Plain_Link(),
                array(
                    'PMA_TXT_LINK',
                    array("./php/", "text_name")
                ),
                '<a href="./php/PMA_TXT_LINK"'
                . ' title="text_name" target="_new">text_name</a>'
            ),
            array(
                new Text_Plain_Longtoipv4(),
                array(
                    42949672,
                    array("option1", "option2")
                ),
                '2.143.92.40'
            ),
            array(
                new Text_Plain_Longtoipv4(),
                array(
                    4294967295,
                    array("option1", "option2")
                ),
                '255.255.255.255'
            ),
            array(
                new Text_Plain_PreApPend(),
                array(
                    'My',
                    array('php', 'Admin')
                ),
                'phpMyAdmin'
            ),
            array(
                new Text_Plain_Substring(),
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
