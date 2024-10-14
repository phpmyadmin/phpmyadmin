<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Properties\Options\Items;

use PhpMyAdmin\Properties\Options\Items\BoolPropertyItem;
use PhpMyAdmin\Properties\Options\Items\DocPropertyItem;
use PhpMyAdmin\Properties\Options\Items\HiddenPropertyItem;
use PhpMyAdmin\Properties\Options\Items\MessageOnlyPropertyItem;
use PhpMyAdmin\Properties\Options\Items\RadioPropertyItem;
use PhpMyAdmin\Properties\Options\Items\SelectPropertyItem;
use PhpMyAdmin\Properties\Options\Items\TextPropertyItem;
use PhpMyAdmin\Tests\AbstractTestCase;

/**
 * @coversNothing
 */
class PropertyItemsTest extends AbstractTestCase
{
    public function testBoolText(): void
    {
        $object = new BoolPropertyItem(null, 'Text');

        self::assertSame('Text', $object->getText());

        $object->setText('xtext2');

        self::assertSame('xtext2', $object->getText());
    }

    public function testBoolName(): void
    {
        $object = new BoolPropertyItem('xname');

        self::assertSame('xname', $object->getName());

        $object->setName('xname2');

        self::assertSame('xname2', $object->getName());
    }

    public function testBoolGetItemType(): void
    {
        $object = new BoolPropertyItem();

        self::assertSame('bool', $object->getItemType());
    }

    public function testGetItemTypeDoc(): void
    {
        $object = new DocPropertyItem();

        self::assertSame('doc', $object->getItemType());
    }

    public function testGetItemTypeHidden(): void
    {
        $object = new HiddenPropertyItem();

        self::assertSame('hidden', $object->getItemType());
    }

    public function testGetItemTypeMessageOnly(): void
    {
        $object = new MessageOnlyPropertyItem();

        self::assertSame('messageOnly', $object->getItemType());
    }

    public function testGetItemTypeRadio(): void
    {
        $object = new RadioPropertyItem();

        self::assertSame('radio', $object->getItemType());
    }

    public function testGetItemTypeSelect(): void
    {
        $object = new SelectPropertyItem();

        self::assertSame('select', $object->getItemType());
    }

    public function testGetItemTypeText(): void
    {
        $object = new TextPropertyItem();

        self::assertSame('text', $object->getItemType());
    }
}
