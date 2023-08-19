<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers\Server\Status;

use PhpMyAdmin\Advisory\Advisor;
use PhpMyAdmin\Config;
use PhpMyAdmin\Controllers\Server\Status\AdvisorController;
use PhpMyAdmin\DatabaseInterface;
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

        $GLOBALS['text_dir'] = 'ltr';

        parent::setGlobalConfig();

        parent::setTheme();

        DatabaseInterface::$instance = $this->createDatabaseInterface();

        $GLOBALS['server'] = 1;
        $config = Config::getInstance();
        $config->selectedServer['DisableIS'] = false;
        $config->selectedServer['host'] = 'localhost';

        $this->response = new ResponseRenderer();
        $this->template = new Template();
        $this->data = new Data(DatabaseInterface::getInstance(), $config);
    }

    public function testIndexWithoutData(): void
    {
        $this->data->dataLoaded = false;

        $controller = new AdvisorController(
            $this->response,
            $this->template,
            $this->data,
            new Advisor(DatabaseInterface::getInstance(), new ExpressionLanguage()),
        );

        $controller($this->createStub(ServerRequest::class));

        $expected = $this->template->render('server/status/advisor/index', ['data' => []]);

        $this->assertSame(
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

        $advisor = $this->createMock(Advisor::class);
        $advisor->method('run')->willReturn($advisorData);

        $this->data->dataLoaded = true;

        $controller = new AdvisorController($this->response, $this->template, $this->data, $advisor);

        $controller($this->createStub(ServerRequest::class));

        $expected = $this->template->render('server/status/advisor/index', ['data' => $advisorData]);

        $this->assertSame(
            $expected,
            $this->response->getHTMLResult(),
        );
    }
}
