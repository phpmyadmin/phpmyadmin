<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers\Import;

use Fig\Http\Message\StatusCodeInterface;
use PhpMyAdmin\Config;
use PhpMyAdmin\Controllers\Import\StatusController;
use PhpMyAdmin\Http\Factory\ServerRequestFactory;
use PhpMyAdmin\Import\Ajax;
use PhpMyAdmin\Message;
use PhpMyAdmin\Plugins\Import\Upload\UploadNoplugin;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tests\AbstractTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use ReflectionProperty;

use function ini_get;
use function ini_set;

#[CoversClass(StatusController::class)]
final class StatusControllerTest extends AbstractTestCase
{
    public function testUploadStatus(): void
    {
        $_SESSION[Ajax::SESSION_KEY] = [
            'handler' => UploadNoplugin::class,
            'abc1234567890' => [
                'id' => 'abc1234567890',
                'finished' => false,
                'percent' => 0,
                'total' => 0,
                'complete' => 0,
                'plugin' => 'noplugin',
            ],
        ];

        $request = ServerRequestFactory::create()->createServerRequest('GET', 'https://example.com/')->withQueryParams([
            'import_status' => '1',
            'id' => 'abc1234567890',
        ]);

        $controller = new StatusController(new Template(new Config()));
        $response = $controller($request);

        self::assertSame(
            '{"id":"abc1234567890","finished":false,"percent":0,"total":0,"complete":0,"plugin":"noplugin"}',
            self::getActualOutputForAssertion(),
        );
        self::assertSame(StatusCodeInterface::STATUS_OK, $response->getStatusCode());
        self::assertSame('', (string) $response->getBody());
    }

    public function testMessage(): void
    {
        (new ReflectionProperty(StatusController::class, 'sleepMicroseconds'))->setValue(null, 1);

        $message = Message::success('Import has been successfully finished, 2 queries executed. (file.sql)');
        $_SESSION['Import_message'] = [];
        $_SESSION['Import_message']['message'] = $message->getDisplay();
        $_SESSION['Import_message']['go_back_url'] = 'https://example.com/index.php?route=/server/import';

        $request = ServerRequestFactory::create()->createServerRequest('GET', 'https://example.com/')->withQueryParams([
            'import_status' => '1',
            'message' => '1',
        ]);

        $controller = new StatusController(new Template(new Config()));
        $response = $controller($request);

        $expected = $message->getDisplay();
        $expected .= <<<'HTML'
            <div class="card"><div class="card-body">
              [ <a href="https://example.com/index.php?route=/server/import">Back</a> ]
            </div></div>

            HTML;
        self::assertSame($expected, self::getActualOutputForAssertion());
        self::assertSame(StatusCodeInterface::STATUS_OK, $response->getStatusCode());
        self::assertSame('', (string) $response->getBody());
    }

    public function testMessageProgressTimeout(): void
    {
        (new ReflectionProperty(StatusController::class, 'sleepMicroseconds'))->setValue(null, 1);
        (new ReflectionProperty(StatusController::class, 'sleepMicrosecondsRetry'))->setValue(null, 1);

        $maxExecutionTime = ini_get('max_execution_time');
        ini_set('max_execution_time', '0');

        $message = Message::error('Could not load the progress of the import.');
        $_SESSION['Import_message'] = [];

        $request = ServerRequestFactory::create()->createServerRequest('GET', 'https://example.com/')->withQueryParams([
            'import_status' => '1',
            'message' => '1',
        ]);

        $controller = new StatusController(new Template(new Config()));
        $response = $controller($request);

        self::assertSame($message->getDisplay(), self::getActualOutputForAssertion());
        self::assertSame(StatusCodeInterface::STATUS_OK, $response->getStatusCode());
        self::assertSame('', (string) $response->getBody());

        ini_set('max_execution_time', $maxExecutionTime);
    }
}
