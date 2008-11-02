<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * @author      Raj Kissu Rajandran
 * @version     1.0
 * @package     BLOBStreaming
 */

set_time_limit(0);

$filename = isset($_REQUEST['file_path']) ? $_REQUEST['file_path'] : NULL;
$c_type = isset($_REQUEST['c_type']) ? $_REQUEST['c_type'] : NULL;

if (isset($filename) && isset($c_type))
{
	$hdrs = get_headers($filename, 1);

	if (is_array($hdrs))
		$f_size = $hdrs['Content-Length'];

	header("Expires: 0");
	header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
	header("Cache-Control: no-store, no-cache, must-revalidate");
	header("Cache-Control: post-check=0, pre-check=0", false);
	header("Pragma: no-cache");
	header("Content-type: $c_type");
	header('Content-length: ' . $f_size);
	header("Content-disposition: attachment; filename=" . basename($filename));

	$fHnd = fopen($filename, "rb");

	if ($fHnd)
	{
		$pos = 0;
		$content = "";

		while (!feof($fHnd))
		{
			$content .= fread($fHnd, $f_size);
			$pos = strlen($content);

			if ($pos >= $f_size)
				break;
		}

		echo $content;
		flush();

		fclose($fHnd);
	}
}
?>
