<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Parser bug report decoder
 *
 * This is the parser bug decoder system
 * Throw the bug data in the query box, and hit submit for output.
 *
 * Copyright 2002 Robin Johnson <robbat2@users.sourceforge.net>
 *
 * @package PhpMyAdmin-debug
 */

/**
 * Displays the form
 */
?>
<!DOCTYPE HTML>
<html xmlns="http://www.w3.org/1999/xhtml" lang="en-US" dir="ltr">

<head>
    <link rel="icon" href="favicon.ico" type="image/x-icon" />
    <link rel="shortcut icon" href="favicon.ico" type="image/x-icon" />
    <meta charset="iso-8859-1" />
    <title>phpMyAdmin - Parser bug report decoder</title>
    <style type="text/css">
    <!--
    body, p {
        font-family: Arial, Helvetica, sans-serif;
        font-size:   medium;
    }
    h1 {
        font-family: Verdana, Arial, Helvetica, sans-serif;
        font-size:   large;
        font-weight: bold;
        color:       #000066;
    }
    //-->
    </style>
</head>


<body bgcolor="#FFFFFF">
<h1>Parser bug report decoder</h1>
<br />

<form method="post" action="./decode_bug.php">
    <input type="hidden" name="bar" value="<?php echo rand(); ?>" />
    Encoded bug report:<br />
    <textarea name="bug_encoded" cols="72" rows="10"></textarea>
    <br /><br />
    <input type="submit" />
</form>
<hr />

<?php
/**
 * If the form has been submitted -> decodes the bug report
 */

/**
 * Display the decoded bug report in ASCII format
 *
 * @param string $textdata the text data
 *
 * @return string  the text enclosed by "<pre>...</pre>" tags
 *
 * @access public
 */
function PMA_printDecodedBug($textdata)
{
    return '<pre>' . htmlspecialchars($textdata) . '</pre><br />';
} // end of the "PMA_printDecodedBug()" function


if (!empty($_POST) && isset($_POST['bug_encoded'])) {
    $bug_encoded = $_POST['bug_encoded'];
}

if (!empty($bug_encoded) && is_string($bug_encoded)) {
    if (function_exists('get_magic_quotes_gpc') && get_magic_quotes_gpc()) {
        $bug_encoded = stripslashes($bug_encoded);
    }

    /** @var PMA_String $pmaString */
    $pmaString = $GLOBALS['PMA_String'];

    $bug_encoded     = preg_replace('/[[:space:]]/', '', $bug_encoded);
    $bug_decoded     = base64_decode($bug_encoded);
    if (/*overload*/mb_substr($bug_encoded, 0, 2) == 'eN') {
        if (function_exists('gzuncompress')) {
            $result  = PMA_printDecodedBug(gzuncompress($bug_decoded));
        } else {
            $result  = 'Error: &quot;gzuncompress()&quot; is unavailable!' . "\n";
        }
    } else {
        $result  = PMA_printDecodedBug($bug_decoded);
    } // end if... else...

    echo '<p>Decoded:</p>' . "\n"
         . $result . "\n";
} // end if
?>
</body>

</html>
