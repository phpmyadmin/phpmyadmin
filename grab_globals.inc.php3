<?php
/* $Id$ */;

/**
 * This library grabs the names and values of the variables sent or posted to a
 * script in the '$HTTP_*_VARS' arrays and sets simple globals variables from
 * them
 */
if (!empty($HTTP_GET_VARS)) {
	while(list($name, $value) = each($HTTP_GET_VARS))
		$$name = $value;
}

if (!empty($HTTP_POST_VARS)) {
	while(list($name, $value) = each($HTTP_POST_VARS))
		$$name = $value;
}

if (!empty($HTTP_POST_FILES)) {
	while(list($name, $value) = each($HTTP_POST_FILES))
		$$name = $value['tmp_name'];
}
?>