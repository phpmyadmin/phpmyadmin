<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests;

use const MYSQLI_BLOB_FLAG;
use const MYSQLI_NUM_FLAG;
use const MYSQLI_TYPE_FLOAT;
use const MYSQLI_TYPE_INT24;
use const MYSQLI_TYPE_STRING;

/** @covers \PhpMyAdmin\FieldMetadata */
class FieldMetadataTest extends AbstractTestCase
{
    public function testEmptyConstruct(): void
    {
        $fm = FieldHelper::fromArray(['type' => -1]);
        $this->assertSame('', $fm->getMappedType());
        $this->assertFalse($fm->isBinary());
        $this->assertFalse($fm->isEnum());
        $this->assertFalse($fm->isUniqueKey());
        $this->assertFalse($fm->isUnsigned());
        $this->assertFalse($fm->isZerofill());
        $this->assertFalse($fm->isSet());
        $this->assertFalse($fm->isNotNull());
        $this->assertFalse($fm->isPrimaryKey());
        $this->assertFalse($fm->isMultipleKey());
        $this->assertFalse($fm->isBlob());
    }

    public function testIsBinary(): void
    {
        $fm = FieldHelper::fromArray(['type' => MYSQLI_TYPE_STRING, 'charsetnr' => 63]);
        $this->assertTrue($fm->isBinary());
        $this->assertFalse($fm->isEnum());
        $this->assertFalse($fm->isUniqueKey());
        $this->assertFalse($fm->isUnsigned());
        $this->assertFalse($fm->isZerofill());
        $this->assertFalse($fm->isSet());
        $this->assertFalse($fm->isNotNull());
        $this->assertFalse($fm->isPrimaryKey());
        $this->assertFalse($fm->isMultipleKey());
        $this->assertFalse($fm->isBlob());
    }

    public function testIsNumeric(): void
    {
        $fm = FieldHelper::fromArray(['type' => MYSQLI_TYPE_INT24, 'flags' => MYSQLI_NUM_FLAG]);
        $this->assertSame('int', $fm->getMappedType());
        $this->assertFalse($fm->isBinary());
        $this->assertFalse($fm->isEnum());
        $this->assertFalse($fm->isUniqueKey());
        $this->assertFalse($fm->isUnsigned());
        $this->assertFalse($fm->isZerofill());
        $this->assertFalse($fm->isSet());
        $this->assertFalse($fm->isNotNull());
        $this->assertFalse($fm->isPrimaryKey());
        $this->assertFalse($fm->isMultipleKey());
        $this->assertTrue($fm->isNumeric());
        $this->assertFalse($fm->isBlob());
    }

    public function testIsBlob(): void
    {
        $fm = FieldHelper::fromArray(['type' => -1, 'flags' => MYSQLI_BLOB_FLAG]);
        $this->assertSame('', $fm->getMappedType());
        $this->assertFalse($fm->isBinary());
        $this->assertFalse($fm->isEnum());
        $this->assertFalse($fm->isUniqueKey());
        $this->assertFalse($fm->isUnsigned());
        $this->assertFalse($fm->isZerofill());
        $this->assertFalse($fm->isSet());
        $this->assertFalse($fm->isNotNull());
        $this->assertFalse($fm->isPrimaryKey());
        $this->assertFalse($fm->isMultipleKey());
        $this->assertTrue($fm->isBlob());
    }

    public function testIsNumericFloat(): void
    {
        $fm = FieldHelper::fromArray(['type' => MYSQLI_TYPE_FLOAT, 'flags' => MYSQLI_NUM_FLAG]);
        $this->assertSame('real', $fm->getMappedType());
        $this->assertFalse($fm->isBinary());
        $this->assertFalse($fm->isEnum());
        $this->assertFalse($fm->isUniqueKey());
        $this->assertFalse($fm->isUnsigned());
        $this->assertFalse($fm->isZerofill());
        $this->assertFalse($fm->isSet());
        $this->assertFalse($fm->isNotNull());
        $this->assertFalse($fm->isPrimaryKey());
        $this->assertFalse($fm->isMultipleKey());
        $this->assertTrue($fm->isNumeric());
        $this->assertFalse($fm->isBlob());
    }
}
