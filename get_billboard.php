<?php
error_reporting(E_ALL^ E_WARNING);
if (!isset($_GET['end']) || !isset($_GET['start'])) {
	die("HTTP 400 Bad Request");
}

$END = (int)$_GET['end'];
$START = (int)$_GET['start'];

//header("Content-type: text/json");
$target_url = "http://www.billboard.com/charts/hot-100";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $target_url);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
curl_setopt($ch, CURLOPT_RETURNTRANSFER,  true); 

$file = curl_exec($ch);

$doc = new DOMDocument();
$doc->loadHTML($file);

$finder = new DomXPath($doc);

$chart = array();

for ($i = $START - 1; $i < $END; ++$i) {
	$chart_item = array();

	$row_id = "row-".($i + 1);
	$parent_node_tmp = $finder->query("//article[@id='$row_id']");
	$parent_node = $parent_node_tmp->item(0);
	//print_r($parent_node->getAttribute("id"));

	/* Get ranks */
	$rank_node = $finder->query(".//span[@class='this-week']", $parent_node);
	$tmp = $rank_node->item(0);
	$rank = (int)trim($tmp->nodeValue);
	$rank_node = $finder->query(".//span[@class='last-week']", $parent_node);
	$tmp = $rank_node->item(0);
	$last_rank_strings = explode(":", trim($tmp->nodeValue));
	$last_rank_val = trim($last_rank_strings[1]);
	if (!is_numeric($last_rank_val))
		$last_rank = -1;
	else
		$last_rank = (int)$last_rank_val;

	/* Get title */
	$title_node = $finder->query(".//div[@class='row-title']", $parent_node);
	$tmp = $title_node->item(0);
	$title_tmp = $tmp->getElementsByTagName("h2");
	$title_node_c = $title_tmp->item(0);
	$title = trim($title_node_c->nodeValue);

	/* Get Artist */
	$artist_node = $finder->query(".//a[@data-tracklabel='Artist Name']", $parent_node);
	$artist_tmp = $artist_node->item(0);
	$artist = $artist_tmp->nodeValue;

	/* Get Image Url */
	$img_url_node = $finder->query(".//div[@class='row-image']", $parent_node);
	$img_url_tmp = $img_url_node->item(0);
	if ($img_url_tmp->hasAttribute("style")) {
		preg_match("/(http\:\/\/[a-zA-Z0-9\-\.]+\.[a-zA-Z]{2,3}(?:\/\S*)?(?:[a-zA-Z0-9_])+\.(?:jpg|jpeg|gif|png))/", $img_url_tmp->getAttribute("style"), $matches);
		$img_url = $matches[0];
	}
	else if ($img_url_tmp->hasAttribute("data-imagesrc")){
		$img_url = $img_url_tmp->getAttribute("data-imagesrc");
	}

	/* Set up chart attribute */
	$chart_item['rank'] = $rank;
	$chart_item['title'] = trim($title);
	$chart_item['last_rank'] = trim($last_rank);
	$chart_item['artist'] = trim($artist);
	$chart_item['img_url'] = $img_url;

	/* Add single chart to json array */
	$chart[] = $chart_item;
}

print json_encode($chart);
?>