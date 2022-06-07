<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Table\Structure;

use PhpMyAdmin\Controllers\AbstractController;
use PhpMyAdmin\Controllers\Table\StructureController;
use PhpMyAdmin\Database\CentralColumns;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Message;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Template;

use function __;

final class CentralColumnsRemoveController extends AbstractController
{
    /** @var CentralColumns */
    private $centralColumns;

    /** @var StructureController */
    private $structureController;

    public function __construct(
        ResponseRenderer $response,
        Template $template,
        CentralColumns $centralColumns,
        StructureController $structureController
    ) {
        parent::__construct($response, $template);
        $this->centralColumns = $centralColumns;
        $this->structureController = $structureController;
    }

    public function __invoke(ServerRequest $request): void
    {
        $GLOBALS['message'] = $GLOBALS['message'] ?? null;

        $selected = $_POST['selected_fld'] ?? [];

        if (empty($selected)) {
            $this->response->setRequestStatus(false);
            $this->response->addJSON('message', __('No column selected.'));

            return;
        }

        $centralColsError = $this->centralColumns->deleteColumnsFromList($GLOBALS['db'], $selected, false);

        if ($centralColsError instanceof Message) {
            $GLOBALS['message'] = $centralColsError;
        }

        if (empty($GLOBALS['message'])) {
            $GLOBALS['message'] = Message::success();
        }

        ($this->structureController)($request);
    }
}
