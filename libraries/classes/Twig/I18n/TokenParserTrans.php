<?php

declare(strict_types=1);

namespace PhpMyAdmin\Twig\I18n;

use PhpMyAdmin\Twig\Extensions\TokenParser\TransTokenParser;
use Twig\Error\SyntaxError;
use Twig\Token;

class TokenParserTrans extends TransTokenParser
{
    /**
     * Parses a token and returns a node.
     *
     * @param Token $token Twig token to parse
     *
     * @return NodeTrans
     *
     * @throws SyntaxError
     */
    public function parse(Token $token)
    {
        $lineno = $token->getLine();
        $stream = $this->parser->getStream();
        $count = null;
        $plural = null;
        $notes = null;
        $context = null;

        if (! $stream->test(Token::BLOCK_END_TYPE)) {
            $body = $this->parser->getExpressionParser()->parseExpression();
        } else {
            $stream->expect(Token::BLOCK_END_TYPE);
            $body = $this->parser->subparse([$this, 'decideForFork']);
            $next = $stream->next()->getValue();

            if ($next === 'plural') {
                $count = $this->parser->getExpressionParser()->parseExpression();
                $stream->expect(Token::BLOCK_END_TYPE);
                $plural = $this->parser->subparse([$this, 'decideForFork']);

                if ($stream->next()->getValue() === 'notes') {
                    $stream->expect(Token::BLOCK_END_TYPE);
                    $notes = $this->parser->subparse([$this, 'decideForEnd'], true);
                }
            } elseif ($next === 'context') {
                $stream->expect(Token::BLOCK_END_TYPE);
                $context = $this->parser->subparse([$this, 'decideForEnd'], true);
            } elseif ($next === 'notes') {
                $stream->expect(Token::BLOCK_END_TYPE);
                $notes = $this->parser->subparse([$this, 'decideForEnd'], true);
            }
        }

        $stream->expect(Token::BLOCK_END_TYPE);

        $this->checkTransString($body, $lineno);

        return new NodeTrans($body, $plural, $count, $context, $notes, $lineno, $this->getTag());
    }

    /**
     * Tests the current token for a type.
     *
     * @param Token $token Twig token to test
     *
     * @return bool
     */
    public function decideForFork(Token $token)
    {
        return $token->test(['plural', 'context', 'notes', 'endtrans']);
    }
}
