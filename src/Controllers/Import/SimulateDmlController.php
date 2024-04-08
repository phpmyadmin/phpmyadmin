<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Import;

use PhpMyAdmin\Controllers\InvocableController;
use PhpMyAdmin\Http\Response;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Import\SimulateDml;
use PhpMyAdmin\Message;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\SqlParser\Parser;
use PhpMyAdmin\SqlParser\Statements\DeleteStatement;
use PhpMyAdmin\SqlParser\Statements\UpdateStatement;
use PhpMyAdmin\SqlParser\Utils\Query;

use function __;
use function count;
use function explode;

final class SimulateDmlController implements InvocableController
{
    public function __construct(private readonly ResponseRenderer $response, private readonly SimulateDml $simulateDml)
    {
    }

    public function __invoke(ServerRequest $request): Response|null
    {
        $error = '';
        $errorMsg = __('Only single-table UPDATE and DELETE queries can be simulated.');
        /** @var string $sqlDelimiter */
        $sqlDelimiter = $request->getParsedBodyParam('sql_delimiter', '');
        $sqlData = [];
        $queries = explode($sqlDelimiter, $GLOBALS['sql_query']);
        foreach ($queries as $sqlQuery) {
            if ($sqlQuery === '') {
                continue;
            }

            // Parsing the query.
            $parser = new Parser($sqlQuery);

            if (empty($parser->statements[0])) {
                continue;
            }

            $statement = $parser->statements[0];

            if (
                ! ($statement instanceof UpdateStatement || $statement instanceof DeleteStatement)
                || ! empty($statement->join)
            ) {
                $error = $errorMsg;
                break;
            }

            $tables = Query::getTables($statement);
            if (count($tables) > 1) {
                $error = $errorMsg;
                break;
            }

            // Get the matched rows for the query.
            $result = $this->simulateDml->getMatchedRows($sqlQuery, $parser, $statement);
            $error = $this->simulateDml->getError();

            if ($error !== '') {
                break;
            }

            $sqlData[] = $result;
        }

        if ($error !== '') {
            $message = Message::rawError($error);
            $this->response->addJSON('message', $message);
            $this->response->addJSON('sql_data', false);

            return null;
        }

        $this->response->addJSON('sql_data', $sqlData);

        return null;
    }
}
