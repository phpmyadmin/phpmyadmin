<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * navigation css file from theme Original
 *
 * @package PhpMyAdmin-theme
 * @subpackage blueorange
 */


if (!defined('PMA_MINIMUM_COMMON') && !defined('TESTSUITE')) {
    exit();
}
?>
html {
  background-color: #ffffff;
    font-size: <?php echo (null !== $GLOBALS['PMA_Config']->get('fontsize') ? $GLOBALS['PMA_Config']->get('fontsize') : $_COOKIE['pma_fontsize']); ?>;
}

body {
    background:         <?php echo $GLOBALS['cfg']['NaviBackground']; ?>;
  background: none repeat scroll 0 0 #F3F3F3;
  border-color: #666699 #666699 #bbbbbb;
  border-style: solid none solid solid;
  border-width: 1px 0 1px 6px;
  
  	border-<?php echo $left; ?>:6px solid #666699;
  	border-<?php echo $right; ?>:0 none;
  
  border-radius: 0 0 10px 0;
  border-bottom-<?php echo $right?>-radius: 10px;
  border-bottom-<?php echo $left?>-radius: 0;
  box-shadow: -10px -20px 30px 0 rgba(50, 50, 50, 0.2) inset;
  box-shadow: -10px -20px 30px 0 rgba(50, 50, 50, 0.2) inset, 1px 0 0 0 #eeeeee inset, 20px 0 20px 0 rgba(50, 50, 150, 0.1) inset;
  
    box-shadow: <?php echo ($left=='right')? '':'-'; ?>10px -20px 30px 0 rgba(50, 50, 50, 0.2) inset, <?php echo ($left=='right')? '-':''; ?>1px 0 0 0 #EEEEEE inset, <?php echo ($left=='right')? '-':''; ?>20px 0 20px 0 rgba(50, 50, 150, 0.1) inset;
  
  <?php if (! empty($GLOBALS['cfg']['FontFamily'])) { ?>
      font-family:        <?php echo $GLOBALS['cfg']['FontFamily']; ?>;
  <?php
} ?>
  color: #666699;
  font-family: helvetica, sans-serif;
  margin: 0 10px 50px 0;
  
  	margin-<?php echo $right; ?>: 10px;
  	margin-<?php echo $left; ?>: 0;
  
  padding: 0;
}

img {
  border: 0 none;
  outline: 0 none;
  margin: 0;
  padding: 0;
}

a:link,
a:visited,
a:active {
  color: #333333;
  text-decoration: none;
  text-shadow: 0 1px 0 #ffffff;
}

ul {
  list-style-type: none;
}

ul#databaseList {
  	padding-<?php echo $left; ?>:0;
}

ul#databaseList li, div#left_tableList p {
  text-indent: 20px;
}

ul li a {
  padding: 5px;
  padding-left: 15px;
  display: block;
}

div#navidbpageselector a:hover,
ul#databaseList a:hover,
div#left_tableList p a:hover {
  background-color: #555555;
  box-shadow: 0 2px 5px 0 #000000 inset;
  color: #FFBB22;
  text-shadow: 0 1px 0 #000000;
}

div#navidbpageselector a,
ul#databaseList a,
div#left_tableList p a {
  background: url("./themes/blueorange/img/database.png") no-repeat scroll 10px 50% transparent;
  border-radius: 4px;
  color: #333333;
  margin: 0 10px 0 7px;
  	margin-<?php echo $left; ?>: 7px;
  	margin-<?php echo $right; ?>: 10px;
  text-shadow: 0 1px 0 #FFFFFF;
}

#pmalogo,
#leftframelinks,
#recentTableList {
  padding: 20px 5px 0;
}

#recentTableList {
  padding: 20px 0 0;
}

select {
  background-color: #555555;
  border-color: #DDDDDD #DDDDDD #666699;
  border-radius: 6px 4px 2px 2px;
  
  	border-top-<?php echo $left; ?>-radius: 6px;
  	border-top-<?php echo $right; ?>-radius: 4px;
  	border-bottom-<?php echo $right; ?>-radius: 2px;
  	border-bottom-<?php echo $left; ?>-radius: 2px;
  
  border-style: none solid solid;
  border-width: 0 0 2px 4px;
  
  	border-<?php echo $right; ?>-width: 0;
  	border-<?php echo $left; ?>-width: 4px;
  
  box-shadow: 1px 3px 9px 0 black inset, -1px 1px 0px 0px #fafafa;
  
    box-shadow: <?php echo ($left=='right')? '-':''; ?>1px 3px 9px 0 #000000 inset, <?php echo ($left=='right')? '':'-'; ?>1px 1px 0px 0px #fafafa; 
  
  color: #FFBB22;
  padding: 5px 4px 3px;
  width: 90%;
}

