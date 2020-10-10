<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * hold PhpMyAdmin\Twig\I18n\TokenParserTrans class
 *
 * @package PhpMyAdmin\Twig\I18n
 */
declare(strict_types=1);

namespace PhpMyAdmin\Twig\I18n;

use PhpMyAdmin\Twig\Extensions\TokenParser\TransTokenParser;
use Twig\Token;

/**
 * Class TokenParserTrans
 *
 * @package PhpMyAdmin\Twig\I18n
 */
class TokenParserTrans extends TransTokenParser
{
    /**
     * Parses a token and returns a node.
     *
     * @param Token $token Twig token to parse
     *
     * @return NodeTrans
     *
     * @throws \Twig\Error\SyntaxError
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

            if ('plural' === $next) {
                $count = $this->parser->getExpressionParser()->parseExpression();
                $stream->expect(Token::BLOCK_END_TYPE);
                $plural = $this->parser->subparse([$this, 'decideForFork']);

                if ('notes' === $stream->next()->getValue()) {
                    $stream->expect(Token::BLOCK_END_TYPE);
                    $notes = $this->parser->subparse([$this, 'decideForEnd'], true);
                }
            } elseif ('context' === $next) {
                $stream->expect(Token::BLOCK_END_TYPE);
                $context = $this->parser->subparse([$this, 'decideForEnd'], true);
            } elseif ('notes' === $next) {
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
