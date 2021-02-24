<?php

declare(strict_types=1);

namespace PhpMyAdmin\Server;

use PhpMyAdmin\DatabaseInterface;

class Plugins
{
    /** @var DatabaseInterface */
    private $dbi;

    /**
     * @param DatabaseInterface $dbi DatabaseInterface instance
     */
    public function __construct(DatabaseInterface $dbi)
    {
        $this->dbi = $dbi;
    }

    /**
     * @return Plugin[]
     */
    public function getAll(): array
    {
        global $cfg;

        $sql = 'SHOW PLUGINS';
        if (! $cfg['Server']['DisableIS']) {
            $sql = 'SELECT * FROM information_schema.PLUGINS ORDER BY PLUGIN_TYPE, PLUGIN_NAME';
        }
        $result = $this->dbi->query($sql);
        $plugins = [];
        while ($row = $this->dbi->fetchAssoc($result)) {
            $plugins[] = $this->mapRowToPlugin($row);
        }
        $this->dbi->freeResult($result);

        return $plugins;
    }

    /**
     * @param array $row Row fetched from database
     */
    private function mapRowToPlugin(array $row): Plugin
    {
        return Plugin::fromState([
            'name' => $row['PLUGIN_NAME'] ?? $row['Name'],
            'version' => $row['PLUGIN_VERSION'] ?? null,
            'status' => $row['PLUGIN_STATUS'] ?? $row['Status'],
            'type' => $row['PLUGIN_TYPE'] ?? $row['Type'],
            'typeVersion' => $row['PLUGIN_TYPE_VERSION'] ?? null,
            'library' => $row['PLUGIN_LIBRARY'] ?? $row['Library'] ?? null,
            'libraryVersion' => $row['PLUGIN_LIBRARY_VERSION'] ?? null,
            'author' => $row['PLUGIN_AUTHOR'] ?? null,
            'description' => $row['PLUGIN_DESCRIPTION'] ?? null,
            'license' => $row['PLUGIN_LICENSE'] ?? $row['License'],
            'loadOption' => $row['LOAD_OPTION'] ?? null,
            'maturity' => $row['PLUGIN_MATURITY'] ?? null,
            'authVersion' => $row['PLUGIN_AUTH_VERSION'] ?? null,
        ]);
    }
}
