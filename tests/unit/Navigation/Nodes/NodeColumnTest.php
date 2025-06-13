<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Navigation\Nodes;

use PhpMyAdmin\Config;
use PhpMyAdmin\Navigation\Nodes\NodeColumn;
use PhpMyAdmin\Navigation\NodeType;
use PhpMyAdmin\Tests\AbstractTestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(NodeColumn::class)]
final class NodeColumnTest extends AbstractTestCase
{
    public function testColumnNode(): void
    {
        $nodeColumn = new NodeColumn(new Config(), [
            'name' => 'actor_id',
            'key' => 'PRI',
            'type' => 'smallint',
            'default' => null,
            'nullable' => '',
        ]);
        self::assertSame('actor_id', $nodeColumn->name);
        self::assertSame(NodeType::Object, $nodeColumn->type);
        self::assertFalse($nodeColumn->isGroup);
        self::assertSame('actor_id (PRI, smallint)', $nodeColumn->displayName);
        self::assertSame('b_primary', $nodeColumn->icon->image);
        self::assertSame('Column', $nodeColumn->icon->title);
        self::assertSame('/table/structure/change', $nodeColumn->icon->route);
        self::assertSame(
            ['change_column' => 1, 'db' => null, 'table' => null, 'field' => null],
            $nodeColumn->icon->params,
        );
        self::assertSame('/table/structure/change', $nodeColumn->link->route);
        self::assertSame(
            ['change_column' => 1, 'db' => null, 'table' => null, 'field' => null],
            $nodeColumn->link->params,
        );
        self::assertSame('Structure', $nodeColumn->link->title);
        self::assertSame('field', $nodeColumn->urlParamName);
    }

    public function testColumnNodeWithTruncatedDefaultValue(): void
    {
        $nodeColumn = new NodeColumn(new Config(), [
            'name' => 'last_update',
            'key' => '',
            'type' => 'timestamp',
            'default' => 'current_timestamp()',
            'nullable' => '',
        ]);
        self::assertSame('last_update (timestamp, curren...)', $nodeColumn->displayName);
        self::assertSame('pause', $nodeColumn->icon->image);
        self::assertSame('Column', $nodeColumn->icon->title);
    }

    public function testColumnNodeWithTruncatedDefaultValue2(): void
    {
        $nodeColumn = new NodeColumn(new Config(), [
            'name' => 'email',
            'key' => 'UNI',
            'type' => 'varchar',
            'default' => 'default',
            'nullable' => 'nullable',
        ]);
        self::assertSame('email (UNI, varchar, defaul..., nullable)', $nodeColumn->displayName);
        self::assertSame('bd_primary', $nodeColumn->icon->image);
        self::assertSame('Column', $nodeColumn->icon->title);
    }

    public function testColumnNodeWithoutTruncatedDefaultValue(): void
    {
        $nodeColumn = new NodeColumn(new Config(), [
            'name' => 'email',
            'key' => '',
            'type' => 'varchar',
            'default' => 'column',
            'nullable' => '',
        ]);
        self::assertSame('email (varchar, column)', $nodeColumn->displayName);
        self::assertSame('pause', $nodeColumn->icon->image);
        self::assertSame('Column', $nodeColumn->icon->title);
    }
}
