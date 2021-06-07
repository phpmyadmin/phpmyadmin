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
        $this->assertIsArray($tables);
        foreach ($tables as $tableName => $table) {
            $this->assertIsString($tableName);
            $this->assertIsArray($table);
            foreach ($table as $fieldName => $field) {
                $this->assertIsString($fieldName);
                $this->assertIsArray($field);
                $this->assertArrayHasKey('foreign_db', $field);
                $this->assertArrayHasKey('foreign_table', $field);
                $this->assertArrayHasKey('foreign_field', $field);
                $this->assertIsString($field['foreign_db']);
                $this->assertIsString($field['foreign_table']);
                $this->assertIsString($field['foreign_field']);
            }
        }
    }

    public function testGetMySql(): void
    {
        $tables = InternalRelations::getMySql();
        $this->assertIsArray($tables);
        foreach ($tables as $tableName => $table) {
            $this->assertIsString($tableName);
            $this->assertIsArray($table);
            foreach ($table as $fieldName => $field) {
                $this->assertIsString($fieldName);
                $this->assertIsArray($field);
                $this->assertArrayHasKey('foreign_db', $field);
                $this->assertArrayHasKey('foreign_table', $field);
                $this->assertArrayHasKey('foreign_field', $field);
                $this->assertIsString($field['foreign_db']);
                $this->assertIsString($field['foreign_table']);
                $this->assertIsString($field['foreign_field']);
            }
        }
    }
}
