<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Database\Structure;

use PhpMyAdmin\Controllers\AbstractController;
use PhpMyAdmin\Controllers\Database\StructureController;
use PhpMyAdmin\Current;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Message;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Table\Table;
use PhpMyAdmin\Template;

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
    }
}
