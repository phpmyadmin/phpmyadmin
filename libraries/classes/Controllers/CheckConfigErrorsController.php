<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers;

use PhpMyAdmin\Config\Settings;

use function get_debug_type;
use function is_array;
use function is_readable;

use const CONFIG_FILE;

final class CheckConfigErrorsController extends AbstractController
{
    public function __invoke(): void
    {
        /** @var array<int|string, mixed> $cfg */
        $cfg = [];

        if (is_readable(CONFIG_FILE)) {
            include CONFIG_FILE;
        }

        $settings = new Settings($cfg);

        $errors = [];
        foreach ($cfg as $key => $value) {
            if (
                $key === 'Servers' || $key === 'Server' || $key === 'Export'
                || $key === 'Import' || $key === 'Schema' || $key === 'DefaultTransformations'
            ) {
                continue;
            }

            if ($settings->$key === $value) {
                continue;
            }

            if (is_array($settings->$key) && is_array($value)) {
                foreach ($value as $key2 => $value2) {
                    if ($settings->$key[$key2] === $value2) {
                        continue;
                    }

                    $errors[] = [
                        'keys' => [$key, $key2],
                        'expected' => [
                            'value' => $settings->$key[$key2],
                            'type' => get_debug_type($settings->$key[$key2]),
                        ],
                        'actual' => ['value' => $value2, 'type' => get_debug_type($value2)],
                    ];
                }

                continue;
            }

            $errors[] = [
                'keys' => [$key],
                'expected' => ['value' => $settings->$key, 'type' => get_debug_type($settings->$key)],
                'actual' => ['value' => $value, 'type' => get_debug_type($value)],
            ];
        }

        $this->render('check_config_errors', ['errors' => $errors]);
    }
}
