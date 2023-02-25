<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Database\Structure;

use PhpMyAdmin\Controllers\AbstractController;
use PhpMyAdmin\Controllers\Database\StructureController;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Message;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Table;
use PhpMyAdmin\Template;

use function count;
use function mb_strlen;
use function mb_substr;

final class CopyTableWithPrefixController extends AbstractController
{
    public function __construct(
        ResponseRenderer $response,
        Template $template,
        private StructureController $structureController,
    ) {
        parent::__construct($response, $template);
    }

    public function __invoke(ServerRequest $request): void
    {
        $selected = $request->getParsedBodyParam('selected', []);
        $fromPrefix = $request->getParsedBodyParam('from_prefix');
        $toPrefix = $request->getParsedBodyParam('to_prefix');

        $selectedCount = count($selected);
        $dropIfExists = $request->getParsedBodyParam('drop_if_exists') === 'true';

        for ($i = 0; $i < $selectedCount; $i++) {
            $current = $selected[$i];
            $newTableName = $toPrefix . mb_substr($current, mb_strlen((string) $fromPrefix));

            Table::moveCopy(
                $GLOBALS['db'],
                $current,
                $GLOBALS['db'],
                $newTableName,
                'data',
                false,
                'one_table',
                $dropIfExists,
            );
        }

        $GLOBALS['message'] = Message::success();

        ($this->structureController)($request);
    }
}
