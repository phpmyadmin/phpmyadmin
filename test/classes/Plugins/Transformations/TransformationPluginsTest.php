<?php
/**
 * Tests for all input/output transformation plugins
 */

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Plugins\Transformations;

use PhpMyAdmin\Plugins\Transformations\Input\Image_JPEG_Upload;
use PhpMyAdmin\Plugins\Transformations\Input\Text_Plain_FileUpload;
use PhpMyAdmin\Plugins\Transformations\Input\Text_Plain_Iptolong;
use PhpMyAdmin\Plugins\Transformations\Input\Text_Plain_RegexValidation;
use PhpMyAdmin\Plugins\Transformations\Output\Application_Octetstream_Download;
use PhpMyAdmin\Plugins\Transformations\Output\Application_Octetstream_Hex;
use PhpMyAdmin\Plugins\Transformations\Output\Image_JPEG_Inline;
use PhpMyAdmin\Plugins\Transformations\Output\Image_JPEG_Link;
use PhpMyAdmin\Plugins\Transformations\Output\Image_PNG_Inline;
use PhpMyAdmin\Plugins\Transformations\Output\Text_Plain_Dateformat;
use PhpMyAdmin\Plugins\Transformations\Output\Text_Plain_External;
use PhpMyAdmin\Plugins\Transformations\Output\Text_Plain_Formatted;
use PhpMyAdmin\Plugins\Transformations\Output\Text_Plain_Imagelink;
use PhpMyAdmin\Plugins\Transformations\Output\Text_Plain_Sql;
use PhpMyAdmin\Plugins\Transformations\Text_Plain_Link;
use PhpMyAdmin\Plugins\Transformations\Text_Plain_Longtoipv4;
use PhpMyAdmin\Plugins\Transformations\Text_Plain_PreApPend;
use PhpMyAdmin\Plugins\Transformations\Text_Plain_Substring;
use PhpMyAdmin\Tests\AbstractTestCase;
use ReflectionMethod;
use function date_default_timezone_set;
use function function_exists;
use function method_exists;

/**
 * Tests for different input/output transformation plugins
 */
class TransformationPluginsTest extends AbstractTestCase
{
    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     *
     * @access protected
     */
    protected function setUp(): void
    {
        parent::setUp();
        parent::setLanguage();
        // For Application Octetstream Download plugin
        global $row, $fields_meta;
        $fields_meta = [];
        $row = [
            'pma' => 'aaa',
            'pca' => 'bbb',
        ];

        // For Image_*_Inline plugin
        parent::setGlobalConfig();
        $GLOBALS['PMA_Config']->enableBc();
        $GLOBALS['Server'] = 1;

        // For Date Format plugin
        date_default_timezone_set('UTC');
    }

