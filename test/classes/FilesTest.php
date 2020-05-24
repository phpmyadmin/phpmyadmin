<?php
/**
 * tests for PhpMyAdmin\Bookmark
 */
declare(strict_types=1);

namespace PhpMyAdmin\Tests;

use PhpMyAdmin\OutputBuffering;

/**
 * tests for PhpMyAdmin\Bookmark
 */
class FilesTest extends AbstractTestCase
{
    /**
     * Setup function for test cases
     */
    protected function setUp(): void
    {
        parent::setUp();
        parent::defineVersionConstants();
        parent::setTheme();
    }

    /**
     * Test for dynamic javascript files
     *
     * @param string $name     Filename to test
     * @param string $expected Expected output
     *
     * @dataProvider listScripts
     */
    public function testDynamicJs($name, $expected): void
    {
        $GLOBALS['pmaThemeImage'] = '';
        $_GET['scripts'] = '["ajax.js"]';
        $cfg = [
            'AllowUserDropDatabase' => true,
            'GridEditing' => 'click',
            'OBGzip' => false,
            'ServerDefault' => 1,
            'FirstDayOfCalendar' => 0,
        ];
        $GLOBALS['cfg'] = $cfg;
        /** @var OutputBuffering $buffer */
        $buffer = null;
        require ROOT_PATH . $name;
        $buffer->stop();
        $out = $buffer->getContents();
        $this->assertStringContainsString($expected, $out);
    }

    /**
     * Data provider for scripts testing
     *
     * @return array
     */
    public function listScripts()
    {
        return [
            [
                'js/messages.php',
                'var Messages = [];',
            ],
        ];
    }
}
