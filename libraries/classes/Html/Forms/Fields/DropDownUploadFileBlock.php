<?php
/**
 * HTML Generator for drop down for file upload
 */
declare(strict_types=1);

namespace PhpMyAdmin\Html\Forms\Fields;

use PhpMyAdmin\FileListing;
use PhpMyAdmin\Message;
use PhpMyAdmin\Plugins\ImportPlugin;
use PhpMyAdmin\Util;

/**
 * * HTML Generator for drop down for file upload
 */
class DropDownUploadFileBlock implements FieldGenerator
{
    /**
     * Prepare the form used to select a file to import from the server upload
     * directory
     *
     * @param ImportPlugin[] $import_list array of import plugins
     * @param string         $uploaddir   upload directory
     *
     * @return string
     */
    public static function generate($import_list, $uploaddir): string
    {
        $fileListing = new FileListing();

        $block_html = '';
        $block_html .= '<label for="radio_local_import_file">'
            . sprintf(
                __('Select from the web server upload directory <b>%s</b>:'),
                htmlspecialchars(Util::userDir($uploaddir))
            )
            . '</label>';

        $extensions = '';
        foreach ($import_list as $import_plugin) {
            if (! empty($extensions)) {
                $extensions .= '|';
            }
            $extensions .= $import_plugin->getProperties()
                ->getExtension();
        }

        $matcher = '@\.(' . $extensions . ')(\.('
            . $fileListing->supportedDecompressions() . '))?$@';

        $active = isset($GLOBALS['timeout_passed'], $GLOBALS['local_import_file']) && $GLOBALS['timeout_passed']
            ? $GLOBALS['local_import_file']
            : '';

        $files = $fileListing->getFileSelectOptions(
            Util::userDir($uploaddir),
            $matcher,
            $active
        );

        if ($files === false) {
            Message::error(
                __('The directory you set for upload work cannot be reached.')
            )
                ->display();
        } elseif (! empty($files)) {
            $block_html .= "\n"
                . '    <select style="margin: 5px" size="1" '
                . 'name="local_import_file" '
                . 'id="select_local_import_file">' . "\n"
                . '        <option value="">&nbsp;</option>' . "\n"
                . $files
                . '    </select>' . "\n";
        } elseif (empty($files)) {
            $block_html .= '<i>' . __('There are no files to upload!') . '</i>';
        }

        return $block_html;
    }
}
