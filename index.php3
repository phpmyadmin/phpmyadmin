<?php
// Process config file to determine default server (if any)
require('./grab_globals.inc.php3');
require('./lib.inc.php3');


// Get the host name
if (empty($HTTP_HOST)) {
  if (!empty($HTTP_ENV_VARS) && isset($HTTP_ENV_VARS['HTTP_HOST'])) {
    $HTTP_HOST = $HTTP_ENV_VARS['HTTP_HOST'];
  }
  else if (@getenv('HTTP_HOST')) {
    $HTTP_HOST = getenv('HTTP_HOST');
  }
}
?>

<html>
<head>
<title>phpMyAdmin <?php echo PHPMYADMIN_VERSION; ?> - <?php echo $HTTP_HOST;?></title>
</head>

<frameset cols="150,*" rows="*" border="0" frameborder="0"> 
  <frame src="left.php3?server=<?php echo $server;?>&lang=<?php echo $lang; echo (empty($db)) ? '' : '&db=' . urlencode($db); ?>" name="nav">
  <frame src="<?php echo (empty($db)) ? 'main.php3' : 'db_details.php3'; ?>?server=<?php echo $server;?>&lang=<?php echo $lang; echo (empty($db)) ? '' : '&db=' . urlencode($db); ?>" name="phpmain">
</frameset>
<noframes>
<body bgcolor="#FFFFFF">

</body>
</noframes>
</html>
