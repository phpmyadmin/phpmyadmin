<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests;

use PhpMyAdmin\Theme;
use function filemtime;

class ThemeTest extends AbstractTestCase
{
    /** @var Theme */
    protected $object;

    /** @var Theme backup for session theme */
    protected $backup;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp(): void
    {
        parent::setUp();
        parent::defineVersionConstants();
        parent::setTheme();
        $this->object = new Theme();
        $this->backup = $GLOBALS['PMA_Theme'];
        $GLOBALS['PMA_Theme'] = $this->object;
        parent::setGlobalConfig();
        $GLOBALS['PMA_Config']->enableBc();
        $GLOBALS['text_dir'] = 'ltr';
        $GLOBALS['server'] = '99';
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown(): void
    {
        parent::tearDown();
        $GLOBALS['PMA_Theme'] = $this->backup;
    }

    /**
     * Test for Theme::loadInfo
     *
     * @group medium
     */
    public function testCheckImgPathNotExisted(): void
    {
        $this->object->setPath('path/to/nowhere');
        $this->assertFalse($this->object->loadInfo());
    }

    /**
     * Test for Theme::loadInfo
     */
    public function testCheckImgPathIncorrect(): void
    {
        $this->object->setPath(ROOT_PATH . 'test/classes/_data/incorrect_theme');
        $this->assertFalse(
            $this->object->loadInfo(),
            'Theme name is not properly set'
        );
    }

    /**
     * Test for Theme::getName, getVersion
     */
    public function testCheckImgPathFull(): void
    {
        $this->object->setFsPath(ROOT_PATH . 'test/classes/_data/gen_version_info/');
        $this->assertTrue($this->object->loadInfo());
        $this->assertEquals('Test Theme', $this->object->getName());
        $this->assertEquals('5.1', $this->object->getVersion());
    }

    /**
     * Test for Theme::loadInfo
     */
    public function testLoadInfo(): void
    {
        $this->object->setFsPath(ROOT_PATH . 'themes/original/');
        $infofile = $this->object->getFsPath() . 'theme.json';
        $this->assertTrue($this->object->loadInfo());

        $this->assertEquals(
            filemtime($infofile),
            $this->object->mtimeInfo
        );

        $this->object->setPath(ROOT_PATH . 'themes/original');
        $this->object->mtimeInfo = filemtime($infofile);
        $this->assertTrue($this->object->loadInfo());
        $this->assertEquals('Original', $this->object->getName());
    }

    /**
     * Test for Theme::load
     */
    public function testLoad(): void
    {
        $newTheme = Theme::load('./themes/original', ROOT_PATH . 'themes/original');
        $this->assertNotNull($newTheme);
    }

    /**
     * Test for Theme::load
     */
    public function testLoadNotExisted(): void
    {
        $this->assertFalse(Theme::load('/path/to/nowhere', '/path/to/nowhere'));
    }

    /**
     * Test fir Theme::checkImgPath
     */
    public function testCheckImgPathFallback(): void
    {
        $this->object->setPath('path/to/nowhere');
        $this->assertTrue($this->object->checkImgPath());
    }

    /**
     * Test for Theme::checkImgPath
     */
    public function testCheckImgPath(): void
    {
        $this->object->setPath(ROOT_PATH . 'themes/original');
        $this->assertTrue($this->object->checkImgPath());
    }

    /**
     * Test for Theme::getPath
     */
    public function testGetSetPath(): void
    {
        $this->assertEmpty($this->object->getPath());
        $this->object->setPath(ROOT_PATH . 'themes/original');

        $this->assertEquals(ROOT_PATH . 'themes/original', $this->object->getPath());
    }

    /**
     * Test for Theme::checkVersion
     *
     * @depends testLoadInfo
     */
    public function testGetSetCheckVersion(): void
    {
        $this->assertEquals(
            '0.0.0.0',
            $this->object->getVersion(),
            'Version 0.0.0.0 by default'
        );

        $this->object->setVersion('1.2.3.4');
        $this->assertEquals('1.2.3.4', $this->object->getVersion());

        $this->assertFalse($this->object->checkVersion('0.0.1.1'));
        $this->assertTrue($this->object->checkVersion('2.0.1.1'));
    }

    /**
     * Test for Theme::getName
     */
    public function testGetSetName(): void
    {
        $this->assertEmpty($this->object->getName(), 'Name is empty by default');
        $this->object->setName('New Theme Name');

        $this->assertEquals('New Theme Name', $this->object->getName());
    }

    /**
     * Test for Theme::getId
     */
    public function testGetSetId(): void
    {
        $this->assertEmpty($this->object->getId(), 'ID is empty by default');
        $this->object->setId('NewID');

        $this->assertEquals('NewID', $this->object->getId());
    }

    /**
     * Test for Theme::getImgPath
     */
    public function testGetSetImgPath(): void
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
     */
    public function testGetPrintPreview(): void
    {
        parent::setLanguage();
        $this->assertStringContainsString(
            '<h2>' . "\n" . '         (0.0.0.0)',
            $this->object->getPrintPreview()
        );
        $this->assertStringContainsString(
            'name="" href="index.php?route=/set-theme&set_theme=&server=99&lang=en">',
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
     * @param string|null $file     file name for image
     * @param string|null $fallback fallback image
     * @param string      $output   expected output
     *
     * @dataProvider providerForGetImgPath
     */
    public function testGetImgPath(?string $file, ?string $fallback, string $output): void
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
    public function providerForGetImgPath(): array
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
