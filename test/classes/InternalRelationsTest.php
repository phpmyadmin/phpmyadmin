<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests;

use PhpMyAdmin\InternalRelations;
use PHPUnit\Framework\TestCase;

/**
 * @covers \PhpMyAdmin\InternalRelations
 */
class InternalRelationsTest extends TestCase
{
    public function testGetInformationSchema(): void
    {
        $tables = InternalRelations::getInformationSchema();
        self::assertIsArray($tables);
        foreach ($tables as $tableName => $table) {
            self::assertIsString($tableName);
            self::assertIsArray($table);
            foreach ($table as $fieldName => $field) {
                self::assertIsString($fieldName);
                self::assertIsArray($field);
                self::assertArrayHasKey('foreign_db', $field);
                self::assertArrayHasKey('foreign_table', $field);
                self::assertArrayHasKey('foreign_field', $field);
                self::assertIsString($field['foreign_db']);
                self::assertIsString($field['foreign_table']);
                self::assertIsString($field['foreign_field']);
            }
        }
    }

    public function testGetMySql(): void
    {
        $tables = InternalRelations::getMySql();
        self::assertIsArray($tables);
        foreach ($tables as $tableName => $table) {
            self::assertIsString($tableName);
            self::assertIsArray($table);
            foreach ($table as $fieldName => $field) {
                self::assertIsString($fieldName);
                self::assertIsArray($field);
                self::assertArrayHasKey('foreign_db', $field);
                self::assertArrayHasKey('foreign_table', $field);
                self::assertArrayHasKey('foreign_field', $field);
                self::assertIsString($field['foreign_db']);
                self::assertIsString($field['foreign_table']);
                self::assertIsString($field['foreign_field']);
            }
        }
    }
}
