<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Table\Structure;

use PhpMyAdmin\Controllers\AbstractController;
use PhpMyAdmin\Controllers\Table\StructureController;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\DbTableExists;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Message;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Template;
use PhpMyAdmin\Url;
use PhpMyAdmin\Util;

use function __;
use function count;
use function is_array;

final class PrimaryController extends AbstractController
{
    public function __construct(
        ResponseRenderer $response,
        Template $template,
        private DatabaseInterface $dbi,
        private StructureController $structureController,
    ) {
        parent::__construct($response, $template);
    }

    public function __invoke(ServerRequest $request): void
    {
        $GLOBALS['message'] ??= null;
        $GLOBALS['urlParams'] ??= null;
        $GLOBALS['errorUrl'] ??= null;

        /** @var string[]|null $selected */
        $selected = $request->getParsedBodyParam('selected_fld', $request->getParsedBodyParam('selected'));

        if (! is_array($selected) || $selected === []) {
            $this->response->setRequestStatus(false);
            $this->response->addJSON('message', __('No column selected.'));

            return;
        }

        $this->dbi->selectDb($GLOBALS['db']);
        $hasPrimary = $this->hasPrimaryKey();

        /** @var string|null $deletionConfirmed */
        $deletionConfirmed = $request->getParsedBodyParam('mult_btn', null);

        if ($hasPrimary && $deletionConfirmed === null) {
            $this->checkParameters(['db', 'table']);

            $GLOBALS['urlParams'] = ['db' => $GLOBALS['db'], 'table' => $GLOBALS['table']];
            $GLOBALS['errorUrl'] = Util::getScriptNameForOption($GLOBALS['cfg']['DefaultTabTable'], 'table');
            $GLOBALS['errorUrl'] .= Url::getCommon($GLOBALS['urlParams'], '&');

            DbTableExists::check($GLOBALS['db'], $GLOBALS['table']);

            $this->render('table/structure/primary', [
                'db' => $GLOBALS['db'],
                'table' => $GLOBALS['table'],
                'selected' => $selected,
            ]);

            return;
        }

        if ($deletionConfirmed === __('Yes') || ! $hasPrimary) {
            $GLOBALS['sql_query'] = 'ALTER TABLE ' . Util::backquote($GLOBALS['table']);
            if ($hasPrimary) {
                $GLOBALS['sql_query'] .= ' DROP PRIMARY KEY,';
            }

            $GLOBALS['sql_query'] .= ' ADD PRIMARY KEY(';

            $i = 1;
            $selectedCount = count($selected);
            foreach ($selected as $field) {
                $GLOBALS['sql_query'] .= Util::backquote($field);
                $GLOBALS['sql_query'] .= $i++ === $selectedCount ? ');' : ', ';
            }

            $this->dbi->selectDb($GLOBALS['db']);
            $result = $this->dbi->tryQuery($GLOBALS['sql_query']);

            if (! $result) {
                $GLOBALS['message'] = Message::error($this->dbi->getError());
            }
        }

        if (empty($GLOBALS['message'])) {
            $GLOBALS['message'] = Message::success();
        }

        ($this->structureController)($request);
    }

    private function hasPrimaryKey(): bool
    {
        $result = $this->dbi->query('SHOW KEYS FROM ' . Util::backquote($GLOBALS['table']));

        foreach ($result as $row) {
            if ($row['Key_name'] === 'PRIMARY') {
                return true;
            }
        }

        return false;
    }
}
