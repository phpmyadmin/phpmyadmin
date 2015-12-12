<?php 
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * main css file from theme Original
 *
 * @package PhpMyAdmin-theme
 * @subpackage blueorange
 */

if (!defined('PMA_MINIMUM_COMMON') && !defined('TESTSUITE')) {
    exit();
}
?>
html {
  font-size: 82%;
  
   	font-size: <?php echo (null !== $GLOBALS['PMA_Config']->get('fontsize') ? $GLOBALS['PMA_Config']->get('fontsize') : (
  	isset($_COOKIE['pma_fontsize']) ? $_COOKIE['pma_fontsize'] : '82%'));?>;
  
}

body {
  font-family: Tahoma, Helvetica, Verdana, sans-serif;
  background: none repeat scroll 0 0 #FAFAFA;
  color: #111111;
  margin: 0.5em;
  padding: 0;
  	
  	<?php if (! empty($GLOBALS['cfg']['FontFamily'])) { ?>
      font-family:        <?php echo $GLOBALS['cfg']['FontFamily']; ?>;
  <?php
} ?>
      background:         <?php echo $GLOBALS['cfg']['NaviBackground']; ?>;
      color:              <?php echo $GLOBALS['cfg']['NaviColor']; ?>;
  
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
}

a:link,
a:visited,
a:active {
  color: #666699;
}
a:link:hover,
a:visited:hover,
a:active:hover {
  color: #dd7710;
}

h2 {
  margin-top: 0;
}

#floating_menubar {
  background-color: #FAFAFA;
  box-shadow: 0 5px 5px 0 #FAFAFA;
  padding-bottom: 10px;
  /*	margin:6px 7px 0 6px; 
  	border-color: transparent #CCCCCC #666699;
  	border-radius: 4px;
  	border-right: 6px solid #CCCCCC;
  	border-width: 0 3px 0;
  	border-style: none solid none;
  	padding-bottom:0;
  	*/
}

textarea, tt, pre, code {
  font-family: monospace;
  
  	<?php if (! empty($GLOBALS['cfg']['FontFamilyFixed'])) { ?>
      font-family: <?php echo $GLOBALS['cfg']['FontFamilyFixed']; ?>;
  <?php
} ?>
  
}

.icon {
  
  	float: <?php echo $left; ?>;
  
}

td .icon {
  margin: 0;
  vertical-align: -3px;
}

.exportoptions ul, .operations_half_width ul, .export_sub_options ul {
  list-style-type: none;
}

div.exportoptions ul, div.importoptions ul {
  
  	border-<?php echo $left; ?>: 3px solid #eee;
  
}

div.operations_half_width ul {
  padding-left: 5px;
}

input {
  outline: none;
}

/*----------------------------------------------------------------------------------------------------------*/
/*Breadcrumbs*/
#serverinfo {
  background: none repeat scroll 0 0 #555555;
  border-color: transparent #CCCCCC #666699;
  border-radius: 4px 4px 0 0;
  
  	border-<?php echo $right; ?>: 6px solid #CCCCCC;
  
  border-style: none solid solid;
  border-width: medium 6px 6px;
  box-shadow: 0 5px 10px 0 #000000 inset;
  padding: 14px 10px 6px;
  text-shadow: 0 1px 0 black;
  margin: 6px 7px 0 6px;
  
  	margin-<?php echo $right; ?>: 7px;
  	margin-<?php echo $left; ?>: 6px;
  
}

#serverinfo a {
  color: #FFBB22;
}

#serverinfo a:hover {
  text-decoration: underline;
}

#serverinfo .item {
  
  	float: <?php echo $left; ?>;
  
  white-space: nowrap;
}

/*----------------------------------------------------------------------------------------------------------*/
/*Main Navigation*/
#querywindowcontainer #topmenucontainer {
  margin: 0;
  border-radius: 4px;
  border-top: 6px solid #669;
  box-shadow: 0 -2px 4px 2px #cccccc inset, 0 -10px 25px 0 #cccccc inset, 0 4px 15px 0 #cccccc inset;
}

#topmenucontainer {
  border-radius: 0 0 4px 4px;
  border-top: 1px solid #e5e5e5;
  box-shadow: 0 1px 2px 2px #cccccc inset, 0 -1px 25px 0 #cccccc inset;
  box-shadow: 0 -2px 4px 2px #cccccc inset, 0 -10px 25px 0 #cccccc inset;
  box-shadow: 0 -2px 4px 2px #cccccc inset, 0 -10px 25px 0 #cccccc inset, 0 10px 10px 0 rgba(50, 50, 150, 0.1) inset;
  box-shadow: 0 -2px 8px 2px #ccccd0 inset, 0 10px 10px 0 rgba(50, 50, 150, 0.1) inset;
  margin: 0 7px 0 6px;
  
  	margin-<?php echo $right; ?>: 7px;
  	margin-<?php echo $left; ?>: 6px;
  
  padding: 0;
  background-color: #f1f1f1;
}

#topmenucontainer ul, #topmenucontainer ul#topmenu li {
  margin: 0;
  padding: 0;
}

#topmenucontainer ul li {
  display: inline-block;
}

#topmenucontainer ul#topmenu li {
  overflow: hidden;
}

ul#topmenu2 li {
  display: block;
}

ul#topmenu li a, ul#topmenu li span {
  font-size: 13px;
  display: inline-block;
  text-shadow: 0 1px 0 white;
  padding: 10px 10px 10px 3px;
  
  	padding-<?php echo $right; ?>: 10px;
  	padding-<?php echo $left; ?>: 3px;
  
  margin: 0 10px;
  border-radius: 4px 4px 0 0;
  border: 1px solid transparent;
  border-style: none;
  border-bottom: 0 none;
  outline: 0 none;
}
  ul#topmenu li a, ul#topmenu li span {
    font-weight: bold;
    color: #666699;
}
  ul#topmenu li a:hover, ul#topmenu li span:hover {
    color: #dd7710;
}

ul#topmenu li .icon {
  
  	float: <?php echo $left; ?>;
  
}

ul#topmenu li a:hover,
ul#topmenu li.active a.tabactive,
.submenuhover a.tabactive {
  box-shadow: 0 5px 15px 0 #AAAAAA;
  box-shadow: 0 5px 10px 0 #AAAAAA;
}

ul#topmenu li.active a.tabactive, .submenuhover a.tabactive {
  background-color: #FAFAFA;
}

ul#topmenu ul.notonly a.tabactive {
  border: 1px solid transparent;
  border-radius: 0;
  box-shadow: 0 0 0 1px  #eaeaea inset;
  background-color: #F0F0F0;
}

ul#topmenu li a:before, ul#topmenu li a:after {
  position: relative;
  bottom: -14px;
  width: 4px;
  height: 4px;
  content: " ";
  display: inline-block;
}
ul#topmenu li a:before {
  <?php echo $left?>: -29px;
  border-bottom-<?php echo $right?>-radius: 4px;
  box-shadow: 2px 2px 0 2px transparent;
  
  	float: <?php echo $left; ?>;
  
  <?php echo $left?>: -7px;
  bottom: -23px;
}
ul#topmenu li a:after {
  <?php echo $right?>: -14px;
  border-bottom-<?php echo $left?>-radius: 4px;
  box-shadow: -2px 2px 0 2px transparent;
}

ul#topmenu li.active a:before, ul#topmenu li.submenuhover a:before {
  box-shadow: 2px 2px 0 2px #FFFFFF;
  
  	box-shadow: <?php echo ($left=='right')? '-':''; ?>2px 2px 0 2px #FFFFFF
  
}

ul#topmenu li.active a:after, ul#topmenu li.submenuhover a:after {
  box-shadow: -2px 2px 0 2px white;
  
    box-shadow: <?php echo ($left=='right')? '':'-'; ?>2px 2px 0 2px #FFFFFF
  
}

