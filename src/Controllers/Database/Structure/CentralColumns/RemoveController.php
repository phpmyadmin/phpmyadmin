<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Database\Structure\CentralColumns;

use PhpMyAdmin\Controllers\AbstractController;
use PhpMyAdmin\Controllers\Database\StructureController;
use PhpMyAdmin\Database\CentralColumns;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Message;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Template;
use Webmozart\Assert\Assert;

use function __;
use function is_array;

final class RemoveController extends AbstractController
{
    public function __construct(
        ResponseRenderer $response,
        Template $template,
        private DatabaseInterface $dbi,
        private StructureController $structureController,
    ) {
        parent::__construct($response, $template);
    }

    public function __invoke(ServerRequest $request): void
    {
        $GLOBALS['message'] ??= null;

        $selected = $request->getParsedBodyParam('selected_tbl', []);

        if (! is_array($selected) || $selected === []) {
            $this->response->setRequestStatus(false);
            $this->response->addJSON('message', __('No table selected.'));

            return;
        }

        Assert::allString($selected);

        $centralColumns = new CentralColumns($this->dbi);
        $error = $centralColumns->deleteColumnsFromList($_POST['db'], $selected);

        $GLOBALS['message'] = $error instanceof Message ? $error : Message::success(__('Success!'));

        unset($_POST['submit_mult']);

        ($this->structureController)($request);
    }
}
