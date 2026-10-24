<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests;

use PhpMyAdmin\FieldMetadata;
use PHPUnit\Framework\Attributes\CoversClass;

use function constant;
use function defined;
use function extension_loaded;

use const MYSQLI_BLOB_FLAG;
use const MYSQLI_NUM_FLAG;
use const MYSQLI_TYPE_FLOAT;
use const MYSQLI_TYPE_INT24;
use const MYSQLI_TYPE_STRING;

#[CoversClass(FieldMetadata::class)]
class FieldMetadataTest extends AbstractTestCase
{
    /**
     * The class constants exist so that the metadata can be handled without the
     * mysqli extension; they must stay identical to the mysqli ones.
     */
    public function testConstantsMatchMysqliConstants(): void
    {
        if (! extension_loaded('mysqli')) {
            self::markTestSkipped('The mysqli extension is not available.');
        }

        $constants = [
            'MYSQLI_NOT_NULL_FLAG' => FieldMetadata::NOT_NULL_FLAG,
            'MYSQLI_PRI_KEY_FLAG' => FieldMetadata::PRI_KEY_FLAG,
            'MYSQLI_UNIQUE_KEY_FLAG' => FieldMetadata::UNIQUE_KEY_FLAG,
            'MYSQLI_MULTIPLE_KEY_FLAG' => FieldMetadata::MULTIPLE_KEY_FLAG,
            'MYSQLI_BLOB_FLAG' => FieldMetadata::BLOB_FLAG,
            'MYSQLI_UNSIGNED_FLAG' => FieldMetadata::UNSIGNED_FLAG,
            'MYSQLI_ZEROFILL_FLAG' => FieldMetadata::ZEROFILL_FLAG,
            'MYSQLI_ENUM_FLAG' => FieldMetadata::ENUM_FLAG,
            'MYSQLI_SET_FLAG' => FieldMetadata::SET_FLAG,
            'MYSQLI_TYPE_DECIMAL' => FieldMetadata::MYSQL_TYPE_DECIMAL,
            'MYSQLI_TYPE_TINY' => FieldMetadata::MYSQL_TYPE_TINY,
            'MYSQLI_TYPE_SHORT' => FieldMetadata::MYSQL_TYPE_SHORT,
            'MYSQLI_TYPE_LONG' => FieldMetadata::MYSQL_TYPE_LONG,
            'MYSQLI_TYPE_FLOAT' => FieldMetadata::MYSQL_TYPE_FLOAT,
            'MYSQLI_TYPE_DOUBLE' => FieldMetadata::MYSQL_TYPE_DOUBLE,
            'MYSQLI_TYPE_NULL' => FieldMetadata::MYSQL_TYPE_NULL,
            'MYSQLI_TYPE_TIMESTAMP' => FieldMetadata::MYSQL_TYPE_TIMESTAMP,
            'MYSQLI_TYPE_LONGLONG' => FieldMetadata::MYSQL_TYPE_LONGLONG,
            'MYSQLI_TYPE_INT24' => FieldMetadata::MYSQL_TYPE_INT24,
            'MYSQLI_TYPE_DATE' => FieldMetadata::MYSQL_TYPE_DATE,
            'MYSQLI_TYPE_TIME' => FieldMetadata::MYSQL_TYPE_TIME,
            'MYSQLI_TYPE_DATETIME' => FieldMetadata::MYSQL_TYPE_DATETIME,
            'MYSQLI_TYPE_YEAR' => FieldMetadata::MYSQL_TYPE_YEAR,
            'MYSQLI_TYPE_NEWDATE' => FieldMetadata::MYSQL_TYPE_NEWDATE,
            'MYSQLI_TYPE_BIT' => FieldMetadata::MYSQL_TYPE_BIT,
            'MYSQLI_TYPE_NEWDECIMAL' => FieldMetadata::MYSQL_TYPE_NEWDECIMAL,
            'MYSQLI_TYPE_ENUM' => FieldMetadata::MYSQL_TYPE_ENUM,
            'MYSQLI_TYPE_SET' => FieldMetadata::MYSQL_TYPE_SET,
            'MYSQLI_TYPE_TINY_BLOB' => FieldMetadata::MYSQL_TYPE_TINY_BLOB,
            'MYSQLI_TYPE_MEDIUM_BLOB' => FieldMetadata::MYSQL_TYPE_MEDIUM_BLOB,
            'MYSQLI_TYPE_LONG_BLOB' => FieldMetadata::MYSQL_TYPE_LONG_BLOB,
            'MYSQLI_TYPE_BLOB' => FieldMetadata::MYSQL_TYPE_BLOB,
            'MYSQLI_TYPE_VAR_STRING' => FieldMetadata::MYSQL_TYPE_VAR_STRING,
            'MYSQLI_TYPE_STRING' => FieldMetadata::MYSQL_TYPE_STRING,
            'MYSQLI_TYPE_GEOMETRY' => FieldMetadata::MYSQL_TYPE_GEOMETRY,
            // MYSQLI_TYPE_JSON is not defined by every client library, see issue #16043
            'MYSQLI_TYPE_JSON' => defined('MYSQLI_TYPE_JSON') ? FieldMetadata::MYSQL_TYPE_JSON : null,
        ];

        foreach ($constants as $mysqliName => $classValue) {
            if ($classValue === null) {
                continue;
            }

            self::assertSame(constant($mysqliName), $classValue, $mysqliName);
        }
    }

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
