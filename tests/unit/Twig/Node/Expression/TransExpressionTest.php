<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Twig\Node\Expression;

use PhpMyAdmin\Template;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Twig\Node\Expression\TransExpression;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use Twig\Compiler;
use Twig\Error\SyntaxError;
use Twig\Node\Expression\ConstantExpression;
use Twig\Node\Expression\NameExpression;
use Twig\Node\Node;

#[CoversClass(TransExpression::class)]
final class TransExpressionTest extends AbstractTestCase
{
    /** @param Node[] $arguments */
    #[DataProvider('transExpressionsProvider')]
    public function testTransExpressions(array $arguments, string $expected): void
    {
        $compiler = $this->getCompiler();
        (new TransExpression('t', new Node($arguments), 1))->compile($compiler);
        self::assertSame($expected, $compiler->getSource());
    }

    /** @return iterable<string, array{Node[], non-empty-string}> */
    public static function transExpressionsProvider(): iterable
    {
        yield 't("Message")' => [[self::getConstantExpression('Message')], '\\_gettext("Message")'];
        yield 't(message = "Message")' => [
            ['message' => self::getConstantExpression('Message')],
            '\\_gettext("Message")',
        ];

        yield 't("Message", notes = "Notes")' => [
            [self::getConstantExpression('Message'), 'notes' => self::getConstantExpression('Notes')],
            "\n" . '// l10n: Notes' . "\n" . '\\_gettext("Message")' . "\n",
        ];

        yield 't(message = "Message", notes = "Notes")' => [
            ['message' => self::getConstantExpression('Message'), 'notes' => self::getConstantExpression('Notes')],
            "\n" . '// l10n: Notes' . "\n" . '\\_gettext("Message")' . "\n",
        ];

        yield 't("Message", notes = " \n\rNotes\rwith line\nbreaks \n ")' => [
            [
                0 => self::getConstantExpression('Message'),
                'notes' => self::getConstantExpression(" \n\rNotes\rwith line\nbreaks \n "),
            ],
            "\n" . '// l10n: Notes with line breaks' . "\n" . '\\_gettext("Message")' . "\n",
        ];

        yield 't("Message", context = "Context")' => [
            [self::getConstantExpression('Message'), 'context' => self::getConstantExpression('Context')],
            '\\_pgettext("Context", "Message")',
        ];

        yield 't(message = "Message", context = "Context")' => [
            ['message' => self::getConstantExpression('Message'), 'context' => self::getConstantExpression('Context')],
            '\\_pgettext("Context", "Message")',
        ];

        yield 't("Message", context = "Context", notes = "Notes")' => [
            [
                0 => self::getConstantExpression('Message'),
                'context' => self::getConstantExpression('Context'),
                'notes' => self::getConstantExpression('Notes'),
            ],
            "\n" . '// l10n: Notes' . "\n" . '\\_pgettext("Context", "Message")' . "\n",
        ];

        yield 't(message = "Message", context = "Context", notes = "Notes")' => [
            [
                'message' => self::getConstantExpression('Message'),
                'context' => self::getConstantExpression('Context'),
                'notes' => self::getConstantExpression('Notes'),
            ],
            "\n" . '// l10n: Notes' . "\n" . '\\_pgettext("Context", "Message")' . "\n",
        ];

        yield 't("One apple", "%d apples", number_of_apples)' => [
            [
                self::getConstantExpression('One apple'),
                self::getConstantExpression('%d apples'),
                self::getNameExpression('number_of_apples'),
            ],
            '\\_ngettext("One apple", "%d apples", // line 1' . "\n" . '($context["number_of_apples"] ?? null))',
        ];

        yield 't("One apple", "%d apples", count = number_of_apples)' => [
            [
                0 => self::getConstantExpression('One apple'),
                1 => self::getConstantExpression('%d apples'),
                'count' => self::getNameExpression('number_of_apples'),
            ],
            '\\_ngettext("One apple", "%d apples", // line 1' . "\n" . '($context["number_of_apples"] ?? null))',
        ];

        yield 't("One apple", plural = "%d apples", count = number_of_apples)' => [
            [
                0 => self::getConstantExpression('One apple'),
                'plural' => self::getConstantExpression('%d apples'),
                'count' => self::getNameExpression('number_of_apples'),
            ],
            '\\_ngettext("One apple", "%d apples", // line 1' . "\n" . '($context["number_of_apples"] ?? null))',
        ];

        yield 't(singular = "One apple", plural = "%d apples", count = number_of_apples)' => [
            [
                'singular' => self::getConstantExpression('One apple'),
                'plural' => self::getConstantExpression('%d apples'),
                'count' => self::getNameExpression('number_of_apples'),
            ],
            '\\_ngettext("One apple", "%d apples", // line 1' . "\n" . '($context["number_of_apples"] ?? null))',
        ];

        yield 't("One apple", "%d apples", 3, notes = "Notes")' => [
            [
                0 => self::getConstantExpression('One apple'),
                1 => self::getConstantExpression('%d apples'),
                2 => self::getConstantExpression(3),
                'notes' => self::getConstantExpression('Notes'),
            ],
            "\n" . '// l10n: Notes' . "\n"
                . '\\_ngettext("One apple", "%d apples", 3)' . "\n",
        ];

        yield 't(singular = "One apple", plural = "%d apples", count = 3, notes = "Notes")' => [
            [
                'singular' => self::getConstantExpression('One apple'),
                'plural' => self::getConstantExpression('%d apples'),
                'count' => self::getConstantExpression(3),
                'notes' => self::getConstantExpression('Notes'),
            ],
            "\n" . '// l10n: Notes' . "\n"
                . '\\_ngettext("One apple", "%d apples", 3)' . "\n",
        ];
    }

