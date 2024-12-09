<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Database\Structure;

use PhpMyAdmin\Controllers\Database\StructureController;
use PhpMyAdmin\Controllers\InvocableController;
use PhpMyAdmin\Current;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Http\Response;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Message;
use PhpMyAdmin\Util;

final class AddPrefixTableController implements InvocableController
{
    public function __construct(
        private readonly DatabaseInterface $dbi,
        private readonly StructureController $structureController,
    ) {
    }

    public function __invoke(ServerRequest $request): Response
    {
        /** @var string[] $selected */
        $selected = $request->getParsedBodyParam('selected', []);

        Current::$sqlQuery = '';

        $this->dbi->selectDb(Current::$database);

        foreach ($selected as $selectedValue) {
            $newTableName = $request->getParsedBodyParamAsString('add_prefix', '') . $selectedValue;
            $aQuery = 'ALTER TABLE ' . Util::backquote($selectedValue) . ' RENAME ' . Util::backquote($newTableName);

            Current::$sqlQuery .= $aQuery . ';' . "\n";
            $this->dbi->query($aQuery);
        }

        $GLOBALS['message'] = Message::success();

        return ($this->structureController)($request);
    }
}
