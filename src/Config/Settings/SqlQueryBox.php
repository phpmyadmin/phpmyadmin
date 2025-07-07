<?php

declare(strict_types=1);

namespace PhpMyAdmin\Config\Settings;

// phpcs:disable Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps

/**
 * SQL query box settings
 *
 * @link https://docs.phpmyadmin.net/en/latest/config.html#sql-query-box-settings
 *
 * @psalm-immutable
 * @psalm-type SqlQueryBoxSettingsType = array{Edit: bool, Explain: bool, ShowAsPHP: bool, Refresh: bool, ShowCreate: bool}
 */
final class SqlQueryBox
{
    /**
     * Display an "Edit" link on the results page to change a query.
     *
     * ```php
     * $cfg['SQLQuery']['Edit'] = true;
     * ```
     *
     * @link https://docs.phpmyadmin.net/en/latest/config.html#cfg_SQLQuery_Edit
     */
    public bool $Edit;

    /**
     * Display an "Explain SQL" link on the results page.
     *
     * ```php
     * $cfg['SQLQuery']['Explain'] = true;
     * ```
     *
     * @link https://docs.phpmyadmin.net/en/latest/config.html#cfg_SQLQuery_Explain
     */
    public bool $Explain;

    /**
     * Display a "Create PHP code" link on the results page to wrap a query in PHP.
     *
     * ```php
     * $cfg['SQLQuery']['ShowAsPHP'] = true;
     * ```
     *
     * @link https://docs.phpmyadmin.net/en/latest/config.html#cfg_SQLQuery_ShowAsPHP
     */
    public bool $ShowAsPHP;

    /**
     * Display a "Refresh" link on the results page.
     *
     * ```php
     * $cfg['SQLQuery']['Refresh'] = true;
     * ```
     *
     * @link https://docs.phpmyadmin.net/en/latest/config.html#cfg_SQLQuery_Refresh
     */
    public bool $Refresh;

    /**
     * Display a "Show create" link on the results page.
     *
     * ```php
     * $cfg['SQLQuery']['ShowCreate'] = true;
     * ```
     *
     * @link https://docs.phpmyadmin.net/en/latest/config.html#cfg_SQLQuery_ShowCreate
     */
    public bool $ShowCreate;

    /** @param mixed[] $sqlQueryBox */
    public function __construct(array $sqlQueryBox = [])
    {
        $this->Edit = $this->setEdit($sqlQueryBox);
        $this->Explain = $this->setExplain($sqlQueryBox);
        $this->ShowAsPHP = $this->setShowAsPHP($sqlQueryBox);
        $this->Refresh = $this->setRefresh($sqlQueryBox);
        $this->ShowCreate = $this->setShowCreate($sqlQueryBox);
    }

    /** @psalm-return SqlQueryBoxSettingsType */
    public function asArray(): array
    {
        return [
            'Edit' => $this->Edit,
            'Explain' => $this->Explain,
            'ShowAsPHP' => $this->ShowAsPHP,
            'Refresh' => $this->Refresh,
            'ShowCreate' => $this->ShowCreate,
        ];
    }

    /** @param mixed[] $sqlQueryBox */
    private function setEdit(array $sqlQueryBox): bool
    {
        return ! isset($sqlQueryBox['Edit']) || $sqlQueryBox['Edit'];
    }

    /** @param mixed[] $sqlQueryBox */
    private function setExplain(array $sqlQueryBox): bool
    {
        return ! isset($sqlQueryBox['Explain']) || $sqlQueryBox['Explain'];
    }

    /** @param mixed[] $sqlQueryBox */
    private function setShowAsPHP(array $sqlQueryBox): bool
    {
        return ! isset($sqlQueryBox['ShowAsPHP']) || $sqlQueryBox['ShowAsPHP'];
    }

    /** @param mixed[] $sqlQueryBox */
    private function setRefresh(array $sqlQueryBox): bool
    {
        return ! isset($sqlQueryBox['Refresh']) || $sqlQueryBox['Refresh'];
    }

    /** @param mixed[] $sqlQueryBox */
    private function setShowCreate(array $sqlQueryBox): bool
    {
        return ! isset($sqlQueryBox['ShowCreate']) || $sqlQueryBox['ShowCreate'];
    }
}