/*
ul#topmenu li.active, ul#topmenu li.submenuhover{
	@include round-out-border-s(4px, #fafafa,10px,10px,23px);
}*/
div#serverStatusTabs li.ui-state-active a:before, div#serverStatusTabs li.ui-state-active a:after {
  position: relative;
  bottom: -8px;
  width: 4px;
  height: 4px;
  content: " ";
  display: inline-block;
}
div#serverStatusTabs li.ui-state-active a:before {
  <?php echo $left?>: -19px;
  border-bottom-<?php echo $right?>-radius: 4px;
  box-shadow: 2px 2px 0 2px #fafafa;
}
div#serverStatusTabs li.ui-state-active a:after {
  <?php echo $right?>: -19px;
  border-bottom-<?php echo $left?>-radius: 4px;
  box-shadow: -2px 2px 0 2px #fafafa;
}

div#serverStatusTabs li.ui-state-active a:after {
  
  	box-shadow: <?php echo ($left=='right')? '':'-'; ?>2px 2px 0 2px #FAFAFA;
  
}

div#serverStatusTabs li.ui-state-active a:before {
  
  	box-shadow: <?php echo ($left=='right')? '-':''; ?>2px 2px 0 2px #FAFAFA;
  
}

/*Hide the more tab*/
#topmenucontainer .submenu {
  display: none;
}

/*Display the more tab*/
ul#topmenu .shown {
  display: inline-block;
}

/*Display the more menu on Hover */
ul#topmenu .submenuhover ul.notonly, ul#topmenu li:hover ul {
  display: block;
}

/*The more menu hidden by default*/
ul#topmenu ul.notonly {
  display: none;
  position: absolute;
  background-color: #fAfAfA;
  border-radius: 6px 6px 8px 8px;
  border-style: solid;
  border-width: 4px 1px 1px;
  border-color: #EEEEEE #DDDDDD #DDDDDD;
  box-shadow: 2px 2px 3px #666666;
  
  	box-shadow: <?php echo ($left=='right')? '':''; ?>1px 2px 3px #666666;
  
}

/*The more menu vertical listing*/
ul#topmenu ul.notonly li {
  display: block;
}

ul#topmenu ul.notonly li a {
  padding: 5px 10px;
  margin: 5px 0;
  border-radius: 0;
  display: block;
  border: 0 none;
}

/*The more menu Hover effect for vertical list item*/
ul#topmenu ul.notonly li a:hover {
  box-shadow: 0 0 10px -5px #aaaaaa inset;
}

#topmenu a.error {
  /* background-image:none;
   background-color: transparent;
   border-color: #CCCCCC #DDDDDD #E5E5E5;
   border-radius: 4px 4px 4px 4px;
   border-style: solid;
   border-width: 1px;
   color: #CCCCCC;
   margin: 3px 6px 5px;
   padding: 8px 13px 3px 3px;
   box-shadow:none;
   */
  background-image: none;
  background-color: rgba(100, 100, 150, 0.02);
  border-color: #CCCCCC #DDDDDD #FAFAFA;
  border-radius: 4px 4px 6px 6px;
  border-style: solid;
  border-width: 1px;
  box-shadow: none;
  color: #CCCCCC;
  margin: 3px 6px 6px;
  padding: 6px 13px 3px 3px;
  
  	padding-<?php echo $right; ?>: 13px;
  	padding-<?php echo $left; ?>: 3px;
  
}

#topmenu a.error:hover {
  box-shadow: none !important;
  color: #CCCCCC;
}

a.error .ic_b_browse {
  background-position: 0 -112px !important;
}

ul#topmenu, ul#topmenu2, ul.tabs {
  font-weight: bold;
  list-style-type: none;
  margin: 0;
  padding: 0;
  overflow: hidden;
}

ul#topmenu {
  height: 36px;
}

ul#topmenu2 {
  clear: both;
  margin: 0.25em 0.5em 0;
  list-style-type: none;
  margin: 0;
  padding: 0;
  box-shadow: 0 1px 1px 0 white, 0 20px 40px -10px #646464, 0 -15px 5px 0 rgba(100, 100, 100, 0.1) inset, 0 0 0 1px rgba(125, 112, 80, 0), 0 -1px 0 0 rgba(125, 112, 80, 0.1) inset;
  border-radius: 6px;
}
  ul#topmenu2 li {
    display: inline-block;
    margin: 0;
    padding: 0;
    /*&:last-child a{
    border-radius: 0 4px 4px 0;	
   
}*/
}
    ul#topmenu2 li:first-child a {
      
      	border-top-<?php echo $left; ?>-radius: 4px;
      	border-top-<?php echo $right; ?>-radius: 0;
      	border-bottom-<?php echo $right; ?>-radius: 0;
      	border-bottom-<?php echo $left; ?>-radius: 4px;
      
}
  ul#topmenu2 a {
    display: block;
    margin: 0 0 0.5em;
    margin: 0;
    padding: 6px 8px;
    /*border-style: solid;
    border-width: 1px;	*/
}
  ul#topmenu2 a.tabactive {
    border: medium none;
}
  ul#topmenu2 a {
    font-family: Verdana,Arial,sans-serif;
    font-size: 1em;
    line-height: 1.3;
    text-shadow: 0 1px 0 #fff;
}
  ul#topmenu2 a.tabactive {
    background-color: #EF9900;
    box-shadow: 0 1px 5px -1px rgba(0, 0, 0, 0.5) inset;
    color: #fff;
    text-shadow: 0 0 2px #a50;
}

/*

ul#topmenu2 {
    clear: both;
    height: 2em;
    margin: 0.25em 0.5em 0;
}

ul#topmenu2 a{
    background: none repeat scroll 0 0 #FAFAFA;
    
    border-color: #EEEEFF #DDDDFF #CCCCCC;
    border-radius: 4px 4px 4px 4px;
    border-style: solid;
    border-width: 1px;
    display: block;
    margin: 0 0.25em 0.5em;
    padding: 4px 8px;
    
    box-shadow: 0 -16px 15px -15px rgba(255, 100, 0, 0.1) inset;
}


ul#topmenu2 li {
    float: left;
    margin: 0;
    overflow: hidden;
    vertical-align: middle;
}
ul#topmenu2 li.active {
    float: right;
}
ul#topmenu2 a.tabactive {
		background-color: transparent;
    border: medium none;
    box-shadow: none;
    color: #000000;
    font-size: 1.5em;
    font-weight: normal;
}
*/
.config-form ul.tabs {
  list-style-type: none;
  margin: 0;
  padding: 3px 3px 0;
  background-color: #666699;
  border-radius: 4px;
}
  .config-form ul.tabs li {
    display: inline-block;
    margin: 0;
    padding: 0;
    position: relative;
}
    .config-form ul.tabs li a:before, .config-form ul.tabs li a:after {
      position: absolute;
      bottom: 0;
      width: 4px;
      height: 4px;
      content: " ";
}
    .config-form ul.tabs li a:before {
      <?php echo $left?>: -4px;
      border-bottom-<?php echo $right?>-radius: 4px;
      box-shadow: 2px 2px 0 2px transparent;
}
    .config-form ul.tabs li a:after {
      <?php echo $right?>: -4px;
      border-bottom-<?php echo $left?>-radius: 4px;
      box-shadow: -2px 2px 0 2px transparent;
}
  .config-form ul.tabs a {
    outline: 0 none;
    display: inline-block;
    padding: 0.2em 1em;
    border: 1px solid red;
    border-bottom: 0 none;
    border-radius: 4px 4px 0 0;
    background-color: #E5E5E5;
    border-color: #EEEEEE;
    font-weight: normal;
    text-shadow: 1px 1px 0 #fff;
    box-shadow: 0 -5px 5px -5px #aaaaaa inset, 0 15px 15px -15px #d5d5de inset;
    font-family: Verdana,Arial,sans-serif;
    font-size: 1.1em;
    line-height: 1.3;
    color: #333366;
}
    .config-form ul.tabs a:hover {
      background-color: #EEEEEE;
}
  .config-form ul.tabs li.active a:before {
    
    box-shadow:	<?php echo ($left=='right')? '-':''; ?>2px 2px 0 2px #FAFAFA;
    
}
  .config-form ul.tabs li.active a:after {
    
    box-shadow:	<?php echo ($left=='right')? '':'-'; ?>2px 2px 0 2px #FAFAFA;
    
}
  .config-form ul.tabs li.active a {
    background-color: #fafafa;
    box-shadow: none;
}
  .config-form ul.tabs a {
    border-radius: 4px 4px 0 0;
    background-color: #E5E5E5;
    border-color: #EEEEEE;
    font-weight: normal;
    text-shadow: 1px 1px 0 #fff;
    box-shadow: 0 -5px 5px -5px #aaaaaa inset, 0 15px 15px -15px #d5d5de inset;
    font-family: Verdana,Arial,sans-serif;
    font-size: 1.1em;
    line-height: 1.3;
    color: #333366;
}
    .config-form ul.tabs a:hover {
      background-color: #EEEEEE;
}

