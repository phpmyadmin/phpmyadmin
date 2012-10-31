<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */

require_once 'libraries/Theme.class.php';
require_once 'libraries/core.lib.php';
require_once 'libraries/Util.class.php';
require_once 'libraries/Config.class.php';
require_once 'libraries/Theme_Manager.class.php';
require_once 'libraries/php-gettext/gettext.inc';
require_once 'libraries/sqlparser.lib.php';
require_once 'libraries/url_generating.lib.php';

/**
 * Test class for PMA_Theme.
 *
 * @package PhpMyAdmin-test
 */
class PMA_ThemeTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var PMA_Theme
     */
    protected $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     *
     * @return void
     */
    protected function setUp()
    {
        $this->object = new PMA_Theme();
        $_SESSION['PMA_Theme'] = $this->object;
        $GLOBALS['PMA_Config'] = new PMA_Config();
        $GLOBALS['PMA_Config']->enableBc();
        $GLOBALS['cfg']['SQP']['fmtColor'] = array('fake' => 'red');
        $GLOBALS['text_dir'] = 'ltr';
        include 'themes/pmahomme/layout.inc.php';
        $_SESSION[' PMA_token '] = 'token';
        $GLOBALS['lang'] = 'en';
        $GLOBALS['server'] = '99';
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     *
     * @return void
     */
    protected function tearDown()
    {
    }

    public function testCheckImgPathNotExisted()
    {
        $this->object->setPath('path/to/nowhere');
        $this->assertFalse($this->object->loadInfo());
    }

    public function testCheckImgPathIncorrect()
    {
        $this->object->setPath('./test/classes/_data/incorrect_theme');
        $this->assertFalse(
            $this->object->loadInfo(),
            'Theme name is not properly set'
        );
    }

    public function testCheckImgPathFull()
    {
        $this->object->setPath('./test/classes/_data/gen_version_info');
        $this->assertTrue($this->object->loadInfo());
        $this->assertEquals('Test Theme', $this->object->getName());
        $this->assertEquals('2.0.3', $this->object->getVersion());
    }

    public function testLoadInfo()
    {
        $this->object->setPath('./themes/original');
        $infofile = $this->object->getPath().'/info.inc.php';
        $this->assertTrue($this->object->loadInfo());

        $this->assertEquals(
            filemtime($infofile),
            $this->object->mtime_info
        );

        $this->object->setPath('./themes/original');
        $this->object->mtime_info = filemtime($infofile);
        $this->assertTrue($this->object->loadInfo());
        $this->assertEquals('Original', $this->object->getName());
    }

    public function testLoad()
    {
        $newTheme = PMA_Theme::load('./themes/original');
        $this->assertNotNull($newTheme);
    }

    public function testLoadNotExisted()
    {
        $this->assertFalse(PMA_Theme::load('/path/to/nowhere'));
    }

    /**
     *
     * @return void
     *
     * @expectedException PHPUnit_Framework_Error
     */
    public function testCheckImgPathBad()
    {
        $GLOBALS['cfg']['ThemePath'] = 'nowhere';
        $this->object->setPath('path/to/nowhere');

        $this->assertFalse($this->object->checkImgPath());
    }

    public function testCheckImgPath()
    {
        $this->object->setPath('./themes/original');
        $this->assertTrue($this->object->checkImgPath());
    }

    public function testCheckImgPathGlobals()
    {
        $this->object->setPath('/this/is/wrong/path');
        $GLOBALS['cfg']['ThemePath'] = 'themes';
        $this->assertTrue($this->object->checkImgPath());
    }

    /**
     *
     * @return void
     *
     * @expectedException PHPUnit_Framework_Error
     */
    public function testCheckImgPathGlobalsWrongPath()
    {
        $prevThemePath = $GLOBALS['cfg']['ThemePath'];
        $GLOBALS['cfg']['ThemePath'] = 'no_themes';

        $this->object->setPath('/this/is/wrong/path');
        $this->object->checkImgPath();

        $GLOBALS['cfg']['ThemePath'] = $prevThemePath;
    }

    /**
     *
     * @return void
     *
     * @covers PMA_Theme::setPath
     * @covers PMA_Theme::getPath
     */
    public function testGetSetPath()
    {
        $this->assertEmpty($this->object->getPath());
        $this->object->setPath('./themes/original');

        $this->assertEquals('./themes/original', $this->object->getPath());
    }

    public function testGetLayoutFile()
    {
        $this->assertContains('layout.inc.php', $this->object->getLayoutFile());
    }

    /**
     *
     * @return void
     *
     * @depends testLoadInfo
     */
    public function testGetSetCheckVersion()
    {
        $this->assertEquals(
            '0.0.0.0',
            $this->object->getVersion(),
            'Version 0.0.0.0 by default'
        );

        $this->object->setVersion("1.2.3.4");
        $this->assertEquals('1.2.3.4', $this->object->getVersion());

        $this->assertFalse($this->object->checkVersion("0.0.1.1"));
        $this->assertTrue($this->object->checkVersion("2.0.1.1"));
    }

    /**
     *
     * @return void
     *
     * @covers PMA_Theme::getName
     * @covers PMA_Theme::setName
     */
    public function testGetSetName()
    {
        $this->assertEmpty($this->object->getName(), 'Name is empty by default');
        $this->object->setName('New Theme Name');

        $this->assertEquals('New Theme Name', $this->object->getName());
    }

    /**
     *
     * @return void
     *
     * @covers PMA_Theme::getId
     * @covers PMA_Theme::setId
     */
    public function testGetSetId()
    {
        $this->assertEmpty($this->object->getId(), 'ID is empty by default');
        $this->object->setId('NewID');

        $this->assertEquals('NewID', $this->object->getId());
    }

    /**
     *
     * @return void
     *
     * @covers PMA_Theme::getImgPath
     * @covers PMA_Theme::setImgPath
     */
    public function testGetSetImgPath()
    {
        $this->assertEmpty(
            $this->object->getImgPath(),
            'ImgPath is empty by default'
        );
        $this->object->setImgPath('/new/path');

        $this->assertEquals('/new/path', $this->object->getImgPath());
    }

    /**
     * Test for getPrintPreview().
     *
     * @return void
     */
    public function testPrintPreview()
    {
        $this->assertEquals(
            $this->object->getPrintPreview(),
            '<div class="theme_preview"><h2> (0.0.0.0) </h2><p><a class="take_theme" name="" href="index.php?set_theme=&amp;server=99&amp;lang=en&amp;token=token">No preview available.[ <strong>take it</strong> ]</a></p></div>'
        );
    }

    /**
     * Test for getCssIEClearFilter
     *
     * @return void
     */
    public function testGetCssIEClearFilter()
    {
        $this->assertEquals(
            $this->object->getCssIEClearFilter(),
            ''
        );
    }

    /**
     * Test for getFontSize
     *
     * @return void
     */
    public function testGetFontSize()
    {
        $this->assertEquals(
            $this->object->getFontSize(),
            '82%'
        );

        $_COOKIE['pma_fontsize'] = '14px';
        $this->assertEquals(
            $this->object->getFontSize(),
            '14px'
        );

        $GLOBALS['PMA_Config']->set('fontsize', '12px');
        $this->assertEquals(
            $this->object->getFontSize(),
            '12px'
        );

    }

    /**
     * Test for getCssGradient
     *
     * @return void
     */
    public function testgetCssGradient()
    {
        $this->assertEquals(
            $this->object->getCssGradient('12345', '54321'),
            'background-image: url(./themes/svg_gradient.php?from=12345&to=54321);
background-size: 100% 100%;
background: -webkit-gradient(linear, left top, left bottom, from(#12345), to(#54321));
background: -webkit-linear-gradient(top, #12345, #54321);
background: -moz-linear-gradient(top, #12345, #54321);
background: -ms-linear-gradient(top, #12345, #54321);
background: -o-linear-gradient(top, #12345, #54321);'
        );
    }

    /**
     * Test for getCssCodeMirror
     *
     * @return void
     */
    public function testGetCssCodeMirror()
    {
        $this->assertEquals(
            $this->object->getCssCodeMirror(),
            'span.cm-keyword, span.cm-statement-verb {
    color: #909;
}
span.cm-variable {
    color: black;
}
span.cm-comment {
    color: #808000;
}
span.cm-mysql-string {
    color: #008000;
}
span.cm-operator {
    color: fuchsia;
}
span.cm-mysql-word {
    color: black;
}
span.cm-builtin {
    color: #f00;
}
span.cm-variable-2 {
    color: #f90;
}
span.cm-variable-3 {
    color: #00f;
}
span.cm-separator {
    color: fuchsia;
}
span.cm-number {
    color: teal;
}'
        );

        $GLOBALS['cfg']['CodemirrorEnable'] = false;
            $this->assertEquals(
                $this->object->getCssCodeMirror(),
                ''
            );
    }

    /**
     * Test for getImgPath
     *
     * @param string $file   file name for image
     * @param string $output expected output
     *
     * @return void
     *
     * @dataProvider providerForGetImgPath
     */
    public function testGetImgPath($file, $output)
    {
        $this->assertEquals(
            $this->object->getImgPath($file),
            $output
        );
    }

    /**
     * Provider for testGetImgPath
     *
     * @return array
     */
    public function providerForGetImgPath()
    {
        return array(
            array(
                null,
                ''
            ),
            array(
                'screen.png',
                './themes/pmahomme/img/screen.png'
            ),
            array(
                'arrow_ltr.png',
                './themes/pmahomme/img/arrow_ltr.png'
            )

        );
    }

    /**
     * Test for buildSQPCssRule
     *
     * @return void
     */
    public function testBuildSQPCssRule()
    {
        $this->assertEquals(
            $this->object->buildSQPCssRule('PMA_Config', 'fontSize', '12px'),
            '.PMA_Config {fontSize: 12px;}
'
        );
    }

    /**
     * Test for buildSQPCssData
     *
     * @return void
     */
    public function testBuildSQPCssData()
    {
        $this->assertEquals(
            $this->object->buildSQPCssData(),
            '.syntax_comment {color: #808000;}
.syntax_comment_mysql {}
.syntax_comment_ansi {}
.syntax_comment_c {}
.syntax_digit {}
.syntax_digit_hex {color: teal;}
.syntax_digit_integer {color: teal;}
.syntax_digit_float {color: aqua;}
.syntax_punct {color: fuchsia;}
.syntax_alpha {}
.syntax_alpha_columnType {color: #f90;}
.syntax_alpha_columnAttrib {color: #00f;}
.syntax_alpha_reservedWord {color: #909;}
.syntax_alpha_functionName {color: #f00;}
.syntax_alpha_identifier {color: black;}
.syntax_alpha_charset {color: #6495ed;}
.syntax_alpha_variable {color: #800000;}
.syntax_quote {color: #008000;}
.syntax_quote_double {}
.syntax_quote_single {}
.syntax_quote_backtick {}
.syntax_indent0 {margin-left: 0em;}
.syntax_indent1 {margin-left: 1em;}
.syntax_indent2 {margin-left: 2em;}
.syntax_indent3 {margin-left: 3em;}
.syntax_indent4 {margin-left: 4em;}
.syntax_indent5 {margin-left: 5em;}
.syntax_indent6 {margin-left: 6em;}
.syntax_indent7 {margin-left: 7em;}
'
        );
    }
}
?>
