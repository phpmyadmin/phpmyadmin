<?php

declare(strict_types=1);

namespace PhpMyAdmin\Config\Settings;

// phpcs:disable Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps

use function in_array;

/** @psalm-immutable */
final class Schema
{
    /** @psalm-var 'pdf'|'eps'|'dia'|'svg' */
    public string $format;

    public bool $pdf_show_color;

    public bool $pdf_show_keys;

    public bool $pdf_all_tables_same_width;

    /** @psalm-var 'L'|'P' */
    public string $pdf_orientation;

    public string $pdf_paper;

    public bool $pdf_show_grid;

    public bool $pdf_with_doc;

    /** @psalm-var ''|'name_asc'|'name_desc' */
    public string $pdf_table_order;

    public bool $dia_show_color;

    public bool $dia_show_keys;

    /** @psalm-var 'L'|'P' */
    public string $dia_orientation;

    public string $dia_paper;

    public bool $eps_show_color;

    public bool $eps_show_keys;

    public bool $eps_all_tables_same_width;

    /** @psalm-var 'L'|'P' */
    public string $eps_orientation;

    public bool $svg_show_color;

    public bool $svg_show_keys;

    public bool $svg_all_tables_same_width;

    /** @param array<int|string, mixed> $schema */
    public function __construct(array $schema = [])
    {
        $this->format = $this->setFormat($schema);
        $this->pdf_show_color = $this->setPdfShowColor($schema);
        $this->pdf_show_keys = $this->setPdfShowKeys($schema);
        $this->pdf_all_tables_same_width = $this->setPdfAllTablesSameWidth($schema);
        $this->pdf_orientation = $this->setPdfOrientation($schema);
        $this->pdf_paper = $this->setPdfPaper($schema);
        $this->pdf_show_grid = $this->setPdfShowGrid($schema);
        $this->pdf_with_doc = $this->setPdfWithDoc($schema);
        $this->pdf_table_order = $this->setPdfTableOrder($schema);
        $this->dia_show_color = $this->setDiaShowColor($schema);
        $this->dia_show_keys = $this->setDiaShowKeys($schema);
        $this->dia_orientation = $this->setDiaOrientation($schema);
        $this->dia_paper = $this->setDiaPaper($schema);
        $this->eps_show_color = $this->setEpsShowColor($schema);
        $this->eps_show_keys = $this->setEpsShowKeys($schema);
        $this->eps_all_tables_same_width = $this->setEpsAllTablesSameWidth($schema);
        $this->eps_orientation = $this->setEpsOrientation($schema);
        $this->svg_show_color = $this->setSvgShowColor($schema);
        $this->svg_show_keys = $this->setSvgShowKeys($schema);
        $this->svg_all_tables_same_width = $this->setSvgAllTablesSameWidth($schema);
    }

    /**
     * @param array<int|string, mixed> $schema
     *
     * @psalm-return 'pdf'|'eps'|'dia'|'svg'
     */
    private function setFormat(array $schema): string
    {
        if (isset($schema['format']) && in_array($schema['format'], ['eps', 'dia', 'svg'], true)) {
            return $schema['format'];
        }

        return 'pdf';
    }

    /** @param array<int|string, mixed> $schema */
    private function setPdfShowColor(array $schema): bool
    {
        if (isset($schema['pdf_show_color'])) {
            return (bool) $schema['pdf_show_color'];
        }

        return true;
    }

    /** @param array<int|string, mixed> $schema */
    private function setPdfShowKeys(array $schema): bool
    {
        if (isset($schema['pdf_show_keys'])) {
            return (bool) $schema['pdf_show_keys'];
        }

        return false;
    }

    /** @param array<int|string, mixed> $schema */
    private function setPdfAllTablesSameWidth(array $schema): bool
    {
        if (isset($schema['pdf_all_tables_same_width'])) {
            return (bool) $schema['pdf_all_tables_same_width'];
        }

        return false;
    }

    /**
     * @param array<int|string, mixed> $schema
     *
     * @psalm-return 'L'|'P'
     */
    private function setPdfOrientation(array $schema): string
    {
        if (isset($schema['pdf_orientation']) && $schema['pdf_orientation'] === 'P') {
            return 'P';
        }

        return 'L';
    }