.config-form {
  box-shadow: 0 0 150px -60px rgba(0, 0, 0, 0.5);
  padding: 3px;
  border-radius: 4px;
}

.tabs_contents {
  padding: 0 20px 15px;
}

/*.config-form ul.tabs {
    float: right;
}
.config-form ul.tabs li {
background-color: #DDDDDD;
    border-color: #CCCCCC #CCCCCC #FFFFFF;
    border-style: hidden;
    border-width: 1px;
    box-shadow: 0 9px 0 0 #9999CC inset;
    box-shadow:0 1px 0 0 #666699 inset, 0 9px 0 0 #9999CC inset;
    float: left;
    padding: 0;;
}
.config-form ul.tabs li a {
background: none repeat scroll 0 0 #FAFAFA;
    border-color: #DDDDFF #CCCCCC transparent;
    border-radius: 2px 2px 2px 2px;
    border-style: solid;
    border-width: 1px;
    display: block;
    margin: 0 0 5px;
    padding: 4px 8px;
    outline:none;
    box-shadow: 0 1px 0 0 rgba(255, 255, 255, 0.3) inset, 0 10px 10px -5px rgba(100, 100, 100, 0.1) inset;
}
.config-form ul.tabs li.active a {
border-width: 1px 1px 1px;
    box-shadow: none;
    margin: 5px 0 0;
    text-decoration: none;
    box-shadow: 0 1px 0 0 rgba(255, 255, 255, 0.3) inset, 0 10px 10px -5px rgba(50, 50, 100, 0.1) inset;
}

*/
div#serverstatus div.tabLinks {
  /*    float:<?php echo $left; ?>;
      float: left;
  */
  padding-bottom: 5px;
  /*box-shadow:0 6px 4px -6px #555555;
  box-shadow:0 10px 4px -6px #bbb;*/
  margin-bottom: 10px;
}

div#serverstatus div.tabLinks a {
  /*padding: 5px 15px;
  margin:5px;
  background-color: #f3f3f3;
  border-radius:4px;
      box-shadow: 0 1px 0 0 rgba(255, 255, 255, 0.3) inset, 0 10px 10px -5px rgba(50, 50, 100, 0.1) inset;
      
      
      @include button2;
  */
}

div#serverStatusTabs {
  box-shadow: 0 0 150px -60px rgba(0, 0, 0, 0.5);
}

/*----------------------------------------------------------------------------------------------------------*/
/*Buttons & other form elements*/
/*base*/
button, input[type="submit"], input[type="button"] {
  background-color: #666699;
  border: 1px solid #666699;
  border-radius: 4px;
  box-shadow: 0 3px 4px -4px rgba(255, 255, 255, 0.1) inset, 0 -10px 10px 0 rgba(0, 0, 0, 0.2) inset, 0 5px 10px 5px rgba(44, 44, 100, 0.1);
  box-shadow: 0 1px 0 0 #8888BB inset;
  box-shadow: 0 3px 4px -4px rgba(255, 255, 255, 0.1) inset, 0 -10px 10px 0 rgba(0, 0, 0, 0.2) inset;
  color: #EEEEEE;
  font-weight: bold;
  line-height: 18px;
  
  	margin-<?php echo $left; ?>: 14px;
  
  padding: 2px 10px 2px 5px;
  
  	padding-<?php echo $right; ?>: 10px;
  	padding-<?php echo $left; ?>: 5px;
  
  text-decoration: none;
  cursor: pointer;
}

button:hover, input[type="submit"]:hover, input[type="button"]:hover {
  background-color: #FF9900;
  border: 1px solid #cc7700;
  /*	box-shadow: 0 1px 0 0 #FFAA11 inset,0 1px 1px 0 rgba(0,0,0,0.1),0 5px 10px 5px rgba(144,100,0 , 0.1);*/
}

#fieldsForm button, button.mult_submit {
  background-color: #FFFFFF;
  border: 1px solid #E9E9E9;
  border-radius: 4px 4px 4px 4px;
  box-shadow: 0 -2px 0 0 #e0e0e0 inset, 0 -4px 15px -12px #999999;
  box-shadow: 0 -1px 0 0 #e0e0e0 inset, 0 -12px 0 0 #fafafd inset, 0 -4px 15px -12px #999999;
  color: #669;
  cursor: pointer;
  font-weight: normal;
  line-height: 18px;
  
  	margin-<?php echo $left; ?>: 14px;
  
  padding: 3px 10px 3px 5px;
  
  	padding-<?php echo $right; ?>: 10px;
  	padding-<?php echo $left; ?>: 5px;
  
  text-decoration: none;
  text-shadow: 0 1px 0 #FFFFFF;
}
#fieldsForm button:hover, button.mult_submit:hover {
  background-color: #ffaa43;
  text-shadow: none;
  color: #fff;
  box-shadow: 0 -2px 0 0 #ee8821 inset, 0 -12px 0 0 #ff9932 inset, 0 -4px 15px -12px #998811;
  border-color: #dd7710;
}

div#sqlqueryresults fieldset a, p a {
  margin-right: 15px;
  display: inline-block;
}

a span.nowrap {
  display: inline-block;
}

.wrapper {
  
  	float: <?php echo $left; ?>;
  
  margin-bottom: 1.5em;
}

.toggleButton {
  position: relative;
  cursor: pointer;
  font-size: 0.8em;
  text-align: center;
  line-height: 1.55em;
  height: 1.55em;
  overflow: hidden;
  border-right: 0.1em solid #888;
  border-left: 0.1em solid #888;
}

.toggleButton table,
.toggleButton td,
.toggleButton img {
  padding: 0;
  position: relative;
}

.toggleButton .container {
  position: absolute;
}

.toggleButton .toggleOn {
  color: white;
  padding: 0 1em;
}

.toggleButton .toggleOff {
  padding: 0 1em;
}

