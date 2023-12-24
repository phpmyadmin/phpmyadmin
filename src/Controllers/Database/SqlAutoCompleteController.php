<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Database;

use PhpMyAdmin\Config;
use PhpMyAdmin\Controllers\AbstractController;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Identifiers\DatabaseName;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Template;

use function sprintf;

/**
 * Table/Column autocomplete in SQL editors.
 */
class SqlAutoCompleteController extends AbstractController
{
    public function __construct(
        ResponseRenderer $response,
        Template $template,
        private readonly DatabaseInterface $dbi,
        private readonly Config $config,
    ) {
        parent::__construct($response, $template);
    }

    public function __invoke(ServerRequest $request): void
    {
        $sqlAutocomplete = [];
        if ($this->config->settings['EnableAutocompleteForTablesAndColumns']) {
            $db = DatabaseName::tryFrom($request->getParam('db'));
            if ($db !== null) {
                $sqlAutocomplete = $this->getColumnList($db);
            }
        }

        $this->response->addJSON(['tables' => $sqlAutocomplete]);
    }

    /** @return string[][][] */
    private function getColumnList(DatabaseName $db): array
    {
        $columns = $this->dbi->query(sprintf(
            'SELECT TABLE_NAME, COLUMN_NAME, COLUMN_TYPE, COLUMN_KEY ' .
                'FROM information_schema.columns WHERE table_schema = %s',
            $this->dbi->quoteString($db->getName()),
        ));

        $autocompleteList = [];
        /** @var array{TABLE_NAME:string, COLUMN_NAME:string, COLUMN_TYPE:string, COLUMN_KEY:string} $columnInfo */
        foreach ($columns as $columnInfo) {
            $autocompleteList[$columnInfo['TABLE_NAME']][] = [
                'field' => $columnInfo['COLUMN_NAME'],
                'columnHint' => $columnInfo['COLUMN_TYPE'] . match ($columnInfo['COLUMN_KEY']) {
                    'PRI' => ' | Primary',
                    'UNI' => ' | Unique',
                    default=> ''
                },
            ];
        }

        return $autocompleteList;
    }
}
