<?php

declare(strict_types=1);

namespace PhpMyAdmin\Config\Settings;

/**
 * @link https://docs.phpmyadmin.net/en/latest/config.html#cfg_DBG
 *
 * @psalm-immutable
 */
final class Debug
{
    /**
     * Output executed queries and their execution times.
     *
     * ```php
     * $cfg['DBG']['sql'] = false;
     * ```
     *
     * @link https://docs.phpmyadmin.net/en/latest/config.html#cfg_DBG_sql
     */
    public bool $sql;

    /**
     * Log executed queries and their execution times to syslog.
     *
     * ```php
     * $cfg['DBG']['sqllog'] = false;
     * ```
     *
     * @link https://docs.phpmyadmin.net/en/latest/config.html#cfg_DBG_sqllog
     */
    public bool $sqllog;

    /**
     * Enable to let server present itself as demo server.
     *
     * ```php
     * $cfg['DBG']['demo'] = false;
     * ```
     *
     * @link https://docs.phpmyadmin.net/en/latest/config.html#cfg_DBG_demo
     */
    public bool $demo;

    /**
     * Enable Simple two-factor authentication.
     *
     * ```php
     * $cfg['DBG']['simple2fa'] = false;
     * ```
     *
     * @link https://docs.phpmyadmin.net/en/latest/config.html#cfg_DBG_simple2fa
     */
    public bool $simple2fa;

    /** @param mixed[] $debug */
    public function __construct(array $debug = [])
    {
        $this->sql = $this->setSql($debug);
        $this->sqllog = $this->setSqlLog($debug);
        $this->demo = $this->setDemo($debug);
        $this->simple2fa = $this->setSimple2fa($debug);
    }

    /** @return array<string, bool> */
    public function asArray(): array
    {
        return ['sql' => $this->sql, 'sqllog' => $this->sqllog, 'demo' => $this->demo, 'simple2fa' => $this->simple2fa];
    }

    /** @param mixed[] $debug */
    private function setSql(array $debug): bool
    {
        return isset($debug['sql']) && $debug['sql'];
    }

    /** @param mixed[] $debug */
    private function setSqlLog(array $debug): bool
    {
        return isset($debug['sqllog']) && $debug['sqllog'];
    }

    /** @param mixed[] $debug */
    private function setDemo(array $debug): bool
    {
        return isset($debug['demo']) && $debug['demo'];
    }

    /** @param mixed[] $debug */
    private function setSimple2fa(array $debug): bool
    {
        return isset($debug['simple2fa']) && $debug['simple2fa'];
    }
}
