<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * This library is used with the server IP allow/deny host authentication
 * feature
 *
 * @version $Id$
 * @package phpMyAdmin
 */


/**
 * Gets the "true" IP address of the current user
 *
 * @return  string   the ip of the user
 *
 * @access  private
 */
function PMA_getIp()
{
    /* Get the address of user */
    if (!empty($_SERVER['REMOTE_ADDR'])) {
        $direct_ip = $_SERVER['REMOTE_ADDR'];
    } else {
        /* We do not know remote IP */
        return false;
    }

    /* Do we trust this IP as a proxy? If yes we will use it's header. */
    if (isset($GLOBALS['cfg']['TrustedProxies'][$direct_ip])) {
        $trusted_header_value = PMA_getenv($GLOBALS['cfg']['TrustedProxies'][$direct_ip]);
        $matches = array();
        // the $ checks that the header contains only one IP address, ?: makes sure the () don't capture
        $is_ip = preg_match('|^(?:[0-9]{1,3}\.){3,3}[0-9]{1,3}$|', $trusted_header_value, $matches);
        if ($is_ip && (count($matches) == 1)) {
            // True IP behind a proxy
            return $matches[0];
        }
    }

    /* Return true IP */
    return $direct_ip;
} // end of the 'PMA_getIp()' function


/**
 * Based on IP Pattern Matcher
 * Originally by J.Adams <jna@retina.net>
 * Found on <http://www.php.net/manual/en/function.ip2long.php>
 * Modified by Robbat2 <robbat2@users.sourceforge.net>
 *
 * Matches:
 * xxx.xxx.xxx.xxx        (exact)
 * xxx.xxx.xxx.[yyy-zzz]  (range)
 * xxx.xxx.xxx.xxx/nn     (CIDR)
 *
 * Does not match:
 * xxx.xxx.xxx.xx[yyy-zzz]  (range, partial octets not supported)
 *
 * @param   string   string of IP range to match
 * @param   string   string of IP to test against range
 *
 * @return  boolean    always true
 *
 * @access  public
 */
function PMA_ipMaskTest($testRange, $ipToTest)
{
   $result = true;

   if (preg_match('|([0-9]+)\.([0-9]+)\.([0-9]+)\.([0-9]+)/([0-9]+)|', $testRange, $regs)) {
       // performs a mask match
       $ipl    = ip2long($ipToTest);
       $rangel = ip2long($regs[1] . '.' . $regs[2] . '.' . $regs[3] . '.' . $regs[4]);

       $maskl  = 0;

       for ($i = 0; $i < 31; $i++) {
           if ($i < $regs[5] - 1) {
               $maskl = $maskl + PMA_pow(2, (30 - $i));
           } // end if
       } // end for

       if (($maskl & $rangel) == ($maskl & $ipl)) {
           return true;
       } else {
           return false;
       }
   } else {
       // range based
       $maskocts = explode('.', $testRange);
       $ipocts   = explode('.', $ipToTest);

       // perform a range match
       for ($i = 0; $i < 4; $i++) {
            if (preg_match('|\[([0-9]+)\-([0-9]+)\]|', $maskocts[$i], $regs)) {
                if (($ipocts[$i] > $regs[2])
                    || ($ipocts[$i] < $regs[1])) {
                    $result = false;
                } // end if
            } else {
                if ($maskocts[$i] <> $ipocts[$i]) {
                    $result = false;
                } // end if
            } // end if/else
       } //end for
   } //end if/else

   return $result;
} // end of the "PMA_IPMaskTest()" function


/**
 * Runs through IP Allow/Deny rules the use of it below for more information
 *
 * @param   string 'allow' | 'deny' type of rule to match
 *
 * @return  bool   Matched a rule ?
 *
 * @access  public
 *
 * @see     PMA_getIp()
 */
function PMA_allowDeny($type)
{
    global $cfg;

    // Grabs true IP of the user and returns if it can't be found
    $remote_ip = PMA_getIp();
    if (empty($remote_ip)) {
        return false;
    }

    // copy username
    $username  = $cfg['Server']['user'];

    // copy rule database
    $rules     = $cfg['Server']['AllowDeny']['rules'];

    // lookup table for some name shortcuts
    $shortcuts = array(
        'all'       => '0.0.0.0/0',
        'localhost' => '127.0.0.1/8'
    );

    // Provide some useful shortcuts if server gives us address:
    if (PMA_getenv('SERVER_ADDR')) {
        $shortcuts['localnetA'] = PMA_getenv('SERVER_ADDR') . '/8';
        $shortcuts['localnetB'] = PMA_getenv('SERVER_ADDR') . '/16';
        $shortcuts['localnetC'] = PMA_getenv('SERVER_ADDR') . '/24';
    }

    foreach ($rules as $rule) {
        // extract rule data
        $rule_data = explode(' ', $rule);

        // check for rule type
        if ($rule_data[0] != $type) {
            continue;
        }

        // check for username
        if (($rule_data[1] != '%') //wildcarded first
            && ($rule_data[1] != $username)) {
            continue;
        }

        // check if the config file has the full string with an extra
        // 'from' in it and if it does, just discard it
        if ($rule_data[2] == 'from') {
            $rule_data[2] = $rule_data[3];
        }

        // Handle shortcuts with above array
        if (isset($shortcuts[$rule_data[2]])) {
            $rule_data[2] = $shortcuts[$rule_data[2]];
        }

        // Add code for host lookups here
        // Excluded for the moment

        // Do the actual matching now
        if (PMA_ipMaskTest($rule_data[2], $remote_ip)) {
            return true;
        }
    } // end while

    return false;
} // end of the "PMA_AllowDeny()" function

?>
