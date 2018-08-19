<?php
use PhpMyAdmin\Response;
require_once 'libraries/common.inc.php';

$response = Response::getInstance();

$url = "https://query.yahooapis.com/v1/public/yql?q=select%20*%20from%20weather.forecast%20where%20woeid%20in%20(select%20woeid%20from%20geo.places(1)%20where%20text%3D%22melbourne%22)&format=xml&env=store%3A%2F%2Fdatatables.org%2Falltableswithkeys";
$invalidurl = false;

if (@simplexml_load_file($url))
{
  $weather = simplexml_load_file($url);
}else {
  $invalidurl = true;
  echo "<h3>Invalid URL.</h3>";
}

if(!empty($weather))
{

 foreach ($weather->results->channel->item as $item) {

  $title = $item->title;
  $link = $item->link;
  $description = $item->description;

?>
 <div >
 <div >
 <h3><a href="<?php echo $link; ?>"><?php echo $title; ?></a></h3>

 </div>
 <div >
 <?php echo implode(' ', explode(' ', trim($description, "]]>"))); ?>
 </div>
 </div>

<?php

 }
}else{
 if(!$invalidurl){
 echo "<h2>No item found</h2>";
 }
}
