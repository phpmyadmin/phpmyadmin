<?php

/**
 * Defines a component that is later extended to parse specialized components or
 * keywords.
 *
 * There is a small difference between *Component and *Keyword classes: usually,
 * *Component parsers can be reused in multiple  situations and *Keyword parsers
 * count on the *Component classes to do their job.
 *
 * @package SqlParser
 */
namespace SqlParser;

require_once 'common.php';

/**
 * A component (of a statement) is a part of a statement that is common to
 * multiple query types.
 *
 * @category Components
 * @package  SqlParser
 * @license  https://www.gnu.org/licenses/gpl-2.0.txt GPL-2.0+
 */
abstract class Component
{

    /**
     * Parses the tokens contained in the given list in the context of the given
     * parser.
     *
     * @param Parser     $parser  The parser that serves as context.
     * @param TokensList $list    The list of tokens that are being parsed.
     * @param array      $options Parameters for parsing.
     *
     * @throws \Exception Not implemented yet.
     *
     * @return mixed
     */
    public static function parse(
        Parser $parser,
        TokensList $list,
        array $options = array()
    ) {
        // This method should be abstract, but it can't be both static and
        // abstract.
        throw new \Exception(\__('Not implemented yet.'));
    }

    /**
     * Builds the string representation of a component of this type.
     *
     * In other words, this function represents the inverse function of
     * `static::parse`.
     *
     * @param mixed $component The component to be built.
     * @param array $options   Parameters for building.
     *
     * @throws \Exception Not implemented yet.
     *
     * @return string
     */
    public static function build($component, array $options = array())
    {
        // This method should be abstract, but it can't be both static and
        // abstract.
        throw new \Exception(\__('Not implemented yet.'));
    }

    /**
     * Builds the string representation of a component of this type.
     *
     * @see static::build
     *
     * @return string
     */
    public function __toString()
    {
        return static::build($this);
    }
}
