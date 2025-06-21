<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers\Normalization;

use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\Controllers\Normalization\MoveRepeatingGroup;
use PhpMyAdmin\Current;
use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\Http\Factory\ServerRequestFactory;
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
        Current::$database = 'test_db';
        Current::$table = 'test_table';

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
        $request = ServerRequestFactory::create()->createServerRequest('POST', 'https://example.com/')
            ->withParsedBody([
                'repeatingColumns' => 'col1, col2',
                'newTable' => 'new_table',
                'newColumn' => 'new_column',
                'primary_columns' => 'id,col1',
            ]);

        $relation = new Relation($dbi);
        $controller = new MoveRepeatingGroup(
            $response,
            new Normalization($dbi, $relation, new Transformations($dbi, $relation), $template),
        );
        $controller($request);

        $message = Message::success('Selected repeating group has been moved to the table \'test_table\'');
        self::assertSame(['queryError' => false, 'message' => $message->getDisplay()], $response->getJSONResult());
    }
}
