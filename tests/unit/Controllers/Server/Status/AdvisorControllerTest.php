<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers\Server\Status;

use PhpMyAdmin\Advisory\Advisor;
use PhpMyAdmin\Config;
use PhpMyAdmin\Controllers\Server\Status\AdvisorController;
use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Server\Status\Data;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\ResponseRenderer;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

#[CoversClass(AdvisorController::class)]
class AdvisorControllerTest extends AbstractTestCase
{
    private ResponseRenderer $response;

    private Template $template;

    private Data $data;

    protected function setUp(): void
    {
        parent::setUp();

        DatabaseInterface::$instance = $this->createDatabaseInterface();

        $config = Config::getInstance();
        $config->selectedServer['DisableIS'] = false;
        $config->selectedServer['host'] = 'localhost';

        $this->response = new ResponseRenderer();
        $this->template = new Template();
        $this->data = new Data(DatabaseInterface::getInstance(), $config);
    }

    public function testIndexWithoutData(): void
    {
        $dummyDbi = $this->createDbiDummy();
        $dbi = $this->createDatabaseInterface($dummyDbi);
        $dummyDbi->addResult('SHOW GLOBAL STATUS', false);
        $data = new Data($dbi, Config::getInstance());

        $controller = new AdvisorController(
            $this->response,
            $this->template,
            $data,
            new Advisor(DatabaseInterface::getInstance(), new ExpressionLanguage()),
        );

        $controller(self::createStub(ServerRequest::class));

        $expected = $this->template->render('server/status/advisor/index', ['data' => []]);

        self::assertSame(
            $expected,
            $this->response->getHTMLResult(),
        );
    }

    public function testIndexWithData(): void
    {
        $advisorData = [
            'parse' => ['errors' => ['Error1', 'Error2']],
            'run' => [
                'errors' => ['Error1', 'Error2'],
                'fired' => [
                    [
                        'issue' => 'issue1',
                        'recommendation' => 'recommendation1',
                        'justification' => 'justification1',
                        'formula' => 'formula1',
                        'test' => 'test1',
                    ],
                    [
                        'issue' => 'issue2',
                        'recommendation' => 'recommendation2',
                        'justification' => 'justification2',
                        'formula' => 'formula2',
                        'test' => 'test2',
                    ],
                ],
            ],
        ];

        $advisor = self::createMock(Advisor::class);
        $advisor->method('run')->willReturn($advisorData);

        $controller = new AdvisorController($this->response, $this->template, $this->data, $advisor);

        $controller(self::createStub(ServerRequest::class));

        $expected = $this->template->render('server/status/advisor/index', ['data' => $advisorData]);

        self::assertSame(
            $expected,
            $this->response->getHTMLResult(),
        );
    }
}