input[type="text"] {
  background-color: #FAFAFA;
  border-color: #BBBBBB #CCCCCC #DDDDDD;
  border-radius: 3px;
  border-style: solid;
  border-width: 1px;
  box-shadow: 0 1px 2px #dddddd inset, 0 -25px 10px -15px rgba(180, 180, 200, 0.1) inset;
  color: #333;
  padding: 5px;
  padding: 3px;
  margin: 5px;
  text-shadow: 0 1px 0 #FFFFFF;
}

#fast_filter {
  color: #bbbbbb;
  padding: 2px;
  width: 85%;
  border-radius: 0;
}

#NavFilter {
  display: none;
}

#clear_fast_filter {
  color: black;
  cursor: pointer;
  padding: 2px 0 0;
  margin: 5px 5px 0 -23px;
  	margin-<?php echo $left; ?>:-23px; 
  	margin-<?php echo $right; ?>:5px; 
  position: relative;
  	float: <?php echo $right; ?>;
  text-shadow: 1px 1px 0 #fff;
}

#databaseList select#lightm_db {
  margin-bottom: 5px;
}

#recentTableList select,
#databaseList select {
  margin: 5px 10px 0;
}

#pmalogo,
#leftframelinks {
  text-align: center;
}

#left_tableList ul#subel0, ul#databaseList {
  overflow: hidden;
  margin-top: 2px;
  padding: 10px 0;
}

#left_tableList ul#subel0 li {
  border-radius: 4px 0 0 4px;
  
  	border-top-<?php echo $right; ?>-radius: 0;
  	border-bottom-<?php echo $right; ?>-radius: 0;
  	border-top-<?php echo $left; ?>-radius: 4px;
  	border-bottom-<?php echo $left; ?>-radius: 4px;
  
  margin: 0;
  padding: 5px 5px 5px 10px;
  	padding-<?php echo $left; ?>: 10px; 
  	padding-<?php echo $right; ?>: 5px; 
  white-space: nowrap;
}

#left_tableList ul#subel0 li a {
  display: inline-block;
  padding: 0;
  font-weight: bold;
  color: #666699;
}

#left_tableList ul#subel0 li a:hover {
  text-decoration: underline;
}

#left_tableList ul#subel0 li:hover {
  box-shadow: -10px 0 10px 5px #dddddd;
  box-shadow: -10px 0 8px 5px #d5d5d7;
}

#left_tableList ul#subel0 li:hover > a {
  color: #dd7710;
}

#left_tableList ul#subel0 li.marked {
  background-color: #fff;
  box-shadow: -10px 0 8px 5px #d5d5d7;
  padding: 8px 25px 8px 20px;
  	
  	padding-<?php echo $left; ?>:20px;
  	padding-<?php echo $right; ?>:25px;
  
}

#left_tableList ul#subel0 li.marked {
  position: relative;
}
  #left_tableList ul#subel0 li.marked a:before, #left_tableList ul#subel0 li.marked a:after {
    position: absolute;
    bottom: 1px;
    width: 4px;
    height: 4px;
    content: " ";
}
  #left_tableList ul#subel0 li.marked a:before {
    top: -4px;
    		<?php echo $right; ?>: 0;
    border-bottom-<?php echo $right?>-radius: 4px;
    box-shadow: 2px 2px 0 2px white;
}
  #left_tableList ul#subel0 li.marked a:after {
    		<?php echo $right; ?>: 0;
    bottom: -4px;
    border-top-<?php echo $right?>-radius: 4px;
    box-shadow: 2px -2px 0 2px white;
}


  #left_tableList ul#subel0 li.marked a:before {
    box-shadow: <?php echo ($left=='right')? '-':''; ?>2px 2px 0 2px white;
}
  #left_tableList ul#subel0 li.marked a:after {
    box-shadow: <?php echo ($left=='right')? '-':''; ?>2px -2px 0 2px white;
}

ul#subel0, ul#newtable {
  padding: 0;
}

ul#newtable li a {
  border-radius: 15px;
  box-shadow: 0 10px 10px 0 rgba(255, 255, 255, 0.5) inset, 0 0 0 1px rgba(150, 150, 150, 0.1);
  background-color: transparent;
  color: #000;
  display: inline-block;
  font-weight: bold;
  	margin-<?php echo $left; ?>:10px;
  padding: 3px 15px 0 0;
  	
  	padding-<?php echo $right; ?>: 15px;
  	padding-<?php echo $left; ?>: 0;
  
  text-shadow: none;
}

ul#newtable li a:hover {
  color: #555;
}

#icon_newtable,
.tableicon {
  	float:<?php echo $left; ?>
}

.tableicon {
  margin-right: 5px;
}