input[type="text"], input[type="password"], select, textarea {
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

.CodeMirror-scroll {
  background-color: #FAFAFA;
  border-color: #BBBBBB #CCCCCC #DDDDDD;
  border-radius: 3px;
  border-style: solid;
  border-width: 1px;
  box-shadow: 0 1px 2px #dddddd inset, 0 -25px 10px -15px rgba(180, 180, 200, 0.1) inset;
  color: #333;
}

.CodeMirror textarea {
  padding: 0;
  margin: 0;
  text-shadow: 0 1px 0 #FFFFFF;
  box-shadow: 0 1px 2px #dddddd inset, 0 -25px 10px -15px rgba(180, 180, 200, 0.1) inset;
  border: 0 none;
  background-color: #fafafa;
}

input[type="text"]:focus, input[type="password"]:focus, select:focus, textarea:focus, .CodeMirror textarea:focus, .CodeMirror-scroll:focus {
  background-color: #FFFFFF;
  box-shadow: 0 1px 3px #cccccc inset, 0 25px 10px -15px rgba(150, 150, 180, 0.1) inset;
  color: #000;
  text-shadow: 0 1px 1px #bbbbbb;
}

#input_import_file {
  margin: 20px 25px;
}

label {
  cursor: pointer;
}

label, fieldset a {
  color: #555555;
  text-shadow: 0 1px 0 #fff;
}
label:hover, fieldset a:hover {
  color: #000000;
}

.CodeMirror textarea {
  font-family: inherit !important;
  font-size: inherit !important;
}

textarea {
  height: 18em;
  overflow: visible;
}

.CodeMirror {
  /*font-size: 140%;*/
  font-family: monospace;
  background: white;
  border: 1px none black;
  cursor: text;
}

.CodeMirror-scroll {
  overflow: auto;
  	height: <?php echo ceil($GLOBALS['cfg']['TextareaRows'] * 1.2); ?>em;
  /* This is needed to prevent an IE[67] bug where the scrolled content
     is visible outside of the scrolling box. */
  position: relative;
  margin: 0;
}

.CodeMirror-gutter {
  position: absolute;
  
    <?php echo $left; ?>: 0;
  
  top: 0;
  z-index: 10;
  background-color: #f7f7f7;
  border-right: 1px solid #eee;
  
  	border-<?php echo $right; ?>: 1px solid #eee;
  
  min-width: 2em;
  height: 100%;
}

.CodeMirror-gutter-text {
  color: #aaa;
  text-align: right;
  
    text-align: <?php echo $right; ?>;
  
  padding: .4em .2em .4em .4em;
  
  	padding-<?php echo $right; ?>: .2em;
  	padding-<?php echo $left; ?>: .4em;
  
  white-space: pre !important;
}

.CodeMirror-lines {
  padding: .4em;
  padding-right: 35px;
}

.CodeMirror pre {
  border-radius: 0;
  border-width: 0;
  margin: 0;
  padding: 0;
  background: transparent;
  font-family: inherit;
  font-size: inherit;
  padding: 0;
  margin: 0;
  white-space: pre;
  word-wrap: normal;
}

.CodeMirror-wrap pre {
  word-wrap: break-word;
  white-space: pre-wrap;
}

.CodeMirror-wrap .CodeMirror-scroll {
  overflow-x: hidden;
}

.CodeMirror textarea {
  font-family: inherit !important;
  font-size: inherit !important;
}

.CodeMirror-cursor {
  z-index: 10;
  position: absolute;
  visibility: hidden;
  
  	border-<?php echo $left; ?>: 1px solid black !important;
  
}

.CodeMirror-focused .CodeMirror-cursor {
  visibility: visible;
}

span.CodeMirror-selected {
  background: #ccc !important;
  color: HighlightText !important;
}

.CodeMirror-focused span.CodeMirror-selected {
  background: Highlight !important;
}

.CodeMirror-matchingbracket {
  color: #0f0 !important;
}

.CodeMirror-nonmatchingbracket {
  color: #f22 !important;
}

.formelement {
  display: inline-block;
}

input.sqlbutton, #result_query input[type="button"] {
  margin: 5px 5px 0 0;
  
  	margin-<?php echo $right; ?>: 5px;
  	margin-<?php echo $left; ?>: 0;
  
}

#tablefieldinsertbuttoncontainer input {
  margin: 5px;
}

.tblFooters input[type="submit"] {
  
  	float: <?php echo $right; ?>;
  
}

a.saveLink, a.editLink, a.cancelLink {
  display: inline-block;
}
  a.saveLink, a.editLink, a.cancelLink {
    color: #444477;
}
  a.saveLink:hover, a.editLink:hover, a.cancelLink:hover {
    color: #dd7710;
}

a.editLink {
  
  	float: <?php echo $left; ?>;
  
}

.cEdit {
  margin: 0;
  padding: 0;
  position: absolute;
}

.cEdit input[type=text] {
  background: #FFF;
  height: 100%;
  margin: 0;
  padding: 0;
}

.cEdit .edit_area {
  background-color: #E4E4E4;
  min-width: 10em;
  padding: 0.3em 0.5em;
  border-radius: 4px;
  box-shadow: 0 3px 5px rgba(0, 0, 0, 0.4);
}

.cEdit .edit_area .ui-datepicker {
  box-shadow: none;
}

.cEdit .edit_area select, .cEdit .edit_area textarea {
  width: 97%;
}

.cEdit textarea {
  margin: 0;
  border-radius: 0;
}

.cEdit .cell_edit_hint {
  color: #555;
  font-size: 0.8em;
  margin: 0.3em 0.2em;
}

.cEdit .edit_box {
  overflow: hidden;
  padding: 0;
}

.cEdit .edit_box_posting {
  	background: #FFF url(<?php echo $_SESSION['PMA_Theme']->getImgPath(); ?>ajax_clock_small.gif) no-repeat right center;
  
  	padding-<?php echo $right; ?>: 1.5em;
  
}

.cEdit .edit_area_loading {
  	background: #FFF url(<?php echo $_SESSION['PMA_Theme']->getImgPath(); ?>ajax_clock_small.gif) no-repeat center;
  height: 10em;
}

.cEdit .goto_link {
  background: #EEE;
  color: #555;
  padding: 0.2em 0.3em;
}

.saving_edited_data {
  	background: url(<?php echo $_SESSION['PMA_Theme']->getImgPath(); ?>ajax_clock_small.gif) no-repeat left;
  
  	padding-<?php echo $left; ?>: 20px;
  
}

iframe.IE_hack {
  border: 0 none;
  display: none;
  position: absolute;
  z-index: 1;
}

/*----------------------------------------------------------------------------------------------------------*/
/*Boxes*/
fieldset, #importmain, #exportmain, form[name="dump"], #submit {
  padding: 10px 10px 8px;
  margin: 1em 0 1em;
  background-color: #eeeeee;
  border-color: #BBBBCC #CCCCCC #CCCCCC;
  border-style: solid;
  border-width: 2px 1px 1px;
  box-shadow: 0 1px 0 0 #FFFFFF inset;
  text-shadow: 0 1px 0 #FFFFFF;
  border-radius: 4px;
  margin: 1em 0 0;
  border-radius: 4px 4px 0 0;
}

fieldset legend {
  background-color: #EEEEEE;
  border-color: #BBBBCC #CCCCCC #CCCCCC;
  border-radius: 6px 6px 2px 2px;
  border-style: solid;
  border-width: 2px 1px 1px;
  box-shadow: 0 1px 0 0 #FFFFFF,0 1px 0 0 #FFFFFF inset;
  color: #444444;
  font-weight: bold;
  padding: 5px 10px;
}

div.tools,
fieldset.tblFooters, #submit {
  margin: 0 0 0.5em;
  border-radius: 0 0 4px 4px;
  box-shadow: 0 1px 0 0 white inset, 0 1px 2px -2px #777777;
  border-top: 0 none;
  background-color: #DDDDDD;
  
  	text-align: <?php echo $right; ?>;
  
  clear: both;
}

#submit {
  padding: 1.5em;
}

.tblFooters {
  background-color: #DDDDDD;
  color: #000000;
  font-weight: normal;
}

.tblFooters table {
  
  	float: <?php echo $right; ?>;
  
}

#chartGrid .monitorChart {
  border-radius: 4px;
  border-color: #E0E0E3;
}

