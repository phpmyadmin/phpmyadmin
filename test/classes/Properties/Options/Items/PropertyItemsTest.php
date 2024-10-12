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

        self::assertEquals('Text', $object->getText());

        $object->setText('xtext2');

        self::assertEquals('xtext2', $object->getText());
    }

    public function testBoolName(): void
    {
        $object = new BoolPropertyItem('xname');

        self::assertEquals('xname', $object->getName());

        $object->setName('xname2');

        self::assertEquals('xname2', $object->getName());
    }

    public function testBoolGetItemType(): void
    {
        $object = new BoolPropertyItem();

        self::assertEquals('bool', $object->getItemType());
    }

    public function testGetItemTypeDoc(): void
    {
        $object = new DocPropertyItem();

        self::assertEquals('doc', $object->getItemType());
    }

    public function testGetItemTypeHidden(): void
    {
        $object = new HiddenPropertyItem();

        self::assertEquals('hidden', $object->getItemType());
    }

    public function testGetItemTypeMessageOnly(): void
    {
        $object = new MessageOnlyPropertyItem();

        self::assertEquals('messageOnly', $object->getItemType());
    }

    public function testGetItemTypeRadio(): void
    {
        $object = new RadioPropertyItem();

        self::assertEquals('radio', $object->getItemType());
    }

    public function testGetItemTypeSelect(): void
    {
        $object = new SelectPropertyItem();

        self::assertEquals('select', $object->getItemType());
    }

    public function testGetItemTypeText(): void
    {
        $object = new TextPropertyItem();

        self::assertEquals('text', $object->getItemType());
    }
}
