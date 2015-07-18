<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * tests for *PropertyItem class
 *
 * @package PhpMyAdmin-test
 */

require_once 'libraries/properties/options/items/BoolPropertyItem.class.php';
require_once 'libraries/properties/options/items/DocPropertyItem.class.php';
require_once 'libraries/properties/options/items/HiddenPropertyItem.class.php';
require_once 'libraries/properties/options/items/MessageOnlyPropertyItem.class.php';
require_once 'libraries/properties/options/items/RadioPropertyItem.class.php';
require_once 'libraries/properties/options/items/SelectPropertyItem.class.php';
require_once 'libraries/properties/options/items/TextPropertyItem.class.php';
/**
 * tests for *PropertyItem class
 *
 * @package PhpMyAdmin-test
 */
class PMA_PropertyItems_Test extends PHPUnit_Framework_TestCase
{
    /**
     * Test for BoolPropertyItem::getItemType
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
     * Test for DocPropertyItem::getItemType
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
     * Test for HiddenPropertyItem::getItemType
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
     * Test for MessageOnlyPropertyItem::getItemType
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
     * Test for RadioPropertyItem::getItemType
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
     * Test for SelectPropertyItem::getItemType
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
     * Test for TextPropertyItem::getItemType
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
