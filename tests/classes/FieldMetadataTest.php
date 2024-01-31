<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests;

use PhpMyAdmin\FieldMetadata;
use PHPUnit\Framework\Attributes\CoversClass;

use const MYSQLI_BLOB_FLAG;
use const MYSQLI_NUM_FLAG;
use const MYSQLI_TYPE_FLOAT;
use const MYSQLI_TYPE_INT24;
use const MYSQLI_TYPE_STRING;

#[CoversClass(FieldMetadata::class)]
class FieldMetadataTest extends AbstractTestCase
{
    public function testEmptyConstruct(): void
    {
        $fm = FieldHelper::fromArray(['type' => -1]);
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

    public function testIsBinary(): void
    {
        $fm = FieldHelper::fromArray(['type' => MYSQLI_TYPE_STRING, 'charsetnr' => 63]);
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
        $fm = FieldHelper::fromArray(['type' => MYSQLI_TYPE_INT24, 'flags' => MYSQLI_NUM_FLAG]);
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
        $fm = FieldHelper::fromArray(['type' => -1, 'flags' => MYSQLI_BLOB_FLAG]);
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
        $fm = FieldHelper::fromArray(['type' => MYSQLI_TYPE_FLOAT, 'flags' => MYSQLI_NUM_FLAG]);
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
