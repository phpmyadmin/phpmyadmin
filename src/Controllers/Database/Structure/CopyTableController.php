<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Database\Structure;

use PhpMyAdmin\Controllers\Database\StructureController;
use PhpMyAdmin\Controllers\InvocableController;
use PhpMyAdmin\Current;
use PhpMyAdmin\Http\Response;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Message;
use PhpMyAdmin\Operations;
use PhpMyAdmin\Routing\Route;
use PhpMyAdmin\Table\MoveMode;
use PhpMyAdmin\Table\MoveScope;
use PhpMyAdmin\Table\TableMover;
use PhpMyAdmin\UserPrivilegesFactory;

#[Route('/database/structure/copy-table', ['POST'])]
final class CopyTableController implements InvocableController
{
    public function __construct(
        private readonly Operations $operations,
        private readonly StructureController $structureController,
        private readonly UserPrivilegesFactory $userPrivilegesFactory,
        private readonly TableMover $tableMover,
    ) {
    }

    public function __invoke(ServerRequest $request): Response
    {
        /** @var string[] $selected */
        $selected = $request->getParsedBodyParam('selected', []);
        $targetDb = $request->getParsedBodyParamAsString('target_db');

        $userPrivileges = $this->userPrivilegesFactory->getPrivileges();

        foreach ($selected as $selectedValue) {
            $this->tableMover->moveCopy(
                Current::$database,
                $selectedValue,
                $targetDb,
                $selectedValue,
                MoveScope::from($request->getParsedBodyParamAsString('what')),
                MoveMode::SingleTable,
                $request->getParsedBodyParam('drop_if_exists') === 'true',
            );

            if (! $request->hasBodyParam('adjust_privileges')) {
                continue;
            }

            $this->operations->adjustPrivilegesCopyTable(
                $userPrivileges,
                Current::$database,
                $selectedValue,
                $targetDb,
                $selectedValue,
            );
        }

        Current::$message = Message::success();

        return ($this->structureController)($request);
    }
}
