<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Abstract class for the external transformations plugins
 *
 * @package    PhpMyAdmin-Transformations
 * @subpackage External
 */
namespace PhpMyAdmin\Plugins\Transformations\Abs;

use PhpMyAdmin\Plugins\TransformationsPlugin;

/**
 * Provides common methods for all of the external transformations plugins.
 *
 * @package PhpMyAdmin
 */
abstract class ExternalTransformationsPlugin extends TransformationsPlugin
{
    /**
     * Gets the transformation description of the specific plugin
     *
     * @return string
     */
    public static function getInfo()
    {
        return __(
            'LINUX ONLY: Launches an external application and feeds it the column'
            . ' data via standard input. Returns the standard output of the'
            . ' application. The default is Tidy, to pretty-print HTML code.'
            . ' For security reasons, you have to manually edit the file'
            . ' libraries/classes/Plugins/Transformations/Output/Text_Plain_External'
            . '.php and list the tools you want to make available.'
            . ' The first option is then the number of the program you want to'
            . ' use and the second option is the parameters for the program.'
            . ' The third option, if set to 1, will convert the output using'
            . ' htmlspecialchars() (Default 1). The fourth option, if set to 1,'
            . ' will prevent wrapping and ensure that the output appears all on'
            . ' one line (Default 1).'
        );
    }

    /**
     * Enables no-wrapping
     *
     * @param array $options transformation options
     *
     * @return bool
     */
    public function applyTransformationNoWrap(array $options = array())
    {
        if (!isset($options[3]) || $options[3] == '') {
            $nowrap = true;
        } elseif ($options[3] == '1' || $options[3] == 1) {
            $nowrap = true;
        } else {
            $nowrap = false;
        }

        return $nowrap;
    }

    /**
     * Does the actual work of each specific transformations plugin.
     *
     * @param string $buffer  text to be transformed
     * @param array  $options transformation options
     * @param string $meta    meta information
     *
     * @return string
     */
    public function applyTransformation($buffer, array $options = array(), $meta = '')
    {
        // possibly use a global transform and feed it with special options

        // further operations on $buffer using the $options[] array.

        $allowed_programs = array();

        //
        // WARNING:
        //
        // It's up to administrator to allow anything here. Note that users may
        // specify any parameters, so when programs allow output redirection or
        // any other possibly dangerous operations, you should write wrapper
        // script that will publish only functions you really want.
        //
        // Add here program definitions like (note that these are NOT safe
        // programs):
        //
        //$allowed_programs[0] = '/usr/local/bin/tidy';
        //$allowed_programs[1] = '/usr/local/bin/validate';

        // no-op when no allowed programs
        if (count($allowed_programs) == 0) {
            return $buffer;
        }

        $cfg = $GLOBALS['cfg'];
        $options = $this->getOptions(
            $options,
            $cfg['DefaultTransformations']['External']
        );

        if (isset($allowed_programs[$options[0]])) {
            $program = $allowed_programs[$options[0]];
        } else {
            $program = $allowed_programs[0];
        }

        // needs PHP >= 4.3.0
        $newstring = '';
        $descriptorspec = array(
            0 => array("pipe", "r"),
            1 => array("pipe", "w"),
        );
        $process = proc_open($program . ' ' . $options[1], $descriptorspec, $pipes);
        if (is_resource($process)) {
            fwrite($pipes[0], $buffer);
            fclose($pipes[0]);

            while (!feof($pipes[1])) {
                $newstring .= fgets($pipes[1], 1024);
            }
            fclose($pipes[1]);
            // we don't currently use the return value
            proc_close($process);
        }

        if ($options[2] == 1 || $options[2] == '2') {
            $retstring = htmlspecialchars($newstring);
        } else {
            $retstring = $newstring;
        }

        return $retstring;
    }


    /* ~~~~~~~~~~~~~~~~~~~~ Getters and Setters ~~~~~~~~~~~~~~~~~~~~ */

    /**
     * Gets the transformation name of the specific plugin
     *
     * @return string
     */
    public static function getName()
    {
        return "External";
    }
}
