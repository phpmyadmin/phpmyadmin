<?php
/* $Id$ */

/** Parser BUG decoder
 * This is the parser bug decoder system
 * Throw the bug data in teh query box, and hit submit for output.
 * 
 * Copyright 2002 Robin Johnson <robbat2@users.sourceforge.net>
 */
?>
<html>
<h4>Parser BUG decoder</h4>
<form method="post" action="decode_bug.php3">
<textarea name="foo" cols="72" rows="10">
</textarea>
<br />
<input type="submit" />
<input type="hidden" name="bar" value="<?php echo rand(); ?>" />
</form>

<hr />
<?php
if(isset($_REQUEST['foo']))
{
    $foo = $_REQUEST['foo'];
} else {
    $foo = "";
}

$foo = eregi_replace("[[:space:]]", "", $foo);
$bar = base64_decode($foo);
if(substr($foo,0,2) == 'eN')
{
    $bar = gzuncompress($bar);
}
echo "Decoded:<br />".$bar."<br />";

?>
</html>
