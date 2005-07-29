<?php
/* $Id$ */
// vim: expandtab sw=4 ts=4 sts=4:

if (isset($GLOBALS['DBG']) && $GLOBALS['DBG']
        && isset($GLOBALS['cfg']['DBG']['profile']['enable'])
        && $GLOBALS['cfg']['DBG']['profile']['enable']) {

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
        $dbg_prof_results = ""; // gma added var
        dbg_get_profiler_results($dbg_prof_results);
        $cwdlen =  strlen(getcwd()); // gma added var, $cwdlen =  strlen(getcwd());
        echo '<br /><table width="1000" cellspacing="0" cellpadding="2" style="font:8pt courier">' . "\n" .
            '<thead>' . "\n";  // gma change "." to ";"
        $tr = '<tr style="background:#808080; color:#FFFFFF">' . "\n" . // gma added "$tr ="
            '<td>' . $GLOBALS['strDBGModule'] . '</td>' . "\n" .
            '<td>' . $GLOBALS['strDBGLine'] . '</td>' . "\n" .
            '<td>' . $GLOBALS['strDBGHits'] . '</td>' . "\n" .
            '<td>' . $GLOBALS['strDBGTimePerHitMs'] . '</td>' . "\n" .
            '<td>' . $GLOBALS['strDBGTotalTimeMs'] . '</td>' . "\n" .
            '<td>' . $GLOBALS['strDBGMinTimeMs'] . '</td>' . "\n" .
            '<td>' . $GLOBALS['strDBGMaxTimeMs'] . '</td>' . "\n" .
            '<td>' . $GLOBALS['strDBGContextID'] . '</td>' . "\n" .
            '<td>' . $GLOBALS['strDBGContext'] . '</td>' . "\n" .
            '</tr></thead>' . "\n";  // gma change "." to ";"
        echo $tr.'<tbody style="vertical-align: top">' . "\n";  $lines = 0; // gma added "echo $tr." and "$lines = 0;"
        foreach ($dbg_prof_results['line_no'] AS $idx => $line_no) { $lines++; // gma added "$lines++;"
            $mod_no = $dbg_prof_results['mod_no'][$idx];
            $mod_name = ""; // gma added var
            dbg_get_module_name($mod_no, $mod_name);

            //if (strpos("!".$mod_name, 'dbg.php') > 0) continue;

            $hit_cnt = $dbg_prof_results['hit_count'][$idx];

            $time_sum = $dbg_prof_results['tm_sum'][$idx] * 1000;
            $time_avg_hit = $time_sum / $hit_cnt;
            $time_min = $dbg_prof_results['tm_min'][$idx] * 1000;
            $time_max = $dbg_prof_results['tm_max'][$idx] * 1000;

            $time_sum = sprintf('%.3f', $time_sum);
            $time_avg_hit = sprintf('%.3f', $time_avg_hit);
            $time_min = sprintf('%.3f', $time_min);
            $time_max = sprintf('%.3f', $time_max);
            $ctx_id = ""; // gma added var
            dbg_get_source_context($mod_no, $line_no, $ctx_id);

            //use a default context name if needed
            $ctx_name = ""; // gma added var
            if (dbg_get_context_name($ctx_id, $ctx_name)
                    && strcmp($ctx_name,'') == 0) {
                $ctx_name = "::main";
            }

            $bk = "#ffffff";
            if (($idx & 1) == 0)
                $bk = "#e0e0e0";

            if ($time_avg_hit > $GLOBALS['cfg']['DBG']['profile']['threshold'] ) {
                echo '<tr style="background:' . $bk . '">' .
                    '<td>' . substr($mod_name,$cwdlen+1) . '</td>' .  // gma changed "$mod_name" to "substr($mod_name,$cwdlen+1)"
                    '<td>' . $line_no . '</td>' .
                    '<td>' . $hit_cnt . '</td>' .
                    '<td>' . $time_avg_hit . '</td>' .
                    '<td>' . $time_sum . '</td>' .
                    '<td>' . $time_min . '</td>' .
                    '<td>' . $time_max . '</td>' .
                    '<td>' . $ctx_id . '</td>' .
                    '<td>' . $ctx_name . '</td>' .
                    '</tr>' . "\n";
                if($lines == 20) { $lines = 0; echo $tr;}  // gma added line. Repeats the header every x lines.
            }
        }
        echo "</tbody></table>";
    }
}
/*  gma ... Developer Notes
These two scriptlets can be used as On/Off buttons in your browsers, add to links. 
ON:
javascript: document.cookie = 'DBGSESSID=' + escape('1;d=1,p=1') + '; path=/'; document.execCommand('refresh'); 
or ... javascript: document.cookie = 'DBGSESSID=' + escape('1;d=0,p=1') + '; path=/'; document.execCommand('refresh');
OFF:
javascript: document.cookie = 'DBGSESSID=' + escape('1;d=0,p=0') + '; path=/'; document.execCommand('refresh');
*/
?>
