<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Test class for Theme.
 *
 * @package PhpMyAdmin-test
 */
use PMA\libraries\Theme;

require_once 'libraries/url_generating.lib.php';
require_once 'test/PMATestCase.php';

/**
 * Test class for Theme.
 *
 * @package PhpMyAdmin-test
 */
class ThemeTest extends PMATestCase
{
    /**
     * @var Theme
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
        $this->object = new Theme();
        $_SESSION['PMA_Theme'] = $this->object;
        $GLOBALS['PMA_Config'] = new PMA\libraries\Config();
        $GLOBALS['PMA_Config']->enableBc();
        $GLOBALS['text_dir'] = 'ltr';
        include 'themes/pmahomme/layout.inc.php';
        $GLOBALS['server'] = '99';
        $GLOBALS['collation_connection'] = 'utf-8';
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

    /**
     * Test for Theme::loadInfo
     *
     * @return void
     * @group medium
     */
    public function testCheckImgPathNotExisted()
    {
        $this->object->setPath('path/to/nowhere');
        $this->assertFalse($this->object->loadInfo());
    }

    /**
     * Test for Theme::loadInfo
     *
     * @return void
     */
    public function testCheckImgPathIncorrect()
    {
        $this->object->setPath('./test/classes/_data/incorrect_theme');
        $this->assertFalse(
            $this->object->loadInfo(),
            'Theme name is not properly set'
        );
    }

    /**
     * Test for Theme::getName, getVersion
     *
     * @return void
     */
    public function testCheckImgPathFull()
    {
        $this->object->setPath('./test/classes/_data/gen_version_info');
        $this->assertTrue($this->object->loadInfo());
        $this->assertEquals('Test Theme', $this->object->getName());
        $this->assertEquals('2.0.3', $this->object->getVersion());
    }

    /**
     * Test for Theme::loadInfo
     *
     * @return void
     */
    public function testLoadInfo()
    {
        $this->object->setPath('./themes/original');
        $infofile = $this->object->getPath() . '/info.inc.php';
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

    /**
     * Test for Theme::load
     *
     * @return void
     */
    public function testLoad()
    {
        $newTheme = Theme::load('./themes/original');
        $this->assertNotNull($newTheme);
    }

    /**
     * Test for Theme::loadCss
     *
     * @param string $theme Path to theme files
     *
     * @return void
     *
     * @dataProvider listThemes
     */
    public function testLoadCss($theme)
    {
        $newTheme = Theme::load($theme);
        ob_start();
        $ret = $newTheme->loadCss();
        $out = ob_get_contents();
        ob_end_clean();
        $this->assertTrue($ret);
        $this->assertContains('FILE: navigation.css.php', $out);
    }

    /**
     * Data provider for Theme::loadCss test
     *
     * @return array with theme paths
     */
    public function listThemes()
    {
        return array(
            array('./themes/original'),
            array('./themes/pmahomme/'),
        );
    }

    /**
     * Test for Theme::load
     *
     * @return void
     */
    public function testLoadNotExisted()
    {
        $this->assertFalse(Theme::load('/path/to/nowhere'));
    }

    /**
     * Test fir Theme::checkImgPath
     *
     * @return void
     * @expectedException PHPUnit_Framework_Error
     */
    public function testCheckImgPathBad()
    {
        $GLOBALS['cfg']['ThemePath'] = 'nowhere';
        $this->object->setPath('path/to/nowhere');

        $this->assertFalse($this->object->checkImgPath());
    }

    /**
     * Test for Theme::checkImgPath
     *
     * @return void
     */
    public function testCheckImgPath()
    {
        $this->object->setPath('./themes/original');
        $this->assertTrue($this->object->checkImgPath());
    }

    /**
     * Test for Theme::checkImgPath
     *
     * @return void
     */
    public function testCheckImgPathGlobals()
    {
        $this->object->setPath('/this/is/wrong/path');
        $GLOBALS['cfg']['ThemePath'] = 'themes';
        $this->assertTrue($this->object->checkImgPath());
    }

    /**
     * Test for Theme::checkImgPath
     *
     * @return void
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
     * Test for Theme::getPath
     *
     * @return void
     */
    public function testGetSetPath()
    {
        $this->assertEmpty($this->object->getPath());
        $this->object->setPath('./themes/original');

        $this->assertEquals('./themes/original', $this->object->getPath());
    }

    /**
     * Test for Theme::loadInfo
     *
     * @return void
     */
    public function testGetLayoutFile()
    {
        $this->assertContains('layout.inc.php', $this->object->getLayoutFile());
    }

    /**
     * Test for Theme::checkVersion
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
     * Test for Theme::getName
     *
     * @return void
     */
    public function testGetSetName()
    {
        $this->assertEmpty($this->object->getName(), 'Name is empty by default');
        $this->object->setName('New Theme Name');

        $this->assertEquals('New Theme Name', $this->object->getName());
    }

    /**
     * Test for Theme::getId
     *
     * @return void
     */
    public function testGetSetId()
    {
        $this->assertEmpty($this->object->getId(), 'ID is empty by default');
        $this->object->setId('NewID');

        $this->assertEquals('NewID', $this->object->getId());
    }

    /**
     * Test for Theme::getImgPath
     *
     * @return void
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
            '<div class="theme_preview"><h2> (0.0.0.0) </h2><p><a class="take_'
            . 'theme" name="" href="index.php?set_theme=&amp;server=99&amp;lang=en'
            . '&amp;collation_connection=utf-8'
            . '&amp;token=token">No preview available.[ <strong>take it</strong> ]'
            . '</a></p></div>'
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
            'background-image: url(./themes/svg_gradient.php?from=12345&to=54321);'
            . "\n" . 'background-size: 100% 100%;'
            . "\n" . 'background: -webkit-gradient(linear, left top, left bottom, '
            . 'from(#12345), to(#54321));'
            . "\n" . 'background: -webkit-linear-gradient(top, #12345, #54321);'
            . "\n" . 'background: -moz-linear-gradient(top, #12345, #54321);'
            . "\n" . 'background: -ms-linear-gradient(top, #12345, #54321);'
            . "\n" . 'background: -o-linear-gradient(top, #12345, #54321);'
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
}
