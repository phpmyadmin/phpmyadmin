<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Table\Structure;

use PhpMyAdmin\Controllers\InvocableController;
use PhpMyAdmin\Controllers\Table\StructureController;
use PhpMyAdmin\Current;
use PhpMyAdmin\Database\CentralColumns;
use PhpMyAdmin\Http\Response;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Identifiers\DatabaseName;
use PhpMyAdmin\Message;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Routing\Route;
use Webmozart\Assert\Assert;

use function __;
use function is_array;

#[Route('/table/structure/central-columns-add', ['POST'])]
final class CentralColumnsAddController implements InvocableController
{
    public function __construct(
        private readonly ResponseRenderer $response,
        private readonly CentralColumns $centralColumns,
        private readonly StructureController $structureController,
    ) {
    }

    public function __invoke(ServerRequest $request): Response
    {
        $selected = $request->getParsedBodyParam('selected_fld', []);

        if (! is_array($selected) || $selected === []) {
            $this->response->setRequestStatus(false);
            $this->response->addJSON('message', __('No column selected.'));

            return $this->response->response();
        }

        Assert::allString($selected);

        $centralColsError = $this->centralColumns->syncUniqueColumns(
            DatabaseName::from($request->getParsedBodyParam('db')),
            $selected,
            false,
            $request->getParsedBodyParamAsString('table'),
        );

        if ($centralColsError instanceof Message) {
            Current::$message = $centralColsError;
        }

        if (Current::$message === null) {
            Current::$message = Message::success();
        }

        return ($this->structureController)($request);
    }
}
