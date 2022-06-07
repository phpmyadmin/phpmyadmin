<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers\Table\Structure;

use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\Controllers\Table\Structure\SaveController;
use PhpMyAdmin\Controllers\Table\StructureController;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\ResponseRenderer;
use PhpMyAdmin\Transformations;
use ReflectionClass;

/**
 * @covers \PhpMyAdmin\Controllers\Table\Structure\SaveController
 */
class SaveControllerTest extends AbstractTestCase
{
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

        $GLOBALS['db'] = 'test_db';
        $GLOBALS['table'] = 'test_table';

        $dummyDbi = $this->createDbiDummy();
        $dummyDbi->addSelectDb('test_db');
        $dummyDbi->addResult(
            'ALTER TABLE `test_table` CHANGE `name` `new_name` VARCHAR(21)'
            . ' CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL;',
            []
        );
        $dbi = $this->createDatabaseInterface($dummyDbi);

        $request = $this->createStub(ServerRequest::class);

        $mock = $this->createMock(StructureController::class);
        $mock->expects($this->once())->method('__invoke')->with($request);

        (new SaveController(
            new ResponseRenderer(),
            new Template(),
            new Relation($dbi),
            new Transformations(),
            $dbi,
            $mock
        ))($request);

        $this->assertArrayNotHasKey('selected', $_POST);
    }

    public function testAdjustColumnPrivileges(): void
    {
        $GLOBALS['server'] = 1;
        $GLOBALS['text_dir'] = 'ltr';
        $GLOBALS['PMA_PHP_SELF'] = 'index.php';
        $GLOBALS['db'] = 'db';
        $GLOBALS['table'] = 'table';

        $dbi = $this->createDatabaseInterface();

        $class = new ReflectionClass(SaveController::class);
        $method = $class->getMethod('adjustColumnPrivileges');
        $method->setAccessible(true);

        $ctrl = new SaveController(
            new ResponseRenderer(),
            new Template(),
            new Relation($dbi),
            new Transformations(),
            $dbi,
            $this->createStub(StructureController::class)
        );

        $this->assertFalse(
            $method->invokeArgs($ctrl, [[]])
        );
    }
}