/*
form[name="dump"]{box-shadow: 0 10px 150px -60px rgba(0, 0, 0, 0.5); padding:20px;}
*/
#importmain, #exportmain, form[name="dump"] {
  background-color: #f3f3f3;
  border-color: #BBBBCC #DFDFDF #CCCCCC;
  padding: 20px;
}

.importoptions h3, .exportoptions h3, form[name="dump"] h3 {
  border-bottom: 1px solid #DDDDDD;
  box-shadow: 0 1px 0 0 #FFFFFF;
}

.group button, .group input[type="submit"], .group input[type="button"] {
  
  margin-<?php echo $left; ?>:0;
  
}

.group-cnt, #importmain, .exportoptions, .operations_half_width, form[name="dump"] {
  line-height: 1.8em;
}

.tabs_contents .lastrow {
  
  text-align: <?php echo $right; ?>;
  
}

fieldset#fieldset_add_user, #replication fieldset, fieldset.caution, .doubleFieldset fieldset, div#tablestatistics fieldset {
  box-shadow: 0 1px 0 0 white inset, 0 1px 2px -2px #333333;
  border-radius: 4px;
}

div#tablestatistics fieldset {
  margin-bottom: 5px;
}

.doubleFieldset fieldset.left {
  margin-right: 10px;
}

.doubleFieldset fieldset {
  
  	float: <?php echo $left; ?>;
  
  padding: 10px;
  width: 45%;
  display: inline-block;
}

.doubleFieldset div.wrap {
  padding: 1.5em;
}

#selflink {
  display: block;
  background-color: #F3F3F3;
  clear: both;
  padding: 10px;
  text-align: right;
  
  text-align: <?php echo $right; ?>;
  	
  
}

.popupContent {
  position: absolute;
  display: none;
  margin: 0;
  z-index: 2;
  padding: 10px;
  background-color: #eee;
  /*box-shadow: 0 1px 4px #AAAAAA;
  box-shadow:0 4px 4px #AAAAAA, 0 4px 4px -4px #555555 inset, 0 9px 3px -4px #FaFaFa inset;
  box-shadow:0 3px 4px #AAAAAA,  0 10px 3px -4px #FAFAFA inset;
  box-shadow:0 1px 4px #AAAAAA,  0 14px 2px -4px #FAFAFA inset;*/
  box-shadow: 0 1px 4px #aaaaaa, 0 5px 5px -5px #fafafa inset;
  border-radius: 0 0 4px 4px;
  border: 1px solid #f8f8f8;
  border: 0 none;
}

.popupContent .icon {
  float: none;
}

.popupContent a, .buttonlinks a {
  display: inline-block;
  margin: 5px;
  padding: 5px 10px;
  color: #669;
  background-color: #fff;
  border-radius: 4px;
  box-shadow: 0 1px 0 0 rgba(100, 100, 100, 0.1), 0 -10px 5px rgba(200, 200, 200, 0.2) inset, 0 -5px 5px 5px rgba(200, 200, 200, 0.1);
}

button.ui-button-text-only span.ui-button-text {
  padding: 2px 10px;
}

.floatleft {
  
  	float: <?php echo $left; ?>;
  
  margin-right: 1em;
}

/* querybox */
div#sqlquerycontainer {
  	float: <?php echo $left; ?>;
  width: 69%;
  /* height: 15em; */
}

div#tablefieldscontainer {
  	float: <?php echo $right; ?>;
  width: 29%;
  /* height: 15em; */
}

div#tablefieldscontainer select {
  width: 100%;
  /* height: 12em; */
}

textarea#sqlquery {
  width: 100%;
  /* height: 100%; */
}

textarea#sql_query_edit {
  height: 7em;
  width: 95%;
  display: block;
}

div#queryboxcontainer div#bookmarkoptions {
  margin-top: 0.5em;
}

/* end querybox */
code.sql {
  border-bottom: 0 none;
  border-top: 0 none;
  display: block;
  margin-bottom: 0;
  margin-top: 0;
  max-height: 10em;
  overflow: auto;
  padding: 0.3em;
  background-color: #F3F3F3;
}

/* MySQL Parser */
.syntax_comment {
  padding-left: 4pt;
  padding-right: 4pt;
}

.syntax_alpha_columnType, .syntax_alpha_columnAttrib, .syntax_alpha_reservedWord, .syntax_alpha_functionName {
  text-transform: uppercase;
}

.syntax_alpha_reservedWord {
  font-weight: bold;
  color: #666699;
}

.syntax_quote {
  white-space: pre;
}

div.tools,
.tblFooters {
  font-weight: normal;
      color: <?php echo $GLOBALS['cfg']['ThColor']; ?>;
}

div.tools {
  padding: 0 5px 3px;
}

.tblHeaders a:link,
.tblHeaders a:active,
.tblHeaders a:visited,
div.tools a:link,
div.tools a:visited,
div.tools a:active,
.tblFooters a:link,
.tblFooters a:active,
.tblFooters a:visited {
  color: #669;
}

div.tools form {
  display: inline;
}

.tblHeaders a:hover,
div.tools a:hover,
.tblFooters a:hover {
  color: #d70;
}

/* forbidden, no privilegs */
.noPrivileges {
  color: #FF0000;
  font-weight: bold;
}

/* disabled text */
.disabled,
.disabled a:link,
.disabled a:active,
.disabled a:visited {
  color: #666666;
}

.disabled a:hover {
  color: #666666;
  text-decoration: none;
}

tr.disabled td,
td.disabled {
  background-color: #cfcfcf;
  text-shadow: 0 1px 0 #dfdfdf;
}

/**
 * login form
 */
body.loginform h1,
body.loginform a.logo {
  display: block;
  text-align: center;
}

body.loginform {
  text-align: center;
}

body.loginform div.container {
      text-align: <?php echo $left; ?>;
  width: 30em;
  margin: 0 auto;
}

form.login label {
      float: <?php echo $left; ?>;
  width: 10em;
  font-weight: bolder;
}

.structure_actions_dropdown {
  display: none;
  position: absolute;
  line-height: 24px;
  padding: 4px;
  z-index: 100;
  box-shadow: 0 1px 2px 1px #DDDDDD;
  border: 1px solid #DDDDDD;
  border-left: none;
  
  	border-<?php echo $left; ?>: none;
  
  border-bottom: none;
  border-top-width: 2px;
  border-radius: 2px 2px 4px 4px;
}

.structure_actions_dropdown {
  /**background-color:#F00 !important;
  background: <?php echo $GLOBALS['cfg']['BrowsePointerBackground'];  ?> !important;
  */
}

/*----------------------------------------------------------------------------------------------------------*/
/*Tables*/
table caption, table th, table td {
  margin: 0.1em;
  padding: 0.5em 0.5em 0;
  text-shadow: 0 1px 0 #FFFFFF;
  vertical-align: top;
}

table th {
  vertical-align: middle;
}

th {
  background: none repeat scroll 0 0 #DDDDDD;
  color: #000000;
  font-weight: bold;
}

thead th {
  box-shadow: 0 3px 2px -2px rgba(0, 0, 0, 0.3);
}

#tableuserrights td, #tablespecificuserrights td, #tabledatabases td {
  vertical-align: middle;
}

table caption, table th, table td {
  margin: 0.1em;
  padding: 0.5em;
  padding: 0.5em 0.5em 0.25em;
  text-shadow: 0 1px 0 #FFFFFF;
  vertical-align: middle;
}

table tr.odd th, table tr.odd, table tr.even th, table tr.even {
  
      text-align: <?php echo $left; ?>;
  
}

table tr.odd th, .odd {
  background: none repeat scroll 0 0 #F3F3F3;
}

table tr.even th, .even {
  background: none repeat scroll 0 0 #FAFAFA;
}

