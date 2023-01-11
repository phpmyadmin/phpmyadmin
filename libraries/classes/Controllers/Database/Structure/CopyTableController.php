<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Database\Structure;

use PhpMyAdmin\Controllers\AbstractController;
use PhpMyAdmin\Controllers\Database\StructureController;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Message;
use PhpMyAdmin\Operations;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Table;
use PhpMyAdmin\Template;

use function count;

final class CopyTableController extends AbstractController
{
    /** @var Operations */
    private $operations;

    /** @var StructureController */
    private $structureController;

    public function __construct(
        ResponseRenderer $response,
        Template $template,
        Operations $operations,
        StructureController $structureController
    ) {
        parent::__construct($response, $template);
        $this->operations = $operations;
        $this->structureController = $structureController;
    }

    public function __invoke(ServerRequest $request): void
    {
        $selected = $request->getParsedBodyParam('selected', []);
        $targetDb = $request->getParsedBodyParam('target_db');
        $selectedCount = count($selected);

        for ($i = 0; $i < $selectedCount; $i++) {
            Table::moveCopy(
                $GLOBALS['db'],
                $selected[$i],
                $targetDb,
                $selected[$i],
                $request->getParsedBodyParam('what'),
                false,
                'one_table',
                $request->getParsedBodyParam('drop_if_exists') === 'true'
            );

            if (! $request->hasBodyParam('adjust_privileges')) {
                continue;
            }

            $this->operations->adjustPrivilegesCopyTable($GLOBALS['db'], $selected[$i], $targetDb, $selected[$i]);
        }

        $GLOBALS['message'] = Message::success();

        ($this->structureController)($request);
    }
}
