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
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Table\Table;
use PhpMyAdmin\Template;
use PhpMyAdmin\UserPrivilegesFactory;

final class CopyTableController implements InvocableController
{
    public function __construct(
        private readonly ResponseRenderer $response,
        private readonly Template $template,
        private readonly Operations $operations,
        private readonly StructureController $structureController,
        private readonly UserPrivilegesFactory $userPrivilegesFactory,
    ) {
    }

    public function __invoke(ServerRequest $request): Response|null
    {
        /** @var string[] $selected */
        $selected = $request->getParsedBodyParam('selected', []);
        /** @var string $targetDb */
        $targetDb = $request->getParsedBodyParam('target_db');

        $userPrivileges = $this->userPrivilegesFactory->getPrivileges();

        foreach ($selected as $selectedValue) {
            Table::moveCopy(
                Current::$database,
                $selectedValue,
                $targetDb,
                $selectedValue,
                $request->getParsedBodyParam('what'),
                false,
                'one_table',
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

        $GLOBALS['message'] = Message::success();

        ($this->structureController)($request);

        return null;
    }
}
