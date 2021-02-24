<?php

declare(strict_types=1);

namespace PhpMyAdmin\Twig\I18n;

use PhpMyAdmin\Twig\Extensions\Node\TransNode;
use Twig\Compiler;
use Twig\Node\Expression\AbstractExpression;
use Twig\Node\Node;
use function array_merge;
use function str_replace;
use function trim;

class NodeTrans extends TransNode
{
    /**
     * The nodes are automatically made available as properties ($this->node).
     * The attributes are automatically made available as array items ($this['name']).
     *
     * @param Node               $body    Body of node trans
     * @param Node|null          $plural  Node plural
     * @param AbstractExpression $count   Node count
     * @param Node|null          $context Node context
     * @param Node|null          $notes   Node notes
     * @param int                $lineno  The line number
     * @param string             $tag     The tag name associated with the Node
     */
    public function __construct(
        Node $body,
        ?Node $plural,
        ?AbstractExpression $count,
        ?Node $context,
        ?Node $notes,
        int $lineno,
        string $tag
    ) {
        $nodes = ['body' => $body];
        if ($count !== null) {
            $nodes['count'] = $count;
        }
        if ($plural !== null) {
            $nodes['plural'] = $plural;
        }
        if ($context !== null) {
            $nodes['context'] = $context;
        }
        if ($notes !== null) {
            $nodes['notes'] = $notes;
        }

        Node::__construct($nodes, [], $lineno, $tag);
    }

    /**
     * Compiles the node to PHP.
     *
     * @param Compiler $compiler Node compiler
     *
     * @return void
     */
    public function compile(Compiler $compiler)
    {
        $compiler->addDebugInfo($this);

        [$msg, $vars] = $this->compileString($this->getNode('body'));

        $msg1 = null;
        if ($this->hasNode('plural')) {
            [$msg1, $vars1] = $this->compileString($this->getNode('plural'));

            $vars = array_merge($vars, $vars1);
        }

        $function = $this->getTransFunction(
            $this->hasNode('plural'),
            $this->hasNode('context')
        );

        if ($this->hasNode('notes')) {
            $message = trim($this->getNode('notes')->getAttribute('data'));

            // line breaks are not allowed cause we want a single line comment
            $message = str_replace(["\n", "\r"], ' ', $message);
            $compiler->write('// l10n: ' . $message . "\n");
        }

        if ($vars) {
            $compiler
                ->write('echo strtr(' . $function . '(')
                ->subcompile($msg);

            if ($this->hasNode('plural')) {
                $compiler
                    ->raw(', ')
                    ->subcompile($msg1)
                    ->raw(', abs(')
                    ->subcompile($this->hasNode('count') ? $this->getNode('count') : null)
                    ->raw(')');
            }

            $compiler->raw('), array(');

            foreach ($vars as $var) {
                if ($var->getAttribute('name') === 'count') {
                    $compiler
                        ->string('%count%')
                        ->raw(' => abs(')
                        ->subcompile($this->hasNode('count') ? $this->getNode('count') : null)
                        ->raw('), ');
                } else {
                    $compiler
                        ->string('%' . $var->getAttribute('name') . '%')
                        ->raw(' => ')
                        ->subcompile($var)
                        ->raw(', ');
                }
            }

            $compiler->raw("));\n");
        } else {
            $compiler->write('echo ' . $function . '(');

            if ($this->hasNode('context')) {
                $context = trim($this->getNode('context')->getAttribute('data'));
                $compiler->write('"' . $context . '", ');
            }

            $compiler->subcompile($msg);

            if ($this->hasNode('plural')) {
                $compiler
                    ->raw(', ')
                    ->subcompile($msg1)
                    ->raw(', abs(')
                    ->subcompile($this->hasNode('count') ? $this->getNode('count') : null)
                    ->raw(')');
            }

            $compiler->raw(");\n");
        }
    }

    /**
     * @param bool $plural        Return plural or singular function to use
     * @param bool $hasMsgContext It has message context?
     */
    protected function getTransFunction($plural, bool $hasMsgContext = false): string
    {
        if ($hasMsgContext) {
            return $plural ? '_ngettext' : '_pgettext';
        }

        return $plural ? '_ngettext' : '_gettext';
    }
}
