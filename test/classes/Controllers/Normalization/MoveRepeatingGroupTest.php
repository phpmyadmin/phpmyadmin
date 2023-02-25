<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers\Normalization;

use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\Controllers\Normalization\MoveRepeatingGroup;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Message;
use PhpMyAdmin\Normalization;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\ResponseRenderer;
use PhpMyAdmin\Transformations;

/** @covers \PhpMyAdmin\Controllers\Normalization\MoveRepeatingGroup */
class MoveRepeatingGroupTest extends AbstractTestCase
{
    public function testDefault(): void
    {
        $GLOBALS['db'] = 'test_db';
        $GLOBALS['table'] = 'test_table';

        // phpcs:disable Generic.Files.LineLength.TooLong
        $dbiDummy = $this->createDbiDummy();
        $dbiDummy->addSelectDb('test_db');
        $dbiDummy->addResult('CREATE TABLE `new_table` SELECT `id`,`col1`,`col1` as `new_column` FROM `test_table` UNION SELECT `id`,`col1`,`col2` as `new_column` FROM `test_table`', []);
        $dbiDummy->addResult('ALTER TABLE `test_table` DROP `col1`, DROP `col2`', []);
        // phpcs:enable

        $dbi = $this->createDatabaseInterface($dbiDummy);
        $GLOBALS['dbi'] = $dbi;
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
