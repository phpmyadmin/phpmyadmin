<?php

declare(strict_types=1);

namespace PhpMyAdmin\Server;

use PhpMyAdmin\Config;
use PhpMyAdmin\Current;
use PhpMyAdmin\Template;
use PhpMyAdmin\Url;
use PhpMyAdmin\Util;

use function count;
use function implode;
use function is_array;
use function str_contains;

/**
 * Displays the MySQL servers choice form
 */
class Select
{
    /**
     * Renders the server selection in list or selectbox form, or option tags only
     *
     * @param bool $notOnlyOptions whether to include form tags or not
     */
    public static function render(bool $notOnlyOptions): string
    {
        $config = Config::getInstance();
        // Show as list?
        if ($notOnlyOptions) {
            $list = $config->settings['DisplayServersList'];
            $notOnlyOptions = ! $list;
        } else {
            $list = false;
        }

        $formAction = '';
        if ($notOnlyOptions) {
            $formAction = Util::getScriptNameForOption($config->settings['DefaultTabServer'], 'server');
        }

        /** @var array{list: list<array<string, mixed>>, select: list<array<string, mixed>>} $servers */
        $servers = ['list' => [], 'select' => []];
        foreach ($config->settings['Servers'] as $key => $server) {
            if (empty($server['host'])) {
                continue;
            }

            $selected = Current::$server > 0 && Current::$server === $key;

            if (! empty($server['verbose'])) {
                $label = $server['verbose'];
            } else {
                $label = $server['host'];
                if (! empty($server['port'])) {
                    $label .= ':' . $server['port'];
                }
            }

            if (! empty($server['only_db'])) {
                if (! is_array($server['only_db'])) {
                    $label .= ' - ' . $server['only_db'];
                    // try to avoid displaying a too wide selector
                } elseif (count($server['only_db']) < 4) {
                    $label .= ' - ' . implode(', ', $server['only_db']);
                }
            }

            if (! empty($server['user']) && $server['auth_type'] === 'config') {
                $label .= '  (' . $server['user'] . ')';
            }

            if ($list) {
                if ($selected) {
                    $servers['list'][] = ['selected' => true, 'href' => '', 'label' => $label];
                } else {
                    $scriptName = Util::getScriptNameForOption($config->settings['DefaultTabServer'], 'server');
                    $href = $scriptName . Url::getCommon(
                        ['server' => $key],
                        ! str_contains($scriptName, '?') ? '?' : '&',
                    );
                    $servers['list'][] = ['selected' => false, 'href' => $href, 'label' => $label];
                }
            } else {
                $servers['select'][] = ['value' => $key, 'selected' => $selected, 'label' => $label];
            }
        }

        $template = new Template();

        return $template->render('server/select/index', [
            'not_only_options' => $notOnlyOptions,
            'servers' => $servers,
            'form_action' => $formAction,
        ]);
    }
}
