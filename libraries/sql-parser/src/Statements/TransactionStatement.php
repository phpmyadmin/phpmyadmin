<?php

/**
 * Transaction statement.
 */

namespace SqlParser\Statements;

use SqlParser\Parser;
use SqlParser\Statement;
use SqlParser\TokensList;
use SqlParser\Components\OptionsArray;

/**
 * Transaction statement.
 *
 * @category   Statements
 *
 * @license    https://www.gnu.org/licenses/gpl-2.0.txt GPL-2.0+
 */
class TransactionStatement extends Statement
{
    /**
     * START TRANSACTION and BEGIN.
     *
     * @var int
     */
    const TYPE_BEGIN = 1;

    /**
     * COMMIT and ROLLBACK.
     *
     * @var int
     */
    const TYPE_END = 2;

    /**
     * The type of this query.
     *
     * @var int
     */
    public $type;

    /**
     * The list of statements in this transaction.
     *
     * @var Statement[]
     */
    public $statements;

    /**
     * The ending transaction statement which may be a `COMMIT` or a `ROLLBACK`.
     *
     * @var TransactionStatement
     */
    public $end;

    /**
     * Options for this query.
     *
     * @var array
     */
    public static $OPTIONS = array(
        'START TRANSACTION' => 1,
        'BEGIN' => 1,
        'COMMIT' => 1,
        'ROLLBACK' => 1,
        'WITH CONSISTENT SNAPSHOT' => 2,
        'WORK' => 2,
        'AND NO CHAIN' => 3,
        'AND CHAIN' => 3,
        'RELEASE' => 4,
        'NO RELEASE' => 4,
    );

    /**
     * @param Parser     $parser the instance that requests parsing
     * @param TokensList $list   the list of tokens to be parsed
     */
    public function parse(Parser $parser, TokensList $list)
    {
        parent::parse($parser, $list);

        // Checks the type of this query.
        if (($this->options->has('START TRANSACTION'))
            || ($this->options->has('BEGIN'))
        ) {
            $this->type = self::TYPE_BEGIN;
        } elseif (($this->options->has('COMMIT'))
            || ($this->options->has('ROLLBACK'))
        ) {
            $this->type = self::TYPE_END;
        }
    }

    /**
     * @return string
     */
    public function build()
    {
        $ret = OptionsArray::build($this->options);
        if ($this->type === self::TYPE_BEGIN) {
            foreach ($this->statements as $statement) {
                /*
                 * @var SelectStatement $statement
                 */
                $ret .= ';' . $statement->build();
            }
            $ret .= ';' . $this->end->build();
        }

        return $ret;
    }
}
