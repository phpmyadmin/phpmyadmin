<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Table\Structure;

use PhpMyAdmin\Controllers\AbstractController;
use PhpMyAdmin\Controllers\Table\StructureController;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Dbal\DatabaseName;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Message;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Template;
use PhpMyAdmin\Util;

use function __;
use function array_map;
use function implode;
use function is_array;

abstract class AbstractIndexController extends AbstractController
{
    /** @var DatabaseInterface */
    private $dbi;

    /** @var StructureController */
    private $structureController;

    public function __construct(
        ResponseRenderer $response,
        Template $template,
        DatabaseInterface $dbi,
        StructureController $structureController
    ) {
        parent::__construct($response, $template);
        $this->dbi = $dbi;
        $this->structureController = $structureController;
    }

    public function handleIndexCreation(ServerRequest $request, string $indexType): void
    {
        $GLOBALS['message'] = $GLOBALS['message'] ?? null;

        $selected = $request->getParsedBodyParam('selected_fld', []);

        if (! is_array($selected) || $selected === []) {
            $this->response->setRequestStatus(false);
            $this->response->addJSON('message', __('No column selected.'));

            return;
        }

        $GLOBALS['sql_query'] = $this->getAddIndexSql($indexType, $GLOBALS['table'], $selected);

        $GLOBALS['message'] = $this->executeAddIndexSql($GLOBALS['db'], $GLOBALS['sql_query']);

        ($this->structureController)($request);
    }

    /**
     * @param string[] $selectedColumns
     */
    private function getAddIndexSql(string $indexType, string $table, array $selectedColumns): string
    {
        $columnsSql = implode(', ', array_map([Util::class, 'backquote'], $selectedColumns));

        return 'ALTER TABLE ' . Util::backquote($table) . ' ADD ' . $indexType . '(' . $columnsSql . ');';
    }

    /**
     * @param string|DatabaseName $db
     */
    private function executeAddIndexSql($db, string $sql): Message
    {
        $this->dbi->selectDb($db);
        $result = $this->dbi->tryQuery($sql);

        if (! $result) {
            return Message::error($this->dbi->getError());
        }

        return Message::success();
    }
}
