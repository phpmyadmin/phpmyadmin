<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Normalization;

use PhpMyAdmin\Controllers\AbstractController;
use PhpMyAdmin\Current;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Normalization;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Template;
use PhpMyAdmin\Url;
use PhpMyAdmin\UserPrivilegesFactory;

use function min;

final class CreateNewColumnController extends AbstractController
{
    public function __construct(
        ResponseRenderer $response,
        Template $template,
        private Normalization $normalization,
        private readonly UserPrivilegesFactory $userPrivilegesFactory,
    ) {
        parent::__construct($response, $template);
    }

    public function __invoke(ServerRequest $request): void
    {
        $userPrivileges = $this->userPrivilegesFactory->getPrivileges();

        $numFields = min(4096, (int) $request->getParsedBodyParam('numFields'));
        $html = $this->normalization->getHtmlForCreateNewColumn(
            $userPrivileges,
            $numFields,
            Current::$database,
            Current::$table,
        );
        $html .= Url::getHiddenInputs(Current::$database, Current::$table);
        $this->response->addHTML($html);
    }
}
