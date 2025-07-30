<?php

declare(strict_types=1);

namespace PhpMyAdmin;

use function array_key_exists;
use function md5;
use function str_contains;

/**
 * Collects information about which JavaScript
 * files and objects are necessary to render
 * the page and generates the relevant code.
 */
class Scripts
{
    /**
     * An array of SCRIPT tags
     *
     * @var array<string, array<string, int|string|array<string, string>>>
     * @psalm-var array<string, array{has_onload: 0|1, filename: non-empty-string, params: array<string, string>}>
     */
    private array $files = [];
    /**
     * A string of discrete javascript code snippets
     */
    private string $code = '';

    /**
     * Generates new Scripts objects
     */
    public function __construct(private readonly Template $template)
    {
    }

    /**
     * Adds a new file to the list of scripts
     *
     * @param string                $filename The name of the file to include
     * @param array<string, string> $params   Additional parameters to pass to the file
     */
    public function addFile(
        string $filename,
        array $params = [],
    ): void {
        $hash = md5($filename);
        if (array_key_exists($hash, $this->files) || $filename === '') {
            return;
        }

        $hasOnload = $this->hasOnloadEvent($filename);
        $this->files[$hash] = ['has_onload' => (int) $hasOnload, 'filename' => $filename, 'params' => $params];
    }

    /**
     * Add new files to the list of scripts
     *
     * @param string[] $filelist The array of file names
     */
    public function addFiles(array $filelist): void
    {
        foreach ($filelist as $filename) {
            $this->addFile($filename);
        }
    }

    /**
     * Determines whether to fire up an onload event for a file
     *
     * @param string $filename The name of the file to be checked against the exclude list.
     *
     * @return bool true to fire up the event, false not to
     */
    private function hasOnloadEvent(string $filename): bool
    {
        return ! str_contains($filename, 'vendor')
            && ! str_contains($filename, 'runtime.js')
            && ! str_contains($filename, 'index.php')
            && ! str_contains($filename, 'shared.js')
            && ! str_contains($filename, 'validator-messages.js');
    }

    /**
     * Adds a new code snippet to the code to be executed
     *
     * @param string $code The JS code to be added
     */
    public function addCode(string $code): void
    {
        $this->code .= $code . "\n";
    }

    /**
     * Returns a list with filenames and a flag to indicate
     * whether to register onload events for this file
     *
     * @return array<int, array<string, int|string>>
     * @psalm-return list<array{name: non-empty-string, fire: 0|1}>
     */
    public function getFiles(): array
    {
        $retval = [];
        foreach ($this->files as $file) {
            //If filename contains a "?", continue.
            if (str_contains($file['filename'], '?')) {
                continue;
            }

            $retval[] = ['name' => $file['filename'], 'fire' => $file['has_onload']];
        }

        return $retval;
    }

    /**
     * Renders all the JavaScript file inclusions, code and events
     */
    public function getDisplay(): string
    {
        return $this->template->render('scripts', ['files' => $this->files, 'code' => $this->code]);
    }
}
