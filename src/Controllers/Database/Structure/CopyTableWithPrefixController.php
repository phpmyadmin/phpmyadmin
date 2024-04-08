<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Database\Structure;

use PhpMyAdmin\Controllers\Database\StructureController;
use PhpMyAdmin\Controllers\InvocableController;
use PhpMyAdmin\Current;
use PhpMyAdmin\Http\Response;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Message;
use PhpMyAdmin\Table\Table;

use function mb_strlen;
use function mb_substr;

final class CopyTableWithPrefixController implements InvocableController
{
    public function __construct(private readonly StructureController $structureController)
    {
    }

    public function __invoke(ServerRequest $request): Response|null
    {
        /** @var string[] $selected */
        $selected = $request->getParsedBodyParam('selected', []);
        $fromPrefix = $request->getParsedBodyParam('from_prefix');
        $toPrefix = $request->getParsedBodyParam('to_prefix');

        $dropIfExists = $request->getParsedBodyParam('drop_if_exists') === 'true';

        foreach ($selected as $selectedValue) {
            $newTableName = $toPrefix . mb_substr($selectedValue, mb_strlen((string) $fromPrefix));

            Table::moveCopy(
                Current::$database,
                $selectedValue,
                Current::$database,
                $newTableName,
                'data',
                false,
                'one_table',
                $dropIfExists,
            );
        }

        $GLOBALS['message'] = Message::success();

        ($this->structureController)($request);

        return null;
    }
}
