<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Navigation;

use PhpMyAdmin\Navigation\NodeFactory;
use PhpMyAdmin\Navigation\Nodes\Node;
use PhpMyAdmin\Tests\AbstractTestCase;
use PHPUnit\Framework\Attributes\CoversClass;

use function restore_error_handler;
use function set_error_handler;

use const E_USER_ERROR;
use const E_USER_WARNING;
use const PHP_VERSION_ID;

/**
 * @covers \PhpMyAdmin\Navigation\NodeFactory
 */
#[CoversClass(NodeFactory::class)]
class NodeFactoryTest extends AbstractTestCase
{
    /**
     * SetUp for test cases
     */
    protected function setUp(): void
    {
        parent::setUp();
        $GLOBALS['server'] = 0;
    }

    public function testDefaultNode(): void
    {
        $node = NodeFactory::getInstance();
        self::assertSame('default', $node->name);
        self::assertSame(Node::OBJECT, $node->type);
        self::assertFalse($node->isGroup);
    }

    public function testDefaultContainer(): void
    {
        $node = NodeFactory::getInstance('Node', 'default', Node::CONTAINER);
        self::assertSame('default', $node->name);
        self::assertSame(Node::CONTAINER, $node->type);
        self::assertFalse($node->isGroup);
    }

    public function testGroupContainer(): void
    {
        $node = NodeFactory::getInstance('Node', 'default', Node::CONTAINER, true);
        self::assertSame('default', $node->name);
        self::assertSame(Node::CONTAINER, $node->type);
        self::assertTrue($node->isGroup);
    }

    public function testFileError(): void
    {
        $message = '';
        set_error_handler(static function (int $errno, string $errstr) use (&$message): bool {
            $message = $errstr;

            return true;
        }, PHP_VERSION_ID < 80400 ? E_USER_ERROR : E_USER_WARNING);

        NodeFactory::getInstance('NodeDoesNotExist');
        restore_error_handler();

        self::assertSame('Could not load class "PhpMyAdmin\Navigation\Nodes\Node"', $message);
    }

    public function testClassNameError(): void
    {
        $message = '';
        set_error_handler(static function (int $errno, string $errstr) use (&$message): bool {
            $message = $errstr;

            return true;
        }, PHP_VERSION_ID < 80400 ? E_USER_ERROR : E_USER_WARNING);

        NodeFactory::getInstance('Invalid');
        restore_error_handler();

        self::assertSame('Invalid class name "Node", using default of "Node"', $message);
    }
}
