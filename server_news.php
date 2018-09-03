<?php
use PhpMyAdmin\Response;
require_once 'libraries/common.inc.php';

$response = Response::getInstance();

/* Weather */
$weatherurl = "https://query.yahooapis.com/v1/public/yql?q=select%20*%20from%20weather.forecast%20where%20woeid%20in%20(select%20woeid%20from%20geo.places(1)%20where%20text%3D%22melbourne%22)&format=xml&env=store%3A%2F%2Fdatatables.org%2Falltableswithkeys";
$invalidurl = false;

if (@simplexml_load_file($weatherurl)) {
  $weather = simplexml_load_file($weatherurl);
} else {
  $invalidurl = true;
  echo "<h3>Invalid URL.</h3>";
}

if(!empty($weather)) {
 foreach ($weather->results->channel->item as $item) {

  $title = $item->title;
  $link = $item->link;
  $description = $item->description;

?>
<div>
  <div>
    <h2><b>Weather</b></h2>
    <h3><a href="<?php echo $link; ?>"><?php echo $title; ?></a></h3>
  </div>
  <div>
    <?php echo implode(' ', explode(' ', trim($description, "]]>"))); ?>
    <h2><b>Sport News</b></h2>
  </div>
</div>

<?php

 }
} else{
 if(!$invalidurl) {
   echo "<h2>No item found</h2>";
 }
}

/* News */
$country = "au";
$category = "sport";
$apikey = "32fc838e51be4a5fbe3f76adc02dd166";
$url = "https://newsapi.org/v2/top-headlines?country=". $country ."&category=".$category . "&apiKey=".$apikey;
$ch = curl_init();
curl_setopt($ch,CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_HEADER, 0);
$resp = curl_exec($ch);
curl_close($ch);
$json = json_decode($resp);
foreach($json->articles as $obj){
$name = $obj->source->name;
$author = $obj->author;
$title = $obj->title;
$description = $obj->description;
$url = $obj->url;
$datePublished = $obj->publishedAt;

$time = strtotime($datePublished.' UTC');
date_default_timezone_set("Australia/Melbourne");
$post_date = date("l, M d, Y", $time);
$post_time = date("h:i a", $time);
echo "<div class='container'>";
echo "<a href='$url'  target='_blank'>
<div class='row'>
    <div class='col-sm-8'>
        <h3> $title </h3>$description <BR> <span class='badge'>$post_date at $post_time</span>
    </div>
</div>
</a>";
echo "<hr>";
echo "</div>";
}
