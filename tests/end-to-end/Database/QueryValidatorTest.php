<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Database;

use PhpMyAdmin\Database\QueryValidator;
use PHPUnit\Framework\TestCase;

class QueryValidatorTest extends TestCase
{
    private QueryValidator $validator;

    protected function setUp(): void
    {
        $this->validator = new QueryValidator();
    }

    public function testValidatesSimpleQueryWithoutTable(): void
    {
        $result = $this->validator->validateQuery('SELECT 1;', null);
        self::assertTrue($result['isValid']);
        self::assertEquals('SELECT 1;', $result['query']);
    }

    public function testRejectsMetadataQueryWithoutTable(): void
    {
        $result = $this->validator->validateQuery('SHOW COLUMNS FROM test_db;', null);
        self::assertFalse($result['isValid']);
        self::assertNotNull($result['error']);
    }

    public function testAllowsShowColumnsWithTable(): void
    {
        $result = $this->validator->validateQuery('SHOW COLUMNS FROM `test_table`;', 'test_table');
        self::assertTrue($result['isValid']);
        self::assertEquals('SHOW COLUMNS FROM `test_table`;', $result['query']);
    }

    public function testAllowsShowIndexesWithTable(): void
    {
        $result = $this->validator->validateQuery('SHOW INDEXES FROM `test_table`;', 'test_table');
        self::assertTrue($result['isValid']);
        self::assertEquals('SHOW INDEXES FROM `test_table`;', $result['query']);
    }

    public function testHandlesMultipleQueriesWithoutTable(): void
    {
        $result = $this->validator->validateQuery('SELECT 1; SHOW COLUMNS FROM test_db; SELECT 2;', null);
        self::assertFalse($result['isValid']);
        if ($result['error'] === null) {
            return;
        }

        self::assertStringContainsString('Table must be selected', $result['error']);
    }

    public function testHandlesMultipleQueriesWithTable(): void
    {
        $result = $this->validator->validateQuery('SELECT 1; SHOW COLUMNS FROM `test_table`; SELECT 2;', 'test_table');
        self::assertTrue($result['isValid']);
        self::assertEquals('SELECT 1; SHOW COLUMNS FROM `test_table`; SELECT 2;', $result['query']);
    }

    public function testCleansMultipleSemicolons(): void
    {
        $result = $this->validator->validateQuery('SELECT 1;;;;;', null);
        self::assertTrue($result['isValid']);
        self::assertEquals('SELECT 1;', $result['query']);
    }

    public function testTrimsWhitespace(): void
    {
        $result = $this->validator->validateQuery("  SELECT 1;  \n  \t  ", null);
        self::assertTrue($result['isValid']);
        self::assertEquals('SELECT 1;', $result['query']);
    }

    public function testPreventsSqlInjectionInMetadataQueries(): void
    {
        $result = $this->validator->validateQuery('SHOW COLUMNS FROM `test_table`; DROP TABLE users; --', 'test_table');
        self::assertFalse($result['isValid']);
        if ($result['error'] === null) {
            return;
        }

        self::assertStringContainsString('Invalid query', $result['error']);
    }
}
