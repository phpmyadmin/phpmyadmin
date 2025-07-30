<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Database\Structure;

use PhpMyAdmin\Controllers\Database\StructureController;
use PhpMyAdmin\Controllers\InvocableController;
use PhpMyAdmin\Current;
use PhpMyAdmin\Http\Response;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Message;
use PhpMyAdmin\Routing\Route;
use PhpMyAdmin\Table\MoveMode;
use PhpMyAdmin\Table\MoveScope;
use PhpMyAdmin\Table\TableMover;

use function mb_strlen;
use function mb_substr;

#[Route('/database/structure/copy-table-with-prefix', ['POST'])]
final class CopyTableWithPrefixController implements InvocableController
{
    public function __construct(
        private readonly StructureController $structureController,
        private readonly TableMover $tableMover,
    ) {
    }

    public function __invoke(ServerRequest $request): Response
    {
        /** @var string[] $selected */
        $selected = $request->getParsedBodyParam('selected', []);
        $fromPrefix = $request->getParsedBodyParamAsString('from_prefix', '');
        $toPrefix = $request->getParsedBodyParamAsString('to_prefix');

        $dropIfExists = $request->getParsedBodyParam('drop_if_exists') === 'true';

        foreach ($selected as $selectedValue) {
            $newTableName = $toPrefix . mb_substr($selectedValue, mb_strlen($fromPrefix));

            $this->tableMover->moveCopy(
                Current::$database,
                $selectedValue,
                Current::$database,
                $newTableName,
                MoveScope::StructureAndData,
                MoveMode::SingleTable,
                $dropIfExists,
            );
        }

        Current::$message = Message::success();

        return ($this->structureController)($request);
    }
}
