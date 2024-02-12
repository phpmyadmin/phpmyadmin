<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Database\Structure;

use PhpMyAdmin\Controllers\AbstractController;
use PhpMyAdmin\Controllers\Database\StructureController;
use PhpMyAdmin\Current;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Message;
use PhpMyAdmin\Operations;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Table\Table;
use PhpMyAdmin\Template;
use PhpMyAdmin\UserPrivilegesFactory;

final class CopyTableController extends AbstractController
{
    public function __construct(
        ResponseRenderer $response,
        Template $template,
        private Operations $operations,
        private StructureController $structureController,
        private readonly UserPrivilegesFactory $userPrivilegesFactory,
    ) {
        parent::__construct($response, $template);
    }

    public function __invoke(ServerRequest $request): void
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
    }
}
