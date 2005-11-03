<?php
/* $Id$ */
// vim: expandtab sw=4 ts=4 sts=4:
/**
 * holds function for dumping profiling data
 * 
 * allways use $GLOBALS here, as this script is included by footer.inc.hp
 * which can also be included from inside a function
 */

if ( ! empty( $GLOBALS['DBG'] ) 
  && $GLOBALS['cfg']['DBG']['profile']['enable'] ) {

    /**
     * Displays profiling results when called
     * WARNING: this function is SLOW
     */
    function dbg_dump_profiling_results() {
        /* Applies to the original 'dbg_dump_profiling_results' function,
         * sourced from http://dd.cron.ru/dbg/download.php?h=prof_sample2
         * Copyright (c) 2002. Dmitri Dmitrienko
         * LICENCE: This source file is subject to Mozilla Public License (MPL)
         * AUTHOR: Dmitri Dmitrienko <dd@cron.ru>
         */
        dbg_get_profiler_results( $dbg_prof_results = '' );
        $cwdlen =  strlen(getcwd()); // gma added var, $cwdlen =  strlen(getcwd());
        echo '<br /><table xml:lang="en" dir="ltr" width="1000" cellspacing="0" cellpadding="2" style="font:8pt courier">' . "\n" .
            '<thead>' . "\n";
        // gma added "$tr ="
        $tr = '<tr>' . "\n" .
            '<th>' . $GLOBALS['strDBGModule'] . '</th>' . "\n" .
            '<th>' . $GLOBALS['strDBGLine'] . '</th>' . "\n" .
            '<th>' . $GLOBALS['strDBGHits'] . '</th>' . "\n" .
            '<th>' . $GLOBALS['strDBGTimePerHitMs'] . '</th>' . "\n" .
            '<th>' . $GLOBALS['strDBGTotalTimeMs'] . '</th>' . "\n" .
            '<th>' . $GLOBALS['strDBGMinTimeMs'] . '</th>' . "\n" .
            '<th>' . $GLOBALS['strDBGMaxTimeMs'] . '</th>' . "\n" .
            '<th>' . $GLOBALS['strDBGContextID'] . '</th>' . "\n" .
            '<th>' . $GLOBALS['strDBGContext'] . '</th>' . "\n" .
            '</tr>' . "\n";  // gma change "." to ";"
        echo $tr.'</thead><tbody style="vertical-align: top">' . "\n";
        $lines      = 0; // gma added "echo $tr." and "$lines = 0;"
        $ctx_name   = '';
        $mod_name   = '';
        $ctx_id     = '';
        $odd_row    = true;
        foreach ( $dbg_prof_results['line_no'] as $idx => $line_no ) {
            dbg_get_module_name( $dbg_prof_results['mod_no'][$idx], $mod_name );

            //if (strpos("!".$mod_name, 'dbg.php') > 0) continue;

            $time_sum = $dbg_prof_results['tm_sum'][$idx] * 1000;
            $time_avg_hit = $time_sum / $dbg_prof_results['hit_count'][$idx];

            $time_sum = sprintf( '%.3f', $time_sum );
            $time_avg_hit = sprintf('%.3f', $time_avg_hit);
            $time_min = sprintf( '%.3f', $dbg_prof_results['tm_min'][$idx] * 1000 );
            $time_max = sprintf( '%.3f', $dbg_prof_results['tm_max'][$idx] * 1000 );
            dbg_get_source_context( $dbg_prof_results['mod_no'][$idx],
                $line_no, $ctx_id );

            // use a default context name if needed
            if ( dbg_get_context_name( $ctx_id, $ctx_name )
                    && strlen($ctx_name) == 0 ) {
                $ctx_name = "::main";
            }

            if ( $time_avg_hit > $GLOBALS['cfg']['DBG']['profile']['threshold'] ) {
                echo '<tr class="' . $odd_row ? 'odd' : 'even' . '">' .
                    // gma changed "$mod_name" to "substr($mod_name,$cwdlen+1)"
                    '<td>' . substr($mod_name,$cwdlen+1) . '</td>' .
                    '<td>' . $line_no . '</td>' .
                    '<td>' . $dbg_prof_results['hit_count'][$idx] . '</td>' .
                    '<td>' . $time_avg_hit . '</td>' .
                    '<td>' . $time_sum . '</td>' .
                    '<td>' . $time_min . '</td>' .
                    '<td>' . $time_max . '</td>' .
                    '<td>' . $ctx_id . '</td>' .
                    '<td>' . $ctx_name . '</td>' .
                    '</tr>' . "\n";
                
                // gma added line. Repeats the header every x lines.
                if ( $lines === 19 ) {
                    $odd_row    = true;
                    $lines      = 0;
                    echo $tr;
                } else {
                    $odd_row    = ! $odd_row;
                    $lines++;
                }
            }
        }
        echo '</tbody></table>';
    }
}
/*  gma ... Developer Notes
These two scriptlets can be used as On/Off buttons in your browsers, add to links. 
ON:
javascript: document.cookie = 'DBGSESSID=' + escape('1;d=1,p=1') + '; path=/'; document.execCommand('refresh'); 
or ... 
javascript: document.cookie = 'DBGSESSID=' + escape('1;d=0,p=1') + '; path=/'; document.execCommand('refresh');
OFF:
javascript: document.cookie = 'DBGSESSID=' + escape('1;d=0,p=0') + '; path=/'; document.execCommand('refresh');
*/
?>