/*table tr.even th, .even, .even .structure_actions_dropdown
{
    background-color: #FFFFFF;
}*/
td.name a {
  color: #444477;
}
td.name a:hover {
  color: #dd7710;
}

/*#serverStatusTabs,*/
#tableuserrights {
  box-shadow: 0 10px 150px -60px rgba(0, 0, 0, 0.5);
  width: 100%;
  border-collapse: collapse;
}

#serverStatusTabs th, #tableuserrights th {
  
      text-align: <?php echo $left; ?>;
  
}

.hide {
  display: none;
}

.ic_b_help, .ic_window-new, .ic_b_info, .ic_b_save, .ic_b_close, .ic_s_cog, .ic_s_asc, .ic_s_desc {
  float: none;
}

/*#serverStatusTabs .ic_b_help{float:right}*/
table#serverconnection_src_remote, table#serverconnection_trg_remote {
  display: inline-block;
}

table#serverconnection_src_remote .icon, table#serverconnection_trg_remote .icon, .data .icon {
  float: none;
}

.tabInnerContent table,
#serverstatusqueriesdetails,
#serverstatusquerieschart,
#tablestatistics table {
  display: inline-block;
}

table caption {
  background-color: #669;
  color: #eee;
  font-weight: bold;
  text-shadow: none;
  border-radius: 2px 2px 0 0;
}

#serverstatusquerieschart {
  width: 500px;
  height: 500px;
  
  	float: <?php echo $right; ?>;
  
}

.odd:hover, .even:hover, .hover, .structure_actions_dropdown {
  background-color: #FFEE66;
  color: #000000;
  box-shadow: 0 1px 2px 1px #DDDDDD;
}

.odd:hover th, .even:hover th {
  background-color: #FFEE66;
}

table {
  border-collapse: collapse;
}

table tr.odd:hover td:nth-child(2n+1),
table tr.even:hover td:nth-child(2n+1) {
  background-color: #FFFF77;
}

table#serverVariables {
  width: 100%;
}

#serverVariables td.value {
  
   text-align:<?php echo $right; ?>;
  
}

table#serverVariables td {
  height: 18px;
}

table#serverVariables td.edit, table.serverVariableEditTable td {
  margin: 0;
  padding: 0;
  vertical-align: middle;
}

table.serverVariableEditTable td input {
  margin: 0;
  
   text-align:<?php echo $right; ?>;
  
  border-radius: 0;
}

table.serverVariableEditTable {
  border: 0;
  margin: 0;
  padding: 0;
  width: 100%;
}

table.serverVariableEditTable td, table.serverVariableEditTable td a {
  vertical-align: middle;
  padding: 0 5px;
}

table.serverVariableEditTable td a {
  margin: 0 5px;
  padding: 0 10px;
  box-shadow: 0 0 10px -7px black;
}

table.serverVariableEditTable td:first-child, td.more_opts {
  white-space: nowrap;
}

fieldset.optbox table tr:nth-child(2n) {
  background-color: #f2f2f2;
}

fieldset.optbox table tr:nth-child(2n+1) {
  background-color: #f5f5f5;
}

fieldset.optbox table tr:hover {
  background-color: #fffffa;
}

table.navigation tr td {
  padding: 0;
  margin: 0;
}

table.navigation .navigation_separator {
  display: none;
}

/*----------------------------------------------------------------------------------------------------------*/
/*Jqui adjustments, some could just be embedded in the original */
.ui-tabs-nav li.ui-state-default {
  box-shadow: 0 -5px 5px -5px #aaaaaa inset, 0 15px 15px -15px #d5d5de inset;
  border: 1px solid #EEEEEE;
}

.ui-tabs-nav li.ui-state-default a {
  color: #333366;
  text-shadow: 0 1px 0 white;
}

.ui-tabs .ui-tabs-nav li.ui-tabs-selected {
  box-shadow: none;
}

/*----------------------------------------------------------------------------------------------------------*/
/*Notifications*/
.notice, .error, .success, .ajax_notification {
  padding: 5px 10px;
  margin: 0.5em 0;
  border-radius: 4px;
  box-shadow: 0 1px 1px -1px rgba(0, 0, 0, 0.2);
  font-weight: bold;
  text-shadow: none;
  color: #fff;
      <?php if ($GLOBALS['cfg']['ErrorIconic']) { ?> 
  background-repeat: no-repeat;
          <?php if ($GLOBALS['text_dir'] === 'ltr') { ?> 
  
      background-position: <?php echo ($left=='right')? '-':''; ?>5px 50%;
  
  padding: 5px 10px 5px 35px;
          <?php
} else { ?>
      background-position: 99% 50%;
      padding:            5px 35px 5px 10px;
          <?php
} ?>
      <?php
} ?>
  
  
  	padding-<?php echo $right; ?>: 10px;
  	padding-<?php echo $left; ?>: 35px;
  
}
  .notice a, .error a, .success a, .ajax_notification a {
    display: inline-block;
    padding: 2px 10px;
    background-color: transparent;
    border-radius: 4px;
    box-shadow: 0 0 10px 0 transparent inset, 0 1px 1px 0 transparent inset, 0 0 10px 0 transparent;
    text-shadow: 0 -1px 2px transparent;
}
  .notice a:hover, .error a:hover, .success a:hover, .ajax_notification a:hover {
    box-shadow: -1px -2px 2px 1px rgba(0, 0, 0, 0.1), 0 1px 1px 0 rgba(255, 255, 255, 0.5) inset, 0 -15px 5px -5px rgba(0, 0, 0, 0.1) inset, 0 0 0 1px rgba(0, 0, 0, 0.1);
    
    		box-shadow: <?php echo ($left=='right')? '':'-'; ?>1px -2px 2px 1px rgba(0, 0, 0, 0.1) , 0 1px 1px 0 rgba(255, 255, 255, 0.5) inset, 0 -15px 5px -5px rgba(0, 0, 0, 0.1) inset, 0 0 0 1px rgba(0,0,0,0.1);
    
    color: #FFF;
    text-shadow: 0 -1px 2px rgba(0, 0, 0, 0.3);
}

.notice {
  background-color: #6677BB;
  border: 1px solid #5566aa;
      <?php if ($GLOBALS['cfg']['ErrorIconic']) { ?> 
      background-image:   url(<?php echo $_SESSION['PMA_Theme']->getImgPath(); ?>s_notice.png); 
      <?php
} ?> 
}

.error {
  background-color: #DD5566;
  border: 1px solid #cc4455;
      <?php if ($GLOBALS['cfg']['ErrorIconic']) { ?> 
      background-image:   url(<?php echo $_SESSION['PMA_Theme']->getImgPath(); ?>s_error.png); 
      <?php
} ?> 
}

.success {
  background-color: #93c630;
  border: 1px solid #88b82d;
  font-weight: normal;
      <?php if ($GLOBALS['cfg']['ErrorIconic']) { ?> 
      background-image:   url(<?php echo $_SESSION['PMA_Theme']->getImgPath(); ?>s_success.png); 
      <?php
} ?> 
}

.ajax_notification {
  background-color: #dEe5f5;
  font-weight: normal;
  color: #669;
  text-shadow: 0 1px 0 #EEEEFF;
}

.notice a, .ajax_notification a {
  color: #FFBB22;
}

.error a, .success a {
  color: #FFEE55;
}

.notice a:hover {
  background-color: #FFBB22;
}

.error a:hover, .success a:hover {
  background-color: #FFAA11;
}

