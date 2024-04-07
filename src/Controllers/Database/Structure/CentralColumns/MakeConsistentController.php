<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Database\Structure\CentralColumns;

use PhpMyAdmin\Controllers\Database\StructureController;
use PhpMyAdmin\Controllers\InvocableController;
use PhpMyAdmin\Current;
use PhpMyAdmin\Database\CentralColumns;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Http\Response;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Message;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Template;
use Webmozart\Assert\Assert;

use function __;
use function is_array;

final class MakeConsistentController implements InvocableController
{
    public function __construct(
        private readonly ResponseRenderer $response,
        private readonly Template $template,
        private readonly DatabaseInterface $dbi,
        private readonly StructureController $structureController,
    ) {
    }

    public function __invoke(ServerRequest $request): Response|null
    {
        $GLOBALS['message'] ??= null;

        $selected = $request->getParsedBodyParam('selected_tbl', []);

        if (! is_array($selected) || $selected === []) {
            $this->response->setRequestStatus(false);
            $this->response->addJSON('message', __('No table selected.'));

            return null;
        }

        Assert::allString($selected);

        $centralColumns = new CentralColumns($this->dbi);
        $error = $centralColumns->makeConsistentWithList(Current::$database, $selected);

        $GLOBALS['message'] = $error instanceof Message ? $error : Message::success(__('Success!'));

        unset($_POST['submit_mult']);

        ($this->structureController)($request);

        return null;
    }
}
