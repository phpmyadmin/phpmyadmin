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

use function mb_strlen;
use function mb_substr;

final class ReplacePrefixController implements InvocableController
{
    public function __construct(
        private readonly DatabaseInterface $dbi,
        private readonly StructureController $structureController,
    ) {
    }

    public function __invoke(ServerRequest $request): Response|null
    {
        /** @var string[] $selected */
        $selected = $request->getParsedBodyParam('selected', []);
        $fromPrefix = $request->getParsedBodyParam('from_prefix', '');
        $toPrefix = $request->getParsedBodyParam('to_prefix', '');

        $GLOBALS['sql_query'] = '';

        $this->dbi->selectDb(Current::$database);

        foreach ($selected as $selectedValue) {
            $subFromPrefix = mb_substr($selectedValue, 0, mb_strlen((string) $fromPrefix));

            if ($subFromPrefix === $fromPrefix) {
                $newTableName = $toPrefix . mb_substr($selectedValue, mb_strlen($fromPrefix));
            } else {
                $newTableName = $selectedValue;
            }

            $aQuery = 'ALTER TABLE ' . Util::backquote($selectedValue)
                . ' RENAME ' . Util::backquote($newTableName);

            $GLOBALS['sql_query'] .= $aQuery . ';' . "\n";
            $this->dbi->query($aQuery);
        }

        $GLOBALS['message'] = Message::success();

        ($this->structureController)($request);

        return null;
    }
}
