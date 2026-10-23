<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers\Table\Structure;

use PhpMyAdmin\Config;
use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\Controllers\Table\Structure\SaveController;
use PhpMyAdmin\Controllers\Table\StructureController;
use PhpMyAdmin\Current;
use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\Http\Factory\ResponseFactory;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\ResponseRenderer;
use PhpMyAdmin\Transformations;
use PhpMyAdmin\UserPrivileges;
use PhpMyAdmin\UserPrivilegesFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionClass;

use function array_merge;

#[CoversClass(SaveController::class)]
class SaveControllerTest extends AbstractTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        DatabaseInterface::$instance = $this->createDatabaseInterface();
    }

    public function testSaveController(): void
    {
        $_POST = [
            'db' => 'test_db',
            'table' => 'test_table',
            'orig_num_fields' => '1',
            'orig_field_where' => '',
            'orig_after_field' => '',
            'selected' => ['name'],
            'field_orig' => ['name'],
            'field_type_orig' => ['VARCHAR'],
            'field_length_orig' => ['20'],
            'field_default_value_orig' => [''],
            'field_default_type_orig' => ['NONE'],
            'field_collation_orig' => ['utf8mb4_general_ci'],
            'field_attribute_orig' => [''],
            'field_null_orig' => ['NO'],
            'field_extra_orig' => [''],
            'field_comments_orig' => [''],
            'field_virtuality_orig' => [''],
            'field_expression_orig' => [''],
            'primary_indexes' => '[]',
            'unique_indexes' => '[]',
            'indexes' => '[]',
            'fulltext_indexes' => '[]',
            'spatial_indexes' => '[]',
            'field_name' => ['new_name'],
            'field_type' => ['VARCHAR'],
            'field_length' => ['21'],
            'field_default_type' => ['NONE'],
            'field_default_value' => [''],
            'field_collation' => ['utf8mb4_general_ci'],
            'field_attribute' => [''],
            'field_adjust_privileges' => ['NULL'],
            'field_comments' => [''],
            'field_virtuality' => [''],
            'field_expression' => [''],
            'field_move_to' => [''],
            'field_mimetype' => [''],
            'field_transformation' => [''],
            'field_transformation_options' => [''],
            'field_input_transformation' => [''],
            'field_input_transformation_options' => [''],
            'do_save_data' => 'Save',
        ];

        Current::$database = 'test_db';
        Current::$table = 'test_table';

        $dummyDbi = $this->createDbiDummy();
        $dummyDbi->addSelectDb('test_db');
        $dummyDbi->addResult(
            'ALTER TABLE `test_table` CHANGE `name` `new_name` VARCHAR(21)'
            . ' CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL;',
            true,
        );
        $dbi = $this->createDatabaseInterface($dummyDbi);

        $request = self::createStub(ServerRequest::class);

        $mock = $this->createMock(StructureController::class);
        $mock->expects(self::once())->method('__invoke')->with($request)
            ->willReturn(ResponseFactory::create()->createResponse());

        $config = new Config();
        $relation = new Relation($dbi, $config);
        (new SaveController(
            new ResponseRenderer(),
            $relation,
            new Transformations($dbi, $relation),
            $dbi,
            $mock,
            new UserPrivilegesFactory($dbi),
            $config,
            new Template($config),
        ))($request);

        self::assertArrayNotHasKey('selected', $_POST);
    }

    /** @param array<string, string[]> $post */
    #[DataProvider('providerInvisibleAndCheck')]
    public function testSaveInvisibleAndCheck(array $post, string $expectedQuery): void
    {
        $_POST = array_merge([
            'db' => 'test_db',
            'table' => 'test_table',
            'orig_num_fields' => '1',
            'selected' => ['price'],
            'field_orig' => ['price'],
            'field_type_orig' => ['INT'],
            'field_length_orig' => [''],
            'field_default_value_orig' => [''],
            'field_default_type_orig' => ['NONE'],
            'field_collation_orig' => [''],
            'field_attribute_orig' => [''],
            'field_null_orig' => ['YES'],
            'field_extra_orig' => [''],
            'field_comments_orig' => [''],
            'field_virtuality_orig' => [''],
            'field_expression_orig' => [''],
            'field_name' => ['price'],
            'field_type' => ['INT'],
            'field_length' => [''],
            'field_default_type' => ['NONE'],
            'field_default_value' => [''],
            'field_collation' => [''],
            'field_attribute' => [''],
            'field_null' => ['YES'],
            'field_comments' => [''],
            'field_virtuality' => [''],
            'field_expression' => [''],
            'field_move_to' => [''],
            'do_save_data' => 'Save',
        ], $post);

        Current::$database = 'test_db';
        Current::$table = 'test_table';

        $dummyDbi = $this->createDbiDummy();
        $dummyDbi->addSelectDb('test_db');
        $dummyDbi->addResult($expectedQuery, true);
        $dbi = $this->createDatabaseInterface($dummyDbi);

        $request = self::createStub(ServerRequest::class);
        $structureController = self::createStub(StructureController::class);
        $structureController->method('__invoke')->willReturn(ResponseFactory::create()->createResponse());

        $config = new Config();
        $relation = new Relation($dbi, $config);
        (new SaveController(
            new ResponseRenderer(),
            $relation,
            new Transformations($dbi, $relation),
            $dbi,
            $structureController,
            new UserPrivilegesFactory($dbi),
            $config,
            new Template($config),
        ))($request);

        $dummyDbi->assertAllQueriesConsumed();
    }

    /** @return array<string, array{array<string, string[]>, string}> */
    public static function providerInvisibleAndCheck(): array
    {
        return [
            'add INVISIBLE and CHECK' => [
                [
                    'field_invisible_orig' => [''],
                    'field_check_orig' => [''],
                    'field_invisible' => ['1'],
                    'field_check' => ['`price` > 0'],
                ],
                'ALTER TABLE `test_table` CHANGE `price` `price` INT NULL INVISIBLE CHECK (`price` > 0);',
            ],
            'remove INVISIBLE and CHECK' => [
                [
                    'field_invisible_orig' => ['1'],
                    'field_check_orig' => ['`price` > 0'],
                    'field_check' => [''],
                ],
                'ALTER TABLE `test_table` CHANGE `price` `price` INT NULL;',
            ],
            'rename keeps the CHECK typed in the form' => [
                [
                    'field_name' => ['cost'],
                    'field_invisible_orig' => ['1'],
                    'field_check_orig' => ['`price` > 0'],
                    'field_invisible' => ['1'],
                    'field_check' => ['`cost` > 0'],
                ],
                'ALTER TABLE `test_table` CHANGE `price` `cost` INT NULL INVISIBLE CHECK (`cost` > 0);',
            ],
        ];
    }

    public function testAdjustColumnPrivileges(): void
    {
        Current::$database = 'db';
        Current::$table = 'table';

        $dbi = $this->createDatabaseInterface();

        $class = new ReflectionClass(SaveController::class);
        $method = $class->getMethod('adjustColumnPrivileges');

        $config = new Config();
        $relation = new Relation($dbi, $config);
        $ctrl = new SaveController(
            new ResponseRenderer(),
            $relation,
            new Transformations($dbi, $relation),
            $dbi,
            self::createStub(StructureController::class),
            new UserPrivilegesFactory($dbi),
            $config,
            new Template($config),
        );

        self::assertFalse(
            $method->invokeArgs($ctrl, [new UserPrivileges(), []]),
        );
    }
}
