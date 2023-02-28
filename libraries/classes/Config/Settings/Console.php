<?php

declare(strict_types=1);

namespace PhpMyAdmin\Config\Settings;

use function in_array;

// phpcs:disable Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps

/**
 * Console settings
 *
 * @link https://docs.phpmyadmin.net/en/latest/config.html#console-settings
 *
 * @psalm-immutable
 */
final class Console
{
    /**
     * Show query history at start
     *
     * ```php
     * $cfg['Console']['StartHistory'] = false;
     * ```
     *
     * @link https://docs.phpmyadmin.net/en/latest/config.html#cfg_Console_StartHistory
     */
    public bool $StartHistory;

    /**
     * Always expand query messages
     *
     * ```php
     * $cfg['Console']['AlwaysExpand'] = false;
     * ```
     *
     * @link https://docs.phpmyadmin.net/en/latest/config.html#cfg_Console_AlwaysExpand
     */
    public bool $AlwaysExpand;

    /**
     * Show current browsing query
     *
     * ```php
     * $cfg['Console']['CurrentQuery'] = true;
     * ```
     *
     * @link https://docs.phpmyadmin.net/en/latest/config.html#cfg_Console_CurrentQuery
     */
    public bool $CurrentQuery;

    /**
     * Execute queries on Enter and insert new line with Shift + Enter
     *
     * ```php
     * $cfg['Console']['EnterExecutes'] = false;
     * ```
     *
     * @link https://docs.phpmyadmin.net/en/latest/config.html#cfg_Console_EnterExecutes
     */
    public bool $EnterExecutes;

    /**
     * Switch to dark theme
     *
     * ```php
     * $cfg['Console']['DarkTheme'] = false;
     * ```
     *
     * @link https://docs.phpmyadmin.net/en/latest/config.html#cfg_Console_DarkTheme
     */
    public bool $DarkTheme;

    /**
     * Console mode
     *
     * ```php
     * $cfg['Console']['Mode'] = 'info';
     * ```
     *
     * @link https://docs.phpmyadmin.net/en/latest/config.html#cfg_Console_Mode
     *
     * @psalm-var 'info'|'show'|'collapse'
     */
    public string $Mode;

    /**
     * Console height
     *
     * ```php
     * $cfg['Console']['Height'] = 92;
     * ```
     *
     * @link https://docs.phpmyadmin.net/en/latest/config.html#cfg_Console_Height
     *
     * @psalm-var positive-int
     */
    public int $Height;

    /**
     * ```php
     * $cfg['Console']['GroupQueries'] = false;
     * ```
     */
    public bool $GroupQueries;

    /**
     * ```php
     * $cfg['Console']['OrderBy'] = 'exec';
     * ```
     *
     * @psalm-var 'exec'|'time'|'count'
     */
    public string $OrderBy;

    /**
     * ```php
     * $cfg['Console']['Order'] = 'asc';
     * ```
     *
     * @psalm-var 'asc'|'desc'
     */
    public string $Order;

    /** @param mixed[] $console */
    public function __construct(array $console = [])
    {
        $this->StartHistory = $this->setStartHistory($console);
        $this->AlwaysExpand = $this->setAlwaysExpand($console);
        $this->CurrentQuery = $this->setCurrentQuery($console);
        $this->EnterExecutes = $this->setEnterExecutes($console);
        $this->DarkTheme = $this->setDarkTheme($console);
        $this->Mode = $this->setMode($console);
        $this->Height = $this->setHeight($console);
        $this->GroupQueries = $this->setGroupQueries($console);
        $this->OrderBy = $this->setOrderBy($console);
        $this->Order = $this->setOrder($console);
    }

    /** @return array<string, string|bool|int> */
    public function asArray(): array
    {
        return [
            'StartHistory' => $this->StartHistory,
            'AlwaysExpand' => $this->AlwaysExpand,
            'CurrentQuery' => $this->CurrentQuery,
            'EnterExecutes' => $this->EnterExecutes,
            'DarkTheme' => $this->DarkTheme,
            'Mode' => $this->Mode,
            'Height' => $this->Height,
            'GroupQueries' => $this->GroupQueries,
            'OrderBy' => $this->OrderBy,
            'Order' => $this->Order,
        ];
    }

    /** @param mixed[] $console */
    private function setStartHistory(array $console): bool
    {
        if (isset($console['StartHistory'])) {
            return (bool) $console['StartHistory'];
        }

        return false;
    }

    /** @param mixed[] $console */
    private function setAlwaysExpand(array $console): bool
    {
        if (isset($console['AlwaysExpand'])) {
            return (bool) $console['AlwaysExpand'];
        }

        return false;
    }

    /** @param mixed[] $console */
    private function setCurrentQuery(array $console): bool
    {
        if (isset($console['CurrentQuery'])) {
            return (bool) $console['CurrentQuery'];
        }

        return true;
    }

    /** @param mixed[] $console */
    private function setEnterExecutes(array $console): bool
    {
        if (isset($console['EnterExecutes'])) {
            return (bool) $console['EnterExecutes'];
        }

        return false;
    }

    /** @param mixed[] $console */
    private function setDarkTheme(array $console): bool
    {
        if (isset($console['DarkTheme'])) {
            return (bool) $console['DarkTheme'];
        }

        return false;
    }

    /**
     * @param mixed[] $console
     *
     * @psalm-return 'info'|'show'|'collapse'
     */
    private function setMode(array $console): string
    {
        if (isset($console['Mode']) && in_array($console['Mode'], ['show', 'collapse'], true)) {
            return $console['Mode'];
        }

        return 'info';
    }

    /**
     * @param mixed[] $console
     *
     * @psalm-return positive-int
     */
    private function setHeight(array $console): int
    {
        if (isset($console['Height'])) {
            $height = (int) $console['Height'];
            if ($height >= 1) {
                return $height;
            }
        }

        return 92;
    }

    /** @param mixed[] $console */
    private function setGroupQueries(array $console): bool
    {
        if (isset($console['GroupQueries'])) {
            return (bool) $console['GroupQueries'];
        }

        return false;
    }

    /**
     * @param mixed[] $console
     *
     * @psalm-return 'exec'|'time'|'count'
     */
    private function setOrderBy(array $console): string
    {
        if (isset($console['OrderBy']) && in_array($console['OrderBy'], ['time', 'count'], true)) {
            return $console['OrderBy'];
        }

        return 'exec';
    }

    /**
     * @param mixed[] $console
     *
     * @psalm-return 'asc'|'desc'
     */
    private function setOrder(array $console): string
    {
        if (isset($console['Order']) && $console['Order'] === 'desc') {
            return 'desc';
        }

        return 'asc';
    }
}
