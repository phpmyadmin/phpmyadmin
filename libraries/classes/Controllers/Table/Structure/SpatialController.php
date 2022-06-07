<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Table\Structure;

use PhpMyAdmin\Controllers\AbstractController;
use PhpMyAdmin\Controllers\Table\StructureController;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Message;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Template;
use PhpMyAdmin\Util;

use function __;
use function count;

final class SpatialController extends AbstractController
{
    /** @var DatabaseInterface */
    private $dbi;

    /** @var StructureController */
    private $structureController;

    public function __construct(
        ResponseRenderer $response,
        Template $template,
        DatabaseInterface $dbi,
        StructureController $structureController
    ) {
        parent::__construct($response, $template);
        $this->dbi = $dbi;
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

        $i = 1;
        $selectedCount = count($selected);
        $GLOBALS['sql_query'] = 'ALTER TABLE ' . Util::backquote($GLOBALS['table']) . ' ADD SPATIAL(';

        foreach ($selected as $field) {
            $GLOBALS['sql_query'] .= Util::backquote($field);
            $GLOBALS['sql_query'] .= $i++ === $selectedCount ? ');' : ', ';
        }

        $this->dbi->selectDb($GLOBALS['db']);
        $result = $this->dbi->tryQuery($GLOBALS['sql_query']);

        if (! $result) {
            $GLOBALS['message'] = Message::error($this->dbi->getError());
        }

        if (empty($GLOBALS['message'])) {
            $GLOBALS['message'] = Message::success();
        }

        ($this->structureController)($request);
    }
}
