<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Test class for Theme.
 *
 * @package PhpMyAdmin-test
 */
declare(strict_types=1);

namespace PhpMyAdmin\Tests;

use PhpMyAdmin\Config;
use PhpMyAdmin\Tests\PmaTestCase;
use PhpMyAdmin\Theme;

/**
 * Test class for Theme.
 *
 * @package PhpMyAdmin-test
 */
class ThemeTest extends PmaTestCase
{
    /**
     * @var Theme
     */
    protected $object;

    /**
     * @var Theme backup for session theme
     */
    protected $backup;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->object = new Theme();
        $this->backup = $GLOBALS['PMA_Theme'];
        $GLOBALS['PMA_Theme'] = $this->object;
        $GLOBALS['PMA_Config'] = new Config();
        $GLOBALS['PMA_Config']->enableBc();
        $GLOBALS['text_dir'] = 'ltr';
        $GLOBALS['server'] = '99';
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        $GLOBALS['PMA_Theme'] = $this->backup;
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
        $this->object->setPath(ROOT_PATH . 'test/classes/_data/incorrect_theme');
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
        $this->object->setPath(ROOT_PATH . 'test/classes/_data/gen_version_info');
        $this->assertTrue($this->object->loadInfo());
        $this->assertEquals('Test Theme', $this->object->getName());
        $this->assertEquals('5.0', $this->object->getVersion());
    }

    /**
     * Test for Theme::loadInfo
     *
     * @return void
     */
    public function testLoadInfo()
    {
        $this->object->setPath(ROOT_PATH . 'themes/original');
        $infofile = $this->object->getPath() . '/theme.json';
        $this->assertTrue($this->object->loadInfo());

        $this->assertEquals(
            filemtime($infofile),
            $this->object->mtime_info
        );

        $this->object->setPath(ROOT_PATH . 'themes/original');
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
        $newTheme = Theme::load(ROOT_PATH . 'themes/original');
        $this->assertNotNull($newTheme);
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
     */
    public function testCheckImgPathFallback()
    {
        $this->object->setPath('path/to/nowhere');
        $this->assertTrue($this->object->checkImgPath());
    }

    /**
     * Test for Theme::checkImgPath
     *
     * @return void
     */
    public function testCheckImgPath()
    {
        $this->object->setPath(ROOT_PATH . 'themes/original');
        $this->assertTrue($this->object->checkImgPath());
    }

    /**
     * Test for Theme::getPath
     *
     * @return void
     */
    public function testGetSetPath()
    {
        $this->assertEmpty($this->object->getPath());
        $this->object->setPath(ROOT_PATH . 'themes/original');

        $this->assertEquals(ROOT_PATH . 'themes/original', $this->object->getPath());
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
    public function testGetPrintPreview()
    {
        $this->assertStringContainsString(
            '<h2>' . "\n" . '         (0.0.0.0)',
            $this->object->getPrintPreview()
        );
        $this->assertStringContainsString(
            'name="" href="index.php?set_theme=&amp;server=99&amp;lang=en">',
            $this->object->getPrintPreview()
        );
        $this->assertStringContainsString(
            'No preview available.',
            $this->object->getPrintPreview()
        );
    }

    /**
     * Test for getImgPath
     *
     * @param string $file     file name for image
     * @param string $fallback fallback image
     * @param string $output   expected output
     *
     * @return void
     *
     * @dataProvider providerForGetImgPath
     */
    public function testGetImgPath($file, $fallback, $output): void
    {
        $this->assertEquals(
            $this->object->getImgPath($file, $fallback),
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
        return [
            [
                null,
                null,
                '',
            ],
            [
                'screen.png',
                null,
                './themes/pmahomme/img/screen.png',
            ],
            [
                'arrow_ltr.png',
                null,
                './themes/pmahomme/img/arrow_ltr.png',
            ],
            [
                'logo_right.png',
                'pma_logo.png',
                './themes/pmahomme/img/pma_logo.png',
            ],
        ];
    }
}