/*
div.success, div.notice, div.warning, div.error {
	background-position: 10px 50%;
	background-repeat: no-repeat;
	padding-left:35px;
}
*/
/*----------------------------------------------------------------------------------------------------------*/
/*layout*/
#maincontainer {
  background: url("data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAQAAAAECAYAAACp8Z5+AAAAFUlEQVQImWNgQAIPHjz4TyqHgYEBAOwRDn3kWbUIAAAAAElFTkSuQmCC") repeat scroll 0 0 #efefef;
  border-radius: 6px;
  display: block;
  margin: 0.5em 0;
  padding: 0 1em;
  box-shadow: 0 1px 1px 1px #DDDDDD inset;
}

#main_pane_left {
  
   	float: <?php echo $left; ?>;
  
  padding-top: 1em;
  width: 60%;
}

#serverstatussection, .clearfloat {
  clear: both;
}

#main_pane_right {
  
  	margin-<?php echo $left; ?>: 60%;
  
  
  	padding-<?php echo $left; ?>: 1em;
  
  padding-top: 1em;
}

.group {
  background: none repeat scroll 0 0 #FAFAFA;
  border-color: #7777AA #DDDDDD #CCCCCC;
  border-radius: 4px;
  border-style: solid;
  border-style: none;
  border-width: 1px;
  box-shadow: 0 0 4px 0 #CCCCCC;
  box-shadow: 0 1px 2px 0 #CCCCCC;
  margin-bottom: 1em;
  padding-bottom: 10px;
  padding: 4px 4px 10px;
}

div.pmagroup {
      background-image: url(<?php echo $_SESSION['PMA_Theme']->getImgPath(); ?>logo_right.png);
      background-position: <?php echo $right; ?> bottom;
  background-repeat: no-repeat;
}

.group .group-cnt {
  margin: 15px;
  display: block;
}

.group h2 {
  background-color: #666699;
  border-color: #BBBBDD #7777AA #9999CC;
  border-radius: 4px 4px 0 0;
  border-style: solid none;
  border-style: none;
  border-width: 1px;
  box-shadow: 0 1px 0 0 #F5F5F5, 0 0 0 1px #666699;
  box-shadow: 0 10px 15px -10px rgba(200, 200, 255, 0.4) inset, 0 0 0 1px #666699;
  color: #EEEEEE;
  font-size: 1.1em;
  font-weight: bold;
  margin-top: 0;
  padding: 0.3em 0.5em;
  box-shadow: none;
  border-radius: 3px;
}

fieldset .formelement {
  
  	margin-<?php echo $right; ?>: 40px;
  
}

.tabs_contents small {
  display: block;
  color: #222222;
  font-weight: normal;
  text-shadow: none;
  font-family: arial, sans-serif;
  letter-spacing: 1px;
  text-shadow: 0 1px 1px #ccc;
  color: #000;
}

.tabs_contents th {
  background-color: transparent;
  
  text-align: <?php echo $left; ?>; 
  
  width: 30%;
}

.cRsz {
  position: absolute;
}

.nowrap {
  white-space: nowrap;
}

#loading_parent {
  position: relative;
  width: 100%;
}

/* jqPlot */
/*rules for the plot target div.  These will be cascaded down to all plot elements according to css rules*/
.jqplot-target {
  position: relative;
  color: #222222;
  font-family: "Trebuchet MS", Arial, Helvetica, sans-serif;
  font-size: 1em;
  /*    height: 300px;
      width: 590px;*/
}

/*rules applied to all axes*/
.jqplot-axis {
  font-size: 0.75em;
}

.jqplot-xaxis {
  margin-top: 10px;
}

.jqplot-x2axis {
  margin-bottom: 10px;
}

.jqplot-yaxis {
  
  	margin-<?php echo $right; ?>: 10px;
  
}

.jqplot-y2axis, .jqplot-y3axis, .jqplot-y4axis, .jqplot-y5axis, .jqplot-y6axis, .jqplot-y7axis, .jqplot-y8axis, .jqplot-y9axis, .jqplot-yMidAxis {
  margin-left: 10px;
  margin-right: 10px;
}

/*rules applied to all axis tick divs*/
.jqplot-axis-tick, .jqplot-xaxis-tick, .jqplot-yaxis-tick, .jqplot-x2axis-tick, .jqplot-y2axis-tick, .jqplot-y3axis-tick, .jqplot-y4axis-tick, .jqplot-y5axis-tick, .jqplot-y6axis-tick, .jqplot-y7axis-tick, .jqplot-y8axis-tick, .jqplot-y9axis-tick, .jqplot-yMidAxis-tick {
  position: absolute;
  white-space: pre;
}

.jqplot-xaxis-tick {
  top: 0px;
  /* initial position untill tick is drawn in proper place */
  
      <?php echo $left; ?>: 15px;
  
  /*    padding-top: 10px;*/
  vertical-align: top;
}

.jqplot-x2axis-tick {
  bottom: 0px;
  /* initial position untill tick is drawn in proper place */
  
      <?php echo $left; ?>: 15px;
  
  /*    padding-bottom: 10px;*/
  vertical-align: bottom;
}

.jqplot-yaxis-tick {
  
  <?php echo $right; ?>: 0px;
  
  /* initial position untill tick is drawn in proper place */
  top: 15px;
  /*    padding-right: 10px;*/
  
      text-align: <?php echo $right; ?>;
  
}

.jqplot-yaxis-tick.jqplot-breakTick {
  padding: 1px 5px 1px 5px;
  
  	<?php echo $right; ?>: -20px;
  		margin-<?php echo $right; ?>: 0;
  
  /*	background-color: white;*/
  z-index: 2;
  font-size: 1.5em;
}

.jqplot-y2axis-tick, .jqplot-y3axis-tick, .jqplot-y4axis-tick, .jqplot-y5axis-tick, .jqplot-y6axis-tick, .jqplot-y7axis-tick, .jqplot-y8axis-tick, .jqplot-y9axis-tick {
  
      <?php echo $left; ?>: 0;
  
  /* initial position untill tick is drawn in proper place */
  top: 15px;
  /*    padding-left: 10px;*/
  /*    padding-right: 15px;*/
  
      text-align: <?php echo $left; ?>;
  
}

.jqplot-yMidAxis-tick {
  text-align: center;
  white-space: nowrap;
}

.jqplot-xaxis-label {
  margin-top: 10px;
  font-size: 11pt;
  position: absolute;
}

.jqplot-x2axis-label {
  margin-bottom: 10px;
  font-size: 11pt;
  position: absolute;
}

.jqplot-yaxis-label {
  
  	margin-<?php echo $right; ?>: 10px;
  
  /*    text-align: center;*/
  font-size: 11pt;
  position: absolute;
}

.jqplot-yMidAxis-label {
  font-size: 11pt;
  position: absolute;
}

.jqplot-y2axis-label, .jqplot-y3axis-label, .jqplot-y4axis-label, .jqplot-y5axis-label, .jqplot-y6axis-label, .jqplot-y7axis-label, .jqplot-y8axis-label, .jqplot-y9axis-label {
  /*    text-align: center;*/
  font-size: 11pt;
  
  	margin-<?php echo $left; ?>: 10px;
  
  position: absolute;
}

.jqplot-meterGauge-tick {
  font-size: 0.75em;
  color: #999999;
}

.jqplot-meterGauge-label {
  font-size: 1em;
  color: #999999;
}

table.jqplot-table-legend {
  margin-top: 12px;
  margin-bottom: 12px;
  margin-left: 12px;
  margin-right: 12px;
}

table.jqplot-table-legend, table.jqplot-cursor-legend {
  background-color: rgba(255, 255, 255, 0.6);
  border: 1px solid #cccccc;
  position: absolute;
  font-size: 0.75em;
}

td.jqplot-table-legend {
  vertical-align: middle;
}

/*
These rules could be used instead of assigning
element styles and relying on js object properties.
*/
/*
td.jqplot-table-legend-swatch {
    padding-top: 0.5em;
    text-align: center;
}

tr.jqplot-table-legend:first td.jqplot-table-legend-swatch {
    padding-top: 0px;
}
*/
td.jqplot-seriesToggle:hover, td.jqplot-seriesToggle:active {
  cursor: pointer;
}

