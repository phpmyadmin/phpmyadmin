<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * tests for *PMA\libraries\properties\PropertyItem class
 *
 * @package PhpMyAdmin-test
 */

use PMA\libraries\properties\options\items\BoolPropertyItem;
use PMA\libraries\properties\options\items\DocPropertyItem;
use PMA\libraries\properties\options\items\HiddenPropertyItem;
use PMA\libraries\properties\options\items\MessageOnlyPropertyItem;
use PMA\libraries\properties\options\items\RadioPropertyItem;
use PMA\libraries\properties\options\items\SelectPropertyItem;
use PMA\libraries\properties\options\items\TextPropertyItem;

/**
 * tests for *PMA\libraries\properties\PropertyItem class
 *
 * @package PhpMyAdmin-test
 */
class PropertyItemsTest extends PHPUnit_Framework_TestCase
{
    /**
     * Test for PMA\libraries\properties\options\items\BoolPropertyItem::getText
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
     * Test for PMA\libraries\properties\options\items\BoolPropertyItem::getName
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
     * Test for PMA\libraries\properties\options\items\BoolPropertyItem::getItemType
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
     * Test for PMA\libraries\properties\options\items\DocPropertyItem::getItemType
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
     * Test for PMA\libraries\properties\options\items\HiddenPropertyItem::getItemType
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
     * Test for PMA\libraries\properties\options\items\MessageOnlyPropertyItem::getItemType
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
     * Test for PMA\libraries\properties\options\items\RadioPropertyItem::getItemType
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
     * Test for PMA\libraries\properties\options\items\SelectPropertyItem::getItemType
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
     * Test for PMA\libraries\properties\options\items\TextPropertyItem::getItemType
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
