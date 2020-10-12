<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers\Server\Status;

use PhpMyAdmin\Advisor;
use PhpMyAdmin\Controllers\Server\Status\AdvisorController;
use PhpMyAdmin\Server\Status\Data;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\Response;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

class AdvisorControllerTest extends AbstractTestCase
{
    /** @var Response */
    private $response;

    /** @var Template */
    private $template;

    /** @var Data */
    private $data;

    protected function setUp(): void
    {
        parent::setUp();
        $GLOBALS['text_dir'] = 'ltr';
        parent::setGlobalConfig();
        $GLOBALS['PMA_Config']->enableBc();
        parent::setTheme();

        $GLOBALS['server'] = 1;
        $GLOBALS['PMA_PHP_SELF'] = 'index.php';
        $GLOBALS['cfg']['Server']['DisableIS'] = false;
        $GLOBALS['cfg']['Server']['host'] = 'localhost';

        $this->response = new Response();
        $this->template = new Template();
        $this->data = new Data();
    }

    public function testIndexWithoutData(): void
    {
        $this->data->dataLoaded = false;

        $controller = new AdvisorController(
            $this->response,
            $this->template,
            $this->data,
            new Advisor($GLOBALS['dbi'], new ExpressionLanguage())
        );

        $controller->index();

        $expected = $this->template->render('server/status/advisor/index', [
            'data' => [],
        ]);

        $this->assertSame(
            $expected,
            $this->response->getHTMLResult()
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

        $controller = new AdvisorController(
            $this->response,
            $this->template,
            $this->data,
            $advisor
        );

        $controller->index();

        $expected = $this->template->render('server/status/advisor/index', ['data' => $advisorData]);

        $this->assertSame(
            $expected,
            $this->response->getHTMLResult()
        );
    }
}
