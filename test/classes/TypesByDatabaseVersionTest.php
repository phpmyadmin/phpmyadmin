<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests;

use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Types;
use PHPUnit\Framework\MockObject\Stub;

/** @covers \PhpMyAdmin\Types */
class TypesByDatabaseVersionTest extends AbstractTestCase
{
    /** @var DatabaseInterface&Stub */
    private DatabaseInterface $dbiStub;

    protected Types $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->dbiStub = $this->createStub(DatabaseInterface::class);
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown(): void
    {
        parent::tearDown();

        unset($this->dbiStub);
        unset($this->object);
    }

    /**
     * @param string $database  Database
     * @param int    $dbVersion Database Version
     * @param string $class     The class to get function list.
     * @param array  $includes  Expected elements should contain in result
     * @param array  $excludes  Expected elements should not contain in result
     * @phpstan-param array<string> $includes
     * @phpstan-param array<string> $excludes
     *
     * @dataProvider providerFortTestGetFunctionsClass
     */
    public function testGetFunctionsClass(
        string $database,
        int $dbVersion,
        string $class,
        array $includes,
        array $excludes,
    ): void {
        $this->createObject($database, $dbVersion);

        $result = $this->object->getFunctionsClass($class);

        if ($includes) {
            foreach ($includes as $value) {
                $this->assertContains($value, $result);
            }
        }

        if (! $excludes) {
            return;
        }

        foreach ($excludes as $value) {
            $this->assertNotContains($value, $result);
        }
    }

