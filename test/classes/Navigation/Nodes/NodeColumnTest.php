<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Navigation\Nodes;

use PhpMyAdmin\Navigation\Nodes\Node;
use PhpMyAdmin\Navigation\Nodes\NodeColumn;
use PhpMyAdmin\Tests\AbstractTestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(NodeColumn::class)]
final class NodeColumnTest extends AbstractTestCase
{
    public function testColumnNode(): void
    {
        $nodeColumn = new NodeColumn([
            'name' => 'actor_id',
            'key' => 'PRI',
            'type' => 'smallint',
            'default' => null,
            'nullable' => '',
        ]);
        $this->assertSame('actor_id', $nodeColumn->name);
        $this->assertSame(Node::OBJECT, $nodeColumn->type);
        $this->assertFalse($nodeColumn->isGroup);
        $this->assertSame('actor_id (PRI, smallint)', $nodeColumn->displayName);
        $this->assertSame(['image' => 'b_primary', 'title' => 'Column'], $nodeColumn->icon);
        $this->assertSame(
            [
                'text' => [
                    'route' => '/table/structure/change',
                    'params' => ['change_column' => 1, 'db' => null, 'table' => null, 'field' => null],
                ],
                'icon' => [
                    'route' => '/table/structure/change',
                    'params' => ['change_column' => 1, 'db' => null, 'table' => null, 'field' => null],
                ],
                'title' => 'Structure',
            ],
            $nodeColumn->links,
        );
        $this->assertSame('field', $nodeColumn->urlParamName);
    }

    public function testColumnNodeWithTruncatedDefaultValue(): void
    {
        $nodeColumn = new NodeColumn([
            'name' => 'last_update',
            'key' => '',
            'type' => 'timestamp',
            'default' => 'current_timestamp()',
            'nullable' => '',
        ]);
        $this->assertSame('last_update (timestamp, curren...)', $nodeColumn->displayName);
        $this->assertSame(['image' => 'pause', 'title' => 'Column'], $nodeColumn->icon);
    }

    public function testColumnNodeWithTruncatedDefaultValue2(): void
    {
        $nodeColumn = new NodeColumn([
            'name' => 'email',
            'key' => 'UNI',
            'type' => 'varchar',
            'default' => 'default',
            'nullable' => 'nullable',
        ]);
        $this->assertSame('email (UNI, varchar, defaul..., nullable)', $nodeColumn->displayName);
        $this->assertSame(['image' => 'bd_primary', 'title' => 'Column'], $nodeColumn->icon);
    }

    public function testColumnNodeWithoutTruncatedDefaultValue(): void
    {
        $nodeColumn = new NodeColumn([
            'name' => 'email',
            'key' => '',
            'type' => 'varchar',
            'default' => 'column',
            'nullable' => '',
        ]);
        $this->assertSame('email (varchar, column)', $nodeColumn->displayName);
        $this->assertSame(['image' => 'pause', 'title' => 'Column'], $nodeColumn->icon);
    }
}
