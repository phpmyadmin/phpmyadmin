<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * hold PMA\libraries\twig\i18n\TokenParserTrans class
 *
 * @package PMA\libraries\twig\i18n
 */
namespace PMA\libraries\twig\i18n;

use Twig_Extensions_TokenParser_Trans;
use Twig_Token;

/**
 * Class TokenParserTrans
 *
 * @package PMA\libraries\twig\i18n
 */
class TokenParserTrans extends Twig_Extensions_TokenParser_Trans
{
    /**
     * Parses a token and returns a node.
     *
     * @param Twig_Token $token Twig token to parse
     *
     * @return Twig_NodeInterface
     *
     * @throws Twig_Error_Syntax
     */
    public function parse(Twig_Token $token)
    {
        $lineno = $token->getLine();
        $stream = $this->parser->getStream();
        $count = null;
        $plural = null;
        $notes = null;
        $context = null;

        if (!$stream->test(Twig_Token::BLOCK_END_TYPE)) {
            $body = $this->parser->getExpressionParser()->parseExpression();
        } else {
            $stream->expect(Twig_Token::BLOCK_END_TYPE);
            $body = $this->parser->subparse(array($this, 'decideForFork'));
            $next = $stream->next()->getValue();

            if ('plural' === $next) {
                $count = $this->parser->getExpressionParser()->parseExpression();
                $stream->expect(Twig_Token::BLOCK_END_TYPE);
                $plural = $this->parser->subparse(array($this, 'decideForFork'));

                if ('notes' === $stream->next()->getValue()) {
                    $stream->expect(Twig_Token::BLOCK_END_TYPE);
                    $notes = $this->parser->subparse(array($this, 'decideForEnd'), true);
                }
            } elseif ('context' === $next) {
                $stream->expect(Twig_Token::BLOCK_END_TYPE);
                $context = $this->parser->subparse(array($this, 'decideForEnd'), true);
            } elseif ('notes' === $next) {
                $stream->expect(Twig_Token::BLOCK_END_TYPE);
                $notes = $this->parser->subparse(array($this, 'decideForEnd'), true);
            }
        }

        $stream->expect(Twig_Token::BLOCK_END_TYPE);

        $this->checkTransString($body, $lineno);

        return new NodeTrans($body, $plural, $count, $context, $notes, $lineno, $this->getTag());
    }

    /**
     * Tests the current token for a type.
     *
     * @param Twig_Token $token Twig token to test
     *
     * @return bool
     */
    public function decideForFork(Twig_Token $token)
    {
        return $token->test(array('plural', 'context', 'notes', 'endtrans'));
    }
}
