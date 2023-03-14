<?php

declare(strict_types=1);

namespace PhpMyAdmin;

use function defined;
use function flush;
use function function_exists;
use function header;
use function in_array;
use function ini_get;
use function ob_end_clean;
use function ob_flush;
use function ob_get_contents;
use function ob_get_length;
use function ob_get_level;
use function ob_get_status;
use function ob_list_handlers;
use function ob_start;
use function sprintf;

/**
 * Output buffering wrapper class
 */
class OutputBuffering
{
    private int $mode;

    private string $content = '';

    private bool $on = false;

    public function __construct()
    {
        $this->mode = $this->getMode();
    }

    /**
     * This function could be used eventually to support more modes.
     *
     * @return int the output buffer mode
     */
    private function getMode(): int
    {
        if (! defined('TESTSUITE') && $GLOBALS['cfg']['OBGzip']) {
            if (ini_get('output_handler') === 'ob_gzhandler') {
                // If a user sets the output_handler in php.ini to ob_gzhandler, then
                // any right frame file in phpMyAdmin will not be handled properly by
                // the browser. My fix was to check the ini file within the
                // PMA_outBufferModeGet() function.
                return 0;
            }

            if (ob_get_level() > 0) {
                // happens when php.ini's output_buffering is not Off
                ob_end_clean();

                return 1;
            }

            return 1;
        }

        // Zero (0) is no mode or in other words output buffering is OFF.
        // Follow 2^0, 2^1, 2^2, 2^3 type values for the modes.
        // Useful if we ever decide to combine modes.  Then a bitmask field of
        // the sum of all modes will be the natural choice.
        return 0;
    }

    /**
     * This function will need to run at the top of all pages if output
     * output buffering is turned on.  It also needs to be passed $mode from
     * the PMA_outBufferModeGet() function or it will be useless.
     */
    public function start(): void
    {
        if (defined('TESTSUITE') || $this->on) {
            return;
        }

        if ($this->mode && function_exists('ob_gzhandler') && ! in_array('ob_gzhandler', ob_list_handlers())) {
            ob_start('ob_gzhandler');
        }

        ob_start();
        $this->sendHeader('X-ob_mode', (string) $this->mode);

        $this->on = true;
    }

    private function sendHeader(string $name, string $value): void
    {
        if (defined('TESTSUITE')) {
            return;
        }

        header(sprintf('%s: %s', $name, $value));
    }

    /**
     * This function will need to run at the bottom of all pages if output
     * buffering is turned on.  It also needs to be passed $mode from the
     * PMA_outBufferModeGet() function or it will be useless.
     */
    public function stop(): void
    {
        if (! $this->on) {
            return;
        }

        $this->on = false;
        $this->content = (string) ob_get_contents();
        if (ob_get_length() <= 0) {
            return;
        }

        ob_end_clean();
    }

    /**
     * Gets buffer content
     *
     * @return string buffer content
     */
    public function getContents(): string
    {
        return $this->content;
    }

    /**
     * Flushes output buffer
     */
    public function flush(): void
    {
        if (ob_get_status() && $this->mode) {
            ob_flush();
        } else {
            flush();
        }
    }
}
