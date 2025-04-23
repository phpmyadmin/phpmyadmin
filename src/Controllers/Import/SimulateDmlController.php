<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Import;

use PhpMyAdmin\Controllers\InvocableController;
use PhpMyAdmin\Current;
use PhpMyAdmin\Http\Response;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Import\SimulateDml;
use PhpMyAdmin\Message;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\SqlParser\Lexer;
use PhpMyAdmin\SqlParser\Parser;
use PhpMyAdmin\SqlParser\Statements\DeleteStatement;
use PhpMyAdmin\SqlParser\Statements\UpdateStatement;
use PhpMyAdmin\SqlParser\TokensList;
use PhpMyAdmin\SqlParser\TokenType;
use PhpMyAdmin\SqlParser\Utils\Query;

use function __;
use function array_filter;
use function array_values;
use function count;

final class SimulateDmlController implements InvocableController
{
    private string $error = '';

    /**
     * @psalm-var list<array{
     *   sql_query: string,
     *   matched_rows: int,
     *   matched_rows_url: string,
     * }>
     */
    private array $data = [];

    public function __construct(private readonly ResponseRenderer $response, private readonly SimulateDml $simulateDml)
    {
    }

    public function __invoke(ServerRequest $request): Response
    {
        $sqlDelimiter = $request->getParsedBodyParamAsString('sql_delimiter', '');

        $parser = $this->createParser(Current::$sqlQuery, $sqlDelimiter);
        $this->process($parser);

        if ($this->error !== '') {
            $this->response->addJSON('message', Message::rawError($this->error));
            $this->response->addJSON('sql_data', false);

            return $this->response->response();
        }

        $this->response->addJSON('sql_data', $this->data);

        return $this->response->response();
    }

    private function createParser(string $query, string $delimiter): Parser
    {
        $lexer = new Lexer($query, false, $delimiter);
        $list = new TokensList(array_values(array_filter(
            $lexer->list->tokens,
            static function ($token): bool {
                return $token->type !== TokenType::Comment;
            },
        )));

        return new Parser($list);
    }

    private function process(Parser $parser): void
    {
        if ($parser->errors !== []) {
            $this->error = $parser->errors[0]->getMessage();

            return;
        }

        foreach ($parser->statements as $statement) {
            if (
                ! $statement instanceof UpdateStatement && ! $statement instanceof DeleteStatement
                || ! empty($statement->join)
                || count(Query::getTables($statement)) > 1
            ) {
                $this->error = __('Only single-table UPDATE and DELETE queries can be simulated.');
                break;
            }

            // Get the matched rows for the query.
            $result = $this->simulateDml->getMatchedRows($parser, $statement);
            $this->error = $this->simulateDml->getError();

            if ($this->error !== '') {
                break;
            }

            $this->data[] = $result;
        }
    }
}
