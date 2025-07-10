<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Server\Status;

use PhpMyAdmin\Advisory\Advisor;
use PhpMyAdmin\Controllers\InvocableController;
use PhpMyAdmin\Http\Response;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Routing\Route;
use PhpMyAdmin\Server\Status\Data;
use PhpMyAdmin\Template;

/**
 * Displays the advisor feature
 */
#[Route('/server/status/advisor', ['GET'])]
final class AdvisorController extends AbstractController implements InvocableController
{
    public function __construct(
        ResponseRenderer $response,
        Template $template,
        Data $data,
        private readonly Advisor $advisor,
    ) {
        parent::__construct($response, $template, $data);
    }

    public function __invoke(ServerRequest $request): Response
    {
        $data = [];
        if ($this->data->dataLoaded) {
            $data = $this->advisor->run();
        }

        $this->response->render('server/status/advisor/index', ['data' => $data]);

        return $this->response->response();
    }
}
