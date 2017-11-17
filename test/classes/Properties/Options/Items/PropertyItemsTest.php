<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * tests for *PhpMyAdmin\Properties\PropertyItem class
 *
 * @package PhpMyAdmin-test
 */
namespace PhpMyAdmin\Tests\Properties\Options\Items;

use PhpMyAdmin\Properties\Options\Items\BoolPropertyItem;
use PhpMyAdmin\Properties\Options\Items\DocPropertyItem;
use PhpMyAdmin\Properties\Options\Items\HiddenPropertyItem;
use PhpMyAdmin\Properties\Options\Items\MessageOnlyPropertyItem;
use PhpMyAdmin\Properties\Options\Items\RadioPropertyItem;
use PhpMyAdmin\Properties\Options\Items\SelectPropertyItem;
use PhpMyAdmin\Properties\Options\Items\TextPropertyItem;
use PHPUnit\Framework\TestCase;

/**
 * tests for *PhpMyAdmin\Properties\PropertyItem class
 *
 * @package PhpMyAdmin-test
 */
class PropertyItemsTest extends TestCase
{
    /**
     * Test for PhpMyAdmin\Properties\Options\Items\BoolPropertyItem::getText
     *
     * @return void
     */
    public function testBoolText()
    {
        $object = new BoolPropertyItem(null, 'Text');

        $this->assertEquals(
            "Text",
            $object->getText()
        );

        $object->setText('xtext2');

        $this->assertEquals(
            "xtext2",
            $object->getText()
        );
    }

    /**
     * Test for PhpMyAdmin\Properties\Options\Items\BoolPropertyItem::getName
     *
     * @return void
     */
    public function testBoolName()
    {
        $object = new BoolPropertyItem('xname');

        $this->assertEquals(
            "xname",
            $object->getName()
        );

        $object->setName('xname2');

        $this->assertEquals(
            "xname2",
            $object->getName()
        );
    }

    /**
     * Test for PhpMyAdmin\Properties\Options\Items\BoolPropertyItem::getItemType
     *
     * @return void
     */
    public function testBoolGetItemType()
    {
        $object = new BoolPropertyItem();

        $this->assertEquals(
            "bool",
            $object->getItemType()
        );
    }

    /**
     * Test for PhpMyAdmin\Properties\Options\Items\DocPropertyItem::getItemType
     *
     * @return void
     */
    public function testGetItemTypeDoc()
    {
        $object = new DocPropertyItem();

        $this->assertEquals(
            "doc",
            $object->getItemType()
        );
    }

    /**
     * Test for PhpMyAdmin\Properties\Options\Items\HiddenPropertyItem::getItemType
     *
     * @return void
     */
    public function testGetItemTypeHidden()
    {
        $object = new HiddenPropertyItem();

        $this->assertEquals(
            "hidden",
            $object->getItemType()
        );
    }

    /**
     * Test for PhpMyAdmin\Properties\Options\Items\MessageOnlyPropertyItem::getItemType
     *
     * @return void
     */
    public function testGetItemTypeMessageOnly()
    {
        $object = new MessageOnlyPropertyItem();

        $this->assertEquals(
            "messageOnly",
            $object->getItemType()
        );
    }

    /**
     * Test for PhpMyAdmin\Properties\Options\Items\RadioPropertyItem::getItemType
     *
     * @return void
     */
    public function testGetItemTypeRadio()
    {
        $object = new RadioPropertyItem();

        $this->assertEquals(
            "radio",
            $object->getItemType()
        );
    }

    /**
     * Test for PhpMyAdmin\Properties\Options\Items\SelectPropertyItem::getItemType
     *
     * @return void
     */
    public function testGetItemTypeSelect()
    {
        $object = new SelectPropertyItem();

        $this->assertEquals(
            "select",
            $object->getItemType()
        );
    }

    /**
     * Test for PhpMyAdmin\Properties\Options\Items\TextPropertyItem::getItemType
     *
     * @return void
     */
    public function testGetItemTypeText()
    {
        $object = new TextPropertyItem();

        $this->assertEquals(
            "text",
            $object->getItemType()
        );
    }
}
