<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Normalization;

use PhpMyAdmin\Controllers\InvocableController;
use PhpMyAdmin\Current;
use PhpMyAdmin\Http\Response;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Normalization;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Routing\Route;
use PhpMyAdmin\Url;
use PhpMyAdmin\UserPrivilegesFactory;

use function min;

#[Route('/normalization/create-new-column', ['POST'])]
final class CreateNewColumnController implements InvocableController
{
    public function __construct(
        private readonly ResponseRenderer $response,
        private readonly Normalization $normalization,
        private readonly UserPrivilegesFactory $userPrivilegesFactory,
    ) {
    }

    public function __invoke(ServerRequest $request): Response
    {
        $userPrivileges = $this->userPrivilegesFactory->getPrivileges();

        $numFields = min(4096, (int) $request->getParsedBodyParamAsStringOrNull('numFields'));
        $html = $this->normalization->getHtmlForCreateNewColumn(
            $userPrivileges,
            $numFields,
            Current::$database,
            Current::$table,
        );
        $html .= Url::getHiddenInputs(Current::$database, Current::$table);
        $this->response->addHTML($html);

        return $this->response->response();
    }
}
