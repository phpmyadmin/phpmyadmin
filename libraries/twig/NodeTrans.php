<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * hold PMA\libraries\twig\NodeTrans class
 *
 * @package PMA\libraries\twig
 */
namespace PMA\libraries\twig;

use Twig_Compiler;
use Twig_Extensions_Node_Trans;
use Twig_Node;
use Twig_Node_Expression;

/**
 * Class NodeTrans
 *
 * @package PMA\libraries\twig
 */
class NodeTrans extends Twig_Extensions_Node_Trans
{
    /**
     * {@inheritdoc}
     */
    public function __construct(
        Twig_Node $body,
        Twig_Node $plural = null,
        Twig_Node_Expression $count = null,
        Twig_Node $context = null,
        Twig_Node $notes = null,
        $lineno,
        $tag = null
    ) {
        $nodes = array('body' => $body);
        if (null !== $count) {
            $nodes['count'] = $count;
        }
        if (null !== $plural) {
            $nodes['plural'] = $plural;
        }
        if (null !== $context) {
            $nodes['context'] = $context;
        }
        if (null !== $notes) {
            $nodes['notes'] = $notes;
        }

        $twigNodeConstructor = new \ReflectionMethod(
            get_parent_class(get_parent_class($this)),
            '__construct'
        );
        $twigNodeConstructor->invoke($this, $nodes, array(), $lineno, $tag);
    }

    /**
     * {@inheritdoc}
     */
    public function compile(Twig_Compiler $compiler)
    {
        $compiler->addDebugInfo($this);

        list($msg, $vars) = $this->compileString($this->getNode('body'));

        if ($this->hasNode('plural')) {
            list($msg1, $vars1) = $this->compileString($this->getNode('plural'));

            $vars = array_merge($vars, $vars1);
        }

        $function = $this->getTransFunction(
            $this->hasNode('plural'),
            $this->hasNode('context')
        );

        if ($this->hasNode('notes')) {
            $message = trim($this->getNode('notes')->getAttribute('data'));

            // line breaks are not allowed cause we want a single line comment
            $message = str_replace(array("\n", "\r"), ' ', $message);
            $compiler->write("// notes: {$message}\n");
        }

        if ($vars) {
            $compiler
                ->write('echo strtr('.$function.'(')
                ->subcompile($msg)
            ;

            if ($this->hasNode('plural')) {
                $compiler
                    ->raw(', ')
                    ->subcompile($msg1)
                    ->raw(', abs(')
                    ->subcompile($this->hasNode('count') ? $this->getNode('count') : null)
                    ->raw(')')
                ;
            }

            $compiler->raw('), array(');

            foreach ($vars as $var) {
                if ('count' === $var->getAttribute('name')) {
                    $compiler
                        ->string('%count%')
                        ->raw(' => abs(')
                        ->subcompile($this->hasNode('count') ? $this->getNode('count') : null)
                        ->raw('), ')
                    ;
                } else {
                    $compiler
                        ->string('%'.$var->getAttribute('name').'%')
                        ->raw(' => ')
                        ->subcompile($var)
                        ->raw(', ')
                    ;
                }
            }

            $compiler->raw("));\n");
        } else {
            $compiler->write('echo '.$function.'(');

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
                    ->raw(')')
                ;
            }

            $compiler->raw(");\n");
        }
    }

    /**
     * @param bool $plural        Return plural or singular function to use
     * @param bool $hasMsgContext It has message context?
     *
     * @return string
     */
    protected function getTransFunction($plural, $hasMsgContext = false)
    {
        if ($hasMsgContext) {
            return $plural ? '_ngettext' : '_pgettext';
        } else {
            return $plural ? '_ngettext' : '_gettext';
        }
    }
}
