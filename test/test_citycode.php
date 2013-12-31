<?php

$url = 'http://api.map.baidu.com/library/CityList/1.2/src/data/CityData.js?t=1354686024700';

$s = file_get_contents($url);

# unpack js statement
$j = substr($s, strpos($s, '=')+1, -1);
$b = json_decode_nice($j, true);
//var_dump($b);

$cities = array();

foreach ($b['municipalities'] as $city) {
	$cities[] = $city;
}

foreach ($b['provinces'] as $province) {
	foreach ($province['cities'] as $city) {
		$cities[] = $city;
	}
}

foreach ($b['other'] as $city) {
	$cities[] = $city;
}

$city_names = array();

foreach ($cities as $city) {
	$url = 'http://map.baidu.com/?newmap=1&reqflag=pcmap&biz=1&qt=cur&curtp=2&wd='.urlencode($city['n']).'&ie=utf-8&l=12';
	$json = file_get_contents($url);
	$j = json_decode($json, true);
	var_dump($j);
	$city_names[] = $j['cur_area_name'];
}

ksort($city_names);

#var_dump($city_names, count($city_names));

function json_decode_nice($json, $assoc = FALSE){
    $json = str_replace(array("\n","\r"),"",$json);
    $json = preg_replace('/([{,]+)(\s*)([^"]+?)\s*:/','$1"$3":',$json);
    return json_decode($json,$assoc);
}



/*
$json = '{aaa:[{a:"asdf",b:{c:1,d:2},c:{c:1,d:2},{a:"asdf",b:{c:1,d:2},c:{c:1,d:2},{a:"asdf",b:{c:1,d:2},c:{c:1,d:2}]}';
    $json = preg_replace('/([{,]+)(\s*)([^"]+?)\s*:/','$1"$3":',$json);
var_dump($json);

*/