<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests;

use PhpMyAdmin\TypeClass;
use PhpMyAdmin\Types;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversClass(Types::class)]
final class TypesByDatabaseVersionTest extends AbstractTestCase
{
    /**
     * @param TypeClass $class    The class to get function list.
     * @param string[]  $includes Expected elements should contain in result
     * @param string[]  $excludes Expected elements should not contain in result
     * @phpstan-param array<string> $includes
     * @phpstan-param array<string> $excludes
     */
    #[DataProvider('providerFortTestGetFunctionsClass')]
    public function testGetFunctionsClass(string $version, TypeClass $class, array $includes, array $excludes): void
    {
        $dbi = $this->createDatabaseInterface();
        $dbi->setVersion(['@@version' => $version]);
        $types = new Types($dbi);

        $result = $types->getFunctionsClass($class);

        foreach ($includes as $value) {
            self::assertContains($value, $result);
        }

        if ($excludes === []) {
            return;
        }

        foreach ($excludes as $value) {
            self::assertNotContains($value, $result);
        }
    }

    /**
     * Data provider for testing function lists
     *
     * @return array<string, array{string, TypeClass, array<string>, array<string>}>
     */
    public static function providerFortTestGetFunctionsClass(): array
    {
        return [
            'mysql 5.1.0 - CHAR - not support INET6 Converter' => [
                '5.1.0',
                TypeClass::Char,
                // should contains
                [],
                // should not exist
                ['INET6_NTOA'],
            ],
            'mysql 8.0.30 - CHAR - support INET6 Converter' => [
                '8.0.30',
                TypeClass::Char,
                // should contains
                ['INET6_NTOA'],
                // should not exist
                [],
            ],
            'mariadb 5.1.0 - CHAR - not support INET6 Converter' => [
                '5.1.0-MariaDB',
                TypeClass::Char,
                // should contains
                [],
                // should not exist
                ['INET6_NTOA'],
            ],
            'mariadb 10.0.12 - CHAR - support INET6 Converter' => [
                '10.0.12-MariaDB',
                TypeClass::Char,
                // should contains
                ['INET6_NTOA'],
                // should not exist
                [],
            ],
            'mariadb 10.9.3 - CHAR - support INET6 Converter and UUID' => [
                '10.9.3-MariaDB',
                TypeClass::Char,
                // should contains
                ['INET6_NTOA', 'UUID'],
                // should not exist
                [],
            ],
            'mysql 5.1.0 - NUMBER - not support INET6 Converter' => [
                '5.1.0',
                TypeClass::Number,
                // should contains
                [],
                // should not exist
                ['INET6_ATON'],
            ],
            'mysql 8.0.30 - NUMBER - support INET6 Converter' => [
                '8.0.30',
                TypeClass::Number,
                // should contains
                ['INET6_ATON'],
                // should not exist
                [],
            ],
            'mariadb 5.1.0 - NUMBER - not support INET6 Converter' => [
                '5.1.0-MariaDB',
                TypeClass::Number,
                // should contains
                [],
                // should not exist
                ['INET6_ATON'],
            ],
            'mariadb 10.0.12 - NUMBER - support INET6 Converter' => [
                '10.0.12-MariaDB',
                TypeClass::Number,
                // should contains
                ['INET6_ATON'],
                // should not exist
                [],
            ],
            'mariadb 10.9.3 - NUMBER - support INET6 Converter and UUID' => [
                '10.9.3-MariaDB',
                TypeClass::Number,
                // should contains
                ['INET6_ATON', 'UUID_SHORT'],
                // should not exist
                [],
            ],
            'mysql 5.1.0 - SPATIAL - not support ST_Geometry' => [
                '5.1.0',
                TypeClass::Spatial,
                // should contains
                [
                    'GeomFromText',
                    'GeomFromWKB',
                    'GeomCollFromText',
                    'LineFromText',
                    'MLineFromText',
                    'PointFromText',
                    'MPointFromText',
                    'PolyFromText',
                    'MPolyFromText',
                    'GeomCollFromWKB',
                    'LineFromWKB',
                    'MLineFromWKB',
                    'PointFromWKB',
                    'MPointFromWKB',
                    'PolyFromWKB',
                    'MPolyFromWKB',
                ],
                // should not exist
                [
                    'ST_GeomFromText',
                    'ST_GeomFromWKB',
                    'ST_GeomCollFromText',
                    'ST_LineFromText',
                    'ST_MLineFromText',
                    'ST_PointFromText',
                    'ST_MPointFromText',
                    'ST_PolyFromText',
                    'ST_MPolyFromText',
                    'ST_GeomCollFromWKB',
                    'ST_LineFromWKB',
                    'ST_MLineFromWKB',
                    'ST_PointFromWKB',
                    'ST_MPointFromWKB',
                    'ST_PolyFromWKB',
                    'ST_MPolyFromWKB',
                ],
            ],
            'mysql 8.0.30 - SPATIAL - support ST_Geometry' => [
                '8.0.30',
                TypeClass::Spatial,
                // should contains
                [
                    'ST_GeomFromText',
                    'ST_GeomFromWKB',
                    'ST_GeomCollFromText',
                    'ST_LineFromText',
                    'ST_MLineFromText',
                    'ST_PointFromText',
                    'ST_MPointFromText',
                    'ST_PolyFromText',
                    'ST_MPolyFromText',
                    'ST_GeomCollFromWKB',
                    'ST_LineFromWKB',
                    'ST_MLineFromWKB',
                    'ST_PointFromWKB',
                    'ST_MPointFromWKB',
                    'ST_PolyFromWKB',
                    'ST_MPolyFromWKB',
                ],
                // should not exist
                [
                    'GeomFromText',
                    'GeomFromWKB',
                    'GeomCollFromText',
                    'LineFromText',
                    'MLineFromText',
                    'PointFromText',
                    'MPointFromText',
                    'PolyFromText',
                    'MPolyFromText',
                    'GeomCollFromWKB',
                    'LineFromWKB',
                    'MLineFromWKB',
                    'PointFromWKB',
                    'MPointFromWKB',
                    'PolyFromWKB',
                    'MPolyFromWKB',
                ],
            ],
        ];
    }

