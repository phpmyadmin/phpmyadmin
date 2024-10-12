<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests;

use PhpMyAdmin\Theme;
use PhpMyAdmin\ThemeManager;

use function filemtime;

use const DIRECTORY_SEPARATOR;
use const TEST_PATH;

/**
 * @covers \PhpMyAdmin\Theme
 */
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
        global $theme;

        parent::setUp();
        parent::setTheme();
        $this->object = new Theme();
        $this->backup = $theme;
        $theme = $this->object;
        parent::setGlobalConfig();
        $GLOBALS['text_dir'] = 'ltr';
        $GLOBALS['server'] = '99';
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown(): void
    {
        global $theme;

        parent::tearDown();
        $theme = $this->backup;
    }

    /**
     * Test for Theme::loadInfo
     *
     * @group medium
     */
    public function testCheckImgPathNotExisted(): void
    {
        $this->object->setPath('path/to/nowhere');
        self::assertFalse($this->object->loadInfo());
    }

    /**
     * Test for Theme::loadInfo
     */
    public function testCheckImgPathIncorrect(): void
    {
        $this->object->setPath(TEST_PATH . 'test/classes/_data/incorrect_theme');
        self::assertFalse($this->object->loadInfo(), 'Theme name is not properly set');
    }

    /**
     * Test for Theme::getName, getVersion
     */
    public function testCheckImgPathFull(): void
    {
        $this->object->setFsPath(TEST_PATH . 'test/classes/_data/gen_version_info/');
        self::assertTrue($this->object->loadInfo());
        self::assertSame('Test Theme', $this->object->getName());
        self::assertSame('5.1', $this->object->getVersion());
    }

    /**
     * Test for Theme::loadInfo
     */
    public function testLoadInfo(): void
    {
        $this->object->setFsPath(ROOT_PATH . 'themes/original/');
        $infofile = $this->object->getFsPath() . 'theme.json';
        self::assertTrue($this->object->loadInfo());

        self::assertSame(filemtime($infofile), $this->object->mtimeInfo);

        $this->object->setPath(ROOT_PATH . 'themes/original');
        $this->object->mtimeInfo = (int) filemtime($infofile);
        self::assertTrue($this->object->loadInfo());
        self::assertSame('Original', $this->object->getName());
    }

    /**
     * Test for Theme::load
     */
    public function testLoad(): void
    {
        $newTheme = Theme::load(
            ThemeManager::getThemesDir() . 'original',
            ThemeManager::getThemesFsDir() . 'original' . DIRECTORY_SEPARATOR,
            'original'
        );
        self::assertNotNull($newTheme);
        self::assertInstanceOf(Theme::class, $newTheme);
    }

    /**
     * Test for Theme::load
     */
    public function testLoadNonExistent(): void
    {
        self::assertNull(Theme::load(
            ThemeManager::getThemesDir() . 'nonexistent',
            ThemeManager::getThemesFsDir() . 'nonexistent' . DIRECTORY_SEPARATOR,
            'nonexistent'
        ));
    }

    /**
     * Test fir Theme::checkImgPath
     */
    public function testCheckImgPathFallback(): void
    {
        $this->object->setPath('path/to/nowhere');
        self::assertTrue($this->object->checkImgPath());
    }

    /**
     * Test for Theme::checkImgPath
     */
    public function testCheckImgPath(): void
    {
        $this->object->setPath(ROOT_PATH . 'themes/original');
        self::assertTrue($this->object->checkImgPath());
    }

    /**
     * Test for Theme::getPath
     */
    public function testGetSetPath(): void
    {
        self::assertEmpty($this->object->getPath());
        $this->object->setPath(ROOT_PATH . 'themes/original');

        self::assertSame(ROOT_PATH . 'themes/original', $this->object->getPath());
    }

    /**
     * Test for Theme::checkVersion
     *
     * @depends testLoadInfo
     */
    public function testGetSetCheckVersion(): void
    {
        self::assertSame('0.0.0.0', $this->object->getVersion(), 'Version 0.0.0.0 by default');

        $this->object->setVersion('1.2.3.4');
        self::assertSame('1.2.3.4', $this->object->getVersion());

        self::assertFalse($this->object->checkVersion('0.0.1.1'));
        self::assertTrue($this->object->checkVersion('2.0.1.1'));
    }

    /**
     * Test for Theme::getName
     */
    public function testGetSetName(): void
    {
        self::assertEmpty($this->object->getName(), 'Name is empty by default');
        $this->object->setName('New Theme Name');

        self::assertSame('New Theme Name', $this->object->getName());
    }

    /**
     * Test for Theme::getId
     */
    public function testGetSetId(): void
    {
        self::assertEmpty($this->object->getId(), 'ID is empty by default');
        $this->object->setId('NewID');

        self::assertSame('NewID', $this->object->getId());
    }

    /**
     * Test for Theme::getImgPath
     */
    public function testGetSetImgPath(): void
    {
        self::assertEmpty($this->object->getImgPath(), 'ImgPath is empty by default');
        $this->object->setImgPath('/new/path');

        self::assertSame('/new/path', $this->object->getImgPath());
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
        self::assertSame($this->object->getImgPath($file, $fallback), $output);
    }

    /**
     * Provider for testGetImgPath
     *
     * @return array
     */
    public static function providerForGetImgPath(): array
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
