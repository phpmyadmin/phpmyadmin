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

final class PrimaryController extends AbstractController
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

    public function __invoke(ServerRequest $request): void
    {
        $GLOBALS['message'] = $GLOBALS['message'] ?? null;
        $GLOBALS['urlParams'] = $GLOBALS['urlParams'] ?? null;
        $GLOBALS['errorUrl'] = $GLOBALS['errorUrl'] ?? null;

        $selected = $_POST['selected'] ?? [];
        $selected_fld = $_POST['selected_fld'] ?? [];

        if (empty($selected) && empty($selected_fld)) {
            $this->response->setRequestStatus(false);
            $this->response->addJSON('message', __('No column selected.'));

            return;
        }

        $primary = $this->getKeyForTablePrimary();
        if (empty($primary) && ! empty($selected_fld)) {
            // no primary key, so we can safely create new
            $mult_btn = __('Yes');
            $selected = $selected_fld;
        }

        $mult_btn = $_POST['mult_btn'] ?? $mult_btn ?? '';

        if (! empty($selected_fld) && ! empty($primary)) {
            $this->checkParameters(['db', 'table']);

            $GLOBALS['urlParams'] = ['db' => $GLOBALS['db'], 'table' => $GLOBALS['table']];
            $GLOBALS['errorUrl'] = Util::getScriptNameForOption($GLOBALS['cfg']['DefaultTabTable'], 'table');
            $GLOBALS['errorUrl'] .= Url::getCommon($GLOBALS['urlParams'], '&');

            DbTableExists::check($GLOBALS['db'], $GLOBALS['table']);

            $this->render('table/structure/primary', [
                'db' => $GLOBALS['db'],
                'table' => $GLOBALS['table'],
                'selected' => $selected_fld,
            ]);

            return;
        }

        if ($mult_btn === __('Yes')) {
            $GLOBALS['sql_query'] = 'ALTER TABLE ' . Util::backquote($GLOBALS['table']);
            if (! empty($primary)) {
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

    /**
     * Gets table primary key
     *
     * @return string
     */
    private function getKeyForTablePrimary()
    {
        $this->dbi->selectDb($GLOBALS['db']);
        $result = $this->dbi->query(
            'SHOW KEYS FROM ' . Util::backquote($GLOBALS['table']) . ';'
        );
        $primary = '';
        foreach ($result as $row) {
            // Backups the list of primary keys
            if ($row['Key_name'] !== 'PRIMARY') {
                continue;
            }

            $primary .= $row['Column_name'] . ', ';
        }

        return $primary;
    }
}