    /**
     * @param string[] $includes Expected elements should contain in result
     * @param string[] $excludes Expected elements should not contain in result
     * @phpstan-param array<string> $includes
     * @phpstan-param array<string> $excludes
     */
    #[DataProvider('providerFortTestGetAllFunctions')]
    public function testGetAllFunctions(string $version, array $includes, array $excludes): void
    {
        $dbi = $this->createDatabaseInterface();
        $dbi->setVersion(['@@version' => $version]);
        $types = new Types($dbi);

        $result = $types->getAllFunctions();

        foreach ($includes as $value) {
            self::assertContains($value, $result);
        }

        if ($excludes === []) {
            return;
        }

        foreach ($excludes as $value) {
            self::assertNotContains($value, $result);
        }
    }

    /**
     * Data provider for testing get all functions
     *
     * @return array<string, array{string, array<string>, array<string>}>
     */
    public static function providerFortTestGetAllFunctions(): array
    {
        return [
            'mysql 5.1.0 - not support INET6_ATON, ST_Geometry' => [
                '5.1.0',
                [
                    'GeomFromText',
                    'GeomFromWKB',
                    'GeomCollFromText',
                    'LineFromText',
                    'MLineFromText',
                    'PointFromText',
                    'MPointFromText',
                    'PolyFromText',
                    'MPolyFromText',
                    'GeomCollFromWKB',
                    'LineFromWKB',
                    'MLineFromWKB',
                    'PointFromWKB',
                    'MPointFromWKB',
                    'PolyFromWKB',
                    'MPolyFromWKB',
                ],
                [
                    'INET6_ATON',
                    'INET6_ATON',
                    'ST_GeomFromText',
                    'ST_GeomFromWKB',
                    'ST_GeomCollFromText',
                    'ST_LineFromText',
                    'ST_MLineFromText',
                    'ST_PointFromText',
                    'ST_MPointFromText',
                    'ST_PolyFromText',
                    'ST_MPolyFromText',
                    'ST_GeomCollFromWKB',
                    'ST_LineFromWKB',
                    'ST_MLineFromWKB',
                    'ST_PointFromWKB',
                    'ST_MPointFromWKB',
                    'ST_PolyFromWKB',
                    'ST_MPolyFromWKB',
                ],
            ],
            'mysql 8.0.30 - support INET6_ATON and ST_Geometry' => [
                '8.0.30',
                [
                    'INET6_ATON',
                    'INET6_ATON',
                    'ST_GeomFromText',
                    'ST_GeomFromWKB',
                    'ST_GeomCollFromText',
                    'ST_LineFromText',
                    'ST_MLineFromText',
                    'ST_PointFromText',
                    'ST_MPointFromText',
                    'ST_PolyFromText',
                    'ST_MPolyFromText',
                    'ST_GeomCollFromWKB',
                    'ST_LineFromWKB',
                    'ST_MLineFromWKB',
                    'ST_PointFromWKB',
                    'ST_MPointFromWKB',
                    'ST_PolyFromWKB',
                    'ST_MPolyFromWKB',
                    'UUID',
                    'UUID_SHORT',
                ],
                [
                    'GeomFromText',
                    'GeomFromWKB',
                    'GeomCollFromText',
                    'LineFromText',
                    'MLineFromText',
                    'PointFromText',
                    'MPointFromText',
                    'PolyFromText',
                    'MPolyFromText',
                    'GeomCollFromWKB',
                    'LineFromWKB',
                    'MLineFromWKB',
                    'PointFromWKB',
                    'MPointFromWKB',
                    'PolyFromWKB',
                    'MPolyFromWKB',
                ],
            ],
            'mariadb 5.1.0 - not support INET6_ATON and ST_Geometry' => [
                '5.1.0-MariaDB',
                [
                    'GeomFromText',
                    'GeomFromWKB',
                    'GeomCollFromText',
                    'LineFromText',
                    'MLineFromText',
                    'PointFromText',
                    'MPointFromText',
                    'PolyFromText',
                    'MPolyFromText',
                    'GeomCollFromWKB',
                    'LineFromWKB',
                    'MLineFromWKB',
                    'PointFromWKB',
                    'MPointFromWKB',
                    'PolyFromWKB',
                    'MPolyFromWKB',
                    'UUID',
                    'UUID_SHORT',
                ],
                [
                    'INET6_ATON',
                    'INET6_ATON',
                    'ST_GeomFromText',
                    'ST_GeomFromWKB',
                    'ST_GeomCollFromText',
                    'ST_LineFromText',
                    'ST_MLineFromText',
                    'ST_PointFromText',
                    'ST_MPointFromText',
                    'ST_PolyFromText',
                    'ST_MPolyFromText',
                    'ST_GeomCollFromWKB',
                    'ST_LineFromWKB',
                    'ST_MLineFromWKB',
                    'ST_PointFromWKB',
                    'ST_MPointFromWKB',
                    'ST_PolyFromWKB',
                    'ST_MPolyFromWKB',
                ],
            ],
            'mariadb 10.6.0 - support INET6_ATON and ST_Geometry' => [
                '10.6.0-MariaDB',
                [
                    'INET6_ATON',
                    'INET6_ATON',
                    'ST_GeomFromText',
                    'ST_GeomFromWKB',
                    'ST_GeomCollFromText',
                    'ST_LineFromText',
                    'ST_MLineFromText',
                    'ST_PointFromText',
                    'ST_MPointFromText',
                    'ST_PolyFromText',
                    'ST_MPolyFromText',
                    'ST_GeomCollFromWKB',
                    'ST_LineFromWKB',
                    'ST_MLineFromWKB',
                    'ST_PointFromWKB',
                    'ST_MPointFromWKB',
                    'ST_PolyFromWKB',
                    'ST_MPolyFromWKB',
                    'UUID',
                    'UUID_SHORT',
                ],
                [
                    'GeomFromText',
                    'GeomFromWKB',
                    'GeomCollFromText',
                    'LineFromText',
                    'MLineFromText',
                    'PointFromText',
                    'MPointFromText',
                    'PolyFromText',
                    'MPolyFromText',
                    'GeomCollFromWKB',
                    'LineFromWKB',
                    'MLineFromWKB',
                    'PointFromWKB',
                    'MPointFromWKB',
                    'PolyFromWKB',
                    'MPolyFromWKB',
                ],
            ],
            'mariadb 10.9.3 - support INET6_ATON, ST_Geometry and UUID' => [
                '10.9.3-MariaDB',
                [
                    'INET6_ATON',
                    'INET6_ATON',
                    'ST_GeomFromText',
                    'ST_GeomFromWKB',
                    'ST_GeomCollFromText',
                    'ST_LineFromText',
                    'ST_MLineFromText',
                    'ST_PointFromText',
                    'ST_MPointFromText',
                    'ST_PolyFromText',
                    'ST_MPolyFromText',
                    'ST_GeomCollFromWKB',
                    'ST_LineFromWKB',
                    'ST_MLineFromWKB',
                    'ST_PointFromWKB',
                    'ST_MPointFromWKB',
                    'ST_PolyFromWKB',
                    'ST_MPolyFromWKB',
                    'UUID',
                    'UUID_SHORT',
                ],
                [
                    'GeomFromText',
                    'GeomFromWKB',
                    'GeomCollFromText',
                    'LineFromText',
                    'MLineFromText',
                    'PointFromText',
                    'MPointFromText',
                    'PolyFromText',
                    'MPolyFromText',
                    'GeomCollFromWKB',
                    'LineFromWKB',
                    'MLineFromWKB',
                    'PointFromWKB',
                    'MPointFromWKB',
                    'PolyFromWKB',
                    'MPolyFromWKB',
                ],
            ],
        ];
    }

