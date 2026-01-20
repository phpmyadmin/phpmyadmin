<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests;

use ArrayIterator;
use PhpMyAdmin\Config;
use PhpMyAdmin\Current;
use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\Footer;
use PhpMyAdmin\Template;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Medium;

use function json_encode;

#[CoversClass(Footer::class)]
#[Medium]
class FooterTest extends AbstractTestCase
{
    /** @var mixed[] store private attributes of PhpMyAdmin\Footer */
    public array $privates = [];

    protected Footer $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->setLanguage();

        DatabaseInterface::$instance = $this->createDatabaseInterface();
        $_SERVER['SCRIPT_NAME'] = 'index.php';
        Current::$database = '';
        Current::$table = '';
        $config = Config::getInstance();
        $config->selectedServer['DisableIS'] = false;
        $config->selectedServer['verbose'] = 'verbose host';
        $_GET['reload_left_frame'] = '1';
        $this->object = new Footer(new Template(), $config);
        Current::$sqlQuery = '';
        $_POST = [];
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown(): void
    {
        parent::tearDown();

        unset($this->object);
    }

    /**
     * Test for getDebugMessage
     */
    public function testGetDebugMessage(): void
    {
        $config = Config::getInstance();
        $config->config->debug->sql = true;
        $_SESSION['debug']['queries'] = [
            ['count' => 1, 'time' => 0.2, 'query' => 'SELECT * FROM `pma_bookmark` WHERE 1'],
            ['count' => 1, 'time' => 2.5, 'query' => 'SELECT * FROM `db` WHERE 1'],
        ];

        self::assertSame(
            '{"queries":[{"count":1,"time":0.2,"query":"SELECT * FROM `pma_bookmark` WHERE 1"},'
            . '{"count":1,"time":2.5,"query":"SELECT * FROM `db` WHERE 1"}]}',
            $this->object->getDebugMessage(),
        );
        $config->config->debug->sql = false;
    }

    /**
     * Test for removeRecursion
     */
    public function testRemoveRecursion(): void
    {
        $object = (object) [];
        $object->child = (object) [];
        $object->childIterator = new ArrayIterator();
        $object->child->parent = $object;

        $this->callFunction($this->object, Footer::class, 'removeRecursion', [&$object]);
        self::assertSame(
            '{"child":{"parent":"***RECURSION***"},"childIterator":"***ITERATOR***"}',
            json_encode($object),
        );
    }

    /**
     * Test for footer get Scripts
     */
    public function testGetScripts(): void
    {
        $footer = new Footer(new Template(), Config::getInstance());
        self::assertStringContainsString(
            '<script data-cfasync="false">',
            $footer->getScripts()->getDisplay(),
        );
    }

    /**
     * Test for displaying footer
     */
    public function testDisplay(): void
    {
        $footer = new Footer(new Template(), Config::getInstance());
        $scripts = <<<'HTML'

            <script data-cfasync="false">
            // <![CDATA[
            window.Console.debugSqlInfo = 'false';

            // ]]>
            </script>

            HTML;

        $expected = [
            'is_minimal' => false,
            'self_url' => 'index.php?route=%2F&server=1&lang=en',
            'error_messages' => '',
            'scripts' => $scripts,
            'is_demo' => false,
            'git_revision_info' => [],
            'footer' => '',
        ];
        self::assertSame($expected, $footer->getDisplay());
    }

    /**
     * Test for minimal footer
     */
    public function testMinimal(): void
    {
        $template = new Template();
        $footer = new Footer($template, Config::getInstance());
        $footer->setMinimal();
        $expected = [
            'is_minimal' => true,
            'self_url' => null,
            'error_messages' => '',
            'scripts' => '',
            'is_demo' => false,
            'git_revision_info' => [],
            'footer' => '',
        ];
        self::assertSame($expected, $footer->getDisplay());
    }
}