    /**
     * Data provider for testing function lists
     *
     * @psalm-return array<string, array{string, int, string, array<string>, array<string>}>
     */
    public static function providerFortTestGetFunctionsClass(): array
    {
        return [
            'mysql 5.1.0 - CHAR - not support INET6 Converter' => [
                'mysql',
                50100,
                'CHAR',
                // should contains
                [],
                // should not exist
                [ 'INET6_NTOA' ],
            ],
            'mysql 8.0.30 - CHAR - support INET6 Converter' => [
                'mysql',
                80030,
                'CHAR',
                // should contains
                [ 'INET6_NTOA' ],
                // should not exist
                [],
            ],
            'mariadb 5.1.0 - CHAR - not support INET6 Converter' => [
                'mariadb',
                50100,
                'CHAR',
                // should contains
                [],
                // should not exist
                [ 'INET6_NTOA' ],
            ],
            'mariadb 10.0.12 - CHAR - support INET6 Converter' => [
                'mariadb',
                100012,
                'CHAR',
                // should contains
                [ 'INET6_NTOA' ],
                // should not exist
                [],
            ],
            'mariadb 10.9.3 - CHAR - support INET6 Converter and UUID' => [
                'mariadb',
                100903,
                'CHAR',
                // should contains
                [ 'INET6_NTOA', 'UUID' ],
                // should not exist
                [],
            ],
            'mysql 5.1.0 - NUMBER - not support INET6 Converter' => [
                'mysql',
                50100,
                'NUMBER',
                // should contains
                [],
                // should not exist
                [ 'INET6_ATON' ],
            ],
            'mysql 8.0.30 - NUMBER - support INET6 Converter' => [
                'mysql',
                80030,
                'NUMBER',
                // should contains
                [ 'INET6_ATON' ],
                // should not exist
                [],
            ],
            'mariadb 5.1.0 - NUMBER - not support INET6 Converter' => [
                'mariadb',
                50100,
                'NUMBER',
                // should contains
                [],
                // should not exist
                [ 'INET6_ATON' ],
            ],
            'mariadb 10.0.12 - NUMBER - support INET6 Converter' => [
                'mariadb',
                100012,
                'NUMBER',
                // should contains
                [ 'INET6_ATON' ],
                // should not exist
                [],
            ],
            'mariadb 10.9.3 - NUMBER - support INET6 Converter and UUID' => [
                'mariadb',
                100903,
                'NUMBER',
                // should contains
                [ 'INET6_ATON', 'UUID_SHORT' ],
                // should not exist
                [],
            ],
            'mysql 5.1.0 - SPATIAL - not support ST_Geometry' => [
                'mysql',
                50100,
                'SPATIAL',
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
                'mysql',
                80030,
                'SPATIAL',
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
     * Test for getFunctions
     *
     * @param string $database  Database
     * @param int    $dbVersion Database Version
     * @param array  $includes  Expected elements should contain in result
     * @param array  $excludes  Expected elements should not contain in result
     * @phpstan-param array<string> $includes
     * @phpstan-param array<string> $excludes
     *
     * @dataProvider providerFortTestGetFunctions
     */
    public function testGetFunctions(string $database, int $dbVersion, array $includes, array $excludes): void
    {
        $this->createObject($database, $dbVersion);

        $result = $this->object->getFunctions('enum');

        if ($includes) {
            foreach ($includes as $value) {
                $this->assertContains($value, $result);
            }
        }

        if (! $excludes) {
            return;
        }

        foreach ($excludes as $value) {
            $this->assertNotContains($value, $result);
        }
    }

    /**
     * Data provider for testing get functions
     *
     * @psalm-return array<string, array{string, int, array<string>, array<string>}>
     */
    public static function providerFortTestGetFunctions(): array
    {
        return [
            'mysql 5.1.0 - not support INET6 Converter' => [
                'mysql',
                50100,
                // should contains
                [],
                // should not exist
                [ 'INET6_NTOA' ],
            ],
            'mysql 8.0.30 - support INET6 Converter' => [
                'mysql',
                80030,
                // should contains
                [ 'INET6_NTOA' ],
                // should not exist
                [],
            ],
            'mariadb 5.1.0 - not support INET6 Converter' => [
                'mariadb',
                50100,
                // should contains
                [],
                // should not exist
                [ 'INET6_NTOA' ],
            ],
            'mariadb 10.9.3 - support INET6 Converter' => [
                'mariadb',
                100903,
                // should contains
                [ 'INET6_NTOA' ],
                // should not exist
                [],
            ],
        ];
    }

    /**
     * Test for getAllFunctions
     *
     * @param string $database  Database
     * @param int    $dbVersion Database Version
     * @param array  $includes  Expected elements should contain in result
     * @param array  $excludes  Expected elements should not contain in result
     * @phpstan-param array<string> $includes
     * @phpstan-param array<string> $excludes
     *
     * @dataProvider providerFortTestGetAllFunctions
     */
    public function testGetAllFunctions(string $database, int $dbVersion, array $includes, array $excludes): void
    {
        $this->createObject($database, $dbVersion);

        $result = $this->object->getAllFunctions();

        if ($includes) {
            foreach ($includes as $value) {
                $this->assertContains($value, $result);
            }
        }

        if (! $excludes) {
            return;
        }

        foreach ($excludes as $value) {
            $this->assertNotContains($value, $result);
        }
    }

    /**
     * Data provider for testing get all functions
     *
     * @psalm-return array<string, array{string, int, array<string>, array<string>}>
     */
    public static function providerFortTestGetAllFunctions(): array
    {
        return [
            'mysql 5.1.0 - not support INET6_ATON, ST_Geometry' => [
                'mysql',
                50100,
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
                'mysql',
                80030,
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
                'mariadb',
                50100,
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
                'mariadb',
                100600,
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
                'mariadb',
                100903,
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

    /**
     * Test for getColumns
     *
     * @param string $database  Database
     * @param int    $dbVersion Database Version
     * @param array  $expected  Expected Result
     * @phpstan-param array<int|string, array<int, string>|string> $expected
     *
     * @dataProvider providerFortTestGetColumns
     */
    public function testGetColumns(string $database, int $dbVersion, array $expected): void
    {
        $this->createObject($database, $dbVersion);

        $this->assertEquals($expected, $this->object->getColumns());
    }

    /**
     * Data provider for testing test columns
     *
     * @psalm-return array<string, array{string, int, array<int|string, array<int, string>|string>}>
     */
    public static function providerFortTestGetColumns(): array
    {
        return [
            'mysql 5.1.0 - not support INET6, JSON and UUID' => [
                'mysql',
                50100,
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
                'mysql',
                80030,
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
                'mariadb',
                50100,
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
                'mariadb',
                100208,
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
                    'JSON' => [ 'JSON' ],
                ],
            ],
            'mariadb 10.5.0 - support JSON and INET6 but not support UUID' => [
                'mariadb',
                100500,
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
                    'JSON' => [ 'JSON' ],
                ],
            ],
            'mariadb 10.9.3 - support INET6, JSON and UUID' => [
                'mariadb',
                100903,
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
                    'JSON' => [ 'JSON' ],
                    'UUID' => [ 'UUID' ],
                ],
            ],
        ];
    }

    /**
     * @param string $database Database
     * @param int    $version  Database Version
     */
    private function createObject(string $database, int $version): void
    {
        $this->dbiStub->method('isMariaDB')->willReturn($database === 'mariadb');
        $this->dbiStub->method('getVersion')->willReturn($version);
        $this->object = new Types($this->dbiStub);
    }
}