    /** @param array<int|string, array<int, string>|string> $expected */
    #[DataProvider('providerFortTestGetColumns')]
    public function testGetColumns(string $version, array $expected): void
    {
        $dbi = $this->createDatabaseInterface();
        $dbi->setVersion(['@@version' => $version]);
        $types = new Types($dbi);

        self::assertSame($expected, $types->getColumns());
    }

    /**
     * Data provider for testing test columns
     *
     * @return array<string, array{string, array<int|string, array<int, string>|string>}>
     */
    public static function providerFortTestGetColumns(): array
    {
        return [
            'mysql 5.1.0 - not support INET6, JSON and UUID' => [
                '5.1.0',
                [
                    0 => 'INT',
                    1 => 'VARCHAR',
                    2 => 'TEXT',
                    3 => 'DATE',
                    'Numeric' => [
                        'TINYINT',
                        'SMALLINT',
                        'MEDIUMINT',
                        'INT',
                        'BIGINT',
                        '-',
                        'DECIMAL',
                        'FLOAT',
                        'DOUBLE',
                        'REAL',
                        '-',
                        'BIT',
                        'BOOLEAN',
                        'SERIAL',
                    ],
                    'Date and time' => ['DATE', 'DATETIME', 'TIMESTAMP', 'TIME', 'YEAR'],
                    'String' => [
                        'CHAR',
                        'VARCHAR',
                        '-',
                        'TINYTEXT',
                        'TEXT',
                        'MEDIUMTEXT',
                        'LONGTEXT',
                        '-',
                        'BINARY',
                        'VARBINARY',
                        '-',
                        'TINYBLOB',
                        'BLOB',
                        'MEDIUMBLOB',
                        'LONGBLOB',
                        '-',
                        'ENUM',
                        'SET',
                    ],
                    'Spatial' => [
                        'GEOMETRY',
                        'POINT',
                        'LINESTRING',
                        'POLYGON',
                        'MULTIPOINT',
                        'MULTILINESTRING',
                        'MULTIPOLYGON',
                        'GEOMETRYCOLLECTION',
                    ],
                ],
            ],
            'mysql 8.0.30 - support JSON but not support INET6 and UUID' => [
                '8.0.30',
                [
                    0 => 'INT',
                    1 => 'VARCHAR',
                    2 => 'TEXT',
                    3 => 'DATE',
                    'Numeric' => [
                        'TINYINT',
                        'SMALLINT',
                        'MEDIUMINT',
                        'INT',
                        'BIGINT',
                        '-',
                        'DECIMAL',
                        'FLOAT',
                        'DOUBLE',
                        'REAL',
                        '-',
                        'BIT',
                        'BOOLEAN',
                        'SERIAL',
                    ],
                    'Date and time' => ['DATE', 'DATETIME', 'TIMESTAMP', 'TIME', 'YEAR'],
                    'String' => [
                        'CHAR',
                        'VARCHAR',
                        '-',
                        'TINYTEXT',
                        'TEXT',
                        'MEDIUMTEXT',
                        'LONGTEXT',
                        '-',
                        'BINARY',
                        'VARBINARY',
                        '-',
                        'TINYBLOB',
                        'BLOB',
                        'MEDIUMBLOB',
                        'LONGBLOB',
                        '-',
                        'ENUM',
                        'SET',
                    ],
                    'Spatial' => [
                        'GEOMETRY',
                        'POINT',
                        'LINESTRING',
                        'POLYGON',
                        'MULTIPOINT',
                        'MULTILINESTRING',
                        'MULTIPOLYGON',
                        'GEOMETRYCOLLECTION',
                    ],
                    'JSON' => ['JSON'],
                ],
            ],
            'mariadb 5.1.0 - not support INET6, JSON and UUID' => [
                '5.1.0-MariaDB',
                [
                    0 => 'INT',
                    1 => 'VARCHAR',
                    2 => 'TEXT',
                    3 => 'DATE',
                    'Numeric' => [
                        'TINYINT',
                        'SMALLINT',
                        'MEDIUMINT',
                        'INT',
                        'BIGINT',
                        '-',
                        'DECIMAL',
                        'FLOAT',
                        'DOUBLE',
                        'REAL',
                        '-',
                        'BIT',
                        'BOOLEAN',
                        'SERIAL',
                    ],
                    'Date and time' => ['DATE', 'DATETIME', 'TIMESTAMP', 'TIME', 'YEAR'],
                    'String' => [
                        'CHAR',
                        'VARCHAR',
                        '-',
                        'TINYTEXT',
                        'TEXT',
                        'MEDIUMTEXT',
                        'LONGTEXT',
                        '-',
                        'BINARY',
                        'VARBINARY',
                        '-',
                        'TINYBLOB',
                        'BLOB',
                        'MEDIUMBLOB',
                        'LONGBLOB',
                        '-',
                        'ENUM',
                        'SET',
                    ],
                    'Spatial' => [
                        'GEOMETRY',
                        'POINT',
                        'LINESTRING',
                        'POLYGON',
                        'MULTIPOINT',
                        'MULTILINESTRING',
                        'MULTIPOLYGON',
                        'GEOMETRYCOLLECTION',
                    ],
                ],
            ],
            'mariadb 10.2.8 - support JSON but not support INET6 and UUID' => [
                '10.2.8-MariaDB',
                [
                    0 => 'INT',
                    1 => 'VARCHAR',
                    2 => 'TEXT',
                    3 => 'DATE',
                    'Numeric' => [
                        'TINYINT',
                        'SMALLINT',
                        'MEDIUMINT',
                        'INT',
                        'BIGINT',
                        '-',
                        'DECIMAL',
                        'FLOAT',
                        'DOUBLE',
                        'REAL',
                        '-',
                        'BIT',
                        'BOOLEAN',
                        'SERIAL',
                    ],
                    'Date and time' => ['DATE', 'DATETIME', 'TIMESTAMP', 'TIME', 'YEAR'],
                    'String' => [
                        'CHAR',
                        'VARCHAR',
                        '-',
                        'TINYTEXT',
                        'TEXT',
                        'MEDIUMTEXT',
                        'LONGTEXT',
                        '-',
                        'BINARY',
                        'VARBINARY',
                        '-',
                        'TINYBLOB',
                        'BLOB',
                        'MEDIUMBLOB',
                        'LONGBLOB',
                        '-',
                        'ENUM',
                        'SET',
                    ],
                    'Spatial' => [
                        'GEOMETRY',
                        'POINT',
                        'LINESTRING',
                        'POLYGON',
                        'MULTIPOINT',
                        'MULTILINESTRING',
                        'MULTIPOLYGON',
                        'GEOMETRYCOLLECTION',
                    ],
                    'JSON' => ['JSON'],
                ],
            ],
            'mariadb 10.5.0 - support JSON and INET6 but not support UUID' => [
                '10.5.0-MariaDB',
                [
                    0 => 'INT',
                    1 => 'VARCHAR',
                    2 => 'TEXT',
                    3 => 'DATE',
                    'Numeric' => [
                        'TINYINT',
                        'SMALLINT',
                        'MEDIUMINT',
                        'INT',
                        'BIGINT',
                        '-',
                        'DECIMAL',
                        'FLOAT',
                        'DOUBLE',
                        'REAL',
                        '-',
                        'BIT',
                        'BOOLEAN',
                        'SERIAL',
                    ],
                    'Date and time' => ['DATE', 'DATETIME', 'TIMESTAMP', 'TIME', 'YEAR'],
                    'String' => [
                        'CHAR',
                        'VARCHAR',
                        '-',
                        'TINYTEXT',
                        'TEXT',
                        'MEDIUMTEXT',
                        'LONGTEXT',
                        '-',
                        'BINARY',
                        'VARBINARY',
                        '-',
                        'TINYBLOB',
                        'BLOB',
                        'MEDIUMBLOB',
                        'LONGBLOB',
                        '-',
                        'ENUM',
                        'SET',
                        '-',
                        'INET6',
                    ],
                    'Spatial' => [
                        'GEOMETRY',
                        'POINT',
                        'LINESTRING',
                        'POLYGON',
                        'MULTIPOINT',
                        'MULTILINESTRING',
                        'MULTIPOLYGON',
                        'GEOMETRYCOLLECTION',
                    ],
                    'JSON' => ['JSON'],
                ],
            ],
            'mariadb 10.9.3 - support INET6, JSON and UUID' => [
                '10.9.3-MariaDB',
                [
                    0 => 'INT',
                    1 => 'VARCHAR',
                    2 => 'TEXT',
                    3 => 'DATE',
                    4 => 'UUID',
                    'Numeric' => [
                        'TINYINT',
                        'SMALLINT',
                        'MEDIUMINT',
                        'INT',
                        'BIGINT',
                        '-',
                        'DECIMAL',
                        'FLOAT',
                        'DOUBLE',
                        'REAL',
                        '-',
                        'BIT',
                        'BOOLEAN',
                        'SERIAL',
                    ],
                    'Date and time' => ['DATE', 'DATETIME', 'TIMESTAMP', 'TIME', 'YEAR'],
                    'String' => [
                        'CHAR',
                        'VARCHAR',
                        '-',
                        'TINYTEXT',
                        'TEXT',
                        'MEDIUMTEXT',
                        'LONGTEXT',
                        '-',
                        'BINARY',
                        'VARBINARY',
                        '-',
                        'TINYBLOB',
                        'BLOB',
                        'MEDIUMBLOB',
                        'LONGBLOB',
                        '-',
                        'ENUM',
                        'SET',
                        '-',
                        'INET6',
                    ],
                    'Spatial' => [
                        'GEOMETRY',
                        'POINT',
                        'LINESTRING',
                        'POLYGON',
                        'MULTIPOINT',
                        'MULTILINESTRING',
                        'MULTIPOLYGON',
                        'GEOMETRYCOLLECTION',
                    ],
                    'JSON' => ['JSON'],
                    'UUID' => ['UUID'],
                ],
            ],
        ];
    }
}