    /** @param Node[] $arguments */
    #[DataProvider('transExpressionsWithErrorProvider')]
    public function testTransExpressionsWithError(array $arguments, string $expected): void
    {
        self::expectException(SyntaxError::class);
        self::expectExceptionMessage($expected);
        (new TransExpression('t', new Node($arguments), 1))->compile($this->getCompiler());
    }

    /** @return iterable<string, array{Node[], non-empty-string}> */
    public static function transExpressionsWithErrorProvider(): iterable
    {
        yield 't()' => [[], 'Value for argument "message" must be a non-empty literal string at line 1.'];
        yield 't("")' => [
            [self::getConstantExpression('')],
            'Value for argument "message" must be a non-empty literal string at line 1.',
        ];

        yield 't(message = "")' => [
            ['message' => self::getConstantExpression('')],
            'Value for argument "message" must be a non-empty literal string at line 1.',
        ];

        yield 't(notes = "Notes")' => [
            ['message' => self::getConstantExpression('')],
            'Value for argument "message" must be a non-empty literal string at line 1.',
        ];

        yield 't(variable_name)' => [
            [self::getNameExpression('variable_name')],
            'Value for argument "message" must be a non-empty literal string at line 1.',
        ];

        yield 't(message = variable_name)' => [
            ['message' => self::getNameExpression('variable_name')],
            'Value for argument "message" must be a non-empty literal string at line 1.',
        ];

        yield 't("Message", unknown = "Unknown argument")' => [
            [self::getConstantExpression('Message'), 'unknown' => self::getConstantExpression('Unknown argument')],
            'Unknown argument "unknown" at line 1.',
        ];

        yield 't("Message", notes = variable_name)' => [
            [self::getConstantExpression('Message'), 'notes' => self::getNameExpression('variable_name')],
            'Value for argument "notes" must be a non-empty literal string at line 1.',
        ];

        yield 't("Message", notes = "")' => [
            [self::getConstantExpression('Message'), 'notes' => self::getConstantExpression('')],
            'Value for argument "notes" must be a non-empty literal string at line 1.',
        ];

        yield 't("Message", notes = " \n ")' => [
            [self::getConstantExpression('Message'), 'notes' => self::getConstantExpression(" \n\r ")],
            'Value for argument "notes" must be a non-empty literal string at line 1.',
        ];

        yield 't("Message", context = variable_name)' => [
            [self::getConstantExpression('Message'), 'context' => self::getNameExpression('variable_name')],
            'Value for argument "context" must be a non-empty literal string at line 1.',
        ];

        yield 't("Message", context = "")' => [
            [self::getConstantExpression('Message'), 'context' => self::getConstantExpression('')],
            'Value for argument "context" must be a non-empty literal string at line 1.',
        ];

        yield 't("Message", message = "Message")' => [
            [self::getConstantExpression('Message'), 'message' => self::getConstantExpression('Message')],
            'Argument "message" is defined twice at line 1.',
        ];

        yield 't("Message", context = "Context", message = "Message")' => [
            [
                0 => self::getConstantExpression('Message'),
                'context' => self::getConstantExpression('Context'),
                'message' => self::getConstantExpression('Message'),
            ],
            'Argument "message" is defined twice at line 1.',
        ];

        yield 't("", "%d apples", 3)' => [
            [
                self::getConstantExpression(''),
                self::getConstantExpression('%d apples'),
                self::getConstantExpression(3),
            ],
            'Value for argument "singular" must be a non-empty literal string at line 1.',
        ];

        yield 't(variable_name, "%d apples", 3)' => [
            [
                self::getNameExpression('variable_name'),
                self::getConstantExpression('%d apples'),
                self::getConstantExpression(3),
            ],
            'Value for argument "singular" must be a non-empty literal string at line 1.',
        ];

        yield 't("One apple", "", 3)' => [
            [
                self::getConstantExpression('One apple'),
                self::getConstantExpression(''),
                self::getConstantExpression(3),
            ],
            'Value for argument "plural" must be a non-empty literal string at line 1.',
        ];

        yield 't("One apple", variable_name, 3)' => [
            [
                self::getConstantExpression('One apple'),
                self::getNameExpression('variable_name'),
                self::getConstantExpression(3),
            ],
            'Value for argument "plural" must be a non-empty literal string at line 1.',
        ];

        yield 't("One apple", "%d apples")' => [
            [
                self::getConstantExpression('One apple'),
                self::getConstantExpression('%d apples'),
            ],
            'Value for argument "count" is required at line 1.',
        ];

        yield 't("One apple", "%d apples", 3, singular = "One apple")' => [
            [
                0 => self::getConstantExpression('One apple'),
                1 => self::getConstantExpression('%d apples'),
                2 => self::getConstantExpression(3),
                'singular' => self::getConstantExpression('One apple'),
            ],
            'Argument "singular" is defined twice at line 1.',
        ];

        yield 't("One apple", "%d apples", 3, plural = "%d apples")' => [
            [
                0 => self::getConstantExpression('One apple'),
                1 => self::getConstantExpression('%d apples'),
                2 => self::getConstantExpression(3),
                'plural' => self::getConstantExpression('%d apples'),
            ],
            'Argument "plural" is defined twice at line 1.',
        ];

        yield 't("One apple", "%d apples", 3, count = 3)' => [
            [
                0 => self::getConstantExpression('One apple'),
                1 => self::getConstantExpression('%d apples'),
                2 => self::getConstantExpression(3),
                'count' => self::getConstantExpression(3),
            ],
            'Argument "count" is defined twice at line 1.',
        ];
    }

    private static function getConstantExpression(mixed $value): ConstantExpression
    {
        return new ConstantExpression($value, 1);
    }

    private static function getNameExpression(string $name): NameExpression
    {
        return new NameExpression($name, 1);
    }

    private function getCompiler(): Compiler
    {
        return (new Compiler(Template::getTwigEnvironment(null, false)))->reset();
    }
}
