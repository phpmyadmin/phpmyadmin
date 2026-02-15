<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Properties\Options\Items;

use PhpMyAdmin\Properties\Options\Items\BoolPropertyItem;
use PhpMyAdmin\Tests\AbstractTestCase;
use PHPUnit\Framework\Attributes\CoversNothing;

#[CoversNothing]
class PropertyItemsTest extends AbstractTestCase
{
    public function testBoolText(): void
    {
        $object = new BoolPropertyItem(null, 'Text');

        self::assertSame(
            'Text',
            $object->getText(),
        );
    }

    public function testBoolName(): void
    {
        $object = new BoolPropertyItem('xname');

        self::assertSame(
            'xname',
            $object->getName(),
        );

        $object->setName('xname2');

        self::assertSame(
            'xname2',
            $object->getName(),
        );
    }
}
