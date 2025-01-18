<?php

declare(strict_types=1);

namespace PhpMyAdmin\Twig\Node\Expression;

use Twig\Attribute\FirstClassTwigCallableReady;
use Twig\Compiler;
use Twig\Error\SyntaxError;
use Twig\Node\Expression\AbstractExpression;
use Twig\Node\Expression\ConstantExpression;
use Twig\Node\Node;

use function in_array;
use function is_string;
use function sprintf;
use function str_replace;
use function trim;

final class TransExpression extends AbstractExpression
{
    #[FirstClassTwigCallableReady]
    public function __construct(string $name, Node $arguments, int $lineno)
    {
        parent::__construct(['arguments' => $arguments], ['name' => $name, 'is_defined_test' => false], $lineno);
    }

    /** @throws SyntaxError */
    public function compile(Compiler $compiler): void
    {
        $parameters = $this->getParameters();

        if (isset($parameters['notes'])) {
            $this->compileNotes($compiler, $parameters);
        }

        if (
            isset($parameters['singular'])
            || isset($parameters['plural'])
            || isset($parameters['count'])
            || isset($parameters[1])
            || isset($parameters[2])
        ) {
            $this->compilePlural($compiler, $parameters);

            return;
        }

        if (isset($parameters['context'])) {
            $this->compileContext($compiler, $parameters);

            return;
        }

        $this->compileMessage($compiler, $parameters);
    }

    /**
     * @return Node[]
     *
     * @throws SyntaxError
     */
    private function getParameters(): array
    {
        $parameters = [];

        foreach ($this->getNode('arguments') as $name => $argument) {
            if (! in_array($name, [0, 1, 2, 'message', 'singular', 'plural', 'count', 'context', 'notes'], true)) {
                throw $this->unknownArgumentSyntaxError($name);
            }

            $parameters[$name] = $argument;
        }

        return $parameters;
    }

    /**
     * @param Node[] $parameters
     *
     * @throws SyntaxError
     */
    private function compileNotes(Compiler $compiler, array $parameters): void
    {
        $notes = $this->getStringFromNode('notes', $parameters['notes'] ?? null);

        // line breaks are not allowed because we want a single line comment
        $notes = trim(str_replace(["\n", "\r"], ' ', $notes));
        if ($notes === '') {
            throw $this->nonEmptyLiteralStringSyntaxError('notes');
        }

        $compiler->raw("\n// l10n: " . $notes . "\n");
    }

    /**
     * @param Node[] $parameters
     *
     * @throws SyntaxError
     */
    private function compilePlural(Compiler $compiler, array $parameters): void
    {
        if (isset($parameters[0], $parameters['singular'])) {
            throw $this->duplicateArgumentSyntaxError('singular');
        }

        if (isset($parameters[1], $parameters['plural'])) {
            throw $this->duplicateArgumentSyntaxError('plural');
        }

        if (isset($parameters[2], $parameters['count'])) {
            throw $this->duplicateArgumentSyntaxError('count');
        }

        $singular = $this->getStringFromNode('singular', $parameters[0] ?? $parameters['singular'] ?? null);
        $plural = $this->getStringFromNode('plural', $parameters[1] ?? $parameters['plural'] ?? null);
        $count = $parameters[2] ?? $parameters['count'] ?? null;
        if ($count === null) {
            throw $this->missingArgumentSyntaxError('count');
        }

        $compiler->raw('\\_ngettext(');
        $compiler->string($singular);
        $compiler->raw(', ');
        $compiler->string($plural);
        $compiler->raw(', ');
        $compiler->subcompile($count);
        $compiler->raw(isset($parameters['notes']) ? ")\n" : ')');
    }

    /**
     * @param Node[] $parameters
     *
     * @throws SyntaxError
     */
    private function compileContext(Compiler $compiler, array $parameters): void
    {
        if (isset($parameters[0], $parameters['message'])) {
            throw $this->duplicateArgumentSyntaxError('message');
        }

        $message = $this->getStringFromNode('message', $parameters[0] ?? $parameters['message'] ?? null);
        $context = $this->getStringFromNode('context', $parameters['context'] ?? null);

        $compiler->raw('\\_pgettext(');
        $compiler->string($context);
        $compiler->raw(', ');
        $compiler->string($message);
        $compiler->raw(isset($parameters['notes']) ? ")\n" : ')');
    }

    /**
     * @param Node[] $parameters
     *
     * @throws SyntaxError
     */
    private function compileMessage(Compiler $compiler, array $parameters): void
    {
        if (isset($parameters[0], $parameters['message'])) {
            throw $this->duplicateArgumentSyntaxError('message');
        }

        $message = $this->getStringFromNode('message', $parameters[0] ?? $parameters['message'] ?? null);

        $compiler->raw('\\_gettext(');
        $compiler->string($message);
        $compiler->raw(isset($parameters['notes']) ? ")\n" : ')');
    }

    /**
     * @psalm-return non-empty-string
     *
     * @throws SyntaxError
     */
    private function getStringFromNode(int|string $name, Node|null $node): string
    {
        if (! ($node instanceof ConstantExpression)) {
            throw $this->nonEmptyLiteralStringSyntaxError($name);
        }

        $value = $node->getAttribute('value');
        if (! is_string($value) || $value === '') {
            throw $this->nonEmptyLiteralStringSyntaxError($name);
        }

        return $value;
    }

    private function unknownArgumentSyntaxError(int|string $name): SyntaxError
    {
        return new SyntaxError(
            sprintf('Unknown argument "%s".', $name),
            $this->getTemplateLine(),
            $this->getSourceContext(),
        );
    }

    private function duplicateArgumentSyntaxError(int|string $name): SyntaxError
    {
        return new SyntaxError(
            sprintf('Argument "%s" is defined twice.', $name),
            $this->getTemplateLine(),
            $this->getSourceContext(),
        );
    }

    private function nonEmptyLiteralStringSyntaxError(int|string $name): SyntaxError
    {
        return new SyntaxError(
            sprintf('Value for argument "%s" must be a non-empty literal string.', $name),
            $this->getTemplateLine(),
            $this->getSourceContext(),
        );
    }

    private function missingArgumentSyntaxError(int|string $name): SyntaxError
    {
        return new SyntaxError(
            sprintf('Value for argument "%s" is required.', $name),
            $this->getTemplateLine(),
            $this->getSourceContext(),
        );
    }
}
