<?php
/**
 * JavaScript management
 */

declare(strict_types=1);

namespace PhpMyAdmin;

use function defined;
use function md5;
use function strpos;

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
     * @access private
     * @var array of strings
     */
    private $files;
    /**
     * A string of discrete javascript code snippets
     *
     * @access private
     * @var string
     */
    private $code;

    /** @var Template */
    private $template;

    /**
     * Generates new Scripts objects
     */
    public function __construct()
    {
        $this->template = new Template();
        $this->files  = [];
        $this->code   = '';
    }

    /**
     * Adds a new file to the list of scripts
     *
     * @param string $filename The name of the file to include
     * @param array  $params   Additional parameters to pass to the file
     *
     * @return void
     */
    public function addFile(
        $filename,
        array $params = []
    ) {
        $hash = md5($filename);
        if (! empty($this->files[$hash])) {
            return;
        }

        $has_onload = $this->hasOnloadEvent($filename);
        $this->files[$hash] = [
            'has_onload' => $has_onload,
            'filename' => $filename,
            'params' => $params,
        ];
    }

    /**
     * Add new files to the list of scripts
     *
     * @param array $filelist The array of file names
     *
     * @return void
     */
    public function addFiles(array $filelist)
    {
        foreach ($filelist as $filename) {
            $this->addFile($filename);
        }
    }

    /**
     * Determines whether to fire up an onload event for a file
     *
     * @param string $filename The name of the file to be checked
     *                         against the exclude list.
     *
     * @return int 1 to fire up the event, 0 not to
     */
    private function hasOnloadEvent($filename)
    {
        if (strpos($filename, 'jquery') !== false
            || strpos($filename, 'codemirror') !== false
            || strpos($filename, 'messages.php') !== false
            || strpos($filename, 'ajax.js') !== false
            || strpos($filename, 'cross_framing_protection.js') !== false
        ) {
            return 0;
        }

        return 1;
    }

    /**
     * Adds a new code snippet to the code to be executed
     *
     * @param string $code The JS code to be added
     *
     * @return void
     */
    public function addCode($code)
    {
        $this->code .= $code . "\n";
    }

    /**
     * Returns a list with filenames and a flag to indicate
     * whether to register onload events for this file
     *
     * @return array
     */
    public function getFiles()
    {
        $retval = [];
        foreach ($this->files as $file) {
            //If filename contains a "?", continue.
            if (strpos($file['filename'], '?') !== false) {
                continue;
            }
            $retval[] = [
                'name' => $file['filename'],
                'fire' => $file['has_onload'],
            ];
        }

        return $retval;
    }

    /**
     * Renders all the JavaScript file inclusions, code and events
     *
     * @return string
     */
    public function getDisplay()
    {
        $baseDir = defined('PMA_PATH_TO_BASEDIR') ? PMA_PATH_TO_BASEDIR : '';

        return $this->template->render('scripts', [
            'base_dir' => $baseDir,
            'files' => $this->files,
            'version' => PMA_VERSION,
            'code' => $this->code,
        ]);
    }
}
