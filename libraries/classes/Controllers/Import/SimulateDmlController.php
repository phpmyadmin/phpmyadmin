<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Import;

use PhpMyAdmin\Controllers\AbstractController;
use PhpMyAdmin\Import\SimulateDml;
use PhpMyAdmin\Message;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\SqlParser\Lexer;
use PhpMyAdmin\SqlParser\Parser;
use PhpMyAdmin\SqlParser\Statements\DeleteStatement;
use PhpMyAdmin\SqlParser\Statements\UpdateStatement;
use PhpMyAdmin\SqlParser\Utils\Query;
use PhpMyAdmin\Template;

use function __;
use function count;

final class SimulateDmlController extends AbstractController
{
    /** @var SimulateDml */
    private $simulateDml;

    public function __construct(
        ResponseRenderer $response,
        Template $template,
        SimulateDml $simulateDml
    ) {
        parent::__construct($response, $template);
        $this->simulateDml = $simulateDml;
    }

    public function __invoke(): void
    {
        $error = '';
        /** @var string $sqlDelimiter */
        $sqlDelimiter = $_POST['sql_delimiter'];
        $sqlData = [];
        $lexer = new Lexer($GLOBALS['sql_query'], false, $sqlDelimiter);
        $parser = new Parser($lexer->list);

        foreach ($parser->statements as $statement) {
            if (
                ! $statement instanceof UpdateStatement && ! $statement instanceof DeleteStatement
                || ! empty($statement->join)
                || count(Query::getTables($statement)) > 1
            ) {
                $error = __('Only single-table UPDATE and DELETE queries can be simulated.');
                break;
            }

            // Get the matched rows for the query.
            $result = $this->simulateDml->getMatchedRows($parser, $statement);
            $error = $this->simulateDml->getError();

            if ($error !== '') {
                break;
            }

            $sqlData[] = $result;
        }

        if ($error) {
            $message = Message::rawError($error);
            $this->response->addJSON('message', $message);
            $this->response->addJSON('sql_data', false);

            return;
        }

        $this->response->addJSON('sql_data', $sqlData);
    }
}