    /**
     * Data provider for testGetMulti
     */
    public function multiDataProvider(): array
    {
        return [
            // Test data for PhpMyAdmin\Plugins\Transformations\Input\Image_JPEG_Upload plugin
            [
                new Image_JPEG_Upload(),
                'getName',
                'Image upload',
            ],
            [
                new Image_JPEG_Upload(),
                'getInfo',
                'Image upload functionality which also displays a thumbnail.'
                . ' The options are the width and height of the thumbnail'
                . ' in pixels. Defaults to 100 X 100.',
            ],
            [
                new Image_JPEG_Upload(),
                'getMIMEType',
                'Image',
            ],
            [
                new Image_JPEG_Upload(),
                'getMIMESubtype',
                'JPEG',
            ],
            [
                new Image_JPEG_Upload(),
                'getScripts',
                ['transformations/image_upload.js'],
            ],
            [
                new Image_JPEG_Upload(),
                'getInputHtml',
                '<img src="" width="150" height="100" '
                . 'alt="Image preview here"><br><input type="file" '
                . 'name="fields_uploadtest" accept="image/*" class="image-upload">',
                [
                    [],
                    0,
                    'test',
                    ['150'],
                    '',
                    'ltr',
                    0,
                    0,
                    0,
                ],
            ],
            [
                new Image_JPEG_Upload(),
                'getInputHtml',
                '<input type="hidden" name="fields_prev2ndtest" '
                . 'value="736f6d657468696e67"><input type="hidden" '
                . 'name="fields2ndtest" value="736f6d657468696e67">'
                . '<img src="index.php?route=/transformation/wrapper&key=value&lang=en" width="100" '
                . 'height="100" alt="Image preview here"><br><input type="file" '
                . 'name="fields_upload2ndtest" accept="image/*" '
                . 'class="image-upload">',
                [
                    [],
                    0,
                    '2ndtest',
                    [
                        'wrapper_link' => '?table=a',
                        'wrapper_params' => ['key' => 'value'],
                    ],
                    'something',
                    'ltr',
                    0,
                    0,
                    0,
                ],
            ],
            // Test data for TextPlainFileupload plugin
            [
                new Text_Plain_FileUpload(),
                'getName',
                'Text file upload',
            ],
            [
                new Text_Plain_FileUpload(),
                'getInfo',
                'File upload functionality for TEXT columns. '
                . 'It does not have a textarea for input.',
            ],
            [
                new Text_Plain_FileUpload(),
                'getMIMEType',
                'Text',
            ],
            [
                new Text_Plain_FileUpload(),
                'getMIMESubtype',
                'Plain',
            ],
            [
                new Text_Plain_FileUpload(),
                'getScripts',
                [],
            ],
            [
                new Text_Plain_FileUpload(),
                'getInputHtml',
                '<input type="file" name="fields_uploadtest">',
                [
                    [],
                    0,
                    'test',
                    [],
                    '',
                    'ltr',
                    0,
                    0,
                    0,
                ],
            ],
            [
                new Text_Plain_FileUpload(),
                'getInputHtml',
                '<input type="hidden" name="fields_prev2ndtest" '
                . 'value="something"><input type="hidden" name="fields2ndtest" '
                . 'value="something"><input type="file" '
                . 'name="fields_upload2ndtest">',
                [
                    [],
                    0,
                    '2ndtest',
                    [],
                    'something',
                    'ltr',
                    0,
                    0,
                    0,
                ],
            ],
            // Test data for Text_Plain_Regexvalidation plugin
            [
                new Text_Plain_RegexValidation(),
                'getName',
                'Regex Validation',
            ],
            [
                new Text_Plain_RegexValidation(),
                'getInfo',
                'Validates the string using regular expression '
                . 'and performs insert only if string matches it. '
                . 'The first option is the Regular Expression.',
            ],
            [
                new Text_Plain_RegexValidation(),
                'getMIMEType',
                'Text',
            ],
            [
                new Text_Plain_RegexValidation(),
                'getMIMESubtype',
                'Plain',
            ],
            [
                new Text_Plain_RegexValidation(),
                'getInputHtml',
                '',
                [
                    [],
                    0,
                    '',
                    [],
                    '',
                    'ltr',
                    0,
                    0,
                    0,
                ],
            ],
            // Test data for PhpMyAdmin\Plugins\Transformations\Output\Application_Octetstream_Download plugin
            [
                new Application_Octetstream_Download(),
                'getName',
                'Download',
            ],
            [
                new Application_Octetstream_Download(),
                'getInfo',
                'Displays a link to download the binary data of the column. You can'
                . ' use the first option to specify the filename, or use the second'
                . ' option as the name of a column which contains the filename. If'
                . ' you use the second option, you need to set the first option to'
                . ' the empty string.',
            ],
            [
                new Application_Octetstream_Download(),
                'getMIMEType',
                'Application',
            ],
            [
                new Application_Octetstream_Download(),
                'getMIMESubtype',
                'OctetStream',
            ],
            // Test data for PhpMyAdmin\Plugins\Transformations\Output\Application_Octetstream_Hex plugin
            [
                new Application_Octetstream_Hex(),
                'getName',
                'Hex',
            ],
            [
                new Application_Octetstream_Hex(),
                'getInfo',
                'Displays hexadecimal representation of data. Optional first'
                . ' parameter specifies how often space will be added (defaults'
                . ' to 2 nibbles).',
            ],
            [
                new Application_Octetstream_Hex(),
                'getMIMEType',
                'Application',
            ],
            [
                new Application_Octetstream_Hex(),
                'getMIMESubtype',
                'OctetStream',
            ],
            // Test data for PhpMyAdmin\Plugins\Transformations\Output\Image_JPEG_Inline plugin
            [
                new Image_JPEG_Inline(),
                'getName',
                'Inline',
            ],
            [
                new Image_JPEG_Inline(),
                'getInfo',
                'Displays a clickable thumbnail. The options are the maximum width'
                . ' and height in pixels. The original aspect ratio is preserved.',
            ],
            [
                new Image_JPEG_Inline(),
                'getMIMEType',
                'Image',
            ],
            [
                new Image_JPEG_Inline(),
                'getMIMESubtype',
                'JPEG',
            ],
            // Test data for PhpMyAdmin\Plugins\Transformations\Output\Image_JPEG_Link plugin
            [
                new Image_JPEG_Link(),
                'getName',
                'ImageLink',
            ],
            [
                new Image_JPEG_Link(),
                'getInfo',
                'Displays a link to download this image.',
            ],
            [
                new Image_JPEG_Link(),
                'getMIMEType',
                'Image',
            ],
            [
                new Image_JPEG_Link(),
                'getMIMESubtype',
                'JPEG',
            ],
            [
                new Image_JPEG_Link(),
                'applyTransformationNoWrap',
                null,
            ],
            // Test data for PhpMyAdmin\Plugins\Transformations\Output\Image_PNG_Inline plugin
            [
                new Image_PNG_Inline(),
                'getName',
                'Inline',
            ],
            [
                new Image_PNG_Inline(),
                'getInfo',
                'Displays a clickable thumbnail. The options are the maximum width'
                . ' and height in pixels. The original aspect ratio is preserved.',
            ],
            [
                new Image_PNG_Inline(),
                'getMIMEType',
                'Image',
            ],
            [
                new Image_PNG_Inline(),
                'getMIMESubtype',
                'PNG',
            ],
            // Test data for PhpMyAdmin\Plugins\Transformations\Output\Text_Plain_Dateformat plugin
            [
                new Text_Plain_Dateformat(),
                'getName',
                'Date Format',
            ],
            [
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
                . ' is done using gmdate() function.',
            ],
            [
                new Text_Plain_Dateformat(),
                'getMIMEType',
                'Text',
            ],
            [
                new Text_Plain_Dateformat(),
                'getMIMESubtype',
                'Plain',
            ],
            // Test data for PhpMyAdmin\Plugins\Transformations\Output\Text_Plain_External plugin
            [
                new Text_Plain_External(),
                'getName',
                'External',
            ],
            [
                new Text_Plain_External(),
                'getInfo',
                'LINUX ONLY:'
                . ' Launches an external application and feeds it the column'
                . ' data via standard input. Returns the standard output of the'
                . ' application. The default is Tidy, to pretty-print HTML code.'
                . ' For security reasons, you have to manually edit the file'
                . ' libraries/classes/Plugins/Transformations/Abs/ExternalTransformationsPlugin'
                . '.php and list the tools you want to make available.'
                . ' The first option is then the number of the program you want to'
                . ' use. The second option should be blank for historical reasons.'
                . ' The third option, if set to 1, will convert the output using'
                . ' htmlspecialchars() (Default 1). The fourth option, if set to 1,'
                . ' will prevent wrapping and ensure that the output appears all on'
                . ' one line (Default 1).',
            ],
            [
                new Text_Plain_External(),
                'getMIMEType',
                'Text',
            ],
            [
                new Text_Plain_External(),
                'getMIMESubtype',
                'Plain',
            ],
            [
                new Text_Plain_External(),
                'applyTransformationNoWrap',
                true,
                [
                    [
                        '/dev/null -i -wrap -q',
                        '/dev/null -i -wrap -q',
                    ],
                ],
            ],
            [
                new Text_Plain_External(),
                'applyTransformationNoWrap',
                true,
                [
                    [
                        '/dev/null -i -wrap -q',
                        '/dev/null -i -wrap -q',
                        '/dev/null -i -wrap -q',
                        1,
                    ],
                ],
            ],
            [
                new Text_Plain_External(),
                'applyTransformationNoWrap',
                true,
                [
                    [
                        '/dev/null -i -wrap -q',
                        '/dev/null -i -wrap -q',
                        '/dev/null -i -wrap -q',
                        '1',
                    ],
                ],
            ],
            [
                new Text_Plain_External(),
                'applyTransformationNoWrap',
                false,
                [
                    [
                        '/dev/null -i -wrap -q',
                        '/dev/null -i -wrap -q',
                        '/dev/null -i -wrap -q',
                        2,
                    ],
                ],
            ],
            // Test data for PhpMyAdmin\Plugins\Transformations\Output\Text_Plain_Formatted plugin
            [
                new Text_Plain_Formatted(),
                'getName',
                'Formatted',
            ],
            [
                new Text_Plain_Formatted(),
                'getInfo',
                'Displays the contents of the column as-is, without running it'
                . ' through htmlspecialchars(). That is, the column is assumed'
                . ' to contain valid HTML.',
            ],
            [
                new Text_Plain_Formatted(),
                'getMIMEType',
                'Text',
            ],
            [
                new Text_Plain_Formatted(),
                'getMIMESubtype',
                'Plain',
            ],
            // Test data for PhpMyAdmin\Plugins\Transformations\Output\Text_Plain_Imagelink plugin
            [
                new Text_Plain_Imagelink(),
                'getName',
                'Image Link',
            ],
            [
                new Text_Plain_Imagelink(),
                'getInfo',
                'Displays an image and a link; '
                . 'the column contains the filename. The first option'
                . ' is a URL prefix like "https://www.example.com/". '
                . 'The second and third options'
                . ' are the width and the height in pixels.',
            ],
            [
                new Text_Plain_Imagelink(),
                'getMIMEType',
                'Text',
            ],
            [
                new Text_Plain_Imagelink(),
                'getMIMESubtype',
                'Plain',
            ],
            // Test data for PhpMyAdmin\Plugins\Transformations\Output\Text_Plain_Sql plugin
            [
                new Text_Plain_Sql(),
                'getName',
                'SQL',
            ],
            [
                new Text_Plain_Sql(),
                'getInfo',
                'Formats text as SQL query with syntax highlighting.',
            ],
            [
                new Text_Plain_Sql(),
                'getMIMEType',
                'Text',
            ],
            [
                new Text_Plain_Sql(),
                'getMIMESubtype',
                'Plain',
            ],
            // Test data for PhpMyAdmin\Plugins\Transformations\Text_Plain_Link plugin
            [
                new Text_Plain_Link(),
                'getName',
                'TextLink',
            ],
            [
                new Text_Plain_Link(),
                'getInfo',
                'Displays a link; the column contains the filename. The first option'
                . ' is a URL prefix like "https://www.example.com/".'
                . ' The second option is a title for the link.',
            ],
            [
                new Text_Plain_Link(),
                'getMIMEType',
                'Text',
            ],
            [
                new Text_Plain_Link(),
                'getMIMESubtype',
                'Plain',
            ],
            // Test data for PhpMyAdmin\Plugins\Transformations\Text_Plain_Longtoipv4 plugin
            [
                new Text_Plain_Longtoipv4(),
                'getName',
                'Long To IPv4',
            ],
            [
                new Text_Plain_Longtoipv4(),
                'getInfo',
                'Converts an (IPv4) Internet network address stored as a BIGINT'
                . ' into a string in Internet standard dotted format.',
            ],
            [
                new Text_Plain_Longtoipv4(),
                'getMIMEType',
                'Text',
            ],
            [
                new Text_Plain_Longtoipv4(),
                'getMIMESubtype',
                'Plain',
            ],
            // Test data for Text_Plain_PreApPend plugin
            [
                new Text_Plain_PreApPend(),
                'getName',
                'PreApPend',
            ],
            [
                new Text_Plain_PreApPend(),
                'getInfo',
                'Prepends and/or Appends text to a string. First option is text'
                . ' to be prepended, second is appended (enclosed in single'
                . ' quotes, default empty string).',
            ],
            [
                new Text_Plain_PreApPend(),
                'getMIMEType',
                'Text',
            ],
            [
                new Text_Plain_PreApPend(),
                'getMIMESubtype',
                'Plain',
            ],
            // Test data for PhpMyAdmin\Plugins\Transformations\Text_Plain_Substring plugin
            [
                new Text_Plain_Substring(),
                'getName',
                'Substring',
            ],
            [
                new Text_Plain_Substring(),
                'getInfo',
                'Displays a part of a string. The first option is the number '
                . 'of characters to skip from the beginning of the string '
                . '(Default 0). The second option is the number of characters '
                . 'to return (Default: until end of string). The third option is '
                . 'the string to append and/or prepend when truncation occurs '
                . '(Default: "…").',
            ],
            [
                new Text_Plain_Substring(),
                'getMIMEType',
                'Text',
            ],
            [
                new Text_Plain_Substring(),
                'getMIMESubtype',
                'Plain',
            ],
            [
                new Text_Plain_Substring(),
                'getOptions',
                [
                    'foo',
                    'bar',
                    'baz',
                ],
                [
                    [],
                    [
                        'foo',
                        'bar',
                        'baz',
                    ],
                ],
            ],
            [
                new Text_Plain_Substring(),
                'getOptions',
                [
                    'foo',
                    'bar',
                    'baz',
                ],
                [
                    [
                        'foo',
                        'bar',
                        'baz',
                    ],
                    [
                        'foo',
                        'bar',
                        'baz',
                    ],
                ],
            ],
            [
                new Text_Plain_Substring(),
                'getOptions',
                [
                    'foo',
                    'bar',
                    'baz',
                ],
                [
                    [
                        'foo',
                        'bar',
                        'baz',
                    ],
                    [
                        1,
                        2,
                        3,
                    ],
                ],
            ],
        ];
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
     * @dataProvider multiDataProvider
     * @group medium
     */
    public function testGetMulti($object, string $method, $expected, array $args = []): void
    {
        if (! method_exists($object, $method)) {
            return;
        }

        $reflectionMethod = new ReflectionMethod($object, $method);
        $this->assertEquals(
            $expected,
            $reflectionMethod->invokeArgs($object, $args)
        );
    }

    /**
     * Data provider for testTransformation
     */
    public function transformationDataProvider(): array
    {
        $result = [
            [
                new Image_JPEG_Upload(),
                [
                    'test',
                    [
                        150,
                        100,
                    ],
                ],
                'test',
            ],
            [
                new Text_Plain_FileUpload(),
                [
                    'test',
                    [],
                ],
                'test',
            ],
            [
                new Text_Plain_RegexValidation(),
                [
                    'phpMyAdmin',
                    ['/php/i'],
                ],
                'phpMyAdmin',
                true,
                '',
            ],
            [
                new Text_Plain_RegexValidation(),
                [
                    'qwerty',
                    ['/^a/'],
                ],
                'qwerty',
                false,
                'Validation failed for the input string qwerty.',
            ],
            [
                new Application_Octetstream_Download(),
                [
                    'PMA_BUFFER',
                    [
                        0 => 'filename',
                        'wrapper_link' => 'PMA_wrapper_link',
                        'wrapper_params' => ['key' => 'value'],
                    ],
                ],
                '<a href="index.php?route=/transformation/wrapper&key=value'
                . '&ct=application%2Foctet-stream&cn=filename&lang=en" '
                . 'title="filename" class="disableAjax">filename</a>',
            ],
            [
                new Application_Octetstream_Download(),
                [
                    'PMA_BUFFER',
                    [
                        0 => '',
                        1 => 'cloumn',
                        'wrapper_link' => 'PMA_wrapper_link',
                        'wrapper_params' => ['key' => 'value'],
                    ],
                ],
                '<a href="index.php?route=/transformation/wrapper&key=value'
                . '&ct=application%2Foctet-stream&cn=binary_file.dat&lang=en" '
                . 'title="binary_file.dat" class="disableAjax">binary_file.dat</a>',
            ],
            [
                new Application_Octetstream_Hex(),
                [
                    '11111001',
                    [3],
                ],
                '313 131 313 130 303 1 ',
            ],
            [
                new Application_Octetstream_Hex(),
                [
                    '11111001',
                    [0],
                ],
                '3131313131303031',
            ],
            [
                new Application_Octetstream_Hex(),
                [
                    '11111001',
                    [],
                ],
                '31 31 31 31 31 30 30 31 ',
            ],
            [
                new Image_JPEG_Link(),
                [
                    'PMA_IMAGE_LINK',
                    [
                        0 => './image/',
                        1 => '200',
                        'wrapper_link' => 'PMA_wrapper_link',
                        'wrapper_params' => ['key' => 'value'],
                    ],
                ],
                '<a class="disableAjax" target="_blank" rel="noopener noreferrer"'
                . ' href="index.php?route=/transformation/wrapper&key=value&lang=en"'
                . ' alt="[PMA_IMAGE_LINK]">[BLOB]</a>',
            ],
            [
                new Text_Plain_Dateformat(),
                [
                    12345,
                    [0],
                    ((object) ['type' => 'int']),
                ],
                '<dfn onclick="alert(\'12345\');" title="12345">'
                . 'Jan 01, 1970 at 03:25 AM</dfn>',
            ],
            [
                new Text_Plain_Dateformat(),
                [
                    12345678,
                    [0],
                    ((object) ['type' => 'string']),
                ],
                '<dfn onclick="alert(\'12345678\');" title="12345678">'
                . 'May 23, 1970 at 09:21 PM</dfn>',
            ],
            [
                new Text_Plain_Dateformat(),
                [
                    123456789,
                    [0],
                    ((object) ['type' => null]),
                ],
                '<dfn onclick="alert(\'123456789\');" title="123456789">'
                . 'Nov 29, 1973 at 09:33 PM</dfn>',
            ],
            [
                new Text_Plain_Dateformat(),
                [
                    '20100201',
                    [0],
                    ((object) ['type' => null]),
                ],
                '<dfn onclick="alert(\'20100201\');" title="20100201">'
                . 'Feb 01, 2010 at 12:00 AM</dfn>',
            ],
            [
                new Text_Plain_Dateformat(),
                [
                    '1617153941',
                    [
                        '0',
                        '%B %d, %Y at %I:%M %p',
                        'local',
                    ],
                    ((object) ['type' => null]),
                ],
                '<dfn onclick="alert(\'1617153941\');" title="1617153941">'
                . 'Mar 31, 2021 at 01:25 AM</dfn>',
            ],
            [
                new Text_Plain_Dateformat(),
                [
                    '1617153941',
                    [
                        '0',
                        '',// Empty uses the "Y-m-d  H:i:s" format
                        'utc',
                    ],
                    ((object) ['type' => null]),
                ],
                '<dfn onclick="alert(\'1617153941\');" title="1617153941">'
                . '2021-03-31  01:25:41</dfn>',
            ],
            [
                new Text_Plain_Dateformat(),
                [
                    '1617153941',
                    [
                        '0',
                        '',// Empty uses the "%B %d, %Y at %I:%M %p" format
                        'local',
                    ],
                    ((object) ['type' => null]),
                ],
                '<dfn onclick="alert(\'1617153941\');" title="1617153941">'
                . 'Mar 31, 2021 at 01:25 AM</dfn>',
            ],
            [
                new Text_Plain_Dateformat(),
                [
                    '1617153941',
                    [
                        '0',
                        'H:i:s Y-d-m',
                        'utc',
                    ],
                    ((object) ['type' => null]),
                ],
                '<dfn onclick="alert(\'1617153941\');" title="1617153941">'
                . '01:25:41 2021-31-03</dfn>',
            ],
            [
                new Text_Plain_External(),
                [
                    'PMA_BUFFER',
                    [
                        '/dev/null -i -wrap -q',
                        '/dev/null -i -wrap -q',
                    ],
                ],
                'PMA_BUFFER',
            ],
            [
                new Text_Plain_Formatted(),
                [
                    "<a ref='https://www.example.com/'>PMA_BUFFER</a>",
                    [
                        'option1',
                        'option2',
                    ],
                ],
                "<iframe srcdoc=\"<a ref='https://www.example.com/'>PMA_BUFFER</a>\" sandbox=\"\"></iframe>",
            ],
            [
                new Text_Plain_Formatted(),
                [
                    '<a ref="https://www.example.com/">PMA_BUFFER</a>',
                    [
                        'option1',
                        'option2',
                    ],
                ],
                "<iframe srcdoc=\"<a ref='https://www.example.com/'>PMA_BUFFER</a>\" sandbox=\"\"></iframe>",
            ],
            [
                new Text_Plain_Imagelink(),
                [
                    'PMA_IMAGE',
                    [
                        'http://image/',
                        '200',
                    ],
                ],
                '<a href="http://image/PMA_IMAGE" rel="noopener noreferrer" target="_blank">' . "\n"
                . '    <img src="http://image/PMA_IMAGE" border="0" width="200" height="50">' . "\n"
                . '    PMA_IMAGE' . "\n"
                . '</a>' . "\n",
            ],
            [
                new Text_Plain_Imagelink(),
                [
                    'PMA_IMAGE',
                    [
                        './image/',
                        '200',
                    ],
                ],
                './image/PMA_IMAGE',
            ],
            [
                new Text_Plain_Sql(),
                [
                    'select *',
                    [
                        'option1',
                        'option2',
                    ],
                ],
                '<code class="sql"><pre>' . "\n"
                . 'select *' . "\n"
                . '</pre></code>',
            ],
            [
                new Text_Plain_Link(),
                [
                    'PMA_TXT_LINK',
                    [
                        './php/',
                        'text_name',
                    ],
                ],
                './php/PMA_TXT_LINK',
            ],
            [
                new Text_Plain_Link(),
                [
                    'PMA_TXT_LINK',
                    [],
                ],
                'PMA_TXT_LINK',
            ],
            [
                new Text_Plain_Link(),
                [
                    'https://example.com/PMA_TXT_LINK',
                    [],
                ],
                '<a href="https://example.com/PMA_TXT_LINK" title=""'
                . ' target="_blank" rel="noopener noreferrer">https://example.com/PMA_TXT_LINK</a>',
            ],
            [
                new Text_Plain_Link(),
                [
                    'PMA_TXT_LINK',
                    [
                        './php/',
                        'text_name',
                    ],
                ],
                './php/PMA_TXT_LINK',
            ],
            [
                new Text_Plain_Longtoipv4(),
                [
                    42949672,
                    [
                        'option1',
                        'option2',
                    ],
                ],
                '2.143.92.40',
            ],
            [
                new Text_Plain_Longtoipv4(),
                [
                    4294967295,
                    [
                        'option1',
                        'option2',
                    ],
                ],
                '255.255.255.255',
            ],
            [
                new Text_Plain_PreApPend(),
                [
                    'My',
                    [
                        'php',
                        'Admin',
                    ],
                ],
                'phpMyAdmin',
            ],
            [
                new Text_Plain_Substring(),
                [
                    'PMA_BUFFER',
                    [
                        1,
                        3,
                        'suffix',
                    ],
                ],
                'suffixMA_suffix',
            ],
            [
                new Text_Plain_Substring(),
                [
                    'PMA_BUFFER',
                    [
                        '1',
                        '3',
                        'suffix',
                    ],
                ],
                'suffixMA_suffix',
            ],
            [
                new Text_Plain_Substring(),
                [
                    'PMA_BUFFER',
                    ['2'],
                ],
                '…A_BUFFER',
            ],
            [
                new Text_Plain_Substring(),
                [
                    'PMA_BUFFER',
                    [2],
                ],
                '…A_BUFFER',
            ],
            [
                new Text_Plain_Substring(),
                [
                    'PMA_BUFFER',
                    [0],
                ],
                'PMA_BUFFER',
            ],
            [
                new Text_Plain_Substring(),
                [
                    'PMA_BUFFER',
                    ['0'],
                ],
                'PMA_BUFFER',
            ],
            [
                new Text_Plain_Substring(),
                [
                    'PMA_BUFFER',
                    [
                        -1,
                    ],
                ],
                '…R…',
            ],
            [
                new Text_Plain_Substring(),
                [
                    'PMA_BUFFER',
                    ['-1'],
                ],
                '…R…',
            ],
            [
                new Text_Plain_Substring(),
                [
                    'PMA_BUFFER',
                    [
                        0,
                        2,
                    ],
                ],
                'PM…',
            ],
            [
                new Text_Plain_Substring(),
                [
                    'PMA_BUFFER',
                    [
                        '0',
                        '2',
                    ],
                ],
                'PM…',
            ],
            [
                new Text_Plain_Substring(),
                [
                    2,
                    [],
                ],
                '2',
            ],
            [
                new Text_Plain_Longtoipv4(),
                [168496141],
                '10.11.12.13',
            ],
            [
                new Text_Plain_Longtoipv4(),
                ['168496141'],
                '10.11.12.13',
            ],
            [
                new Text_Plain_Longtoipv4(),
                ['my ip'],
                'my ip',
            ],
            [
                new Text_Plain_Longtoipv4(),
                ['<my ip>'],
                '&lt;my ip&gt;',
            ],
            [
                new Text_Plain_Iptolong(),
                ['10.11.12.13'],
                168496141,
            ],
            [
                new Text_Plain_Iptolong(),
                ['10.11.12.913'],
                '10.11.12.913',
            ],
            [
                new Text_Plain_Iptolong(),
                ['my ip'],
                'my ip',
            ],
            [
                new Text_Plain_Iptolong(),
                ['<my ip>'],
                '<my ip>',
            ],
        ];

        if (function_exists('imagecreatetruecolor')) {
            $result[] = [
                new Image_JPEG_Inline(),
                [
                    'PMA_JPEG_Inline',
                    [
                        0 => './image/',
                        1 => '200',
                        'wrapper_link' => 'PMA_wrapper_link',
                        'wrapper_params' => ['key' => 'value'],
                    ],
                ],
                '<a href="index.php?route=/transformation/wrapper&key=value&lang=en" '
                . 'rel="noopener noreferrer" target="_blank"><img src="index.php?route=/transformation/wrapper'
                . '&key=value&resize=jpeg&newWidth=0&'
                . 'newHeight=200&lang=en" alt="[PMA_JPEG_Inline]" border="0"></a>',
            ];
            $result[] = [
                new Image_PNG_Inline(),
                [
                    'PMA_PNG_Inline',
                    [
                        0 => './image/',
                        1 => '200',
                        'wrapper_link' => 'PMA_wrapper_link',
                        'wrapper_params' => ['key' => 'value'],
                    ],
                ],
                '<a href="index.php?route=/transformation/wrapper&key=value&lang=en"'
                . ' rel="noopener noreferrer" target="_blank"><img src="index.php?route=/transformation/wrapper'
                . '&key=value&resize=jpeg&newWidth=0&newHeight=200&lang=en" '
                . 'alt="[PMA_PNG_Inline]" border="0"></a>',
            ];
        }

        return $result;
    }

    /**
     * Tests for applyTransformation, isSuccess, getError
     *
     * @param object     $object      instance of the plugin
     * @param array      $applyArgs   arguments for applyTransformation
     * @param string|int $transformed the expected output of applyTransformation
     * @param bool       $success     the expected output of isSuccess
     * @param string     $error       the expected output of getError
     *
     * @dataProvider transformationDataProvider
     * @group medium
     */
    public function testTransformation(
        $object,
        array $applyArgs,
        $transformed,
        bool $success = true,
        string $error = ''
    ): void {
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
        if (! method_exists($object, 'getError')) {
            return;
        }

        $this->assertEquals(
            $error,
            $object->getError()
        );
    }
}