    /** @param array<int|string, mixed> $schema */
    private function setPdfPaper(array $schema): string
    {
        if (isset($schema['pdf_paper'])) {
            return (string) $schema['pdf_paper'];
        }

        return 'A4';
    }

    /** @param array<int|string, mixed> $schema */
    private function setPdfShowGrid(array $schema): bool
    {
        if (isset($schema['pdf_show_grid'])) {
            return (bool) $schema['pdf_show_grid'];
        }

        return false;
    }

    /** @param array<int|string, mixed> $schema */
    private function setPdfWithDoc(array $schema): bool
    {
        if (isset($schema['pdf_with_doc'])) {
            return (bool) $schema['pdf_with_doc'];
        }

        return true;
    }

    /**
     * @param array<int|string, mixed> $schema
     *
     * @psalm-return ''|'name_asc'|'name_desc'
     */
    private function setPdfTableOrder(array $schema): string
    {
        if (
            isset($schema['pdf_table_order']) && in_array($schema['pdf_table_order'], ['name_asc', 'name_desc'], true)
        ) {
            return $schema['pdf_table_order'];
        }

        return '';
    }

    /** @param array<int|string, mixed> $schema */
    private function setDiaShowColor(array $schema): bool
    {
        if (isset($schema['dia_show_color'])) {
            return (bool) $schema['dia_show_color'];
        }

        return true;
    }

    /** @param array<int|string, mixed> $schema */
    private function setDiaShowKeys(array $schema): bool
    {
        if (isset($schema['dia_show_keys'])) {
            return (bool) $schema['dia_show_keys'];
        }

        return false;
    }

    /**
     * @param array<int|string, mixed> $schema
     *
     * @psalm-return 'L'|'P'
     */
    private function setDiaOrientation(array $schema): string
    {
        if (isset($schema['dia_orientation']) && $schema['dia_orientation'] === 'P') {
            return 'P';
        }

        return 'L';
    }

    /** @param array<int|string, mixed> $schema */
    private function setDiaPaper(array $schema): string
    {
        if (isset($schema['dia_paper'])) {
            return (string) $schema['dia_paper'];
        }

        return 'A4';
    }

    /** @param array<int|string, mixed> $schema */
    private function setEpsShowColor(array $schema): bool
    {
        if (isset($schema['eps_show_color'])) {
            return (bool) $schema['eps_show_color'];
        }

        return true;
    }

    /** @param array<int|string, mixed> $schema */
    private function setEpsShowKeys(array $schema): bool
    {
        if (isset($schema['eps_show_keys'])) {
            return (bool) $schema['eps_show_keys'];
        }

        return false;
    }

    /** @param array<int|string, mixed> $schema */
    private function setEpsAllTablesSameWidth(array $schema): bool
    {
        if (isset($schema['eps_all_tables_same_width'])) {
            return (bool) $schema['eps_all_tables_same_width'];
        }

        return false;
    }

    /**
     * @param array<int|string, mixed> $schema
     *
     * @psalm-return 'L'|'P'
     */
    private function setEpsOrientation(array $schema): string
    {
        if (isset($schema['eps_orientation']) && $schema['eps_orientation'] === 'P') {
            return 'P';
        }

        return 'L';
    }

    /** @param array<int|string, mixed> $schema */
    private function setSvgShowColor(array $schema): bool
    {
        if (isset($schema['svg_show_color'])) {
            return (bool) $schema['svg_show_color'];
        }

        return true;
    }

    /** @param array<int|string, mixed> $schema */
    private function setSvgShowKeys(array $schema): bool
    {
        if (isset($schema['svg_show_keys'])) {
            return (bool) $schema['svg_show_keys'];
        }

        return false;
    }

    /** @param array<int|string, mixed> $schema */
    private function setSvgAllTablesSameWidth(array $schema): bool
    {
        if (isset($schema['svg_all_tables_same_width'])) {
            return (bool) $schema['svg_all_tables_same_width'];
        }

        return false;
    }
}
