<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests;

use PhpMyAdmin\FieldMetadata;
use stdClass;

use const MYSQLI_BLOB_FLAG;
use const MYSQLI_NUM_FLAG;
use const MYSQLI_TYPE_FLOAT;
use const MYSQLI_TYPE_STRING;

class FieldMetadataTest extends AbstractTestCase
{
    public function testEmptyConstruct(): void
    {
        $fm = new FieldMetadata(-1, 0, (object) []);
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
        $this->assertFalse($fm->isNumeric());
        $this->assertFalse($fm->isBlob());
    }

    public function testIsBinaryStdClassAsObject(): void
    {
        $obj = new stdClass();
        $obj->charsetnr = 63;
        $fm = new FieldMetadata(MYSQLI_TYPE_STRING, 0, $obj);
        $this->assertTrue($fm->isBinary());
        $this->assertFalse($fm->isEnum());
        $this->assertFalse($fm->isUniqueKey());
        $this->assertFalse($fm->isUnsigned());
        $this->assertFalse($fm->isZerofill());
        $this->assertFalse($fm->isSet());
        $this->assertFalse($fm->isNotNull());
        $this->assertFalse($fm->isPrimaryKey());
        $this->assertFalse($fm->isMultipleKey());
        $this->assertFalse($fm->isNumeric());
        $this->assertFalse($fm->isBlob());
    }

    public function testIsBinaryCustomClassAsObject(): void
    {
        $obj = new stdClass();
        $obj->charsetnr = 63;
        $objmd = new FieldMetadata(MYSQLI_TYPE_STRING, 0, $obj);
        $fm = new FieldMetadata(MYSQLI_TYPE_STRING, 0, $objmd);
        $this->assertTrue($fm->isBinary());
        $this->assertFalse($fm->isEnum());
        $this->assertFalse($fm->isUniqueKey());
        $this->assertFalse($fm->isUnsigned());
        $this->assertFalse($fm->isZerofill());
        $this->assertFalse($fm->isSet());
        $this->assertFalse($fm->isNotNull());
        $this->assertFalse($fm->isPrimaryKey());
        $this->assertFalse($fm->isMultipleKey());
        $this->assertFalse($fm->isNumeric());
        $this->assertFalse($fm->isBlob());
    }

    public function testIsBinary(): void
    {
        $fm = new FieldMetadata(MYSQLI_TYPE_STRING, 0, (object) ['charsetnr' => 63]);
        $this->assertTrue($fm->isBinary());
        $this->assertFalse($fm->isEnum());
        $this->assertFalse($fm->isUniqueKey());
        $this->assertFalse($fm->isUnsigned());
        $this->assertFalse($fm->isZerofill());
        $this->assertFalse($fm->isSet());
        $this->assertFalse($fm->isNotNull());
        $this->assertFalse($fm->isPrimaryKey());
        $this->assertFalse($fm->isMultipleKey());
        $this->assertFalse($fm->isNumeric());
        $this->assertFalse($fm->isBlob());
    }

    public function testIsNumeric(): void
    {
        $fm = new FieldMetadata(-1, MYSQLI_NUM_FLAG, (object) []);
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
        $this->assertTrue($fm->isNumeric());
        $this->assertFalse($fm->isBlob());
    }

    public function testIsBlob(): void
    {
        $fm = new FieldMetadata(-1, MYSQLI_BLOB_FLAG, (object) []);
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
        $this->assertFalse($fm->isNumeric());
        $this->assertTrue($fm->isBlob());
    }

    public function testIsNumericFloat(): void
    {
        $fm = new FieldMetadata(MYSQLI_TYPE_FLOAT, MYSQLI_NUM_FLAG, (object) []);
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
