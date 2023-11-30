<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers\Normalization;

use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\Controllers\Normalization\MoveRepeatingGroup;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Message;
use PhpMyAdmin\Normalization;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\ResponseRenderer;
use PhpMyAdmin\Transformations;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(MoveRepeatingGroup::class)]
class MoveRepeatingGroupTest extends AbstractTestCase
{
    public function testDefault(): void
    {
        $GLOBALS['db'] = 'test_db';
        $GLOBALS['table'] = 'test_table';

        $dbiDummy = $this->createDbiDummy();
        $dbiDummy->addSelectDb('test_db');
        $dbiDummy->addResult(
            'CREATE TABLE `new_table`'
            . ' SELECT `id`,`col1`,`col1` as `new_column` FROM `test_table` UNION'
            . ' SELECT `id`,`col1`,`col2` as `new_column` FROM `test_table`',
            true,
        );
        $dbiDummy->addResult('ALTER TABLE `test_table` DROP `col1`, DROP `col2`', true);

        $dbi = $this->createDatabaseInterface($dbiDummy);
        DatabaseInterface::$instance = $dbi;
        $response = new ResponseRenderer();
        $template = new Template();
        $request = $this->createStub(ServerRequest::class);
        $request->method('getParsedBodyParam')->willReturnMap([
            ['repeatingColumns', null, 'col1, col2'],
            ['newTable', null, 'new_table'],
            ['newColumn', null, 'new_column'],
            ['primary_columns', null, 'id,col1'],
        ]);

        $controller = new MoveRepeatingGroup(
            $response,
            $template,
            new Normalization($dbi, new Relation($dbi), new Transformations(), $template),
        );
        $controller($request);

        $message = Message::success('Selected repeating group has been moved to the table \'test_table\'');
        $this->assertSame(['queryError' => false, 'message' => $message->getDisplay()], $response->getJSONResult());
    }
}
