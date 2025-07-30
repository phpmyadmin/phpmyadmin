<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Database\Structure;

use PhpMyAdmin\ConfigStorage\RelationCleanup;
use PhpMyAdmin\Controllers\Database\StructureController;
use PhpMyAdmin\Controllers\InvocableController;
use PhpMyAdmin\Current;
use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\Http\Response;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Message;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Routing\Route;
use PhpMyAdmin\Util;
use PhpMyAdmin\Utils\ForeignKey;

use function __;

#[Route('/database/structure/drop-table', ['POST'])]
final class DropTableController implements InvocableController
{
    public function __construct(
        private readonly DatabaseInterface $dbi,
        private readonly RelationCleanup $relationCleanup,
        private readonly StructureController $structureController,
    ) {
    }

    public function __invoke(ServerRequest $request): Response
    {
        if ($request->hasBodyParam('reload')) {
            $reload = $request->getParsedBodyParamAsString('reload');
            ResponseRenderer::$reload = $reload === '1' || $reload === 'true';
        }

        $multBtn = $_POST['mult_btn'] ?? '';
        /** @var string[] $selected */
        $selected = $_POST['selected'] ?? [];

        if ($multBtn !== __('Yes')) {
            Current::$message = Message::success(__('No change'));

            unset($_POST['mult_btn']);

            return ($this->structureController)($request);
        }

        $defaultFkCheckValue = ForeignKey::handleDisableCheckInit();
        Current::$sqlQuery = '';
        $sqlQueryViews = '';

        foreach ($selected as $selectedValue) {
            $this->relationCleanup->table(Current::$database, $selectedValue);

            if ($this->dbi->getTable(Current::$database, $selectedValue)->isView()) {
                $sqlQueryViews .= ($sqlQueryViews === '' ? 'DROP VIEW ' : ', ') . Util::backquote($selectedValue);
            } else {
                Current::$sqlQuery .= (Current::$sqlQuery === '' ? 'DROP TABLE ' : ', ')
                    . Util::backquote($selectedValue);
            }

            ResponseRenderer::$reload = true;
        }

        if (Current::$sqlQuery !== '') {
            Current::$sqlQuery .= ';';
        } elseif ($sqlQueryViews !== '') {
            Current::$sqlQuery = $sqlQueryViews . ';';
            $sqlQueryViews = '';
        }

        // Unset cache values for tables count, issue #14205
        if (isset($_SESSION['tmpval'])) {
            if (isset($_SESSION['tmpval']['table_limit_offset'])) {
                unset($_SESSION['tmpval']['table_limit_offset']);
            }

            if (isset($_SESSION['tmpval']['table_limit_offset_db'])) {
                unset($_SESSION['tmpval']['table_limit_offset_db']);
            }
        }

        Current::$message = Message::success();

        $this->dbi->selectDb(Current::$database);
        $result = $this->dbi->tryQuery(Current::$sqlQuery);

        if (! $result) {
            Current::$message = Message::error($this->dbi->getError());
        }

        if ($result && $sqlQueryViews !== '') {
            Current::$sqlQuery .= ' ' . $sqlQueryViews . ';';
            $result = $this->dbi->tryQuery($sqlQueryViews);
            unset($sqlQueryViews);
        }

        if (! $result) {
            Current::$message = Message::error($this->dbi->getError());
        }

        ForeignKey::handleDisableCheckCleanup($defaultFkCheckValue);

        unset($_POST['mult_btn']);

        return ($this->structureController)($request);
    }
}
