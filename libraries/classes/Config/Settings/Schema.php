<?php

declare(strict_types=1);

namespace PhpMyAdmin\Config\Settings;

// phpcs:disable Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps

use function in_array;

/**
 * @psalm-immutable
 */
final class Schema
{
    /**
     * @var string
     * @psalm-var 'pdf'|'eps'|'dia'|'svg'
     */
    public $format = 'pdf';

    /** @var bool */
    public $pdf_show_color = true;

    /** @var bool */
    public $pdf_show_keys = false;

    /** @var bool */
    public $pdf_all_tables_same_width = false;

    /**
     * @var string
     * @psalm-var 'L'|'P'
     */
    public $pdf_orientation = 'L';

    /** @var string */
    public $pdf_paper = 'A4';

    /** @var bool */
    public $pdf_show_grid = false;

    /** @var bool */
    public $pdf_with_doc = true;

    /**
     * @var string
     * @psalm-var ''|'name_asc'|'name_desc'
     */
    public $pdf_table_order = '';

    /** @var bool */
    public $dia_show_color = true;

    /** @var bool */
    public $dia_show_keys = false;

    /**
     * @var string
     * @psalm-var 'L'|'P'
     */
    public $dia_orientation = 'L';

    /** @var string */
    public $dia_paper = 'A4';

    /** @var bool */
    public $eps_show_color = true;

    /** @var bool */
    public $eps_show_keys = false;

    /** @var bool */
    public $eps_all_tables_same_width = false;

    /**
     * @var string
     * @psalm-var 'L'|'P'
     */
    public $eps_orientation = 'L';

    /** @var bool */
    public $svg_show_color = true;

    /** @var bool */
    public $svg_show_keys = false;

    /** @var bool */
    public $svg_all_tables_same_width = false;

    /**
     * @param array<int|string, mixed> $schema
     */
    public function __construct(array $schema = [])
    {
        if (isset($schema['format']) && in_array($schema['format'], ['eps', 'dia', 'svg'], true)) {
            $this->format = $schema['format'];
        }

        if (isset($schema['pdf_show_color'])) {
            $this->pdf_show_color = (bool) $schema['pdf_show_color'];
        }

        if (isset($schema['pdf_show_keys'])) {
            $this->pdf_show_keys = (bool) $schema['pdf_show_keys'];
        }

        if (isset($schema['pdf_all_tables_same_width'])) {
            $this->pdf_all_tables_same_width = (bool) $schema['pdf_all_tables_same_width'];
        }

        if (isset($schema['pdf_orientation']) && $schema['pdf_orientation'] === 'P') {
            $this->pdf_orientation = 'P';
        }

        if (isset($schema['pdf_paper'])) {
            $this->pdf_paper = (string) $schema['pdf_paper'];
        }

        if (isset($schema['pdf_show_grid'])) {
            $this->pdf_show_grid = (bool) $schema['pdf_show_grid'];
        }

        if (isset($schema['pdf_with_doc'])) {
            $this->pdf_with_doc = (bool) $schema['pdf_with_doc'];
        }

        if (
            isset($schema['pdf_table_order']) && in_array($schema['pdf_table_order'], ['name_asc', 'name_desc'], true)
        ) {
            $this->pdf_table_order = $schema['pdf_table_order'];
        }

        if (isset($schema['dia_show_color'])) {
            $this->dia_show_color = (bool) $schema['dia_show_color'];
        }

        if (isset($schema['dia_show_keys'])) {
            $this->dia_show_keys = (bool) $schema['dia_show_keys'];
        }

        if (isset($schema['dia_orientation']) && $schema['dia_orientation'] === 'P') {
            $this->dia_orientation = 'P';
        }

        if (isset($schema['dia_paper'])) {
            $this->dia_paper = (string) $schema['dia_paper'];
        }

        if (isset($schema['eps_show_color'])) {
            $this->eps_show_color = (bool) $schema['eps_show_color'];
        }

        if (isset($schema['eps_show_keys'])) {
            $this->eps_show_keys = (bool) $schema['eps_show_keys'];
        }

        if (isset($schema['eps_all_tables_same_width'])) {
            $this->eps_all_tables_same_width = (bool) $schema['eps_all_tables_same_width'];
        }

        if (isset($schema['eps_orientation']) && $schema['eps_orientation'] === 'P') {
            $this->eps_orientation = 'P';
        }

        if (isset($schema['svg_show_color'])) {
            $this->svg_show_color = (bool) $schema['svg_show_color'];
        }

        if (isset($schema['svg_show_keys'])) {
            $this->svg_show_keys = (bool) $schema['svg_show_keys'];
        }

        if (! isset($schema['svg_all_tables_same_width'])) {
            return;
        }

        $this->svg_all_tables_same_width = (bool) $schema['svg_all_tables_same_width'];
    }
}
