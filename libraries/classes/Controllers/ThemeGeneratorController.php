<?php
/**
 * Theme generator tool
 */

declare(strict_types=1);

namespace PhpMyAdmin\Controllers;

use PhpMyAdmin\Message;
use PhpMyAdmin\Sanitize;
use PhpMyAdmin\ThemeGenerator;
use function sprintf;
use function file_exists;
use function file_put_contents;
use const LOCK_EX;
use function json_encode;
use function is_dir;

class ThemeGeneratorController extends AbstractController
{
    public function index(): void
    {
        global $cfg;
        if (! $cfg['ThemeGenerator']) {
            return;
        }

        if (! ThemeGenerator::isThemeDirectoryWritable()) {
            $this->response->setRequestStatus(false);
                $this->response->addJSON(
                    [
                        'message' => Message::error(
                            sprintf(
                                'The "%s" directory is not writable by the webserver process.'
                                . ' You must change permissions for the theme generator'
                                . ' to be able to write the generated theme.',
                                ROOT_PATH . 'themes/'
                            )
                        ),
                    ]
                );

                return;
        }

        $theme = new ThemeGenerator();
        $sassData = $theme->getScssFile();

        $this->addScriptFiles([
            'vendor/sass.js/sass.sync.js',
            'vendor/mozilla-color-picker.js',
            'theme_generator/functions.js',
            'theme_generator/preview.js',
        ]);

        $this->render('theme_generator/index', [
            'sass_config' => json_encode($sassData),
        ]);
    }

    public function save(): void
    {
        $name = Sanitize::sanitizeFilename($_POST['name'], true);
        if ($name === '') {
            $this->response->setRequestStatus(false);
            $this->response->addJSON(
                [
                    'message' => Message::error('The theme name can not be empty.'),
                ]
            );

            return;
        }

        $couldBeBetterFileChooser = (int) $_POST['count'];

        // Array related with generateFiles array in generate_css.js
        $generateFiles = ['printview.css', 'theme.css', 'theme-rtl.css'];

        if ($couldBeBetterFileChooser === 0) {
            (new ThemeGenerator())->createFileStructure($name);
        }
        $fileName = $generateFiles[$couldBeBetterFileChooser];
        $contents = $_POST['CSS_file'];
        $themeFolderPath = ROOT_PATH . 'themes/' . $name;

        if (! is_dir($themeFolderPath)) {
            $this->response->setRequestStatus(false);
            $this->response->addJSON(
                [
                    'message' => Message::error(
                        sprintf(
                            'The "%s" folder does not exist.',
                            $themeFolderPath
                        )
                    ),
                ]
            );

            return;
        }

        if ($name === '') {
            $this->response->setRequestStatus(false);
            $this->response->addJSON(
                [
                    'message' => Message::error('The theme name can not be empty.'),
                ]
            );

            return;
        }

        $path = $themeFolderPath . '/css/' . $fileName;

        if (! file_exists($path)) {
            $file = file_put_contents($path, $contents, LOCK_EX);

            // Check if the file is successfully written
            if ($file === false) {
                $this->response->setRequestStatus(false);
                $this->response->addJSON(
                    [
                        'message' => Message::error(
                            sprintf(
                                'The "%s" file is not writable by the webserver process.'
                                . ' You must change permissions for the theme generator'
                                . ' to be able to write the generated theme.',
                                $path
                            )
                        ),
                    ]
                );

                return;
            }
        }

        $this->response->setRequestStatus(true);
        $this->response->addJSON(
            [
                'message' => Message::success(
                    sprintf(
                        __('Theme saved, go to the %smain page%s to try it'),
                        '<a href="index.php?route=/" class="ajax">',
                        '</a>'
                    )
                ),
            ]
        );
    }
}
