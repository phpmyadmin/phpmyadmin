<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * tests for bookmark.lib.php
 *
 * @package PhpMyAdmin-test
 */
declare(strict_types=1);

namespace PhpMyAdmin\Tests;

use PHPUnit\Framework\TestCase;

/**
 * tests for bookmark.lib.php
 *
 * @package PhpMyAdmin-test
 */
class FilesTest extends TestCase
{
    /**
     * Test for dynamic javascript files
     *
     * @param string $name     Filename to test
     * @param string $expected Expected output
     *
     * @return void
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
        ];
        $GLOBALS['cfg'] = $cfg;
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
                'js/whitelist.php',
                'var GotoWhitelist',
            ],
            [
                'js/messages.php',
                'var Messages = [];',
            ],
        ];
    }
}
