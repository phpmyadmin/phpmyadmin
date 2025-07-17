<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Database\Structure\CentralColumns;

use PhpMyAdmin\Controllers\Database\StructureController;
use PhpMyAdmin\Controllers\InvocableController;
use PhpMyAdmin\Current;
use PhpMyAdmin\Database\CentralColumns;
use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\Http\Response;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Message;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Routing\Route;
use Webmozart\Assert\Assert;

use function __;
use function is_array;

#[Route('/database/structure/central-columns/remove', ['POST'])]
final class RemoveController implements InvocableController
{
    public function __construct(
        private readonly ResponseRenderer $response,
        private readonly DatabaseInterface $dbi,
        private readonly StructureController $structureController,
    ) {
    }

    public function __invoke(ServerRequest $request): Response
    {
        $selected = $request->getParsedBodyParam('selected_tbl', []);

        if (! is_array($selected) || $selected === []) {
            $this->response->setRequestStatus(false);
            $this->response->addJSON('message', __('No table selected.'));

            return $this->response->response();
        }

        Assert::allString($selected);

        $centralColumns = new CentralColumns($this->dbi);
        $error = $centralColumns->deleteColumnsFromList($_POST['db'], $selected);

        Current::$message = $error instanceof Message ? $error : Message::success(__('Success!'));

        unset($_POST['submit_mult']);

        return ($this->structureController)($request);
    }
}
