<?php
/**
 * tests for *PhpMyAdmin\Properties\PropertyItem class
 */

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
 * tests for *PhpMyAdmin\Properties\PropertyItem class
 */
class PropertyItemsTest extends AbstractTestCase
{
    /**
     * Test for PhpMyAdmin\Properties\Options\Items\BoolPropertyItem::getText
     */
    public function testBoolText(): void
    {
        $object = new BoolPropertyItem(null, 'Text');

        $this->assertEquals(
            'Text',
            $object->getText()
        );

        $object->setText('xtext2');

        $this->assertEquals(
            'xtext2',
            $object->getText()
        );
    }

    /**
     * Test for PhpMyAdmin\Properties\Options\Items\BoolPropertyItem::getName
     */
    public function testBoolName(): void
    {
        $object = new BoolPropertyItem('xname');

        $this->assertEquals(
            'xname',
            $object->getName()
        );

        $object->setName('xname2');

        $this->assertEquals(
            'xname2',
            $object->getName()
        );
    }

    /**
     * Test for PhpMyAdmin\Properties\Options\Items\BoolPropertyItem::getItemType
     */
    public function testBoolGetItemType(): void
    {
        $object = new BoolPropertyItem();

        $this->assertEquals(
            'bool',
            $object->getItemType()
        );
    }

    /**
     * Test for PhpMyAdmin\Properties\Options\Items\DocPropertyItem::getItemType
     */
    public function testGetItemTypeDoc(): void
    {
        $object = new DocPropertyItem();

        $this->assertEquals(
            'doc',
            $object->getItemType()
        );
    }

    /**
     * Test for PhpMyAdmin\Properties\Options\Items\HiddenPropertyItem::getItemType
     */
    public function testGetItemTypeHidden(): void
    {
        $object = new HiddenPropertyItem();

        $this->assertEquals(
            'hidden',
            $object->getItemType()
        );
    }

    /**
     * Test for PhpMyAdmin\Properties\Options\Items\MessageOnlyPropertyItem::getItemType
     */
    public function testGetItemTypeMessageOnly(): void
    {
        $object = new MessageOnlyPropertyItem();

        $this->assertEquals(
            'messageOnly',
            $object->getItemType()
        );
    }

    /**
     * Test for PhpMyAdmin\Properties\Options\Items\RadioPropertyItem::getItemType
     */
    public function testGetItemTypeRadio(): void
    {
        $object = new RadioPropertyItem();

        $this->assertEquals(
            'radio',
            $object->getItemType()
        );
    }

    /**
     * Test for PhpMyAdmin\Properties\Options\Items\SelectPropertyItem::getItemType
     */
    public function testGetItemTypeSelect(): void
    {
        $object = new SelectPropertyItem();

        $this->assertEquals(
            'select',
            $object->getItemType()
        );
    }

    /**
     * Test for PhpMyAdmin\Properties\Options\Items\TextPropertyItem::getItemType
     */
    public function testGetItemTypeText(): void
    {
        $object = new TextPropertyItem();

        $this->assertEquals(
            'text',
            $object->getItemType()
        );
    }
}