.jqplot-table-legend .jqplot-series-hidden {
  text-decoration: line-through;
}

div.jqplot-table-legend-swatch-outline {
  border: 1px solid #cccccc;
  padding: 1px;
}

div.jqplot-table-legend-swatch {
  width: 0px;
  height: 0px;
  border-top-width: 5px;
  border-bottom-width: 5px;
  border-left-width: 6px;
  border-right-width: 6px;
  border-top-style: solid;
  border-bottom-style: solid;
  border-left-style: solid;
  border-right-style: solid;
}

.jqplot-title {
  top: 0px;
  <?php echo $left?>: 0px;
  padding-bottom: 0.5em;
  font-size: 1.2em;
}

table.jqplot-cursor-tooltip {
  border: 1px solid #cccccc;
  font-size: 0.75em;
}

.jqplot-cursor-tooltip {
  border: 1px solid #cccccc;
  font-size: 0.75em;
  white-space: nowrap;
  background: rgba(208, 208, 208, 0.5);
  padding: 1px;
}

.jqplot-highlighter-tooltip, .jqplot-canvasOverlay-tooltip {
  border: 1px solid #cccccc;
  font-size: 0.75em;
  white-space: nowrap;
  background: rgba(208, 208, 208, 0.5);
  padding: 1px;
}

.jqplot-point-label {
  font-size: 0.75em;
  z-index: 2;
}

td.jqplot-cursor-legend-swatch {
  vertical-align: middle;
  text-align: center;
}

div.jqplot-cursor-legend-swatch {
  width: 1.2em;
  height: 0.7em;
}

.jqplot-error {
  /*   Styles added to the plot target container when there is an error go here.*/
  text-align: center;
}

.jqplot-error-message {
  /*    Styling of the custom error message div goes here.*/
  position: relative;
  top: 46%;
  display: inline-block;
}

div.jqplot-bubble-label {
  font-size: 0.8em;
  /*    background: rgba(90%, 90%, 90%, 0.15);*/
  padding-left: 2px;
  padding-right: 2px;
  color: #333333;
}

div.jqplot-bubble-label.jqplot-bubble-label-highlight {
  background: rgba(229, 229, 229, 0.7);
}

div.jqplot-noData-container {
  text-align: center;
  background-color: rgba(244, 244, 244, 0.3);
}

/* profiling */
div#profilingchart {
  
  	float: <?php echo $left; ?>;
  
  width: 550px;
  height: 500px;
  
  	margin-<?php echo $left; ?>: 20px;
  
}

div#profilingchart * {
  box-shadow: none;
}

/* END profiling */
form#addColumns .icon {
  margin-top: 10px;
}

h3#serverstatusqueries span {
  display: inline;
  font-size: 60%;
}

	<?php if ($GLOBALS['cfg']['MainPageIconic']) { ?> 
/* iconic view for ul items */
li#li_create_database {
      list-style-image: url(<?php echo $_SESSION['PMA_Theme']->getImgPath(); ?>b_newdb.png); 
}

li#li_select_lang {
      list-style-image: url(<?php echo $_SESSION['PMA_Theme']->getImgPath(); ?>s_lang.png); 
}

li#li_select_mysql_collation {
      list-style-image: url(<?php echo $_SESSION['PMA_Theme']->getImgPath(); ?>s_asci.png); 
}

li#li_select_theme {
      list-style-image: url(<?php echo $_SESSION['PMA_Theme']->getImgPath(); ?>s_theme.png); 
}

li#li_user_info {
  /* list-style-image: url(<?php echo $_SESSION['PMA_Theme']->getImgPath(); ?>s_rights.png); */
}

li#li_mysql_status {
      list-style-image: url(<?php echo $_SESSION['PMA_Theme']->getImgPath(); ?>s_status.png); 
}

li#li_mysql_variables {
      list-style-image: url(<?php echo $_SESSION['PMA_Theme']->getImgPath(); ?>s_vars.png); 
}

li#li_mysql_processes {
      list-style-image: url(<?php echo $_SESSION['PMA_Theme']->getImgPath(); ?>s_process.png); 
}

li#li_mysql_collations {
      list-style-image: url(<?php echo $_SESSION['PMA_Theme']->getImgPath(); ?>s_asci.png); 
}

li#li_mysql_engines {
      list-style-image: url(<?php echo $_SESSION['PMA_Theme']->getImgPath(); ?>b_engine.png); 
}

li#li_mysql_binlogs {
      list-style-image: url(<?php echo $_SESSION['PMA_Theme']->getImgPath(); ?>s_tbl.png); 
}

li#li_mysql_databases {
      list-style-image: url(<?php echo $_SESSION['PMA_Theme']->getImgPath(); ?>s_db.png); 
}

li#li_export {
      list-style-image: url(<?php echo $_SESSION['PMA_Theme']->getImgPath(); ?>b_export.png); 
}

li#li_import {
      list-style-image: url(<?php echo $_SESSION['PMA_Theme']->getImgPath(); ?>b_import.png); 
}

li#li_change_password {
      list-style-image: url(<?php echo $_SESSION['PMA_Theme']->getImgPath(); ?>s_passwd.png); 
}

li#li_log_out {
      list-style-image: url(<?php echo $_SESSION['PMA_Theme']->getImgPath(); ?>s_loggoff.png); 
}

li#li_mysql_privilegs {
      list-style-image: url(<?php echo $_SESSION['PMA_Theme']->getImgPath(); ?>s_rights.png); 
}

li#li_switch_dbstats {
      list-style-image: url(<?php echo $_SESSION['PMA_Theme']->getImgPath(); ?>b_dbstatistics.png); 
}

li#li_flush_privileges {
      list-style-image: url(<?php echo $_SESSION['PMA_Theme']->getImgPath(); ?>s_reload.png); 
}

li#li_user_preferences {
      list-style-image: url(<?php echo $_SESSION['PMA_Theme']->getImgPath(); ?>b_tblops.png); 
}

/* END iconic view for ul items */
 <?php
} // end if $GLOBALS['cfg']['MainPageIconic'] 
 ?> 
body#bodythemes {
  background-color: #444;
  text-align: center;
  box-shadow: 0 0 10px 5px #000000 inset;
  margin: 0;
  padding: 0 10px 20px;
  border-radius: 6px 6px  0 0;
  border-bottom: 6px solid #669;
}
  body#bodythemes a {
    background: none repeat scroll 0 0 #666699;
    background: none repeat scroll 0 0 #000;
    border: 4px solid #000;
    border-radius: 4px 4px 4px 4px;
    color: #DDDDDD;
    display: inline-block;
    overflow: hidden;
    text-align: center;
    margin: 0 3px;
}
    body#bodythemes a strong {
      padding: 5px;
      display: inline-block;
}
    body#bodythemes a:hover {
      color: orange;
}
    body#bodythemes a img {
      display: block;
}
  body#bodythemes h1, body#bodythemes h2 {
    text-align: center;
    margin: 0;
    color: #aaaaaa;
}
  body#bodythemes h1 {
    color: #FFBB22;
    font-style: italic;
    text-shadow: 0px -1px 1px #ffdb42, 0 1px 2px black;
    padding: 20px 0 10px;
}
  body#bodythemes h2 {
    padding: 5px;
}
  body#bodythemes .theme_preview {
    background-color: #000;
    display: inline-block;
    border-radius: 6px 6px 4px 4px;
    margin: 0 10px 20px;
    text-align: center;
}
    body#bodythemes .theme_preview p {
      margin: 0;
      padding: 0;
}
  body#bodythemes br {
    display: none;
}
