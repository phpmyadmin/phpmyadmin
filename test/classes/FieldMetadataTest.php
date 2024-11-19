<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests;

use PhpMyAdmin\FieldMetadata;
use stdClass;

use const MYSQLI_BLOB_FLAG;
use const MYSQLI_NUM_FLAG;
use const MYSQLI_TYPE_FLOAT;
use const MYSQLI_TYPE_INT24;
use const MYSQLI_TYPE_STRING;

/**
 * @covers \PhpMyAdmin\FieldMetadata
 */
class FieldMetadataTest extends AbstractTestCase
{
    public function testEmptyConstruct(): void
    {
        $fm = new FieldMetadata(-1, 0, (object) []);
        self::assertSame('', $fm->getMappedType());
        self::assertFalse($fm->isBinary());
        self::assertFalse($fm->isEnum());
        self::assertFalse($fm->isUniqueKey());
        self::assertFalse($fm->isUnsigned());
        self::assertFalse($fm->isZerofill());
        self::assertFalse($fm->isSet());
        self::assertFalse($fm->isNotNull());
        self::assertFalse($fm->isPrimaryKey());
        self::assertFalse($fm->isMultipleKey());
        self::assertFalse($fm->isBlob());
    }

    public function testIsBinaryStdClassAsObject(): void
    {
        $obj = new stdClass();
        $obj->charsetnr = 63;
        $fm = new FieldMetadata(MYSQLI_TYPE_STRING, 0, $obj);
        self::assertTrue($fm->isBinary());
        self::assertFalse($fm->isEnum());
        self::assertFalse($fm->isUniqueKey());
        self::assertFalse($fm->isUnsigned());
        self::assertFalse($fm->isZerofill());
        self::assertFalse($fm->isSet());
        self::assertFalse($fm->isNotNull());
        self::assertFalse($fm->isPrimaryKey());
        self::assertFalse($fm->isMultipleKey());
        self::assertFalse($fm->isBlob());
    }

    public function testIsBinaryCustomClassAsObject(): void
    {
        $obj = new stdClass();
        $obj->charsetnr = 63;
        $objmd = new FieldMetadata(MYSQLI_TYPE_STRING, 0, $obj);
        $fm = new FieldMetadata(MYSQLI_TYPE_STRING, 0, $objmd);
        self::assertTrue($fm->isBinary());
        self::assertFalse($fm->isEnum());
        self::assertFalse($fm->isUniqueKey());
        self::assertFalse($fm->isUnsigned());
        self::assertFalse($fm->isZerofill());
        self::assertFalse($fm->isSet());
        self::assertFalse($fm->isNotNull());
        self::assertFalse($fm->isPrimaryKey());
        self::assertFalse($fm->isMultipleKey());
        self::assertFalse($fm->isBlob());
    }

    public function testIsBinary(): void
    {
        $fm = new FieldMetadata(MYSQLI_TYPE_STRING, 0, (object) ['charsetnr' => 63]);
        self::assertTrue($fm->isBinary());
        self::assertFalse($fm->isEnum());
        self::assertFalse($fm->isUniqueKey());
        self::assertFalse($fm->isUnsigned());
        self::assertFalse($fm->isZerofill());
        self::assertFalse($fm->isSet());
        self::assertFalse($fm->isNotNull());
        self::assertFalse($fm->isPrimaryKey());
        self::assertFalse($fm->isMultipleKey());
        self::assertFalse($fm->isBlob());
    }

    public function testIsNumeric(): void
    {
        $fm = new FieldMetadata(MYSQLI_TYPE_INT24, MYSQLI_NUM_FLAG, (object) []);
        self::assertSame('int', $fm->getMappedType());
        self::assertFalse($fm->isBinary());
        self::assertFalse($fm->isEnum());
        self::assertFalse($fm->isUniqueKey());
        self::assertFalse($fm->isUnsigned());
        self::assertFalse($fm->isZerofill());
        self::assertFalse($fm->isSet());
        self::assertFalse($fm->isNotNull());
        self::assertFalse($fm->isPrimaryKey());
        self::assertFalse($fm->isMultipleKey());
        self::assertTrue($fm->isNumeric());
        self::assertFalse($fm->isBlob());
    }

    public function testIsBlob(): void
    {
        $fm = new FieldMetadata(-1, MYSQLI_BLOB_FLAG, (object) []);
        self::assertSame('', $fm->getMappedType());
        self::assertFalse($fm->isBinary());
        self::assertFalse($fm->isEnum());
        self::assertFalse($fm->isUniqueKey());
        self::assertFalse($fm->isUnsigned());
        self::assertFalse($fm->isZerofill());
        self::assertFalse($fm->isSet());
        self::assertFalse($fm->isNotNull());
        self::assertFalse($fm->isPrimaryKey());
        self::assertFalse($fm->isMultipleKey());
        self::assertTrue($fm->isBlob());
    }

    public function testIsNumericFloat(): void
    {
        $fm = new FieldMetadata(MYSQLI_TYPE_FLOAT, MYSQLI_NUM_FLAG, (object) []);
        self::assertSame('real', $fm->getMappedType());
        self::assertFalse($fm->isBinary());
        self::assertFalse($fm->isEnum());
        self::assertFalse($fm->isUniqueKey());
        self::assertFalse($fm->isUnsigned());
        self::assertFalse($fm->isZerofill());
        self::assertFalse($fm->isSet());
        self::assertFalse($fm->isNotNull());
        self::assertFalse($fm->isPrimaryKey());
        self::assertFalse($fm->isMultipleKey());
        self::assertTrue($fm->isNumeric());
        self::assertFalse($fm->isBlob());
    }
}
